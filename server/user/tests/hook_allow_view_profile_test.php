<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @package core_user
 */

defined('MOODLE_INTERNAL') || die();

class core_user_hook_allow_view_profile_testcase extends advanced_testcase {

    public function test_basic_operation_without_course() {
        $hook = new \core_user\hook\allow_view_profile(7, 64);

        self::assertSame(7, $hook->target_user_id);
        self::assertSame(64, $hook->viewing_user_id);
        self::assertNull($hook->get_course());
        self::assertNull($hook->get_course_context());
        self::assertFalse($hook->has_permission());
        $hook->give_permission();
        self::assertTrue($hook->has_permission());
        $hook->give_permission();
        self::assertTrue($hook->has_permission());
    }

    public function test_basic_operation_with_course_without_context() {
        $course = $this->getDataGenerator()->create_course();

        $hook = new \core_user\hook\allow_view_profile(7, 64, $course);

        self::assertSame(7, $hook->target_user_id);
        self::assertSame(64, $hook->viewing_user_id);
        self::assertSame($course, $hook->get_course());
        self::assertEquals(\context_course::instance($course->id), $hook->get_course_context());
        self::assertFalse($hook->has_permission());
        $hook->give_permission();
        self::assertTrue($hook->has_permission());
        $hook->give_permission();
        self::assertTrue($hook->has_permission());
    }


    public function test_basic_operation_with_course_and_context() {
        $course = $this->getDataGenerator()->create_course();
        $course_context = \context_course::instance($course->id);

        $hook = new \core_user\hook\allow_view_profile(7, 64, $course, $course_context);

        self::assertSame(7, $hook->target_user_id);
        self::assertSame(64, $hook->viewing_user_id);
        self::assertSame($course, $hook->get_course());
        self::assertSame($course_context, $hook->get_course_context());
        self::assertFalse($hook->has_permission());
        $hook->give_permission();
        self::assertTrue($hook->has_permission());
        $hook->give_permission();
        self::assertTrue($hook->has_permission());
    }

    public function test_no_course_but_context() {
        $course_context = \context_course::instance($this->getDataGenerator()->create_course()->id);

        self::expectException(\coding_exception::class);
        new \core_user\hook\allow_view_profile(7, 64, null, $course_context);
    }

    public function test_mismatching_course() {
        $course = $this->getDataGenerator()->create_course();
        $course_context = \context_course::instance($this->getDataGenerator()->create_course()->id);

        self::expectException(\coding_exception::class);
        new \core_user\hook\allow_view_profile(7, 64, $course, $course_context);
    }

}