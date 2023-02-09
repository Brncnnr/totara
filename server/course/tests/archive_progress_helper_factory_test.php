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

use core_course\local\archive_progress_helper\factory;
use core_course\local\archive_progress_helper\single_user;
use core_course\local\archive_progress_helper\current_user;
use core_course\local\archive_progress_helper\completed_users;
use core_phpunit\testcase;

/**
 * @covers \core_course\local\archive_progress_helper\factory
 */
class core_course_archive_progress_helper_factory_testcase extends testcase {

    public function test_get_helper_current_user() {
        $course = new stdClass();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        self::assertInstanceOf(current_user::class,  factory::get_helper($course, $user));
    }

    public function test_get_helper_single_user() {
        $course = new stdClass();
        $user = $this->getDataGenerator()->create_user();
        self::assertInstanceOf(single_user::class,  factory::get_helper($course, $user));
    }

    public function test_get_helper_completed_users() {
        $course = new stdClass();
        self::assertInstanceOf(completed_users::class,  factory::get_helper($course, null));
    }

}