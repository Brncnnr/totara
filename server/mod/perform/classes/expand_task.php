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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform;

use context;
use core\entity\expandable;
use core\entity\user;
use core\orm\collection;
use core\orm\entity\entity;
use core\orm\query\builder;
use mod_perform\dates\constants;
use mod_perform\dates\resolvers\anniversary_of;
use mod_perform\entity\activity\track_assignment;
use mod_perform\entity\activity\track_assignment_repository;
use mod_perform\entity\activity\track_user_assignment;
use mod_perform\entity\activity\track_user_assignment_via;
use mod_perform\event\track_user_assigned_bulk;
use mod_perform\event\track_user_unassigned;
use mod_perform\models\activity\track;
use mod_perform\models\activity\track_assignment as track_assignment_model;
use mod_perform\task\expand_task\assignment_parameters;
use mod_perform\task\expand_task\assignment_parameters_collection;
use mod_perform\task\service\track_schedule_sync;
use mod_perform\user_groups\grouping;

/**
 * This class performs the creation of track_user_assignment records for track_assignments.
 *
 * "Expansion" refers to the expanding of groups of users to the individual users.
 *
 * It goes through all assignments scheduled for expansion and determines whether there are new
 * track_user_assignments to be created or existing one reactivated which were previously deleted.
 *
 * For each user there will always only be one track_user_assignment record per track.
 *
 * @package mod_perform
 */
class expand_task {

    private $assign_buffer = [];

    /**
     * This variable is set at the very beginning to have
     * a time to identify all records belonging to this run.
     * It's not a unique identifier but it would give us an indication
     * of which records were created by the same task as usually a task
     * can run only once.
     *
     * @var int
     */
    private $time_for_run;

    /**
     * Don't use the constructor directly, use the factory create() method
     * provided to create a new instance of the expand task.
     *
     * @param int|null $time_for_run
     */
    private function __construct(int $time_for_run = null) {
        $this->time_for_run = $time_for_run ?? time();
    }

    /**
     * Factory method to create a new instance. This will ensure
     * the time_for_run is not the same in this script run
     *
     * @return static
     */
    public static function create(): self {
        static $time_for_run;
        $time_for_run = time() !== $time_for_run ? time() : $time_for_run + 1;

        return new expand_task($time_for_run);
    }

    /**
     * Expand all active assignments
     *
     * @param bool $force_not_flagged Also expand assignments not flagged for expansion.
     */
    public function expand_all(bool $force_not_flagged = false) {
        $assignments = $this->build_query($force_not_flagged)->get_lazy();
        foreach ($assignments as $assignment) {
            $this->expand_assignment($assignment);
        }

        $this->delete_orphaned_user_assignments();
    }

    /**
     * Expand only given assignments. Non existing or inactive assignments will be ignored.
     *
     * @param array $assignment_ids
     */
    public function expand_multiple(array $assignment_ids) {
        $assignment_ids = $this->sanitise_ids($assignment_ids);
        if (!empty($assignment_ids)) {
            $assignments = $this->build_query()
                ->filter_by_ids($assignment_ids)
                ->get_lazy();

            foreach ($assignments as $assignment) {
                $this->expand_assignment($assignment);
            }
        }

        $this->delete_orphaned_user_assignments();
    }

    /**
     * Expand a single assignment with the given id. Missing assignment is ignored.
     *
     * @param int $assignment_id
     */
    public function expand_single(int $assignment_id) {
        /** @var track_assignment $assignment */
        $assignment = $this->build_query()
            ->where('id', $assignment_id)
            ->one();

        if ($assignment) {
            $this->expand_assignment($assignment);
        }

        $this->delete_orphaned_user_assignments();
    }

    /**
     * Builds core query for all expand variations
     *
     * @param bool $force_not_flagged
     * @return track_assignment_repository
     */
    private function build_query(bool $force_not_flagged = false): track_assignment_repository {
        $repository = track_assignment::repository();

        if (!$force_not_flagged) {
            $repository->filter_by_expand();
        }

        return $repository->filter_by_active_track_and_activity()
            ->order_by('id');
    }

    /**
     * Expand the given assignment.
     *
     * @param track_assignment $assignment
     */
    private function expand_assignment(track_assignment $assignment): void {
        // Reset the expand flag early as something could change it in between
        $assignment->expand = 0;
        $assignment->save();

        // load all current source targets relations of the assignment
        // to avoid more requests to the database when checking if entry
        // already exists.
        $existing_user_assignments = $this->load_current_entries($assignment);

        $assignment_model = track_assignment_model::load_by_entity($assignment);
        $track = $assignment_model->track;

        // Get all users currently in the group
        $expanded_user_ids = $this->get_expanded_users(
            $assignment->user_group_type,
            $assignment->user_group_id,
            $track->activity->get_context()
        );

        // Get a collection of parameters which match the users currently in the group
        $parameters_collection = $this->get_assignment_parameters_collection($track, $expanded_user_ids);

        // Find all parameters where we do not have an existing user assignments yet
        $assignments_to_create = $parameters_collection->remove_matching_user_assignments($existing_user_assignments);

        $transaction = builder::get_db()->start_delegated_transaction();

        if ($assignments_to_create->count() > 0) {
            // A user assignment could already exist linked to a different assignment
            // but we want a user to be assigned only once per track.
            // Let's get all existing user_assignments for the track to check
            // whether if a user_assignment already exists. If yes, link and potentially reactivate it.
            $existing_track_user_assignments = $this->get_existing_user_assignments_for_track(
                $assignment,
                $assignments_to_create->pluck_user_ids()
            );

            $user_ids_assigned = [];
            $assignment_to_link = [];

            foreach ($assignments_to_create as $key => $assignment_to_create) {
                // For performance reasons we use the same search key as created above
                $existing_track_user_assignment = $existing_track_user_assignments->item($key);

                if ($existing_track_user_assignment) {
                    $assignment_to_link[] = $existing_track_user_assignment;
                    if ($existing_track_user_assignment->deleted) {
                        $this->reactivate_user_assignment($existing_track_user_assignment, $track);
                    }
                } else {
                    $new_users_assigned = $this->add_to_assign_buffer($assignment, $assignment_to_create);
                    foreach ($new_users_assigned as $user_id) {
                        $user_ids_assigned[$user_id] = $user_id;
                    }
                }
            }

            // Make sure that the buffer got flushed
            $new_users_assigned = $this->flush_assign_buffer($assignment);
            foreach ($new_users_assigned as $user_id) {
                $user_ids_assigned[$user_id] = $user_id;
            }

            // Insert new rows to link the user_assignments with the assignments
            $this->link_new_assignments($assignment, $user_ids_assigned);
            $this->link_existing_assignments($assignment, collection::new($assignment_to_link));

            // Trigger an event with all just newly created user assignments
            if (!empty($user_ids_assigned)) {
                track_user_assigned_bulk::create_from_track_assignment(
                    $assignment_model,
                    $user_ids_assigned
                )->trigger();
            }
        }

        // Unlink all records which are not in the current assignment anymore
        $this->unlink_users($assignment, $existing_user_assignments, $parameters_collection);

        $transaction->allow_commit();
    }

    /**
     * Load all current entries for the given assignment, key uniquely identifies the source target
     *
     * @param track_assignment $assignment
     * @return collection|track_user_assignment[]
     */
    private function load_current_entries(track_assignment $assignment): collection {
        return $assignment->user_assignments()->get();
    }

    /**
     * Expand the user group, means we get all individual users
     * who are in that group at this point in time
     *
     * @param int $user_group_type one of the grouping enums.
     * @param int $user_group_id
     * @param context $context we need the context for multi tenancy compatibility
     * @return array
     */
    private function get_expanded_users(
        int $user_group_type,
        int $user_group_id,
        context $context
    ): array {
        return $this->expand_entity($user_group_type, $user_group_id, $context);
    }

    /**
     * Expand the entity, if possible, to individual entries. for example expand a cohort to it's members
     *
     * @param string $type
     * @param int $target_id
     * @param context $context we need the context for multi tenancy compatibility
     * @return array
     */
    private function expand_entity(string $type, int $target_id, context $context): array {
        /** @var entity $class_name */
        $class_name = grouping::get_entity_class_by_user_group_type($type);
        if (is_subclass_of($class_name, expandable::class)) {
            if (!is_subclass_of($class_name, entity::class)) {
                throw new \coding_exception('Currently only entities can be expanded');
            }
            // Try to load the user group entity to make sure it's there
            /** @var expandable $entity */
            $entity = $class_name::repository()->find($target_id);
            if (!$entity) {
                // User group got probably deleted
                // in this case return empty result which will trigger unassigning
                // of all users still assigned to that group
                return [];
            }
            return $entity->expand($context);
        } else if ($class_name === user::class) {
            $user = $class_name::repository()->find($target_id);

            // We are only interested in non-deleted users
            return $user && !$user->deleted ? [$target_id] : [];
        }
        return [$target_id];
    }

    /**
     * Get an array with entries consisting of user_id and job_assignment_id.
     *
     * If we are running in per job assignment mode there will be one entry
     * for each users job assignment.
     *
     * If we are running in per user mode there will be an entry for each
     * user, and the job_assignment_id value will be null.
     *
     * @param track $track
     * @param array $users_ids
     * @return assignment_parameters_collection
     */
    private function get_assignment_parameters_collection(
        track $track,
        array $users_ids
    ): assignment_parameters_collection {
        if ($track->is_per_job_subject_instance_generation()) {
            $users_ids_chunks = array_chunk($users_ids, $this->get_max_per_chunk());
            $job_assignments = [];
            foreach ($users_ids_chunks as $users_ids_chunk) {
                $job_assignments_temp = builder::table('job_assignment')
                    ->select(['id', 'userid'])
                    ->where('userid', $users_ids_chunk)
                    ->get();

                foreach ($job_assignments_temp as $item) {
                    $job_assignments[] = $item;
                }
            }

            return assignment_parameters_collection::create_from_job_assignments($job_assignments);
        }

        return assignment_parameters_collection::create_from_user_ids($users_ids);
    }

    /**
     * @param track_assignment $assignment
     * @param array $user_ids
     * @return collection|track_user_assignment[]
     */
    private function get_existing_user_assignments_for_track(track_assignment $assignment, array $user_ids): collection {
        $user_ids_chunks = array_chunk($user_ids, builder::get_db()->get_max_in_params());

        $result = [];
        foreach ($user_ids_chunks as $user_ids_chunk) {
            /** @var track_user_assignment[] $user_assignments */
            $user_assignments = track_user_assignment::repository()
                ->where('track_id', $assignment->track_id)
                ->where('subject_user_id', $user_ids_chunk)
                ->get();

            foreach ($user_assignments as $user_assignment) {
                $result[$user_assignment->key] = $user_assignment;
            }
        }

        return collection::new($result);
    }

    /**
     * Add to assign buffer, also flushes the buffer if max amount per buffer is reached
     *
     * @param track_assignment $assignment
     * @param assignment_parameters $assignment_parameters
     * @return array user_ids assigned
     */
    private function add_to_assign_buffer(track_assignment $assignment, assignment_parameters $assignment_parameters): array {
        $this->assign_buffer[] = [
            'track_id' => $assignment->track_id,
            'subject_user_id' => $assignment_parameters->get_user_id(),
            'created_at' => $this->time_for_run,
            'deleted' => 0,
            'job_assignment_id' => $assignment_parameters->get_job_assignment_id()
        ];

        $count = count($this->assign_buffer);
        // Make sure we inset multiple rows but only to a certain limit
        // to limit memory consumption and prevent leaks
        if ($count >= BATCH_INSERT_MAX_ROW_COUNT) {
            return $this->flush_assign_buffer($assignment);
        }
        return [];
    }

    /**
     * Flush buffer and assign all in bulk (only if buffer count is reached)
     *
     * @param track_assignment $assignment
     * @return array user_ids assigned
     */
    private function flush_assign_buffer(track_assignment $assignment): array {
        if (!empty($this->assign_buffer)) {
            $user_ids_assigned = $this->assign_bulk($assignment, $this->assign_buffer);
            $this->assign_buffer = [];
            return $user_ids_assigned;
        }
        return [];
    }

    /**
     * Create multiple assignments with the least amount of queries
     *
     * @param track_assignment $assignment
     * @param array $to_create
     * @return array the user_ids assigned
     */
    private function assign_bulk(track_assignment $assignment, array $to_create): array {
        // Bulk fetch all the start and end reference dates.

        $track_model = new track($assignment->track);
        $date_resolver = $track_model->get_date_resolver(collection::new($to_create));

        if ($assignment->track->schedule_use_anniversary) {
            $date_resolver = new anniversary_of($date_resolver, $this->time_for_run);
        }

        $resolver_base = $date_resolver->get_resolver_base();
        // Add the dates to the assignments.
        foreach ($to_create as $index => $row) {
            if ($resolver_base == constants::DATE_RESOLVER_JOB_BASED) {
                $to_create[$index]['period_start_date'] = $date_resolver->get_start($row['job_assignment_id']);
                $to_create[$index]['period_end_date'] = $date_resolver->get_end($row['job_assignment_id']);
            } else {
                $to_create[$index]['period_start_date'] = $date_resolver->get_start($row['subject_user_id']);
                $to_create[$index]['period_end_date'] = $date_resolver->get_end($row['subject_user_id']);
            }
        }

        // Insert the assignments.
        builder::get_db()->insert_records('perform_track_user_assignment', $to_create);

        return array_column($to_create, 'subject_user_id');
    }

    /**
     * Link multiple user assignments to an existing assignment
     *
     * @param track_assignment $assignment
     * @param collection $existing_user_assignments
     */
    private function link_existing_assignments(
        track_assignment $assignment,
        collection $existing_user_assignments
    ) {
        $inserts = [];
        foreach ($existing_user_assignments as $user_assignment) {
            $inserts[] = (object) [
                'track_user_assignment_id' => $user_assignment->id,
                'track_assignment_id' => $assignment->id,
                'created_at' => $this->time_for_run
            ];
        }
        if (!empty($inserts)) {
            builder::get_db()->insert_records_via_batch('perform_track_user_assignment_via', $inserts);
        }
    }

    /**
     * Create the track_user_assignments_via records.
     *
     * @param track_assignment $assignment
     * @param array $user_ids
     */
    private function link_new_assignments(track_assignment $assignment, array $user_ids): void {
        $user_ids_chunks = array_chunk($user_ids, $this->get_max_per_chunk());

        foreach ($user_ids_chunks as $user_ids_chunk) {
            [$user_ids_sql, $user_ids_params] = builder::get_db()->get_in_or_equal($user_ids_chunk, SQL_PARAMS_NAMED);

            $sql = "
                INSERT INTO {perform_track_user_assignment_via}
                    (track_assignment_id, track_user_assignment_id, created_at)
                SELECT {$assignment->id}, id, {$this->time_for_run}
                FROM {perform_track_user_assignment}
                WHERE track_id = {$assignment->track_id}
                    AND subject_user_id {$user_ids_sql}
                    AND deleted = 0
                    AND created_at = {$this->time_for_run}
            ";

            builder::get_db()->execute($sql, $user_ids_params);
        }
    }

    /**
     * Restore single user assignment
     *
     * @param track_user_assignment $user_assignment
     * @param track $track
     */
    private function reactivate_user_assignment(track_user_assignment $user_assignment, track $track): void {
        $this->sync_schedule_for_user_assignments($track, collection::new([$user_assignment]));

        $user_assignment->deleted = 0;
        $user_assignment->save();
    }

    /**
     * @param track $track
     * @param collection|track_user_assignment[] $user_assignments
     */
    private function sync_schedule_for_user_assignments(track $track, collection $user_assignments): void {
        // Bulk fetch all the start and end reference dates.;
        $date_resolver = $track->get_date_resolver($user_assignments);

        track_schedule_sync::sync_user_assignment_schedules(
            $date_resolver,
            $user_assignments,
            $track->schedule_use_anniversary
        );
    }

    /**
     * Unlink all user assignments from the assignment which are not in the user group anymore
     *
     * @param track_assignment $assignment
     * @param collection $current_records keys are identifier, values are entities
     * @param assignment_parameters_collection $assignment_parameter_collection newly determined user ids job
     *                                         assignment combinations which should be in the group now
     */
    private function unlink_users(
        track_assignment $assignment,
        collection $current_records,
        assignment_parameters_collection $assignment_parameter_collection
    ): void {
        $records_to_delete = $current_records->filter(
            function (track_user_assignment $track_user_assignment) use ($assignment_parameter_collection) {
                return !$assignment_parameter_collection->find_from_track_user_assignment($track_user_assignment);
            }
        );

        if ($records_to_delete->count() > 0) {
            $ids_chunks = array_chunk($records_to_delete->pluck('id'), $this->get_max_per_chunk());
            foreach ($ids_chunks as $ids_chunk) {
                track_user_assignment_via::repository()
                    ->where('track_assignment_id', $assignment->id)
                    ->where('track_user_assignment_id', $ids_chunk)
                    ->delete();
            }
        }
    }

    /**
     * Mark all user assignments which are not linked to any assignment anymore as deleted
     */
    public function delete_orphaned_user_assignments(): void {
        $orphaned_user_assignments = track_user_assignment::repository()
            ->left_join([track_user_assignment_via::TABLE, 'via'], 'id', 'track_user_assignment_id')
            ->where('deleted', false)
            ->where_null('via.id')
            ->get();

        if ($orphaned_user_assignments->count() > 0) {
            $sql = "
                UPDATE {perform_track_user_assignment}
                SET deleted = 1, updated_at = {$this->time_for_run}
                WHERE NOT EXISTS (
                    SELECT id
                    FROM {perform_track_user_assignment_via} tuav
                    WHERE tuav.track_user_assignment_id = {perform_track_user_assignment}.id
                )
            ";

            builder::get_db()->execute($sql, []);

            /** @var track_user_assignment $assignment_user */
            foreach ($orphaned_user_assignments as $assignment_user) {
                // Trigger event for all affected entries
                $event = track_user_unassigned::create_from_user_assignment($assignment_user);
                $event->trigger();
            }
        }
    }

    /**
     * Sanitize given ids, leave only proper positive numbers
     *
     * @param array $ids
     * @return array
     */
    private function sanitise_ids(array $ids): array {
        $ids = array_filter(
            $ids,
            function ($id) {
                return is_numeric($id) && $id > 0;
            }
        );
        return array_map('intval', $ids);
    }

    /**
     * @return int
     */
    private function get_max_per_chunk(): int {
        return builder::get_db()->get_dbfamily() === 'mssql' ? 10000 : builder::get_db()->get_max_in_params();
    }

}
