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
 * @author  Cody Finegan <cody.finegan@totaralearning.com>
 * @package totara_notification
 */

use core_phpunit\testcase;
use totara_notification\schedule\schedule_on_event;

/**
 * Test cases covering the totara_notification\schedule\schedule_on_event class
 */
class totara_notification_schedule_on_event_test extends testcase {

    /**
     * Check that timestamps are calculated correctly.
     */
    public function test_calculate_timestamp() {
        // Use a fixed timestamp for our tests
        $base_timestamp = 584217720;

        $result = schedule_on_event::calculate_timestamp($base_timestamp, -6);
        self::assertEquals($base_timestamp, $result, "Offset -6");

        $result = schedule_on_event::calculate_timestamp($base_timestamp, 9);
        self::assertEquals($base_timestamp, $result, "Offset -9");
    }

    /**
     * Test the correct label is returned for the specified offsets
     */
    public function test_get_label() {
        $this->assertEquals(
            get_string('schedule_label_on_event', 'totara_notification', 1),
            schedule_on_event::get_label(1),
        );

        $this->assertEquals(
            get_string('schedule_label_on_event', 'totara_notification', 5),
            schedule_on_event::get_label(5),
        );
    }

    /**
     * Test validations are handled
     */
    public function test_validate_offset() {
        $test_cases = [
            [0, true],
            [-10, false],
            [5, false],
        ];

        foreach ($test_cases as [$value, $expected_result]) {
            self::assertEquals($expected_result, schedule_on_event::validate_offset($value), $value);
        }
    }
}