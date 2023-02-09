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

use core\webapi\param\file;

class core_webapi_param_file_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_parse_value_with_valid_data(): void {
        self::assertEquals('file.mp4', file::parse_value('file.mp4'));
        self::assertNull(file::parse_value(null));
        self::assertNull(file::parse_value(''));
    }

    /**
     * @return void
     */
    public function test_parse_value_with_invalid_data() {
        $invalid_values = [
            '<script>alert("file_name.mp4")</script>',
            '..',
            '.',
            '../file_name.mp4',
            'file://filex.exe',
            false,
            0,
            15,
            true,
            42,
            42.22,
            1.00
        ];

        foreach ($invalid_values as $invalid_value) {
            try {
                file::parse_value($invalid_value);
            } catch (invalid_parameter_exception $e) {
                self::assertStringContainsString(
                    get_string('invalidparameter', 'debug'),
                    $e->getMessage()
                );

                continue;
            }

            self::fail(
                "Expecting an exception of " . invalid_parameter_exception::class .
                " to be thrown, but none was for value '{$invalid_value}'"
            );
        }
    }
}