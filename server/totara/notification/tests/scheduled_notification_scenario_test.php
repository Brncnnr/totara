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
use totara_notification\entity\notifiable_event_queue;
use totara_notification\entity\notification_queue;
use totara_notification\local\schedule_helper;
use totara_notification\manager\event_queue_manager;
use totara_notification\manager\scheduled_event_manager;
use totara_notification\observer\notifiable_event_observer;
use totara_notification\schedule\schedule_after_event;
use totara_notification\schedule\schedule_before_event;
use totara_notification\schedule\schedule_on_event;
use totara_notification\testing\generator;
use totara_notification_mock_recipient as mock_recipient;
use totara_notification_mock_scheduled_aware_event_resolver as mock_scheduled_event_resolver;
use totara_notification_mock_scheduled_event_with_on_event as mock_event;
use totara_notification_mock_scheduled_event_with_on_event_resolver as mock_event_resolver;
use totara_notification_mock_scheduled_built_in_notification as mock_built_in;
use core_phpunit\testcase;

class totara_notification_scheduled_notification_scenario_testcase extends testcase {
    private $user;

    /**
     * @return void
     */
    protected function setUp(): void {
        $generator = generator::instance();
        $generator->include_mock_scheduled_event_with_on_event();
        $generator->include_mock_scheduled_event_with_on_event_resolver();
        $generator->include_mock_scheduled_aware_notifiable_event_resolver();

        $generator->include_mock_recipient();

        $this->user = self::getDataGenerator()->create_user();
    }

    protected function tearDown(): void {
        $this->user = null;
    }

    /**
     * @return void
     */
    public function test_sending_notifications_that_include_on_after_and_before_event(): void {
        global $DB;
        $generator = self::getDataGenerator();

        $sink = self::redirectMessages();
        self::assertEquals(0, $sink->count());
        self::assertEmpty($sink->get_messages());

        // The receiver.
        $this->user = $generator->create_user();
        $extended_context = extended_context::make_system();

        // Create 3 notification preferences for the mock event.
        // + One to be on the event time
        // + One to be 3 days in the after the event
        // + Last one to be 6 days before the event.

        $notification_generator = generator::instance();
        $notification_generator->add_notifiable_event_resolver(mock_event_resolver::class);

        $on_event_preference = $notification_generator->create_notification_preference(
            mock_event_resolver::class,
            $extended_context,
            [
                'recipient' => mock_recipient::class,
                'schedule_offset' => 0,
                'body' => 'This is on_event body',
                'subject' => 'This is on_event subject',
                'body_format' => FORMAT_PLAIN,
                'subject_format' => FORMAT_PLAIN,
            ]
        );

        $before_event_preference = $notification_generator->create_notification_preference(
            mock_event_resolver::class,
            $extended_context,
            [
                'recipient' => mock_recipient::class,
                'schedule_offset' => schedule_helper::days_to_seconds(-6),
                'body' => 'This is before_event body',
                'subject' => 'This is before_event subject',
                'body_format' => FORMAT_PLAIN,
                'subject_format' => FORMAT_PLAIN,
            ]
        );

        $after_event_preference = $notification_generator->create_notification_preference(
            mock_event_resolver::class,
            $extended_context,
            [
                'recipient' => mock_recipient::class,
                'schedule_offset' => schedule_helper::days_to_seconds(3),
                'body' => 'This is after_event body',
                'subject' => 'This is after_event subject',
                'body_format' => FORMAT_PLAIN,
                'subject_format' => FORMAT_PLAIN,
            ]
        );

        $now = time();

        // The first event is happening now.
        $event = new mock_event(
            $extended_context,
            [
                mock_recipient::RECIPIENT_IDS_KEY => [$this->user->id],
                mock_event_resolver::EVENT_TIME_KEY => $now,
            ]
        );

        // An event that happened 3 days ago.
        // An event that will happen 10 days from now.
        mock_event_resolver::set_events([
            [
                mock_recipient::RECIPIENT_IDS_KEY => [$this->user->id],
                mock_event_resolver::EVENT_TIME_KEY => ($now - schedule_helper::days_to_seconds(3)),
            ],
            [
                mock_recipient::RECIPIENT_IDS_KEY => [$this->user->id],
                mock_event_resolver::EVENT_TIME_KEY => ($now + schedule_helper::days_to_seconds(10)),
            ],
        ]);

        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        notifiable_event_observer::watch_notifiable_event($event);

        self::assertEquals(1, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        // Process the event.
        $event_manager = new event_queue_manager();
        $event_manager->process_queues();

        // Zero queue now
        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        // Process the scheduled event manager.
        self::assertEquals(1, $sink->count());
        $on_event_messages = $sink->get_messages();

        self::assertCount(1, $on_event_messages);
        $on_event_message = reset($on_event_messages);
        self::assertIsObject($on_event_message);

        self::assertObjectHasAttribute('fullmessage', $on_event_message);
        self::assertEquals('This is on_event body', $on_event_message->fullmessage);

        self::assertObjectHasAttribute('subject', $on_event_message);
        self::assertEquals('This is on_event subject', $on_event_message->subject);

        self::assertObjectHasAttribute('useridto', $on_event_message);
        self::assertEquals($this->user->id, $on_event_message->useridto);

        $sink->clear();
        self::assertEquals(0, $sink->count());
        self::assertEmpty($sink->get_messages());

        // Process the scheduled event from now.
        $scheduled_manager = new scheduled_event_manager();
        $scheduled_manager->execute($now + DAYSECS, $now);

        self::assertEquals(1, $sink->count());
        $after_event_messages = $sink->get_messages();

        self::assertCount(1, $after_event_messages);
        $after_event_message = reset($after_event_messages);
        self::assertIsObject($after_event_message);

        self::assertObjectHasAttribute('fullmessage', $after_event_message);
        self::assertEquals('This is after_event body', $after_event_message->fullmessage);

        self::assertObjectHasAttribute('subject', $after_event_message);
        self::assertEquals('This is after_event subject', $after_event_message->subject);

        self::assertObjectHasAttribute('useridto', $after_event_message);
        self::assertEquals($this->user->id, $after_event_message->useridto);

        $sink->clear();
        self::assertEquals(0, $sink->count());
        self::assertEmpty($sink->get_messages());

        // Execute 4 days from now.
        $scheduled_manager->execute(
            ($now + schedule_helper::days_to_seconds(4) + HOURSECS),
            ($now + DAYSECS)
        );

        // Zero queue now
        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        self::assertEquals(1, $sink->count());
        $before_event_messages = $sink->get_messages();

        self::assertCount(1, $before_event_messages);
        $before_event_message = reset($before_event_messages);
        self::assertIsObject($before_event_message);

        self::assertObjectHasAttribute('fullmessage', $before_event_message);
        self::assertEquals('This is before_event body', $before_event_message->fullmessage);

        self::assertObjectHasAttribute('subject', $before_event_message);
        self::assertEquals('This is before_event subject', $before_event_message->subject);

        self::assertObjectHasAttribute('useridto', $before_event_message);
        self::assertEquals($this->user->id, $before_event_message->useridto);
    }

    /**
     * @return void
     */
    public function test_one_notifiable_event_with_no_built_in_or_custom_notification_preferences(): void {
        global $DB;

        $generator = generator::instance();
        $generator->purge_built_in_notifications();
        $generator->add_notifiable_event_resolver(mock_event_resolver::class);

        $sink = self::redirectMessages();
        self::assertEquals(0, $sink->count());
        self::assertEmpty($sink->get_messages());


        // The receiver.
        $extended_context = extended_context::make_system();

        // Add one scheduled events to the resolver's getter.
        mock_event_resolver::set_events([
            [
                'subject' => 'Subject Event Now',
                mock_recipient::RECIPIENT_IDS_KEY => [$this->user->id],
                mock_event_resolver::EVENT_TIME_KEY => time()
            ],
        ]);


        $event = new mock_event($extended_context, []);

        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        notifiable_event_observer::watch_notifiable_event($event);

        // 1 event was queue.
        self::assertEquals(1, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $event_manager = new event_queue_manager();
        $event_manager->process_queues();

        $scheduled_manager = new scheduled_event_manager();
        $scheduled_manager->execute(time(), time());

        // There are no preferences to queue or send.
        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));
        self::assertEquals(0, $sink->count());
        self::assertEmpty($sink->get_messages());
    }

    /**
     * @return void
     */
    public function test_one_notiable_event_that_support_on_and_after_without_built_in_preferences(): void {
        global $DB;

        $generator = generator::instance();
        $generator->purge_built_in_notifications();
        $generator->add_notifiable_event_resolver(mock_scheduled_event_resolver::class);

        $sink = self::redirectMessages();
        self::assertEquals(0, $sink->count());
        self::assertEmpty($sink->get_messages());

        mock_scheduled_event_resolver::set_scheduled_classes(
            schedule_on_event::class,
            schedule_after_event::class
        );

        // The receiver.
        $extended_context = extended_context::make_system();

        $custom_preference_one = $generator->create_notification_preference(
            mock_scheduled_event_resolver::class,
            $extended_context,
            [
                'subject' => 'Subject After 10',
                'recipient' => mock_recipient::class,
                'schedule_offset' => schedule_after_event::default_value(10),
            ]
        );

        $custom_preference_two = $generator->create_notification_preference(
            mock_scheduled_event_resolver::class,
            $extended_context,
            [
                'subject' => 'Subject After 5',
                'recipient' => mock_recipient::class,
                'schedule_offset' => schedule_after_event::default_value(5),
            ]
        );

        $now = time();

        // Add two mock events, which one is happening 6 days ago,
        // and one is occurring 11 days ago.
        mock_scheduled_event_resolver::set_events([
            [
                mock_recipient::RECIPIENT_IDS_KEY => [$this->user->id],
                mock_scheduled_event_resolver::EVENT_TIME_KEY => $now - schedule_helper::days_to_seconds(6)
            ],
            [
                mock_recipient::RECIPIENT_IDS_KEY => [$this->user->id],
                mock_scheduled_event_resolver::EVENT_TIME_KEY => $now - schedule_helper::days_to_seconds(11)
            ],
        ]);

        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $manager = new scheduled_event_manager();
        $manager->execute($now, $now - schedule_helper::days_to_seconds(2));

        // As there is one notification preference that are meant to sent 10 days after
        // the actual event time happened. And one notification preference that are meant to
        // sent 5 days after the actual event happened.
        // Therefore there should be two notification sent by these two custom preferences
        $messages = $sink->get_messages();
        self::assertCount(2, $messages);

        // Check that messages went to expected user and match expected preferences (via subject)
        $subjects = ['Subject After 5', 'Subject After 10'];
        foreach ($messages as $message) {
            self::assertEquals($this->user->id, $message->useridto);
            self::assertContains($message->subject, $subjects);
        }
    }

    /**
     * @return void
     */
    public function test_one_notifiable_event_that_supports_on_after_and_before_with_out_built_in_preferences(): void {
        global $DB;

        $generator = generator::instance();
        $generator->purge_built_in_notifications();
        $generator->add_notifiable_event_resolver(mock_scheduled_event_resolver::class);

        $sink = self::redirectMessages();
        self::assertEquals(0, $sink->count());
        self::assertEmpty($sink->get_messages());

        mock_scheduled_event_resolver::set_scheduled_classes(
            schedule_on_event::class,
            schedule_before_event::class,
            schedule_after_event::class
        );

        // The receiver.
        $extended_context = extended_context::make_system();

        // Two custom preferences with one is 10 days before the event
        // and one is 5 days before the event
        $custom_preference_one = $generator->create_notification_preference(
            mock_scheduled_event_resolver::class,
            $extended_context,
            [
                'subject' => 'Subject Before 10',
                'recipient' => mock_recipient::class,
                'schedule_offset' => schedule_before_event::default_value(10),
            ]
        );

        $custom_preference_two = $generator->create_notification_preference(
            mock_scheduled_event_resolver::class,
            $extended_context,
            [
                'subject' => 'Subject Before 5',
                'recipient' => mock_recipient::class,
                'schedule_offset' => schedule_before_event::default_value(5),
            ]
        );

        $now = time();

        // Add two mock events, which one is happening 4 days from now,
        // and one is occurring 9 days from now.
        mock_scheduled_event_resolver::set_events([
            [
                mock_recipient::RECIPIENT_IDS_KEY => [$this->user->id],
                mock_scheduled_event_resolver::EVENT_TIME_KEY => $now + schedule_helper::days_to_seconds(4)
            ],
            [
                mock_recipient::RECIPIENT_IDS_KEY => [$this->user->id],
                mock_scheduled_event_resolver::EVENT_TIME_KEY => $now + schedule_helper::days_to_seconds(9)
            ],
        ]);

        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));
        $manager = new scheduled_event_manager();
        $manager->execute($now, $now - schedule_helper::days_to_seconds(2));

        // As there is one notification preference that are meant to sent 10 days before
        // the actual event time happened. And one notification preference that are meant to
        // sent 5 days before the actual event happened.
        // Therefore there should be two notification queued up under these two custom preferences
        $messages = $sink->get_messages();
        self::assertCount(2, $messages);

        // Check that messages went to expected user and match expected preferences (via subject)
        $subjects = ['Subject Before 5', 'Subject Before 10'];
        foreach ($messages as $message) {
            self::assertEquals($this->user->id, $message->useridto);
            self::assertContains($message->subject, $subjects);
        }
    }

    /**
     * scenario: One notifiable event that supports on, after and before. One custom notification with schedule 10 days
     * before, and one custom notification with schedule 5 days after (both exist at the same time - we're testing the
     * combination).
     * + Create an event that occurred 6 days ago. execute(time()-2*DAYSECS, time()) should send the 5 day notif.
     * + Create an event that will occur in 9 days. execute(time()-2*DAYSECS, time()) should send the 10 day notif.
     *
     * @return void
     */
    public function test_one_notifiable_event_taht_supports_on_after_and_before_in_and_with_before_and_after_event_window(): void {
        global $DB;

        $generator = generator::instance();
        $generator->purge_built_in_notifications();
        $generator->add_notifiable_event_resolver(mock_scheduled_event_resolver::class);

        $sink = self::redirectMessages();
        self::assertEquals(0, $sink->count());
        self::assertEmpty($sink->get_messages());

        mock_scheduled_event_resolver::set_scheduled_classes(
            schedule_on_event::class,
            schedule_before_event::class,
            schedule_after_event::class
        );

        // The receiver.
        $extended_context = extended_context::make_system();

        // Two custom preferences with one is 10 days before the event
        // and one is 5 days after the event
        $custom_preference_one = $generator->create_notification_preference(
            mock_scheduled_event_resolver::class,
            $extended_context,
            [
                'subject' => 'Subject Before 10',
                'recipient' => mock_recipient::class,
                'schedule_offset' => schedule_before_event::default_value(10),
            ]
        );

        $custom_preference_two = $generator->create_notification_preference(
            mock_scheduled_event_resolver::class,
            $extended_context,
            [
                'subject' => 'Subject After 5',
                'recipient' => mock_recipient::class,
                'schedule_offset' => schedule_after_event::default_value(5),
            ]
        );

        $now = time();

        // Add two mock events, which one is happening 6 days ago,
        // and one is occurring 9 days from now.
        mock_scheduled_event_resolver::set_events([
            [
                mock_recipient::RECIPIENT_IDS_KEY => [$this->user->id],
                mock_scheduled_event_resolver::EVENT_TIME_KEY => $now - schedule_helper::days_to_seconds(6)
            ],
            [
                mock_recipient::RECIPIENT_IDS_KEY => [$this->user->id],
                mock_scheduled_event_resolver::EVENT_TIME_KEY => $now + schedule_helper::days_to_seconds(9)
            ],
        ]);

        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $manager = new scheduled_event_manager();
        $manager->execute($now, $now - schedule_helper::days_to_seconds(2));

        // As there is one notification preference that are meant to sent 10 days before
        // the actual event time happened. And one notification preference that are meant to
        // sent 5 days after the actual event happened.
        // Therefore there should be two notification queued up under these two custom preferences
        $messages = $sink->get_messages();
        self::assertCount(2, $messages);

        // Check that messages went to expected user and match expected preferences (via subject)
        $subjects = ['Subject Before 10', 'Subject After 5'];
        foreach ($messages as $message) {
            self::assertEquals($this->user->id, $message->useridto);
            self::assertContains($message->subject, $subjects);
        }
    }

    /**
     * @return void
     */
    public function test_one_notifiable_event_that_supports_on_and_after_with_built_in_and_custom_preference(): void {
        global $DB;

        $generator = generator::instance();
        $generator->include_mock_scheduled_built_in_notification();
        $generator->add_notifiable_event_resolver(mock_scheduled_event_resolver::class);

        $sink = self::redirectMessages();
        self::assertEquals(0, $sink->count());
        self::assertEmpty($sink->get_messages());

        mock_scheduled_event_resolver::set_scheduled_classes(
            schedule_on_event::class,
            schedule_after_event::class,
            schedule_before_event::class
        );

        // Set the mock built in to 10 days after.
        mock_built_in::set_default_schedule_offset(schedule_after_event::default_value(10));

        // The receiver.
        $extended_context = extended_context::make_system();

        $built_in = $generator->add_mock_built_in_notification_for_component(mock_built_in::class);
        $custom_preference = $generator->create_notification_preference(
            mock_scheduled_event_resolver::class,
            $extended_context,
            [
                'subject' => 'Subject After 5',
                'recipient' => mock_recipient::class,
                'schedule_offset' => schedule_after_event::default_value(5)
            ]
        );

        $now = time();

        // Add two mock events, which one is happening 6 days ago,
        // and one is occurring 11 days ago.
        mock_scheduled_event_resolver::set_events([
            [
                mock_recipient::RECIPIENT_IDS_KEY => [$this->user->id],
                mock_scheduled_event_resolver::EVENT_TIME_KEY => $now - schedule_helper::days_to_seconds(6)
            ],
            [
                mock_recipient::RECIPIENT_IDS_KEY => [$this->user->id],
                mock_scheduled_event_resolver::EVENT_TIME_KEY => $now - schedule_helper::days_to_seconds(11)
            ],
        ]);

        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $manager = new scheduled_event_manager();
        $manager->execute($now, $now - schedule_helper::days_to_seconds(2));

        // As there is one built-in notification preference that are meant to sent 10 days after
        // the actual event time happened. And one custom notification preference that are meant to
        // sent 5 days after the actual event happened.
        // Therefore there should be two notification queued up under these two custom preferences
        $messages = $sink->get_messages();
        self::assertCount(2, $messages);

        // Check that messages went to expected user and match expected preferences (via subject)
        $subjects = ['Subject After 5', 'Centralised notification'];
        foreach ($messages as $message) {
            self::assertEquals($this->user->id, $message->useridto);
            self::assertContains($message->subject, $subjects);
        }
    }

    /**
     * @return void
     */
    public function test_scenario_on_and_after_with_built_in_and_custom_preference_where_custom_is_greater_than_built_in(): void {
        global $DB;

        $generator = generator::instance();
        $generator->include_mock_scheduled_built_in_notification();
        $generator->add_notifiable_event_resolver(mock_scheduled_event_resolver::class);

        $sink = self::redirectMessages();
        self::assertEquals(0, $sink->count());
        self::assertEmpty($sink->get_messages());

        mock_scheduled_event_resolver::set_scheduled_classes(
            schedule_on_event::class,
            schedule_after_event::class,
            schedule_before_event::class
        );

        // Set the mock built in to 5 days after.
        mock_built_in::set_default_schedule_offset(schedule_after_event::default_value(5));

        // The receiver.
        $extended_context = extended_context::make_system();

        $built_in = $generator->add_mock_built_in_notification_for_component(mock_built_in::class);
        $custom_preference = $generator->create_notification_preference(
            mock_scheduled_event_resolver::class,
            $extended_context,
            [
                'subject' => 'Subject After 10',
                'recipient' => mock_recipient::class,
                'schedule_offset' => schedule_after_event::default_value(10)
            ]
        );

        $now = time();

        // Add two mock events, which one is happening 6 days ago,
        // and one is occurring 11 days ago.
        mock_scheduled_event_resolver::set_events([
            [
                mock_recipient::RECIPIENT_IDS_KEY => [$this->user->id],
                mock_scheduled_event_resolver::EVENT_TIME_KEY => $now - schedule_helper::days_to_seconds(6)
            ],
            [
                mock_recipient::RECIPIENT_IDS_KEY => [$this->user->id],
                mock_scheduled_event_resolver::EVENT_TIME_KEY => $now - schedule_helper::days_to_seconds(11)
            ],
        ]);

        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $manager = new scheduled_event_manager();
        $manager->execute($now, $now - schedule_helper::days_to_seconds(2));

        // As there is one built-in notification preference that are meant to sent 5 days after
        // the actual event time happened. And one custom notification preference that are meant to
        // sent 10 days after the actual event happened.
        // Therefore there should be two notification queued up under these two custom preferences
        $messages = $sink->get_messages();
        self::assertCount(2, $messages);

        // Check that messages went to expected user and match expected preferences (via subject)
        $subjects = ['Subject After 10', 'Centralised notification'];
        foreach ($messages as $message) {
            self::assertEquals($this->user->id, $message->useridto);
            self::assertContains($message->subject, $subjects);
        }
    }

    /**
     * @return void
     */
    public function test_one_notifiable_event_that_supports_on_after_and_before_with_built_in_and_custom_preference(): void {
        global $DB;

        $generator = generator::instance();
        $generator->include_mock_scheduled_built_in_notification();
        $generator->add_notifiable_event_resolver(mock_scheduled_event_resolver::class);

        $sink = self::redirectMessages();
        self::assertEquals(0, $sink->count());
        self::assertEmpty($sink->get_messages());

        mock_scheduled_event_resolver::set_scheduled_classes(
            schedule_on_event::class,
            schedule_after_event::class,
            schedule_before_event::class
        );

        // Set the mock built in to 5 days after.
        mock_built_in::set_default_schedule_offset(schedule_before_event::default_value(10));

        // The receiver.
        $extended_context = extended_context::make_system();

        $built_in = $generator->add_mock_built_in_notification_for_component(mock_built_in::class);
        $custom_preference = $generator->create_notification_preference(
            mock_scheduled_event_resolver::class,
            $extended_context,
            [
                'subject' => 'Subject Before 5',
                'recipient' => mock_recipient::class,
                'schedule_offset' => schedule_before_event::default_value(5)
            ]
        );

        $now = time();

        // Add two mock events, which one is happening 4 days from now,
        // and one is occurring 9 days from now.
        mock_scheduled_event_resolver::set_events([
            [
                mock_recipient::RECIPIENT_IDS_KEY => [$this->user->id],
                mock_scheduled_event_resolver::EVENT_TIME_KEY => $now + schedule_helper::days_to_seconds(4)
            ],
            [
                mock_recipient::RECIPIENT_IDS_KEY => [$this->user->id],
                mock_scheduled_event_resolver::EVENT_TIME_KEY => $now + schedule_helper::days_to_seconds(9)
            ],
        ]);

        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $manager = new scheduled_event_manager();
        $manager->execute($now, $now - schedule_helper::days_to_seconds(2));

        // As there is one built in notification preference that are meant to sent 10 days before
        // the actual event time happened. And one custom notification preference that are meant to
        // sent 5 days before the actual event happened.
        // Therefore there should be two notification queued up under these two custom preferences
        $messages = $sink->get_messages();
        self::assertCount(2, $messages);

        // Check that messages went to expected user and match expected preferences (via subject)
        $subjects = ['Subject Before 5', 'Centralised notification'];
        foreach ($messages as $message) {
            self::assertEquals($this->user->id, $message->useridto);
            self::assertContains($message->subject, $subjects);
        }
    }

    /**
     * @return void
     */
    public function test_one_notifiable_event_with_built_in_and_custom_preference_has_after_and_before(): void {
        global $DB;

        $generator = generator::instance();
        $generator->include_mock_scheduled_built_in_notification();
        $generator->add_notifiable_event_resolver(mock_scheduled_event_resolver::class);

        $sink = self::redirectMessages();
        self::assertEquals(0, $sink->count());
        self::assertEmpty($sink->get_messages());

        mock_scheduled_event_resolver::set_scheduled_classes(
            schedule_on_event::class,
            schedule_after_event::class,
            schedule_before_event::class
        );

        // Set the mock built in to 5 days after.
        mock_built_in::set_default_schedule_offset(schedule_before_event::default_value(5));

        // The receiver.
        $extended_context = extended_context::make_system();

        $built_in = $generator->add_mock_built_in_notification_for_component(mock_built_in::class);
        $custom_preference = $generator->create_notification_preference(
            mock_scheduled_event_resolver::class,
            $extended_context,
            [
                'subject' => 'Subject Before 5',
                'recipient' => mock_recipient::class,
                'schedule_offset' => schedule_before_event::default_value(5)
            ]
        );

        $now = time();

        // Add two mock events, which one is happening 4 days from now,
        // and one is occurring 9 days from now.
        mock_scheduled_event_resolver::set_events([
            [
                mock_recipient::RECIPIENT_IDS_KEY => [$this->user->id],
                mock_scheduled_event_resolver::EVENT_TIME_KEY => $now + schedule_helper::days_to_seconds(4)
            ],
            [
                mock_recipient::RECIPIENT_IDS_KEY => [$this->user->id],
                mock_scheduled_event_resolver::EVENT_TIME_KEY => $now + schedule_helper::days_to_seconds(9)
            ],
        ]);

        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $manager = new scheduled_event_manager();
        $manager->execute($now, $now - schedule_helper::days_to_seconds(2));

        // As there is one built in notification preference that are meant to sent 4 days before
        // the actual event time happened. And one custom notification preference that are meant to
        // sent 9 days before the actual event happened.
        // Therefore there should be two notification queued up under these two custom preferences
        $messages = $sink->get_messages();
        self::assertCount(2, $messages);

        // Check that messages went to expected user and match expected preferences (via subject)
        $subjects = ['Subject Before 5', 'Centralised notification'];
        foreach ($messages as $message) {
            self::assertEquals($this->user->id, $message->useridto);
            self::assertContains($message->subject, $subjects);
        }
    }
}
