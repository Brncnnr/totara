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
use totara_notification\loader\notification_preference_loader;
use totara_notification\observer\notifiable_event_observer;
use totara_notification\task\process_event_queue_task;
use totara_notification\testing\generator;
use totara_notification_mock_built_in_notification as mock_built_in;
use totara_notification_mock_notifiable_event as mock_event;
use totara_notification_mock_notifiable_event_resolver as mock_resolver;

class totara_notification_send_message_with_preferences_testcase extends testcase {
    /**
     * @return void
     */
    protected function setUp(): void {
        $generator = generator::instance();
        $generator->include_mock_recipient();
        $generator->include_mock_notifiable_event_resolver();
        $generator->include_mock_notifiable_event();

        $generator->add_mock_built_in_notification_for_component();
    }

    /**
     * @return void
     */
    public function test_send_message_at_lower_context_without_overridden(): void {
        global $DB;
        $generator = self::getDataGenerator();

        $course = $generator->create_course();
        $receiver = $generator->create_user();

        $notification_generator = generator::instance();
        $notification_generator->add_mock_recipient_ids_to_resolver([$receiver->id]);

        $event = new mock_event(context_course::instance($course->id)->id);
        notifiable_event_observer::watch_notifiable_event($event);

        self::assertEquals(1, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        // Start the sink, then run the process notification task.
        $sink = $this->redirectMessages();
        self::assertEquals(0, $sink->count());

        // Run the event queue task.
        $event_task = new process_event_queue_task();
        $event_task->execute();

        self::assertEquals(1, $sink->count());
        $messages = $sink->get_messages();

        self::assertCount(1, $messages);
        $first_message = reset($messages);

        self::assertIsObject($first_message);
        self::assertObjectHasAttribute('fullmessage', $first_message);
        self::assertObjectHasAttribute('subject', $first_message);

        $built_in_body = mock_built_in::get_default_body()->out();
        $built_in_subject = mock_built_in::get_default_subject()->out();

        self::assertEquals($built_in_body, $first_message->fullmessage);
        self::assertEquals($built_in_subject, $first_message->subject);
    }

    /**
     * @return void
     */
    public function test_send_message_at_lower_context_with_overridden(): void {
        $generator = self::getDataGenerator();

        $course = $generator->create_course();
        $receiver = $generator->create_user();

        $context_course = context_course::instance($course->id);

        $notification_generator = generator::instance();
        $notification_generator->add_mock_recipient_ids_to_resolver([$receiver->id]);

        $system_built_in = notification_preference_loader::get_built_in(mock_built_in::class);

        $course_built_in = $notification_generator->create_overridden_notification_preference(
            $system_built_in,
            extended_context::make_with_context($context_course),
            [
                'body' => 'Course body',
                'subject' => 'Course subject',
                'recipient' => totara_notification_mock_recipient::class,
            ]
        );

        $event = new mock_event($context_course->id);
        notifiable_event_observer::watch_notifiable_event($event);

        // Start the sink, then run the process the notification event queue task.
        $sink = $this->redirectMessages();
        self::assertEquals(0, $sink->count());

        // Run the event queue task.
        $event_task = new process_event_queue_task();
        $event_task->execute();

        self::assertEquals(1, $sink->count());
        $messages = $sink->get_messages();

        self::assertCount(1, $messages);
        $first_message = reset($messages);

        self::assertIsObject($first_message);
        self::assertObjectHasAttribute('fullmessage', $first_message);
        self::assertObjectHasAttribute('subject', $first_message);

        $built_in_body = mock_built_in::get_default_body()->out();
        $built_in_subject = mock_built_in::get_default_subject()->out();

        self::assertNotEquals($built_in_body, $first_message->fullmessage);
        self::assertNotEquals($built_in_subject, $first_message->subject);

        self::assertEquals($course_built_in->get_body(), $first_message->fullmessage);
        self::assertEquals($course_built_in->get_subject(), $first_message->subject);
    }

    /**
     * @return void
     */
    public function test_send_message_at_lower_context_with_overridden_in_middle(): void {
        global $DB;
        $generator = self::getDataGenerator();

        $course = $generator->create_course();
        $receiver = $generator->create_user();

        $context_course = context_course::instance($course->id);
        $context_category = context_coursecat::instance($course->category);

        /** @var generator $notification_generator */
        $notification_generator = $generator->get_plugin_generator('totara_notification');
        $notification_generator->add_mock_recipient_ids_to_resolver([$receiver->id]);

        $system_built_in = notification_preference_loader::get_built_in(
            totara_notification_mock_built_in_notification::class
        );

        $category_built_in = $notification_generator->create_overridden_notification_preference(
            $system_built_in,
            extended_context::make_with_context($context_category),
            [
                'body' => 'Category body',
                'recipient' => totara_notification_mock_recipient::class,
            ]
        );

        $event = new totara_notification_mock_notifiable_event($context_course->id);
        notifiable_event_observer::watch_notifiable_event($event);

        self::assertEquals(1, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        // Start the sink, then run the process notification task.
        $sink = $this->redirectMessages();
        self::assertEquals(0, $sink->count());

        // Run the event queue task.
        $event_task = new process_event_queue_task();
        $event_task->execute();

        self::assertEquals(1, $sink->count());
        $messages = $sink->get_messages();

        self::assertCount(1, $messages);
        $first_message = reset($messages);

        self::assertIsObject($first_message);
        self::assertObjectHasAttribute('fullmessage', $first_message);
        self::assertObjectHasAttribute('subject', $first_message);

        $built_in_body = totara_notification_mock_built_in_notification::get_default_body()->out();
        $built_in_subject = totara_notification_mock_built_in_notification::get_default_subject()->out();

        self::assertNotEquals($built_in_body, $first_message->fullmessage);
        self::assertEquals($built_in_subject, $first_message->subject);

        self::assertEquals($category_built_in->get_body(), $first_message->fullmessage);
    }

    /**
     * @return void
     */
    public function test_send_message_at_lower_context_with_multiple_preferences(): void {
        global $DB;
        $generator = self::getDataGenerator();

        $course = $generator->create_course();
        $user_one = $generator->create_user();

        $context_category = context_coursecat::instance($course->category);
        $context_course = context_course::instance($course->id);

        /** @var generator $notification_generator */
        $notification_generator = $generator->get_plugin_generator('totara_notification');
        $notification_generator->add_mock_recipient_ids_to_resolver([$user_one->id]);

        // Add a custom notification at course category's level.
        $notification_generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_with_context($context_category),
            [
                'body' => 'Custom category body',
                'subject' => 'Custom category subject',
                'title' => 'Custom category title',
                'body_format' => FORMAT_MOODLE,
                'recipient' => totara_notification_mock_recipient::class,
            ]
        );

        // Add overridden of a built in notification at category context and top level.
        $system_built_in = notification_preference_loader::get_built_in(mock_built_in::class);
        $category_built_in = $notification_generator->create_overridden_notification_preference(
            $system_built_in,
            extended_context::make_with_context($context_category),
            [
                'body' => 'Built in category body',
                'recipient' => totara_notification_mock_recipient::class,
            ]
        );

        $notification_generator->create_overridden_notification_preference(
            $category_built_in,
            extended_context::make_with_context($context_course),
            [
                'subject' => 'Built in course subject',
                'recipient' => totara_notification_mock_recipient::class,
            ]
        );

        $event = new mock_event($context_course->id);
        notifiable_event_observer::watch_notifiable_event($event);

        self::assertEquals(1, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        // Start the sink, then run the process notification task.
        $sink = $this->redirectMessages();
        self::assertEquals(0, $sink->count());

        // Run the event queue task.
        $event_task = new process_event_queue_task();
        $event_task->execute();

        self::assertEquals(2, $sink->count());
        $messages = $sink->get_messages();

        self::assertCount(2, $messages);

        foreach ($messages as $message) {
            self::assertIsObject($message);
            self::assertObjectHasAttribute('fullmessage', $message);
            self::assertObjectHasAttribute('subject', $message);

            self::assertNotEquals(
                mock_built_in::get_default_body()->out(),
                $message->fullmessage
            );

            self::assertNotEquals(
                mock_built_in::get_default_subject()->out(),
                $message->subject
            );

            self::assertContainsEquals(
                $message->fullmessage,
                [
                    'Built in category body',
                    'Custom category body',
                ]
            );

            self::assertContainsEquals(
                $message->subject,
                [
                    'Built in course subject',
                    'Custom category subject',
                ]
            );
        }
    }
}
