<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @package totara_engage
 */
defined('MOODLE_INTERNAL') || die();

use totara_engage\resource\input\access_validator;
use totara_engage\access\access;

class totara_engage_access_validator_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_valid_input(): void {
        $validator = new access_validator();
        $this->assertTrue($validator->is_valid(access::PRIVATE));
        $this->assertTrue($validator->is_valid(access::PUBLIC));
        $this->assertTrue($validator->is_valid(access::RESTRICTED));
    }

    /**
     * @return void
     */
    public function test_invalid_input(): void {
        $validator = new access_validator();

        $this->assertFalse($validator->is_valid('heloo'));
        $this->assertFalse($validator->is_valid(123));
        $this->assertFalse($validator->is_valid(1.5));
        $this->assertFalse($validator->is_valid(false));

        $this->assertDebuggingCalledCount(3);
    }
}