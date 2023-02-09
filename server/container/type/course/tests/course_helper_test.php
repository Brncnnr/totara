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
use container_course\hook\remove_module_hook;
use core\entity\enrol;
use core\orm\query\builder;
use core_phpunit\testcase;
use container_course\course_helper;

class container_course_course_helper_testcase extends testcase {
    /**
     * @return void
     */
    public function test_get_all_modules_execute_hook(): void {
        $hook_sink = self::redirectHooks();
        self::assertEquals(0, $hook_sink->count());
        self::assertEmpty($hook_sink->get_hooks());

        course_helper::get_all_modules(true);
        self::assertEquals(2, $hook_sink->count());
        $hooks = $hook_sink->get_hooks();

        self::assertCount(2, $hooks);

        // We will not be interesting in the first hook, but the last for this test.
        $second_hook = end($hooks);
        self::assertInstanceOf(remove_module_hook::class, $second_hook);
    }

    /**
     * @return void
     */
    public function test_get_all_modules_skip_execute_hook(): void {
        $hook_sink = self::redirectHooks();
        self::assertEquals(0, $hook_sink->count());
        self::assertEmpty($hook_sink->get_hooks());

        course_helper::get_all_modules(true, false, false);
        self::assertEquals(1, $hook_sink->count());
        $hooks = $hook_sink->get_hooks();

        self::assertCount(1, $hooks);

        // We will not be interesting in the first hook, but the last for this test.
        $hook = end($hooks);
        self::assertNotInstanceOf(remove_module_hook::class, $hook);
    }

    /**
     * Test to cover the case where user is just a normal user, not a guest
     *
     * @return void
     */
    public function test_should_not_render_enrolment_banner_for_non_guest_user(): void {
        $generator = self::getDataGenerator();
        $course_record = $generator->create_course();

        $user = $generator->create_user();
        $course = course::from_record($course_record);

        self::assertFalse(course_helper::should_render_enrolment_banner($course, $user->id));
    }

    /**
     * @return void
     */
    public function test_should_not_render_enrolment_banner_for_enrolled_user(): void {
        $generator = self::getDataGenerator();
        $course_record = $generator->create_course();

        $user = $generator->create_user();
        $generator->enrol_user($user->id, $course_record->id);

        $course = course::from_record($course_record);
        self::assertFalse(course_helper::should_render_enrolment_banner($course, $user->id));
    }

    /**
     * @return void
     */
    public function test_should_render_enrolment_banner_for_guest_user(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/enrollib.php");

        $generator = self::getDataGenerator();
        $course_record = $generator->create_course();
        $user = $generator->create_user();
        self::setUser($user);

        $plugin = enrol_get_plugin("guest");
        $instance = enrol::repository()->find_enrol("guest", $course_record->id);

        $update_instance = $instance->to_record(true);
        $plugin->update_status($update_instance, ENROL_INSTANCE_ENABLED);

        $course = course::from_record($course_record);
        self::assertTrue(course_helper::should_render_enrolment_banner($course, $user->id));
    }

    /**
     * @return void
     */
    public function test_should_render_enrolment_banner_for_admin_user(): void {
        $generator = self::getDataGenerator();
        $course_record = $generator->create_course();

        $admin = get_admin();
        $course = course::from_record($course_record);

        self::assertTrue(
            course_helper::should_render_enrolment_banner($course, $admin->id)
        );
    }

    /**
     * @return void
     */
    public function test_shouldrender_enrolmentt_for_enrolled_admin_user(): void {
        $generator = self::getDataGenerator();
        $course_record = $generator->create_course();

        $admin = get_admin();
        $generator->enrol_user($admin->id, $course_record->id);

        $course = course::from_record($course_record);
        self::assertFalse(course_helper::should_render_enrolment_banner($course, $admin->id));
    }

    /**
     * Test suite to check if the banner is rendered for user who view a course that does
     * not have guest access enabled.
     *
     * @return void
     */
    public function test_render_enrolment_banner_for_non_guest_user(): void {
        global $OUTPUT;

        $generator = self::getDataGenerator();
        $course_record = $generator->create_course();

        $user = $generator->create_user();

        self::assertEmpty(
            course_helper::render_enrolment_banner(
                $OUTPUT,
                $course_record,
                $user->id
            )
        );
    }

    /**
     * @return void
     */
    public function test_render_enrolment_banner_for_guest_user(): void {
        global $OUTPUT, $CFG;
        require_once("{$CFG->dirroot}/lib/enrollib.php");

        $generator = self::getDataGenerator();
        $course_record = $generator->create_course();
        $user = $generator->create_user();

        $plugin = enrol_get_plugin("guest");
        $instance = enrol::repository()->find_enrol("guest", $course_record->id);

        $update_instance = $instance->to_record(true);
        $update_instance->status = ENROL_INSTANCE_ENABLED;
        unset($update_instance->id);

        $plugin->update_instance($instance->to_record(true), $update_instance);

        // Enrol guest user
        self::setUser($user);

        $instance->refresh();
        $plugin->try_guestaccess($instance->to_record(true));

        // Check the status.
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

        $content = course_helper::render_enrolment_banner($OUTPUT, $course_record, $user->id);
        self::assertNotEmpty($content);
        self::assertStringContainsString(
            get_string("view_course_as_guest", "container_course"),
            $content
        );
    }

    /**
     * @return void
     */
    public function test_render_enrolment_banner_from_enrolled_user(): void {
        global $OUTPUT;

        $generator = self::getDataGenerator();
        $course_record = $generator->create_course();

        $user = $generator->create_user();
        $generator->enrol_user($user->id, $course_record->id);

        self::assertEmpty(
            course_helper::render_enrolment_banner($OUTPUT, $course_record, $user->id)
        );
    }

    /**
     * @return void
     */
    public function test_render_enrolment_banner_for_admin_user(): void {
        global $OUTPUT;

        $generator = self::getDataGenerator();
        $course_record = $generator->create_course();

        $admin = get_admin();
        $content = course_helper::render_enrolment_banner($OUTPUT, $course_record, $admin->id);

        self::assertNotEmpty($content);
        self::assertStringContainsString(
            get_string("view_course_as_admin", "container_course"),
            $content
        );
    }

    /**
     * @return void
     */
    public function test_render_enrolment_banner_for_admin_user_with_enrolled_option(): void {
        global $OUTPUT, $CFG;
        require_once("{$CFG->dirroot}/lib/enrollib.php");

        $generator = self::getDataGenerator();
        $course_record = $generator->create_course();

        $plugin = enrol_get_plugin("self");
        $instance = enrol::repository()->find_enrol("self", $course_record->id);

        $update_instance = $instance->to_record(true);
        $update_instance->status = ENROL_INSTANCE_ENABLED;
        unset($update_instance->id);

        $plugin->update_instance($instance->to_record(true), $update_instance);

        $admin = get_admin();
        $content = course_helper::render_enrolment_banner($OUTPUT, $course_record, $admin->id);

        self::assertNotEmpty($content);
        self::assertStringContainsString(
            get_string("view_course_as_admin_with_enrol_options", "container_course"),
            $content
        );
    }

    /**
     * @return void
     */
    public function test_render_enrrolment_banner_for_guest_user_with_enrolled_options(): void {
        global $OUTPUT, $CFG;
        require_once("{$CFG->dirroot}/lib/enrollib.php");

        $generator = self::getDataGenerator();
        $course_record = $generator->create_course();
        $user = $generator->create_user();

        $guest_enrol = enrol_get_plugin("guest");
        $guest_enrol_instance = enrol::repository()->find_enrol("guest", $course_record->id);

        $update_guest_instance = $guest_enrol_instance->to_record(true);
        $update_guest_instance->status = ENROL_INSTANCE_ENABLED;
        unset($update_guest_instance->id);

        $guest_enrol->update_instance($guest_enrol_instance->to_record(true), $update_guest_instance);

        // Enrol guest user
        self::setUser($user);

        $guest_enrol_instance->refresh();
        $guest_enrol->try_guestaccess($guest_enrol_instance->to_record(true));

        // Enable self enrolment options.
        $self_enrol = enrol_get_plugin("self");
        $self_enrol_instance = enrol::repository()->find_enrol("self", $course_record->id);

        $update_self_instance = $self_enrol_instance->to_record(true);
        $update_self_instance->status = ENROL_INSTANCE_ENABLED;
        unset($update_self_instance->id);

        $self_enrol->update_instance($self_enrol_instance->to_record(true), $update_self_instance);

        // Check the status.
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

        $content = course_helper::render_enrolment_banner($OUTPUT, $course_record, $user->id);
        self::assertNotEmpty($content);
        self::assertStringContainsString(
            get_string("view_course_as_guest_with_enrol_options", "container_course"),
            $content
        );
    }
}