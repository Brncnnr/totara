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

use contentmarketplace_linkedin\core_json\data_format\asset_type;
use core_phpunit\testcase;
use contentmarketplace_linkedin\constants;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_json_data_format_asset_type_testcase extends testcase {
    /**
     * @return void
     */
    public function test_validation(): void {
        $asset_type = new asset_type();

        self::assertTrue($asset_type->validate(constants::ASSET_TYPE_COURSE));
        self::assertTrue($asset_type->validate(constants::ASSET_TYPE_CHAPTER));
        self::assertTrue($asset_type->validate(constants::ASSET_TYPE_VIDEO));
        self::assertTrue($asset_type->validate(constants::ASSET_TYPE_LEARNING_PATH));

        self::assertFalse($asset_type->validate(strtolower(constants::ASSET_TYPE_COURSE)));
        self::assertFalse($asset_type->validate(strtolower(constants::ASSET_TYPE_CHAPTER)));
        self::assertFalse($asset_type->validate(strtolower(constants::ASSET_TYPE_VIDEO)));
        self::assertFalse($asset_type->validate(strtolower(constants::ASSET_TYPE_LEARNING_PATH)));

        self::assertFalse($asset_type->validate(129.2));
        self::assertFalse($asset_type->validate(false));
        self::assertFalse($asset_type->validate('288202'));
        self::assertFalse($asset_type->validate('mkkjo'));
    }
}