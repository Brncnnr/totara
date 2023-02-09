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
use totara_notification\model\notification_preference;
use totara_notification\task\process_event_queue_task;
use totara_notification\testing\generator;
use totara_notification_mock_recipient as mock_recipient;

/**
 * This test is indirectly cover {@see event_queue_manager}
 */
class totara_notification_process_event_task_testcase extends testcase {
    /**
     * @return void
     */
    protected function setUp(): void {
        $notification_generator = generator::instance();
        $notification_generator->include_mock_notifiable_event();
        $notification_generator->include_mock_recipient();
    }

    /**
     * @return void
     */
    public function test_process_event_task_with_valid_event(): void {
        global $DB;
        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $generator = self::getDataGenerator();
        $recipient = self::getDataGenerator()->create_user(['username' => 'recipient1']);

        /** @var generator $notification_generator */
        $notification_generator = $generator->get_plugin_generator('totara_notification');
        $notification_generator->add_mock_built_in_notification_for_component();
        $notification_generator->add_mock_recipient_ids_to_resolver([$recipient->id]);


        $context_system = context_system::instance();
        $data = [
            'user_id' => 4242,
            'expected_context_id' => $context_system->id,
        ];

        // Create mock event first.
        $event_queue = new notifiable_event_queue();
        $event_queue->resolver_class_name = totara_notification_mock_notifiable_event_resolver::class;
        $event_queue->set_decoded_event_data($data);
        $event_queue->set_extended_context(extended_context::make_with_context($context_system));
        $event_queue->save();

        self::assertEquals(1, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        // Redirect messages.
        $sink = self::redirectMessages();
        $messages = $sink->get_messages();
        self::assertCount(0, $messages);

        // Execute the tasks, which it should create a new notification queue from the event queue.
        try {
            $task = new process_event_queue_task();
            $task->execute();
        } catch (Throwable $e) {
            var_dump($e->getMessage());
        }

        // There shouldn't be anything in the queue, it should have sent a message.
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));
        $messages = $sink->get_messages();
        self::assertCount(1, $messages);

        $message = array_pop($messages);
        self::assertEquals($recipient->id, $message->useridto);
        self::assertEquals('Centralised notification', $message->subject);
    }

    /**
     * @return void
     */
    public function test_prcocess_valid_event_without_built_in_notification(): void {
        global $DB;
        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $context_system = context_system::instance();

        // Create mock event first.
        $event_queue = new notifiable_event_queue();
        $event_queue->resolver_class_name = totara_notification_mock_notifiable_event_resolver::class;
        $event_queue->set_decoded_event_data([]);
        $event_queue->set_extended_context(extended_context::make_with_context($context_system));
        $event_queue->save();

        self::assertEquals(1, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        // Execute the tasks, which it should not create any new notification queue
        // from the event queue, because the built in notifications are not found for such event.
        $task = new process_event_queue_task();
        $task->execute();

        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));
    }

    /**
     * @return void
     */
    public function test_process_on_invalid_event(): void {
        global $DB;
        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $context_system = context_system::instance();

        // Create mock event first.
        $event_queue = new notifiable_event_queue();
        $event_queue->resolver_class_name = 'martin_garrix_anima_resolver';
        $event_queue->set_decoded_event_data([]);
        $event_queue->set_extended_context(extended_context::make_with_context($context_system));
        $event_queue->save();

        self::assertEquals(1, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        /** @var generator $generator */
        $generator = self::getDataGenerator()->get_plugin_generator('totara_notification');
        $trace = $generator->get_test_progress_trace();

        // Execute the tasks, which it should not create any new notification queue
        // from the event queue, because the built in notifications are not found for such event.
        $task = new process_event_queue_task();
        $task->set_trace($trace);
        $task->execute();

        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $messages = $trace->get_messages();
        self::assertCount(1, $messages);

        $message = reset($messages);
        self::assertEquals(
            "Cannot send notification event queue record with id '{$event_queue->id}': " .
            "Coding error detected, it must be fixed by a programmer: " .
            "The resolver class name is not a notifiable event resolver: 'martin_garrix_anima_resolver'",
            $message
        );
    }
}
