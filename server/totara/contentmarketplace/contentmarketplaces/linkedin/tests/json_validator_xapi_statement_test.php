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

use contentmarketplace_linkedin\core_json\structure\xapi_statement;
use core\json\validation_adapter;
use core_phpunit\testcase;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_json_validator_xapi_statement_testcase extends testcase {
    /**
     * @return void
     */
    public function test_validate_valid_json(): void {
        $json = json_encode([
            "actor" => [
                "mbox" => "mailto:example@example.com",
                "objectType" => "Agent"
            ],
            "result" => [
                "completion" => false,
                "extensions" => [
                    "https://w3id.org/xapi/cmi5/result/extensions/progress" => 39,
                ]
            ],
            "verb" => [
                "display" => [
                    "en-US" => "PROGRESSED"
                ],
                "id" => "http://adlnet.gov/expapi/verbs/progressed"
            ],
            "id" => "6a9fefbb-3517-4b92-9d5e-5710aaba924f",
            "object" => [
                "definition" => [
                    "type" => "http://adlnet.gov/expapi/activities/course",
                ],
                "id" => "urn:lyndaCourse:252",
                "objectType" => "Activity"
            ],
            "timestamp" => "2021-08-18T04:44:23.764Z"
        ]);

        $json_validator = validation_adapter::create_default();
        $result = $json_validator->validate_by_structure_class_name($json, xapi_statement::class);

        self::assertTrue($result->is_valid());
        self::assertEmpty($result->get_error_message());
    }

    /**
     * @return void
     */
    public function test_validate_invalid_json(): void {
        $json = json_encode([
            "actor" => [
                "mbox" => "mailto:example@example.com",
                "objectType" => "Agent"
            ],
            "result" => [
                "extensions" => [
                    "https://w3id.org/xapi/cmi5/result/extensions/progress" => 39,
                ]
            ],
            "verb" => [
                "display" => [
                    "en-US" => "PROGRESSED"
                ],
                "id" => "http://adlnet.gov/expapi/verbs/progressed"
            ],
            "id" => "6a9fefbb-3517-4b92-9d5e-5710aaba924f",
            "object" => [
                "definition" => [
                    "type" => "http://adlnet.gov/expapi/activities/course",
                ],
                "id" => "urn:lyndaCourse:252",
                "objectType" => "Activity"
            ],
            "timestamp" => "2021-08-18T04:44:23.764Z"
        ]);

        $json_validator = validation_adapter::create_default();
        $result = $json_validator->validate_by_structure_class_name($json, xapi_statement::class);

        self::assertFalse($result->is_valid());
        $message = $result->get_error_message();

        self::assertNotEmpty($message);
        self::assertEquals(
            "Missing field 'completion', within object at field 'result'.",
            $message
        );
    }
}