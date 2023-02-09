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
 * @author  Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Test of changes in lib/setuplib.php
 */
class totara_core_setuplib_testcase extends advanced_testcase {
    public function test_moodle_exception_constructor() {
        $exception = new moodle_exception('erroronline');
        $this->assertSame('erroronline', $exception->errorcode);
        $this->assertSame('error', $exception->module);
        $this->assertSame('', $exception->link);
        $this->assertSame(null, $exception->a);
        $this->assertSame(null, $exception->debuginfo);
        $this->assertSame('Error on line {$a}', $exception->getMessage());

        $exception = new moodle_exception('erroronline', 'moodle', '/x/y/', 'grr', 'lalala');
        $this->assertSame('erroronline', $exception->errorcode);
        $this->assertSame('error', $exception->module);
        $this->assertSame('/x/y/', $exception->link);
        $this->assertSame('grr', $exception->a);
        $this->assertSame('lalala', $exception->debuginfo);
        $this->assertSame('Error on line grr (lalala)', $exception->getMessage());

        $exception = new moodle_exception('weird', 'stuff');
        $this->assertSame('weird', $exception->errorcode);
        $this->assertSame('stuff', $exception->module);
        $this->assertSame('', $exception->link);
        $this->assertSame(null, $exception->a);
        $this->assertSame(null, $exception->debuginfo);
        $this->assertSame("stuff/weird".PHP_EOL."\$a contents: ", $exception->getMessage());

        $exception = new moodle_exception('This is so wrong, let\'s not code like <this>', 'book', '/yyy/', 'grrr', 'ohlala');
        $this->assertSame('notlocalisederrormessage', $exception->errorcode);
        $this->assertSame('error', $exception->module);
        $this->assertSame('/yyy/', $exception->link);
        $this->assertSame('This is so wrong, let&#039;s not code like &lt;this&gt;', $exception->a);
        $this->assertSame('ohlala', $exception->debuginfo);
        $this->assertSame('This is so wrong, let&#039;s not code like &lt;this&gt; (ohlala)', $exception->getMessage());
    }
}
