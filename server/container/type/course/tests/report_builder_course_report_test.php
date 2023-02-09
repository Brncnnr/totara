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

use totara_plan\task\update_record_of_learning_task;
use totara_reportbuilder\report_helper;

class container_course_report_builder_course_report_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_course_report_does_not_include_workspace_records(): void {
        // Make sure the record of learning is up to date. For some
        // reason the record of learning table is not emptied reliably after tests
        (new update_record_of_learning_task())->execute();

        $generator = $this->getDataGenerator();
        $this->setAdminUser();

        // Create 5 courses.
        for ($i = 0; $i < 5; $i++) {
            $generator->create_course();
        }

        // Create 1 workspaces.

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $report_id = report_helper::create('courses');
        $report = reportbuilder::create($report_id, new rb_config(), true);

        // Fetch the records from this very report.
        $reflection_class = new \ReflectionClass($report);

        $get_data_method = $reflection_class->getMethod('get_data');
        $get_data_method->setAccessible(true);

        /** @var moodle_recordset $recordset */
        $recordset = $get_data_method->invoke($report);
        $records = $recordset->to_array();

        self::assertCount(5, $records);

        foreach ($records as $record) {
            self::assertNotEquals($workspace->get_id(), $record->id);
            self::assertNotEquals(SITEID, $record->id);
        }
    }

    /**
     * @return void
     */
    public function test_course_report_does_not_include_other_container_records(): void {
        // Make sure the record of learning is up to date. For some
        // reason the record of learning table is not emptied reliably after tests
        (new update_record_of_learning_task())->execute();

        global $DB;
        $generator = $this->getDataGenerator();

        // Create 5 courses.
        for ($i = 0; $i < 5; $i++) {
            $generator->create_course();
        }

        // Create 3 different what-so-ever container.
        $non_courses = [];

        for ($i = 0; $i < 3; $i++) {
            $record = $generator->create_course();
            $record->containertype = 'something_else_wow';

            $DB->update_record('course', $record);
            $non_courses[] = $record->id;
        }

        $report_id = report_helper::create('courses');
        $report = reportbuilder::create($report_id, new rb_config(), false);

        // Fetch the records from this very report.
        $reflection_class = new \ReflectionClass($report);

        $get_data_method = $reflection_class->getMethod('get_data');
        $get_data_method->setAccessible(true);

        /** @var moodle_recordset $recordset */
        $recordset = $get_data_method->invoke($report);
        $records = $recordset->to_array();

        self::assertCount(5, $records);
        foreach ($records as $record) {
            self::assertNotEquals(SITEID, $record->id);
            self::assertFalse(in_array($record->id, $non_courses));
        }
    }
}