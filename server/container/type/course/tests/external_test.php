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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package container_course
 */

use container_course\external as external_api;
use core\entity\enrol;
use core\entity\user_enrolment;
use core_phpunit\testcase;

class container_course_external_testcase extends testcase {
    /**
     * @return void
     */
    public function test_external_with_admin(): void {
        self::setAdminUser();
        $generator = self::getDataGenerator();
        $course = $generator->create_course();

        self::assertEquals(0, user_enrolment::repository()->count());

        $enrol = enrol_get_plugin("self");
        $instance = enrol::repository()->find_enrol("self", $course->id);
        $enrol->update_status($instance->to_record(true), ENROL_INSTANCE_ENABLED);

        self::assertTrue(external_api::process_non_interactive_enrol($course->id));
        self::assertTrue(user_enrolment::repository()->where('userid', get_admin()->id)->exists());
    }

    /**
     * @return void
     */
    public function test_external_with_site_guest(): void {
        self::setGuestUser();
        $generator = self::getDataGenerator();
        $course = $generator->create_course();

        self::assertEquals(0, user_enrolment::repository()->count());

        $enrol = enrol_get_plugin("self");
        $instance = enrol::repository()->find_enrol("self", $course->id);
        $enrol->update_status($instance->to_record(true), ENROL_INSTANCE_ENABLED);

        self::expectExceptionMessage('Not support to site guest');
        self::expectException(coding_exception::class);
        external_api::process_non_interactive_enrol($course->id);
    }

    /**
     * @return void
     */
    public function test_external_with_enrolled_user(): void {
        $generator = self::getDataGenerator();
        $user = $generator->create_user();
        $course = $generator->create_course();

        self::setUser($user);
        $enrol = enrol_get_plugin("self");
        $instance = enrol::repository()->find_enrol("self", $course->id);
        $instance = $instance->to_record(true);
        $enrol->update_status($instance, ENROL_INSTANCE_ENABLED);

        // Enrol user.
        $enrol->enrol_user($instance, $user->id);

        self::expectExceptionMessage('You have already enrolled');
        self::expectException(coding_exception::class);
        external_api::process_non_interactive_enrol($course->id);
    }

    /**
     * @return void
     */
    public function test_external_with_password_change(): void {
        $generator = self::getDataGenerator();
        $user = $generator->create_user();
        $course = $generator->create_course();

        self::setUser($user);
        $enrol = enrol_get_plugin("self");
        $instance = enrol::repository()->find_enrol("self", $course->id);
        $update_instance = $instance->to_record(true);
        $update_instance->password = 'password';
        $enrol->update_instance($instance->to_record(true), $update_instance);


        self::expectExceptionMessage('Not support non interactive enrol');
        self::expectException(coding_exception::class);
        external_api::process_non_interactive_enrol($course->id);
    }

    /**
     * @return void
     */
    public function test_external_new_enrol_setting(): void {
        $generator = self::getDataGenerator();
        $user = $generator->create_user();
        $course = $generator->create_course();

        self::setUser($user);
        $enrol = enrol_get_plugin("self");
        $instance = enrol::repository()->find_enrol("self", $course->id);
        $update_instance = $instance->to_record(true);
        $update_instance->customint6 = '0';
        $enrol->update_instance($instance->to_record(true), $update_instance);

        self::expectExceptionMessage('Not support non interactive enrol');
        self::expectException(coding_exception::class);
        external_api::process_non_interactive_enrol($course->id);
    }

    /**
     * @return void
     */
    public function test_external_with_audience_enabled(): void {
        $generator = self::getDataGenerator();
        $user = $generator->create_user();
        $course = $generator->create_course();

        self::setUser($user);
        $enrol = enrol_get_plugin("self");
        $instance = enrol::repository()->find_enrol("self", $course->id);
        $update_instance = $instance->to_record(true);
        $update_instance->customint5 = '1';
        $enrol->update_instance($instance->to_record(true), $update_instance);

        self::expectException(coding_exception::class);
        self::expectExceptionMessage('Not support non interactive enrol');
        external_api::process_non_interactive_enrol($course->id);
    }

}