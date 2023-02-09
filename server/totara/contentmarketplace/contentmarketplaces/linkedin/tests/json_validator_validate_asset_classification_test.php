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
 * @package contentmarketplace_linkedin
 */

use contentmarketplace_linkedin\constants;
use contentmarketplace_linkedin\core_json\structure\asset_classification;
use core\json\validation_adapter;
use core_phpunit\testcase;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_json_validator_validate_asset_classification_testcase extends testcase {
    /**
     * @return void
     */
    public function test_validate_valid_json(): void {
        $json = json_encode([
            "assigner" => [
                "urn" => "urn:li:org:1133",
                "name" => [
                    "locale" => [
                        "country" => "US",
                        "language" => "en"
                    ],
                    "value" => "Totara"
                ]
            ],
            "associatedClassification" => [
                "owner" => [
                    "urn" => "urn:li:org:133",
                    "name" => [
                        "locale" => [
                            "country" => "US",
                            "language" => "en"
                        ],
                        "value" => "Bomba"
                    ]
                ],
                "name" => [
                    "locale" => [
                        "country" => "US",
                        "language" => "en"
                    ],
                    "value" => "How to?"
                ],
                "urn" => "urn:li:skill:111",
                "type" => constants::CLASSIFICATION_TYPE_SKILL
            ],
            "path" => []
        ]);

        $validator = validation_adapter::create_default();
        $result = $validator->validate_by_structure_class_name(
            $json,
            asset_classification::class
        );

        self::assertTrue($result->is_valid());
        self::assertEmpty($result->get_error_message());
    }

    /**
     * @return void
     */
    public function test_validate_invalid_json_1(): void {
        $json = json_encode([
            "assigner" => [
                "urn" => "urn:li:org:1133",
                "name" => [
                    "locale" => [
                        "country" => "US",
                        "language" => "en"
                    ],
                    "value" => "Totara"
                ]
            ],
            "associatedClassification" => "",
            "path" => []
        ]);


        $validator = validation_adapter::create_default();
        $result = $validator->validate_by_structure_class_name(
            $json,
            asset_classification::class
        );

        self::assertFalse($result->is_valid());
        self::assertNotEmpty($result->get_error_message());
        self::assertEquals(
            "Expect type of field 'associatedClassification' to be object, but receive type string.",
            $result->get_error_message()
        );
    }

    /**
     * @return void
     */
    public function test_validate_invalid_json_2(): void {
        $json = json_encode([
            "assigner" => "",
            "associatedClassification" => [
                "owner" => [
                    "urn" => "urn:li:org:133",
                    "name" => [
                        "locale" => [
                            "country" => "US",
                            "language" => "en"
                        ],
                        "value" => "Bomba"
                    ]
                ],
                "name" => [
                    "locale" => [
                        "country" => "US",
                        "language" => "en"
                    ],
                    "value" => "How to?"
                ],
                "urn" => "urn:li:skill:111",
                "type" => constants::CLASSIFICATION_TYPE_SKILL
            ],
            "path" => []
        ]);


        $validator = validation_adapter::create_default();
        $result = $validator->validate_by_structure_class_name(
            $json,
            asset_classification::class
        );

        self::assertFalse($result->is_valid());
        self::assertNotEmpty($result->get_error_message());
        self::assertEquals(
            "Expect type of field 'assigner' to be object, but receive type string.",
            $result->get_error_message()
        );
    }
}