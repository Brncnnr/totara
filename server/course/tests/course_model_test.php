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
 * @package core_course
 */

use container_course\course as course_container;
use core_course\model\course as course_model;
use core_phpunit\testcase;

defined('MOODLE_INTERNAL') || die();

class course_model_test extends testcase {

    /**
     * @dataProvider user_due_date_provider
     * @param array $course_params
     * @param int $system_completion
     * @param int|string|null $expected
     */
    public function test_calculate_duedate_from_time(array $course_params, int $system_completion, $expected): void {
        global $CFG;

        $generator = self::getDataGenerator();

        // Create course
        $course = $generator->create_course($course_params);

        // Set global completion
        $CFG->enablecompletion = $system_completion ? COMPLETION_ENABLED : COMPLETION_DISABLED;

        // Now test
        $model = course_model::load_by_id($course->id);
        $now = time();

        if (is_string($expected)) {
            $expected = strtotime($expected, $now);
        }
        self::assertEquals($expected, $model->calculate_duedate_from_time($now));
    }

    public function user_due_date_provider(): array {
        $date_in_future = strtotime('+22 days');

        return [
            [
                'course' => [
                    'fullname' => 'Course with no completion tracking',
                    'enablecompletion' => 0,
                ],
                'system_completion' => 1,
                'expected' => null,
            ],
            [
                'course' => [
                    'fullname' => 'Course with no completion tracking and due date',
                    'enablecompletion' => 0,
                    'duedate_op' => course_container::DUEDATEOPERATOR_FIXED,
                    'duedate' => $date_in_future,
                ],
                'system_completion' => 1,
                'expected' => null,
            ],
            [
                'course' => [
                    'fullname' => 'Course with completion no due date',
                    'enablecompletion' => 1,
                ],
                'system_completion' => 1,
                'expected' => null,
            ],
            [
                'course' => [
                    'fullname' => 'Course with completion and fixed due date',
                    'enablecompletion' => 1,
                    'duedate_op' => course_container::DUEDATEOPERATOR_FIXED,
                    'duedate' => $date_in_future,
                ],
                'system_completion' => 1,
                'expected' => $date_in_future,
            ],
            [
                'course' => [
                    'fullname' => 'Course with completion and fixed due date',
                    'enablecompletion' => 1,
                    'duedate_op' => course_container::DUEDATEOPERATOR_FIXED,
                    'duedate' => $date_in_future,
                ],
                'system_completion' => 0,
                'expected' => $date_in_future,
            ],
            [
                'course' => [
                    'fullname' => 'Course due within 3 days',
                    'enablecompletion' => 1,
                    'duedate_op' => course_container::DUEDATEOPERATOR_RELATIVE,
                    'duedateoffsetamount' => 5,
                    'duedateoffsetunit' => course_container::DUEDATEOFFSETUNIT_DAYS,
                ],
                'system_completion' => 1,
                'expected' => '+5 days',
            ],
            [
                'course' => [
                    'fullname' => 'Course due within 2 weeks',
                    'enablecompletion' => 1,
                    'duedate_op' => course_container::DUEDATEOPERATOR_RELATIVE,
                    'duedateoffsetamount' => 3,
                    'duedateoffsetunit' => course_container::DUEDATEOFFSETUNIT_WEEKS,
                ],
                'system_completion' => 1,
                'expected' => '+3 weeks',
            ],
            [
                'course' => [
                    'fullname' => 'Course due within 2 weeks',
                    'enablecompletion' => 1,
                    'duedate_op' => course_container::DUEDATEOPERATOR_RELATIVE,
                    'duedateoffsetamount' => 1,
                    'duedateoffsetunit' => course_container::DUEDATEOFFSETUNIT_MONTHS,
                ],
                'system_completion' => 1,
                'expected' => '+1 month',
            ],
        ];
    }
}
