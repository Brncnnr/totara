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

use core\json\structure\structure;
use core\json\type;
use core\json\validation_adapter;
use core_phpunit\testcase;

class core_json_validator_collection_testcase extends testcase {
    /**
     * @return void
     */
    public function test_validate_json_with_list_item_schema(): void {
        $structure = json_encode([
            'type' => type::ARRAY,
            'items' => [
                'type' => type::OBJECT,
                'properties' => [
                    'id' => [
                        'type' => type::INT,
                    ],
                    'name' => [
                        'type' => type::STRING,
                    ],
                ],
                'required' => ['id', 'name'],
            ],
        ]);

        $validator = validation_adapter::create_default();
        $result_one = $validator->validate_by_json_structure(
            json_encode([
                [
                    'id' => 42,
                    'name' => 'abcde',
                ],
                [
                    'id' => 52,
                    'name' => 'abcde',
                ],
            ]),
            $structure
        );

        self::assertTrue($result_one->is_valid());
        self::assertEmpty($result_one->get_error_message());

        $result_two = $validator->validate_by_json_structure(json_encode([]), $structure);
        self::assertTrue($result_two->is_valid());
        self::assertEmpty($result_two->get_error_message());
    }

    /**
     * @return void
     */
    public function test_validate_json_with_min_items_in_schema(): void {
        $structure = json_encode([
            'type' => type::ARRAY,
            'items' => [
                ['type' => type::INT],
            ],
            structure::MIN_ITEMS => 1,
        ]);

        $validator = validation_adapter::create_default();

        $result_one = $validator->validate_by_json_structure([42, 21], $structure);
        self::assertTrue($result_one->is_valid());
        self::assertEmpty($result_one->get_error_message());

        $result_two = $validator->validate_by_json_structure([], $structure);
        self::assertFalse($result_two->is_valid());
        self::assertEquals(
            "Expect the min items to be 1, but actual count is 0.",
            $result_two->get_error_message()
        );
    }

    /**
     * @return void
     */
    public function test_validate_json_with_max_items_in_schema(): void {
        $structure = json_encode([
            'type' => type::ARRAY,
            'items' => ['type' => type::INT],
            structure::MAX_ITEMS => 2,
        ]);

        $validator = validation_adapter::create_default();

        $valid_data = [
            [],
            [1],
            [2, 3],
        ];

        foreach ($valid_data as $datum) {
            $result = $validator->validate_by_json_structure($datum, $structure);
            self::assertTrue($result->is_valid());
            self::assertEmpty($result->get_error_message());
        }

        $result_two = $validator->validate_by_json_structure([1, 2, 3], $structure);
        self::assertFalse($result_two->is_valid());
        self::assertEquals(
            "Expect the max items to be 2, but actual count is 3.",
            $result_two->get_error_message()
        );
    }

    /**
     * @return void
     */
    public function test_validate_json_with_max_and_min_items_in_schema(): void {
        $structure = json_encode([
            'type' => type::ARRAY,
            'items' => [
                ['type' => type::INT],
            ],
            structure::MAX_ITEMS => 3,
            structure::MIN_ITEMS => 1,
        ]);

        $validator = validation_adapter::create_default();
        $data = [
            [[], false, "Expect the min items to be 1, but actual count is 0."],
            [[1], true, ""],
            [[2, 3], true, ""],
            [[3, 4, 5], true, ""],
            [[3, 4, 5, 6], false, "Expect the max items to be 3, but actual count is 4."],
        ];

        foreach ($data as [$datum, $valid, $message]) {
            $result = $validator->validate_by_json_structure($datum, $structure);

            self::assertEquals($valid, $result->is_valid());
            self::assertEquals($message, $result->get_error_message());
        }
    }

    /**
     * @return void
     */
    public function test_validate_json_with_contains_item_in_schema(): void {
        $structure = json_encode([
            'type' => type::ARRAY,
            'contains' => [
                'type' => type::OBJECT,
                'properties' => [
                    'id' => [
                        'type' => type::INT,
                    ],
                ],
                'required' => ['id'],
            ],
        ]);

        $validator = validation_adapter::create_default();
        $result_one = $validator->validate_by_json_structure([], $structure);

        // This is invalid, because the json data does not contains any item.
        self::assertFalse($result_one->is_valid());
        self::assertEquals(
            "The json instance does not contain any items.",
            $result_one->get_error_message()
        );

        $result_two = $validator->validate_by_json_structure(
            json_encode([['id' => 15]]),
            $structure
        );

        self::assertTrue($result_two->is_valid());
        self::assertEmpty($result_two->get_error_message());

        $result_three = $validator->validate_by_json_structure(
            json_encode([['id' => 42], 15]),
            $structure
        );

        // This is valid, because the collection contains an object that ,meets the schema.
        self::assertTrue($result_three->is_valid());
        self::assertEmpty($result_three->get_error_message());
    }

    /**
     * @return void
     */
    public function test_validate_json_contains_additional_items(): void {
        $structure = json_encode([
            'type' => type::ARRAY,
            'items' => [
                ['type' => type::BOOL],
                [
                    'type' => type::OBJECT,
                    'properties' => [
                        'id' => [
                            'type' => type::INT
                        ]
                    ]
                ]
            ],
            structure::ADDITIONAL_ITEMS => [
                'type' => type::STRING
            ]
        ]);

        $validator = validation_adapter::create_default();
        $valid_data = [
            json_encode([true, ['id' => 42], 'hello world']),
            json_encode([false, ['id' => 55]])
        ];

        foreach ($valid_data as $valid_datum) {
            $result = $validator->validate_by_json_structure($valid_datum, $structure);
            self::assertTrue($result->is_valid(), "Expect the datum '{$valid_datum}' to not yield error ");
            self::assertEmpty($result->get_error_message());

            unset($result);
        }

        $invalid_data = [
            [json_encode([true, ['id' => 42], false]), 'boolean'],
            [json_encode([false, ['id' => 55], 1]), 'integer'],
            [json_encode([false, ['id' => 55], []]), 'array']
        ];

        foreach ($invalid_data as [$invalid_datum, $type]) {
            $result = $validator->validate_by_json_structure($invalid_datum, $structure);
            self::assertFalse($result->is_valid());
            self::assertEquals(
                "Expect type of index '2' to be string, but receive type {$type}.",
                $result->get_error_message()
            );
        }
    }
}