<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package core
 */

use core\date_format;
use core\format;
use core\webapi\formatter\field\date_field_formatter;
use core\webapi\formatter\field\string_field_formatter;
use core\webapi\formatter\formatter;

defined('MOODLE_INTERNAL') || die();

class core_webapi_formatter_testcase extends advanced_testcase {

    public function test_format() {
        // Enable the multilang filter and set it to apply to headings and content.
        filter_set_global_state('multilang', TEXTFILTER_ON);
        filter_set_applies_to_strings('multilang', true);
        filter_manager::reset_caches();

        $time = time();

        $data = [
            'multilangstring' => '<span lang="en" class="multilang">Summer</span>'.
                                 '<span lang="de" class="multilang">Sommer</span>',
            'datetimefield' => $time,
            'closurefield' => '<span lang="en" class="multilang">Autumn</span>'.
                              '<span lang="de" class="multilang">Herbst</span>',
            'closurefield2' => '<span lang="en" class="multilang">Spring</span>'.
                               '<span lang="de" class="multilang">Fruehling</span>',
            'customfunctionfield' => 'thisiscustom'
        ];

        $formatter = $this->get_formatter($data);

        // String field
        $value = $formatter->format('multilangstring', format::FORMAT_HTML);
        $this->assertEquals('Summer', $value);
        // Same field, different format
        $value = $formatter->format('multilangstring', format::FORMAT_RAW);
        $this->assertEquals($data['multilangstring'], $value);

        // Date field
        $value = $formatter->format('datetimefield', date_format::FORMAT_DATETIME);
        $expected = userdate($time, get_string('strftimedatetime', 'langconfig'));
        $this->assertEquals($expected, $value);

        // String field with Closures
        $value = $formatter->format('closurefield', format::FORMAT_HTML);
        $this->assertEquals('Autumn', $value);
        // Same field, different format
        $value = $formatter->format('closurefield', format::FORMAT_RAW);
        $this->assertEquals($data['closurefield'], $value);

        // String field with Closures 2
        $value = $formatter->format('closurefield2', format::FORMAT_HTML);
        $this->assertEquals('Spring', $value);
        // Same field, different format
        $value = $formatter->format('closurefield2', format::FORMAT_RAW);
        $this->assertEquals($data['closurefield2'], $value);

        // Custom function name
        $value = $formatter->format('customfunctionfield', format::FORMAT_RAW);
        $this->assertEquals($data['customfunctionfield'].format::FORMAT_RAW, $value);
    }

    public function test_format_mismatch_throws_error() {
        $time = time();

        $data = [
            'multilangstring' => '<span class="test">Test</span>',
            'datetimefield' => $time
        ];

        $formatter = $this->get_formatter($data);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Invalid format given');

        $formatter->format('datetimefield', format::FORMAT_HTML);
    }

    public function test_empty_field_throws_error() {
        $time = time();

        $data = [
            'multilangstring' => '<span class="test">Test</span>',
            'datetimefield' => $time
        ];

        $formatter = $this->get_formatter($data);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Field name cannot be empty');

        $formatter->format('', format::FORMAT_HTML);
    }

    public function test_unknown_field_throws_error() {
        $time = time();

        $data = [
            'multilangstring' => '<span class="test">Test</span>',
            'datetimefield' => $time
        ];

        $formatter = $this->get_formatter($data);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Unknown field foobar');

        $formatter->format('foobar', format::FORMAT_HTML);
    }

    public function test_works_with_objects() {
        $time = time();

        $data = new stdClass();
        $data->multilangstring = '<span class="test">Test</span>';
        $data->datetimefield = $time;

        $formatter = $this->get_formatter($data);

        $value = $formatter->format('multilangstring', format::FORMAT_RAW);
        $this->assertEquals($data->multilangstring, $value);
    }

    public function test_works_with_custom_object() {
        $time = time();

        $data = [
            'test' => 'my value',
            'test2' => $time
        ];

        // This is a custom class with custom get and has function to be used by the formatter
        $object = new class($data) {
            protected $fields = [];

            public function __construct($fields) {
                $this->fields = $fields;
            }

            public function get($field) {
                return $this->fields[$field] ?? null;
            }

            public function has($field) {
                return array_key_exists($field, $this->fields);
            }
        };

        // Override get_field and has_field to make use of custom object
        $formatter = new class($object, context_system::instance()) extends formatter {
            protected function get_map(): array {
                return [
                    'test' => string_field_formatter::class,
                    'test2' => date_field_formatter::class,
                ];
            }

            protected function get_field(string $field) {
                return $this->object->get($field);
            }

            protected function has_field(string $field): bool {
                return $this->object->has($field);
            }
        };

        $value = $formatter->format('test', format::FORMAT_RAW);
        $this->assertEquals($data['test'], $value);

        $value = $formatter->format('test2', date_format::FORMAT_TIMESTAMP);
        $this->assertEquals($data['test2'], $value);

        try {
            $formatter->format('test3', date_format::FORMAT_TIMESTAMP);
            $this->fail('Expected exception for non-existent field');
        } catch (Exception $e) {
            $this->assertInstanceOf(coding_exception::class, $e);
            $this->assertMatchesRegularExpression('/Unknown field test3/', $e->getMessage());
        }
    }

    public function test_field_not_defined_in_map_throws_error() {
        $time = time();

        $data = [
            'multilangstring' => '<span class="test">Test</span>',
            'datetimefield' => $time,
            'teststring' => 'test'
        ];

        $formatter = $this->get_formatter($data);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Field was not found in the format map');

        $formatter->format('teststring');
    }

    public function test_unknown_format_function_throws_error() {
        $data = [
            'multilangstring' => '<span class="test">Test</span>',
            'datetimefield' => time(),
        ];

        $formatter = new class($data, context_system::instance()) extends formatter {
            protected function get_map(): array {
                return [
                    'multilangstring' => 'idontexist',
                    'datetimefield' => date_field_formatter::class
                ];
            }
        };

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Format method not found!');

        $formatter->format('multilangstring');
    }

    public function test_null_format() {
        $data = [
            'multilangstring' => '<span lang="en" class="multilang">Summer</span>'.
                '<span lang="de" class="multilang">Sommer</span>',
            'customfunctionfield' => 'thisiscustom'
        ];

        $formatter = $this->get_formatter($data);

        // Custom function name does not care about the format so null is possible
        $value = $formatter->format('customfunctionfield', null);
        $this->assertEquals($data['customfunctionfield'], $value);

        // This one does not work with a null value
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Invalid format given');

        $formatter->format('multilangstring', null);
    }

    protected function get_formatter($data): formatter {
        $formatter = new class($data, context_system::instance()) extends formatter {
            protected function get_map(): array {
                return [
                    'multilangstring' => string_field_formatter::class,
                    'datetimefield' => date_field_formatter::class,
                    'closurefield' => function ($value, string_field_formatter $formatter) {
                        return $formatter->format($value);
                    },
                    'closurefield2' => function ($value, $format) {
                        $formatter = new string_field_formatter($format, $this->context);
                        return $formatter->format($value);
                    },
                    'customfunctionfield' => 'custom_format_function'
                ];
            }

            protected function custom_format_function($value, $format) {
                return $value.$format;
            }
        };

        return $formatter;
    }


}
