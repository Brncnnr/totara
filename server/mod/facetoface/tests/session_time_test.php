<?php
/*
 * This file is part of Totara LMS
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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();

use mod_facetoface\output\session_time;

/**
 * A unit test for the session_time output class
 *
 */
class mod_facetoface_session_time_testcase extends advanced_testcase {
    public function test_format_duration() {
        $tests = [
            '+2 hours' => '2 hours',
            '+7 hours 55 minutes' => '7 hours 55 mins',
            '+8 hours 5 minutes' => '1 day',
            '+8 hours' => '1 day',
            '+23 hours 55 minutes' => '1 day',
            '+24 hours' => '1 day',
            '+25 hours' => '1 day 1 hour',
            '+4 days' => '4 days',
            '+4 days 5 minutes' => '4 days',
            '+4 days 7 hours' => '4 days 7 hours',
            '+4 days 8 hours' => '5 days',
            '+4 days 8 hours 5 minutes' => '5 days',
        ];

        $timestart = time();
        foreach ($tests as $test => $expected) {
            $timefinish = strtotime($test, $timestart);
            $this->assertEquals(session_time::format_duration($timestart, $timefinish), $expected, $test);
        }
    }
}