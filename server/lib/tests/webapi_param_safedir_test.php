<?php
/*
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package core
 */

class core_webapi_param_safedir_testcase extends basic_testcase {
    public function test_parse_value() {
        $this->assertSame('safedir', \core\webapi\param\safedir::parse_value('safedir'));
        $this->assertSame('_Safe-dir2_', \core\webapi\param\safedir::parse_value('_Safe-dir2_'));
        $this->assertSame('3safedir', \core\webapi\param\safedir::parse_value('3safedir'));
        $this->assertSame('3', \core\webapi\param\safedir::parse_value(3));
        $this->assertSame('0', \core\webapi\param\safedir::parse_value(0));

        $this->assertNull(\core\webapi\param\safedir::parse_value(''));
        $this->assertNull(\core\webapi\param\safedir::parse_value(null));

        $invalids = ['.', '..', '/' , ' ' , 'safedir ', 'safedir/', 'safedir.', false, true, 1.1];
        foreach ($invalids as $invalid) {
            $message = 'invalid_parameter_exception exception expected for value: ' . var_export($invalid, true);
            try {
                \core\webapi\param\safedir::parse_value($invalid);
                $this->fail($message);
            } catch (moodle_exception $e) {
                $this->assertInstanceOf('invalid_parameter_exception', $e, $message);
            }
        }
    }
}