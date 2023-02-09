<?php
/**
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

namespace mod_perform\task\service;

use coding_exception;
use core\collection;
use core\orm\query\builder;
use core\orm\query\sql\query;
use mod_perform\dates\date_offset;
use mod_perform\entity\activity\activity_repository;
use mod_perform\entity\activity\subject_instance;
use mod_perform\entity\activity\temp_track_user_assignment_queue;
use mod_perform\entity\activity\track;
use mod_perform\entity\activity\track_user_assignment;
use mod_perform\hook\subject_instances_created;
use mod_perform\models\activity\trigger\repeating\factory as repeating_schedule_factory;
use mod_perform\state\subject_instance\active;
use mod_perform\state\subject_instance\pending;
use mod_perform\task\service\data\subject_instance_activity_collection;
use stdClass;
use xmldb_table;

/**
 * This class is responsible for creating new subject instances for users who
 * are assigned to a track.
 *
 * It creates new instance for every assignment which does not have a
 * subject instance yet and meets time interval restrictions.
 * It also creates repeating subject instances, if the track is configured that way.
 */
class subject_instance_creation {

    /**
     * Limit on number of user assignments to process per batch.
     *
     * @var int
     */
    private $limit = 10000;

    /**
     * @var string
     */
    private $task_id;

    /**
     * Queues the potential track user assignments and attempts to create subject instances.
     *
     * @return void
     */
    public function generate_instances(): void {
        $this->create_temp_table();
        $this->queue_user_assignments_into_temp_table();
        $last_temp_track_user_assignment_id = 0;

        while (true) {
            // For each loop we want a new id
            $this->task_id = uniqid();

            $user_assignments = $this->get_user_assignments_potentially_needing_instances($last_temp_track_user_assignment_id);
            $last_temp_track_user_assignment_id = $user_assignments->last()->id ?? null;

            if ($user_assignments->count() === 0) {
                break;
            }
            $this->create_subject_instances($user_assignments);
        }
        $this->drop_temp_table();
    }

    /**
     * Create temporary table used for queuing potential track user assignments.
     *
     * @return void
     */
    private function create_temp_table(): void {
        $db_manager = builder::get_db()->get_manager();
        $temp_table = new xmldb_table(temp_track_user_assignment_queue::TABLE);

        if ($db_manager->table_exists($temp_table)) {
            $db_manager->drop_table($temp_table);
        }

        foreach (temp_track_user_assignment_queue::FIELDS as $name => $field_properties) {
            $temp_table->add_field($name, ...$field_properties);
        }
        $temp_table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $db_manager->create_temp_table($temp_table);
    }

    /**
     * Queues track user assignments potentially needing instances into temporary table.
     *
     * We check several conditions:
     *  - assignment, track and activity have to be active
     *  - period settings must match
     *  - track is not flagged for schedule synchronisation because that should happen before we create instances
     *  - assignment either doesn't have any instances or the repeat config is such that it potentially can have more
     *
     * @return void
     */
    private function queue_user_assignments_into_temp_table(): void {
        $tua_builder = track_user_assignment::repository()
            ->as('tua')
            ->add_select([
                'tua.id as track_user_assignment_id',
            ])
            ->join([track::TABLE, 't'], 'track_id', 'id')
            ->filter_by_possibly_has_subject_instances_to_create()
            ->filter_by_active()
            ->filter_by_active_track_and_activity()
            ->filter_by_time_interval()
            ->filter_by_does_not_need_schedule_sync()
            ->filter_by_job_assignment_specific_has_existing_job_assignment()
            ->filter_suspended_users_if_hide_suspended_is_enabled()
            ->order_by('t.activity_id')
            ->order_by('id')
            ->get_builder();
        $tua_query = query::from_builder($tua_builder)
            ->build();

        $fields = array_filter(
            array_keys(temp_track_user_assignment_queue::FIELDS),
            function ($field) {
                return $field !== 'id';
            }
        );

        $query = sprintf(
            "INSERT INTO {%s} (%s) %s",
            temp_track_user_assignment_queue::TABLE,
            implode(',', $fields),
            $tua_query[0]
        );

        builder::get_db()->execute($query, $tua_query[1]);
    }

    /**
     * Get a chunk of user assignments that potentially need a subject instance created.
     *
     * @param int $cursor
     * @return collection
     */
    private function get_user_assignments_potentially_needing_instances(int $cursor = 0): collection {
        return temp_track_user_assignment_queue::repository()
            ->as('temp_tua')
            ->join([track_user_assignment::TABLE, 'tua'], 'track_user_assignment_id', 'id')
            ->with([
                'activity' => function (activity_repository $repository) {
                    $repository->eager_load_instance_creation_data();
                }
            ])
            ->where('id', '>', $cursor)
            ->order_by('id', 'ASC')
            ->limit($this->limit)
            ->select(['tua.*', 'temp_tua.*'])
            ->get();
    }

    /**
     * Create subject instances for the given track user assignments.
     *
     * @param collection $user_assignments
     *
     * @return void
     */
    private function create_subject_instances(collection $user_assignments): void {
        $activity_collection = new subject_instance_activity_collection();

        builder::get_db()->transaction(function () use ($user_assignments, $activity_collection) {
            $now = time();

            $subject_instance_to_create = [];
            $user_assignments_meta = [];
            foreach ($user_assignments as $user_assignment) {
                $activity = $user_assignment->activity;
                $activity_collection->add_activity_config($activity);

                if (!$this->is_it_time_for_a_new_subject_instance($user_assignment)) {
                    continue;
                }

                $status = $activity_collection->get_activity_config($activity->id)->has_manual_relationship()
                    ? pending::get_code()
                    : active::get_code();
                $subject_instance = new stdClass();
                $subject_instance->track_user_assignment_id = $user_assignment->track_user_assignment_id;
                $subject_instance->subject_user_id = $user_assignment->subject_user_id;
                $subject_instance->job_assignment_id = $user_assignment->job_assignment_id;
                $subject_instance->status = $status;
                $subject_instance->created_at = $now;
                $subject_instance->due_date = $this->calculate_due_date($user_assignment, $now);
                $subject_instance->task_id = $this->task_id;

                $subject_instance_to_create[] = $subject_instance;

                $track_data = [
                    'track_id' => $user_assignment->track_id,
                    'activity_id' => $activity->id,
                ];
                $user_assignments_meta[$user_assignment->track_user_assignment_id] = $track_data;
            }

            $this->insert_subject_instances($subject_instance_to_create, $user_assignments_meta, $activity_collection);
        });
    }

    /**
     * Insert the subject instances into the table and trigger hook
     *
     * @param array $subject_instance_to_create
     * @param $user_assignments_meta
     * @param subject_instance_activity_collection $activity_collection
     */
    private function insert_subject_instances(
        array $subject_instance_to_create,
        array $user_assignments_meta,
        subject_instance_activity_collection $activity_collection
    ): void {
        if (!empty($subject_instance_to_create)) {
            $dtos = new collection();

            builder::get_db()->insert_records_via_batch(subject_instance::TABLE, $subject_instance_to_create);

            $subject_instances = subject_instance::repository()
                ->where('task_id', $this->task_id)
                ->get_lazy();

            foreach ($subject_instances as $subject_instance) {
                $track_data = $user_assignments_meta[$subject_instance->track_user_assignment_id] ?? null;
                if (!$track_data) {
                    throw new coding_exception('Missing track meta information');
                }
                $dtos->append(subject_instance_dto::create_from_entity($subject_instance, $track_data));
            }

            if ($dtos->count() > 0) {
                $hook = new subject_instances_created($dtos, $activity_collection);
                $hook->execute();
            }

            subject_instance::repository()
                ->where('task_id', $this->task_id)
                ->update(['task_id' => null]);
        }
    }

    /**
     * @param temp_track_user_assignment_queue $user_assignment
     * @param int $reference_date
     *
     * @return int|null
     */
    private function calculate_due_date(temp_track_user_assignment_queue $user_assignment, int $reference_date): ?int {
        if (!$user_assignment->track_due_date_is_enabled) {
            return null;
        }

        if ($user_assignment->track_due_date_is_fixed) {
            return $user_assignment->track_due_date_fixed;
        }

        $offset = date_offset::create_from_json($user_assignment->track_due_date_offset);
        return $offset->apply($reference_date);
    }

    /**
     * Check if the track user assignment should have a new subject instance created according to repeat settings.
     * Note this is not checking if the repeat limit is reached. That should be checked before calling this method.
     *
     * @param temp_track_user_assignment_queue $user_assignment
     * @return bool
     */
    private function is_it_time_for_a_new_subject_instance(temp_track_user_assignment_queue $user_assignment): bool {
        if (is_null($user_assignment->subject_instance_count)) {
            // Does not have a subject instance yet.
            return true;
        }

        if ((int)$user_assignment->track_repeating_is_enabled === 0) {
            // Already has at least one subject instance and repeating is off.
            return false;
        }

        return repeating_schedule_factory::get_instance()
            ->create_trigger($user_assignment->track_repeating_trigger)
            ->should_trigger($user_assignment);
    }

    /**
     * Drops the temporary queue table after use.
     */
    private function drop_temp_table(): void {
        $db_manager = builder::get_db()->get_manager();
        $temp_table = new xmldb_table(temp_track_user_assignment_queue::TABLE);
        $db_manager->drop_table($temp_table);
    }
}