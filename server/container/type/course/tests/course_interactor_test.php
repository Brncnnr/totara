<?php
/**
 * This file is part of Totara Core
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package container_course
 */

use container_course\interactor\course_interactor;
use core\orm\query\builder;
use core_phpunit\testcase;
use container_course\course;

/**
 * Class container_course_course_interactor_testcase
 */
class container_course_course_interactor_testcase extends testcase {
    /**
     * @return void
     */
    public function test_can_view_course_as_guest_user(): void {
        $generator = self::getDataGenerator();
        $course_record = $generator->create_course();

        $guest_user = guest_user();
        $course = course::from_record($course_record);

        $interactor = new course_interactor($course, $guest_user->id);
        self::assertTrue($interactor->can_view());

        // Remove the visibility of the course the guest will not be
        // able to view the course.
        $new_data = new stdClass();
        $new_data->visible = 0;
        $new_data->visibleold = 1;

        $course->update($new_data);
        self::assertFalse($interactor->can_view());
    }

    /**
     * @return void
     */
    public function test_can_access_course_as_guest_user(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/enrollib.php");

        $generator = self::getDataGenerator();
        $course_record = $generator->create_course();

        $course = course::from_record($course_record);

        self::setGuestUser();
        $interactor = new course_interactor($course);

        // User is not able to access the course.
        self::assertFalse($interactor->can_access());

        // But if we enable the guest enrolment, then guest user should be able to access it.
        $db = builder::get_db();
        $enrol_instance = $db->get_record(
            'enrol',
            ['enrol' => 'guest', 'courseid' => $course->id],
            '*',
            MUST_EXIST
        );

        // Call to require login because it will allow the auto enrol guest.

        try {
            // However since guest enrol is not enabled, therefore guest user is not enrolled.
            require_login($course_record->id, true, null, false, true);
            self::fail("Expect the require login to throw exception since the guest enrol is not enabled");
        } catch (require_login_exception $e) {
            self::assertStringContainsString(
                get_string('requireloginerror', 'error'),
                $e->getMessage()
            );
        }

        self::assertFalse($interactor->can_access());

        $plugin = enrol_get_plugin('guest');
        $new_enrol_instance = new stdClass();
        $new_enrol_instance->status = ENROL_INSTANCE_ENABLED;

        $plugin->update_instance($enrol_instance, $new_enrol_instance);
        $course->rebuild_cache();

        require_login($course_record->id, true, null, false, true);
        self::assertTrue($interactor->can_access());
    }

    /**
     * @return void
     */
    public function test_can_view_course_as_authenticated_user(): void {
        $generator = self::getDataGenerator();

        $course_record = $generator->create_course();
        $user = $generator->create_user();

        $course = course::from_record($course_record);
        $interactor = new course_interactor($course, $user->id);

        // This is happening because the course is publicly viewable.
        self::assertTrue($interactor->can_view());

        // Remove the visibility of the course the guest will not be
        // able to view the course.
        $new_data = new stdClass();
        $new_data->visible = 0;
        $new_data->visibleold = 1;

        $course->update($new_data);
        self::assertFalse($interactor->can_view());
    }

    /**
     * @return void
     */
    public function test_can_access_course_as_authenticated_user(): void {
        $generator = self::getDataGenerator();

        $course_record = $generator->create_course();
        $user = $generator->create_user();

        $course = course::from_record($course_record);
        $interactor = new course_interactor($course, $user->id);
        self::assertFalse($interactor->can_access());

        $generator->enrol_user($user->id, $course->id);
        self::assertTrue($interactor->can_access());
    }

    /**
     * @return void
     */
    public function test_require_view_for_authenticated_user(): void {
        $generator = self::getDataGenerator();
        $course_record = $generator->create_course();
        $user = $generator->create_user();

        $course = course::from_record($course_record);
        $interactor = new course_interactor($course, $user->id);

        $interactor->require_view();

        // Make the course invisible.
        $new_data = new stdClass();
        $new_data->visible = 0;
        $new_data->visibleold = 1;
        $course->update($new_data);

        try {
            $interactor->require_view();
            self::fail("Expect the require view should yield error");
        } catch (moodle_exception $e) {
            self::assertEquals(
                get_string('error:course_hidden', 'container_course'),
                $e->getMessage()
            );
        }
    }

    /**
     * @return void
     */
    public function test_require_access_for_autneticated_user(): void {
        $generator = self::getDataGenerator();
        $course_record = $generator->create_course();
        $user = $generator->create_user();

        $course = course::from_record($course_record);
        $interactor = new course_interactor($course, $user->id);

        try {
            $interactor->require_access();
            self::fail("Expect the require access should yield error");
        } catch (moodle_exception $e) {
            self::assertEquals(
                get_string('error:course_access', 'container_course'),
                $e->getMessage()
            );
        }

        $generator->enrol_user($user->id, $course->id);
        $interactor->require_access();
    }
}