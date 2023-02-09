<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core
 */
defined('MOODLE_INTERNAL') || die();

use core\editor\variant_name;

class core_editor_variant_name_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_validate_invalid_name(): void {
        $invalid_data = [
            'wohoo',
            'bolobala',
            'john_elton',
            'vader_darth',
            'big_citi_boi'
        ];

        foreach ($invalid_data as $str) {
            try {
                variant_name::validate($str);
            } catch (coding_exception $e) {
                self::assertStringContainsString(
                    "Invalid variant name '{$str}'",
                    $e->getMessage()
                );
                continue;
            }

            self::fail("An expected exception was not thrown for invalid variant '{$str}'");
        }
    }

    /**
     * This test is to make sure that the function {@see variant_name::is_valid()} is always
     * sync with the constant defined in variant_name.
     *
     * @return void
     */
    public function test_ensure_variant_name_constants_are_sync_with_function(): void {
        $ref_class = new ReflectionClass(variant_name::class);
        $constants = $ref_class->getReflectionConstants();

        foreach ($constants as $ref_constant) {
            self::assertTrue(
                variant_name::is_valid($ref_constant->getValue()),
                "It seems like the function \\core\\editor\\variant_name::is_valid " .
                "is out of sync with the variant name \\core\\editor\\variant_name::{$ref_constant->getName()}"
            );
        }
    }
}