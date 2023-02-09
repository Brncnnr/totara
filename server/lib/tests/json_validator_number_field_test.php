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

class core_json_validator_number_field_testcase extends testcase {
    /**
     * @return void
     */
    public function test_validate_minium(): void {
        $validator = validation_adapter::create_default();
        $structure = json_encode([
            'type' => type::INT,
            'minimum' => 1,
        ]);

        $result = $validator->validate_by_json_structure(json_encode(12), $structure);

        self::assertTrue($result->is_valid());
        self::assertEmpty($result->get_error_message());

        $invalid_result = $validator->validate_by_json_structure(json_encode(0), $structure);

        self::assertFalse($invalid_result->is_valid());
        self::assertEquals(
            "Expect the value to exceed 1, actual value is 0.",
            $invalid_result->get_error_message()
        );
    }

    /**
     * @return void
     */
    public function test_validate_minimum_of_a_field_in_object(): void {
        $validator = validation_adapter::create_default();
        $structure = json_encode([
            'type' => type::OBJECT,
            'properties' => [
                'id' => [
                    'type' => type::INT,
                    'minimum' => 1
                ]
            ]
        ]);

        $obj = new stdClass();
        $obj->id = 42;

        $result = $validator->validate_by_json_structure($obj, $structure);
        self::assertTrue($result->is_valid());
        self::assertEmpty($result->get_error_message());

        $invalid_obj = new stdClass();
        $invalid_obj->id = 0;

        $invalid_result = $validator->validate_by_json_structure($invalid_obj, $structure);
        self::assertFalse($invalid_result->is_valid());
        self::assertEquals(
            "Expect the value of field 'id' to exceed 1, actual value is 0.",
            $invalid_result->get_error_message()
        );
    }

    /**
     * @return void
     */
    public function test_validate_maximum(): void {
        $validator = validation_adapter::create_default();
        $structure = json_encode([
            'type' => type::INT,
            'maximum' => 10,
        ]);

        $result = $validator->validate_by_json_structure(json_encode(9), $structure);

        self::assertTrue($result->is_valid());
        self::assertEmpty($result->get_error_message());

        $invalid_result = $validator->validate_by_json_structure(json_encode(11), $structure);

        self::assertFalse($invalid_result->is_valid());
        self::assertEquals(
            "Expect the value to not exceed 10, actual value is 11.",
            $invalid_result->get_error_message()
        );
    }

    /**
     * @return void
     */
    public function test_validate_maximum_of_a_field_in_object(): void {
        $validator = validation_adapter::create_default();
        $structure = json_encode([
            'type' => type::OBJECT,
            'properties' => [
                'id' => [
                    'type' => type::INT,
                    'maximum' => 43
                ]
            ]
        ]);

        $obj = new stdClass();
        $obj->id = 42;

        $result = $validator->validate_by_json_structure($obj, $structure);
        self::assertTrue($result->is_valid());
        self::assertEmpty($result->get_error_message());

        $invalid_obj = new stdClass();
        $invalid_obj->id = 44;

        $invalid_result = $validator->validate_by_json_structure($invalid_obj, $structure);
        self::assertFalse($invalid_result->is_valid());
        self::assertEquals(
            "Expect the value of field 'id' to not exceed 43, actual value is 44.",
            $invalid_result->get_error_message()
        );
    }
}