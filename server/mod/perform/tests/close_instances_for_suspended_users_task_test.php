<?php
/**
 * This file is part of Totara Perform
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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_perform
 */

use core_phpunit\testcase;
use mod_perform\entity\activity\participant_instance as participant_instance_entity;
use mod_perform\entity\activity\subject_instance as subject_instance_entity;
use mod_perform\state\participant_instance\closed as participant_instance_availability_closed;
use mod_perform\state\participant_instance\open as participant_instance_availability_open;
use mod_perform\state\subject_instance\closed as subject_instance_availability_closed;
use mod_perform\state\subject_instance\open as subject_instance_availability_open;
use mod_perform\task\close_instances_for_suspended_users_task;
use mod_perform\testing\generator;

global $CFG;
require_once($CFG->dirroot . '/user/lib.php');

/**
 * @group perform
 */
class mod_perform_close_instances_for_suspended_users_task_testcase extends testcase {

    public function test_execute(): void {
        self::setAdminUser();

        $perform_generator = generator::instance();
        $core_generator = self::getDataGenerator();

        $activity = $perform_generator->create_activity_in_container();

        $user1_id = $core_generator->create_user()->id;
        $other_participant1_id = $core_generator->create_user()->id;
        $si_data = [
            'activity_id' => $activity->id,
            'subject_user_id' => $user1_id,
            'other_participant_id' => $other_participant1_id,
            'subject_is_participating' => true,
            'include_questions' => false
        ];
        $perform_generator->create_subject_instance($si_data);

        $user2_id = $core_generator->create_user()->id;
        $other_participant2_id = $core_generator->create_user()->id;
        $si_data['subject_user_id'] = $user2_id;
        $si_data['other_participant_id'] = $other_participant2_id;
        $perform_generator->create_subject_instance($si_data);

        // Make sure all the expected instances are open.
        self::assertEquals(2, subject_instance_entity::repository()->count());
        self::assertEquals(4, participant_instance_entity::repository()->count());
        self::assertEquals(
            2,
            subject_instance_entity::repository()
                ->where('availability', subject_instance_availability_open::get_code())
                ->count()
        );
        self::assertEquals(
            4,
            participant_instance_entity::repository()
                ->where('availability', participant_instance_availability_open::get_code())
                ->count()
        );

        // Suspend two users.
        user_suspend_user($user1_id);
        user_suspend_user($other_participant2_id);

        // Run the task.
        close_instances_for_suspended_users_task::create(get_admin()->id)->execute();

        // Make sure the expected instances are closed.
        $closed_subject_instances = subject_instance_entity::repository()
                ->where('availability', subject_instance_availability_closed::get_code())
                ->get()
                ->all();
        self::assertCount(1, $closed_subject_instances);
        self::assertEquals($user1_id, $closed_subject_instances[0]->subject_user_id);

        $closed_participant_instances = participant_instance_entity::repository()
                ->where('availability', participant_instance_availability_closed::get_code())
                ->get();
        self::assertCount(3, $closed_participant_instances);
        self::assertEqualsCanonicalizing(
            [$user1_id, $other_participant1_id, $other_participant2_id],
            $closed_participant_instances->pluck('participant_id')
        );
    }
}