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

use contentmarketplace_linkedin\core_json\structure\classification_element;
use core\json\validation_adapter;
use core_phpunit\testcase;
use contentmarketplace_linkedin\testing\generator;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_json_validator_validate_classification_element_testcase extends testcase {
    /**
     * @return void
     */
    public function test_validate_classification_json(): void {
        $generator = generator::instance();
        $json_data = $generator->get_json_content_from_fixtures('classification_1.json');

        $validator = validation_adapter::create_default();
        $result = $validator->validate_by_structure_class_name(
            $json_data,
            classification_element::class
        );

        self::assertTrue($result->is_valid());
        self::assertEmpty($result->get_error_message());
    }

    /**
     * @return void
     */
    public function test_validate_invalid_classification_json(): void {
        $generator = generator::instance();
        $json_data = $generator->get_json_content_from_fixtures('invalid_classification_1.json');

        $validator = validation_adapter::create_default();
        $result = $validator->validate_by_structure_class_name(
            $json_data,
            classification_element::class
        );

        self::assertFalse($result->is_valid());

        $message = $result->get_error_message();
        self::assertNotEmpty($message);
        self::assertEquals(
            "Missing field 'name', within object at field 'owner'.",
            $message
        );
    }
}