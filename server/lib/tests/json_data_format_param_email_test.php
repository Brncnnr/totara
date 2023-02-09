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
 * @package core
 */

use core\json\data_format\param_email;
use core_phpunit\testcase;

class core_json_data_format_param_email_testcase extends testcase {
    /**
     * @return void
     */
    public function test_validate(): void {
        $format = new param_email();

        self::assertTrue($format->validate('abcd_123@example.com'));
        self::assertTrue($format->validate('abcd123@example.com'));
        self::assertTrue($format->validate('abcd-123@example.com'));
        self::assertTrue($format->validate('abcd~123@example.com'));

        self::assertFalse($format->validate('abcd123@example@com'));
        self::assertFalse($format->validate('abcd12[3]@example.com'));
        self::assertFalse($format->validate('abcd12(3)@example.com'));
        self::assertFalse($format->validate('abcd12(3)@example.com'));

        // Sadly our email validation does not accept localised email
        self::assertFalse($format->validate('朝@example.com'));
        self::assertFalse($format->validate('아침@example.com'));
        self::assertFalse($format->validate('buổi_sáng@example.com'));
    }
}