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

class core_webapi_param_boolean_testcase extends basic_testcase {
    public function test_parse_value() {
        $this->assertSame(1, \core\webapi\param\boolean::parse_value(true));
        $this->assertSame(1, \core\webapi\param\boolean::parse_value('1'));
        $this->assertSame(1, \core\webapi\param\boolean::parse_value(1));
        $this->assertSame(1, \core\webapi\param\boolean::parse_value('on'));
        $this->assertSame(1, \core\webapi\param\boolean::parse_value('yes'));
        $this->assertSame(1, \core\webapi\param\boolean::parse_value('true'));
        $this->assertSame(0, \core\webapi\param\boolean::parse_value(false));
        $this->assertSame(0, \core\webapi\param\boolean::parse_value('0'));
        $this->assertSame(0, \core\webapi\param\boolean::parse_value(0));
        $this->assertSame(0, \core\webapi\param\boolean::parse_value('off'));
        $this->assertSame(0, \core\webapi\param\boolean::parse_value('no'));
        $this->assertSame(0, \core\webapi\param\boolean::parse_value('false'));

        $this->assertNull(\core\webapi\param\boolean::parse_value(''));
        $this->assertNull(\core\webapi\param\boolean::parse_value(null));

        $invalids = [-1, 2, 'ano', 'ne', 1.1, 0.0];
        foreach ($invalids as $invalid) {
            $message = 'invalid_parameter_exception exception expected for value: ' . var_export($invalid, true);
            try {
                \core\webapi\param\boolean::parse_value($invalid);
                $this->fail($message);
            } catch (moodle_exception $e) {
                $this->assertInstanceOf('invalid_parameter_exception', $e, $message);
            }
        }
    }
}