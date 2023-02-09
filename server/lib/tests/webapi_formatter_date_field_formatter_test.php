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
use core\webapi\formatter\field\date_field_formatter;

defined('MOODLE_INTERNAL') || die();

class core_webapi_formatter_date_field_formatter_testcase extends basic_testcase {

    public function test_formats() {
        $formats = date_format::get_available();
        $context = context_system::instance();
        $time = 1561672231;

        $expected_values = [
            date_format::FORMAT_ISO8601 => '2019-06-28T05:50:31+0800',
            date_format::FORMAT_TIMESTAMP => '1561672231',
            date_format::FORMAT_DAYDATETIME => 'Friday, 28 June 2019, 5:50 AM',
            date_format::FORMAT_TIME => '5:50 AM',
            date_format::FORMAT_TIMESHORT => '05:50',
            date_format::FORMAT_DATE => '28 June 2019',
            date_format::FORMAT_DATESHORT => '28 June',
            date_format::FORMAT_DATELONG => '28/06/2019',
            date_format::FORMAT_DATETIME => '28 June 2019, 5:50 AM',
            date_format::FORMAT_DATETIMESHORT => '28/06/19, 05:50',
            date_format::FORMAT_DATETIMELONG => '28/06/2019, 05:50',
            date_format::FORMAT_DATETIMESECONDS => '28 Jun 2019 at 05:50:31'
        ];

        foreach ($formats as $format) {
            $formatter = new date_field_formatter($format, $context);
            $result = $formatter->format($time);
            $this->assertSame($expected_values[$format], $result, "Wrong format for $format format");
        }
    }

    public function test_unknown_format() {
        $formatter = new date_field_formatter('foo', context_system::instance());

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Invalid format given');

        $formatter->format(time());
    }

    public function test_null_value() {
        $formats = date_format::get_available();
        $context = context_system::instance();

        foreach ($formats as $format) {
            $formatter = new date_field_formatter($format, $context);
            $result = $formatter->format(null);
            $this->assertNull($result, "Wrong format for $format format");
        }
    }

}
