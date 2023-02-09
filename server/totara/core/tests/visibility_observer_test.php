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

use totara_core\task\visibility_map_regenerate_all;

/**
 * Test visibility event observers
 *
 * To test, run this from the command line from the $CFG->dirroot
 * vendor/bin/phpunit totara_core_visibility_observer_testcase
 *
 */
class totara_core_visibility_observer_testcase extends advanced_testcase {

    private const TABLE_COURSE = 'totara_core_course_vis_map';
    private const TABLE_PROGRAM = 'totara_core_program_vis_map';
    private const TABLE_CERTIFICATION = 'totara_core_certification_vis_map';

    public function test_react_category_deleted() {
        global $DB;

        $gen = self::getDataGenerator();
        /** @var \totara_program\testing\generator $gen_prog */
        $gen_prog = $gen->get_plugin_generator('totara_program');

        $roleid = $gen->create_role();

        foreach (\totara_core\local\visibility\map::view_hidden_capabilities() as $capability) {
            assign_capability($capability, CAP_ALLOW, $roleid, context_system::instance());
        }

        $category = $gen->create_category();
        $course1 = $gen->create_course(['category' => $category->id]);
        $course2 = $gen->create_course();
        $program1 = $gen_prog->create_program(['category' => $category->id]);
        $program2 = $gen_prog->create_program();
        $certification1 = $gen_prog->create_certification(['category' => $category->id]);
        $certification2 = $gen_prog->create_certification();

        array_map(function (\totara_core\local\visibility\map $map) use ($roleid) {
            $map->recalculate_map_for_role($roleid);
        }, \totara_core\local\visibility\map::all());

        self::assertTrue($DB->record_exists(self::TABLE_COURSE, ['roleid' => $roleid, 'courseid' => $course1->id]));
        self::assertTrue($DB->record_exists(self::TABLE_COURSE, ['roleid' => $roleid, 'courseid' => $course2->id]));
        self::assertTrue($DB->record_exists(self::TABLE_PROGRAM, ['roleid' => $roleid, 'programid' => $program1->id]));
        self::assertTrue($DB->record_exists(self::TABLE_PROGRAM, ['roleid' => $roleid, 'programid' => $program2->id]));
        self::assertTrue($DB->record_exists(self::TABLE_CERTIFICATION, ['roleid' => $roleid, 'programid' => $certification1->id]));
        self::assertTrue($DB->record_exists(self::TABLE_CERTIFICATION, ['roleid' => $roleid, 'programid' => $certification2->id]));

        self::assertFalse($DB->record_exists('task_adhoc', ['classname' => '\\' . visibility_map_regenerate_all::class]));

        $DB->set_field('task_scheduled', 'nextruntime', time() + 86400,
            ['classname' => '\\' . visibility_map_regenerate_all::class]);

        $cat = coursecat::get($category->id);
        $cat->delete_full();

        // Check it does not happen immediately.
        self::assertTrue($DB->record_exists(self::TABLE_COURSE, ['roleid' => $roleid, 'courseid' => $course1->id]));
        self::assertTrue($DB->record_exists(self::TABLE_COURSE, ['roleid' => $roleid, 'courseid' => $course2->id]));
        self::assertTrue($DB->record_exists(self::TABLE_PROGRAM, ['roleid' => $roleid, 'programid' => $program1->id]));
        self::assertTrue($DB->record_exists(self::TABLE_PROGRAM, ['roleid' => $roleid, 'programid' => $program2->id]));
        self::assertTrue($DB->record_exists(self::TABLE_CERTIFICATION, ['roleid' => $roleid, 'programid' => $certification1->id]));
        self::assertTrue($DB->record_exists(self::TABLE_CERTIFICATION, ['roleid' => $roleid, 'programid' => $certification2->id]));

        self::assertTrue($DB->record_exists('task_scheduled',
            ['classname' => '\\' . visibility_map_regenerate_all::class, 'nextruntime' => 0]));
        $this->execute_task(new visibility_map_regenerate_all());

        // Check that having run cron now everything is up to date.
        self::assertFalse($DB->record_exists(self::TABLE_COURSE, ['roleid' => $roleid, 'courseid' => $course1->id]));
        self::assertTrue($DB->record_exists(self::TABLE_COURSE, ['roleid' => $roleid, 'courseid' => $course2->id]));
        self::assertFalse($DB->record_exists(self::TABLE_PROGRAM, ['roleid' => $roleid, 'programid' => $program1->id]));
        self::assertTrue($DB->record_exists(self::TABLE_PROGRAM, ['roleid' => $roleid, 'programid' => $program2->id]));
        self::assertFalse($DB->record_exists(self::TABLE_CERTIFICATION, ['roleid' => $roleid, 'programid' => $certification1->id]));
        self::assertTrue($DB->record_exists(self::TABLE_CERTIFICATION, ['roleid' => $roleid, 'programid' => $certification2->id]));
    }

    public function test_category_moved_permission_maintained() {
        global $DB;

        $gen = self::getDataGenerator();
        /** @var \totara_program\testing\generator $gen_prog */
        $gen_prog = $gen->get_plugin_generator('totara_program');

        $roleid = $gen->create_role();
        foreach (\totara_core\local\visibility\map::view_hidden_capabilities() as $capability) {
            assign_capability($capability, CAP_ALLOW, $roleid, context_system::instance());
        }

        $category1 = $gen->create_category();
        $category2 = $gen->create_category();
        $course1 = $gen->create_course(['category' => $category1->id]);
        $course2 = $gen->create_course();
        $program1 = $gen_prog->create_program(['category' => $category1->id]);
        $program2 = $gen_prog->create_program();
        $certification1 = $gen_prog->create_certification(['category' => $category1->id]);
        $certification2 = $gen_prog->create_certification();

        array_map(function (\totara_core\local\visibility\map $map) use ($roleid) {
            $map->recalculate_map_for_role($roleid);
        }, \totara_core\local\visibility\map::all());

        self::assertTrue($DB->record_exists(self::TABLE_COURSE, ['roleid' => $roleid, 'courseid' => $course1->id]));
        self::assertTrue($DB->record_exists(self::TABLE_COURSE, ['roleid' => $roleid, 'courseid' => $course2->id]));
        self::assertTrue($DB->record_exists(self::TABLE_PROGRAM, ['roleid' => $roleid, 'programid' => $program1->id]));
        self::assertTrue($DB->record_exists(self::TABLE_PROGRAM, ['roleid' => $roleid, 'programid' => $program2->id]));
        self::assertTrue($DB->record_exists(self::TABLE_CERTIFICATION, ['roleid' => $roleid, 'programid' => $certification1->id]));
        self::assertTrue($DB->record_exists(self::TABLE_CERTIFICATION, ['roleid' => $roleid, 'programid' => $certification2->id]));

        self::assertFalse($DB->record_exists('task_adhoc', ['classname' => '\\' . visibility_map_regenerate_all::class]));

        $DB->set_field('task_scheduled', 'nextruntime', time() + 86400,
            ['classname' => '\\' . visibility_map_regenerate_all::class]);

        $cat = coursecat::get($category1->id);
        $cat->change_parent($category2);

        // Check it does not happen immediately.
        self::assertTrue($DB->record_exists(self::TABLE_COURSE, ['roleid' => $roleid, 'courseid' => $course1->id]));
        self::assertTrue($DB->record_exists(self::TABLE_COURSE, ['roleid' => $roleid, 'courseid' => $course2->id]));
        self::assertTrue($DB->record_exists(self::TABLE_PROGRAM, ['roleid' => $roleid, 'programid' => $program1->id]));
        self::assertTrue($DB->record_exists(self::TABLE_PROGRAM, ['roleid' => $roleid, 'programid' => $program2->id]));
        self::assertTrue($DB->record_exists(self::TABLE_CERTIFICATION, ['roleid' => $roleid, 'programid' => $certification1->id]));
        self::assertTrue($DB->record_exists(self::TABLE_CERTIFICATION, ['roleid' => $roleid, 'programid' => $certification2->id]));

        self::assertTrue($DB->record_exists('task_scheduled',
            ['classname' => '\\' . visibility_map_regenerate_all::class, 'nextruntime' => 0]));
        $this->execute_task(new visibility_map_regenerate_all());

        // Check that having run cron now everything is up to date.
        self::assertTrue($DB->record_exists(self::TABLE_COURSE, ['roleid' => $roleid, 'courseid' => $course1->id]));
        self::assertTrue($DB->record_exists(self::TABLE_COURSE, ['roleid' => $roleid, 'courseid' => $course2->id]));
        self::assertTrue($DB->record_exists(self::TABLE_PROGRAM, ['roleid' => $roleid, 'programid' => $program1->id]));
        self::assertTrue($DB->record_exists(self::TABLE_PROGRAM, ['roleid' => $roleid, 'programid' => $program2->id]));
        self::assertTrue($DB->record_exists(self::TABLE_CERTIFICATION, ['roleid' => $roleid, 'programid' => $certification1->id]));
        self::assertTrue($DB->record_exists(self::TABLE_CERTIFICATION, ['roleid' => $roleid, 'programid' => $certification2->id]));
    }

    public function test_category_moved_permission_gained() {
        global $DB;

        $gen = self::getDataGenerator();
        /** @var \totara_program\testing\generator $gen_prog */
        $gen_prog = $gen->get_plugin_generator('totara_program');

        $category1 = $gen->create_category();
        $category2 = $gen->create_category();
        $course1 = $gen->create_course(['category' => $category1->id]);
        $course2 = $gen->create_course();
        $program1 = $gen_prog->create_program(['category' => $category1->id]);
        $program2 = $gen_prog->create_program();
        $certification1 = $gen_prog->create_certification(['category' => $category1->id]);
        $certification2 = $gen_prog->create_certification();

        $roleid = $gen->create_role();
        $context = context_coursecat::instance($category2->id);
        foreach (\totara_core\local\visibility\map::view_hidden_capabilities() as $capability) {
            assign_capability($capability, CAP_ALLOW, $roleid, $context);
        }

        array_map(function (\totara_core\local\visibility\map $map) use ($roleid) {
            $map->recalculate_map_for_role($roleid);
        }, \totara_core\local\visibility\map::all());

        self::assertFalse($DB->record_exists(self::TABLE_COURSE, ['roleid' => $roleid, 'courseid' => $course1->id]));
        self::assertFalse($DB->record_exists(self::TABLE_COURSE, ['roleid' => $roleid, 'courseid' => $course2->id]));
        self::assertFalse($DB->record_exists(self::TABLE_PROGRAM, ['roleid' => $roleid, 'programid' => $program1->id]));
        self::assertFalse($DB->record_exists(self::TABLE_PROGRAM, ['roleid' => $roleid, 'programid' => $program2->id]));
        self::assertFalse($DB->record_exists(self::TABLE_CERTIFICATION, ['roleid' => $roleid, 'programid' => $certification1->id]));
        self::assertFalse($DB->record_exists(self::TABLE_CERTIFICATION, ['roleid' => $roleid, 'programid' => $certification2->id]));

        self::assertFalse($DB->record_exists('task_adhoc', ['classname' => '\\' . visibility_map_regenerate_all::class]));

        $DB->set_field('task_scheduled', 'nextruntime', time() + 86400,
            ['classname' => '\\' . visibility_map_regenerate_all::class]);

        $cat = coursecat::get($category1->id);
        $cat->change_parent($category2);

        // Check it does not happen immediately.
        self::assertFalse($DB->record_exists(self::TABLE_COURSE, ['roleid' => $roleid, 'courseid' => $course1->id]));
        self::assertFalse($DB->record_exists(self::TABLE_COURSE, ['roleid' => $roleid, 'courseid' => $course2->id]));
        self::assertFalse($DB->record_exists(self::TABLE_PROGRAM, ['roleid' => $roleid, 'programid' => $program1->id]));
        self::assertFalse($DB->record_exists(self::TABLE_PROGRAM, ['roleid' => $roleid, 'programid' => $program2->id]));
        self::assertFalse($DB->record_exists(self::TABLE_CERTIFICATION, ['roleid' => $roleid, 'programid' => $certification1->id]));
        self::assertFalse($DB->record_exists(self::TABLE_CERTIFICATION, ['roleid' => $roleid, 'programid' => $certification2->id]));

        self::assertTrue($DB->record_exists('task_scheduled',
            ['classname' => '\\' . visibility_map_regenerate_all::class, 'nextruntime' => 0]));
        $this->execute_task(new visibility_map_regenerate_all());

        // Check that having run cron now everything is up to date.
        self::assertTrue($DB->record_exists(self::TABLE_COURSE, ['roleid' => $roleid, 'courseid' => $course1->id]));
        self::assertFalse($DB->record_exists(self::TABLE_COURSE, ['roleid' => $roleid, 'courseid' => $course2->id]));
        self::assertTrue($DB->record_exists(self::TABLE_PROGRAM, ['roleid' => $roleid, 'programid' => $program1->id]));
        self::assertFalse($DB->record_exists(self::TABLE_PROGRAM, ['roleid' => $roleid, 'programid' => $program2->id]));
        self::assertTrue($DB->record_exists(self::TABLE_CERTIFICATION, ['roleid' => $roleid, 'programid' => $certification1->id]));
        self::assertFalse($DB->record_exists(self::TABLE_CERTIFICATION, ['roleid' => $roleid, 'programid' => $certification2->id]));
    }

    public function test_category_moved_permission_lost() {
        global $DB;

        $gen = self::getDataGenerator();
        /** @var \totara_program\testing\generator $gen_prog */
        $gen_prog = $gen->get_plugin_generator('totara_program');

        $category0 = $gen->create_category();
        $category1 = $gen->create_category(['parent' => $category0->id]);
        $category2 = $gen->create_category();
        $course1 = $gen->create_course(['category' => $category1->id]);
        $course2 = $gen->create_course();
        $program1 = $gen_prog->create_program(['category' => $category1->id]);
        $program2 = $gen_prog->create_program();
        $certification1 = $gen_prog->create_certification(['category' => $category1->id]);
        $certification2 = $gen_prog->create_certification();

        $roleid = $gen->create_role();
        $context = context_coursecat::instance($category0->id);
        foreach (\totara_core\local\visibility\map::view_hidden_capabilities() as $capability) {
            assign_capability($capability, CAP_ALLOW, $roleid, $context);
        }

        array_map(function (\totara_core\local\visibility\map $map) use ($roleid) {
            $map->recalculate_map_for_role($roleid);
        }, \totara_core\local\visibility\map::all());

        self::assertTrue($DB->record_exists(self::TABLE_COURSE, ['roleid' => $roleid, 'courseid' => $course1->id]));
        self::assertFalse($DB->record_exists(self::TABLE_COURSE, ['roleid' => $roleid, 'courseid' => $course2->id]));
        self::assertTrue($DB->record_exists(self::TABLE_PROGRAM, ['roleid' => $roleid, 'programid' => $program1->id]));
        self::assertFalse($DB->record_exists(self::TABLE_PROGRAM, ['roleid' => $roleid, 'programid' => $program2->id]));
        self::assertTrue($DB->record_exists(self::TABLE_CERTIFICATION, ['roleid' => $roleid, 'programid' => $certification1->id]));
        self::assertFalse($DB->record_exists(self::TABLE_CERTIFICATION, ['roleid' => $roleid, 'programid' => $certification2->id]));

        self::assertFalse($DB->record_exists('task_adhoc', ['classname' => '\\' . visibility_map_regenerate_all::class]));

        $DB->set_field('task_scheduled', 'nextruntime', time() + 86400,
            ['classname' => '\\' . visibility_map_regenerate_all::class]);

        $cat = coursecat::get($category1->id);
        $cat->change_parent($category2);

        // Check it does not happen immediately.
        self::assertTrue($DB->record_exists(self::TABLE_COURSE, ['roleid' => $roleid, 'courseid' => $course1->id]));
        self::assertFalse($DB->record_exists(self::TABLE_COURSE, ['roleid' => $roleid, 'courseid' => $course2->id]));
        self::assertTrue($DB->record_exists(self::TABLE_PROGRAM, ['roleid' => $roleid, 'programid' => $program1->id]));
        self::assertFalse($DB->record_exists(self::TABLE_PROGRAM, ['roleid' => $roleid, 'programid' => $program2->id]));
        self::assertTrue($DB->record_exists(self::TABLE_CERTIFICATION, ['roleid' => $roleid, 'programid' => $certification1->id]));
        self::assertFalse($DB->record_exists(self::TABLE_CERTIFICATION, ['roleid' => $roleid, 'programid' => $certification2->id]));

        self::assertTrue($DB->record_exists('task_scheduled',
            ['classname' => '\\' . visibility_map_regenerate_all::class, 'nextruntime' => 0]));
        $this->execute_task(new visibility_map_regenerate_all());

        // Check that having run cron now everything is up to date.
        self::assertFalse($DB->record_exists(self::TABLE_COURSE, ['roleid' => $roleid, 'courseid' => $course1->id]));
        self::assertFalse($DB->record_exists(self::TABLE_COURSE, ['roleid' => $roleid, 'courseid' => $course2->id]));
        self::assertFalse($DB->record_exists(self::TABLE_PROGRAM, ['roleid' => $roleid, 'programid' => $program1->id]));
        self::assertFalse($DB->record_exists(self::TABLE_PROGRAM, ['roleid' => $roleid, 'programid' => $program2->id]));
        self::assertFalse($DB->record_exists(self::TABLE_CERTIFICATION, ['roleid' => $roleid, 'programid' => $certification1->id]));
        self::assertFalse($DB->record_exists(self::TABLE_CERTIFICATION, ['roleid' => $roleid, 'programid' => $certification2->id]));
    }

    public function test_react_course_created() {
        global $DB;

        $gen = self::getDataGenerator();
        $roleid = $gen->create_role();
        $context = context_system::instance();
        foreach (\totara_core\local\visibility\map::view_hidden_capabilities() as $capability) {
            assign_capability($capability, CAP_ALLOW, $roleid, $context);
        }

        $course1 = $gen->create_course();
        $course2 = $gen->create_course();
        // Make sure its not instance.
        self::assertFalse($DB->record_exists(self::TABLE_COURSE, ['roleid' => $roleid, 'courseid' => $course1->id]));
        self::assertFalse($DB->record_exists(self::TABLE_COURSE, ['roleid' => $roleid, 'courseid' => $course2->id]));

        // Run things.
        ob_start();
        self::execute_adhoc_tasks();
        ob_end_clean();

        // Check both are up to date.
        self::assertTrue($DB->record_exists(self::TABLE_COURSE, ['roleid' => $roleid, 'courseid' => $course1->id]));
        self::assertTrue($DB->record_exists(self::TABLE_COURSE, ['roleid' => $roleid, 'courseid' => $course2->id]));
    }

    public function test_react_course_updated() {
        global $DB;

        $gen = self::getDataGenerator();
        $roleid = $gen->create_role();
        $context = context_system::instance();
        foreach (\totara_core\local\visibility\map::view_hidden_capabilities() as $capability) {
            assign_capability($capability, CAP_ALLOW, $roleid, $context);
        }

        $course1 = $gen->create_course();
        // Run things.
        ob_start();
        self::execute_adhoc_tasks();
        ob_end_clean();

        // Check both are up to date.
        self::assertTrue($DB->record_exists(self::TABLE_COURSE, ['roleid' => $roleid, 'courseid' => $course1->id]));
        $DB->delete_records(self::TABLE_COURSE, ['roleid' => $roleid]);
        self::assertFalse($DB->record_exists(self::TABLE_COURSE, ['roleid' => $roleid, 'courseid' => $course1->id]));

        $course1 = $DB->get_record('course', ['id' => $course1->id], '*', MUST_EXIST);
        $course1->fullname = 'test';
        $course1->visibility = 0;
        update_course($course1);

        // Run things.
        ob_start();
        self::execute_adhoc_tasks();
        ob_end_clean();

        // Check both are up to date.
        self::assertTrue($DB->record_exists(self::TABLE_COURSE, ['roleid' => $roleid, 'courseid' => $course1->id]));
    }

    public function test_react_course_deleted() {
        global $DB;

        $gen = self::getDataGenerator();
        $roleid = $gen->create_role();
        $context = context_system::instance();
        foreach (\totara_core\local\visibility\map::view_hidden_capabilities() as $capability) {
            assign_capability($capability, CAP_ALLOW, $roleid, $context);
        }

        $course1 = $gen->create_course();
        // Run things.
        ob_start();
        self::execute_adhoc_tasks();
        ob_end_clean();

        // Check both are up to date.
        self::assertTrue($DB->record_exists(self::TABLE_COURSE, ['roleid' => $roleid, 'courseid' => $course1->id]));

        delete_course($course1, false);

        // Run things.
        ob_start();
        self::execute_adhoc_tasks();
        ob_end_clean();

        // Check both are up to date.
        self::assertFalse($DB->record_exists(self::TABLE_COURSE, ['roleid' => $roleid, 'courseid' => $course1->id]));
    }

    public function test_react_course_restored() {
        global $DB;

        $gen = self::getDataGenerator();
        $course1 = $gen->create_course();
        $roleid = $gen->create_role();
        $context = context_course::instance($course1->id);
        foreach (\totara_core\local\visibility\map::view_hidden_capabilities() as $capability) {
            assign_capability($capability, CAP_ALLOW, $roleid, $context);
        }

        // Run things.
        ob_start();
        self::execute_adhoc_tasks();
        ob_end_clean();

        self::assertTrue($DB->record_exists(self::TABLE_COURSE, ['roleid' => $roleid, 'courseid' => $course1->id]));

        self::setAdminUser();
        $course2id = $this->backup_and_restore($course1);

        self::assertTrue($DB->record_exists(self::TABLE_COURSE, ['roleid' => $roleid, 'courseid' => $course1->id]));
        self::assertFalse($DB->record_exists(self::TABLE_COURSE, ['roleid' => $roleid, 'courseid' => $course2id]));

        // Run things.
        ob_start();
        self::execute_adhoc_tasks();
        ob_end_clean();

        self::assertTrue($DB->record_exists(self::TABLE_COURSE, ['roleid' => $roleid, 'courseid' => $course1->id]));
        self::assertTrue($DB->record_exists(self::TABLE_COURSE, ['roleid' => $roleid, 'courseid' => $course2id]));
    }

    public function test_react_program_created() {
        global $DB;

        $gen = self::getDataGenerator();
        $roleid = $gen->create_role();
        $context = context_system::instance();
        foreach (\totara_core\local\visibility\map::view_hidden_capabilities() as $capability) {
            assign_capability($capability, CAP_ALLOW, $roleid, $context);
        }

        $program1 = $gen->get_plugin_generator('totara_program')->create_program();
        $program2 = $gen->get_plugin_generator('totara_program')->create_program();
        // Make sure its not instance.
        self::assertFalse($DB->record_exists(self::TABLE_PROGRAM, ['roleid' => $roleid, 'programid' => $program1->id]));
        self::assertFalse($DB->record_exists(self::TABLE_PROGRAM, ['roleid' => $roleid, 'programid' => $program2->id]));

        // Run things.
        ob_start();
        self::execute_adhoc_tasks();
        ob_end_clean();

        // Check both are up to date.
        self::assertTrue($DB->record_exists(self::TABLE_PROGRAM, ['roleid' => $roleid, 'programid' => $program1->id]));
        self::assertTrue($DB->record_exists(self::TABLE_PROGRAM, ['roleid' => $roleid, 'programid' => $program2->id]));
    }

    public function test_react_program_updated() {
        global $DB;

        $gen = self::getDataGenerator();
        $roleid = $gen->create_role();
        $context = context_system::instance();
        foreach (\totara_core\local\visibility\map::view_hidden_capabilities() as $capability) {
            assign_capability($capability, CAP_ALLOW, $roleid, $context);
        }

        $program1 = $gen->get_plugin_generator('totara_program')->create_program();
        // Run things.
        ob_start();
        self::execute_adhoc_tasks();
        ob_end_clean();

        // Check both are up to date.
        self::assertTrue($DB->record_exists(self::TABLE_PROGRAM, ['roleid' => $roleid, 'programid' => $program1->id]));
        $DB->delete_records(self::TABLE_PROGRAM, ['roleid' => $roleid]);
        self::assertFalse($DB->record_exists(self::TABLE_PROGRAM, ['roleid' => $roleid, 'programid' => $program1->id]));

        // This bit of API is not encapsulated.
        $other = array('certifid' => empty($program1->certifid) ? 0 : $program1->certifid);
        $dataevent = array('id' => $program1->id, 'other' => $other);
        \totara_program\event\program_updated::create_from_data($dataevent)->trigger();

        self::assertFalse($DB->record_exists(self::TABLE_PROGRAM, ['roleid' => $roleid, 'programid' => $program1->id]));

        // Run things.
        ob_start();
        self::execute_adhoc_tasks();
        ob_end_clean();

        // Check both are up to date.
        self::assertTrue($DB->record_exists(self::TABLE_PROGRAM, ['roleid' => $roleid, 'programid' => $program1->id]));
    }

    public function test_react_certification_updated() {
        global $DB;

        $gen = self::getDataGenerator();
        $roleid = $gen->create_role();
        $context = context_system::instance();
        foreach (\totara_core\local\visibility\map::view_hidden_capabilities() as $capability) {
            assign_capability($capability, CAP_ALLOW, $roleid, $context);
        }

        $certificationid = $gen->get_plugin_generator('totara_program')->create_certification();
        // Run things.
        ob_start();
        self::execute_adhoc_tasks();
        ob_end_clean();

        // Check both are up to date.
        self::assertTrue($DB->record_exists(self::TABLE_CERTIFICATION, ['roleid' => $roleid, 'programid' => $certificationid->id]));
        $DB->delete_records(self::TABLE_CERTIFICATION, ['roleid' => $roleid]);
        self::assertFalse($DB->record_exists(self::TABLE_CERTIFICATION, ['roleid' => $roleid, 'programid' => $certificationid->id]));

        // This bit of API is not encapsulated.
        \totara_certification\event\certification_updated::create_from_instance(new program($certificationid->id))->trigger();

        self::assertFalse($DB->record_exists(self::TABLE_CERTIFICATION, ['roleid' => $roleid, 'programid' => $certificationid->id]));

        // Run things.
        ob_start();
        self::execute_adhoc_tasks();
        ob_end_clean();

        // Check both are up to date.
        self::assertTrue($DB->record_exists(self::TABLE_CERTIFICATION, ['roleid' => $roleid, 'programid' => $certificationid->id]));
    }

    public function test_react_program_deleted() {
        global $DB;

        $gen = self::getDataGenerator();
        $roleid = $gen->create_role();
        $context = context_system::instance();
        foreach (\totara_core\local\visibility\map::view_hidden_capabilities() as $capability) {
            assign_capability($capability, CAP_ALLOW, $roleid, $context);
        }

        $program1 = $gen->get_plugin_generator('totara_program')->create_program();
        // Run things.
        ob_start();
        self::execute_adhoc_tasks();
        ob_end_clean();

        // Check both are up to date.
        self::assertTrue($DB->record_exists(self::TABLE_PROGRAM, ['roleid' => $roleid, 'programid' => $program1->id]));

        $prog = new program($program1);
        $prog->delete();

        // Run things.
        ob_start();
        self::execute_adhoc_tasks();
        ob_end_clean();

        // Check both are up to date.
        self::assertFalse($DB->record_exists(self::TABLE_PROGRAM, ['roleid' => $roleid, 'programid' => $program1->id]));
    }

    private static function backup_and_restore(\stdClass $course) {
        global $USER, $CFG;

        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

        // Turn off file logging, otherwise it can't delete the file (Windows).
        $CFG->backup_file_logger_level = backup::LOG_NONE;

        // Do backup with default settings. MODE_IMPORT means it will just
        // create the directory and not zip it.
        $bc = new backup_controller(backup::TYPE_1COURSE, $course->id,
            backup::FORMAT_MOODLE, backup::INTERACTIVE_NO, backup::MODE_IMPORT,
            $USER->id);
        $backupid = $bc->get_backupid();
        $bc->execute_plan();
        $bc->destroy();

        // Do restore to new course with default settings.
        $newcourseid = restore_dbops::create_new_course(
            $course->fullname, $course->shortname . '_2', $course->category);
        $rc = new restore_controller($backupid, $newcourseid,
            backup::INTERACTIVE_NO, backup::MODE_GENERAL, $USER->id,
            backup::TARGET_NEW_COURSE);
        self::assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();

        return $newcourseid;
    }

    private static function execute_task(core\task\task_base $task) {
        ob_start();
        $task->execute();
        ob_end_clean();
    }

    public function test_task_names() {
        $task = new visibility_map_regenerate_all();
        $task->get_name();

        $task = new \totara_core\task\visibility_map_regenerate_certification();
        $task->get_name();

        $task = new \totara_core\task\visibility_map_regenerate_course();
        $task->get_name();

        $task = new \totara_core\task\visibility_map_regenerate_program();
        $task->get_name();
    }
}