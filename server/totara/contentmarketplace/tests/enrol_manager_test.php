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
 * @package totara_contentmarketplace
 */

use container_course\course;
use core\entity\enrol;
use core\orm\query\builder;
use core_phpunit\testcase;
use totara_contentmarketplace\course\enrol_manager;
use core\entity\user_enrolment;

/**
 * @group totara_contentmarketplace
 */
class totara_contentmarketplace_enrol_manager_testcase extends testcase {
    /**
     * @return void
     */
    public function test_enrol_admin_to_course_as_course_creator(): void {
        global $USER;
        self::setAdminUser();

        $generator = self::getDataGenerator();
        $course_record = $generator->create_course();

        $course = course::from_record($course_record);
        $enrol_manager = new enrol_manager($course);
        $enrol_manager->enrol_course_creator($USER->id);

        // Check that the admin does not get enrolled directly.
        $db = builder::get_db();
        self::assertFalse(
            $db->record_exists_sql(
                '
                    SELECT 1 FROM "ttr_user_enrolments" ue
                    INNER JOIN "ttr_enrol" e ON e.id = ue.enrolid
                    WHERE e.courseid = :course_id AND ue.userid = :user_id
                ',
                [
                    'course_id' => $course->id,
                    'user_id' => $USER->id,
                ]
            )
        );
    }

    /**
     * @return void
     */
    public function test_enrol_authenticated_user_to_course_as_course_creator(): void {
        global $USER;
        $generator = self::getDataGenerator();

        $user = $generator->create_user();
        self::setUser($user);

        $course_record = $generator->create_course();
        $course = course::from_record($course_record);

        $enrol_manager = new enrol_manager($course);
        $enrol_manager->enrol_course_creator($USER->id);

        // Check that the admin does not get enrolled directly.
        $db = builder::get_db();
        self::assertTrue(
            $db->record_exists_sql(
                '
                    SELECT 1 FROM "ttr_user_enrolments" ue
                    INNER JOIN "ttr_enrol" e ON e.id = ue.enrolid
                    WHERE e.courseid = :course_id AND ue.userid = :user_id
                ',
                [
                    'course_id' => $course->id,
                    'user_id' => $USER->id,
                ]
            )
        );
    }

    /**
     * @return void
     */
    public function test_enrol_guest_user_to_course_as_course_creator(): void {
        global $USER;
        self::setGuestUser();

        $generator = self::getDataGenerator();
        $course_record = $generator->create_course();
        $course = course::from_record($course_record);

        $enrol_manager = new enrol_manager($course);
        $enrol_manager->enrol_course_creator($USER->id);

        // Check that the admin does not get enrolled directly.
        $db = builder::get_db();
        self::assertFalse(
            $db->record_exists_sql(
                '
                    SELECT 1 FROM "ttr_user_enrolments" ue
                    INNER JOIN "ttr_enrol" e ON e.id = ue.enrolid
                    WHERE e.courseid = :course_id AND ue.userid = :user_id
                ',
                [
                    'course_id' => $course->id,
                    'user_id' => $USER->id,
                ]
            )
        );

        self::assertDebuggingCalled("Cannot enrol the guest user as the course creator to the course");
    }

    /**
     * @return void
     */
    public function test_enable_system_disabled_enrol(): void {
        $generator = self::getDataGenerator();
        $course_record = $generator->create_course();

        $course = course::from_record($course_record);
        set_config('enrol_plugins_enabled', 'totara_facetoface');

        $repository = enrol::repository();

        self::assertNull($repository->find_enrol('totara_facetoface', $course->id));
    }

    /**
     * @return void
     */
    public function test_self_enrol(): void {
        global $DB;
        self::setAdminUser();
        $generator = self::getDataGenerator();
        $course_record = $generator->create_course();

        $course = course::from_record($course_record);
        $manager = new enrol_manager($course);

        $instance = $DB->get_record('enrol', ['courseid' => $course->get_id(), 'enrol' => 'self'], '*', MUST_EXIST);
        $plugin = enrol_get_plugin('self');
        $plugin->update_status($instance, ENROL_INSTANCE_ENABLED);

        // No user enrolment record for a user
        self::assertFalse(user_enrolment::repository()->join(['enrol', 'e'], 'enrolid', 'id')
            ->where('e.courseid', $course->id)
            ->where('e.enrol', 'self')
            ->where('userid', get_admin()->id)
            ->exists());

        $manager->do_non_interactive_enrol(get_admin()->id);

        // User enrolment record is created
        self::assertTrue(user_enrolment::repository()->join(['enrol', 'e'], 'enrolid', 'id')
            ->where('e.courseid', $course->id)
            ->where('e.enrol', 'self')
            ->where('userid', get_admin()->id)
            ->exists());
    }
}