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

use core_course\totara_notification\placeholder\course as course_placeholder_group;
use core_phpunit\testcase;
use totara_notification\placeholder\option;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_notification
 */
class core_course_totara_notification_course_placeholder_testcase extends testcase {
    /**
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        course_placeholder_group::clear_instance_cache();
    }

    protected function tearDown(): void {
        parent::tearDown();
        course_placeholder_group::clear_instance_cache();
    }

    public function test_course_placeholders(): void {
        // Make devs aware they should extend this test when adding placeholders.
        $option_keys = array_map(static function (option $option) {
            return $option->get_key();
        }, course_placeholder_group::get_options());
        self::assertEqualsCanonicalizing(
            ['full_name', 'full_name_link'],
            $option_keys,
            'Please add missing placeholders to test coverage.'
        );

        $course = self::getDataGenerator()->create_course(['fullname' => 'A test course 1']);

        $placeholder_group = course_placeholder_group::from_id($course->id);
        self::assertEquals('A test course 1', $placeholder_group->do_get('full_name'));
        self::assertEquals(
            '<a href="https://www.example.com/moodle/course/view.php?id='
            . $course->id . '">A test course 1</a>',
            $placeholder_group->do_get('full_name_link')
        );
    }

    public function test_course_placeholders_not_available(): void {
        $course = self::getDataGenerator()->create_course(['fullname' => 'Another test course']);
        $placeholder_group = course_placeholder_group::from_id($course->id);

        self::expectException(coding_exception::class);
        self::expectExceptionMessage("Invalid key 'whatever'");
        $placeholder_group->do_get('whatever');
    }

    public function test_course_placeholders_invalid_course(): void {
        $placeholder_group = course_placeholder_group::from_id(123);
        self::expectException(coding_exception::class);
        self::expectExceptionMessage('The course entity record is empty');
        $placeholder_group->do_get('full_name');
    }

    public function test_course_placeholder_instances_are_cached(): void {
        global $DB;

        self::setAdminUser();
        $course1 = self::getDataGenerator()->create_course();
        $course2 = self::getDataGenerator()->create_course();

        $query_count = $DB->perf_get_reads();
        course_placeholder_group::from_id($course1->id);
        self::assertEquals($query_count + 1, $DB->perf_get_reads());

        course_placeholder_group::from_id($course1->id);
        self::assertEquals($query_count + 1, $DB->perf_get_reads());

        course_placeholder_group::from_id($course2->id);
        self::assertEquals($query_count + 2, $DB->perf_get_reads());

        course_placeholder_group::from_id($course1->id);
        course_placeholder_group::from_id($course2->id);
        self::assertEquals($query_count + 2, $DB->perf_get_reads());
    }

    public function test_course_customfield_placeholders(): void {
        $generator = static::getDataGenerator();
        $cf_generator = $generator->get_plugin_generator('totara_customfield');

        $text_ids = $cf_generator->create_text('course', ['text1', 'text2']);
        $mult_ids = $cf_generator->create_multiselect('course', ['multi1' => ['opt1', 'opt2']]);
        $textarea_ids = $cf_generator->create_textarea('course', ['textarea']);

        // Create course.
        $course1 = $generator->create_course(['fullname' => 'Course 1']);
        // Add customfield data to course 1.
        $cf_generator->set_text($course1, $text_ids['text1'], 'Course 1 text 1 value', 'course', 'course');
        $cf_generator->set_multiselect($course1, $mult_ids['multi1'], ['opt1', 'opt2'], 'course', 'course');
        $cf_generator->set_textarea($course1, $textarea_ids['textarea'], '<p>Course 1 textarea value</p>', 'course', 'course');

        $placeholder1_group = course_placeholder_group::from_id($course1->id);
        self::assertEquals('Course 1 text 1 value', $placeholder1_group->do_get('cf_text1'));
        self::assertEquals('', $placeholder1_group->do_get('cf_text2'));
        self::assertEquals('opt1, opt2', $placeholder1_group->do_get('cf_multi1'));
        self::assertEquals('Course 1 textarea value', $placeholder1_group->do_get('cf_textarea'));

        static::expectExceptionMessage("Invalid key 'cf_whatever'");
        $placeholder1_group->do_get('cf_whatever');
    }
}