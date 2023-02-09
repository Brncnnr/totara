<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 */

use core\collection;
use core_phpunit\testcase;
use mod_perform\entity\activity\subject_instance;
use mod_perform\state\subject_instance\active as subject_active;
use mod_perform\state\subject_instance\open as subject_availability_open;
use mod_perform\state\subject_instance\closed as subject_availability_close;
use mod_perform\state\subject_instance\complete as subject_progress_complete;
use mod_perform\testing\generator;

class mod_perform_closed_at_upgrade_testcase extends testcase {
    public function test_upgrade_1(): void {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/perform/db/upgradelib.php');

        $constants = (object) [
            'closed_code' => subject_availability_close::get_code(),
            'opened_code' => subject_availability_open::get_code(),
            'completed_code' => subject_progress_complete::get_code(),
            'updated_at' => time() - 86400 * 30 // 1 month ago.
        ];

        // These are for subject instances that were 'closed'.
        $closed_ids = $this
            ->create_test_data()
            ->subject_instances
            ->map(
                function (subject_instance $entity) use ($constants): int {
                    $entity->availability = $constants->closed_code;
                    return $entity->save()->id;
                }
            )
            ->all();

        // These are for subject instances that are still open.
        $opened_ids = $this
            ->create_test_data()
            ->subject_instances
            ->map(
                function (subject_instance $entity) use ($constants): int {
                    $entity->availability = $constants->opened_code;
                    return $entity->save()->id;
                }
            )
            ->all();

        // Need to update the last modified time in this way; using the subject
        // instance entity automatically uses the current time for updated_at
        // even if you explicitly set it to another time.
        $DB->set_field('perform_subject_instance', 'updated_at', $constants->updated_at);

        // Pre test check to see if record values are correct.
        $all_ids = array_merge($closed_ids, $opened_ids);
        subject_instance::repository()
            ->where('id', $all_ids)
            ->get()
            ->map(
                function (subject_instance $entity) use ($opened_ids, $constants): void {
                    $this->assertNull($entity->closed_at);
                    $this->assertEquals($constants->completed_code, $entity->progress);
                    $this->assertEquals($constants->updated_at, (int)$entity->updated_at);

                    $availability = in_array($entity->id, $opened_ids)
                        ? $constants->opened_code
                        : $constants->closed_code;

                    $this->assertEquals($availability, $entity->availability);
                }
            );

        // Do the 'upgrade'.
        mod_perform_upgrade_subject_instances_closed_at_times();

        // Post upgrade check:
        // * unclosed subject instances should have a null closed_at timestamp.
        // * closed subject instances should have the closed_at time equal to the updated_at time.
        subject_instance::repository()
            ->where('id', $all_ids)
            ->get()
            ->map(
                function (subject_instance $entity) use ($constants, $opened_ids): void {
                    $this->assertEquals($constants->completed_code, $entity->progress);
                    $this->assertEquals($constants->updated_at, (int)$entity->updated_at);

                    if (in_array($entity->id, $opened_ids)) {
                        $this->assertEquals($constants->opened_code, $entity->availability);
                        $this->assertNull($entity->closed_at);
                    } else {
                        $this->assertEquals($constants->closed_code, $entity->availability);
                        $this->assertEquals($constants->updated_at, $entity->closed_at);
                    }
                }
            );
    }

    private function create_test_data(
        int $no_of_subject_instances = 1
    ): stdClass {
        $this->setAdminUser();

        $perform_generator = generator::instance();
        $activity = $perform_generator->create_activity_in_container();

        $core_generator = $this->getDataGenerator();
        $si_data = [
            'activity_id' => $activity->id,
            'other_participant_id' => $core_generator->create_user()->id,
            'third_participant_username' => $core_generator->create_user()->username,
            'subject_is_participating' => true,
            'include_questions' => false,
            'status' => subject_active::get_code()
        ];

        $completed = subject_progress_complete::get_code();

        $subject_instances = collection::new(range(1, $no_of_subject_instances))
            ->map_to(
                function (int $i) use ($core_generator): int {
                    return $core_generator->create_user()->id;
                }
            )
            ->map_to(
                function (int $uid) use ($si_data, $perform_generator, $completed): subject_instance {
                    $data = array_merge(['subject_user_id' => $uid], $si_data);

                    $entity = $perform_generator->create_subject_instance($data);
                    $entity->closed_at = null;
                    $entity->progress = $completed;

                    return $entity->save();
                }
            );

        return (object) [
            'activity' => $activity,
            'subject_instances' => $subject_instances
        ];
    }
}
