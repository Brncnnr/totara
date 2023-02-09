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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_notification
 */

use totara_core\extended_context;
use totara_notification\local\schedule_helper;
use totara_notification\schedule\time_window;
use totara_notification\testing\generator;
use totara_notification_mock_recipient as mock_recipient;
use totara_notification_mock_scheduled_aware_event_resolver as scheduled_resolver;
use core_phpunit\testcase;

class totara_notification_notification_preference_time_hit_testcase extends testcase {
    /**
     * @return void
     */
    protected function setUp(): void {
        $generator = generator::instance();
        $generator->include_mock_scheduled_aware_notifiable_event_resolver();
        $generator->include_mock_recipient();
    }

    /**
     * @return void
     */
    public function test_check_time_hit_against_scheduled_after_event(): void {
        $generator = generator::instance();
        $now = time();

        // Event time is happened a day ago.
        $event_time = $now - schedule_helper::days_to_seconds(1);
        $preference = $generator->create_notification_preference(
            scheduled_resolver::class,
            extended_context::make_system(),
            [
                'recipient' => mock_recipient::class,
                'recipients' => [mock_recipient::class],
                'schedule_offset' => schedule_helper::days_to_seconds(3),
            ]
        );

        self::assertFalse($preference->is_in_time_window($event_time, new time_window($now, $now)));

        // Move the time now to 3 days up and we will have time hit.
        self::assertTrue(
            $preference->is_in_time_window(
                $event_time,
                new time_window($now, $now + schedule_helper::days_to_seconds(3))
            )
        );
    }


    /**
     * @return void
     */
    public function test_check_time_hit_against_scheduled_before_event(): void {
        $generator = generator::instance();
        $now = time();

        // Event time is happened in 6 days from now.
        $event_time = $now + schedule_helper::days_to_seconds(6);

        // Scheduled to send a notification 3 days before the event time.
        $preference = $generator->create_notification_preference(
            scheduled_resolver::class,
            extended_context::make_system(),
            [
                'recipient' => mock_recipient::class,
                'recipients' => [mock_recipient::class],
                'schedule_offset' => schedule_helper::days_to_seconds(-3),
            ]
        );

        // Time now is not yet up to the 3 days in 6 days event is set for.
        // Hence preference is not hitting with the time yet.
        self::assertFalse($preference->is_in_time_window($event_time, new time_window($now, $now)));

        // Move the time now to 3 days and an hour up and we will have time hit.
        self::assertTrue(
            $preference->is_in_time_window(
                $event_time,
                new time_window($now, $now + schedule_helper::days_to_seconds(3) + HOURSECS)
            )
        );
    }

    /**
     * @return void
     */
    public function test_check_time_hit_against_scheduled_on_event(): void {
        $generator = generator::instance();
        $now = time();

        // Event time is happening now.
        $event_time = $now;

        // Scheduled to send a notification 3 days before the event time.
        $preference = $generator->create_notification_preference(
            scheduled_resolver::class,
            extended_context::make_system(),
            [
                'recipient' => mock_recipient::class,
                'recipients' => [mock_recipient::class],
                'schedule_offset' => 0,
            ]
        );

        // It is happening now.
        self::assertTrue($preference->is_in_time_window($event_time, new time_window($now, $now + HOURSECS)));
        self::assertTrue(
            $preference->is_in_time_window(
                $event_time,
                new time_window($now, $now + schedule_helper::days_to_seconds(3))
            )
        );
    }
}