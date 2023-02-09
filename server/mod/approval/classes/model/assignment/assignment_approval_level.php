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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\model\assignment;

use core\entity\cohort as cohort_entity;
use core\orm\collection;
use core\orm\query\builder;
use mod_approval\entity\assignment\assignment as assignment_entity;
use mod_approval\entity\assignment\assignment_approver as assignment_approver_entity;
use mod_approval\model\assignment\approver_type\user as approver_type_user;
use mod_approval\model\assignment\assignment_type\provider;
use mod_approval\model\assignment\assignment_type\cohort;
use mod_approval\model\assignment\assignment_type\organisation;
use mod_approval\model\assignment\assignment_type\position;
use mod_approval\model\console_trait;
use mod_approval\model\status;
use mod_approval\model\workflow\workflow_stage_approval_level;
use moodle_transaction;
use totara_hierarchy\entity\hierarchy_item;

/**
 * Class workflow_assignment
 *
 * Properties:
 * @property-read assignment assignment
 * @property-read workflow_stage_approval_level approval_level
 * @property-read assignment_approver[] approvers
 * @property-read assignment_approver[] approvers_with_inheritance
 * @property-read assignment_approval_level inherited_from_assignment_approval_level
 *
 * @package mod_approval\data_provider\assignment
 */
class assignment_approval_level {

    use console_trait;

    /**
     * @var assignment
     */
    private $assignment;

    /**
     * @var workflow_stage_approval_level
     */
    private $approval_level;

    /**
     * @var assignment_approver[]
     */
    private $approvers_cache = null;

    /**
     * @var bool Whether to ignore draft assignments (true) or not (false)
     */
    private $activemode = false;

    /**
     * @var int Maximum number of ineligible inheriting assignments that can be filtered using SQL query
     *
     *  To determine which assignments inherit from this one, we take the tree of all possible
     *  inheriting assignments, and then subtract all the trees where a direct appover is set. For a resonable
     *  number of ineligible assignments, this can be done in a single SQL query. For a large number of
     *  ineligible assignments, it is necessary to use PHP to filter the tree by looping through it (could be
     *  parallelized?).
     */
    private const INELIGIBLE_ASSIGNMENT_OPTIMIZATION_BREAKPOINT = 32;

    /**
     * @param assignment $assignment
     * @param workflow_stage_approval_level $approval_level
     */
    public function __construct(
        assignment $assignment,
        workflow_stage_approval_level $approval_level
    ) {
        $this->assignment = $assignment;
        $this->approval_level = $approval_level;
    }

    /**
     * Get value of activemode setting.
     *
     * @return bool
     */
    public function get_activemode(): bool {
        return $this->activemode;
    }

    /**
     * Turn activemode on or off.
     *
     * Activemode ignores approvers on draft assignments when resolving inheritance.
     *
     * @param bool $activemode
     * @return bool
     */
    public function set_activemode(bool $activemode): bool {
        if ($this->activemode !== $activemode) {
            $this->activemode = $activemode;
        }
        return $this->activemode;
    }

    /**
     * @param assignment_approver[] $approvers
     */
    public function set_approvers_cache(array $approvers): void {
        $this->approvers_cache = $approvers;
    }

    /**
     * @return assignment
     */
    public function get_assignment(): assignment {
        return $this->assignment;
    }

    /**
     * @return workflow_stage_approval_level
     */
    public function get_approval_level(): workflow_stage_approval_level {
        return $this->approval_level;
    }

    /**
     * Get the approvers for this assignment and approval level.
     *
     * Note that this returns the raw set of approvers, and doesn't compute inheritance if there are no
     * inherited approvers in the database (as on a draft activity for example).
     *
     * Use get_approvers_with_inheritance to always get approvers from an ancestor assignment approval level if there
     * are none defined for this one.
     *
     * @param bool $include_inherited Default is to only return direct approvers
     * @return assignment_approver[]
     */
    public function get_approvers(bool $include_inherited = false): array {
        // If there IS an approvers_cache already, return it. But do not set one here.
        if (!is_null($this->approvers_cache)) {
            return $this->approvers_cache;
        }
        // Load from the repository.
        $approvers = assignment_approver_entity::repository()
            ->where('approval_id', $this->assignment->id)
            ->where('workflow_stage_approval_level_id', $this->approval_level->id)
            ->where('active', '=', true);
        if (!$include_inherited) {
            $approvers->where_null('ancestor_id');
        }
        return $approvers->get()
            ->map_to(assignment_approver::class)
            ->all();
    }

    /**
     * Get the approvers that are applicable for this assignment and approval level.
     *
     * Note that this returns the inherited approvers if there are no approvers defined for this
     * assignment and approval level.
     *
     * This set of approvers will be empty if no approvers are defined for this assignment and approval level
     * and all ancestor assignments (if any) also do not define any approvers.
     *
     * @return assignment_approver[]
     */
    public function get_approvers_with_inheritance(): array {
        // If this is the default assignment, or if this assignment and level has direct or inherited approvers,
        // then there is no need to get approvers from an ancestor.
        $approvers = $this->get_approvers(true);
        if (!empty($approvers) || $this->assignment->is_default) {
            return $approvers;
        }

        // We need to find an ancestor that has approvers, or default.
        $inherit_from_assignment_approval_level = $this->get_inherited_from_assignment_approval_level();
        if (is_null($inherit_from_assignment_approval_level)) {
            $approvers_with_inheritance = [];
        } else {
            $approvers_with_inheritance = $inherit_from_assignment_approval_level->get_approvers();
        }

        return $approvers_with_inheritance;
    }

    /**
     * Finds the assignment_approval_level from which this one inherits its approvers.
     *
     * This could be an ancestor in an organisation or position framework, or if there are no ancestor assignments with
     * approvers or this is a cohort assignment, then it inherits from the default assignment.
     *
     * Returns null if there is no inheritance because this level has direct approvers, or the assignment is default.
     *
     * @return assignment_approval_level|null
     */
    public function get_inherited_from_assignment_approval_level(): ?assignment_approval_level {
        // Obviously, if there are approvers then there is no need to inherit.
        if (!empty($this->get_approvers())) {
            return null;
        }
        // Find the ancestor of this assignment.
        return $this->get_ancestor_assignment_approval_level();
    }

    /**
     * Finds the assignment_approval_level from which this one would inherit approvers if it had no
     * approvers of its own.
     *
     * @return assignment_approval_level|null
     */
    public function get_ancestor_assignment_approval_level(): ?assignment_approval_level {
        // If this is default assignment, there is nowhere to inherit from.
        if ($this->assignment->is_default) {
            return null;
        }

        $default_assignment_approval_level = new assignment_approval_level(
            $this->assignment->workflow->default_assignment,
            $this->approval_level
        );
        $assigned_to = $this->assignment->get_assigned_to();

        // Audience assignments inherit from the default assignment.
        if (get_class($assigned_to) == cohort_entity::class) {
            return $default_assignment_approval_level;
        }

        if (!is_subclass_of($assigned_to, hierarchy_item::class)) {
            throw new \coding_exception('Unknown assignment type class');
        }

        /** @var hierarchy_item $hierarchy */
        $hierarchy = $assigned_to;
        $ancestor_items = $hierarchy->crumbtrail;
        $ancestor_ids = [];

        foreach ($ancestor_items as $ancestor_item) {
            // Skip frameworks.
            if ($ancestor_item['type'] === 'framework') {
                continue;
            }

            // Skip the assignment's own hierarchy item.
            if ($ancestor_item['id'] == $this->assignment->assignment_identifier) {
                continue;
            }

            $ancestor_ids[] = $ancestor_item['id'];
        }

        // There are no ancestor hierarchy items.
        if (empty($ancestor_ids)) {
            return $default_assignment_approval_level;
        }

        $ancestor_assignments_query = builder::table(assignment_entity::TABLE, 'assignment')
            ->where_in('assignment_identifier', $ancestor_ids)
            ->where('assignment_type', $this->assignment->assignment_type)
            ->where('course', $this->assignment->course_id)
            ->where('status', '!=', status::ARCHIVED);

        if ($this->get_activemode()) {
            $ancestor_assignments_query->where('status', '!=', status::DRAFT);
        }

        $ancestor_assignments = $ancestor_assignments_query
            ->get()
            ->map_to(assignment_entity::class);

        // There are ancestor hierarchy items, but none of them have assignments.
        if (empty($ancestor_assignments)) {
            return $default_assignment_approval_level;
        }

        // Search the ancestor assignments that were discovered in the order of the hierarchy.
        foreach (array_reverse($ancestor_ids) as $ancestor_id) {
            /** @var assignment_entity $ancestor_assignment */
            foreach ($ancestor_assignments as $ancestor_assignment) {
                if ($ancestor_assignment->assignment_identifier != $ancestor_id) {
                    // Not the assignment we are looking for this time around.
                    continue;
                }

                $ancestor_assignment_model = assignment::load_by_entity($ancestor_assignment);
                $ancestor_assignment_approval_level = new assignment_approval_level(
                    $ancestor_assignment_model,
                    $this->approval_level
                );

                // If there are approvers in this assignment approval level then this it the one we are looking for!
                if (!empty($ancestor_assignment_approval_level->get_approvers())) {
                    return $ancestor_assignment_approval_level;
                }

                // No need to continue with this $ancestor_id, we've already found it in the assignment list.
                continue 2;
            }
        }

        return $default_assignment_approval_level;
    }

    /**
     * Finds all of the descendant assignment_approval_levels where there are no uninherited approvers.
     *
     * @return collection|assignment_approval_level[]
     */
    public function get_descendants(): collection {
        // For cohort assignments, only the default assignment can have descendants.
        if ($this->assignment->assignment_type == cohort::get_code() && !$this->assignment->is_default) {
            return new collection();
        }

        // First, get any descendent assignments where the same level is set directly, rather than inherited -- these are ineligible.
        // (We don't need to do this for cohort assignments.)
        $ineligible_assignments = new collection();
        if ($this->assignment->assignment_type !== cohort::get_code()) {
            $assignee = $this->assignment->assigned_to;
            $assignee_entity_table = provider::get_by_code($this->assignment->assignment_type)
                ::instance($this->assignment->assignment_identifier)
                ->get_entity()::TABLE;
            $ineligible_assignments_builder = builder::table('approval')
                ->select_raw('DISTINCT approval.id, hier.path')
                ->join(['approval_approver', 'approver'], function (builder $joining) {
                    $joining->where_field('approval_id', '=', 'approval.id')
                        ->where('active', '=', true)
                        ->where('workflow_stage_approval_level_id', '=', $this->approval_level->id)
                        ->where_null('ancestor_id');
                })
                ->join([$assignee_entity_table, 'hier'], 'hier.id', '=', 'approval.assignment_identifier')
                ->where_like_raw('hier.path', $assignee->path . '/%')
                ->where('approval.assignment_type', '=', $this->assignment->assignment_type)
                ->where('approval.course', '=', $this->assignment->course_id)
                ->where('approval.status', '!=', status::ARCHIVED);

            if ($this->get_activemode()) {
                $ineligible_assignments_builder->where('approval.status', '!=', status::DRAFT);
            }
            $ineligible_assignments = $ineligible_assignments_builder->get();
        }

        // Now find assignments where the same level is not set (might already have other inherited approvers, that's ok).
        $potential_assignments_builder = assignment_entity::repository()
            ->where('assignment_type', '=', $this->assignment->assignment_type)
            ->where('is_default', '=', false)
            ->where('course', '=', $this->assignment->course_id)
            ->where('status', '!=', status::ARCHIVED)
            ->left_join(['approval_approver', 'approver'], function (builder $joining) {
                $joining->where('approver.workflow_stage_approval_level_id', '=', $this->approval_level->id)
                    ->where_null('approver.ancestor_id')
                    ->where('approver.active', '=', true)
                    ->where_field('approver.approval_id', '=', 'approver.id');
            });

        if ($this->get_activemode()) {
            $potential_assignments_builder->where('status', '!=', status::DRAFT);
        }

        // For hierarchical assignments, do not include any assignments that are descendants
        //     of any of the ineligible assignments.
        switch ($this->assignment->assignment_type) {
            case organisation::get_code():
            case position::get_code():
                $potential_assignments_builder->join([$assignee_entity_table, 'hier'], 'hier.id', '=', 'approval.assignment_identifier')
                    ->where_like_raw('hier.path', $assignee->path . '/%')
                    ->where('approval.assignment_type', '=', $this->assignment->assignment_type);
                // If there are only a few ineligible assignments, filter them out in the query.
                if ($ineligible_assignments->count() < self::INELIGIBLE_ASSIGNMENT_OPTIMIZATION_BREAKPOINT) {
                    foreach ($ineligible_assignments as $ineligible_assignment) {
                        $potential_assignments_builder->where('hier.path', '!like_raw', $ineligible_assignment->path . '%');
                    }
                    // All potential assignments are inheriting.
                    $inheriting_assignments = $potential_assignments_builder->get()->map_to(assignment::class);
                }
                else {
                    // There are a lot of ineligible assignments? Like, tens of thousands?
                    // Then the above won't work, and we need to load all of the potentials and then filter out the ineligbles
                    //   using PHP.
                    $potential_assignments_builder->select_raw('approval.*, hier.path');
                    $potential_assignments = $potential_assignments_builder->get();
                    // There is almost certainly a way to make this more performant...
                    foreach ($ineligible_assignments as $ineligible) {
                        $potential_assignments = $potential_assignments->filter(function ($item) use ($ineligible) {
                            return !(substr($item->path, 0, strlen($ineligible->path)) == $ineligible->path);
                        });
                    }
                    $inheriting_assignments = $potential_assignments->map_to(assignment::class);
                }
                break;
            default:
                // For cohort assignments, all potentials are also inheriting.
                $inheriting_assignments = $potential_assignments_builder->get()->map_to(assignment::class);
        }

        // Add each inheriting assignment approval level to our collection.
        $collection = new collection();
        foreach ($inheriting_assignments as $inheriting_assignment) {
            $collection->append(new assignment_approval_level($inheriting_assignment, $this->approval_level));
        }
        return $collection;
    }

    /**
     * Deactivates inherited approvers on this assignment_approval_level, and descendant levels.
     *
     * @param moodle_transaction $transaction This method requires a database transaction
     */
    public function deactivate_inherited_approvers(moodle_transaction $transaction) {
        // If this is a default assignment, we can skip this.
        if ($this->get_assignment()->is_default) {
            return;
        }
        $this->log("Deactivating inherited approvers from {$this->assignment->name} / {$this->approval_level->name}");

        // Are there inherited approvers here?
        $inherited_approvers = builder::table(assignment_approver_entity::TABLE, 'approver')
            ->where('approval_id', $this->assignment->id)
            ->where('workflow_stage_approval_level_id', $this->approval_level->id)
            ->where_not_null('ancestor_id')
            ->where('active', '=', true)
            ->get()
            ->map_to(assignment_approver_entity::class)
            ->map_to(assignment_approver::class);
        if ($inherited_approvers->count() == 0) {
            $this->log("No inherited approvers at {$this->assignment->name} - {$this->approval_level->name} to deactivate.");
        } else {
            $this->log("Found {$inherited_approvers->count()} inherited approvers at {$this->assignment->name} - {$this->approval_level->name} to deactivate.");
            foreach ($inherited_approvers as $inherited_approver) {
                $inherited_approver->deactivate();
            }
        }

        // Find descendant approval levels
        $descendant_levels = $this->get_descendants();
        if ($descendant_levels->count() == 0) {
            $this->log("No descendant levels from at {$this->assignment->name} - {$this->approval_level->name}");
        } else {
            // For each inherited approver, deactivate here and at all descendant levels.
            foreach ($descendant_levels as $descendant_level) {
                $this->log("Looking for inherited approvers in {$descendant_level->assignment->name} - {$descendant_level->approval_level->name}");
                $inherited_approvers = assignment_approver_entity::repository()
                    ->where('approval_id', $descendant_level->assignment->id)
                    ->where('workflow_stage_approval_level_id', $descendant_level->approval_level->id)
                    ->where_not_null('ancestor_id')
                    ->where('active', '=', true)
                    ->get()
                    ->map_to(assignment_approver::class);
                if ($inherited_approvers->count() == 0) {
                    $this->log("No undiscovered descendant inherited approvers at {$descendant_level->assignment->name} - {$descendant_level->approval_level->name} to deactivate.");
                } else {
                    $this->log("Found {$inherited_approvers->count()} inherited approvers at {$descendant_level->assignment->name} - {$descendant_level->approval_level->name} to deactivate.");
                    foreach ($inherited_approvers as $approver) {
                        /* @var \mod_approval\model\assignment\assignment_approver $approver */
                        if ($approver->type == approver_type_user::get_code()) {
                            $this->log("Deactivated approver {$approver->approver_entity->username} {$approver->id} (ancestor of {$approver->ancestor_id})");
                        } else {
                            $this->log("Deactivated {$approver->approver_entity->idnumber} approver {$approver->id} (ancestor of {$approver->ancestor_id})");
                        }
                        $approver->deactivate(true);
                    }
                }
            }
        }
    }

    public function __isset($field) {
        return in_array($field, [
            'assignment',
            'approval_level',
            'approvers',
            'approvers_with_inheritance',
            'inherited_from_assignment_approval_level',
        ]);
    }

    public function __get($field) {
        $function = 'get_' . $field;
        return $this->$function();
    }
}
