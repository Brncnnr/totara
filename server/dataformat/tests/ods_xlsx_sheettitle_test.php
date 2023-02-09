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
 * @author Vernon Denny <vernon.denny@totaralearning.com>
 * @package core_dataformat
 */
defined('MOODLE_INTERNAL') || die();

use dataformat_excel\writer as excel_writer;
use dataformat_ods\writer as ods_writer;

class core_dataformat_ods_xlsx_sheettitle_testcase extends advanced_testcase {
    public function test_core_dataformat_ods_xlsx_sheettitle(): void {
        $ods = new ods_writer();
        $xlsx = new excel_writer();

        $this->validate_sheettitle($ods);
        $this->validate_sheettitle($xlsx);
    }

    private function validate_sheettitle($writer) {
        // Set up reflection.
        $reflection_writer = new ReflectionObject($writer);
        $title_property = $reflection_writer->getProperty('sheettitle');
        $title_property->setAccessible(true);

        // Long title trimmed.
        $writer->set_sheettitle('0123456789012345678901234567890123');
        self::assertEquals(31, strlen($title_property->getValue($writer)));

        // Illegal characters substituted.
        $title = "0123456789[]0123456789";
        $writer->set_sheettitle($title);
        self::assertEquals(strlen($title), strlen($title_property->getValue($writer)));
        self::assertEquals("0123456789  0123456789", $title_property->getValue($writer));

        $title = '0123456789 "0123" 456789';
        $writer->set_sheettitle($title);
        self::assertEquals(strlen($title), strlen($title_property->getValue($writer)));
        self::assertEquals('0123456789  0123  456789', $title_property->getValue($writer));

        // Does not start with single quote.
        $title = "'0123";
        $writer->set_sheettitle($title);
        self::assertEquals(strlen($title) - 1, strlen($title_property->getValue($writer)));
        self::assertEquals("0123", $title_property->getValue($writer));

        // Does not end with single quote.
        $title = "0123'";
        $writer->set_sheettitle($title);
        self::assertEquals(strlen($title) - 1, strlen($title_property->getValue($writer)));
        self::assertEquals("0123", $title_property->getValue($writer));

        // May contain single quote.
        $title = "01'23";
        $writer->set_sheettitle($title);
        self::assertEquals(strlen($title), strlen($title_property->getValue($writer)));
        self::assertEquals("01'23", $title_property->getValue($writer));
    }
}
