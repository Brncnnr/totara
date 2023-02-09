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
use totara_plan\entity\record_of_learning as record_of_learning_entity;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for the enrolment observer
 */
class totara_plan_enrolment_observer_testcase extends testcase {

    public function test_enrolment_creates_record_of_learning_record() {
        $generator = $this->getDataGenerator();

        $user1 = $generator->create_user();
        $user2 = $generator->create_user();

        $course = $generator->create_course();

        $this->assertEquals(0, record_of_learning_entity::repository()->count());

        // Enrolling a user should result in a record in the record of learning
        $generator->enrol_user($user1->id, $course->id);

        $this->assertEquals(1, record_of_learning_entity::repository()->count());

        $this->assertTrue(
            record_of_learning_entity::repository()
                ->where('userid', $user1->id)
                ->where('instanceid', $course->id)
                ->exists()
        );

        // Enrolling again should not change anything
        $generator->enrol_user($user1->id, $course->id);

        $this->assertEquals(1, record_of_learning_entity::repository()->count());

        $this->assertTrue(
            record_of_learning_entity::repository()
                ->where('userid', $user1->id)
                ->where('instanceid', $course->id)
                ->exists()
        );

        // Enrol a different user
        $generator->enrol_user($user2->id, $course->id);

        $this->assertEquals(2, record_of_learning_entity::repository()->count());

        $this->assertTrue(
            record_of_learning_entity::repository()
                ->where('userid', $user2->id)
                ->where('instanceid', $course->id)
                ->exists()
        );
    }
}
