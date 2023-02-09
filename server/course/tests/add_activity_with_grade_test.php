<?php
/**
 * This file is part of Totara Core
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core_course
 */

use core\entity\adhoc_task;
use core\orm\query\builder;
use core_course\local\grade_helper;
use core_phpunit\testcase;

class core_course_add_activity_with_grade_testcase extends testcase {
    protected function setUp(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/gradelib.php");
        // Lower the limit before grades are recalculated asynchronously.
        set_config('course_regrade_grade_items_async', 10);
        set_config('course_regrade_enrolments_async', 10);
    }

    protected function tearDown(): void {
        set_config('course_regrade_grade_items_async', 100);
        set_config('course_regrade_enrolments_async', 100);
    }

    /**
     * @return void
     */
    public function test_add_assignment_module_should_create_adhoc_tasks(): void {
        $generator = self::getDataGenerator();
        $course = $generator->create_course();

        $grade_item = grade_item::fetch_course_item($course->id);
        $admin_user = get_admin();

        // Create thirteen users and enrol user into the course with grading.
        for ($i = 0; $i < 13; $i++) {
            $user = $generator->create_user(['username' => "user_{$i}"]);
            $generator->enrol_user($user->id, $course->id);

            $grade_grade = new grade_grade();
            $grade_grade->rawgrademax = $grade_item->grademax;
            $grade_grade->rawgrademin = $grade_item->grademin;
            $grade_grade->rawscaleid = $grade_item->scaleid;
            $grade_grade->itemid = $grade_item->id;
            $grade_grade->finalgrade = rand($grade_item->grademin, $grade_item->grademax);
            $grade_grade->userid = $user->id;
            $grade_grade->usermodified = $admin_user->id;

            $grade_grade->insert();
        }

        // Clear the adhoc tasks first.
        self::executeAdhocTasks();
        $db = builder::get_db();

        // 13 grade records so far, as these are manually created when users
        // are enrolled to the course.
        self::assertEquals(13, $db->count_records('grade_grades'));
        self::assertEquals(1, $db->count_records('grade_items', ['courseid' => $course->id]));
        self::assertEquals(0, $db->count_records(adhoc_task::TABLE));

        // Add module assign to the course.
        $generator->create_module('assign', ['course' => $course->id]);

        self::assertEquals(13, $db->count_records('grade_grades'));
        self::assertEquals(2, $db->count_records('grade_items', ['courseid' => $course->id, 'needsupdate' => 1]));

        // There should be one adhoc task queued.
        self::assertEquals(1, $db->count_records(adhoc_task::TABLE));
        self::assertEquals(0, $db->count_records('grade_items', ['courseid' => $course->id, 'needsupdate' => 0]));

        // Execute adhoc task. After the adhoc tasks are executed, then new record(s) for grade will be created
        // then the grade items should be upgrade.
        self::executeAdhocTasks();
        self::assertEquals(
            2,
            $db->count_records(
                'grade_items',
                [
                    'courseid' => $course->id,
                    'needsupdate' => 0
                ]
            )
        );

        self::assertEquals(
            0,
            $db->count_records(
                'grade_items',
                [
                    'courseid' => $course->id,
                    'needsupdate' => 1
                ]
            )
        );

        // 26 grade records now.
        self::assertEquals(26, $db->count_records('grade_grades'));
        self::assertEquals(0, $db->count_records(adhoc_task::TABLE));

        // Check that the field user modified id is not populated by the system.
        self::assertEquals(
            13,
            $db->count_records('grade_grades', ['usermodified' => null])
        );
    }

    /**
     * This test is to make sure that the adhoc task won't change even though it is being queued multiple times.
     * It is to make sure that the adhoc tasks won't violate the data integrity when it is being called multiple times
     * especially when cron is not yet running.
     *
     * @return void
     */
    public function test_add_two_assignment_module_should_not_cause_reaggregation_failure(): void {
        $generator = self::getDataGenerator();
        $course = $generator->create_course();

        $grade_item = grade_item::fetch_course_item($course->id);
        $admin_user = get_admin();

        // Create thirteen users and enrol user into the course with grading.
        for ($i = 0; $i < 13; $i++) {
            $user = $generator->create_user(['username' => "user_{$i}"]);
            $generator->enrol_user($user->id, $course->id);

            $grade_grade = new grade_grade();
            $grade_grade->rawgrademax = $grade_item->grademax;
            $grade_grade->rawgrademin = $grade_item->grademin;
            $grade_grade->rawscaleid = $grade_item->scaleid;
            $grade_grade->itemid = $grade_item->id;
            $grade_grade->finalgrade = rand($grade_item->grademin, $grade_item->grademax);
            $grade_grade->userid = $user->id;
            $grade_grade->usermodified = $admin_user->id;

            $grade_grade->insert();
        }

        // Clear the adhoc tasks first.
        self::executeAdhocTasks();
        $db = builder::get_db();

        // 13 grade records so far, as these are manually created when users
        // are enrolled to the course.
        self::assertEquals(13, $db->count_records('grade_grades'));
        self::assertEquals(1, $db->count_records('grade_items', ['courseid' => $course->id]));
        self::assertEquals(0, $db->count_records(adhoc_task::TABLE));

        // Add module assign to the course.
        $generator->create_module('assign', ['course' => $course->id, 'name' => 'Assignment 1']);
        $generator->create_module('assign', ['course' => $course->id, 'name' => 'Assignment 2']);

        self::assertEquals(13, $db->count_records('grade_grades'));

        // There should only be one adhoc task queued.
        self::assertEquals(1, $db->count_records(adhoc_task::TABLE));
        self::assertEquals(
            3,
            $db->count_records(
                'grade_items',
                [
                    'courseid' => $course->id,
                    'needsupdate' => 1
                ]
            )
        );

        self::assertEquals(
            0,
            $db->count_records(
                'grade_items',
                [
                    'courseid' => $course->id,
                    'needsupdate' => 0
                ]
            )
        );

        // Execute adhoc task. After the adhoc tasks are executed, then new record(s) for grade will be created
        // then the grade items should be upgrade.
        self::executeAdhocTasks();
        self::assertEquals(
            3,
            $db->count_records(
                'grade_items',
                [
                    'courseid' => $course->id,
                    'needsupdate' => 0
                ]
            )
        );

        self::assertEquals(
            0,
            $db->count_records(
                'grade_items',
                [
                    'courseid' => $course->id,
                    'needsupdate' => 1
                ]
            )
        );

        // 39 grade records now.
        self::assertEquals(39, $db->count_records('grade_grades'));
        self::assertEquals(0, $db->count_records(adhoc_task::TABLE));
    }
}