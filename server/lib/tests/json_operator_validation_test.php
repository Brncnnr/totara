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

use core\json\validation_adapter;
use core\json\structure\structure;
use core\json\type;
use core_phpunit\testcase;

class core_json_operator_validation_testcase extends testcase {
    /**
     * @return void
     */
    public function test_validate_any_of_for_string(): void {
        $structure = json_encode([
            'type' => type::OBJECT,
            'properties' => [
                'name' => [
                    'type' => type::STRING,
                    structure::ANY_OF => [
                        ['const' => 'Hi'],
                        ['const' => 'Boom']
                    ]
                ]
            ]
        ]);

        $validator = validation_adapter::create_default();
        $result_one = $validator->validate_by_json_structure(
            json_encode(['name' => 'Hi']),
            $structure
        );

        $result_two = $validator->validate_by_json_structure(
            json_encode(['name' => 'Boom']),
            $structure
        );

        self::assertTrue($result_one->is_valid());
        self::assertTrue($result_two->is_valid());
        self::assertEmpty($result_one->get_error_message());
        self::assertEmpty($result_two->get_error_message());

        $result_three = $validator->validate_by_json_structure(
            json_encode(['name' => 'data']),
            $structure
        );

        self::assertFalse($result_three->is_valid());
        self::assertEquals(
            "Field 'name' does not match value 'Hi', but receive 'data'. " .
            "Or field 'name' does not match value 'Boom', but receive 'data'.",
            $result_three->get_error_message()
        );
    }

    /**
     * @return void
     */
    public function test_validate_any_of_for_integer(): void {
        $structure = json_encode([
            'type' => type::OBJECT,
            'properties' => [
                'id' => [
                    'type' => type::INT,
                    structure::ANY_OF => [
                        ['const' => 15],
                        ['const' => 42]
                    ]
                ]
            ],
            'required' => ['id']
        ]);

        $validator = validation_adapter::create_default();
        $result_one = $validator->validate_by_json_structure(
            json_encode(['id' => 42]),
            $structure
        );

        $result_two = $validator->validate_by_json_structure(
            json_encode(['id' => 15]),
            $structure
        );

        self::assertTrue($result_one->is_valid());
        self::assertTrue($result_two->is_valid());

        self::assertEmpty($result_one->get_error_message());
        self::assertEmpty($result_two->get_error_message());

        $result_three = $validator->validate_by_json_structure(
            json_encode(['id' => 52]),
            $structure
        );

        self::assertFalse($result_three->is_valid());
        self::assertEquals(
            "Field 'id' does not match value '15', but receive '52'. " .
            "Or field 'id' does not match value '42', but receive '52'.",
            $result_three->get_error_message()
        );
    }

    /**
     * @return void
     */
    public function test_validate_any_of_for_array_with_contains(): void {
        $validator = validation_adapter::create_default();
        $structure = json_encode([
            'type' => type::ARRAY,
            structure::ANY_OF => [
                [
                    'contains' => [
                        'type' => type::OBJECT,
                        'properties' => [
                            'id' => [
                                'type' => type::INT,
                            ],
                        ],
                        'required' => ['id'],
                        structure::ADDITIONAL_PROPERTIES => false,
                    ],
                ],
                [
                    'contains' => [
                        'type' => type::OBJECT,
                        'properties' => [
                            'uuid' => [
                                'type' => type::STRING
                            ]
                        ],
                        'required' => ['uuid'],
                        structure::ADDITIONAL_PROPERTIES => false,
                    ]
                ]
            ]
        ]);

        $valid_data = [
            json_encode([['id' => 42]]),
            json_encode([['uuid' => 'uuid']]),
            json_encode([['id' => 42], ['uuid' => 'uuid']]),

            // This array here considered as valid data because with anyOf, if a single item within
            // the collection is matched with any sub schema then the whole collection is valid.
            json_encode([['id' => 22], ['uuid' => 'ccc'], ['x' => 'y']])
        ];

        foreach ($valid_data as $valid_datum) {
            $result = $validator->validate_by_json_structure($valid_datum, $structure);
            self::assertTrue($result->is_valid(), "Failed to validate datum '{$valid_datum}'");
            self::assertEmpty($result->get_error_message());
        }

        // This array is failing, because none of the items matched the array as above.
        $result_two = $validator->validate_by_json_structure(
            json_encode([['x' => 'z']]),
            $structure
        );

        self::assertFalse($result_two->is_valid());
        self::assertEquals(
            "Missing field 'id', within object at index '0'. Or missing field 'uuid', within object at index '0'.",
            $result_two->get_error_message()
        );
    }

    /**
     * This test is to make sure that anyOf operator with 'items' keyword will fail
     * the operator logics. This kind of structure should never be done in the first place.
     *
     * @return void
     */
    public function test_validate_any_of_for_array_with_items(): void {
        $validator = validation_adapter::create_default();
        $structure = json_encode([
            'type' => type::ARRAY,
            structure::ANY_OF => [
                [
                    'items' => [
                        'type' => type::OBJECT,
                        'properties' => [
                            'id' => [
                                'type' => type::INT,
                            ]
                        ],
                        'required' => ['id'],
                        structure::ADDITIONAL_PROPERTIES => false
                    ]
                ],
                [
                    'items' => [
                        'type' => type::OBJECT,
                        'properties' => [
                            'uuid' => [
                                'type' => type::STRING
                            ]
                        ],
                        'required' => ['uuid'],
                        structure::ADDITIONAL_PROPERTIES => false
                    ]
                ]
            ]
        ]);

        $valid_data = [
            json_encode([['id' => 42]]),
            json_encode([['uuid' => '42']])
        ];

        foreach ($valid_data as $valid_datum) {
            $result = $validator->validate_by_json_structure($valid_datum, $structure);

            self::assertTrue($result->is_valid(), "Expects the validation is valid for data: {$valid_datum}");
            self::assertEmpty($result->get_error_message());

            unset($result);
        }

        $invalid_data = [
            [
                json_encode([['z' => 'y']]),
                "Missing field 'id', within object at index '0'. " .
                "Or missing field 'uuid', within object at index '0'."
            ],
            [
                json_encode([['id' => 42], ['uuid' => '42']]),
                "Missing field 'id', within object at index '1'. " .
                "Or missing field 'uuid', within object at index '0'."
            ],
        ];

        foreach ($invalid_data as [$invalid_datum, $message]) {
            $result = $validator->validate_by_json_structure($invalid_datum, $structure);

            self::assertFalse($result->is_valid());
            self::assertEquals(
                $message,
                $result->get_error_message(),
                "Invalid message for datum {$invalid_datum}"
            );
        }
    }

    /**
     * With oneOf and "items" keyword, all the items within the collection must matches with one of the sub schema.
     * There cannot be both matching of sub schema.
     *
     * @return void
     */
    public function test_validate_one_of_for_array_with_items(): void {
        $validator = validation_adapter::create_default();
        $structure = json_encode([
            'type' => type::ARRAY,
            structure::ONE_OF => [
                [
                    'items' => [
                        'type' => type::OBJECT,
                        'properties' => [
                            'id' => [
                                'type' => type::INT,
                            ]
                        ],
                        'required' => ['id']
                    ]
                ],
                [
                    'items' => [
                        'type' => type::OBJECT,
                        'properties' => [
                            'uuid' => [
                                'type' => type::STRING
                            ]
                        ],
                        'required' => ['uuid']
                    ]
                ]
            ]
        ]);

        $valid_data = [
            json_encode([['id' => 42]]),
            json_encode([['uuid' => '42']])
        ];

        foreach ($valid_data as $valid_datum) {
            $result = $validator->validate_by_json_structure($valid_datum, $structure);
            self::assertTrue($result->is_valid(), "Expect valid result from datum {$valid_datum}");
            self::assertEmpty($result->get_error_message());

            unset($result);
        }

        $invalid_data = [
            json_encode([['z' => 'y']]),
            json_encode([['id' => 42], ['uuid' => '42']])
        ];

        foreach ($invalid_data as $invalid_datum) {
            $result = $validator->validate_by_json_structure($invalid_datum, $structure);

            self::assertFalse($result->is_valid(), "Expect invalid result from datum {$invalid_datum}");
            self::assertEquals(
                "Expect exactly 1 matched of data model, but there are 0 matches.",
                $result->get_error_message()
            );
        }
    }

    /**
     * @return void
     */
    public function test_validate_one_of_for_array_with_contains(): void {
        $validator = validation_adapter::create_default();
        $structure = json_encode([
            'type' => type::ARRAY,
            structure::ONE_OF => [
                [
                    'contains' => [
                        'type' => type::OBJECT,
                        'properties' => [
                            'id' => [
                                'type' => type::INT,
                            ]
                        ],
                        'required' => ['id']
                    ]
                ],
                [
                    'contains' => [
                        'type' => type::OBJECT,
                        'properties' => [
                            'uuid' => [
                                'type' => type::STRING
                            ]
                        ],
                        'required' => ['uuid']
                    ]
                ]
            ]
        ]);

        $valid_data = [
            json_encode([['id' => 42]]),
            json_encode([['uuid' => '42']])
        ];

        foreach ($valid_data as $valid_datum) {
            $result = $validator->validate_by_json_structure($valid_datum, $structure);
            self::assertTrue($result->is_valid(), "Expect valid result from datum {$valid_datum}");
            self::assertEmpty($result->get_error_message());

            unset($result);
        }

        $invalid_data = [
            [json_encode([['z' => 'y']]), 0],
            [json_encode([['id' => 42], ['uuid' => '42']]), 2]
        ];

        foreach ($invalid_data as [$invalid_datum, $matches]) {
            $result = $validator->validate_by_json_structure($invalid_datum, $structure);

            self::assertFalse($result->is_valid(), "Expect invalid result from datum {$invalid_datum}");
            self::assertEquals(
                "Expect exactly 1 matched of data model, but there are {$matches} matches.",
                $result->get_error_message()
            );
        }
    }

    /**
     * The operator allOf with "items" keyword will be result in that none of the json will suit the
     * schema, hence everything will be failed.
     *
     * This test is to remind about that.
     *
     * @return void
     */
    public function test_validate_all_of_for_array_with_items(): void {
        $validator = validation_adapter::create_default();
        $structure = json_encode([
            'type' => type::ARRAY,
            structure::ALL_OF => [
                [
                    'items' => [
                        'type' => type::OBJECT,
                        'properties' => [
                            'id' => [
                                'type' => type::INT
                            ]
                        ],
                        'required' => ['id'],
                        structure::ADDITIONAL_PROPERTIES => false
                    ]
                ],
                [
                    'items' => [
                        'type' => type::OBJECT,
                        'properties' => [
                            'uuid' => [
                                'type' => type::STRING
                            ]
                        ],
                        'required' => ['uuid'],
                        structure::ADDITIONAL_PROPERTIES => false
                    ]
                ]
            ]
        ]);

        $data = [
            [
                json_encode([['id' => 42], ['uuid' => '42']]),
                "Missing field 'id', within object at index '1'."
            ],
            [
                json_encode([['uuid' => '42']]),
                "Missing field 'id', within object at index '0'."
            ],
            [
                json_encode([['id' => 42]]),
                "Missing field 'uuid', within object at index '0'."
            ],
            [
                json_encode([['id' => 42], ['uuid' => '42'], ['uuid' => '42']]),
                "Missing field 'id', within object at index '1'."
            ],
            [
                json_encode([['id' => 42], ['uuid' => '42'], ['id' => 42]]),
                "Missing field 'id', within object at index '1'."
            ],
            [
                json_encode([['z' => 'y'], ['id' => 42]]),
                "Missing field 'id', within object at index '0'."
            ]
        ];

        foreach ($data as [$datum, $message]) {
            $result = $validator->validate_by_json_structure($datum, $structure);

            self::assertFalse($result->is_valid(), "Expects the result to be invalid for datum {$datum}");
            self::assertEquals($message, $result->get_error_message(), "Error message for datum {$datum}");

            unset($result);
        }
    }

    /**
     * @return void
     */
    public function test_validate_all_of_for_array_with_contains(): void {
        $validator = validation_adapter::create_default();
        $structure = json_encode([
            'type' => type::ARRAY,
            structure::ALL_OF => [
                [
                    'contains' => [
                        'type' => type::OBJECT,
                        'properties' => [
                            'id' => [
                                'type' => type::INT
                            ]
                        ],
                        'required' => ['id'],
                        structure::ADDITIONAL_PROPERTIES => false
                    ]
                ],
                [
                    'contains' => [
                        'type' => type::OBJECT,
                        'properties' => [
                            'uuid' => [
                                'type' => type::STRING
                            ]
                        ],
                        'required' => ['uuid'],
                        structure::ADDITIONAL_PROPERTIES => false
                    ]
                ]
            ]
        ]);

        $valid_data = [
            json_encode([['id' => 42], ['uuid' => '42']]),
            json_encode([['id' => 42], ['uuid' => '42'], ['uuid' => '42']]),
            json_encode([['id' => 42], ['uuid' => '42'], ['id' => 42]]),
        ];

        foreach ($valid_data as $valid_datum) {
            $result = $validator->validate_by_json_structure($valid_datum, $structure);

            self::assertTrue($result->is_valid(), "Expects the result to be valid for datum {$valid_datum}");
            self::assertEmpty($result->get_error_message());

            unset($result);
        }

        $invalid_data = [
            [
                json_encode([['id' => 42], ['id' => 40]]),
                "Missing field 'uuid', within object at index '0'. And missing field 'uuid', within object at index '1'."
            ],
            [
                json_encode([['uuid' => '442'], ['uuid' => '42']]),
                "Missing field 'id', within object at index '0'. And missing field 'id', within object at index '1'."
            ],
            [
                json_encode([['z' => 'y']]),
                "Missing field 'id', within object at index '0'."
            ],
            [
                json_encode([['id' => 42, 'uuid' => '42']]),
                "There are unexpected additional properties at index '0'"
            ]
        ];

        foreach ($invalid_data as [$invalid_datum, $message]) {
            $result = $validator->validate_by_json_structure($invalid_datum, $structure);

            self::assertFalse($result->is_valid(), "Expects the result to be invalid for data {$invalid_datum}");
            self::assertEquals(
                $message,
                $result->get_error_message()
            );
        }
    }
}