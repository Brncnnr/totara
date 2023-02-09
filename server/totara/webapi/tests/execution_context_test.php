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
 * @package totara_webapi
 */

use core\webapi\execution_context;
use totara_webapi\endpoint_type\ajax;
use totara_webapi\endpoint_type\dev;
use totara_webapi\query_complexity_exception;

defined('MOODLE_INTERNAL') || die();

class totara_webapi_execution_context_testcase extends advanced_testcase {
    public function test_create() {
        $ec = execution_context::create('ajax', 'core_lang_strings_nosession');
        $this->assertInstanceOf(execution_context::class, $ec);
        $this->assertSame('core_lang_strings_nosession', $ec->get_operationname());
        $this->assertSame('ajax', $ec->get_type());
        $this->assertInstanceOf(ajax::class, $ec->get_endpoint_type());

        $devec = execution_context::create('dev', null);
        $this->assertInstanceOf(execution_context::class, $devec);
        $this->assertSame(null, $devec->get_operationname());
        $this->assertSame('dev', $devec->get_type());
        $this->assertInstanceOf(dev::class, $devec->get_endpoint_type());
    }

    public function test_format_core_date() {
        $debugging_msg = 'format_core_date() in execution_context is deprecated, please use the new \core\webapi\formatter\field\date_field_formatter class';

        $time = 123456;
        $ec = execution_context::create('dev', null);
        $this->assertSame('123456', $ec->format_core_date($time, []));
        $this->assertDebuggingCalled($debugging_msg);
        $this->assertSame('123456', $ec->format_core_date($time, ['format' => 'TIMESTAMP']));
        $this->assertDebuggingCalled($debugging_msg);
        $this->assertSame('1970-01-02T18:17:36+0800', $ec->format_core_date($time, ['format' => 'ISO8601']));
        $this->assertDebuggingCalled($debugging_msg);
        $this->assertSame('Friday, 2 January 1970, 6:17 PM', $ec->format_core_date($time, ['format' => 'DAYDATETIME']));
        $this->assertDebuggingCalled($debugging_msg);
        $this->assertSame('6:17 PM', $ec->format_core_date($time, ['format' => 'TIME']));
        $this->assertDebuggingCalled($debugging_msg);
        $this->assertSame('18:17', $ec->format_core_date($time, ['format' => 'TIMESHORT']));
        $this->assertDebuggingCalled($debugging_msg);
        $this->assertSame('2 January 1970', $ec->format_core_date($time, ['format' => 'DATE']));
        $this->assertDebuggingCalled($debugging_msg);
        $this->assertSame('2 January', $ec->format_core_date($time, ['format' => 'DATESHORT']));
        $this->assertDebuggingCalled($debugging_msg);
        $this->assertSame('2/01/1970', $ec->format_core_date($time, ['format' => 'DATELONG']));
        $this->assertDebuggingCalled($debugging_msg);
        $this->assertSame('2 January 1970, 6:17 PM', $ec->format_core_date($time, ['format' => 'DATETIME']));
        $this->assertDebuggingCalled($debugging_msg);
        $this->assertSame('2/01/70, 18:17', $ec->format_core_date($time, ['format' => 'DATETIMESHORT']));
        $this->assertDebuggingCalled($debugging_msg);
        $this->assertSame('2/01/1970, 18:17', $ec->format_core_date($time, ['format' => 'DATETIMELONG']));
        $this->assertDebuggingCalled($debugging_msg);
        $this->assertSame('2 Jan 1970 at 18:17:36', $ec->format_core_date($time, ['format' => 'DATETIMESECONDS']));
        $this->assertDebuggingCalled($debugging_msg);
    }

    public function test_format_text() {
        $debugging_msg = 'format_text() in execution_context is deprecated, please use the new \core\webapi\formatter\field\text_field_formatter class';

        $ec = execution_context::create('dev', null);
        $this->assertSame(null, $ec->format_text(null));
        $this->assertDebuggingCalled($debugging_msg);
        $this->assertSame('', $ec->format_text('<script>alert(1)</script>', FORMAT_HTML));
        $this->assertDebuggingCalled($debugging_msg);
        $this->assertSame('<script>alert(1)</script>', $ec->format_text('<script>alert(1)</script>', FORMAT_HTML, ['allowxss' => true]));
        $this->assertDebuggingCalled($debugging_msg);
        $this->assertSame('&lt;script&gt;alert(1)&lt;/script&gt;', $ec->format_text('<script>alert(1)</script>', FORMAT_PLAIN));
        $this->assertDebuggingCalled($debugging_msg);
        $this->assertSame('<h1>title</h1>' . "\n", $ec->format_text('#title', FORMAT_MARKDOWN));
        $this->assertDebuggingCalled($debugging_msg);
    }

    /**
     * @return void
     */
    public function test_increment_query_complexity_cost(): void {
        set_config('max_query_complexity',10, 'totara_api');
        $ec = execution_context::create('dev', null);
        $ec->increment_query_complexity_cost(2);
        self::assertEquals(2, $ec->get_query_complexity_cost());

        self::expectException(query_complexity_exception::class);
        $ec->increment_query_complexity_cost(12);
    }
}