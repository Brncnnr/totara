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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package core_course
 */

use core_course\local\archive_progress_helper\output\single_user as single_user_page_output;
use core_course\local\archive_progress_helper\output\validator\single_user as single_user_validator;
use core_phpunit\testcase;
use core_course\local\archive_progress_helper\single_user;

/**
 * @covers \core_course\local\archive_progress_helper\single_user
 */
class core_course_archive_progress_helper_single_user_testcase extends testcase {

    public function test_construction_an_instance() {
        $user_1 = $this->getDataGenerator()->create_user();
        $user_2 = $this->getDataGenerator()->create_user();
        new single_user(new stdClass(), (object)['id' => $user_2->id]);

        // When user->id is same as logged-in user.
        $this->setUser($user_1);

        // then an exception is thrown.
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Current user cannot their own state using this");
        new single_user(new stdClass(), (object)['id' => $user_1->id]);
    }

    public function test_get_reason_cannot_archive_completion() {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $course->enablecompletion = 1;
        $guest = guest_user();

        // When a guest user.
        $instance = new single_user($course, $guest);

        // Then guest user progress is the reason.
        $this->assertEquals('Cannot archive guest user progress.', $instance->get_unable_to_archive_reason());

        // When user does not have the capability to archive target user's progress.
        $this->setUser($user);
        $target_user = $this->getDataGenerator()->create_user();
        $instance = new single_user($course, $target_user);

        // Then missing capability is the reason.
        $capability = 'totara/core:archiveusercourseprogress';
        $this->assertStringContainsString("Missing capability: $capability", $instance->get_unable_to_archive_reason());

        // Assign the capability.
        $roleid = $this->getDataGenerator()->create_role();
        $context = context_course::instance($course->id);
        role_change_permission($roleid, $context, $capability, CAP_ALLOW);
        role_assign($roleid, $user->id, $context);

        // When target user's completion info is not tracked.
        // Then completion not tracked is the reason.
        $this->assertEquals('User does not have a completion tracked role', $instance->get_unable_to_archive_reason());

        // When user has the capability to archive target user's progress and target user's completion info is tracked.
        $this->getDataGenerator()->enrol_user($target_user->id, $course->id, 'student');

        // Then the reason is null.
        $this->assertNull($instance->get_unable_to_archive_reason());
    }

    public function test_archive_and_reset() {
        $this->markTestSkipped('Covered in base class');
    }

    public function test_get_validator() {
        $course = $this->getDataGenerator()->create_course();
        $target_user = $this->getDataGenerator()->create_user();

        $instance = new single_user($course, $target_user);
        $this->assertInstanceOf(single_user_validator::class, $instance->get_validator());
    }

    public function test_get_page_output() {
        $course = $this->getDataGenerator()->create_course();
        $target_user = $this->getDataGenerator()->create_user();

        $instance = new single_user($course, $target_user);
        $this->assertInstanceOf(single_user_page_output::class, $instance->get_page_output());
    }
}
