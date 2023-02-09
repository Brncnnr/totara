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
 * @package container_course
 */
defined('MOODLE_INTERNAL') || die();

use container_course\course;
use container_course\module\course_module;
use container_course\section\course_section;

class container_course_move_module_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_move_module_within_a_section(): void {
        $generator = self::getDataGenerator();
        $course_record = $generator->create_course();

        $course = course::from_record($course_record);

        $mod_f2f_one = $generator->create_module('facetoface', ['course' => $course->id]);
        $mod_f2f_two = $generator->create_module('facetoface', ['course' => $course->id]);

        $module_one = course_module::from_id($mod_f2f_one->cmid);
        $module_two = course_module::from_id($mod_f2f_two->cmid);

        $section_zero = $course->get_section(0);
        $mod_sequences = $section_zero->get_sequence();

        self::assertEquals(
            [$module_one->get_id(), $module_two->get_id()],
            $mod_sequences
        );

        $module_two->move_to_section($section_zero->get_section_number(), $module_one->get_id());
        $section_zero->reload();

        $updated_mod_sequences = $section_zero->get_sequence();
        self::assertEquals(
            [$module_two->get_id(), $module_one->get_id()],
            $updated_mod_sequences
        );
    }

    /**
     * @return void
     */
    public function test_move_module_within_a_section_without_before_mod(): void {
        $generator = self::getDataGenerator();
        $course_record = $generator->create_course();
        $generator->create_module('facetoface', ['course' => $course_record->id]);

        $course = course::from_record($course_record);
        $section_zero = $course->get_section(0);

        $all_modules = $section_zero->get_all_modules();
        self::assertCount(1, $all_modules);

        $module_one = reset($all_modules);
        self::assertEquals($section_zero->get_id(), $module_one->get_section()->get_id());

        // This is to make sure that the API is not moving the module to a section that does not exist yet.
        $result = $module_one->move_to_section(0);
        self::assertTrue($result);

        $section_zero->reload();
        $updated_all_modules = $section_zero->get_all_modules();
        self::assertCount(1, $updated_all_modules);

        $module_one->reload();
        self::assertEquals($section_zero->get_id(), $module_one->get_section()->get_id());
    }

    /**
     * @return void
     */
    public function test_move_module_to_the_bottom_of_the_list(): void {
        $generator = self::getDataGenerator();
        $course_record = $generator->create_course();

        $f2f_one = $generator->create_module('facetoface', ['course' => $course_record->id]);
        $f2f_two = $generator->create_module('facetoface', ['course' => $course_record->id]);

        $course = course::from_record($course_record);
        $section_zero = $course->get_section(0);


        self::assertCount(2, $section_zero->get_sequence());
        self::assertEquals([$f2f_one->cmid, $f2f_two->cmid], $section_zero->get_sequence());

        // Move the facetoface module one to the bottom of the list within the same section.
        $module_f2f_one = course_module::from_id($f2f_one->cmid);
        $module_f2f_one->move_to_section($section_zero->get_section_number(), null);

        $section_zero->reload();
        self::assertEquals([$f2f_two->cmid, $f2f_one->cmid], $section_zero->get_sequence());
    }

    /**
     * @return void
     */
    public function test_to_not_move_module_to_a_non_existing_section(): void {
        $generator = self::getDataGenerator();
        $course_record = $generator->create_course();
        $generator->create_module('facetoface', ['course' => $course_record->id]);

        $course = course::from_record($course_record);
        $section_zero = $course->get_section(0);

        $all_modules = $section_zero->get_all_modules();
        self::assertCount(1, $all_modules);

        $module_one = reset($all_modules);
        self::assertEquals($section_zero->get_id(), $module_one->get_section()->get_id());

        // This is to make sure that the API is not moving the module to a section that does not exist yet.
        $result = $module_one->move_to_section(42);
        self::assertFalse($result);

        $section_zero->reload();
        $updated_all_modules = $section_zero->get_all_modules();
        self::assertCount(1, $updated_all_modules);

        $module_one->reload();
        self::assertEquals($section_zero->get_id(), $module_one->get_section()->get_id());
    }

    /**
     * @return void
     */
    public function test_move_module_to_a_hidden_section(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/course/lib.php");

        $generator = self::getDataGenerator();
        $course_record = $generator->create_course();

        $generator->create_module('facetoface', ['course' => $course_record->id]);

        // Make section one to be hidden
        set_section_visible($course_record->id, 1, 0);

        $section_one = course_section::from_section_number($course_record->id, 1);
        self::assertEquals(0, $section_one->get_visible());
        self::assertEmpty($section_one->get_all_modules());
        self::assertEmpty($section_one->get_sequence());

        $section_zero = course_section::from_section_number($course_record->id, 0);
        self::assertEquals(1, $section_zero->get_visible());

        $all_modules = $section_zero->get_all_modules();
        self::assertCount(1, $all_modules);

        $module_one = reset($all_modules);
        self::assertEquals(1, $module_one->get_visible());
        self::assertEquals(1, $module_one->get_visible_old());

        // Move to section one, which is hidden.
        $module_one->move_to_section($section_one->get_section_number());
        $module_one->reload();

        self::assertEquals(0, $module_one->get_visible());
        self::assertEquals(1, $module_one->get_visible_old());

        $section_one->reload();
        $section_zero->reload();

        self::assertEmpty($section_zero->get_all_modules());
        self::assertEmpty($section_zero->get_sequence());

        self::assertCount(1, $section_one->get_all_modules());
        self::assertCount(1, $section_one->get_sequence());
        self::assertEquals([$module_one->get_id()], $section_one->get_sequence());
    }
}