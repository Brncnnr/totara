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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package core
 */

use core\date_format;

defined('MOODLE_INTERNAL') || die();

class core_date_format_testcase extends basic_testcase {

    public function test_constants() {
        $reflection = new ReflectionClass(date_format::class);
        $constants = $reflection->getConstants();

        $format_constants = [];
        foreach ($constants as $constant => $value) {
            if (strpos($constant, 'FORMAT_') === 0) {
                $format_constants[] = $value;
                $this->assertTrue(date_format::is_defined($value), "Format $value not defined?");
            }
        }

        // All constants should be part of the available array
        $this->assertEqualsCanonicalizing($format_constants, date_format::get_available());
    }

    public function test_lang_strings() {
        $available = date_format::get_available();

        foreach ($available as $format) {
            // except for timestamp and iso8601 which do not have langstrings
            if (in_array($format, [date_format::FORMAT_TIMESTAMP, date_format::FORMAT_ISO8601])) {
                continue;
            }

            $string = date_format::get_lang_string($format);
            $this->assertNotEmpty($string);

            // Test if there's a language string for the format
            $lang_string = get_string($string, 'langconfig');
            $this->assertNotEquals("[[{$string}]]", $lang_string, "Missing lang string for date format '$format'");
        }
    }

    public function test_unknown_lang_string() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('No language string mapping defined for format qwerty');
        date_format::get_lang_string('qwerty');
    }

}
