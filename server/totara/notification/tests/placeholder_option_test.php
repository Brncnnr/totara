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
 * @package totara_notification
 */

use core_phpunit\testcase;
use totara_notification\placeholder\option;

class totara_notification_placeholder_option_testcase extends testcase {
    /**
     * @return void
     */
    public function test_create_option_from_invalid_key(): void {
        $invalid_keys = [
            'sometext+',
            'sometext~',
            'text+x',
            'bolobala:',
        ];

        foreach ($invalid_keys as $invalid_key) {
            try {
                option::create($invalid_key, 'Boom');
                self::fail("Expecting a coding_exception to be thrown for invalid key '{$invalid_key}'");
            } catch (coding_exception $e) {
                self::assertStringContainsString(
                    "The key '{$invalid_key}' contains illegal character(s)",
                    $e->getMessage()
                );
            }
        }
    }

    /**
     * @return void
     */
    public function test_create_option_from_valid_key(): void {
        $valid_keys = [
            'firstname',
            'first_name',
            'commenter_author:firstname',
            'commenter:first_name',
            'commenter:101_f',
            'data_101:111_cc',
            'cc_11',
            '189u',
            '1892:90_kp',
        ];

        foreach ($valid_keys as $valid_key) {
            $option = option::create($valid_key, 'Boom');
            self::assertEquals($valid_key, $option->get_key());
            self::assertEquals('Boom', $option->get_label());
        }
    }
}