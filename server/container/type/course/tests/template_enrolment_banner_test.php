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

use container_course\course;
use container_course\output\enrolment_banner;
use core\entity\enrol;
use core\orm\query\builder;
use core_phpunit\testcase;

class container_course_template_enrolment_banner_testcase extends testcase {
    /**
     * @return void
     */
    protected function setUp(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/enrollib.php");
    }

    /**
     * @return void
     */
    public function test_render_viewing_as_admin_with_enrol_option(): void {
        $db = builder::get_db();

        $generator = self::getDataGenerator();
        $course = $generator->create_course();

        // Enable self enrolment for the course.
        $self_enrol = enrol_get_plugin("self");
        $self_enrol_instance = enrol::repository()->find_enrol("self", $course->id);

        // Update enrol record
        $update_enrol_record = $self_enrol_instance->to_record();
        $update_enrol_record->status = ENROL_INSTANCE_ENABLED;

        unset($update_enrol_record->id);

        $self_enrol->update_instance(
            $self_enrol_instance->to_record(),
            $update_enrol_record
        );

        // Checking that the self enrol is enabled.
        self::assertTrue(
            $db->record_exists(
                enrol::TABLE,
                [
                    "status" => ENROL_INSTANCE_ENABLED,
                    "enrol" => "self",
                    "courseid" => $course->id
                ],
            )
        );

        $course = course::from_record($course);
        $admin = get_admin();

        self::setUser($admin);

        $banner = enrolment_banner::create_from_course($course, $admin->id);
        $data = $banner->get_template_data();

        self::assertArrayHasKey("message", $data);
        self::assertEquals(
            get_string("view_course_as_admin_with_enrol_options", "container_course"),
            $data["message"]
        );

        self::assertArrayHasKey("enrol_button", $data);
        self::assertIsArray($data["enrol_button"]);

        self::assertArrayHasKey("display", $data["enrol_button"]);
        self::assertArrayHasKey("url", $data["enrol_button"]);

        // There is a link to enrolment options.
        self::assertTrue($data["enrol_button"]["display"]);
    }

    /**
     * @return void
     */
    public function test_render_viewing_as_admin_without_enrol_option(): void {
        $generator = self::getDataGenerator();
        $course = $generator->create_course();

        $admin = get_admin();
        self::setUser($admin);

        // Make sure that self enrolment is disabled.
        $db = builder::get_db();
        self::assertFalse(
            $db->record_exists(
                enrol::TABLE,
                [
                    "status" => ENROL_INSTANCE_ENABLED,
                    "enrol" => "self",
                    "courseid" => $course->id
                ]
            )
        );

        // Check the banner data.
        $course_container = course::from_record($course);
        $banner = enrolment_banner::create_from_course($course_container, $admin->id);
        $data = $banner->get_template_data();

        self::assertArrayHasKey("message", $data);
        self::assertEquals(
            get_string("view_course_as_admin", "container_course"),
            $data["message"]
        );

        self::assertArrayHasKey("enrol_button", $data);
        self::assertArrayHasKey("display", $data["enrol_button"]);
        self::assertArrayHasKey("url", $data["enrol_button"]);

        self::assertFalse($data["enrol_button"]["display"]);
    }

    /**
     * @return void
     */
    public function test_render_viewing_as_guest_with_enrol_option(): void {
        $generator = self::getDataGenerator();
        $course_record = $generator->create_course();

        $user_one = $generator->create_user();

        $enrol_repository = enrol::repository();

        // Enable guest enrolment and self enrolment.
        $self_enrol = enrol_get_plugin("self");
        $self_enrol_instance = $enrol_repository->find_enrol("self", $course_record->id);

        $update_self_record = $self_enrol_instance->to_record();
        $update_self_record->status = ENROL_INSTANCE_ENABLED;
        unset($update_self_record->id);

        $self_enrol->update_instance($self_enrol_instance->to_record(), $update_self_record);

        $guest_enrol = enrol_get_plugin("guest");
        $guest_enrol_instance = $enrol_repository->find_enrol("guest", $course_record->id);

        $update_guest_record = $guest_enrol_instance->to_record(true);
        $update_guest_record->status = ENROL_INSTANCE_ENABLED;
        unset($update_guest_record->id);

        $guest_enrol->update_instance($guest_enrol_instance->to_record(), $update_guest_record);

        // Check enable status of the two enrol methods.
        $db = builder::get_db();

        self::assertTrue(
            $db->record_exists(
                enrol::TABLE,
                [
                    "status" => ENROL_INSTANCE_ENABLED,
                    "enrol" => "self",
                    "courseid" => $course_record->id
                ]
            )
        );

        self::assertTrue(
            $db->record_exists(
                enrol::TABLE,
                [
                    "status" => ENROL_INSTANCE_ENABLED,
                    "enrol" => "guest",
                    "courseid" => $course_record->id
                ]
            )
        );

        self::setUser($user_one);

        // Check the banner.
        $course = course::from_record($course_record);
        $banner = enrolment_banner::create_from_course($course, $user_one->id);

        $data = $banner->get_template_data();
        self::assertArrayHasKey("message", $data);

        self::assertEquals(
            get_string(
                "view_course_as_guest_with_enrol_options",
                "container_course"
            ),
            $data["message"]
        );

        self::assertArrayHasKey("enrol_button", $data);
        self::assertArrayHasKey("display", $data["enrol_button"]);
        self::assertArrayHasKey("url", $data["enrol_button"]);

        // The button should be displayed for non enrolled user.
        self::assertTrue($data["enrol_button"]["display"]);
    }

    /**
     * @return void
     */
    public function test_render_viewing_as_guest_without_enrol_option(): void  {
        $generator = self::getDataGenerator();
        $course_record = $generator->create_course();

        $user_one = $generator->create_user();

        $guest_enrol = enrol_get_plugin("guest");
        $guest_enrol_instance = enrol::repository()->find_enrol("guest", $course_record->id);

        $update_guest_record = $guest_enrol_instance->to_record(true);
        $update_guest_record->status = ENROL_INSTANCE_ENABLED;
        unset($update_guest_record->id);

        $guest_enrol->update_instance($guest_enrol_instance->to_record(), $update_guest_record);

        // Check enable status of the two enrol methods.
        $db = builder::get_db();
        self::assertTrue(
            $db->record_exists(
                enrol::TABLE,
                [
                    "status" => ENROL_INSTANCE_ENABLED,
                    "enrol" => "guest",
                    "courseid" => $course_record->id
                ]
            )
        );

        self::setUser($user_one);

        $course = course::from_record($course_record);
        $banner = enrolment_banner::create_from_course($course, $user_one->id);

        $data = $banner->get_template_data();
        self::assertArrayHasKey("message", $data);

        self::assertEquals(
            get_string("view_course_as_guest", "container_course"),
            $data["message"]
        );

        self::assertArrayHasKey("enrol_button", $data);
        self::assertArrayHasKey("display", $data["enrol_button"]);
        self::assertArrayHasKey("url", $data["enrol_button"]);

        // The button should be displayed for non enrolled user.
        self::assertFalse($data["enrol_button"]["display"]);
    }

    /**
     * @return void
     */
    public function test_render_banner_for_user_who_is_already_enrolled(): void {
        $generator = self::getDataGenerator();
        $course = $generator->create_course();

        $user_one = $generator->create_user();
        $generator->enrol_user($user_one->id, $course->id);

        $db = builder::get_db();

        self::assertFalse(
            $db->record_exists(
                enrol::TABLE,
                [
                    "enrol" => "guest",
                    "status" => ENROL_INSTANCE_ENABLED,
                    "courseid" => $course->id
                ]
            )
        );

        self::setUser($user_one);
        $course_container = course::from_record($course);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage(
            "Cannot create an enrolment banner for the user who is already enrolled into the given course"
        );

        enrolment_banner::create_from_course($course_container, $user_one->id);
    }
}