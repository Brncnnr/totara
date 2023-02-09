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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_plan
 */

use core\entity\user_enrolment;
use core\orm\query\builder;
use core_phpunit\testcase;
use totara_plan\entity\record_of_learning as record_of_learning_entity;
use totara_plan\record_of_learning;
use totara_plan\task\update_record_of_learning_task;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for the task which updates the record of learning
 */
class totara_plan_update_record_of_learning_task_testcase extends testcase {

    public function test_task_updates_record_of_learning() {
        $generator = $this->getDataGenerator();

        /* @var \core_completion\testing\generator $completion_generator */
        $completion_generator = $generator->get_plugin_generator('core_completion');

        /** @var \totara_plan\testing\generator $plan_generator */
        $plan_generator = $generator->get_plugin_generator('totara_plan');

        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $user3 = $generator->create_user();

        $course1 = $generator->create_course();
        $course2 = $generator->create_course();
        $course3 = $generator->create_course();

        $completion_generator->enable_completion_tracking($course2);

        // Enrolling a user should result in a record in the record of learning
        $generator->enrol_user($user1->id, $course1->id);

        // Now make sure the user has a completion record
        // but is not enrolled anymore. A completion record should
        // result in a record in the record of learning
        $completion_generator->complete_course($course2, $user2);

        // Now assign a course to the learning plan of user 3 which
        // should result in a record in the record of learning
        $this->assertFalse(user_enrolment::repository()->where('userid', $user2->id)->exists());
        $plan1 = $plan_generator->create_learning_plan(['userid' => $user3->id]);

        $this->setUser($user3);
        $plan_generator->add_learning_plan_course($plan1->id, $course3->id);

        $this->setAdminUser();

        // Make sure the table is empty so we can verify the task executed correctly
        record_of_learning_entity::repository()->delete();

        (new update_record_of_learning_task())->execute();

        $this->assertEquals(3, record_of_learning_entity::repository()->count());

        $user1_record = record_of_learning_entity::repository()
            ->where('userid', $user1->id)
            ->one();

        $this->assertEquals($course1->id, $user1_record->instanceid);
        $this->assertEquals(record_of_learning::TYPE_COURSE, $user1_record->type);

        $user2_record = record_of_learning_entity::repository()
            ->where('userid', $user2->id)
            ->one();

        $this->assertEquals($course2->id, $user2_record->instanceid);
        $this->assertEquals(record_of_learning::TYPE_COURSE, $user2_record->type);

        $user3_record = record_of_learning_entity::repository()
            ->where('userid', $user3->id)
            ->one();

        $this->assertEquals($course3->id, $user3_record->instanceid);
        $this->assertEquals(record_of_learning::TYPE_COURSE, $user3_record->type);

        // Now unenrol the user and rerun the task:
        // he should not have a record anymore
        $generator->unenrol_user($user1->id, $course1->id);

        (new update_record_of_learning_task())->execute();

        $this->assertEquals(2, record_of_learning_entity::repository()->count());

        // As the user got unenrolled and has no completion records he shouldn't
        // have a record of learning anymore
        $this->assertFalse(record_of_learning_entity::repository()->where('userid', $user1->id)->exists());

        // Now enrol the user too, before he only had a plan record
        $generator->enrol_user($user3->id, $course3->id);

        // Delete the plan assignments
        builder::table('dp_plan_course_assign')->delete();

        (new update_record_of_learning_task())->execute();

        $this->assertEquals(2, record_of_learning_entity::repository()->count());

        // The user should still have a record as he is still enrolled
        $this->assertTrue(
            record_of_learning_entity::repository()
                ->where('userid', $user3->id)
                ->where('instanceid', $course3->id)
                ->exists()
        );

        // Now re-enrol user1 which should result in a record of learning record
        $generator->enrol_user($user1->id, $course1->id);

        // Make sure the table is empty so we can verify the task executed correctly
        record_of_learning_entity::repository()->delete();

        (new update_record_of_learning_task())->execute();

        $this->assertEquals(3, record_of_learning_entity::repository()->count());

        $this->assertTrue(
            record_of_learning_entity::repository()
                ->where('userid', $user1->id)
                ->where('instanceid', $course1->id)
                ->exists()
        );
    }
}
