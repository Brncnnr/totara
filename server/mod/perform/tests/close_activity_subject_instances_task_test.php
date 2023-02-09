<?php
/**
 * This file is part of Totara Perform
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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 */

use core\collection;
use core_phpunit\testcase;
use mod_perform\models\activity\subject_instance;
use mod_perform\state\subject_instance\active as subject_active;
use mod_perform\state\subject_instance\closed as subject_availability_close;
use mod_perform\state\subject_instance\open as subject_availability_open;
use mod_perform\state\subject_instance\pending as subject_pending;
use mod_perform\state\participant_instance\open as participant_availability_open;
use mod_perform\state\participant_instance\closed as participant_availability_close;
use mod_perform\task\close_activity_subject_instances_task;
use mod_perform\testing\generator;

/**
 * @coversDefaultClass \mod_perform\task\close_activity_subject_instances_task
 *
 * @group perform
 */
class mod_perform_close_activity_subject_instances_task_testcase extends testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->setAdminUser();
    }

    public function test_close(): void {
        [$task1, $task1_si] = $this->create_test_data();
        [$task2, $task2_si] = $this->create_test_data();

        [$si_open, $si_close, $pi_open, $pi_close] = $this->availability_statuses();
        $this->assert_availability($task1_si, $si_open, $pi_open);
        $this->assert_availability($task2_si, $si_open, $pi_open);

        $task1->execute();
        $this->assert_availability($task1_si, $si_close, $pi_close);
        $this->assert_availability($task2_si, $si_open, $pi_open);

        $task2->execute();
        $this->assert_availability($task1_si, $si_close, $pi_close);
        $this->assert_availability($task2_si, $si_close, $pi_close);
    }

    private function create_test_data(
        int $no_of_subject_instances = 5,
        bool $subject_instance_active = true
    ): array {
        $user_id = get_admin()->id;
        $this->setUser($user_id);

        $perform_generator = generator::instance();
        $activity_id = $perform_generator->create_activity_in_container()->id;
        $task = close_activity_subject_instances_task::create($activity_id, $user_id);

        $core_generator = $this->getDataGenerator();
        $si_data = [
            'activity_id' => $activity_id,
            'other_participant_id' => $core_generator->create_user()->id,
            'third_participant_username' => $core_generator->create_user()->username,
            'subject_is_participating' => true,
            'include_questions' => false,
            'status' => $subject_instance_active ? subject_active::get_code() : subject_pending::get_code()
        ];

        $subject_instances = collection::new(range(1, $no_of_subject_instances))
            ->map_to(
                function (int $i) use ($core_generator): int {
                    return $core_generator->create_user()->id;
                }
            )
            ->map_to(
                function (int $uid) use ($si_data, $perform_generator): subject_instance {
                    $data = array_merge(['subject_user_id' => $uid], $si_data);
                    $entity = $perform_generator->create_subject_instance($data);

                    return subject_instance::load_by_entity($entity);
                }
            );

        return [$task, $subject_instances];
    }

    private function assert_availability(
        collection $subject_instances,
        string $expected_si_status,
        string $expected_pi_status
    ): void {
        foreach ($this->refresh($subject_instances) as $si) {
            $this->assertEquals($expected_si_status, $si->availability_status);

            foreach ($si->participant_instances as $pi) {
                $this->assertEquals($expected_pi_status, $pi->availability_status);
            }
        }
    }

    private function refresh(
        collection $subject_instances
    ): collection {
        // Unfortunately there is no refresh() method on an ORM model so need
        // to do it in a roundabout way.
        return $subject_instances
            ->map(
                function (subject_instance $si): subject_instance {
                    return subject_instance::load_by_id($si->id);
                }
            );
    }

    private function availability_statuses(): array {
        return [
            subject_availability_open::get_name(),
            subject_availability_close::get_name(),
            participant_availability_open::get_name(),
            participant_availability_close::get_name()
        ];
    }
}