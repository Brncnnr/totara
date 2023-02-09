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

use core_phpunit\testcase;
use totara_completionimport\event\bulk_course_completionimport;
use totara_plan\entity\record_of_learning as record_of_learning_entity;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for the completion observer
 */
class totara_completion_observer_testcase extends testcase {

    public function test_completion_creates_record_of_learning_record() {
        $generator = $this->getDataGenerator();

        /* @var \core_completion\testing\generator $completion_generator */
        $completion_generator = $generator->get_plugin_generator('core_completion');

        $user1 = $generator->create_user();
        $user2 = $generator->create_user();

        $course = $generator->create_course();

        $completion_generator->enable_completion_tracking($course);

        $this->assertEquals(0, record_of_learning_entity::repository()->count());

        // Completing the course should result in a record in the record of learning
        $completion_generator->complete_course($course, $user1);

        $this->assertEquals(1, record_of_learning_entity::repository()->count());

        $this->assertTrue(
            record_of_learning_entity::repository()
                ->where('userid', $user1->id)
                ->where('instanceid', $course->id)
                ->exists()
        );

        // Completing again should not change anything
        $completion_generator->complete_course($course, $user1);

        $this->assertEquals(1, record_of_learning_entity::repository()->count());

        $this->assertTrue(
            record_of_learning_entity::repository()
                ->where('userid', $user1->id)
                ->where('instanceid', $course->id)
                ->exists()
        );

        // Complete for a different user
        $completion_generator->complete_course($course, $user2);

        $this->assertEquals(2, record_of_learning_entity::repository()->count());

        $this->assertTrue(
            record_of_learning_entity::repository()
                ->where('userid', $user2->id)
                ->where('instanceid', $course->id)
                ->exists()
        );
    }

    public function test_bulk_completion_creates_record_of_learning_record() {
        $generator = $this->getDataGenerator();

        /* @var \core_completion\testing\generator $completion_generator */
        $completion_generator = $generator->get_plugin_generator('core_completion');

        $user1 = $generator->create_user();
        $user2 = $generator->create_user();

        $course = $generator->create_course();

        $this->assertEquals(0, record_of_learning_entity::repository()->count());

        $user_courses = [
            [
                'userid' => $user1->id,
                'courseid' => $course->id
            ],
        ];

        // Completing the course should result in a record in the record of learning
        bulk_course_completionimport::create_from_list($user_courses)->trigger();

        $this->assertEquals(1, record_of_learning_entity::repository()->count());

        $this->assertTrue(
            record_of_learning_entity::repository()
                ->where('userid', $user1->id)
                ->where('instanceid', $course->id)
                ->exists()
        );

        $user_courses = [
            [
                'userid' => $user1->id,
                'courseid' => $course->id
            ],
            [
                'userid' => $user2->id,
                'courseid' => $course->id
            ],
        ];

        // Completing the course should result in a record in the record of learning
        bulk_course_completionimport::create_from_list($user_courses)->trigger();

        $this->assertEquals(2, record_of_learning_entity::repository()->count());

        $this->assertTrue(
            record_of_learning_entity::repository()
                ->where('userid', $user1->id)
                ->where('instanceid', $course->id)
                ->exists()
        );

        $this->assertTrue(
            record_of_learning_entity::repository()
                ->where('userid', $user2->id)
                ->where('instanceid', $course->id)
                ->exists()
        );
    }
}
