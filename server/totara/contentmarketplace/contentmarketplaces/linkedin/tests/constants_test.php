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
use core_phpunit\testcase;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_constants_testcase extends testcase {
    /**
     * @return void
     */
    public function test_check_is_valid_asset_types(): void {
        self::assertTrue(constants::is_valid_asset_type(constants::ASSET_TYPE_COURSE));
        self::assertTrue(constants::is_valid_asset_type(constants::ASSET_TYPE_VIDEO));
        self::assertTrue(constants::is_valid_asset_type(constants::ASSET_TYPE_CHAPTER));

        self::assertFalse(constants::is_valid_asset_type('chapter'));
        self::assertFalse(constants::is_valid_asset_type(strtolower(constants::ASSET_TYPE_COURSE)));
        self::assertFalse(constants::is_valid_asset_type('real_g'));
    }

    /**
     * @return void
     */
    public function test_check_is_valid_availability(): void {
        self::assertTrue(constants::is_valid_availability(constants::AVAILABILITY_RETIRED));
        self::assertTrue(constants::is_valid_availability(constants::AVAILABILITY_AVAILABLE));

        self::assertFalse(constants::is_valid_availability(strtolower(constants::AVAILABILITY_RETIRED)));
        self::assertFalse(constants::is_valid_availability('available'));
        self::assertFalse(constants::is_valid_availability('xdg_open'));
    }

    /**
     * @return void
     */
    public function test_check_is_valid_classification_type(): void {
        self::assertTrue(constants::is_valid_classification_type(constants::CLASSIFICATION_TYPE_TOPIC));
        self::assertTrue(constants::is_valid_classification_type(constants::CLASSIFICATION_TYPE_SUBJECT));
        self::assertTrue(constants::is_valid_classification_type(constants::CLASSIFICATION_TYPE_LIBRARY));
        self::assertTrue(constants::is_valid_classification_type(constants::CLASSIFICATION_TYPE_SKILL));

        self::assertFalse(constants::is_valid_classification_type(strtolower(constants::CLASSIFICATION_TYPE_TOPIC)));
        self::assertFalse(constants::is_valid_classification_type(strtolower(constants::CLASSIFICATION_TYPE_SUBJECT)));
        self::assertFalse(constants::is_valid_classification_type(strtolower(constants::CLASSIFICATION_TYPE_LIBRARY)));
        self::assertFalse(constants::is_valid_classification_type(strtolower(constants::CLASSIFICATION_TYPE_SKILL)));

        self::assertFalse(constants::is_valid_classification_type('cDkoow'));
    }
}