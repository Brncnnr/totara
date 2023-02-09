<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @package totara_notification
 */

use core_phpunit\testcase;
use totara_notification\placeholder\customfield_options;

class totara_notification_placeholder_customfield_options_testcase extends testcase {

    public function test_get_options(): void {
        $generator = static::getDataGenerator();
        $cf_generator = $generator->get_plugin_generator('totara_customfield');

        $cf_generator->create_text('course', ['text 1', 'text2']);
        $cf_generator->create_multiselect('course', ['multi' => ['opt1', 'opt2']]);
        $cf_generator->create_text('facetoface_session', ['session_text', 'test']);
        $cf_generator->create_text('facetoface_room', ['test']);

        $options = customfield_options::get_options('course', 'course', 'Course');
        $expected = [
            ['key' => 'cf_course_text1', 'label' => 'Course text 1'],
            ['key' => 'cf_course_text2', 'label' => 'Course text2'],
            ['key' => 'cf_course_multi', 'label' => 'Course multi'],
        ];
        static::verify_options($expected, $options);

        $options = customfield_options::get_options('facetoface_session', 'session');
        $expected = [
            ['key' => 'cf_session_session_text', 'label' => 'session_text'],
            ['key' => 'cf_session_test', 'label' => 'test'],
        ];
        static::verify_options($expected, $options);

        // Rooms have default customfields that are created automatically
        $options = customfield_options::get_options('facetoface_room');
        $expected = [
            ['key' => 'cf_building', 'label' => 'Building'],
            ['key' => 'cf_location', 'label' => 'Location'],
            ['key' => 'cf_test', 'label' => 'test'],
        ];
        static::verify_options($expected, $options);
    }

    public function test_get_key_field_map(): void {
        $generator = static::getDataGenerator();
        $cf_generator = $generator->get_plugin_generator('totara_customfield');

        $cf_generator->create_text('course', ['text 1', 'text2']);
        $cf_generator->create_multiselect('course', ['multi' => ['opt1', 'opt2']]);

        $field_map = customfield_options::get_key_field_map('course');
        $expected = [
            'cf_text1' => 'text1',
            'cf_text2' => 'text2',
            'cf_multi' => 'multi',
        ];
        static::verify_key_field_map($expected, $field_map);

        $field_map = customfield_options::get_key_field_map('course', 'course');
        $expected = [
            'cf_course_text1' => 'text1',
            'cf_course_text2' => 'text2',
            'cf_course_multi' => 'multi',
        ];
        static::verify_key_field_map($expected, $field_map);
    }

    /**
     * @return void
     */
    public function test_get_all_values(): void {
        $generator = static::getDataGenerator();
        $cf_generator = $generator->get_plugin_generator('totara_customfield');

        $text_ids = $cf_generator->create_text('course', ['text1', 'text2']);
        $multi_ids = $cf_generator->create_multiselect('course', ['multi' => ['opt1', 'opt2']]);

        // Create course 1.
        $course1 = $generator->create_course(['fullname' => 'Course 1']);
        // Add customfields data to course 1.
        $cf_generator->set_text($course1, $text_ids['text1'], 'Course 1 text 1 value', 'course', 'course');
        $cf_generator->set_multiselect($course1, $multi_ids['multi'], ['opt1', 'opt2'], 'course', 'course');

        // Create course 2.
        $course2 = $generator->create_course(['fullname' => 'Course 2']);
        // Add customfields data to course 2.
        $cf_generator->set_text($course2, $text_ids['text1'], 'Course 2 text 1 value', 'course', 'course');
        $cf_generator->set_text($course2, $text_ids['text2'], 'Course 2 text 2 value', 'course', 'course');
        $cf_generator->set_multiselect($course2, $multi_ids['multi'], ['opt2'], 'course', 'course');

        $values = customfield_options::get_all_values($course1->id, 'course', 'course');
        $expected = [
            'cf_text1' => 'Course 1 text 1 value',
            'cf_text2' => '',
            'cf_multi' => 'opt1, opt2',
        ];
        static::verify_values($expected, $values);

        $values = customfield_options::get_all_values($course2->id, 'course', 'course', 'xyz');
        $expected = [
            'cf_xyz_text1' => 'Course 2 text 1 value',
            'cf_xyz_text2' => 'Course 2 text 2 value',
            'cf_xyz_multi' => 'opt2',
        ];
        static::verify_values($expected, $values);
    }

    /**
     * @return void
     */
    public function test_get_field_value(): void {
        $this->setAdminUser();

        $generator = static::getDataGenerator();
        $cf_generator = $generator->get_plugin_generator('totara_customfield');

        $cfids = [];
        $cfids['text']= $cf_generator->create_text('course', ['test_text']);
        $cfids['textarea'] = $cf_generator->create_textarea('course', ['test_textarea']);
        $cfids['multiselect'] = $cf_generator->create_multiselect('course', ['test_multi' => ['opt1', 'opt2']]);
        $cfids['datetime'] = $cf_generator->create_datetime('course', ['test_date' => []]);
        $cfsettings = ['test_location' => ['shortname' => 'test_location']];
        $cfids['location'] = $cf_generator->create_location('course', $cfsettings);
        $cfids['file'] = $cf_generator->create_file('course', ['test_file' => []]);
        $cfids['menu'] = $cf_generator->create_menu('course', ['test_menu' => ['item1', 'item2']]);
        $cfids['checkbox'] = $cf_generator->create_checkbox('course', ['test_checkbox' => []]);

        // Create course 1.
        $course1 = $generator->create_course(['fullname' => 'Course 1']);
        // Add customfields data to course 1.
        $course1_dt = strtotime('-1 day');
        $cf_generator->set_text($course1, $cfids['text']['test_text'], 'Course 1 text 1 value', 'course', 'course');
        $cf_generator->set_textarea($course1, $cfids['textarea']['test_textarea'], 'Course 1 textarea value', 'course', 'course');
        $cf_generator->set_multiselect($course1, $cfids['multiselect']['test_multi'], ['opt1', 'opt2'], 'course', 'course');
        $cf_generator->set_datetime($course1, $cfids['datetime']['test_date'], $course1_dt, 'course', 'course');
        $cf_generator->set_location_address($course1, $cfids['location']['test_location'], '186 Willis Street', 'course', 'course');

        $filename = 'testfile1.txt';
        $filecontent = 'Test file content';
        $testfile1 = $cf_generator->create_test_file_from_content($filename, $filecontent, $course1->id);
        $cf_generator->set_file($course1, $cfids['file']['test_file'], $course1->id, 'course', 'course');

        $cf_generator->set_menu($course1, $cfids['menu']['test_menu'], 'item2', 'course', 'course');
        $cf_generator->set_checkbox($course1, $cfids['checkbox']['test_checkbox'], true, 'course', 'course');

        // Verifying that the same values are returned as when getting all values
        $all_values = customfield_options::get_all_values($course1->id, 'course', 'course');
        $key_field_map = customfield_options::get_key_field_map('course');

        foreach ($key_field_map as $key => $cf_field) {
            $value = customfield_options::get_field_value($course1->id, $cf_field, 'course', 'course');

            // The generated ids in the link for files are different every time
            if ($key == 'cf_test_file') {
                $re = '/id="action_link[^"]*"/';
                $expected = preg_replace($re, '', $all_values[$key]);
                $actual = preg_replace($re, '', $value);
                static::assertEquals($expected, $actual);
            } else {
                static::assertEquals($all_values[$key], $value);
            }
        }
    }

    public function test_get_options_invalid_table() {
        // We can't be too specific on the error message returned from the different db vendors differ for non-existing tables
        static::expectException(dml_exception::class);
        static::expectExceptionMessageMatches('/whatever_info_field/');

        customfield_options::get_options('whatever');
    }

    public function test_get_values_invalid_table_prefix() {
        // We can't be too specific on the error message returned from the different db vendors differ for non-existing tables
        static::expectException(dml_exception::class);
        static::expectExceptionMessageMatches('/whatever_info_field/');

        customfield_options::get_all_values(123, 'whatever', 'abc');
    }

    public function test_get_values_invalid_cf_prefix() {
        // We can't be too specific on the error message returned from the different db vendors differ for non-existing columns
        static::expectException(dml_exception::class);
        static::expectExceptionMessageMatches('/tid.abcid/');

        customfield_options::get_all_values(123, 'course', 'abc');
    }

    private static function verify_options(array $expected_options, array $actual_options) {
        static::assertSame(count($expected_options), count($actual_options));

        foreach ($actual_options as $actual) {
            foreach ($expected_options as $idx => $expected) {
                if ($expected['key'] == $actual->get_key() && $expected['label'] == $actual->get_label()) {
                    unset($expected_options[$idx]);
                    continue 2;
                }
            }
        }

        static::assertEmpty($expected_options);
    }

    private static function verify_key_field_map(array $expected, array $actual) {
        static::assertSame(count($expected), count($actual));

        foreach ($actual as $key => $field) {
            if (isset($expected[$key]) && $expected[$key] == $field) {
                unset($expected[$key]);
            }
        }

        static::assertEmpty($expected);
    }

    private static function verify_values(array $expected_values, array $actual_values) {
        static::assertSame(count($expected_values), count($actual_values));

        foreach ($actual_values as $key => $value) {
            if (isset($expected_values[$key]) && $expected_values[$key] == $value) {
                unset($expected_values[$key]);
            }
        }

        if (!empty($expected_values)) {
            print_r($actual_values);
            print_r($expected_values);
        }
        static::assertEmpty($expected_values);
    }
}