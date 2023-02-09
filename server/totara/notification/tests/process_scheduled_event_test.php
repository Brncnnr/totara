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
use totara_notification\entity\notification_queue;
use totara_notification\local\schedule_helper;
use totara_notification\task\process_scheduled_event_task;
use totara_notification\testing\generator;
use totara_notification_mock_recipient as mock_recipient;
use totara_notification_mock_scheduled_aware_event_resolver as mock_resolver;

/**
 * Test suite to process all the scheduled events that can make the centralised notification
 * to send the notification out to the user.
 *
 * Note that this test does not include the scenario where we are having to processed both
 * events in the future and events in the past together.
 *
 * If you want to see it, please prefer to the scenario tests.
 */
class totara_notification_process_scheduled_event_testcase extends testcase {
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
    public function test_process_scheduled_before_event_notification(): void {
        global $DB;


        $notification_generator = generator::instance();
        $notification_generator->add_notifiable_event_resolver(mock_resolver::class);

        $recipient = self::getDataGenerator()->create_user();
        $extended_context = extended_context::make_system();
        $now = time();

        $sink = self::redirectMessages();
        self::assertEquals(0, $sink->count());
        self::assertEmpty($sink->get_messages());

        // Create a custom notification that will be sent 3 days before
        // the actual event time.
        $notification_generator->create_notification_preference(
            mock_resolver::class,
            $extended_context,
            [
                'subject' => 'Custom subject',
                'recipient' => mock_recipient::class,
                'schedule_offset' => schedule_helper::days_to_seconds(-3),
            ]
        );

        // Create an event that have the event time set for 6 days from now.
        mock_resolver::set_events([
            [
                mock_recipient::RECIPIENT_IDS_KEY => [$recipient->id],
                mock_resolver::EVENT_TIME_KEY => $now + schedule_helper::days_to_seconds(6)
            ],
        ]);

        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $task = new process_scheduled_event_task();
        $task->set_time_now($now);

        // Nothing to match with the sending preference.
        $task->execute();
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        // Move the time now to 2 days up.
        $task->set_time_now($now + schedule_helper::days_to_seconds(2));

        // Last run time is today.
        set_config(process_scheduled_event_task::LAST_RUN_TIME_NAME, $now, 'totara_notification');
        $task->execute();

        // Since the last run time is only 2 days up - therefore we are not going to receive any queues.
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        // Move the last run time to 2 days from now. Then we will receive notifications.
        $task->set_last_run_time($now + schedule_helper::days_to_seconds(2));

        // Move time now to 3 days from now
        $task->set_time_now($now + schedule_helper::days_to_seconds(3) + HOURSECS);
        $task->execute();

        $messages = $sink->get_messages();
        self::assertCount(1, $messages);

        $message = array_pop($messages);
        self::assertEquals($message->useridto, $recipient->id);
        self::assertEquals($message->subject, 'Custom subject');
    }

    /**
     * @return void
     */
    public function test_process_scheduled_after_event_notification(): void {
        global $DB;

        $notification_generator = generator::instance();
        $notification_generator->add_notifiable_event_resolver(mock_resolver::class);

        $recipient = self::getDataGenerator()->create_user();
        $extended_context = extended_context::make_system();
        $now = time();

        $sink = self::redirectMessages();
        self::assertEquals(0, $sink->count());
        self::assertEmpty($sink->get_messages());

        // Create a custom notification that will be sent 3 days after the
        // event time had happened.
        $notification_preference = $notification_generator->create_notification_preference(
            mock_resolver::class,
            $extended_context,
            [
                'subject' => 'Custom subject',
                'recipient' => mock_recipient::class,
                'schedule_offset' => schedule_helper::days_to_seconds(3),
            ]
        );

        // Create an event that had happened 1 day before now.
        mock_resolver::set_events([
            [
                mock_recipient::RECIPIENT_IDS_KEY => [$recipient->id],
                mock_resolver::EVENT_TIME_KEY => $now - schedule_helper::days_to_seconds(1)
            ],
        ]);

        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));
        $task = new process_scheduled_event_task();

        // Set the time is now and it will not trigger the
        // notification queue just yet.
        $task->set_time_now($now);
        $task->execute();

        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        // Set the time is 2 days after from now which it will make the
        // event match the criteria of notification preference and will trigger
        // the notification queue.
        set_config(process_scheduled_event_task::LAST_RUN_TIME_NAME, $now, 'totara_notification');
        $task->set_time_now($now + schedule_helper::days_to_seconds(2) + HOURSECS);
        $task->execute();

        $messages = $sink->get_messages();
        self::assertCount(1, $messages);

        $message = array_pop($messages);
        self::assertEquals($message->useridto, $recipient->id);
        self::assertEquals($message->subject, 'Custom subject');
    }
}
