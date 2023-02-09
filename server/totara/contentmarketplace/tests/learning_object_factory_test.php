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
 * @package totara_contentmarketplace
 */

use core_phpunit\testcase;
use totara_contentmarketplace\learning_object\factory;

/**
 * @group totara_contentmarketplace
 */
class totara_contentmarketplace_learning_object_factory_testcase extends testcase {
    /**
     * @return void
     */
    public function test_resolve_resolver_class_with_invalid_marketplace_component(): void {
        $invalid_data = [
            'contentmarketplace_abcde',
            'abcde'
        ];

        foreach ($invalid_data as $invalid_datum) {
            try {
                factory::resolve_resolver_class_name($invalid_datum);
                self::fail("Expect processing invalid data '{$invalid_datum}' to yield error");
            } catch (coding_exception $e) {
                self::assertStringContainsString(
                    "Invalid marketplace type '{$invalid_datum}'",
                    $e->getMessage()
                );
            }
        }
    }

    /**
     * @return void
     */
    public function test_check_is_valid_marketplace_component(): void {
        self::assertFalse(factory::is_valid_marketplace_component('linkedin'));
        self::assertFalse(factory::is_valid_marketplace_component('goone'));
        self::assertFalse(factory::is_valid_marketplace_component('abcde'));

        self::assertTrue(factory::is_valid_marketplace_component('contentmarketplace_linkedin'));
        self::assertTrue(factory::is_valid_marketplace_component('contentmarketplace_goone'));
    }

}