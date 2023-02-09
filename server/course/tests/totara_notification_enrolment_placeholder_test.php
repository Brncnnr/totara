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
 * @category totara_notification
 */

use core_course\totara_notification\placeholder\enrolment as enrolment_placeholder_group;
use core_phpunit\testcase;
use totara_notification\placeholder\option;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_notification
 */
class core_course_totara_notification_enrolment_placeholder_testcase extends testcase {
    /**
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        enrolment_placeholder_group::clear_instance_cache();
    }

    protected function tearDown(): void {
        parent::tearDown();
        enrolment_placeholder_group::clear_instance_cache();
    }

    public function test_course_enrolment_placeholders(): void {
        // Make devs aware they should extend this test when adding placeholders.
        $option_keys = array_map(static function (option $option) {
            return $option->get_key();
        }, enrolment_placeholder_group::get_options());
        self::assertEqualsCanonicalizing(
            ['enrolment_date'],
            $option_keys,
            'Please add missing placeholders to test coverage.'
        );

        $time_5 = strtotime('-5 days');
        $time_5_string = userdate($time_5);
        $now = time();
        $now_string = userdate($now);

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $course1 = self::getDataGenerator()->create_course();
        $course2 = self::getDataGenerator()->create_course();

        self::getDataGenerator()->enrol_user($user1->id, $course1->id, null, $enrol = 'manual', $now);
        self::getDataGenerator()->enrol_user($user1->id, $course1->id, null, $enrol = 'self', $time_5);
        self::getDataGenerator()->enrol_user($user1->id, $course2->id, null, $enrol = 'manual', $now);
        self::getDataGenerator()->enrol_user($user2->id, $course2->id,null, $enrol = 'self', $time_5);

        $placeholder_group = enrolment_placeholder_group::from_course_id_and_user_id($course1->id, $user1->id);
        self::assertEquals($time_5_string, $placeholder_group->do_get('enrolment_date'));

        $placeholder_group = enrolment_placeholder_group::from_course_id_and_user_id($course2->id, $user1->id);
        self::assertEquals($now_string, $placeholder_group->do_get('enrolment_date'));

        $placeholder_group = enrolment_placeholder_group::from_course_id_and_user_id($course2->id, $user2->id);
        self::assertEquals($time_5_string, $placeholder_group->do_get('enrolment_date'));
    }

    public function test_course_enrolment_placeholders_not_available(): void {
        $user1 = self::getDataGenerator()->create_user();
        $course = self::getDataGenerator()->create_course();
        self::getDataGenerator()->enrol_user($user1->id, $course->id);

        self::expectException(coding_exception::class);
        self::expectExceptionMessage("Invalid key 'whatever'");
        $placeholder_group = enrolment_placeholder_group::from_course_id_and_user_id($course->id, $user1->id);
        $placeholder_group->do_get('whatever');
    }

    public function test_course_enrolment_placeholders_invalid_course(): void {
        $user1 = self::getDataGenerator()->create_user();

        $placeholder_group = enrolment_placeholder_group::from_course_id_and_user_id(123, $user1->id);

        self::expectException(coding_exception::class);
        self::expectExceptionMessage('The course enrolment record is empty');
        $placeholder_group->do_get('enrolment_date');
    }

    public function test_course_enrolment_placeholders_invalid_user(): void {
        $course1 = self::getDataGenerator()->create_course(['fullname' => 'Test course 1', 'enablecompletion' => 1]);

        $placeholder_group = enrolment_placeholder_group::from_course_id_and_user_id($course1->id, 123);

        self::expectException(coding_exception::class);
        self::expectExceptionMessage('The course enrolment record is empty');
        $placeholder_group->do_get('enrolment_date');
    }

    public function test_course_enrolment_placeholders_not_enrolled(): void {
        $user1 = self::getDataGenerator()->create_user();
        $course1 = self::getDataGenerator()->create_course();

        $placeholder_group = enrolment_placeholder_group::from_course_id_and_user_id($course1->id, $user1->id);

        self::expectException(coding_exception::class);
        self::expectExceptionMessage('The course enrolment record is empty');
        $placeholder_group->do_get('enrolment_date');
    }

    public function test_course_enrolment_placeholder_instances_are_cached(): void {
        global $DB;

        self::setAdminUser();
        $user1 = self::getDataGenerator()->create_user();
        $course1 = self::getDataGenerator()->create_course();
        $course2 = self::getDataGenerator()->create_course();

        self::getDataGenerator()->enrol_user($user1->id, $course1->id);
        self::getDataGenerator()->enrol_user($user1->id, $course2->id);

        $query_count = $DB->perf_get_reads();
        enrolment_placeholder_group::from_course_id_and_user_id($course1->id, $user1->id);
        self::assertEquals($query_count + 1, $DB->perf_get_reads());

        enrolment_placeholder_group::from_course_id_and_user_id($course1->id, $user1->id);
        self::assertEquals($query_count + 1, $DB->perf_get_reads());

        enrolment_placeholder_group::from_course_id_and_user_id($course2->id, $user1->id);
        self::assertEquals($query_count + 2, $DB->perf_get_reads());

        enrolment_placeholder_group::from_course_id_and_user_id($course1->id, $user1->id);
        enrolment_placeholder_group::from_course_id_and_user_id($course2->id, $user1->id);
        self::assertEquals($query_count + 2, $DB->perf_get_reads());
    }

}
