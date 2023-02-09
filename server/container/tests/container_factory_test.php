<?php
/**
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core_container
 */
defined('MOODLE_INTERNAL') || die();

use core_container\factory;

class core_container_container_factory_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_fetch_by_id(): void {
        $generator = $this->getDataGenerator();
        $course_record = $generator->create_course();

        $course = factory::from_id($course_record->id);
        $this->assertEquals($course->get_id(), $course_record->id);
        $this->assertEquals('container_course', $course->containertype);
    }

    /**
     * @return void
     */
    public function test_fetch_by_invalid_id(): void {
        $this->expectException(dml_missing_record_exception::class);
        factory::from_id(42);
    }

    /**
     * @return void
     */
    public function test_fetch_by_zero(): void {
        $this->expectException(coding_exception::class);
        factory::from_id(0);
    }

    /**
     * @return void
     */
    public function test_construct_by_record(): void {
        $generator = $this->getDataGenerator();
        $course_record = $generator->create_course();

        $course = factory::from_record($course_record);
        $this->assertEquals($course_record->id, $course->get_id());
        $this->assertEquals($course_record->fullname, $course->fullname);
        $this->assertEquals($course_record->containertype, $course->containertype);
    }

    /**
     * @return void
     */
    public function test_construct_by_record_without_id(): void {
        $generator = $this->getDataGenerator();
        $course_record = $generator->create_course();

        unset($course_record->id);

        $this->expectException(coding_exception::class);
        factory::from_record($course_record);
    }
}