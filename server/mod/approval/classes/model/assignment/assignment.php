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
 * @author David Curry <david.curry@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\model\assignment;

use coding_exception;
use container_approval\approval as approval_container;
use container_approval\module\approval_module;
use context_module;
use core\entity\cohort as cohort_entity;
use core\orm\collection;
use core\orm\entity\entity;
use core\orm\entity\model;
use core\orm\query\builder;
use hierarchy_organisation\entity\organisation as organisation_entity;
use hierarchy_position\entity\position as position_entity;
use mod_approval\entity\application\application as application_entity;
use mod_approval\entity\assignment\assignment as assignment_entity;
use mod_approval\entity\assignment\assignment_approver as assignment_approver_entity;
use mod_approval\entity\workflow\workflow as workflow_entity;
use mod_approval\entity\workflow\workflow_stage_approval_level as workflow_stage_approval_level_entity;
use mod_approval\event\workflow_assignment_archived;
use mod_approval\event\workflow_assignment_created;
use mod_approval\event\workflow_assignment_deleted;
use mod_approval\event\workflow_stage_assignment_approvers_for_level_changed;
use mod_approval\exception\model_exception;
use mod_approval\interactor\assignment_interactor;
use mod_approval\model\application\application;
use mod_approval\model\assignment\assignment_approver as assignment_approver_model;
use mod_approval\model\assignment\assignment_type\provider;
use mod_approval\model\assignment\helper\assignment_approver_inheritance_builder;
use mod_approval\model\model_trait;
use mod_approval\model\status;
use mod_approval\model\status_trait;
use mod_approval\model\workflow\workflow;
use mod_approval\model\workflow\workflow_stage_approval_level;
use mod_approval\model\workflow\workflow_type;
use stdClass;

/**
 * Approval workflow assignment model
 *
 * Properties:
 * @property-read int $id Database record ID
 * @property-read string  $name
 * @property-read string  $id_number organization|position|cohort database shortname record
 * @property-read boolean $is_default
 * @property-read boolean $to_be_deleted
 * @property-read int $assignment_type Assignment type code (organization|position|cohort)
 * @property-read string $assignment_type_label Assignment type label (Organization|Position|Cohort)
 * @property-read int $assignment_identifier ID of assignment database record
 * @property-read int $status Assignment status code (draft|active|archived)
 * @property-read string $status_label Assignment status label (Draft|Active|Archived)
 * @property-read int $created Creation timestamp
 * @property-read int $updated Last-modified timestamp; same as created if not modified
 * @property-read int $course_id Course ID
 * @property-read int $contextid Context Module ID
 *
 * Relationships:
 * @property-read approval_container $container The parent container
 * @property-read workflow $workflow The workflow associated with the assignment
 * @property-read workflow_type $workflow_type The workflow type associated with the assignment
 * @property-read collection|assignment_approver[] $approvers Collection of active assignment_approvers for this assignment
 * @property-read organisation_entity|position_entity|cohort_entity $assigned_to The entity assigned by this assignment (entity type depends on $assignment_type)
 * @property-read collection|application[] $applications Collection of applications associated with this assignment
 *
 * Methods:
 * @method static self load_by_id(int $id)
 * @method static self load_by_entity(assignment_entity $entity)
 */
final class assignment extends model {

    use model_trait;
    use status_trait {
        activate as private status_trait_activate;
        archive as private status_trait_archive;
    }

    /** @var assignment_entity */
    protected $entity;

    /** @var string[] */
    protected $entity_attribute_whitelist = [
        'id',
        'name',
        'id_number',
        'is_default',
        'to_be_deleted',
        'assignment_type',
        'assignment_identifier',
        'status',
        'created',
        'updated'
    ];

    /** @var string[] */
    protected $model_accessor_whitelist = [
        'workflow',
        'approvers',
        'assignment_type_label',
        'assigned_to',
        'workflow_type',
        'applications',
        'container',
        'course_id',
        'contextid',
        'status_label',
    ];

    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
    protected static function get_entity_class(): string {
        return assignment_entity::class;
    }

    /**
     * Get the active approvers for this assignment.
     *
     * @return collection|assignment_approver[]
     */
    public function get_approvers(): collection {
        return $this->entity->active_approvers->map_to(assignment_approver::class);
    }

    /**
     * Set the approvers for the given approval level.
     *
     * Note that $approvers should contain all approver pairs - the existing set of approvers will be replaced.
     *
     * @param workflow_stage_approval_level $approval_level
     * @param array $approvers containing a complete set of [int 'assignment_approver_type', int 'identifier'] pairs
     * @return void
     */
    public function set_approvers_for_level(workflow_stage_approval_level $approval_level, array $approvers): void {
        // Pre-check the approver types.
        foreach ($approvers as $approver) {
            if (!assignment_approver_type::is_valid($approver['assignment_approver_type'])) {
                throw new model_exception('Unknown approver type provided');
            }
        }

        builder::get_db()->transaction(function () use ($approval_level, $approvers) {
            // Create or update all active approvers that were provided.
            $active_approver_ids = [];
            $existing_approvers_map = $this->get_existing_approvers_map($approval_level);

            foreach ($approvers as $approver) {

                if (!empty($existing_approvers_map[$approver['assignment_approver_type']][$approver['identifier']])) {
                    // The approver already exists. Update it to the correct state, if required.
                    /** @var assignment_approver_model $existing_approver */
                    $existing_approver = $existing_approvers_map[$approver['assignment_approver_type']][$approver['identifier']];

                    if (!$existing_approver->active) {
                        $existing_approver->activate();
                    }
                    $active_approver_ids[] = $existing_approver->id;
                } else {
                    // The approver doesn't exist, so add it.
                    $new_approver = assignment_approver_model::create(
                        $this,
                        $approval_level,
                        $approver['assignment_approver_type'],
                        $approver['identifier']
                    );
                    $active_approver_ids[] = $new_approver->id;
                }
            }

            // Deactivate all approvers that were not just provided.
            $this->deactivate_approvers($active_approver_ids, $approval_level);
        });

        // Relations will have changed.
        $this->refresh(true);

        // Trigger event
        workflow_stage_assignment_approvers_for_level_changed::execute($this, $approval_level);
    }

    /**
     * Get existing approvers keyed by type and identifiers.
     *
     * @param workflow_stage_approval_level $approval_level
     * @return array
     */
    private function get_existing_approvers_map(workflow_stage_approval_level $approval_level): array {
        /** @var collection|assignment_approver_model[] $existing_approvers */
        $existing_approvers = assignment_approver_entity::repository()
            ->where('approval_id', $this->id)
            ->where('workflow_stage_approval_level_id', $approval_level->id)
            ->where('active', '=', true)
            ->get()->map_to(assignment_approver_model::class);

        $existing_approvers_map = [];

        foreach ($existing_approvers as $existing_approver) {
            $existing_approvers_map[$existing_approver->type][$existing_approver->identifier] = $existing_approver;
        }

        return $existing_approvers_map;
    }

    /**
     * Deactivate approvers for an approval level.
     *
     * @param array $approver_ids
     * @param workflow_stage_approval_level $approval_level
     */
    private function deactivate_approvers(array $approver_ids, workflow_stage_approval_level $approval_level): void {
        $approvers_to_deactivate = assignment_approver_entity::repository()
            ->where_not_in('id', $approver_ids)
            ->where('approval_id', $this->id)
            ->where('workflow_stage_approval_level_id', $approval_level->id)
            ->where('active', true)
            ->get()->map_to(assignment_approver_model::class);

        /** @var assignment_approver_model $approver_to_deactivate */
        foreach ($approvers_to_deactivate as $approver_to_deactivate) {
            $approver_to_deactivate->deactivate();
        }
    }

    /**
     * Get the entity which applies to this assignment.
     *
     * @return organisation_entity|position_entity|cohort_entity
     */
    public function get_assigned_to(): entity {
        return provider::get_by_code($this->assignment_type)::instance($this->assignment_identifier)->get_entity();
    }

    /**
     * Get the assignment type label.
     *
     * @return string
     */
    public function get_assignment_type_label(): string {
        $type = provider::get_by_code($this->entity->assignment_type);

        return $type::get_label();
    }

    /**
     * Gets the workflow_type for this assignment.
     *
     * @return workflow_type
     */
    public function get_workflow_type(): workflow_type {
        $workflow = workflow_entity::repository()->where('course_id', '=', $this->course_id)->one();
        return workflow_type::load_by_id($workflow->workflow_type_id);
    }

    /**
     * Get the applications associated with this assignment
     *
     * @return collection|application[]
     */
    public function get_applications(): collection {
        return $this->entity->applications->map_to(application::class);
    }

    /**
     * Get the parent container.
     *
     * @return approval_container
     */
    public function get_container(): approval_container {
        return approval_container::from_id($this->course_id);
    }

    /**
     * Get the course module record associated with the assignment / approval.
     * Note: course modules doesn't have a model so this isn't a proper relation.
     *
     * @return stdClass
     */
    public function get_course_module(): stdClass {
        return get_coursemodule_from_instance(assignment_entity::TABLE, $this->entity->id, $this->course_id, false, MUST_EXIST);
    }

    /**
     * Get the module instance associated with the assignment / approval.
     *
     * @return approval_module
     */
    public function get_module(): approval_module {
        $cm = $this->get_course_module();
        unset($cm->name, $cm->modname);
        return approval_module::from_record($cm);
    }

    /**
     * Get the module context associated with this assignment / approval.
     *
     * @return context_module
     */
    public function get_context(): context_module {
        return $this->get_module()->get_context();
    }

    /**
     * Get the module context id associated with this assignment / approval.
     *
     * @return int context_module_id
     */
    public function get_contextid(): int {
        return $this->get_context()->id;
    }

    /**
     * Return the course id associated to this instance.
     *
     * @return int
     */
    public function get_course_id(): int {
        return $this->entity->course;
    }

    /**
     * Get the status label.
     *
     * @return string
     */
    public function get_status_label(): string {
        return status::label($this->entity->status);
    }

    /**
     * Gets a workflow model object
     *
     * @return workflow
     */
    public function get_workflow(): workflow {
        return workflow::load_by_entity($this->entity->workflow);
    }

    /**
     * Create a new assignment
     * if an active/draft assignment already exists, an exception is thrown.
     *
     * @param stdClass|approval_container|int $course Course instance or id
     * @param int $type One of assignment_type
     * @param int $identifier Relationship identifier depending on $type
     * @param bool $default Default state
     * @param string $idnumber External reference number
     * @return self
     */
    public static function create($course, int $type, int $identifier, bool $default = false, string $idnumber = ''): self {
        $assignment_instance = provider::get_by_code($type)::instance($identifier);
        $assignment_name = $assignment_instance->get_name();
        if (empty($idnumber)) {
            $idnumber = $assignment_instance->get_id_number();
        }

        if (empty($course)) {
            throw new model_exception('Course cannot be empty');
        }

        // Allow either a course database object or straight id be handed through.
        if ($course instanceof stdClass || $course instanceof approval_container) {
            $course = $course->id;
        }

        if ($default) {
            $has_default_assignment = assignment_entity::repository()
                ->where('course', $course)
                ->where('is_default', true)
                ->one();
            if ($has_default_assignment) {
                throw new model_exception('Default assignment already exists');
            }
        }

        return builder::get_db()->transaction(function () use ($course, $type, $identifier, $default, $idnumber, $assignment_name) {
            // Check for existing assignment
            /** @var assignment_entity $existing_assignment_entity*/
            $existing_assignment_entity = assignment_entity::repository()
                ->where('course', $course)
                ->where('assignment_type', $type)
                ->where('assignment_identifier', $identifier)
                ->where('status', '!=', status::ARCHIVED)
                ->one();

            if ($existing_assignment_entity) {
                throw new model_exception("Assignment already exists");
            } else {
                $assignment = self::create_new($course, $assignment_name, $idnumber, $default, $type, $identifier);
            }

            if ($assignment->workflow->latest_version->is_active()) {
                $assignment->activate();
            }

            return $assignment;
        });
    }


    /**
     * Creates a new assignment and adds it as a module to the course.
     *
     * @param int $course
     * @param string $assignment_name
     * @param string $idnumber
     * @param bool $default
     * @param int $type
     * @param int $identifier
     *
     * @return assignment
     */
    private static function create_new(int $course, string $assignment_name, string $idnumber, bool $default, int $type, int $identifier): assignment {
        // Set up an entity with all the data.
        $entity = new assignment_entity();
        $entity->course = $course;
        $entity->name = $assignment_name;
        $entity->id_number = $idnumber;
        $entity->is_default = $default;
        $entity->assignment_type = $type;
        $entity->assignment_identifier = $identifier;
        $entity->status = status::DRAFT;
        $entity->to_be_deleted = false;
        $entity->save();
        $assignment = self::load_by_entity($entity);

        if (!$assignment->workflow->active) {
            throw new model_exception("Workflow must be active");
        }

        // Create the course_module associated with the entity.
        $modinfo = new stdClass();
        $modinfo->modulename = 'approval';
        $modinfo->course = $course;
        $modinfo->name = $assignment_name;
        $modinfo->timemodified = time();
        $modinfo->visible = true;
        $modinfo->section = 0;
        $modinfo->groupmode = 0;
        $modinfo->groupingid = 0;
        $modinfo->instanceid = $entity->id;
        approval_container::from_id($course)->add_module($modinfo);

        // Trigger event
        workflow_assignment_created::execute($assignment);

        return $assignment;
    }

    /**
     * Set status to active if possible, and then create/update descendant approvers as necessary
     *
     * @param bool $debug If true, enable console on inheritance_builder class
     * @return self
     */
    public function activate(bool $debug = false): self {
        $transaction = builder::get_db()->start_delegated_transaction();

        $this->status_trait_activate();

        $inheritance_builder = new assignment_approver_inheritance_builder();
        if ($debug) {
            assignment_approver_inheritance_builder::$logging_enabled = true;
        }
        $inheritance_builder->rebuild_tree_for_assignment($this, $this->workflow->latest_version, $debug);
        if ($debug) {
            assignment_approver_inheritance_builder::$logging_enabled = false;
        }

        $transaction->allow_commit();
        return $this;
    }

    /**
     * Archive an assignment and deactivates its approvers.
     *
     * @return void
     */
    public function archive(): void {
        $this->status_trait_archive();

        foreach ($this->approvers as $approver) {
            $approver->deactivate();
        }
        $this->refresh(true);

        // Trigger event
        workflow_assignment_archived::execute($this);
    }

    /**
     * Delete the record.
     *
     * @param boolean $delete_non_draft Force delete (do not use it)
     * @param boolean $delete_applications Force delete applications (do not use it)
     * @return self
     */
    public function delete(bool $delete_non_draft = false, bool $delete_applications = false): self {
        if (!$this->entity->exists()) {
            return $this;
        }

        if (!$delete_non_draft && !$this->is_draft()) {
            throw new model_exception("Only draft assignments can be deleted");
        }

        builder::get_db()->transaction(function () use ($delete_applications) {
            if ($delete_applications) {
                // Delete applications associated with this assignment.
                /** @var application[] $applications */
                $applications = application_entity::repository()->where('approval_id', $this->id)->get()->map_to(application::class);
                foreach ($applications as $application) {
                    $application->delete(true);
                }
            }

            // Delete approvers associated with this assignment.
            /** @var assignment_approver_model[] $approvers */
            $approvers = assignment_approver_entity::repository()->where('approval_id', $this->id)->get()->map_to(assignment_approver::class);
            foreach ($approvers as $approver) {
                $approver->delete();
            }

            // Delete the course module.
            $module = $this->get_module();
            $module->delete();

            // Trigger event
            workflow_assignment_deleted::execute($this);

            // Delete the entity.
            $this->entity->delete();
        });

        return $this;
    }

    /**
     * Delete the record asynchronously.
     *
     * @return self
     */
    public function delete_later(): self {
        $this->entity->to_be_deleted = true;
        $this->entity->save();
        // TODO: schedule an ad-hoc task.
        return $this;
    }

    /**
     * Get an interactor which can be used to determine if the given user can perform actions in relation to this assignment.
     *
     * @param int $applicant_user_id of the user whose application is being created
     * @param int $interactor_user_id of the user who is performing the actions
     * @return assignment_interactor
     */
    public function get_interactor(int $applicant_user_id, int $interactor_user_id): assignment_interactor {
        return new assignment_interactor($this->get_context(), $applicant_user_id, $interactor_user_id);
    }
}
