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

class core_webapi_scalar_id_testcase extends basic_testcase {
    public function test_parse_value() {
        $this->assertSame(1, \core\webapi\scalar\id::parse_value(1));
        $this->assertSame(2, \core\webapi\scalar\id::parse_value('2'));
        $this->assertSame(PHP_INT_MAX, \core\webapi\scalar\id::parse_value(PHP_INT_MAX));
        $this->assertSame(PHP_INT_MAX, \core\webapi\scalar\id::parse_value((string)PHP_INT_MAX));

        $this->assertNull(\core\webapi\scalar\id::parse_value(0));
        $this->assertNull(\core\webapi\scalar\id::parse_value('0'));
        $this->assertNull(\core\webapi\scalar\id::parse_value(''));
        $this->assertNull(\core\webapi\scalar\id::parse_value(null));

        $invalids = [' ', ' 1', '1 ', '-1', -1, true, false, 1.0];
        foreach ($invalids as $invalid) {
            $message = 'invalid_parameter_exception exception expected for value: ' . var_export($invalid, true);
            try {
                \core\webapi\scalar\id::parse_value($invalid);
                $this->fail($message);
            } catch (moodle_exception $e) {
                $this->assertInstanceOf('invalid_parameter_exception', $e, $message);
            }
        }
    }

    public function test_serialize() {
        $this->assertSame('1', \core\webapi\scalar\id::serialize(1));
        $this->assertSame('2', \core\webapi\scalar\id::serialize('2'));
        $this->assertSame((string)PHP_INT_MAX, \core\webapi\scalar\id::serialize(PHP_INT_MAX));
        $this->assertSame((string)PHP_INT_MAX, \core\webapi\scalar\id::serialize((string)PHP_INT_MAX));

        $this->assertNull(\core\webapi\scalar\id::serialize(-2));
        $this->assertNull(\core\webapi\scalar\id::serialize(0));
        $this->assertNull(\core\webapi\scalar\id::serialize('0'));
        $this->assertNull(\core\webapi\scalar\id::serialize(''));
        $this->assertNull(\core\webapi\scalar\id::serialize(null));
    }
}