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
 * @package core
 */

use core\webapi\resolver\type\course_format;
use core_phpunit\testcase;
use totara_webapi\phpunit\webapi_phpunit_helper;

class core_webapi_resolver_test_course_format_testcase extends testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    protected function setUp(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/course/format/lib.php");
    }

    /**
     * @return void
     */
    public function test_resolve_field_format(): void {
        $course = self::getDataGenerator()->create_course(["format" => "singleactivity"]);
        $course_format = course_get_format($course);

        self::assertEquals(
            "singleactivity",
            $this->resolve_graphql_type(
                $this->get_graphql_name(course_format::class),
                "format",
                $course_format,
                [],
                context_course::instance($course->id)
            )
        );
    }

    /**
     * @return void
     */
    public function test_resolve_field_name(): void {
        $course = self::getDataGenerator()->create_course(["format" => "topics"]);
        $course_format = course_get_format($course);

        self::assertEquals(
            $course_format->get_format_name(),
            $this->resolve_graphql_type(
                $this->get_graphql_name(course_format::class),
                "name",
                $course_format,
                [],
                context_course::instance($course->id)
            )
        );
    }

    /**
     * @return void
     */
    public function test_resolve_field_has_course_view_page(): void {
        $course = self::getDataGenerator()->create_course(["format" => "topics"]);
        $course_format = course_get_format($course);

        self::assertEquals(
            $course_format->has_view_page(),
            $this->resolve_graphql_type(
                $this->get_graphql_name(course_format::class),
                "has_course_view_page",
                $course_format,
                [],
                context_course::instance($course->id)
            )
        );
    }
}