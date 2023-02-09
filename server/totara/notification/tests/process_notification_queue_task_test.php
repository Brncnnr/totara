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
use totara_notification\entity\notifiable_event_preference;
use totara_notification\entity\notifiable_event_user_preference;
use totara_notification\entity\notification_preference;
use totara_notification\entity\notification_queue;
use totara_notification\loader\delivery_channel_loader;
use totara_notification\loader\notification_preference_loader;
use totara_notification\manager\notification_queue_manager;
use totara_notification\task\process_notification_queue_task;
use totara_notification\testing\generator;
use totara_notification_mock_notifiable_event_resolver as mock_resolver;

/**
 * This tes is indirectly cover {@see notification_queue_manager}
 */
class totara_notification_process_notification_queue_task_testcase extends testcase {
    /**
     * @return void
     */
    protected function setUp(): void {
        $generator = self::getDataGenerator();

        /** @var generator $notification_generator */
        $notification_generator = $generator->get_plugin_generator('totara_notification');
        $notification_generator->include_mock_built_in_notification();

        $notification_generator->add_mock_built_in_notification_for_component();
    }

    /**
     * @return void
     */
    public function test_sending_message_with_mock(): void {
        global $DB;

        $generator = self::getDataGenerator();
        $user_one = $generator->create_user();

        /** @var generator $notification_generator */
        $notification_generator = $generator->get_plugin_generator('totara_notification');
        $notification_generator->add_mock_recipient_ids_to_resolver([$user_one->id]);

        // Mock the body and subject.
        $notification_generator->add_string_subject_to_mock_built_in_notification('Bomba');
        $notification_generator->add_string_body_to_mock_built_in_notification('Kian');

        $context_user = context_user::instance($user_one->id);
        $repository = notification_preference::repository();

        // Adding queue to process.
        $queue = new notification_queue();
        $queue->set_extended_context(extended_context::make_with_context($context_user));
        $queue->scheduled_time = 15;
        $queue->event_data = json_encode([
            'message' => 'my_name',
            'expected_context_id' => context_system::instance()->id,
        ]);

        // Fetching preference out of the notification's name.
        $preference = $repository->find_in_system_context(totara_notification_mock_built_in_notification::class);
        $queue->notification_preference_id = $preference->id;
        $queue->save();

        self::assertEquals(1, $DB->count_records(notification_queue::TABLE));

        // Start the message redirection.
        $sink = $this->redirectMessages();
        self::assertEquals(0, $sink->count());
        self::assertEmpty($sink->get_messages());

        $task = new process_notification_queue_task();
        $this->assertDebuggingCalled("process_notification_queue_task has been deprecated since 17.0");

        $notification_generator->set_due_time_of_process_notification_task($task, 50);
        $task->execute();

        // Message is sent, the queue should be cleared now.
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));
        self::assertEquals(1, $sink->count());

        $messages = $sink->get_messages();
        self::assertCount(1, $messages);

        $first_message = reset($messages);
        self::assertIsObject($first_message);
        self::assertObjectHasAttribute('fullmessage', $first_message);
        self::assertEquals('Kian', $first_message->fullmessage);

        self::assertObjectHasAttribute('subject', $first_message);
        self::assertEquals('Bomba', $first_message->subject);

        self::assertObjectNotHasAttribute('attachname', $first_message);
        self::assertObjectNotHasAttribute('attachment', $first_message);
        self::assertObjectHasAttribute('attachment_list', $first_message);
        self::assertCount(2, $first_message->attachment_list);
        self::assertEquals(['test0.ics', 'test1.ics'], array_keys($first_message->attachment_list));
    }

    /**
     * @return void
     */
    public function test_sending_message_out_with_invalid_notification(): void {
        global $DB;

        // Adding queue to process.
        $queue = new notification_queue();
        $queue->set_extended_context(extended_context::make_with_context(context_system::instance()));
        $queue->scheduled_time = 15;
        $queue->notification_preference_id = 4242;
        $queue->event_data = json_encode(['message' => 'my_name']);
        $queue->save();

        self::assertEquals(1, $DB->count_records(notification_queue::TABLE));

        /** @var \totara_notification\testing\generator $generator */
        $generator = self::getDataGenerator()->get_plugin_generator('totara_notification');
        $trace = $generator->get_test_progress_trace();

        // Start the message redirection.
        $sink = $this->redirectMessages();
        self::assertEquals(0, $sink->count());
        self::assertEmpty($sink->get_messages());

        $task = new process_notification_queue_task();
        $this->assertDebuggingCalled("process_notification_queue_task has been deprecated since 17.0");

        $task->set_trace($trace);
        $task->execute();

        // Message should not be sent due to invalid notification name, as it was skipped.
        self::assertEquals(0, $sink->count());
        self::assertEmpty($sink->get_messages());

        $messages = $trace->get_messages();
        self::assertNotEmpty($messages);
        self::assertCount(1, $messages);

        $first_message = reset($messages);
        self::assertEquals(
            "The notification preference record with id '4242' does not exist",
            $first_message
        );
    }

    /**
     * @return void
     */
    public function test_sending_message_with_mock_and_not_yet_due_time(): void {
        global $DB;

        $generator = self::getDataGenerator();
        $user_one = $generator->create_user();

        $context_user = context_user::instance($user_one->id);
        $repository = notification_preference::repository();
        $preference = $repository->find_in_system_context(totara_notification_mock_built_in_notification::class);

        // Adding queue to process.
        $queue = new notification_queue();
        $queue->set_extended_context(extended_context::make_with_context($context_user));
        $queue->scheduled_time = 10;
        $queue->notification_preference_id = $preference->id;
        $queue->event_data = json_encode(['message' => 'my_name']);
        $queue->save();

        self::assertEquals(1, $DB->count_records(notification_queue::TABLE));

        // Start the message redirection.
        $sink = $this->redirectMessages();
        self::assertEquals(0, $sink->count());
        self::assertEmpty($sink->get_messages());

        /** @var generator $notification_generator */
        $notification_generator = $generator->get_plugin_generator('totara_notification');

        $task = new process_notification_queue_task();
        $this->assertDebuggingCalled("process_notification_queue_task has been deprecated since 17.0");

        $notification_generator->set_due_time_of_process_notification_task($task, 5);

        $task->execute();

        // Message should not be sent, because there are no queues that due.
        self::assertEquals(1, $DB->count_records(notification_queue::TABLE));
        self::assertEquals(0, $sink->count());
        self::assertEmpty($sink->get_messages());
    }

    /**
     * @return void
     */
    public function test_sending_message_with_valid_and_invalid_notification_queues(): void {
        global $DB;

        $generator = self::getDataGenerator();
        $user_one = $generator->create_user();

        $context_user = context_user::instance($user_one->id);
        $preference = notification_preference_loader::get_built_in(totara_notification_mock_built_in_notification::class);

        /** @var generator $notification_generator */
        $notification_generator = $generator->get_plugin_generator('totara_notification');
        $notification_generator->add_mock_recipient_ids_to_resolver([$user_one->id]);

        // Create a valid queue
        $valid_queue = new notification_queue();
        $valid_queue->set_extended_context(extended_context::make_with_context($context_user));
        $valid_queue->scheduled_time = 10;
        $valid_queue->notification_preference_id = $preference->get_id();
        $valid_queue->event_data = json_encode([
            'message' => 'bolobala',
            'expected_context_id' => context_system::instance()->id
        ]);
        $valid_queue->save();

        // Create an invalid queue
        $invalid_queue = new notification_queue();
        $invalid_queue->set_extended_context(extended_context::make_with_context($context_user));
        $invalid_queue->scheduled_time = 10;
        $invalid_queue->notification_preference_id = 4242;
        $invalid_queue->event_data = json_encode(['message' => 'this is an invalid queue']);
        $invalid_queue->save();

        self::assertEquals(2, $DB->count_records(notification_queue::TABLE));
        $sink = $this->redirectMessages();

        self::assertEquals(0, $sink->count());
        $trace = $notification_generator->get_test_progress_trace();

        // Start the task that help to sending out the messages.
        $task = new process_notification_queue_task();
        $this->assertDebuggingCalled("process_notification_queue_task has been deprecated since 17.0");

        $task->set_trace($trace);

        $notification_generator->set_due_time_of_process_notification_task($task, 15);
        $task->execute();

        // The sending message will yield debugging message, because there is
        // one invalid queue in the table.
        $error_messages = $trace->get_messages();
        self::assertCount(1, $error_messages);

        $first_message = reset($error_messages);
        self::assertEquals(
            "The notification preference record with id '4242' does not exist",
            $first_message
        );

        // There should only be one message sending out to the user.
        self::assertEquals(1, $sink->count());

        // The invalid notification at this point should NOT be removed from the queue.
        // Which means that there should be zero records within the table.
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));
    }

    /**
     * @return void
     */
    public function test_sending_message_keep_invalid_notification_queue_for_next_run(): void {
        global $DB;

        $generator = self::getDataGenerator();
        $user_one = $generator->create_user();

        $context_user = context_user::instance($user_one->id);
        $preference = notification_preference_loader::get_built_in(totara_notification_mock_built_in_notification::class);

        /** @var generator $notification_generator */
        $notification_generator = $generator->get_plugin_generator('totara_notification');
        $notification_generator->add_mock_recipient_ids_to_resolver([$user_one->id]);

        // Create an invalid queue
        $invalid_queue = new notification_queue();
        $invalid_queue->set_extended_context(extended_context::make_with_context($context_user));
        $invalid_queue->scheduled_time = 10;
        $invalid_queue->notification_preference_id = $preference->get_id();
        $invalid_queue->event_data = 'é';
        $invalid_queue->save();

        // Create a valid queue
        $valid_queue = new notification_queue();
        $valid_queue->set_extended_context(extended_context::make_with_context($context_user));
        $valid_queue->scheduled_time = 10;
        $valid_queue->notification_preference_id = $preference->get_id();
        $valid_queue->event_data = json_encode([
            'message' => 'bolobala',
            'expected_context_id' => $context_user->id
        ]);
        $valid_queue->save();

        self::assertEquals(2, $DB->count_records(notification_queue::TABLE));
        $sink = $this->redirectMessages();

        self::assertEquals(0, $sink->count());
        $trace = $notification_generator->get_test_progress_trace();

        // Start the task that help to sending out the messages.
        $task = new process_notification_queue_task();
        $this->assertDebuggingCalled("process_notification_queue_task has been deprecated since 17.0");

        $task->set_trace($trace);

        $notification_generator->set_due_time_of_process_notification_task($task, 15);
        $task->execute();

        // one invalid queue in the table.
        $error_messages = $trace->get_messages();
        self::assertCount(1, $error_messages);

        $first_message = reset($error_messages);
        self::assertEquals(
            "Cannot send notification queue record with id '{$invalid_queue->id}': " .
            "Coding error detected, it must be fixed by a programmer: " .
            "Cannot decode the json string due to: Syntax error",
            $first_message
        );

        // There should only be one message sending out to the user.
        self::assertEquals(1, $sink->count());

        // The invalid notification queue keep in the table for next run.
        self::assertEquals(1, $DB->count_records(notification_queue::TABLE));
    }

    /**
     * @covers totara_notification\manager\notification_queue_manager::filter_message_processors_by_delivery_channel
     * @return void
     */
    public function test_filter_message_processors_without_forced_delivery_channels(): void {
        $generator = generator::instance();
        $generator->include_mock_notifiable_event_resolver();

        $user = $this->getDataGenerator()->create_user();

        $extended_context = extended_context::make_system();

        $entity = new notifiable_event_preference();
        $entity->context_id = $extended_context->get_context_id();
        $entity->resolver_class_name = mock_resolver::class;
        $entity->component = $extended_context->get_component();
        $entity->area = $extended_context->get_area();
        $entity->item_id = $extended_context->get_item_id();
        $entity->default_delivery_channels = ",email,";
        $entity->enabled = true;
        $entity->save();

        // Reset the delivery channel loader cache for our test
        delivery_channel_loader::reset();

        $reflection_class = new ReflectionClass(notification_queue_manager::class);
        $method = $reflection_class->getMethod('filter_message_processors_by_delivery_channel');
        $method->setAccessible(true);

        $message_processors = get_message_processors(false, true);
        $manager = new notification_queue_manager();
        $resolver = new mock_resolver(['expected_context_id' => $extended_context->get_context_id()]);

        // Expect to see email only
        $result = $method->invokeArgs($manager, [$user->id, $resolver, $message_processors]);
        self::assertIsArray($result);
        // Note that we cannot check for the exact same size, because the list of message processors can include
        // the third parties plugins.
        self::assertNotSameSize($message_processors, $result);
        self::assertArrayHasKey('email', $result);
        self::assertArrayNotHasKey('popup', $result);

        // Expect to see email and popup
        $result = $method->invokeArgs($manager, [$user->id, $resolver, $message_processors, ['popup']]);
        self::assertIsArray($result);
        // Note that we cannot check for the exact same size, because the list of message processors can include
        // the third parties plugins.
        self::assertNotSameSize($message_processors, $result);
        self::assertArrayHasKey('email', $result);
        self::assertArrayHasKey('popup', $result);

        // Now repeat the tests, but let the user override the expected preferences
        $user_entity = new notifiable_event_user_preference();
        $user_entity->user_id = $user->id;
        $user_entity->context_id = $extended_context->get_context_id();
        $user_entity->resolver_class_name = mock_resolver::class;
        $user_entity->component = $extended_context->get_component();
        $user_entity->area = $extended_context->get_area();
        $user_entity->item_id = $extended_context->get_item_id();
        $user_entity->delivery_channels = ['popup'];
        $user_entity->enabled = true;
        $user_entity->save();

        // Expect to see popup (user chosen) but not email
        $result = $method->invokeArgs($manager, [$user->id, $resolver, $message_processors]);
        self::assertIsArray($result);
        // Note that we cannot check for the exact same size, because the list of message processors can include
        // the third parties plugins.
        self::assertNotSameSize($message_processors, $result);
        self::assertArrayHasKey('popup', $result);
        self::assertArrayNotHasKey('email', $result);

        // Expect to see popup (user chosen) and email (forced)
        $result = $method->invokeArgs($manager, [$user->id, $resolver, $message_processors, ['email']]);
        self::assertIsArray($result);
        // Note that we cannot check for the exact same size, because the list of message processors can include
        // the third parties plugins.
        self::assertNotSameSize($message_processors, $result);
        self::assertArrayHasKey('popup', $result);
        self::assertArrayHasKey('email', $result);

        // Expect to not see email or popup (user disabled all)
        $user_entity->delivery_channels = [];
        $user_entity->save();
        $result = $method->invokeArgs($manager, [$user->id, $resolver, $message_processors]);
        self::assertIsArray($result);
        // Note that we cannot check for the exact same size, because the list of message processors can include
        // the third parties plugins.
        self::assertNotSameSize($message_processors, $result);
        self::assertArrayNotHasKey('popup', $result);
        self::assertArrayNotHasKey('email', $result);

        // Expect to see popup (via forced) but not email
        $result = $method->invokeArgs($manager, [$user->id, $resolver, $message_processors, ['popup']]);
        self::assertIsArray($result);
        // Note that we cannot check for the exact same size, because the list of message processors can include
        // the third parties plugins.
        self::assertNotSameSize($message_processors, $result);
        self::assertArrayHasKey('popup', $result);
        self::assertArrayNotHasKey('email', $result);
    }

    /**
     * @covers totara_notification\manager\notification_queue_manager::dispatch_to_target
     * @return void
     */
    public function test_get_attachments_with_valid_params(): void {

        $target_user = $this->getDataGenerator()->create_user();
        $extended_context = extended_context::make_system();
        $message_processors = get_message_processors(true, (defined('PHPUNIT_TEST') && PHPUNIT_TEST));
        $preference = notification_preference_loader::get_built_in(totara_notification_mock_built_in_notification::class);

        $reflection_class = new ReflectionClass(notification_queue_manager::class);
        $method = $reflection_class->getMethod('dispatch_to_target');
        $method->setAccessible(true);

        $resolver = new mock_resolver(['expected_context_id' => $extended_context->get_context_id()]);
        // Set valid params.
        $resolver::set_attachment_user($target_user);
        $resolver::set_attachment_preference($preference);

        // Invoke dispatch_to_target
        $manager = new notification_queue_manager();
        $method->invokeArgs($manager, [$target_user, $preference, $resolver, $message_processors]);
        $is_get_attachment_params_valid = $resolver::is_get_attachment_params_valid();
        self::assertEquals(true, $is_get_attachment_params_valid);
    }

    /**
     * @covers totara_notification\manager\notification_queue_manager::dispatch_to_target
     * @return void
     */
    public function test_get_attachments_with_invalid_params(): void {

        $target_user = $this->getDataGenerator()->create_user();
        $extended_context = extended_context::make_system();
        $message_processors = get_message_processors(true, (defined('PHPUNIT_TEST') && PHPUNIT_TEST));
        $preference = notification_preference_loader::get_built_in(totara_notification_mock_built_in_notification::class);

        $reflection_class = new ReflectionClass(notification_queue_manager::class);
        $method = $reflection_class->getMethod('dispatch_to_target');
        $method->setAccessible(true);

        $resolver = new mock_resolver(['expected_context_id' => $extended_context->get_context_id()]);
        // Set invalid params.
        $resolver::set_attachment_user(null);
        $resolver::set_attachment_preference(null);

        // Invoke dispatch_to_target
        $manager = new notification_queue_manager();
        $method->invokeArgs($manager, [$target_user, $preference, $resolver, $message_processors]);
        $is_get_attachment_params_valid = $resolver::is_get_attachment_params_valid();
        self::assertEquals(false, $is_get_attachment_params_valid);
    }
}
