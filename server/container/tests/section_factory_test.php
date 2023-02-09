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

use core_container\section\section_factory;
use core_container\section\section;

class core_container_section_factory_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_fetch_section_from_id(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $course_record = $generator->create_course();

        $section_id = $DB->get_field(
            'course_sections',
            'id',
            [
                'course' => $course_record->id,
                'section' => 0
            ],
            MUST_EXIST
        );
        $section = section_factory::from_id($section_id);

        $this->assertInstanceOf(section::class, $section);
        $this->assertEquals($course_record->id, $section->get_container_id());
        $this->assertEquals(0, $section->get_section_number());
        $this->assertEmpty($section->get_sequence());
        $this->assertEquals($section_id, $section->get_id());
    }

    /**
     * @return void
     */
    public function test_fetch_section_by_record(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $course_record = $generator->create_course();

        $section_record = $DB->get_record(
            'course_sections',
            [
                'course' => $course_record->id,
                'section' => 0
            ]
        );

        $section = section_factory::from_record($section_record);
        $this->assertInstanceOf(section::class, $section);

        $this->assertEquals($course_record->id, $section->get_container_id());
        $this->assertEquals(0, $section->get_section_number());
        $this->assertEmpty($section->get_sequence());
        $this->assertEquals($section_record->id, $section->get_id());
    }

    /**
     * @return void
     */
    public function test_fetch_section_by_not_existing_id_with_strict_mode(): void {
        $this->expectException(dml_missing_record_exception::class);
        section_factory::from_id(42);
    }

    /**
     * @return void
     */
    public function test_fetch_section_by_not_existing_id_without_strict_mode(): void {
        $section = section_factory::from_id(42, false);
        $this->assertNull($section);
    }
}