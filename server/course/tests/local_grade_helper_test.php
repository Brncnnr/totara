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
 * @package core_course
 */

use core_course\local\grade_helper;
use core_phpunit\testcase;

/**
 * @coversDefaultClass core_course\local\grade_helper
 */
class core_course_local_grade_helper_testcase extends testcase {

    protected function setUp(): void {
        // Lower the limit before grades are recalculated asynchronously.
        set_config('course_regrade_grade_items_async', 10);
        set_config('course_regrade_enrolments_async', 10);
    }

    protected function tearDown(): void {
        set_config('course_regrade_grade_items_async', 100);
        set_config('course_regrade_enrolments_async', 100);
    }

    /**
     * @covers ::does_course_need_regrade
     * @return void
     */
    public function test_does_course_need_regrade(): void {
        $generator = self::getDataGenerator();
        $course = $generator->create_course();

        for ($i = 0; $i < 9; $i++) {
            $generator->create_module('assign', ['course' => $course->id]);
        }

        self::assertFalse(grade_helper::does_course_need_regrade($course->id));
        $generator->create_module('assign', ['course' => $course->id]);

        self::assertTrue(grade_helper::does_course_need_regrade($course->id));
    }

    /**
     * @covers ::use_async_course_regrade
     * @return void
     */
    public function test_use_async_course_regrade_items(): void {
        $generator = self::getDataGenerator();
        $course = $generator->create_course();
        self::assertFalse(grade_helper::use_async_course_regrade($course->id));

        // Add limit items.
        // There is already 1 grade item for the course, so we need to add 9 more.
        for ($i = 0; $i < 9; $i++) {
            // This takes ages, by the way.
            $generator->create_module('assign', ['course' => $course->id]);
        }
        self::assertFalse(grade_helper::use_async_course_regrade($course->id));

        // Push over the limit
        $generator->create_module('assign', ['course' => $course->id]);
        self::assertTrue(grade_helper::use_async_course_regrade($course->id));
    }

    /**
     * @covers ::use_async_course_regrade
     * @return void
     */
    public function test_use_async_course_regrade_users(): void {
        $generator = self::getDataGenerator();
        $course = $generator->create_course();
        self::assertFalse(grade_helper::use_async_course_regrade($course->id));

        // Add limit enrolments.
        for ($i = 0; $i < 10; $i++) {
            $generator->enrol_user($generator->create_user()->id, $course->id);
        }
        self::assertFalse(grade_helper::use_async_course_regrade($course->id));

        // Push over the limit
        $generator->enrol_user($generator->create_user()->id, $course->id);
        self::assertTrue(grade_helper::use_async_course_regrade($course->id));
    }
}