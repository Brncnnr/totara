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
use core\json\validation_adapter;
use core_phpunit\testcase;

class core_json_validator_bool_testcase extends testcase {
    /**
     * @return void
     */
    public function test_validate_valid_field_boolean(): void {
        $validator = validation_adapter::create_default();
        $structure = json_encode([
            'type' => type::OBJECT,
            'properties' => [
                'field_1' => [
                    'type' => type::BOOL,
                ],
                'field_2' => [
                    'type' => type::BOOL,
                ],
            ],
            'required' => ['field_1'],
        ]);

        $json = new stdClass();
        $json->field_1 = true;
        $json->field_2 = false;

        $result = $validator->validate_by_json_structure($json, $structure);
        self::assertTrue($result->is_valid());
        self::assertEmpty($result->get_error_message());
    }

    /**
     * @return void
     */
    public function test_validate_invalid_field_boolean(): void {
        $validator = validation_adapter::create_default();
        $structure = json_encode([
            'type' => type::OBJECT,
            'properties' => [
                'field_1' => [
                    'type' => type::BOOL,
                ],
            ],
            'required' => ['field_1'],
        ]);

        $invalid_data = [
            [json_encode(['field_1' => 1]), "integer"],
            [json_encode(['field_1' => 0]), "integer"],
            [json_encode(['field_1' => 'yes']), "string"],
            [json_encode(['field_1' => 'no']), "string"],
            [json_encode(['field_1' => 'true']), "string"],
            [json_encode(['field_1' => 'false']), "string"],
        ];

        foreach ($invalid_data as [$invalid_datum, $type]) {
            $result = $validator->validate_by_json_structure($invalid_datum, $structure);
            self::assertFalse($result->is_valid(), "Expect datum '{$invalid_datum}' should yield error");
            self::assertEquals(
                "Expect type of field 'field_1' to be boolean, but receive type {$type}.",
                $result->get_error_message()
            );
        }
    }
}