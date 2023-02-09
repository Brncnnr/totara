<?php
/**
 * This file is part of Totara Learn
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package core_completion
 */

use container_course\course as course_container;
use core\entity\adhoc_task;
use core_completion\task\update_completion_due_dates_task;
use core_phpunit\testcase;

defined('MOODLE_INTERNAL') || die();

class core_completion_update_completion_due_dates_task_testcase extends testcase {

    public function test_task(): void {
        global $DB;

        $generator = self::getDataGenerator();

        $now = time();
        $day_ago = strtotime('-1 days');
        $days3_ago = strtotime('-3 days');
        $days5_ago = strtotime('-5 days');
        $day_in_future = strtotime('+6 weeks');

        // Create a base user.
        $user1 = $generator->create_user(['lastname' => 'User1 last name']);
        $user2 = $generator->create_user(['lastname' => 'User2 last name']);
        $user3 = $generator->create_user(['lastname' => 'User3 last name']);
        $user4 = $generator->create_user(['lastname' => 'User4 last name']);

        // Create courses.
        $course1 = $generator->create_course([
            'fullname' => 'Test one',
            'enablecompletion' => COMPLETION_ENABLED,
        ]);

        // Directly create course_completion records with specific status and due date values
        $to_insert = [
            [
                'course' => $course1->id,
                'userid' => $user1->id,
                'timeenrolled' => $now,
                'timestarted' => $now,
                'reaggregate' => 0,
                'status' => COMPLETION_STATUS_NOTYETSTARTED,
            ],
            [
                'course' => $course1->id,
                'userid' => $user2->id,
                'timeenrolled' => $day_ago,
                'timestarted' => $now,
                'reaggregate' => 0,
                'status' => COMPLETION_STATUS_INPROGRESS,
            ],
            [
                'course' => $course1->id,
                'userid' => $user3->id,
                'timeenrolled' => $days3_ago,
                'timestarted' => $now,
                'reaggregate' => 0,
                'status' => COMPLETION_STATUS_COMPLETE,
            ],
            [
                'course' => $course1->id,
                'userid' => $user4->id,
                'timeenrolled' => $days5_ago,
                'timestarted' => $now,
                'reaggregate' => 0,
                'status' => COMPLETION_STATUS_COMPLETEVIARPL,
            ],
        ];

        $DB->insert_records('course_completions', $to_insert);

        self::verify_due_dates([
            [
                'course' => $course1->id,
                'userid' => $user1->id,
                'duedate' => null,
            ],
            [
                'course' => $course1->id,
                'userid' => $user2->id,
                'duedate' => null,
            ],
            [
                'course' => $course1->id,
                'userid' => $user3->id,
                'duedate' => null,
            ],
            [
                'course' => $course1->id,
                'userid' => $user4->id,
                'duedate' => null,
            ],
        ]);

        // Now update the course's due date settings, verify the ad-hoc task is triggered
        // Execute the task and verify results
        $course = course_container::from_record($course1);

        $new_data = new stdClass();
        $new_data->duedate_op = course_container::DUEDATEOPERATOR_FIXED;
        $new_data->duedate = $day_in_future;
        self::update_and_run_task($course, $new_data, [
            [
                'course' => $course1->id,
                'userid' => $user1->id,
                'duedate' => $day_in_future,
            ],
            [
                'course' => $course1->id,
                'userid' => $user2->id,
                'duedate' => $day_in_future,
            ],
            [
                'course' => $course1->id,
                'userid' => $user3->id,
                'duedate' => $day_in_future,
            ],
            [
                'course' => $course1->id,
                'userid' => $user4->id,
                'duedate' => $day_in_future,
            ],
        ]);

        $new_data = new stdClass();
        $new_data->duedate_op = course_container::DUEDATEOPERATOR_RELATIVE;
        $new_data->duedateoffsetunit = course_container::DUEDATEOFFSETUNIT_MONTHS;
        $new_data->duedateoffsetamount = 1;
        self::update_and_run_task($course, $new_data, [
            [
                'course' => $course1->id,
                'userid' => $user1->id,
                'duedate' => strtotime('+1 month', $now),
            ],
            [
                'course' => $course1->id,
                'userid' => $user2->id,
                'duedate' => strtotime('+1 month', $day_ago),
            ],
            [
                'course' => $course1->id,
                'userid' => $user3->id,
                'duedate' => strtotime('+1 month', $days3_ago),
            ],
            [
                'course' => $course1->id,
                'userid' => $user4->id,
                'duedate' => strtotime('+1 month', $days5_ago),
            ],
        ]);

        $new_data = new stdClass();
        $new_data->duedate_op = course_container::DUEDATEOPERATOR_NONE;
        self::update_and_run_task($course, $new_data, [
            [
                'course' => $course1->id,
                'userid' => $user1->id,
                'duedate' => null,
            ],
            [
                'course' => $course1->id,
                'userid' => $user2->id,
                'duedate' => null,
            ],
            [
                'course' => $course1->id,
                'userid' => $user3->id,
                'duedate' => null,
            ],
            [
                'course' => $course1->id,
                'userid' => $user4->id,
                'duedate' => null,
            ],
        ]);
    }

    private static function verify_due_dates(array $expected): void {
        global $DB;

        $actual = $DB->get_records('course_completions');
        self::assertSame(count($expected), count($actual));

        foreach ($expected as $idx => $tst_expected) {
            foreach ($actual as $tst_actual) {
                if ($tst_expected['course'] == $tst_actual->course
                    && $tst_expected['userid'] == $tst_actual->userid
                    && $tst_expected['duedate'] == $tst_actual->duedate) {
                    unset($expected[$idx]);
                    break;
                }
            }
        }

        self::assertEmpty($expected);
    }

    private static function update_and_run_task(course_container $course, stdClass $new_data, array $expected): void {
        global $DB;

        $task_class_name = '\\' . update_completion_due_dates_task::class;

        $course->update($new_data);
        self::assertEquals(1, $DB->count_records(adhoc_task::TABLE, ["classname" => $task_class_name]));

        self::executeAdhocTasks();
        self::verify_due_dates($expected);
    }
}
