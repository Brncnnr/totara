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

use core\format;

defined('MOODLE_INTERNAL') || die();

class core_format_testcase extends basic_testcase {

    public function test_constants() {
        $reflection = new ReflectionClass(format::class);
        $constants = $reflection->getConstants();

        $format_constants = [];
        foreach ($constants as $constant => $value) {
            if (strpos($constant, 'FORMAT_') === 0) {
                $format_constants[] = $value;
                $this->assertTrue(format::is_defined($value), "Format $value not defined?");
            }
        }

        // All constants should be part of the available array
        $this->assertEqualsCanonicalizing($format_constants, format::get_available());
    }

    /**
     * @return void
     */
    public function test_get_moodle_format() {
        $available = format::get_available();

        foreach ($available as $format) {
            $string = format::get_moodle_format($format);
            $this->assertNotEmpty($string);
        }
        $this->assertEquals(FORMAT_HTML, format::get_moodle_format(format::FORMAT_HTML));
        $this->assertEquals(FORMAT_PLAIN, format::get_moodle_format(format::FORMAT_PLAIN));
        $this->assertEquals(FORMAT_MARKDOWN, format::get_moodle_format(format::FORMAT_MARKDOWN));
        $this->assertEquals(FORMAT_JSON_EDITOR, format::get_moodle_format(format::FORMAT_MOBILE));
        $this->assertEquals(FORMAT_JSON_EDITOR, format::get_moodle_format(format::FORMAT_JSON_EDITOR));
        $this->assertEquals(FORMAT_PLAIN, format::get_moodle_format(format::FORMAT_RAW));

        self::expectException(\coding_exception::class);
        format::get_moodle_format('FORMAT_CREOLE');
    }

    /**
     * @return void
     */
    public function test_from_moodle() {
        $this->assertEquals(format::FORMAT_HTML, format::from_moodle(FORMAT_HTML));
        $this->assertEquals(format::FORMAT_PLAIN, format::from_moodle(FORMAT_PLAIN));
        $this->assertEquals(format::FORMAT_MARKDOWN, format::from_moodle(FORMAT_MARKDOWN));
        $this->assertEquals(format::FORMAT_JSON_EDITOR, format::from_moodle(FORMAT_JSON_EDITOR));
        $this->assertEquals(format::FORMAT_HTML, format::from_moodle(FORMAT_MOODLE));

        self::expectException(\coding_exception::class);
        format::from_moodle(FORMAT_WIKI);
    }
}
