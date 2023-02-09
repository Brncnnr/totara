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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\task\service;

use core\collection;
use core\orm\query\builder;
use mod_perform\entity\activity\subject_static_instance;
use stdClass;
use totara_job\entity\job_assignment as job_assignment_entity;

/**
 * Class subject_static_instance_creation
 *
 * @package mod_perform\task\service
 */
class subject_static_instance_creation {

    /**
     * @var array
     */
    private $subject_static_instances = [];

    /**
     * @var array
     */
    private $all_existing_static_instances;

    /**
     * Maximum number of static instances aggregated before bulk insert.
     *
     * @var int
     */
    private $buffer_count = BATCH_INSERT_MAX_ROW_COUNT;

    /**
     * Generate static instances for a list of subject instances.
     *
     * @param collection|subject_instance_dto[] $subject_instances
     *
     * @return void
     */
    public function generate_instances(collection $subject_instances): void {
        builder::get_db()->transaction(
            function () use ($subject_instances) {
                $subject_instance_chunks = array_chunk($subject_instances->all(), builder::get_db()->get_max_in_params());

                foreach ($subject_instance_chunks as $subject_instance_chunk) {
                    $subject_instance_chunk = new collection($subject_instance_chunk);
                    $all_subject_job_assignments = job_assignment_entity::repository()
                        ->where_in('userid', $subject_instance_chunk->pluck('subject_user_id'))
                        ->get()
                        ->all(true);

                    $job_assignments_keyed_by_userid = [];
                    foreach ($all_subject_job_assignments as $subject_job_assignment) {
                        $job_assignments_keyed_by_userid[$subject_job_assignment->userid][(int)$subject_job_assignment->id] = $subject_job_assignment;
                    }

                    $existing_static_instances = subject_static_instance::repository()
                        ->where_in('subject_instance_id', $subject_instance_chunk->pluck('id'))
                        ->get();
                    $this->all_existing_static_instances = [];

                    foreach ($existing_static_instances as $existing_static_instance) {
                        $this->all_existing_static_instances[$existing_static_instance->subject_instance_id][$existing_static_instance->job_assignment_id] = $existing_static_instance;
                    }

                    foreach ($subject_instance_chunk as $subject_instance) {
                        // If subject instance is per job assignment then get specific job assignment.
                        // Else get all job assignments of subject.
                        if (empty($subject_instance->job_assignment_id)) {
                            $job_assignments = $job_assignments_keyed_by_userid[$subject_instance->subject_user_id] ?? [];
                        } else {
                            $job_assignments = [$job_assignments_keyed_by_userid[$subject_instance->subject_user_id][$subject_instance->job_assignment_id]] ?? [];
                        }

                        foreach ($job_assignments as $job_assignment) {
                            $data = new stdClass();
                            $data->subject_instance_id = $subject_instance->id;
                            $data->job_assignment_id = $job_assignment->id;
                            $data->manager_job_assignment_id = $job_assignment->managerjaid;
                            $data->position_id = $job_assignment->positionid;
                            $data->organisation_id = $job_assignment->organisationid;
                            $data->created_at = time();
                            $data->updated_at = $data->created_at;
                            $this->aggregate_static_subject_instances($data);
                        }
                    }
                }
                $this->save_subject_static_instances();
            }
        );
    }

    /**
     * Find out whether a subject static instance exists for the given data.
     *
     * @param int $subject_instance_id
     * @param int $job_assignment_id
     * @return bool
     */
    private function subject_static_instance_exists(
        int $subject_instance_id,
        int $job_assignment_id
    ): bool {
        return isset($this->all_existing_static_instances[$subject_instance_id])
            && isset($this->all_existing_static_instances[$subject_instance_id][$job_assignment_id]);
    }

    /**
     * Aggregates the static_subject_instance to create.
     *
     * @param stdClass $data
     * @return void
     */
    private function aggregate_static_subject_instances(stdClass $data): void {
        if ($this->subject_static_instance_exists($data->subject_instance_id, $data->job_assignment_id)) {
            return;
        }

        $this->subject_static_instances[] = $data;

        if (count($this->subject_static_instances) === $this->buffer_count) {
            $this->save_subject_static_instances();
        }
    }

    /**
     * Inserts records into the database.
     *
     * @return void
     */
    private function save_subject_static_instances(): void {
        if (empty($this->subject_static_instances)) {
            return;
        }

        builder::get_db()->insert_records(
            subject_static_instance::TABLE,
            $this->subject_static_instances
        );

        $this->subject_static_instances = [];
    }

}
