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

use core_container\module\module_factory;
use core_container\factory;
use core_container\module\module;

class core_container_module_factory_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_fetch_from_id(): void {
        $generator = $this->getDataGenerator();
        $course_record = $generator->create_course();

        $module_info = new stdClass();
        $module_info->course = $course_record->id;

        $facetoface = $generator->create_module('facetoface', $module_info);

        $this->assertObjectHasAttribute('cmid', $facetoface);
        $this->assertObjectHasAttribute('id', $facetoface);

        $module = module_factory::from_id($facetoface->cmid);
        $this->assertInstanceOf(module::class, $module);

        $this->assertEquals($facetoface->cmid, $module->get_id());
        $this->assertEquals($facetoface->id, $module->get_instance());

        $course = factory::from_record($course_record);
        $section_zero = $course->get_section(0);

        $this->assertEquals($section_zero->get_id(), $module->get_section()->get_id());
    }

    /**
     * @return void
     */
    public function test_construct_module_from_record(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $course_record = $generator->create_course();

        $module_info = new stdClass();
        $module_info->course = $course_record->id;

        $facetoface = $generator->create_module('facetoface', $module_info);

        $this->assertObjectHasAttribute('cmid', $facetoface);
        $this->assertObjectHasAttribute('id', $facetoface);

        $module_record = $DB->get_record('course_modules', ['id' => $facetoface->cmid]);
        $module = module_factory::from_record($module_record);

        $this->assertInstanceOf(module::class, $module);

        $this->assertEquals($facetoface->cmid, $module->get_id());
        $this->assertEquals($facetoface->id, $module->get_instance());

        $course = factory::from_record($course_record);
        $section_zero = $course->get_section(0);

        $this->assertEquals($section_zero->get_id(), $module->get_section()->get_id());
    }
}