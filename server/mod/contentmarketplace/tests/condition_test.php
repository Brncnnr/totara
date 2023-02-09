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
 * @package mod_contentmarketplace
 */

use core_phpunit\testcase;
use mod_contentmarketplace\completion\condition;
use totara_contentmarketplace\plugininfo\contentmarketplace;

/**
 * @group totara_contentmarketplace
 */
class mod_contentmarketplace_condition_testcase extends testcase {
    /**
     * Returns the data provided for test checking validity.
     *
     * @internal
     * @return array
     */
    public static function provideConditionValue(): array {
        return [
            [condition::CONTENT_MARKETPLACE, true],
            [3, false],
            [42, false]
        ];
    }

    /**
     * @dataProvider provideConditionValue
     *
     * @param int $value
     * @param bool $result
     *
     * @return void
     */
    public function test_is_valid(int $value, bool $result): void {
        self::assertEquals($result, condition::is_valid($value));
    }

    /**
     * @return void
     */
    public function test_get_content_marketplace_conditions_string(): void {
        $plugin_info = contentmarketplace::plugin('linkedin');
        self::assertEquals(
            get_string("completion_content_provider_description", "mod_contentmarketplace", $plugin_info->displayname),
            condition::get_content_marketplace_conditions_string("contentmarketplace_linkedin")
        );
    }

    /**
     * @return void
     */
    public function test_get_contentmarketplace_conditions_string_with_error(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Unknown content marketplace plugin requested: 'invalid'");

        condition::get_content_marketplace_conditions_string("contentmarketplace_invalid");
    }

    /**
     * @return void
     */
    public function test_validate_invalid_case(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The completion condition is invalid");

        condition::validate(42);
    }
}