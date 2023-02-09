<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\models\activity;

use coding_exception;
use context_module;
use core\collection;
use core\entity\user;
use core\orm\entity\model;
use core\orm\lazy_collection;
use core\orm\query\builder;
use mod_perform\constants;
use mod_perform\entity\activity\activity as activity_entity;
use mod_perform\entity\activity\activity_setting as activity_setting_entity;
use mod_perform\entity\activity\subject_instance as subject_instance_entity;
use mod_perform\event\activity_subject_instances_closed;
use mod_perform\event\subject_instance_manual_participants_selected;
use mod_perform\models\due_date;
use mod_perform\models\activity\helpers\manual_participant_helper;
use mod_perform\rb\util;
use mod_perform\entity\activity\track;
use mod_perform\entity\activity\track_user_assignment;
use mod_perform\state\activity\active as activity_status_active;
use mod_perform\state\participant_instance\open as participant_instance_open;
use mod_perform\state\state;
use mod_perform\state\state_aware;
use mod_perform\state\subject_instance\active;
use mod_perform\state\subject_instance\closed;
use mod_perform\state\subject_instance\complete;
use mod_perform\state\subject_instance\open;
use mod_perform\state\subject_instance\pending;
use mod_perform\state\subject_instance\subject_instance_availability;
use mod_perform\state\subject_instance\subject_instance_manual_status;
use mod_perform\state\subject_instance\subject_instance_progress;
use mod_perform\event\subject_instance_manually_deleted;
use moodle_exception;
use stdClass;
use totara_core\relationship\relationship;
use totara_job\job_assignment;

/**
 * Class subject_instance
 *
 * This class represents a specific activity about a specific person (subject_instance)
 *
 * @property-read int $id
 * @property-read user $subject_user The user that this activity is about
 * @property-read int $subject_user_id The user id for the user this instance is about
 * @property-read int $created_at When this instance was created.
 * @property-read int $due_date When this instance is due to be completed.
 * @property-read int $status Whether the instance is pending or not
 * @property-read int $progress The progress status code
 * @property-read int $availability The availability status code
 * @property-read activity $activity The top level perform activity this is an instance of
 * @property-read collection|participant_instance[] $participant_instances models created from participant_instance entities
 * @property-read int|null $job_assignment_id
 * @property-read job_assignment|null $job_assignment The job assignment this instance is in relation to (per job activities),
 *                                               null for per user activities
 * @property-read string $progress_status internal name of current progress state
 * @property-read subject_instance_progress|state $progress_state Current progress state
 * @property-read subject_instance_availability|state $availability_state Current availability state
 * @property-read subject_instance_manual_status|state $manual_state Current manual status state
 * @property-read bool $is_overdue
 * @property-read int $instance_count
 * @property-read collection|subject_static_instance[] $static_instances
 * @property-read int|null $closed_at When this instance was closed.
 *
 * @package mod_perform\models\activity
 */
class subject_instance extends model {

    use state_aware;

    protected $entity_attribute_whitelist = [
        'id',
        'subject_user_id',
        'job_assignment_id',
        'created_at',
        'progress',
        'availability',
        'closed_at',
        'due_date',
        'status',
    ];

    protected $model_accessor_whitelist = [
        'activity',
        'participant_instances',
        'job_assignment',
        'progress_status',
        'availability_status',
        'progress_state',
        'availability_state',
        'manual_state',
        'is_overdue',
        'subject_user',
        'instance_count',
        'static_instances',
        'due_on'
    ];

    /** @var subject_instance_entity */
    protected $entity;

    public function __construct(subject_instance_entity $subject_instance) {
        parent::__construct($subject_instance);
    }

    /**
     * @inheritDoc
     */
    protected static function get_entity_class(): string {
        return subject_instance_entity::class;
    }

    /**
     * Closes open (including pending) subject instances associated with the
     * given activity.
     *
     * @param activity $activity activity whose subject instances are to be closed.
     * @param int $user_id the user who is closing the instances.
     */
    public static function close_subject_instances_in_activity(
        activity $activity,
        int $user_id
    ): void {
        $id = $activity->id;
        $si_id_alias = subject_instance_entity::TABLE . '.subject_user_id';
        [$sql, $params] = util::get_manage_participation_sql($user_id, $si_id_alias);

        $subject_instances = subject_instance_entity::repository()
            ->filter_by_activity_id($id)
            ->join(['context', 'ctx'], function (builder $builder) use ($si_id_alias) {
                $builder->where_field($si_id_alias, 'ctx.instanceid')
                    ->where('ctx.contextlevel', CONTEXT_USER);
            })
            ->where('availability', open::get_code())
            ->where_raw($sql, $params)
            ->get()
            ->map_to(self::class);

        if ($subject_instances->count() === 0) {
            // Nothing to close
            return;
        }

        $errors = $subject_instances->reduce(
            function (string $errors, subject_instance $si): string {
                try {
                    $si->manually_close(true);
                } catch (moodle_exception $e) {
                    $error = sprintf('subject instance %d: %s', $si->id, $e->getMessage());
                    $errors = $errors ? "$errors\n$error" : $error;
                }

                return $errors;
            },
            ''
        );

        if ($errors) {
            throw new moodle_exception(
                "could not close subject instances in '$id':\n$errors"
            );
        }

        activity_subject_instances_closed::create_from_activity($activity)->trigger();
    }

    /**
     * @return activity The abstract perform activity that this user activity is an instance of
     */
    public function get_activity(): activity {
        $activity_entity = $this->entity->activity();

        return activity::load_by_entity($activity_entity);
    }

    /**
     * Get the context object for the overarching abstract perform activity (perform in the database).
     *
     * @return context_module
     */
    public function get_context(): context_module {
        return $this->get_activity()->get_context();
    }

    /**
     * Get internal name of current progress state.
     *
     * @return string
     */
    public function get_progress_status(): string {
        return $this->get_progress_state()->get_name();
    }

    /**
     * Get internal name of current availability state.
     *
     * @return string
     */
    public function get_availability_status(): string {
        return $this->get_availability_state()->get_name();
    }

    /**
     * Returns due date details.
     *
     * @return due_date the due date details or null if there is no due date.
     */
    public function get_due_on(): ?due_date {
        return !$this->is_complete() && !empty($this->entity->due_date)
            ? new due_date($this->entity->due_date)
            : null;
    }

    /**
     * Checks if overdue
     *
     * @return bool
     */
    public function get_is_overdue(): bool {
        $due_date = $this->get_due_on();
        return !is_null($due_date) ? $due_date->is_overdue() : false;
    }

    /**
     * Checks if subject instance is complete.
     *
     * @return bool
     */
    public function is_complete(): bool {
        return $this->get_progress_state() instanceof complete;
    }

    /**
     * Update progress status.
     *
     * Must be called when something happened that can affect the progress status.
     */
    public function update_progress_status() {
        /** @var subject_instance_progress $state */
        $state = $this->get_progress_state();
        $state->update_progress();
    }

    public function get_current_state_code(string $state_type): int {
        return $this->{$state_type};
    }

    protected function update_state_code(state $state): void {
        $this->entity->{$state::get_type()} = $state::get_code();
        $this->entity->update();
    }

    /**
     * Get the subject user.
     *
     * @return participant
     */
    public function get_subject_user(): participant {
        return new participant($this->entity->subject_user);
    }

    /**
     * @return participant_instance[]|collection
     */
    public function get_participant_instances(): collection {
        return $this->entity->participant_instances->map_to(participant_instance::class);
    }

    /**
     * Get participant instances with the relationships.
     *
     * @param array|int[] $relationship_ids
     * @return participant_instance[]|collection
     */
    public function get_participant_instances_with_relationships(array $relationship_ids): collection {
        return $this->get_participant_instances()->filter(
            function (participant_instance $participant_instance) use ($relationship_ids) {
                return in_array($participant_instance->core_relationship_id, $relationship_ids);
            }
        );
    }

    /**
     * @return job_assignment|null
     */
    public function get_job_assignment(): ?job_assignment {
        if ($this->entity->job_assignment === null) {
            return null;
        }

        return job_assignment::from_entity($this->entity->job_assignment);
    }

    /**
     * Get all the static instances this subject has.
     *
     * @return job_assignment[]
     */
    public function get_static_instances(): array {
        $models = $this->entity->static_instances->map_to(subject_static_instance::class);

        /** @var subject_static_instance $model */
        $jobs = [];
        foreach ($models as $model) {
            $jobs[] = $model->get_job_assignment();
        }

        return $jobs;
    }

    /**
     * Get progress state class.
     *
     * @return subject_instance_progress
     */
    public function get_progress_state(): state {
        return $this->get_state(subject_instance_progress::get_type());
    }

    /**
     * Get the current availability state.
     *
     * @return subject_instance_availability|state
     */
    public function get_availability_state(): state {
        return $this->get_state(subject_instance_availability::get_type());
    }

    /**
     * Returns true if this instance is open
     *
     * @return bool
     */
    public function is_open(): bool {
        return $this->get_availability_state() instanceof open;
    }

    /**
     * Returns true if this instance is closed
     *
     * @return bool
     */
    public function is_closed(): bool {
        return $this->get_availability_state() instanceof closed;
    }

    /**
     * Get the current manual status state.
     *
     * @return subject_instance_manual_status|state
     */
    public function get_manual_state(): state {
        return $this->get_state(subject_instance_manual_status::get_type());
    }

    /**
     * Returns true if this instance is in active state
     *
     * @return bool
     */
    public function is_active(): bool {
        return $this->get_manual_state() instanceof active;
    }

    /**
     * Returns true if this instance is in pending state
     *
     * @return bool
     */
    public function is_pending(): bool {
        return $this->get_manual_state() instanceof pending;
    }

    /**
     * Check whether manual participants can be added
     *
     * @return bool
     */
    public function can_add_participants(): bool {
        // Cannot add participants to pending subject instances
        if ($this->is_pending()
            || !$this->activity->is_active()
        ) {
            return false;
        }
        return true;
    }

    /**
     * Set the users for each relevant manual relationship to participate in this subject's activity.
     *
     * @param int $by_user User ID of who is setting the participants.
     * @param array[] $relationships_and_participants Array of ['manual_relationship_id' => int, 'users' => ['user_id'/'email' ...]]
     */
    public function set_participant_users(int $by_user, array $relationships_and_participants): void {
        global $DB;
        $manual_participant_helper = manual_participant_helper::for_user($by_user);

        if (!$this->is_pending()) {
            throw new coding_exception("Subject instance {$this->id} is not pending.");
        }

        if (!$manual_participant_helper->has_pending_selections($this->id)) {
            throw new coding_exception("User id {$by_user} does not have any pending selections for subject instance {$this->id}");
        }

        $relationship_ids = array_column($relationships_and_participants, 'manual_relationship_id');
        $manual_participant_helper->validate_participant_relationship_ids($this->id, $relationship_ids);

        $DB->transaction(function () use ($relationships_and_participants, $by_user, $manual_participant_helper) {
            foreach ($relationships_and_participants as $relationship_and_participants) {
                $relationship_id = $relationship_and_participants['manual_relationship_id'];
                $users = $relationship_and_participants['users'];

                if (relationship::load_by_id($relationship_id)->idnumber === constants::RELATIONSHIP_EXTERNAL) {
                    subject_instance_manual_participant::create_multiple_for_external(
                        $this->id, $by_user, $relationship_id, $users
                    );
                } else {
                    subject_instance_manual_participant::create_multiple_for_internal(
                        $this->id, $by_user, $relationship_id, $users
                    );
                }

                $manual_participant_helper->set_progress_complete($this->id, $relationship_id);
            }
        });

        subject_instance_manual_participants_selected::create_from_selected_participants($relationships_and_participants, $this)
            ->trigger();

        if (!$this->manual_state->can_switch(active::class)) {
            return;
        }

        $this->switch_state(active::class);

        $this->entity->refresh();
        if ($this->entity->relation_loaded('participant_instances')) {
            $this->entity->load_relation('participant_instances');
        }
    }

    /**
     * Manually close the subject instance
     *
     * Related participant instances and sections may be affected by this action.
     *
     * The following changes are applied, in this order:
     * - Change availability to "Closed"
     * - If progress is "Not yet started" or "In progress" then set progress to "Not submitted"
     * - Change participant instances availability to "Closed"
     * - If participant instances progress is "Not yet started" or "In progress" then set progress to "Not submitted"
     * - Change participant sections availability to "Closed"
     * - If participant sections progress is "Not yet started" or "In progress" then set progress to "Not submitted"
     *
     * @param bool $close_pending  When true: close even if subject instance is in pending status.
     * This should only be used before deletion of the subject instance.
     *
     * @return void
     */
    public function manually_close(bool $close_pending = false): void {
        if (!$this->is_open()) {
            throw new coding_exception('This function can only be called if the subject instance is open');
        }
        if (!$close_pending && $this->is_pending()) {
            throw new coding_exception('Cannot close a pending subject instance.');
        }

        $this->get_availability_state()->close();
        $this->get_progress_state()->manually_complete();

        foreach ($this->participant_instances as $participant_instance) {
            // This will trigger an event which will end up calling $this->update_progress_status!
            if ($participant_instance->get_availability_state() instanceof participant_instance_open) {
                $participant_instance->manually_close();
            }
        }
    }

    /**
     * Manually open the subject instance
     *
     * Related participant instances and sections may be affected by this action.
     *
     * The following changes are applied, in this order:
     * - Change participant sections availability to "Open"
     * - Recalculate participant sections progress, either "Not yet started" or "In progress"
     * - Change participant instances availability to "Open"
     * - Recalculate participant instances progress, either "Not yet started" or "In progress"
     * - Change availability to "Open"
     * - Recalculate progress, either "Not yet started" or "In progress"
     *
     * @param bool $open_children
     */
    public function manually_open(bool $open_children = true): void {
        if (!$this->is_closed()) {
            throw new coding_exception('This function can only be called if the subject instance is closed');
        }
        if ($this->is_pending()) {
            throw new coding_exception('Cannot open a pending subject instance.');
        }

        if ($open_children) {
            foreach ($this->participant_instances as $participant_instance) {
                // This will trigger an event which will end up calling $this->update_progress_status!
                $participant_instance->manually_open(false, true);
            }
        }

        $this->get_availability_state()->open();
        $this->get_progress_state()->manually_uncomplete();
    }

    /**
     * Get the number of instances for this particular subject-user, track, and activity.
     *
     * @return int
     */
    public function get_instance_count(): int {
        $row = builder::table(subject_instance_entity::TABLE)
            ->select_raw('count(*) as count')
            ->where('track_user_assignment_id', $this->entity->track_user_assignment_id)
            ->where('created_at', '<=', $this->entity->created_at)
            ->one(true);

        return $row->count;
    }

    /**
     * Returns a record representation of the underlying entity
     *
     * @return stdClass
     */
    public function to_record(): stdClass {
        return (object) $this->entity->get_attributes_raw();
    }

    /**
     * Checks whether this subject instance should be hidden in the ui.
     * Based on the subject being hidden or suspended (if applicable).
     *
     * @return bool
     */
    public function should_be_hidden(): bool {
        if ($this->is_subject_user_deleted()) {
            return true;
        }

        return get_config(null, 'perform_hide_suspended_users') && $this->is_subject_user_suspended();
    }

    /**
     * Checks whether the subject user is deleted
     *
     * @return bool
     */
    public function is_subject_user_deleted(): bool {
        return $this->subject_user->deleted;
    }

    /**
     * Checks whether the subject user is suspended
     *
     * @return bool
     */
    private function is_subject_user_suspended(): bool {
        return $this->subject_user->suspended;
    }

    /**
     * Manually delete the subject instance and linked records
     *
     * @return void
     */
    public function manually_delete(): void {
        $deleted_event = subject_instance_manually_deleted::create_from_subject_instance($this);
        builder::get_db()->transaction(function () {

            if (!($this->get_availability_state() instanceof closed)) {
                $this->manually_close(true);
            }

            foreach ($this->participant_instances as $participant_instance) {
                $participant_instance->manually_delete();
            }

            $this->entity->delete();
        });
        $deleted_event->trigger();
    }

    /**
     * Get all subject instances that are due to be closed according to activity settings and appropriate statuses.
     *
     * @return collection|subject_instance_entity[]
     */
    public static function get_subject_instances_due_to_be_closed(): collection {
        $now = time();

        return subject_instance_entity::repository()
            ->join([track_user_assignment::TABLE, 'tua'], 'track_user_assignment_id', 'id')
            ->join([track::TABLE, 'track'], 'tua.track_id', 'id')
            ->join([activity_setting_entity::TABLE, 'setting'], 'track.activity_id', 'activity_id')
            ->join([activity_entity::TABLE, 'activity'], 'activity.id', 'setting.activity_id')
            ->where('setting.name', activity_setting::CLOSE_ON_DUE_DATE)
            ->where('setting.value', 1)
            ->where('activity.status', activity_status_active::get_code())
            ->where('availability', open::get_code())
            ->where('due_date', '<', $now)
            ->get();
    }

    /**
     * Get all subject instances for suspended users that aren't closed yet.
     * Could be a lot, so we return a lazy_collection.
     *
     * @return lazy_collection|subject_instance_entity[]
     */
    public static function get_subject_instances_to_close_for_suspended_users(): lazy_collection {
        return subject_instance_entity::repository()
            ->join([user::TABLE, 'u'], 'subject_user_id', 'id')
            ->where('u.suspended', 1)
            ->where('availability', open::get_code())
            ->get_lazy();
    }
}
