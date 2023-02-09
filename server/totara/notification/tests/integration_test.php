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
use totara_notification\entity\notification_queue;
use totara_notification\observer\notifiable_event_observer;
use totara_notification\task\process_event_queue_task;
use totara_notification\testing\generator;

/**
 * Test case where we are integrating everything and use mocks data.
 */
class totara_notification_integration_testcase extends testcase {
    /**
     * @return void
     */
    protected function setUp(): void {
        $generator = generator::instance();

        $generator->include_mock_notifiable_event();
        $generator->include_mock_built_in_notification();
        $generator->add_mock_built_in_notification_for_component();
    }

    /**
     * @return void
     */
    public function test_with_mock(): void {
        global $DB;
        $generator = self::getDataGenerator();

        $user_one = $generator->create_user();
        $notification_generator = generator::instance();

        // Mask the recipient ids to be sent to.
        $notification_generator->add_mock_recipient_ids_to_resolver([$user_one->id]);

        $event = new totara_notification_mock_notifiable_event(context_system::instance()->id);

        // Initial state.
        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        // Queue the event.
        notifiable_event_observer::watch_notifiable_event($event);

        self::assertEquals(1, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        // Start the sink and making sure that no messages are sent yet.
        $sink = $this->redirectMessages();
        self::assertEquals(0, $sink->count());
        self::assertEmpty($sink->get_messages());

        // Mock the default body and subject, so that our sending task can fall to this data.
        $notification_generator->add_string_body_to_mock_built_in_notification('This is body');
        $notification_generator->add_string_subject_to_mock_built_in_notification('This is subject');

        // Run the process event task, which it should delte
        $event_task = new process_event_queue_task();
        $event_task->execute();

        // There should be one message sent out by now.
        self::assertEquals(1, $sink->count());

        $messages = $sink->get_messages();
        self::assertNotEmpty($messages);
        self::assertCount(1, $messages);

        $first_message = reset($messages);
        self::assertIsObject($first_message);

        self::assertObjectHasAttribute('fullmessage', $first_message);
        self::assertEquals('This is body', $first_message->fullmessage);

        self::assertObjectHasAttribute('subject', $first_message);
        self::assertEquals('This is subject', $first_message->subject);

        self::assertObjectHasAttribute('useridto', $first_message);
        self::assertEquals($user_one->id, $first_message->useridto);
    }
}
