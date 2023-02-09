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

class core_webapi_param_alphanum_testcase extends basic_testcase {
    public function test_parse_value() {
        $this->assertSame('Alpha09', \core\webapi\param\alphanum::parse_value('Alpha09'));
        $this->assertSame('09', \core\webapi\param\alphanum::parse_value('09'));
        $this->assertSame('9', \core\webapi\param\alphanum::parse_value('9'));
        $this->assertSame('', \core\webapi\param\alphanum::parse_value(''));

        $this->assertNull(\core\webapi\param\alphanum::parse_value(null));

        $invalids = [' ', 'Alpha09_', 'Alpha09_', 'Alpha09-', 'Alpha09/', 'Alpha.', false, true, 9.9];
        foreach ($invalids as $invalid) {
            $message = 'invalid_parameter_exception exception expected for value: ' . var_export($invalid, true);
            try {
                \core\webapi\param\alphanum::parse_value($invalid);
                $this->fail($message);
            } catch (moodle_exception $e) {
                $this->assertInstanceOf('invalid_parameter_exception', $e, $message);
            }
        }
    }
}