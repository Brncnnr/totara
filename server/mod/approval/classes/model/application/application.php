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

namespace mod_approval\model\application;

use coding_exception;
use context_module;
use core\entity\user as user_entity;
use core\orm\collection;
use core\orm\entity\model;
use core\orm\query\builder;
use core_date;
use DateTime;
use invalid_parameter_exception;
use mod_approval\entity\application\application as application_entity;
use mod_approval\entity\application\application_action as application_action_entity;
use mod_approval\entity\application\application_activity as application_activity_entity;
use mod_approval\entity\application\application_submission as application_submission_entity;
use mod_approval\exception\model_exception;
use mod_approval\interactor\application_interactor;
use mod_approval\model\application\action\approve as approve_action;
use mod_approval\model\application\action\reject as reject_action;
use mod_approval\model\application\action\withdraw_before_submission;
use mod_approval\model\application\action\withdraw_in_approvals;
use mod_approval\model\application\activity\creation;
use mod_approval\model\assignment\assignment;
use mod_approval\model\assignment\assignment_approval_level;
use mod_approval\model\assignment\assignment_approver_resolver;
use mod_approval\model\form\form_version;
use mod_approval\model\model_trait;
use mod_approval\model\workflow\stage_type\finished;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_stage_approval_level;
use mod_approval\model\workflow\workflow_type;
use mod_approval\model\workflow\workflow_version;
use totara_job\entity\job_assignment as job_assignment_entity;
use totara_job\job_assignment;

/**
 * Approval workflow application model
 *
 * Properties:
 *
 * @property-read int $id Database record ID
 * @property-read string $title Human-readable name
 * @property-read string $id_number Auto-generated reference number
 * @property-read int $user_id Applicant id
 * @property-read int|null $job_assignment_id Applicant job assignment
 * @property-read int $workflow_version_id Related workflow_version ID
 * @property-read int $form_version_id Related form_version ID
 * @property-read int $approval_id Related assignment ID (aka approval.id or $assignment.id)
 * @property-read int $creator_id User who initially create this application
 * @property-read int $owner_id Owner of the application, initially the creator
 * @property-read int $created Creation timestamp
 * @property-read int $updated Last-modified timestamp; same as created if not modified
 * @property-read int|null $submitted Application submitted timestamp, or null
 * @property-read int|null $submitter_id Application submitted user, or null
 * @property-read int|null $completed Application completed timestamp, or null
 * @property-read context_module $context Assignment module context for this application
 *
 * Relationships:
 * @property-read user_entity $user Related user entity
 * @property-read job_assignment|null $job_assignment The job assignment for this applicant
 * @property-read workflow_version $workflow_version Related workflow_version
 * @property-read form_version $form_version Related form_version
 * @property-read assignment $assignment Related assignment entity
 * @property-read user_entity $creator Related user entity
 * @property-read user_entity $owner Related user entity
 * @property-read user_entity|null $submitter Related user entity
 * @property-read workflow_stage $current_stage Current workflow_stage, if any
 * @property-read workflow_stage|null $next_stage Next workflow_stage, if any
 * @property-read workflow_stage_approval_level|null $current_approval_level Current approval_level if any
 * @property-read application_state $current_state Current state
 * @property-read collection|application_action[] $actions Related application_action entity
 * @property-read collection|application_submission[] $submissions Related application_submission entity
 * @property-read collection|application_activity[] $activities Related application_activity entity
 * @property-read workflow_type $workflow_type Workflow type for this application
 * @property-read string $overall_progress The overall progress ENUM for this application.
 * @property-read string $overall_progress_label The overall progress label for this application.
 * @property-read string $your_progress Approver's progress ENUM on this application, for dashboard display
 * @property-read string $your_progress_label Approver's progress on this application, for dashboard display
 * @property-read application_submission|null $last_submission Last applicant submission
 * @property-read application_submission|null $last_published_submission Last applicant submission that was published
 * @property-read application_action|null $last_action Last action (approved/rejected/withdrawn)
 * @property-read collection|user_entity[] $approver_users Approvers of the current approval level
 *
 * Methods:
 * @method static self load_by_id(int $id)
 * @method static self load_by_entity(application_entity $entity)
 * @package mod_approval\models\application
 */
class application extends model {

    use model_trait;

    /** @var application_entity */
    protected $entity;

    /** @var string[] */
    protected $entity_attribute_whitelist = [
        'id',
        'user_id',
        'job_assignment_id',
        'workflow_version_id',
        'form_version_id',
        'approval_id',
        'creator_id',
        'owner_id',
        'created',
        'updated',
        'submitted',
        'submitter_id',
        'completed',
        'title',
        'id_number',
    ];

    /** @var string[] */
    protected $model_accessor_whitelist = [
        'user',
        'job_assignment',
        'workflow_version',
        'form_version',
        'assignment',
        'creator',
        'owner',
        'submitter',
        'current_stage',
        'next_stage',
        'current_approval_level',
        'current_state',
        'actions',
        'submissions',
        'activities',
        'workflow_type',
        'overall_progress',
        'overall_progress_label',
        'your_progress',
        'your_progress_label',
        'last_submission',
        'last_published_submission',
        'last_action',
        'context',
        'approver_users',
    ];

    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
    protected static function get_entity_class(): string {
        return application_entity::class;
    }

    /**
     * Create a new application.
     *
     * @param workflow_version $workflow_version Related workflow_version
     * @param assignment $assignment Parent assignment
     * @param int $creator_id ID of user that creates this application
     * @param int|null $applicant_id ID of applicant, or null to use the creator
     * @param job_assignment_entity|null $job_assignment Applicant's job assignment or null
     * @param string|null $title Optional title or null to use a workflow type name
     * @param application|null $source Source application when cloning
     * @return self
     */
    public static function create(
        workflow_version $workflow_version,
        assignment $assignment,
        int $creator_id,
        ?int $applicant_id = null,
        ?job_assignment_entity $job_assignment = null,
        ?string $title = null,
        ?application $source = null
    ): self {
        // Check that the workflow_version is active.
        if (!$workflow_version->is_active()) {
            throw new model_exception("Workflow_version status must be active for a new application");
        }

        // Check that the form_version is active, and matches the workflow_version at create time.
        if (!$workflow_version->form_version->is_active()) {
            throw new model_exception("Form_version status must be active for a new application");
        }

        // Check that the assignment is active.
        if (!$assignment->is_active()) {
            throw new model_exception("Assignment status must be active for a new application");
        }

        $start_stage = $workflow_version->get_stages()->first();
        $start_state = $start_stage->state_manager->get_creation_state();

        $application = self::create_internal(
            $start_state,
            $workflow_version,
            $assignment,
            $creator_id,
            $applicant_id,
            $job_assignment,
            $title
        );

        // Create first application_activity.
        if ($source !== null) {
            $info = ['source' => $source->id];
        } else {
            $info = [];
        }

        application_activity::create(
            $application,
            $creator_id,
            creation::class,
            $info
        );
        $start_stage->state_manager->on_application_start($application, $creator_id);

        if ($application->entity->relation_loaded('activities')) {
            $application->entity->load_relation('activities');
        }

        return $application;
    }

    /**
     * Create a new application at some state, exclusively for use in workflow admin preview, and meant to be deleted.
     *
     * Note that the usual workflow_version, form_version, and assignment status safeguards are not observed,
     * and no application_activity record is created.
     *
     * @param application_state $start_state State to set the new application to
     * @param workflow_version $workflow_version Related workflow_version
     * @param assignment $assignment Parent assignment
     * @param int $creator_id ID of user that creates this application
     * @param int|null $applicant_id ID of applicant, or null to use the creator
     * @param job_assignment_entity|null $job_assignment Applicant's job assignment or null
     * @param string|null $title Optional title or null to use a workflow type name
     * @return static
     */
    public static function create_admin_preview(
        application_state $start_state,
        workflow_version $workflow_version,
        assignment $assignment,
        int $creator_id,
        ?int $applicant_id = null,
        ?job_assignment_entity $job_assignment = null,
        ?string $title = null
    ): self {
        return self::create_internal(
            $start_state,
            $workflow_version,
            $assignment,
            $creator_id,
            $applicant_id,
            $job_assignment,
            $title
        );
    }

    /**
     * Internal create method consolidating code used by public create methods.
     *
     * @param application_state $start_state
     * @param workflow_version $workflow_version
     * @param assignment $assignment
     * @param int $creator_id
     * @param int|null $applicant_id
     * @param job_assignment_entity|null $job_assignment
     * @param string|null $title
     * @return static
     */
    private static function create_internal(
        application_state $start_state,
        workflow_version $workflow_version,
        assignment $assignment,
        int $creator_id,
        ?int $applicant_id,
        ?job_assignment_entity $job_assignment,
        ?string $title
    ): self {
        // Check if the job_assignment belong to applicant
        if (!is_null($job_assignment) && $job_assignment->userid != $applicant_id) {
            throw new model_exception("Job assignment belongs to other user");
        }

        // Creator is applicant unless specified
        if (is_null($applicant_id)) {
            $applicant_id = $creator_id;
        }

        $workflow_type_name = $workflow_version->workflow->workflow_type->name;

        $entity = new application_entity();
        $entity->title = $title ?? $workflow_type_name;
        $entity->user_id = $applicant_id;
        if (!is_null($job_assignment)) {
            $entity->job_assignment_id = $job_assignment->id;
        }
        $entity->workflow_version_id = $workflow_version->id;
        $entity->form_version_id = $workflow_version->form_version->id;
        $entity->approval_id = $assignment->id;
        $entity->creator_id = $creator_id;
        $entity->owner_id = $creator_id;

        $entity->current_stage_id = $start_state->get_stage_id();
        $entity->is_draft = $start_state->is_draft();
        $entity->current_approval_level_id = $start_state->get_approval_level_id();
        $entity->save();

        self::update_id_number($entity, $workflow_type_name, $entity->created);

        return self::load_by_entity($entity);
    }

    /**
     * @param application_entity $entity
     * @param string $workflow_type_name
     * @param int $time
     */
    private static function update_id_number(application_entity $entity, string $workflow_type_name, int $time): void {
        // Base26 encode the first three bytes of the hash of the id number and the entity id.
        $time = DateTime::createFromFormat('U', $time)->setTimezone(core_date::get_server_timezone_object())->format('YmdHis');
        $hash = '';
        $value = hash_hmac('sha512', $workflow_type_name, dechex($entity->id), true);
        $value = ord($value[0]) + 256 * ord($value[1]) + 65536 * ord($value[2]);
        for ($i = 0; $i < 4; $i++) {
            $hash .= substr('ABCDEFGHIJKLMNOPQRSTUVWXYZ', $value % 26, 1);
            $value = intdiv($value, 26);
        }
        // Cut off the trailing title so that id_number will not exceed 255 chars.
        $id_number = substr($workflow_type_name, 0, 255 - strlen($time) - 4) . $time . $hash;
        $entity->id_number = $id_number;
        $entity->save();
    }

    /**
     * Get the applicant.
     *
     * @return user_entity
     */
    public function get_user(): user_entity {
        return $this->entity->user;
    }

    /**
     * Get the applicant's job assignment, if there is one.
     *
     * @return job_assignment|null
     */
    public function get_job_assignment(): ?job_assignment {
        if ($this->entity->job_assignment) {
            return job_assignment::from_entity($this->entity->job_assignment);
        } else {
            return null;
        }
    }

    /**
     * Get the workflow_version for this application
     *
     * @return workflow_version
     */
    public function get_workflow_version(): workflow_version {
        return workflow_version::load_by_entity($this->entity->workflow_version);
    }

    /**
     * Get the form_version for this application
     *
     * @return form_version
     */
    public function get_form_version(): form_version {
        return form_version::load_by_entity($this->entity->form_version);
    }

    /**
     * Get the workflow assignment for this application
     *
     * @return assignment
     */
    public function get_assignment(): assignment {
        return assignment::load_by_entity($this->entity->assignment);
    }

    /**
     * Get the creator of this application.
     *
     * @return user_entity
     */
    public function get_creator(): user_entity {
        return $this->entity->creator;
    }

    /**
     * Get the owner of this application.
     *
     * @return user_entity
     */
    public function get_owner(): user_entity {
        return $this->entity->owner;
    }

    /**
     * Get the user who submitted this application for the first time.
     *
     * @return user_entity|null
     */
    public function get_submitter(): ?user_entity {
        return $this->entity->submitter;
    }

    /**
     * Get the current workflow stage for this application
     *
     * @return workflow_stage
     */
    public function get_current_stage(): workflow_stage {
        return workflow_stage::load_by_entity($this->entity->current_stage);
    }

    /**
     * Get the next workflow stage for this application, the one after the current stage - if any.
     *
     * @return workflow_stage|null
     */
    public function get_next_stage(): ?workflow_stage {
        return $this->workflow_version->get_next_stage($this->current_state->get_stage()->id);
    }

    /**
     * Get the current workflow stage approval level for this application
     *
     * @return workflow_stage_approval_level|null
     */
    public function get_current_approval_level(): ?workflow_stage_approval_level {
        if (is_null($this->entity->current_approval_level_id)) {
            return null;
        } else {
            return workflow_stage_approval_level::load_by_id($this->entity->current_approval_level_id);
        }
    }

    /**
     * Get the current application state
     *
     * @return application_state
     */
    public function get_current_state(): application_state {
        return new application_state(
            $this->entity->current_stage_id,
            $this->entity->is_draft,
            $this->entity->current_approval_level_id
        );
    }

    /**
     * Get the actions for this application.
     *
     * @return collection|application_action[]
     */
    public function get_actions(): collection {
        return $this->entity->actions->map_to(application_action::class);
    }

    /**
     * Get the submissions for this application.
     *
     * @return collection|application_submission[]
     */
    public function get_submissions(): collection {
        return $this->entity->submissions->map_to(application_submission::class);
    }

    /**
     * Get the most recent unsuperseded application_submission on the application.
     *
     * @return application_submission|null
     */
    public function get_last_submission(): ?application_submission {
        $repository = application_submission_entity::repository()
            ->filter_by_application_id($this->id)
            ->where('superseded', 0)
            ->order_by('id', 'DESC');
        /** @var application_submission_entity $entity */
        $entity = $repository->first();
        if (!$entity) {
            return null;
        }
        return application_submission::load_by_entity($entity);
    }

    /**
     * Get the most recent unsuperseded published/submitted application_submission on the application.
     *
     * @return application_submission|null
     */
    public function get_last_published_submission(): ?application_submission {
        $repository = application_submission_entity::repository()
            ->filter_by_application_id($this->id)
            ->where('superseded', 0)
            ->where('submitted', '>', 0)
            ->order_by('submitted', 'DESC');
        /** @var application_submission_entity $entity */
        $entity = $repository->first();
        if (!$entity) {
            return null;
        }
        return application_submission::load_by_entity($entity);
    }

    /**
     * Get the last submission at the particular stage.
     *
     * @param integer $workflow_stage_id
     * @return application_submission|null
     */
    public function get_last_submission_for(int $workflow_stage_id): ?application_submission {
        $repository = application_submission_entity::repository()
            ->filter_by_application_id($this->id)
            ->where('superseded', 0)
            ->where('workflow_stage_id', '=', $workflow_stage_id)
            ->order_by('id', 'DESC');
        /** @var application_submission_entity $entity */
        $entity = $repository->first();
        if (!$entity) {
            return null;
        }
        return application_submission::load_by_entity($entity);
    }

    /**
     * Get the most recent unsuperseded application_action on the application.
     *
     * @return application_action|null
     */
    public function get_last_action(): ?application_action {
        /** @var application_action_entity $entity */
        $entity = application_action_entity::repository()
            ->where('application_id', '=', $this->id)
            ->where('superseded', '=', 0)
            ->order_by('id', 'DESC')
            ->first();
        if (!$entity) {
            return null;
        }
        return application_action::load_by_entity($entity);
    }

    /**
     * Get the activities for this application.
     *
     * @return collection|application_activity[]
     */
    public function get_activities(): collection {
        return $this->entity->activities->map_to(application_activity::class);
    }

    /**
     * Get workflow_type of this application.
     *
     * @return workflow_type
     */
    public function get_workflow_type(): workflow_type {
        return $this->workflow_version->workflow->workflow_type;
    }

    /**
     * Get "overall progress" enum of this application
     *
     * This is not an ENUM representation of the application state.
     * One of: DRAFT, IN_PROGRESS, REJECTED, FINISHED, WITHDRAWN
     *
     * @return string
     */
    public function get_overall_progress(): string {
        $last_action = $this->last_action;
        if ($last_action) {
            if ($last_action->code == reject_action::get_code()) {
                return 'REJECTED';
            } else if ($last_action->code == withdraw_in_approvals::get_code()
                || $last_action->code == withdraw_before_submission::get_code()) {
                return 'WITHDRAWN';
            }
        }

        $current_state = $this->current_state;
        if ($current_state->is_draft()) {
            return 'DRAFT';
        } else if ($current_state->is_stage_type(finished::get_code())) {
            return 'FINISHED';
        }

        return 'IN_PROGRESS';
    }

    /**
     * Get overall progress label of this application for display on dashboard.
     *
     * @return string
     */
    public function get_overall_progress_label(): string {
        $enum_value = $this->get_overall_progress();
        switch ($enum_value) {
            case 'REJECTED':
                return get_string('overall_progress_rejected', 'mod_approval');
            case 'WITHDRAWN':
                return get_string('overall_progress_withdrawn', 'mod_approval');
            case 'DRAFT':
                return get_string('overall_progress_draft', 'mod_approval');
            case 'FINISHED':
                return get_string('overall_progress_finished', 'mod_approval');
            default:
                return get_string('overall_progress_in_progress', 'mod_approval');
        }
    }

    /**
     * Get the "your progress" enum state of this application for a user, for display on dashboard.
     *
     * This is not an actual application state, rather an untranslated label for display.
     * One of: PENDING, APPROVED, REJECTED, or N/A
     *
     * @param int|null $user_id
     * @return string
     */
    public function get_your_progress(int $user_id = null): string {
        if (is_null($user_id)) {
            $user_id = user_entity::logged_in()->id;
        }
        if ($this->get_approver_users(null, true)->has('id', $user_id)) {
            return 'PENDING';
        }
        $actions = $this->actions->filter('user_id', $user_id);
        if ($actions->count()) {
            /** @var application_action $last_action */
            $last_action = $actions->last();
            if ($last_action->code === approve_action::get_code()) {
                return 'APPROVED';
            } else if ($last_action->code === reject_action::get_code()) {
                return 'REJECTED';
            }
        }
        return 'NA';
    }

    /**
     * Get the "your progress" state of this application for a user, for display on dashboard.
     *
     * This is not an actual application state, rather an untranslated label for display.
     * One of: Pending, Approved, Rejected, or N/A
     *
     * @param int|null $user_id
     * @return string
     */
    public function get_your_progress_label(int $user_id = null): string {
        $enum_value = $this->get_your_progress($user_id);
        if ($enum_value == 'PENDING') {
            return get_string('your_progress_pending', 'mod_approval');
        } else if ($enum_value == 'APPROVED') {
            return get_string('your_progress_approved', 'mod_approval');
        } else if ($enum_value == 'REJECTED') {
            return get_string('your_progress_rejected', 'mod_approval');
        }
        return get_string('your_progress_na', 'mod_approval');
    }

    /**
     * Get the application's context, from the parent assignment.
     *
     * @return context_module
     */
    public function get_context(): context_module {
        return $this->assignment->get_context();
    }

    /**
     * Delete this application.
     *
     * @param boolean $force Force delete (do not use it)
     * @return self
     */
    public function delete(bool $force = false): self {
        if ($force) {
            builder::get_db()->transaction(function () {
                // Delete any application_submissions for this application first.
                application_submission_entity::repository()
                    ->where('application_id', '=', $this->id)
                    ->delete();
                // Also delete any application_activity entries.
                application_activity_entity::repository()
                    ->where('application_id', '=', $this->id)
                    ->delete();
                // Also delete any application_action entries.
                application_action_entity::repository()
                    ->where('application_id', '=', $this->id)
                    ->delete();
                $this->entity->delete();
            });
            return $this;
        }
        if (!$this->current_state->is_draft()) {
            throw new model_exception("Unable to delete application not in draft state");
        }
        builder::get_db()->transaction(function () {
            // Delete any unsubmitted application_submissions for this application first.
            application_submission_entity::repository()
                ->where('application_id', '=', $this->id)
                ->where_null('submitted')
                ->delete();
            // Also delete any application_activity entries.
            application_activity_entity::repository()
                ->where('application_id', '=', $this->id)
                ->delete();
            $this->entity->delete();
        });
        return $this;
    }

    /**
     * Clone this application.
     *
     * @param int $cloner_id ID of the user who is doing the cloning
     * @return self
     */
    public function clone(int $cloner_id): self {
        // Fetch the latest instance in case 'this' is stale.
        $source = self::load_by_id($this->entity->id);
        $workflow_version = $source->workflow_version->workflow->active_version;
        if (!$workflow_version) {
            throw new model_exception("Workflow version must be active for clone application");
        }
        $transaction = builder::get_db()->start_delegated_transaction();
        // Fetch the latest active instance in case 'this' is stale.
        $destination = self::create(
            $workflow_version,
            $source->assignment,
            $cloner_id,
            $source->user->id,
            $source->entity->job_assignment,
            $source->title,
            $source
        );

        /** @var workflow_stage|null */
        $first_stage = $source->workflow_version->stages->first();
        if ($first_stage) {
            /** @var application_submission $source_last_submission */
            $source_last_submission = $source->submissions
                ->filter('workflow_stage_id', $first_stage->id)
                ->sort('id', 'desc')
                ->first();
            if ($source_last_submission) {
                $source_last_submission->clone($destination);
            }
            if ($destination->entity->relation_loaded('submissions')) {
                $destination->entity->load_relation('submissions');
            }
        }
        $transaction->allow_commit();
        return $destination;
    }

    /**
     * Get an interactor which can be used to determine if the given user can perform actions in relation to this application.
     *
     * @param int $interactor_user_id of the user who is performing the actions
     * @return application_interactor
     */
    public function get_interactor(int $interactor_user_id): application_interactor {
        return application_interactor::from_application_model($this, $interactor_user_id);
    }

    /**
     * Get a list of all the approver users for the given approval level.
     *
     * @param workflow_stage_approval_level|null $approval_level Set null for the current approval level
     * @param boolean $ignore_caps **Set true when calling from an interactor**
     *                             See application_interactor::is_pending for more details
     * @return collection|user_entity[] as [id => user]
     */
    public function get_approver_users(
        workflow_stage_approval_level $approval_level = null,
        bool $ignore_caps = false
    ): collection {
        if ($approval_level === null) {
            $approval_level = $this->current_approval_level;
        }
        if ($approval_level === null) {
            // Just return an empty collection.
            return new collection();
        }
        if (!$this->workflow_version->has_approval_level($approval_level->id)) {
            throw new invalid_parameter_exception('The requested approval_level does not belong to this application');
        }
        $resolver = assignment_approver_resolver::from_user($this->user_id, $this->job_assignment_id);
        $assignment_approver_level = new assignment_approval_level(
            $this->assignment,
            $approval_level
        );
        $assignment_approvers = $assignment_approver_level->get_approvers_with_inheritance();
        $users = $resolver->resolve($assignment_approvers);
        if (!$ignore_caps) {
            $users = $users->filter(function (user_entity $approver) {
                return $this->get_interactor($approver->id)->can_approve();
            });
        }
        return $users;
    }

    /**
     * Sets the application's current state
     *
     * Use this rather than setting state properties directly
     *
     * @param application_state $state
     *
     * todo Make private in TL-33182
     */
    public function set_current_state(application_state $state): void {
        $this->entity->current_stage_id = $state->get_stage_id();
        $this->entity->is_draft = $state->is_draft();
        $this->entity->current_approval_level_id = $state->get_approval_level_id();
        $this->entity->save();
        $this->entity->load_relation('current_stage');
        $this->entity->load_relation('current_approval_level');
    }

    /**
     * Change from the current state to the given state, recording all activities in the process.
     *
     * @param application_state $new_state
     * @param int|null $actor_id ID of the user who is performing the change, or null for cron etc.
     */
    public function change_state(application_state $new_state, ?int $actor_id): void {
        $current_state = $this->current_state;

        if ($current_state->is_same_as($new_state)) {
            return;
        }
        builder::get_db()->transaction(function () use ($new_state, $current_state, $actor_id) {
            $this->get_current_stage()->state_manager->on_state_exit($this, $new_state, $actor_id);
            $this->set_current_state($new_state);
            $this->get_current_stage()->state_manager->on_state_entry($this, $current_state, $actor_id);
        });
    }

    /**
     * Indicates whether the application has been marked submitted.
     *
     * @return bool
     */
    public function is_submitted(): bool {
        return !empty($this->entity->submitted);
    }

    /**
     * Marks the application as having been submitted for the first time by the given user at the current time.
     *
     * This can only be called when the application has not already been marked submitted. You could however remove the
     * previous submission data, to make it appear as if it had never been submitted, then mark it submitted again.
     *
     * @param int $submitter_id ID of the use who is performing the action
     */
    public function mark_submitted(int $submitter_id): void {
        if ($this->entity->submitted) {
            throw new coding_exception('Cannot submit application that has already been marked submitted');
        }

        $this->entity->submitted = time();
        $this->entity->submitter_id = $submitter_id;
        $this->entity->save();
    }

    /**
     * Marks the application as completed.
     *
     * @return void
     */
    public function mark_completed(): void {
        $this->entity->completed = time();
        $this->entity->save();
    }
}
