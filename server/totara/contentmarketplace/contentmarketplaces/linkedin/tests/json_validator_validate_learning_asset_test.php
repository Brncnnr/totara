<?php
/**
 * This file is part of Totara Learn
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
use core_phpunit\testcase;
use contentmarketplace_linkedin\testing\generator;
use core\json\validation_adapter;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_json_validator_validate_learning_asset_testcase extends testcase {
    /**
     * @return void
     */
    public function test_validate_learning_asset_collection(): void {
        $generator = generator::instance();
        $json = $generator->get_json_content_from_fixtures('response_1');

        $validation_adapter = validation_adapter::create_default();
        $result = $validation_adapter->validate_by_structure_name(
            $json,
            'learning_asset_collection',
            'contentmarketplace_linkedin'
        );

        self::assertEquals('', $result->get_error_message());
        self::assertTrue($result->is_valid());
    }

    /**
     * @return void
     */
    public function test_validate_learning_asset(): void {
        $generator = generator::instance();
        $json = $generator->get_json_content_from_fixtures('learning_asset_1');

        $validator = validation_adapter::create_default();
        $result = $validator->validate_by_structure_name($json, 'learning_asset_element', 'contentmarketplace_linkedin');

        self::assertEquals('', $result->get_error_message());
        self::assertTrue($result->is_valid());
    }

    /**
     * @return void
     */
    public function test_validate_sub_asset(): void {
        $generator = generator::instance();
        $sub_asset_files = [
            'sub_asset_1',
            'sub_asset_2'
        ];

        foreach ($sub_asset_files as $sub_asset_file) {
            $json = $generator->get_json_content_from_fixtures($sub_asset_file);

            $validator = validation_adapter::create_default();
            $result = $validator->validate_by_structure_name($json, 'sub_asset', 'contentmarketplace_linkedin');

            self::assertEquals('', $result->get_error_message());
            self::assertTrue($result->is_valid());
        }
    }
}