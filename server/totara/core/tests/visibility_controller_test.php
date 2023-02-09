<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

use \totara_core\local\visibility;
use \totara_core\visibility_controller;

/**
 * Test Totara visibility controller
 *
 * To test, run this from the command line from the $CFG->dirroot
 * vendor/bin/phpunit totara_core_visibility_controller_testcase
 *
 */
class totara_core_visibility_controller_testcase extends advanced_testcase {

    public function test_get_types() {
        self::assertSame(
            [
                'certification',
                'course',
                'program',
            ],
            visibility_controller::types()
        );
    }

    public function test_get_all_maps() {
        $maps = visibility_controller::get_all_maps();
        self::assertIsArray($maps);
        self::assertSame(
            [
                'certification',
                'course',
                'program',
            ],
            array_keys($maps)
        );
        self::assertInstanceOf(visibility\certification\map::class, $maps['certification']);
        self::assertInstanceOf(visibility\course\map::class, $maps['course']);
        self::assertInstanceOf(visibility\program\map::class, $maps['program']);
    }

    public function test_get_traditional() {
        self::assertInstanceOf(visibility\certification\traditional::class, visibility_controller::get('certification'));
        self::assertInstanceOf(visibility\course\traditional::class, visibility_controller::get('course'));
        self::assertInstanceOf(visibility\program\traditional::class, visibility_controller::get('program'));
    }

    public function test_get_audiencebased() {
        global $CFG;
        $CFG->audiencevisibility = 1;
        self::assertInstanceOf(visibility\certification\audiencebased::class, visibility_controller::get('certification'));
        self::assertInstanceOf(visibility\course\audiencebased::class, visibility_controller::get('course'));
        self::assertInstanceOf(visibility\program\audiencebased::class, visibility_controller::get('program'));
    }

    public function test_certification() {
        global $CFG;
        self::assertInstanceOf(visibility\certification\traditional::class, visibility_controller::get('certification'));
        $CFG->audiencevisibility = 1;
        self::assertInstanceOf(visibility\certification\audiencebased::class, visibility_controller::get('certification'));
    }

    public function test_course() {
        global $CFG;
        self::assertInstanceOf(visibility\course\traditional::class, visibility_controller::get('course'));
        $CFG->audiencevisibility = 1;
        self::assertInstanceOf(visibility\course\audiencebased::class, visibility_controller::get('course'));
    }

    public function test_program() {
        global $CFG;
        self::assertInstanceOf(visibility\program\traditional::class, visibility_controller::get('program'));
        $CFG->audiencevisibility = 1;
        self::assertInstanceOf(visibility\program\audiencebased::class, visibility_controller::get('program'));
    }

    public function test_get_unknown() {
        self::expectException(coding_exception::class);
        self::expectExceptionMessage('Unknown visibility controller type');
        visibility_controller::get('monkey');
    }

}