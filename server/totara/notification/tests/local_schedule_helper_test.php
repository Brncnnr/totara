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
use totara_notification\schedule\schedule_before_event;
use totara_notification\schedule\schedule_on_event;
use totara_notification\testing\generator;
use totara_notification_mock_notifiable_event_resolver as mock_resolver;
use totara_notification_mock_scheduled_aware_event_resolver as mock_scheduled_resolver;

class totara_notification_local_schedule_helper_testcase extends testcase {
    /**
     * @return void
     */
    protected function setUp(): void {
        $generator = generator::instance();
        $generator->include_mock_notifiable_event_resolver();
        $generator->include_mock_scheduled_aware_notifiable_event_resolver();
    }


    /**
     * @return void
     */
    public function test_get_available_schedules_for_notifiable_event_resolver(): void {
        $expected = ['ON_EVENT'];

        $schedules = schedule_helper::get_available_schedules_for_resolver(mock_resolver::class);
        self::assertSame($expected, $schedules);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Resolver class is not a valid resolver');

        schedule_helper::get_available_schedules_for_resolver('invalid_event');
    }

    /**
     * @return void
     */
    public function test_get_available_schedules_for_scheduled_aware_event(): void {
        $expected = [
            'BEFORE_EVENT',
            'AFTER_EVENT',
        ];


        $schedules = schedule_helper::get_available_schedules_for_resolver(mock_scheduled_resolver::class);
        self::assertSame($expected, $schedules);
    }

    /**
     * @return void
     */
    public function test_get_schedule_class_from_offset(): void {
        $test_cases = [
            -5 => schedule_before_event::class,
            0 => schedule_on_event::class,
            10 => schedule_after_event::class,
        ];

        foreach ($test_cases as $offset => $expected) {
            self::assertSame($expected, schedule_helper::get_schedule_class_from_offset($offset));
        }
    }

    /**
     * @return void
     */
    public function test_get_schedule_class_from_type(): void {
        $test_cases = [
            'ON_EVENT' => schedule_on_event::class,
            'BEFORE_EVENT' => schedule_before_event::class,
            'AFTER_EVENT' => schedule_after_event::class,
        ];

        foreach ($test_cases as $type => $expected) {
            self::assertSame($expected, schedule_helper::get_schedule_class_from_type($type));
        }

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Unknown schedule type of 'invalid_type' provided");

        schedule_helper::get_schedule_class_from_type('invalid_type');
    }

    /**
     * @return void
     */
    public function test_get_human_readable_schedule_label(): void {
        $test_cases = [
            (-1 * DAYSECS) => get_string('schedule_label_before_event_singular', 'totara_notification', 1),
            (-5 * DAYSECS) => get_string('schedule_label_before_event', 'totara_notification', 5),
            0 => get_string('schedule_label_on_event', 'totara_notification', 0),
            (1 * DAYSECS) => get_string('schedule_label_after_event_singular', 'totara_notification', 1),
            (5 * DAYSECS) => get_string('schedule_label_after_event', 'totara_notification', 5),
        ];

        foreach ($test_cases as $offset => $expected) {
            self::assertSame($expected, schedule_helper:: get_human_readable_schedule_label($offset));
        }
    }

    /**
     * @return void
     */
    public function test_get_schedule_identifier(): void {
        $test_cases = [
            -5 => 'BEFORE_EVENT',
            0 => 'ON_EVENT',
            10 => 'AFTER_EVENT',
        ];

        foreach ($test_cases as $offset => $expected) {
            self::assertSame($expected, schedule_helper::get_schedule_identifier($offset));
        }
    }

    /**
     * @return void
     */
    public function test_calculate_schedule_timestamp(): void {
        $base_timestamp = 1614732640;

        $test_cases = [
            (-5 * DAYSECS) => 1614300640,
            (0 * DAYSECS) => 1614732640,
            (5 * DAYSECS) => 1615164640,
        ];

        foreach ($test_cases as $offset => $expected) {
            self::assertSame($expected, schedule_helper::calculate_schedule_timestamp($base_timestamp, $offset));
        }
    }

    /**
     * @return void
     */
    public function test_convert_schedule_offset_for_storage(): void {
        $test_cases = [
            -(5 * DAYSECS) => ['BEFORE_EVENT', 5],
            0 => ['ON_EVENT', 0],
            (5 * DAYSECS) => ['AFTER_EVENT', 5],
        ];

        foreach ($test_cases as $expected => $test_data) {
            self::assertSame($expected, schedule_helper::convert_schedule_offset_for_storage(...$test_data));
        }
    }

    /**
     * @return void
     */
    public function test_convert_days_to_seconds(): void {
        self::assertEquals((24 * 60 * 60 * 5), schedule_helper::days_to_seconds(5));
        self::assertEquals((24 * 60 * 60 * -5), schedule_helper::days_to_seconds(-5));
    }

    /**
     * @return void
     */
    public function test_check_schedule_status(): void {
        self::assertFalse(schedule_helper::is_on_event(1));
        self::assertFalse(schedule_helper::is_on_event(2));
        self::assertFalse(schedule_helper::is_on_event(3));
        self::assertTrue(schedule_helper::is_on_event(0));
    }
}