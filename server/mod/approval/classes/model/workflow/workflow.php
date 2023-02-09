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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\model\workflow;

use container_approval\approval as approval_container;
use context_course;
use core\orm\collection;
use core\orm\entity\model;
use core\orm\query\builder;
use core\orm\query\order;
use mod_approval\entity\assignment\assignment as assignment_entity;
use mod_approval\entity\assignment\assignment_approver as assignment_approver_entity;
use mod_approval\entity\workflow\workflow as workflow_entity;
use mod_approval\entity\workflow\workflow_version as workflow_version_entity;
use mod_approval\entity\workflow\workflow_stage as workflow_stage_entity;
use mod_approval\entity\workflow\workflow_stage_approval_level as workflow_stage_approval_level_entity;
use mod_approval\entity\workflow\workflow_stage_interaction as workflow_stage_interaction_entity;
use mod_approval\event\workflow_version_archived;
use mod_approval\event\workflow_cloned;
use mod_approval\event\workflow_created;
use mod_approval\event\workflow_edited;
use mod_approval\event\workflow_version_published;
use mod_approval\event\workflow_version_unarchived;
use mod_approval\exception\model_exception;
use mod_approval\interactor\workflow_interactor;
use mod_approval\model\active_trait;
use mod_approval\model\assignment\assignment;
use mod_approval\model\assignment\assignment_approver;
use mod_approval\model\form\form;
use mod_approval\model\model_trait;
use mod_approval\model\status;
use moodle_exception;
use stdClass;

/**
 * Approval workflow model
 *
 * Properties:
 *
 * @property-read int $id Database record ID
 * @property-read int $course_id Course ID
 * @property-read int $context_id Workflow course context ID
 * @property-read int $workflow_type_id Workflow_type ID
 * @property-read string $name Human-readable name
 * @property-read string $description JSONDoc description
 * @property-read string $id_number External reference number
 * @property-read int $form_id Approval form ID
 * @property-read int|null $template_id ID of workflow used as template, if there is one
 * @property-read bool $active Is this workflow active or not?
 * @property-read int $created Creation timestamp
 * @property-read int $updated Last-modified timestamp; same as created if not modified
 * @property-read bool $to_be_deleted Flag that marks this workflow as waiting to be deleted
 *
 * Relationships:
 * @property-read approval_container $container Parent course container
 * @property-read workflow_type $workflow_type Workflow_type for this workflow
 * @property-read form $form Form this workflow uses
 * @property-read workflow|null $template Workflow that is the template for this workflow, if any
 * @property-read collection|workflow_version[] $versions Collection of versions of this workflow
 * @property-read workflow_version $latest_version Workflow latest version.
 * @property-read collection|assignment[] $assignments Collection of assignment entities for this workflow
 * @property-read assignment $default_assignment The default assignment entity for this workflow
 * @property-read collection|workflow[] $template_instances Active workflows which use this workflow as a template
 * @property-read workflow_version $active_version Workflow latest active version.
 *
 * Methods:
 * @method static self load_by_id(int $id)
 * @method static self load_by_entity(workflow_entity $entity)
 */
final class workflow extends model {

    use active_trait;
    use model_trait;

    /** @var workflow_entity */
    protected $entity;

    /** @var string[] */
    protected $entity_attribute_whitelist = [
        'id',
        'course_id',
        'workflow_type_id',
        'name',
        'id_number',
        'form_id',
        'template_id',
        'active',
        'created',
        'updated',
        'to_be_deleted',
    ];

    /** @var string[] */
    protected $model_accessor_whitelist = [
        'description',
        'container',
        'context_id',
        'workflow_type',
        'form',
        'template',
        'template_instances',
        'latest_version',
        'versions',
        'assignments',
        'default_assignment',
        'active_version',
    ];

    /** @var string[] */
    protected $deactivate_checklist = [
        workflow_version::class => 'workflow_id',
        workflow::class => 'template_id',
    ];

    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
    protected static function get_entity_class(): string {
        return workflow_entity::class;
    }

    /**
     * Get the description.
     *
     * @return string
     */
    public function get_description(): string {
        return $this->entity->description ?? '';
    }

    /**
     * Get the parent container.
     *
     * @return approval_container
     */
    public function get_container(): approval_container {
        /** @var approval_container $approval_container */
        $approval_container = approval_container::from_id($this->entity->course_id);
        return $approval_container;
    }

    /**
     * Get parent workflow_type.
     *
     * @return workflow_type
     */
    public function get_workflow_type(): workflow_type {
        return workflow_type::load_by_entity($this->entity->workflow_type);
    }

    /**
     * Get the form this workflow uses.
     *
     * @return form
     */
    public function get_form(): form {
        return form::load_by_entity($this->entity->form);
    }

    /**
     * Get the template workflow this workflow is based on.
     *
     * @return workflow|null
     */
    public function get_template(): ?workflow {
        if (!$this->entity->template_id) {
            return null;
        }
        return workflow::load_by_entity($this->entity->template);
    }

    /**
     * Get the latest workflow version.
     *
     * @return workflow_version
     */
    public function get_latest_version(): workflow_version {
        return workflow_version::load_latest_by_workflow_id($this->id);
    }

    /**
     * Get the latest active workflow version.
     *
     * @return workflow_version|null
     */
    public function get_active_version(): ?workflow_version {
        return workflow_version::load_active_by_workflow_id($this->id);
    }

    /**
     * Get the workflow versions for this workflow.
     *
     * @return collection|workflow_version[]
     */
    public function get_versions(): collection {
        return $this->entity->versions->map_to(workflow_version::class);
    }

    /**
     * Get the active workflows which use this workflow as a template.
     *
     * @return collection|workflow[]
     */
    public function get_template_instances(): collection {
        return $this->entity->template_instances->map_to(workflow::class);
    }

    /**
     * Return the context object for this workflow.
     *
     * @return context_course
     */
    public function get_context(): context_course {
        return context_course::instance($this->course_id);
    }

    /**
     * Get the workflow's course context id.
     *
     * @return int
     */
    public function get_context_id(): int {
        return $this->get_context()->id;
    }

    /**
     * Get all the assignments mapped to this workflow via the course object.
     *
     * @return collection|assignment[]
     */
    public function get_assignments(): collection {
        return $this->entity->assignments->map_to(assignment::class);
    }

    /**
     * Get the default assignment for this workflow / course container.
     *
     * @return assignment
     */
    public function get_default_assignment(): assignment {
        return assignment::load_by_entity($this->entity->default_assignment);
    }

    /**
     * Gets a workflow model object based on course id
     *
     * @param int $course_id Course id
     * @return self
     */
    public static function load_by_course_id(int $course_id): self {
        /** @var workflow_entity $entity */
        $entity = workflow_entity::repository()->where('course_id', '=', $course_id)->one(true);
        return self::load_by_entity($entity);
    }

    /**
     * Return true if a course is the container of a workflow.
     *
     * @param stdClass|integer $course_or_id
     * @return boolean
     */
    public static function is_workflow_container($course_or_id): bool {
        if (isset($course_or_id->container_type)) {
            return $course_or_id->container_type === 'container_approval';
        }
        if (is_int($course_or_id)) {
            $course_id = $course_or_id;
        } else {
            $course_id = $course_or_id->id;
        }
        return workflow_entity::repository()->where('course_id', '=', $course_id)->exists();
    }

    /**
     * Are all workflow_versions of this workflow draft?
     *
     * @return boolean
     */
    public function are_all_draft(): bool {
        return $this->are_all_in_status(status::DRAFT);
    }

    /**
     * Are any workflow_versions of this workflow in draft?
     *
     * @return boolean
     */
    public function are_any_draft(): bool {
        return $this->is_any_in_status(status::DRAFT);
    }

    /**
     * Is any workflow_versions of this workflow active?
     *
     * @return boolean
     */
    public function is_any_active(): bool {
        return $this->is_any_in_status(status::ACTIVE);
    }

    /**
     * Is any workflow_versions of this workflow archived?
     *
     * @return boolean
     */
    public function is_any_archived(): bool {
        return $this->is_any_in_status(status::ARCHIVED);
    }

    /**
     * Are all workflow_versions of this workflow the specific status?
     *
     * @param integer $status
     * @return boolean
     */
    private function are_all_in_status(int $status): bool {
        // everything is status === nothing is not status
        return builder::table(workflow_version_entity::TABLE, 'wv')
                ->join([workflow_entity::TABLE, 'w'], 'w.id', 'wv.workflow_id')
                ->where('wv.status', '!=', $status)
                ->where('w.id', $this->id)
                ->exists()
            == false;
    }

    /**
     * Is any workflow_versions of this workflow the specific status?
     *
     * @param integer $status
     * @return boolean
     */
    private function is_any_in_status(int $status): bool {
        return workflow_version_entity::repository()
            ->where('workflow_id', $this->id)
            ->where('status', $status)
            ->exists();
    }

    /**
     * Create a workflow from scratch.
     *
     * @param workflow_type $workflow_type Related workflow_type
     * @param form $form Form to be used
     * @param string $name Human-readable name
     * @param string $description JSONDoc description
     * @param int $assignment_type Type for default assignment
     * @param int $assignment_identifier Assignment identifier
     * @param string $id_number External reference number
     * @param string $assignment_id_number External reference number for assignment
     * @return self
     */
    public static function create(
        workflow_type $workflow_type,
        form $form,
        string $name,
        string $description,
        int $assignment_type,
        int $assignment_identifier,
        string $id_number = '',
        string $assignment_id_number = ''
    ): self {
        self::trim_strings($name, $description, $id_number);
        if (!$workflow_type->active) {
            throw new model_exception("Workflow_type must be active");
        }
        if (!$form->active) {
            throw new model_exception("Form must be active");
        }
        if ($name === '') {
            throw new model_exception('Workflow name cannot be empty');
        }
        if (!$assignment_type) {
            throw new model_exception('Assignment type cannot be empty');
        }
        if (!$assignment_identifier) {
            throw new model_exception('Assignment identifier cannot be empty');
        }
        if ($id_number === '') {
            // Generate a default id_number
            $id_number = uniqid('workflow');
        }
        if (!self::is_unique_id_number($id_number)) {
            throw new moodle_exception('error:workflow_id_not_unique', 'mod_approval');
        }
        $container = approval_container::create((object) ['category' => approval_container::get_default_category_id()]);
        $entity = new workflow_entity();
        $entity->workflow_type_id = $workflow_type->id;
        $entity->name = $name;
        $entity->description = $description;
        $entity->id_number = $id_number;
        $entity->form_id = $form->id;
        $entity->template_id = null;
        $workflow = self::create_internal($container, $entity, $assignment_type, $assignment_identifier, $assignment_id_number);

        // Trigger event
        workflow_created::execute($workflow);

        return $workflow;
    }

    /**
     * Create a workflow based on an existing workflow.
     *
     * @param workflow $template Base template
     * @param string $name Human-readable name
     * @param string $description JSONDoc description
     * @param int $assignment_type Type for default assignment
     * @param int $assignment_identifier Assignment identifier
     * @param string $id_number External reference number
     * @return self
     */
    public static function create_from_template(
        workflow $template,
        string $name,
        string $description,
        int $assignment_type,
        int $assignment_identifier,
        string $id_number = ''
    ): self {
        self::trim_strings($name, $description, $id_number);
        if (!$template->active) {
            throw new model_exception("Workflow template must be active");
        }
        if ($name === '') {
            throw new model_exception('Workflow name cannot be empty');
        }
        if (!$assignment_type) {
            throw new model_exception('Assignment type cannot be empty');
        }
        if (!$assignment_identifier) {
            throw new model_exception('Assignment identifier cannot be empty');
        }
        if ($id_number === '') {
            // Generate a default id_number
            $id_number = uniqid('workflow');
        }
        if (!self::is_unique_id_number($id_number)) {
            throw new moodle_exception('error:workflow_id_not_unique', 'mod_approval');
        }
        $container = approval_container::create((object) ['category' => approval_container::get_default_category_id()]);
        $entity = new workflow_entity();
        $entity->workflow_type_id = $template->workflow_type_id;
        $entity->name = $name;
        $entity->description = $description;
        $entity->id_number = $id_number;
        $entity->form_id = $template->form->id;
        $entity->template_id = $template->id;
        return self::create_internal($container, $entity, $assignment_type, $assignment_identifier);
    }

    /**
     * @param approval_container $container
     * @param workflow_entity $entity
     * @param int $assignment_type
     * @param int $assignment_identifier
     * @param string $assignment_id_number
     * @return self
     */
    private static function create_internal(
        approval_container $container,
        workflow_entity $entity,
        int $assignment_type,
        int $assignment_identifier,
        string $assignment_id_number = ''
    ): self {
        /** @var workflow $workflow */
        $workflow = builder::get_db()->transaction(function () use ($entity, $container, $assignment_type, $assignment_identifier, $assignment_id_number) {
            $entity->course_id = $container->id;
            $entity->active = true;
            $entity->to_be_deleted = false;
            $entity->save();
            $container->update((object) [
                'fullname' => $entity->name,
            ]);
            $workflow = self::load_by_entity($entity);

            workflow_version::create($workflow, $workflow->form->latest_version);
            assignment::create($container, $assignment_type, $assignment_identifier, true, $assignment_id_number)->activate();
            return $workflow;
        });

        // Refresh and return the workflow.
        return $workflow->refresh();
    }

    /**
     * Update workflow with new data
     *
     * @param string $name New name
     * @param string $description New description
     * @param string $id_number New id number
     * @return self
     */
    public function edit(string $name, string $description, string $id_number): self {
        self::trim_strings($name, $description, $id_number);
        if ($name === '') {
            throw new model_exception('Workflow name cannot be empty');
        }
        if ($id_number === '') {
            throw new model_exception('Workflow id_number cannot be empty');
        }
        builder::get_db()->transaction(function () use ($name, $description, $id_number) {
            if (!workflow::is_unique_id_number($id_number, $this->id)) {
                throw new moodle_exception('error:workflow_id_not_unique', 'mod_approval');
            }
            $this->entity->name = $name;
            $this->entity->description = $description;
            $this->entity->id_number = $id_number;
            $this->entity->save();

            // Update course container
            $this->container->update((object) [
                'fullname' => $name,
            ]);

            // Trigger event
            workflow_edited::execute($this);
        });
        return $this;
    }

    /**
     * Remove whitespaces around name, description and id_number.
     *
     * @param string $name
     * @param string $description
     * @param string $id_number
     */
    private static function trim_strings(string &$name, string &$description, string &$id_number): void {
        $name = trim($name);
        $description = trim($description);
        $id_number = trim($id_number);
    }

    /**
     * Check if a workflow id_number is unique.
     *
     * If current_id is not null, the workflow with that ID will be ignored.
     *
     * @param string $id_number
     * @param int|null $current_id
     * @return boolean
     */
    public static function is_unique_id_number(string $id_number, ?int $current_id = null): bool {
        $repository = workflow_entity::repository()->where('id_number', trim($id_number));
        if (!is_null($current_id)) {
            $repository->where('id', '!=', $current_id);
        }
        return !$repository->exists();
    }

    /**
     * Check the existence.
     *
     * @return boolean
     */
    public function exists(): bool {
        return $this->entity->exists();
    }

    /**
     * Delete the record.
     * Did you mean: container_approval\approval::delete
     *
     * @return self
     */
    public function delete(): self {
        // workflow::delete will fail if the workflow has applications.
        $this->container->delete(false);
        $this->entity->set_deleted();
        return $this;
    }

    /**
     * Delete the record.
     *
     * Do *not* use this function. To delete a workflow alongside its course container,
     * use container_approval\approval::delete instead
     *
     * @param boolean $force Dangerous. Do not use it
     * @return self
     */
    public function delete_internal(bool $force = false): self {
        if (!$this->entity->exists()) {
            return $this;
        }
        builder::get_db()->transaction(function () use ($force) {
            $id = $this->entity->id;
            // TODO TL-30193: This is probably not how deletion should be handled.
            //    In fact, it may be that the model simply does not support deletion, ever.
            //    But if it does, it should only support deletion of draft workflows, and ideally
            //    deletion via model classes not entities so appropriate checks can be done.
            /** @var assignment[] $assignments */
            $assignments = assignment_entity::repository()->where('course', $this->course_id)->get()->map_to(assignment::class);
            foreach ($assignments as $assignment) {
                // Force delete an assignment regardless of its status.
                $assignment->delete(true, $force);
            }
            $vers = workflow_version_entity::repository()->where('workflow_id', $id)->get()->map_to(workflow_version::class);
            foreach ($vers as $ver) {
                // TODO: TL-32211 delete vvv if not necessary.
                assignment_approver_entity::repository()
                    ->as('aa')
                    ->join([workflow_stage_approval_level_entity::TABLE, 'al'], 'al.id', 'aa.workflow_stage_approval_level_id')
                    ->join([workflow_stage_entity::TABLE, 'ws'], 'ws.id', 'al.workflow_stage_id')
                    ->where('ws.workflow_version_id', $ver->id)
                    ->get()
                    ->map_to(function ($approver) {
                        return assignment_approver::load_by_entity($approver)->delete();
                    });
                /// TODO: ^^^ end of probable deletion
                /** @var workflow_stage[] $stages */
                $stages = workflow_stage_entity::repository()->where('workflow_version_id', $ver->id)->get()->map_to(workflow_stage::class);
                foreach ($stages as $stage) {
                    workflow_stage_approval_level_entity::repository()
                        ->where('workflow_stage_id', $stage->id)
                        ->get()
                        ->map_to(function ($level) {
                            return workflow_stage_approval_level::load_by_entity($level)->delete();
                        });
                    workflow_stage_interaction_entity::repository()
                        ->where('workflow_stage_id', $stage->id)
                        ->get()
                        ->map_to(function ($interaction) {
                            return workflow_stage_interaction::load_by_entity($interaction)->delete();
                        });
                    $stage->delete($force);
                }
                $ver->delete($force);
            }
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
     * Get an interactor which can be used to determine if the given user can perform actions in relation to this workflow.
     *
     * @param int $interactor_user_id of the user who is performing the actions
     * @return workflow_interactor
     */
    public function get_interactor(int $interactor_user_id): workflow_interactor {
        return workflow_interactor::from_workflow($this, $interactor_user_id);
    }

    /**
     * Create new workflow by cloning itself
     *
     * @param string $name
     * @param int $assignment_type Type of new default assignment
     * @param int $assignment_identifier Identifier of new assignment
     * @return workflow
     */
    public function clone(string $name, int $assignment_type, int $assignment_identifier): workflow {
        $new_workflow = self::create(
            $this->get_workflow_type(),
            $this->get_form(),
            $name,
            $this->description ?? '',
            $assignment_type,
            $assignment_identifier
        );

        // Workflow cloned event
        workflow_cloned::execute($this, $new_workflow);

        return $new_workflow;
    }

    /**
     * Archive this workflow.
     */
    public function archive(): void {
        builder::get_db()->transaction(function () {
            if (!$this->exists()) {
                throw new model_exception('The workflow no longer exists');
            }
            $active_versions = workflow_version_entity::repository()
                ->where('workflow_id', $this->id)
                ->where('status', status::ACTIVE)
                ->get(false);

            if (!$active_versions->count()) {
                throw new model_exception('Cannot archive workflow because it is not active');
            }

            foreach ($active_versions as $active_version) {
                $active_version->status = status::ARCHIVED;
                $active_version->save();

                // Trigger event
                workflow_version_archived::execute(workflow_version::load_by_entity($active_version));
            }

            if ($this->entity->relation_loaded('versions')) {
                $this->entity->load_relation('versions');
            }
        });
    }

    /**
     * Publish this workflow.
     *
     * @param workflow_version $workflow_version
     */
    public function publish(workflow_version $workflow_version): void {
        if (!$this->exists()) {
            throw new model_exception('The workflow no longer exists');
        }

        if ($workflow_version->workflow_id != $this->id) {
            throw new model_exception('The given workflow version does not belong to this workflow');
        }

        // No need to check that the workflow version is draft because it is checked in ->activate().

        builder::get_db()->transaction(function () use ($workflow_version) {
            /** @var workflow_version[] $active_versions */
            $active_versions = workflow_version_entity::repository()
                ->where('workflow_id', $this->id)
                ->where('status', status::ACTIVE)
                ->get()
                ->map_to(workflow_version::class);

            foreach ($active_versions as $active_version) {
                $active_version->archive();

                // Trigger event
                workflow_version_archived::execute($active_version);
            }

            $workflow_version->activate();
            $this->activate_draft_assignments();

            // Trigger event
            workflow_version_published::execute($workflow_version);
        });

        if ($this->entity->relation_loaded('versions')) {
            $this->entity->load_relation('versions');
        }
    }

    /**
     * Activate draft assignments that belong to this workflow.
     *
     * @return void
     */
    private function activate_draft_assignments(): void {
        $assignment_entities = $this->entity->draft_override_assignments;

        foreach ($assignment_entities as $assignment_entity) {
            assignment::load_by_entity($assignment_entity)->activate();
        }
    }

    /**
     * Unarchive this workflow.
     */
    public function unarchive(): void {
        builder::get_db()->transaction(function () {
            if (!$this->exists()) {
                throw new model_exception('The workflow no longer exists');
            }
            if ($this->is_any_in_status(status::ACTIVE)) {
                throw new model_exception('Cannot unarchive workflow because it is already active');
            }
            /** @var workflow_version_entity $archived_version */
            $archived_version = workflow_version_entity::repository()
                ->where('workflow_id', $this->id)
                ->where('status', status::ARCHIVED)
                ->order_by('id', order::DIRECTION_DESC)
                ->first();
            if (!$archived_version) {
                throw new model_exception('Cannot unarchive workflow because it is not archived');
            }
            $archived_version->status = status::ACTIVE;
            $archived_version->save();
            if ($this->entity->relation_loaded('versions')) {
                $this->entity->load_relation('versions');
            }

            // Trigger event
            $workflow_version = workflow_version::load_by_entity($archived_version);
            workflow_version_unarchived::execute($workflow_version);
        });
    }
}
