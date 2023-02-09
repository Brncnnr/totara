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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package container_course
 */
defined('MOODLE_INTERNAL');

use core_container\factory;

class container_course_section_modules_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_get_all_modules_in_section(): void {
        global $DB;
        $this->setAdminUser();

        $gen = $this->getDataGenerator();
        $course = $gen->create_course();

        $course = factory::from_record($course);

        // Add a few modules facetoface.
        for ($i = 0; $i < 2; $i++) {
            $gen->create_module('facetoface', ['course' => $course->id]);
        }

        $section = $course->get_section(0);
        $modules = $section->get_all_modules();

        $this->assertCount(2, $modules);

        // Now start deleting one of the modules and see whether the function give us a debugging message or not.
        $DB->delete_records('course_modules', ['id' => $modules[0]->get_id()]);

        // One module is missing.
        $section->get_all_modules();

        $this->assertDebuggingCalled(
            "There are missing module(s) in the section '{$section->get_section_number()}'",
            DEBUG_DEVELOPER
        );
    }
}