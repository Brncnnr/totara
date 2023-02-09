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

use core_course\local\archive_progress_helper\output\current_user as current_user_page_output;
use core_course\local\archive_progress_helper\output\validator\single_user as single_user_validator;
use core_phpunit\testcase;
use core_course\local\archive_progress_helper\current_user;

/**
 * @covers \core_course\local\archive_progress_helper\current_user
 */
class core_course_archive_progress_helper_current_user_testcase extends testcase {

    public function test_construction_an_instance() {
        $user_1 = $this->getDataGenerator()->create_user();
        $this->setUser($user_1);

        // When class is instantiated.
        $instance = new current_user(new stdClass());

        // Then the user is the logged-in user.
        $user_property = new ReflectionProperty($instance, 'user');
        $user_property->setAccessible(true);
        $this->assertEquals($user_1->id, $user_property->getValue($instance)->id);
    }

    public function test_get_reason_cannot_archive_completion() {
        $course = $this->getDataGenerator()->create_course();
        $course->enablecompletion = 1;

        // When a guest user.
        $this->setGuestUser();
        $instance = new current_user($course);

        // Then guest user progress is the reason.
        $this->assertEquals('Guest users cannot archive their progress.', $instance->get_unable_to_archive_reason());

        // When user does not have the capability to archive own progress.
        $target_user = $this->getDataGenerator()->create_user();
        $this->setUser($target_user);
        $instance = new current_user($course);

        // Then missing capability is the reason.
        $capability = 'totara/core:archivemycourseprogress';
        $this->assertStringContainsString("Missing capability: $capability", $instance->get_unable_to_archive_reason());

        // Assign the capability.
        $role_id = $this->getDataGenerator()->create_role();
        $context = context_course::instance($course->id);
        role_change_permission($role_id, $context, $capability, CAP_ALLOW);
        role_assign($role_id, $target_user->id, $context);

        // When target user's completion info is not tracked.
        // Then completion not tracked is the reason.
        $this->assertEquals('User does not have a completion tracked role', $instance->get_unable_to_archive_reason());

        // When target user has the capability and target user's completion info is tracked.
        $this->getDataGenerator()->enrol_user($target_user->id, $course->id, 'student');

        // Then there is reason is null.
        $this->assertNull($instance->get_unable_to_archive_reason());
    }

    public function test_archive_and_reset() {
        $this->markTestSkipped('Covered in base class');
    }

    public function test_get_validator() {
        $course = $this->getDataGenerator()->create_course();
        $target_user = $this->getDataGenerator()->create_user();
        $this->setUser($target_user);

        $instance = new current_user($course);
        $this->assertInstanceOf(single_user_validator::class, $instance->get_validator());
    }

    public function test_get_page_output() {
        $course = $this->getDataGenerator()->create_course();
        $target_user = $this->getDataGenerator()->create_user();
        $this->setUser($target_user);

        $instance = new current_user($course);
        $this->assertInstanceOf(current_user_page_output::class, $instance->get_page_output());
    }
}
