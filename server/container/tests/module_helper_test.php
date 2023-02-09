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
use core_container\module\helper;

class core_container_module_helper_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_prepare_default_course_module(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $course_record = $generator->create_course();

        $course = factory::from_record($course_record);

        $module_info = new stdClass();
        $module_info->course = $course->id;
        $module_info->name = "random name";
        $module_info->visible = 1;
        $module_info->modulename = 'facetoface';
        $module_info->groupmode = null;
        $module_info->groupingid = null;

        $cloned_module_info = clone $module_info;
        $new_course_mdoule = helper::prepare_new_cm($cloned_module_info, $course);

        // Make sure that the $cloned_module_info has not been modified via references by
        // the function itself.
        $this->assertEquals($cloned_module_info,$module_info);
        $this->assertNotEquals($new_course_mdoule, $module_info);

        $facetoface_module_id = $DB->get_field('modules', 'id', ['name' => 'facetoface']);
        $this->assertEquals($facetoface_module_id, $new_course_mdoule->module);

        $this->assertObjectHasAttribute('visibleold', $new_course_mdoule);
        $this->assertEquals($new_course_mdoule->visibleold, $module_info->visible);
    }

    /**
     * Debugging should be called when there is no visible provided for course module.
     * @return void
     */
    public function test_prepare_default_course_module_without_visible(): void {
        $generator = $this->getDataGenerator();
        $course_record = $generator->create_course();

        $course = factory::from_record($course_record);

        $module_info = new stdClass();
        $module_info->course = $course->id;
        $module_info->name = 'random name';
        $module_info->modulename = 'facetoface';
        $module_info->groupmode = null;
        $module_info->groupingid = null;

        helper::prepare_new_cm($module_info, $course);
        $debug_messages = $this->getDebuggingMessages();
        $this->assertDebuggingCalled();

        $this->assertNotEmpty($debug_messages);
        $this->assertCount(1, $debug_messages);

        $debug_message = reset($debug_messages);
        $this->assertStringContainsString(
            "There is no 'visible' property set for parameter",
            $debug_message->message
        );
    }

    /**
     * @return void
     */
    public function test_prepare_default_course_module_without_module_name(): void {
        $generator = $this->getDataGenerator();
        $course_record = $generator->create_course();

        $course = factory::from_record($course_record);

        $module_info = new stdClass();
        $module_info->course = $course->id;

        $this->expectException(coding_exception::class);
        helper::prepare_new_cm($module_info, $course);
    }
}