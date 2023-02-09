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

use core_container\container_helper;
use core_container\factory;

class core_container_container_helper_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_get_container_from_invalid_classname(): void {
        $this->expectException(coding_exception::class);
        container_helper::get_container_type_from_classname(15);
    }

    /**
     * @return void
     */
    public function test_get_container_from_not_existing_classname(): void {
        $this->expectException(coding_exception::class);
        container_helper::get_container_type_from_classname('\\dota\\two\\pudge');
    }

    /**
     * @return void
     */
    public function test_get_container_from_instance(): void {
        $generator = $this->getDataGenerator();
        $course_record = $generator->create_course();

        $course = factory::from_record($course_record);
        $container_type = container_helper::get_container_type_from_classname($course);

        $this->assertSame($course_record->containertype, $container_type);
    }

    /**
     * @return void
     */
    public function test_is_container_existing_with_field(): void {
        $generator = $this->getDataGenerator();

        $new_course = new stdClass();
        $new_course->idnumber = '15_x';
        $new_course->shortname = 'course_15';

        $course_record = $generator->create_course($new_course);
        $this->assertTrue(
            container_helper::is_container_existing_with_field('idnumber', $course_record->idnumber)
        );

        $this->assertTrue(
            container_helper::is_container_existing_with_field('shortname', $course_record->shortname)
        );

        $this->assertFalse(
            container_helper::is_container_existing_with_field(
                'idnumber',
                $course_record->idnumber,
                $course_record->id
            )
        );

        $this->assertFalse(
            container_helper::is_container_existing_with_field(
                'shortname',
                $course_record->shortname,
                $course_record->id
            )
        );

        $this->assertFalse(
            container_helper::is_container_existing_with_field('idnumber', uniqid())
        );
    }
}