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
 * @package core
 */

use core\json\type;
use core_phpunit\testcase;
use core\json\validation_adapter;

class core_json_validator_string_field_testcase extends testcase {
    /**
     * @return void
     */
    public function test_min_length_validation(): void {
        $validator = validation_adapter::create_default();
        $structure = json_encode([
            'type' => type::STRING,
            'minLength' => 2
        ]);

        $result = $validator->validate_by_json_structure(
            json_encode("this is data"),
            $structure
        );

        self::assertTrue($result->is_valid());
        self::assertEmpty($result->get_error_message());

        $invalid_result = $validator->validate_by_json_structure(
            json_encode('s'),
            $structure,
        );

        self::assertFalse($invalid_result->is_valid());
        self::assertEquals(
            "Expect the length to exceed 2, actual length is 1.",
            $invalid_result->get_error_message()
        );
    }

    /**
     * @return void
     */
    public function test_min_length_validation_in_object(): void {
        $validator = validation_adapter::create_default();
        $structure = json_encode([
            'type' => type::OBJECT,
            'properties' => [
                'str' => [
                    'type' => type::STRING,
                    'minLength' => 2
                ],
            ]
        ]);

        $obj = new stdClass();
        $obj->str = 'data';

        $result = $validator->validate_by_json_structure($obj, $structure);
        self::assertTrue($result->is_valid());
        self::assertEmpty($result->get_error_message());

        $invalid_obj = new stdClass();
        $invalid_obj->str = 'c';

        $invalid_result = $validator->validate_by_json_structure($invalid_obj, $structure);
        self::assertFalse($invalid_result->is_valid());
        self::assertEquals(
            "Expect the length of field 'str' to exceed 2, actual length is 1.",
            $invalid_result->get_error_message()
        );
    }

    /**
     * @return void
     */
    public function test_max_length_validation(): void {
        $validator = validation_adapter::create_default();
        $structure = json_encode([
            'type' => type::STRING,
            'maxLength' => 10
        ]);

        $result = $validator->validate_by_json_structure(
            json_encode(str_repeat('c', 9)),
            $structure
        );

        self::assertTrue($result->is_valid());
        self::assertEmpty($result->get_error_message());

        $invalid_result = $validator->validate_by_json_structure(
            json_encode(str_repeat('c', 11)),
            $structure
        );

        self::assertFalse($invalid_result->is_valid());
        self::assertEquals(
            "Expect the length to not exceed 10, actual length is 11.",
            $invalid_result->get_error_message()
        );
    }

    /**
     * @return void
     */
    public function test_max_length_validation_in_object(): void {
        $validator = validation_adapter::create_default();
        $structure = json_encode([
            'type' => type::OBJECT,
            'properties' => [
                'str' => [
                    'type' => type::STRING,
                    'maxLength' => 10
                ],
            ]
        ]);

        $obj = new stdClass();
        $obj->str = 'data';

        $result = $validator->validate_by_json_structure($obj, $structure);
        self::assertTrue($result->is_valid());
        self::assertEmpty($result->get_error_message());

        $invalid_obj = new stdClass();
        $invalid_obj->str = str_repeat('c', 11);

        $invalid_result = $validator->validate_by_json_structure($invalid_obj, $structure);
        self::assertFalse($invalid_result->is_valid());
        self::assertEquals(
            "Expect the length of field 'str' to not exceed 10, actual length is 11.",
            $invalid_result->get_error_message()
        );
    }
}