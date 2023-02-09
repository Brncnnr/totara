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
use totara_core\extended_context;
use totara_notification\entity\notifiable_event_queue;
use totara_notification\entity\notification_queue;
use totara_notification\manager\event_queue_manager;
use totara_notification\testing\generator;

class totara_notification_event_queue_manager_testcase extends testcase {
    /**
     * @return void
     */
    protected function setUp(): void {
        $generator = generator::instance();

        $generator->include_mock_notifiable_event();
        $generator->include_mock_notifiable_event_resolver();
        $generator->add_mock_built_in_notification_for_component();
    }

    /**
     * @return void
     */
    public function test_process_queues_with_valid_and_invalid_items(): void {
        global $DB;

        $generator = self::getDataGenerator();
        $user_one = $generator->create_user();

        $context_user = context_user::instance($user_one->id);

        // Create a valid queue.
        $valid_queue = new notifiable_event_queue();
        $valid_queue->set_extended_context(extended_context::make_with_context($context_user));
        $valid_queue->set_decoded_event_data([
            'message' => 'data',
            'expected_context_id' => $context_user->id,
        ]);
        $valid_queue->resolver_class_name = totara_notification_mock_notifiable_event_resolver::class;
        $valid_queue->save();

        // Create an invalid queue.
        $invalid_queue = new notifiable_event_queue();
        $invalid_queue->set_extended_context(extended_context::make_with_context($context_user));
        $invalid_queue->set_decoded_event_data([
            'boom' => 'kaboom',
            'expected_context_id' => $context_user->id,
        ]);
        $invalid_queue->resolver_class_name = 'anima_martin_garrix';
        $invalid_queue->save();

        // There should be two queues within database.
        self::assertEquals(2, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $notification_generator = generator::instance();
        $trace = $notification_generator->get_test_progress_trace();

        // Process the queue.
        $manager = new event_queue_manager($trace);
        $manager->process_queues();

        $error_messages = $trace->get_messages();
        self::assertNotEmpty($error_messages);
        self::assertCount(1, $error_messages);

        $message = reset($error_messages);

        self::assertEquals(
            "Cannot send notification event queue record with id '{$invalid_queue->id}': " .
            "Coding error detected, it must be fixed by a programmer: " .
            "The resolver class name is not a notifiable event resolver: 'anima_martin_garrix'",
            $message
        );
    }

    /**
     * @return void
     */
    public function test_process_queues_with_valid_and_disabled_items(): void {
        global $DB;

        $generator = self::getDataGenerator();
        $user_one = $generator->create_user();
        $notification_generator = generator::instance();

        $context_user = context_user::instance($user_one->id);

        // Create a valid queue.
        $valid_queue = new notifiable_event_queue();
        $valid_queue->set_extended_context(extended_context::make_with_context($context_user));
        $valid_queue->set_decoded_event_data(
            ['message' => 'data', 'expected_context_id' => $context_user->id,]
        );
        $valid_queue->resolver_class_name = totara_notification_mock_notifiable_event_resolver::class;
        $valid_queue->save();

        // Create an disabled queue.
        $queue_disabled_resolver = new notifiable_event_queue();
        $queue_disabled_resolver->set_extended_context(extended_context::make_with_context($context_user));
        $queue_disabled_resolver->set_decoded_event_data(
            ['message' => 'data', 'expected_context_id' => $context_user->id,]
        );
        $queue_disabled_resolver->resolver_class_name = totara_notification_mock_notifiable_event_resolver::class;
        $queue_disabled_resolver->save();

        //disabled the resolver
        $notification_generator->create_notification_preference(
            totara_notification_mock_notifiable_event_resolver::class,
            extended_context::make_system(),
            ['recipient' => totara_notification_mock_recipient::class, 'enabled' => false,]
        );

        // There should be two queues within database.
        self::assertEquals(2, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $trace = $notification_generator->get_test_progress_trace();

        // Process the queue.
        $manager = new event_queue_manager($trace);
        $manager->process_queues();

        // check empty records in event queue
        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));

        $error_messages = $trace->get_messages();
        self::assertEmpty($error_messages);
    }
}
