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
use contentmarketplace_linkedin\core_json\data_format\difficulty_level;
use core_phpunit\testcase;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_json_data_format_difficulty_level_testcase extends testcase {
    /**
     * @return void
     */
    public function test_validation(): void {
        $difficulty_level = new difficulty_level();

        self::assertTrue($difficulty_level->validate(constants::DIFFICULTY_LEVEL_BEGINNER));
        self::assertTrue($difficulty_level->validate(constants::DIFFICULTY_LEVEL_INTERMEDIATE));
        self::assertTrue($difficulty_level->validate(constants::DIFFICULTY_LEVEL_ADVANCED));

        self::assertFalse($difficulty_level->validate(strtolower(constants::DIFFICULTY_LEVEL_BEGINNER)));
        self::assertFalse($difficulty_level->validate(strtolower(constants::DIFFICULTY_LEVEL_INTERMEDIATE)));
        self::assertFalse($difficulty_level->validate(strtolower(constants::DIFFICULTY_LEVEL_ADVANCED)));

        self::assertFalse($difficulty_level->validate('xcx'));
        self::assertFalse($difficulty_level->validate(991));
        self::assertFalse($difficulty_level->validate('intermediate'));
    }
}