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
use totara_reportbuilder\rb\display\base;
use totara_reportbuilder\report_helper;

class container_course_report_builder_dp_course_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_fetch_course_only(): void {
        // Make sure the record of learning is up to date. For some
        // reason the record of learning table is not emptied reliably after tests
        (new update_record_of_learning_task())->execute();

        $generator = self::getDataGenerator();
        $user_one = $generator->create_user();

        $course_one = $generator->create_course();
        $generator->enrol_user($user_one->id, $course_one->id);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $this->setUser($user_one);
        $workspace = $workspace_generator->create_workspace();

        $report_id = report_helper::create('dp_course');
        $report = reportbuilder::create($report_id, new rb_config(), false);

        $ref_class = new ReflectionClass($report);

        $get_data_method = $ref_class->getMethod('get_data');
        $get_data_method->setAccessible(true);

        /** @var moodle_recordset $record_set */
        $record_set = $get_data_method->invoke($report);
        $records = $record_set->to_array();

        $record_set->close();

        // There should be only on course record returned from the report.
        self::assertCount(1, $records);

        // Check that if the workspace appear.
        $record = reset($records);

        // Get the course's id from the courselink column, as courselink field
        // is a default column option.
        $course_link = $report->columns['course-courselink'];
        $extra_data = base::get_extrafields_row($record, $course_link);

        self::assertNotEquals($workspace->get_id(), $extra_data->course_id);
        self::assertEquals($course_one->id, $extra_data->course_id);
    }
}