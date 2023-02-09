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
 * @package core_course
 */
defined('MOODLE_INTERNAL') || die();

use core\entity\course_section;
use core\entity\course_section_repository;
use core\orm\query\exceptions\record_not_found_exception;
use core_phpunit\testcase;

class core_course_section_repository_testcase extends testcase {
    /**
     * @return void
     */
    public function test_find_by_section_number_and_course(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();

        $section_zero = new stdClass();
        $section_zero->course = $course->id;
        $section_zero->section = 0;

        $created_section = $generator->create_course_section($section_zero);
        $this->assertTrue(
            $DB->record_exists(
                'course_sections',
                [
                    'section' => 0,
                    'course' => $course->id
                ]
            )
        );

        /** @var course_section_repository $repository */
        $repository = course_section::repository();
        $section = $repository->find_by_section_number_and_course($course->id, 0);

        $this->assertEquals($section->id, $created_section->id);
        $this->assertEquals($section->name, $created_section->name);
        $this->assertEquals($section->course, $created_section->course);

        $this->assertInstanceOf(course_section::class, $section);
    }

    /**
     * @return void
     */
    public function test_fetch_section_not_found(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();

        /** @var course_section $repository */
        $repository = course_section::repository();

        // All the newly created course(s) will have a section zero.
        $section = $repository->find_by_section_number_and_course($course->id, 0);
        $this->assertNotNull($section);
        $this->assertEquals(0, $section->section);
        $this->assertEquals($course->id, $section->course);

        // Start fetching the section that does not exist within system.
        $not_existing_section = $repository->find_by_section_number_and_course($course->id, 15, false);
        $this->assertNull($not_existing_section);

        // Start fetching the section that does not exist but the repository will throw the exception.
        try {
            $repository->find_by_section_number_and_course($course->id, 15);
            $this->fail("The fetching function does not throw any exception");
        } catch (record_not_found_exception $exception) {
            $this->assertStringContainsString("Can not find data record in database.", $exception->getMessage());
        }
    }
}