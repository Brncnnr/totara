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
use totara_notification\local\schedule_helper;
use totara_notification\schedule\schedule_after_event;

/**
 * Test cases covering the totara_notification\schedule\schedule_after_event class
 */
class totara_notification_schedule_after_event_test extends testcase {

    /**
     * Check that timestamps are calculated correctly.
     */
    public function test_calculate_timestamp() {
        // Use a fixed timestamp for our tests
        $base_timestamp = 584217720;

        $result = schedule_after_event::calculate_timestamp($base_timestamp, schedule_helper::days_to_seconds(5));
        self::assertEquals(584649720, $result, "Offset +5 days");

        $result = schedule_after_event::calculate_timestamp($base_timestamp, schedule_helper::days_to_seconds(26));
        self::assertEquals(586464120, $result, "Offset +26 days");

        // Check for the exception
        self::expectExceptionMessage('Schedule after event must have a offset greater than zero');
        self::expectException(\coding_exception::class);
        schedule_after_event::calculate_timestamp($base_timestamp, schedule_helper::days_to_seconds(-10));
    }

    /**
     * Test the correct label is returned for the specified offsets
     */
    public function test_get_label() {
        // Assert the singular & double come back correctly
        $this->assertEquals(
            get_string('schedule_label_after_event_singular', 'totara_notification', 1),
            schedule_after_event::get_label(schedule_helper::days_to_seconds(1)),
        );

        $this->assertEquals(
            get_string('schedule_label_after_event', 'totara_notification', 5),
            schedule_after_event::get_label(schedule_helper::days_to_seconds(5)),
        );
    }

    /**
     * Test the default value is correctly calculated
     */
    public function test_default_value() {
        $result = schedule_after_event::default_value(10);
        self::assertEquals(10 * DAYSECS, $result);

        self::expectExceptionMessage('Schedule After Event must have had a days_offset provided');
        self::expectException(\coding_exception::class);
        schedule_after_event::default_value(-10);
    }

    /**
     * Test validations are handled
     */
    public function test_validate_offset() {
        $test_cases = [
            [0, false],
            [-10, false],
            [5, true],
        ];

        foreach ($test_cases as [$value, $expected_result]) {
            self::assertEquals($expected_result, schedule_after_event::validate_offset($value), $value);
        }
    }
}