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
 * @package totara_userstatus
 */

namespace degeneration;

use core\orm\query\builder;
use degeneration\items\job_assignment;
use degeneration\items\organisation;
use degeneration\items\position;
use degeneration\items\user;
use totara_job\entity\job_assignment as job_assignment_entity;

class job_assignments_creator {
    /**
     * @var array
     */
    protected $users;
    /**
     * @var array
     */
    protected $positions;
    /**
     * @var array
     */
    protected $organisations;

    /**
     * @param array|user[] $users
     * @param array|position[] $positions
     * @param array|organisation[] $organisations
     */
    public function __construct(array $users, array $positions, array $organisations) {
        $this->users = $users;
        $this->positions = $positions;
        $this->organisations = $organisations;
    }

    /**
     * Generate job assignments for the users
     *
     * @param int $job_assignments_for_user
     */
    public function generate(int $job_assignments_for_user) {
        // Skip if no job assignments should be created per user
        if (empty($job_assignments_for_user)) {
            return;
        }

        builder::get_db()->transaction(function () use ($job_assignments_for_user) {
            $user_pool = $this->users;
            $pos_pool = $this->positions;
            $org_pool = $this->organisations;

            $max_existing_sortorder = builder::table(job_assignment_entity::TABLE)
                ->select('MAX(sortorder) AS sortorder')
                ->one();
            $max_existing_sortorder = $max_existing_sortorder->sortorder;

            $pick_user = function (array $exclude_users, array &$user_pool): ?int {
                $escape = 5;
                do {
                    // Pick a random user who is not the current one
                    $key = array_rand($user_pool);
                    $escape--;
                } while (in_array($key, $exclude_users) && $escape);

                if (!$escape) {
                    return null;
                }

                // Remove from pool so it can't be picked anymore
                unset($user_pool[$key]);

                // Refill the pool
                if (empty($user_pool)) {
                    $user_pool = $this->users;
                }

                return $this->users[$key];
            };

            $pick_assignment = function (array $exclude_users, array $pool): ?job_assignment {
                $escape = 5;
                do {
                    // Pick a random user who is not the current one
                    $key = array_rand($pool);
                    $escape--;
                } while (in_array($key, $exclude_users) && $escape);

                if (!$escape) {
                    return null;
                }

                $assignment = $pool[$key];

                if ($assignment && !$assignment instanceof job_assignment) {
                    $assignment = job_assignment::new()->fill($assignment);
                }

                return $assignment;
            };

            $pick_pos_org = function (array &$pool, array $original) {
                if (empty($original)) {
                    return null;
                }

                // Pick a random position from the pool
                $key = array_rand($pool);
                unset($pool[$key]);
                if (empty($pool)) {
                    $pool = $original;
                }
                return $original[$key];
            };

            echo PHP_EOL . "Creating manager job assignments for users..." . PHP_EOL;

            $buffer = [];
            $total = count($this->users) / BATCH_INSERT_MAX_ROW_COUNT;
            $done = 0;
            foreach ($this->users as $current_user_key => $user) {
                $buffer[] = job_assignment::for_user($user)
                    ->set_sort_order($max_existing_sortorder + 1)
                    ->use_bulk()
                    ->save_and_return()
                    ->get_data();

                if (count($buffer) >= BATCH_INSERT_MAX_ROW_COUNT) {
                    $this->process_bulk($buffer);
                    performance_testing::show_progress($done, $total);
                }
            }

            // Make sure all is saved
            $this->process_bulk($buffer);

            echo PHP_EOL . "Creating actual job assignments for users..." . PHP_EOL;

            $manager_job_assignments = builder::table(job_assignment_entity::TABLE)
                ->results_as_arrays()
                ->get()
                ->key_by('userid')
                ->all(true);

            $total = (count($this->users) * $job_assignments_for_user) / BATCH_INSERT_MAX_ROW_COUNT;
            $done = 0;
            foreach ($this->users as $current_user_key => $user) {
                for ($i = 1; $i <= $job_assignments_for_user; $i++) {
                    // Now get three different random users
                    // to be assigned as manager, appraiser and temporary manager
                    $manager_job_assignment = $pick_assignment(
                        [
                            $current_user_key
                        ],
                        $manager_job_assignments
                    );
                    $temp_manager_job_assignment = $pick_assignment(
                        [
                            $current_user_key,
                            $manager_job_assignment->get_data('userid')
                        ],
                        $manager_job_assignments
                    );
                    $appraiser = $pick_user(
                        [
                            $current_user_key,
                            $manager_job_assignment->get_data('userid'),
                            $temp_manager_job_assignment->get_data('userid')
                        ],
                        $user_pool
                    );

                    $job_assignment = job_assignment::for_user($user);

                    if ($manager_job_assignment) {
                        $job_assignment->set_manager_job_assignment($manager_job_assignment);
                    }

                    if ($temp_manager_job_assignment) {
                        $job_assignment->set_temp_manager_job_assignment($temp_manager_job_assignment);
                    }

                    if ($appraiser) {
                        $job_assignment->set_appraiser_id($appraiser);
                    }

                    $buffer[] = $job_assignment
                        ->set_position($pick_pos_org($pos_pool, $this->positions))
                        ->set_organisation($pick_pos_org($org_pool, $this->organisations))
                        ->set_sort_order($i + $max_existing_sortorder + 1)   // we already have 1, so let's start with 2
                        ->use_bulk()
                        ->save_and_return()
                        ->get_data();

                    if (count($buffer) >= BATCH_INSERT_MAX_ROW_COUNT) {
                        $this->process_bulk($buffer);
                        performance_testing::show_progress($done, $total);
                    }

                    unset($manager_job_assignment);
                    unset($temp_manager_job_assignment);
                    unset($appraiser);
                }
            }

            // Make sure all records are saved.
            $this->process_bulk($buffer);
        });
    }

    /**
     * Save all job assignments in bulk which are in the buffer so far
     */
    public function process_bulk(array &$buffer) {
        if (!empty($buffer)) {
            builder::get_db()->insert_records(job_assignment_entity::TABLE, $buffer);
            $buffer = [];
        }
    }

}