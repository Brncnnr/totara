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
 * @category totara_notification
 */

use \container_course\course as course_container;
use core_completion\totara_notification\placeholder\course_completion as course_completion_placeholder_group;
use core_phpunit\testcase;
use totara_notification\placeholder\option;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_notification
 */
class core_completion_totara_notification_course_completion_placeholder_testcase extends testcase {
    /**
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        course_completion_placeholder_group::clear_instance_cache();
    }

    protected function tearDown(): void {
        parent::tearDown();
        course_completion_placeholder_group::clear_instance_cache();
    }

    public function test_course_completion_placeholders(): void {
        // Make devs aware they should extend this test when adding placeholders.
        $option_keys = array_map(static function (option $option) {
            return $option->get_key();
        }, course_completion_placeholder_group::get_options());
        self::assertEqualsCanonicalizing(
            ['completion_date', 'due_date'],
            $option_keys,
            'Please add missing placeholders to test coverage.'
        );

        $now = time();
        $date_in_future = strtotime('+5 weeks');

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $courses = [
            1 => self::getDataGenerator()->create_course([
                'fullname' => 'Test course 1',
                'enablecompletion' => COMPLETION_ENABLED
            ]),
            2 => self::getDataGenerator()->create_course([
                'fullname' => 'Test course 2',
                'enablecompletion' => COMPLETION_ENABLED,
                'duedate_op' => course_container::DUEDATEOPERATOR_FIXED,
                'duedate' => $date_in_future
            ]),
            3 => self::getDataGenerator()->create_course([
                'fullname' => 'Test course 3',
                'enablecompletion' => COMPLETION_ENABLED,
                'duedate_op' => course_container::DUEDATEOPERATOR_RELATIVE,
                'duedateoffsetunit' => course_container::DUEDATEOFFSETUNIT_MONTHS,
                'duedateoffsetamount' => 3,
            ]),
            4 => self::getDataGenerator()->create_course([
                'fullname' => 'Test course 4',
                'enablecompletion' => COMPLETION_ENABLED,
                'duedate_op' => course_container::DUEDATEOPERATOR_RELATIVE,
                'duedateoffsetunit' => course_container::DUEDATEOFFSETUNIT_MONTHS,
                'duedateoffsetamount' => 3,
            ]),
        ];

        /* @var \core_completion\testing\generator $completiongenerator */
        $completiongenerator = self::getDataGenerator()->get_plugin_generator('core_completion');

        for ($i = 1; $i <= 4; $i++) {
            self::getDataGenerator()->enrol_user($user1->id, $courses[$i]->id, null, $enrol = 'manual', $now - 100);
            self::getDataGenerator()->enrol_user($user2->id, $courses[$i]->id, null, $enrol = 'manual', $now);

            $completiongenerator->complete_course($courses[$i], $user1, $now);

            // For course4 we disable completion AFTER the user has been enrolled (to ensure that the course_completion records were previously created
            if ($i === 4) {
                $courses[$i]->enablecompletion = COMPLETION_DISABLED;
                $course4_container = course_container::from_record($courses[$i]);
                $course4_container->update($courses[$i]);
            }

            $placeholder_group1 = course_completion_placeholder_group::from_course_id_and_user_id($courses[$i]->id, $user1->id);
            $placeholder_group2 = course_completion_placeholder_group::from_course_id_and_user_id($courses[$i]->id, $user2->id);

            // Only user1 completed the courses
            self::assertEquals(userdate($now), $placeholder_group1->do_get('completion_date'));
            self::assertEmpty($placeholder_group2->do_get('completion_date'));

            // Due dates
            $date_time_format = get_string("strftimedatefulllong", "langconfig");

            switch ($i) {
                case 2:
                    $expected1 = $expected2 = userdate(
                        $date_in_future,
                        $date_time_format,
                        99, // Use current user's timezone which should be the notification recipient's one.
                        false
                    );
                    break;
                case 3:
                    $expected1 = userdate(
                        strtotime('+3 months', $now - 100),
                        $date_time_format,
                        99, // Use current user's timezone which should be the notification recipient's one.
                        false
                    );
                    $expected2 = userdate(
                        strtotime('+3 months', $now),
                        $date_time_format,
                        99, // Use current user's timezone which should be the notification recipient's one.
                        false
                    );
                    break;
                default:
                    $expected1 = $expected2 = '';
            }

            self::assertEquals($expected1, $placeholder_group1->do_get('due_date'));
            self::assertEquals($expected2, $placeholder_group2->do_get('due_date'));
        }
    }

    public function test_course_completion_placeholders_not_available(): void {
        $user1 = self::getDataGenerator()->create_user();
        $course = self::getDataGenerator()->create_course(['fullname' => 'A test course 1', 'enablecompletion' => 1]);
        self::getDataGenerator()->enrol_user($user1->id, $course->id);

        self::expectException(coding_exception::class);
        self::expectExceptionMessage("Invalid key 'whatever'");
        $placeholder_group = course_completion_placeholder_group::from_course_id_and_user_id($course->id, $user1->id);
        $placeholder_group->do_get('whatever');
    }

    public function test_course_completion_placeholders_invalid_course(): void {
        $user1 = self::getDataGenerator()->create_user();

        $placeholder_group = course_completion_placeholder_group::from_course_id_and_user_id(123, $user1->id);

        self::expectException(coding_exception::class);
        self::expectExceptionMessage('The course completion entity record is empty');
        $placeholder_group->do_get('tracking_start_date');
    }

    public function test_course_completion_placeholders_invalid_user(): void {
        $course1 = self::getDataGenerator()->create_course(['fullname' => 'Test course 1', 'enablecompletion' => 1]);

        $placeholder_group = course_completion_placeholder_group::from_course_id_and_user_id($course1->id, 123);

        self::expectException(coding_exception::class);
        self::expectExceptionMessage('The course completion entity record is empty');
        $placeholder_group->do_get('tracking_start_date');
    }

    public function test_course_completion_placeholders_not_enrolled(): void {
        $user1 = self::getDataGenerator()->create_user();
        $course1 = self::getDataGenerator()->create_course(['fullname' => 'Test course 1', 'enablecompletion' => 1]);

        $placeholder_group = course_completion_placeholder_group::from_course_id_and_user_id($course1->id, $user1->id);

        self::expectException(coding_exception::class);
        self::expectExceptionMessage('The course completion entity record is empty');
        $placeholder_group->do_get('tracking_start_date');
    }

    public function test_course_completion_placeholder_instances_are_cached(): void {
        global $DB;

        self::setAdminUser();
        $user1 = self::getDataGenerator()->create_user();
        $course1 = self::getDataGenerator()->create_course();
        $course2 = self::getDataGenerator()->create_course();

        self::getDataGenerator()->enrol_user($user1->id, $course1->id);
        self::getDataGenerator()->enrol_user($user1->id, $course2->id);

        $query_count = $DB->perf_get_reads();
        course_completion_placeholder_group::from_course_id_and_user_id($course1->id, $user1->id);
        self::assertEquals($query_count + 1, $DB->perf_get_reads());

        course_completion_placeholder_group::from_course_id_and_user_id($course1->id, $user1->id);
        self::assertEquals($query_count + 1, $DB->perf_get_reads());

        course_completion_placeholder_group::from_course_id_and_user_id($course2->id, $user1->id);
        self::assertEquals($query_count + 2, $DB->perf_get_reads());

        course_completion_placeholder_group::from_course_id_and_user_id($course1->id, $user1->id);
        course_completion_placeholder_group::from_course_id_and_user_id($course2->id, $user1->id);
        self::assertEquals($query_count + 2, $DB->perf_get_reads());
    }

}
