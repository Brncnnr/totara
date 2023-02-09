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

use core\json\data_format\param_alphanumext;
use core_phpunit\testcase;

class core_json_data_format_param_alphanumext_testcase extends testcase {
    /**
     * @return void
     */
    public function test_validate(): void {
        $format = new param_alphanumext();

        self::assertTrue($format->validate('abcde'));
        self::assertTrue($format->validate('abcde123'));
        self::assertTrue($format->validate('abcde_123'));
        self::assertTrue($format->validate('abcde-123'));
        self::assertTrue($format->validate('abc_de-123'));
        self::assertFalse($format->validate([]));
        self::assertFalse($format->validate(1.1));
        self::assertFalse($format->validate(1));
        self::assertFalse($format->validate('abcde hell 123'));
        self::assertFalse($format->validate('abcde~2123_9i390'));
    }
}