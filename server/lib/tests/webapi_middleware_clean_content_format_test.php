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
 * @package core
 */
defined('MOODLE_INTERNAL') || die();

use core\webapi\resolver\payload;
use core\webapi\execution_context;
use core\webapi\resolver\result;
use core\webapi\middleware\clean_content_format;

class core_webapi_middleware_clean_content_format_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_set_default_content_format_value(): void {
        $ec = execution_context::create('dev');
        $payload = new payload([], $ec);

        $middleware = new clean_content_format('key_format', FORMAT_JSON_EDITOR);
        $result = $middleware->handle(
            $payload,
            function (payload $payload): result {
                return new result($payload->get_variables());
            }
        );

        $data = $result->get_data();

        self::assertIsArray($data);
        self::arrayHasKey('key_format');
        self::assertEquals(FORMAT_JSON_EDITOR, $data['key_format']);
    }

    /**
     * @return void
     */
    public function test_validate_content_format_value_with_valid_value(): void {
        $ec = execution_context::create('dev');
        $formats = [
            FORMAT_PLAIN,
            FORMAT_MOODLE,
            FORMAT_HTML,
            FORMAT_JSON_EDITOR,
            FORMAT_MARKDOWN
        ];

        $middleware = new clean_content_format('key_format', FORMAT_JSON_EDITOR);

        foreach ($formats as $format_value) {
            $payload = new payload(['key_format' => $format_value], $ec);
            $result = $middleware->handle(
                $payload,
                function (payload $payload): result {
                    return new result($payload->get_variables());
                }
            );

            $data = $result->get_data();

            self::assertIsArray($data);
            self::arrayHasKey('key_format');
            self::assertEquals($format_value, $data['key_format']);
        }
    }

    /**
     * @return void
     */
    public function test_validate_content_format_value_with_invalid_value(): void {
        $ec = execution_context::create('dev');
        $middleware = new clean_content_format('key_format', FORMAT_JSON_EDITOR);

        $payload = new payload(['key_format' => 42], $ec);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The format value is invalid");

        $middleware->handle(
            $payload,
            function (payload $payload): result {
                return new result($payload->get_variables());
            }
        );
    }

    /**
     * @return void
     */
    public function test_construct_middleware_with_invalid_default_value(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Default value '42' is invalid");

        new clean_content_format('key', 42);
    }

    /**
     * @return void
     */
    public function test_construct_middleware_with_invalid_restricted_formats(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("One of the restricted format does not exist in system provided formats");

        new clean_content_format('key', FORMAT_PLAIN, [42]);
    }

    /**
     * @return void
     */
    public function test_validate_content_format_value_with_null_value(): void {
        $ec = execution_context::create('dev');
        $middleware = new clean_content_format('key_format', null);

        $payload = new payload(['key_format' => null], $ec);
        $result = $middleware->handle(
            $payload,
            function (payload $payload): result {
                return new result($payload->get_variables());
            }
        );

        $data = $result->get_data();
        self::assertArrayHasKey('key_format', $data);
        self::assertNull($data['key_format']);
    }
}