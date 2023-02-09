<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package core_dataformat
 */

defined('MOODLE_INTERNAL') || die();

use core_phpunit\testcase;

/**
 * Test the dataformatlib functinos
 */
class core_dataformatlib_testcase extends testcase {
    /**
     * Assert that the download_as_dataformat function properly reports html types to the provided callbacks
     */
    public function test_callback_provided_html_flag() {
        global $CFG;
        require_once $CFG->libdir . "/tests/fixtures/dataformatlib_fixtures.php";
        require_once $CFG->libdir . "/dataformatlib.php";

        $with_html = null;
        $callback = function ($one, $two) use (&$with_html) {
            $with_html = $two;
            return array();
        };

        $mock_array = new ArrayObject(array(1, 2, 3, 4, 5));

        // Assert that when we call a non-HTML dataformat, the callback indicates non-HTML support
        download_as_dataformat('test', 'mock', array(), $mock_array->getIterator(), $callback);
        self::assertFalse($with_html);

        // Assert that when we call a HTML dataformat, the callback indicates HTML support
        $with_html = null;
        download_as_dataformat('test', 'mock_html', array(), $mock_array->getIterator(), $callback);
        self::assertTrue($with_html);
    }
}