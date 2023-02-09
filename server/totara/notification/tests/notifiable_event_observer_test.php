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

use core_phpunit\testcase;
use totara_notification\entity\notifiable_event_queue;
use totara_notification\observer\notifiable_event_observer;
use totara_notification\testing\generator;

class totara_notification_notifiable_event_observer_testcase extends testcase {
    /**
     * @return void
     */
    protected function setUp(): void {
        $generator = generator::instance();
        $generator->include_mock_notifiable_event();
        $generator->include_mock_notifiable_event_resolver();
    }

    /**
     * @return void
     */
    public function test_observe_an_event(): void {
        global $DB;
        $generator = self::getDataGenerator();

        $user_one = $generator->create_user();
        $context_user = context_user::instance($user_one->id);

        $mock_event_data = [
            'user_id' => $user_one->id,
            'expected_context_id' => $context_user->id,
        ];
        $event = new totara_notification_mock_notifiable_event(
            $context_user->id,
            $mock_event_data
        );

        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        notifiable_event_observer::watch_notifiable_event($event);

        self::assertEquals(1, $DB->count_records(notifiable_event_queue::TABLE));

        // Fetch the first record.
        $queue_record = $DB->get_record(
            notifiable_event_queue::TABLE,
            ['context_id' => $context_user->id],
            '*',
            MUST_EXIST
        );

        $notifiable_queue = new notifiable_event_queue($queue_record);

        self::assertEquals(json_encode($mock_event_data), $notifiable_queue->event_data);
        self::assertEquals(totara_notification_mock_notifiable_event_resolver::class, $notifiable_queue->resolver_class_name);
        self::assertEquals($mock_event_data, $notifiable_queue->get_decoded_event_data());

        // This assertion is pretty much redundant, but it is better to have one.
        self::assertEquals($context_user->id, $notifiable_queue->context_id);
    }
}