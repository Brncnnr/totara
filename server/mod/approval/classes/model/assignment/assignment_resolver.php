<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\model\assignment;

use core\entity\user;
use core\orm\collection;
use core\orm\query\builder;
use mod_approval\entity\assignment\assignment as assignment_entity;
use mod_approval\entity\workflow\workflow;
use mod_approval\entity\workflow\workflow_type;
use mod_approval\entity\workflow\workflow_version;
use mod_approval\model\assignment\assignment as assignment_model;
use mod_approval\interactor\assignment_interactor;
use mod_approval\model\assignment\assignment_type\provider;
use mod_approval\model\assignment\assignment_type\cohort;
use mod_approval\model\assignment\assignment_type\organisation;
use mod_approval\model\assignment\assignment_type\position;
use mod_approval\model\status;
use mod_approval\model\workflow\workflow_type as workflow_type_model;
use mod_approval\webapi\schema_object\new_application_menu_item as new_application_menu_item_so;
use totara_job\job_assignment;

/** @var core_config $CFG */
require_once($CFG->dirroot.'/cohort/lib.php');

/**
 * Assignment_resolver class to resolve assignments for applicants, approvers, and others.
 *
 * @package mod_approval
 */
class assignment_resolver {

    /** @var user */
    protected $applicant;

    /** @var user */
    protected $creator;

    /** @var workflow_type_model */
    protected $workflow_type;

    /** @var collection|assignment[] */
    protected $assignments;

    /** @var collection|new_application_menu_item_so[] */
    protected $menu_items;

    /**
     * Assignment_resolver constructor.
     *
     * @param user $applicant The intended applicant
     * @param user $creator The user who is creating the application
     * @param workflow_type_model|null $workflow_type Optional workflow_type to filter on
     */
    public function __construct(user $applicant, user $creator, workflow_type_model $workflow_type = null) {
        $this->applicant = $applicant;
        $this->creator = $creator;
        $this->workflow_type = $workflow_type;
        $this->assignments = new collection();
        $this->menu_items = new collection();
    }

    /**
     * Loads a user's workflow assignments and new application menu items.
     */
    public function resolve(): void {
        // For each job assignment, find any matching approval_workflow assignments by organisation or position
        $job_assignments = job_assignment::get_all($this->applicant->id);
        foreach ($job_assignments as $job_assignment) {
            $job_assignment_name = $this->name_from_job_assignment($job_assignment);
            if (!empty($job_assignment->organisationid)) {
                $this->resolve_hierarchical_assignments(
                    organisation::get_code(),
                    $job_assignment->organisationid,
                    $job_assignment_name,
                    $job_assignment->id
                );
            }
            if (!empty($job_assignment->positionid)) {
                $this->resolve_hierarchical_assignments(
                    position::get_code(),
                    $job_assignment->positionid,
                    $job_assignment_name,
                    $job_assignment->id
                );
            }
        }

        // For each audience, find any matching approval_workflow assignments
        $cohorts = totara_cohort_get_user_cohorts($this->applicant->id);
        $this->resolve_cohort_assignments($cohorts, $job_assignments);
    }

    /**
     * @return collection|assignment[]
     */
    public function get_assignments(): collection {
        return $this->assignments;
    }

    /**
     * @return collection|new_application_menu_item_so[]
     */
    public function get_menu_items(): collection {
        return $this->menu_items;
    }

    /**
     * Determine an appropriate name for a job assignment.
     *
     * @param job_assignment $job_assignment
     * @return string
     */
    private function name_from_job_assignment(job_assignment $job_assignment): string {
        return $job_assignment->fullname ?? $job_assignment->idnumber;
    }

    /**
     * Gets a collection of possible assignments, given an assignment type and an array of identifier ids.
     *
     * @param int $assignment_type
     * @param array|int[] $identifier_ids
     * @return collection|assignment[]
     */
    private function possible_assignments(int $assignment_type, array $identifier_ids): collection {
        $repository = assignment_entity::repository()
            ->join('course', 'course', '=', 'course.id')
            ->join([workflow::TABLE, 'w'], 'course.id', '=', 'w.course_id')
            ->join([workflow_version::TABLE, 'v'], function (builder $builder) {
                return $builder->where_field('workflow_id', '=', 'w.id')
                    ->where('status', '=', status::ACTIVE);
            })
            ->where('status', '=', status::ACTIVE)
            ->where('assignment_type', '=', $assignment_type)
            ->where('assignment_identifier', 'in', $identifier_ids)
            ->order_by('id');
        if (!is_null($this->workflow_type)) {
            $repository->join([workflow_type::TABLE, 'wt'], 'w.workflow_type_id', '=', 'wt.id');
            $repository->where('wt.id', '=', $this->workflow_type->id);
        }
        return $repository->get()->map_to(assignment::class);
    }

    /**
     * Given a hierarchical entity (by type and id), loads assignments matching the entity and its
     * parents, and finds the one with the longest path -- that is, the one closest to the specified entity
     * in the tree -- for each distinct workflow_type.
     *
     * @param int $type assignment_type code
     * @param int $identifier entity id
     * @param string $job_assignment_name
     * @param int $job_assignment_id
     */
    private function resolve_hierarchical_assignments(int $type, int $identifier, string $job_assignment_name, int $job_assignment_id): void {
        $entity = provider::get_by_code($type)::instance($identifier)->get_entity();

        // Get the hierarchy, minus the framework at the top.
        $hierarchy = explode('/', $entity->path);
        unset($hierarchy[0]);

        // Get all possible assignments in this hierarchy.
        $possibles = $this->possible_assignments($type, $hierarchy);

        // Now find the closest (that is, the deepest) assignment to the original entity for each workflow_type.
        $depths = [];
        $closest = [];
        foreach ($possibles as $assignment) {
            if (!$this->can_create_application($assignment)) {
                continue;
            }
            $depth = count(explode('/', $assignment->assigned_to->path));
            $workflow_type_id = $assignment->workflow->workflow_type_id;
            if (!isset($depths[$workflow_type_id])) {
                $depths[$workflow_type_id] = $depth;
                $closest[$workflow_type_id] = $assignment;
            } else if ($depth > $depths[$workflow_type_id]) {
                $depths[$workflow_type_id] = $depth;
                $closest[$workflow_type_id] = $assignment;
            }
        }

        // Now append to our collections.
        foreach ($closest as $assignment) {
            $this->assignments->append($assignment);
            $menu_item = new new_application_menu_item_so($assignment->id, $assignment->workflow_type, $job_assignment_name, $job_assignment_id);
            $this->menu_items->append($menu_item);
        }
    }

    /**
     * Given a list of cohort ids, find eligible assignments that match.
     *
     * @param array $cohort_ids
     * @param array $job_assignments
     */
    private function resolve_cohort_assignments(array $cohort_ids, array $job_assignments): void {
        // Get all possible assignments for these cohort ids.
        $possibles = $this->possible_assignments(cohort::get_code(), $cohort_ids);

        // Now append to our collections.
        $workflow_types = [];
        foreach ($possibles as $assignment) {
            if (!$this->can_create_application($assignment)) {
                continue;
            }
            $workflow_type_id = $assignment->workflow->workflow_type_id;
            if (empty($workflow_types[$workflow_type_id])) {
                $workflow_types[$workflow_type_id] = $assignment->id;
                $this->assignments->append($assignment);
                // If user has job assignments, include one cohort menu item entry per assignment, as they will need to pick.
                if (count($job_assignments)) {
                    foreach ($job_assignments as $job_assignment) {
                        $job_assignment_name = $this->name_from_job_assignment($job_assignment);
                        $menu_item = new new_application_menu_item_so($assignment->id, $assignment->workflow_type, $job_assignment_name, $job_assignment->id);
                        $this->menu_items->append($menu_item);
                    }
                } else {
                    $menu_item = new new_application_menu_item_so($assignment->id, $assignment->workflow_type);
                    $this->menu_items->append($menu_item);
                }
            }
        }
    }

    /**
     * Can create an application for the applicant.
     * CA02, CU02, CO02
     *
     * @param assignment_model $assignment
     * @return bool
     */
    public function can_create_application(assignment_model $assignment): bool {
        return (new assignment_interactor(
                $assignment->get_context(),
                $this->applicant->id,
                $this->creator->id))
            ->can_create_application();
    }
}