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

use core\webapi\param\path;

class core_webapi_param_path_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_parse_valid_value(): void {
        self::assertEquals('/hello/world', path::parse_value('/hello/world'));
        self::assertEquals('/', path::parse_value('/'));
        self::assertNull(path::parse_value(''));
        self::assertNull(path::parse_value(null));
    }

    /**
     * @return void
     */
    public function test_parse_invalid_value(): void {
        $invalid_values = [
            '/file/<script>alert("/another/filepath")</script>',
            '/../../hello_world.png',
            false,
            true,
            0,
            42,
            0.11,
            1.00,
            '//file/heloo_World',
            '/./././file//file'
        ];

        foreach ($invalid_values as $invalid_value) {
            try {
                path::parse_value($invalid_value);
            } catch (invalid_parameter_exception $e) {
                self::assertStringContainsString(
                    get_string('invalidparameter', 'debug'),
                    $e->getMessage()
                );

                continue;
            }

            self::fail(
                "Expecting an exception of " . invalid_parameter_exception::class .
                " to be thrown, but none was thrown for value '{$invalid_value}'"
            );
        }
    }
}