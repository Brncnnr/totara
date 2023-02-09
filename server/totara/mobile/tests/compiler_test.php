<?php
/*
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package totara_mobile
 */

use totara_mobile\language\compiler;

defined('MOODLE_INTERNAL') || die();

class totara_mobile_compiler_testcase extends advanced_testcase {

    public function test_compiler_handles_strings_correctly() {
        global $CFG;
        require_once($CFG->dirroot . '/totara/mobile/tests/fixtures/fake_strings.php');
        $source = new fake_strings();
        $source->filter_prefix('app:');
        $result = compiler::instance($source)->get_json();
        $this->assertEquals('{"app":{"my-learning":{"action_primary":"A","primary_info":{"zero":"B","one":"C {{count}} D","other":"E"},"no_learning_message":"F"}}}', $result);
    }
}