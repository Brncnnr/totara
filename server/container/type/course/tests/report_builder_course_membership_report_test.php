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

use container_workspace\member\member;
use totara_plan\task\update_record_of_learning_task;
use totara_reportbuilder\report_helper;
use container_course\course;

class container_course_report_builder_course_membership_report_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_fetch_member_ship_that_does_not_include_workspace_member(): void {
        // Make sure the record of learning is up to date. For some
        // reason the record of learning table is not emptied reliably after tests
        (new update_record_of_learning_task())->execute();

        global $DB;
        $this->setAdminUser();
        $generator = $this->getDataGenerator();

        // Create a course.
        $course = $generator->create_course();

        // Create list of users which enrolled to this course.
        for ($i = 0; $i < 3; $i++) {
            $user = $generator->create_user();
            $generator->enrol_user($user->id, $course->id);
        }

        // Create a workspace and enrol one user to this workspace.
        $user_one = $generator->create_user();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Added user one to the workspace.
        member::added_to_workspace($workspace, $user_one->id, false);

        // Create a report.
        $report_id = report_helper::create('course_membership');
        $report = reportbuilder::create($report_id, new rb_config(), false);

        // Fetch the records from report.
        $reflection_class = new ReflectionClass($report);

        $get_data_method = $reflection_class->getMethod('get_data');
        $get_data_method->setAccessible(true);

        $recordset = $get_data_method->invoke($report);
        $records = $recordset->to_array();

        // Only 3 users are enrol to the proper course.
        self::assertCount(3, $records);
        $course_type = course::get_type();

        foreach ($records as $record) {
            [$user_id, $course_id] = explode(',', $record->id);

            // We just want to make sure that this is a valid in system.
            self::assertTrue(
                $DB->record_exists(
                    'course',
                    [
                        'id' => $course_id,
                        'containertype' => $course_type
                    ]
                )
            );

            self::assertTrue($DB->record_exists('user', ['id' => $user_id]));
            self::assertNotEquals($user_one->id, $user_id);
        }
    }
}