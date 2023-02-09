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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core
 */
defined('MOODLE_INTERNAL') || die();

use core\json_editor\formatter\default_formatter;
use core\json_editor\formatter\formatter_factory;


class core_json_editor_formatter_factory_testcase extends advanced_testcase {
    /**
     * This test is to make sure that if any developers change the behaviour of factory method will have to update
     * this test accordingly.
     *
     * @return void
     */
    public function test_get_default_formatter(): void {
        $formatter = formatter_factory::create_formatter();
        $this->assertInstanceOf(default_formatter::class, $formatter);
    }

    /**
     * @return void
     */
    public function test_get_invalid_formatter(): void {
        $formatter = formatter_factory::create_formatter('something_invalid');

        $messages = $this->getDebuggingMessages();
        $this->assertDebuggingCalled();

        $this->assertInstanceOf(default_formatter::class, $formatter);
        $message = reset($messages);

        $this->assertEquals(
            "The json editor formatter for component 'something_invalid' is not found, make sure " .
            "that the class '\\something_invalid\\json_editor\\formatter\\formatter' exist within the system",
            $message->message
        );
    }
}