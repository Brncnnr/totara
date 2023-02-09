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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_notification
 */

use core_phpunit\testcase;
use message_email\totara_notification\delivery\channel\delivery_channel as email_channel;
use message_popup\totara_notification\delivery\channel\delivery_channel as popup_channel;
use totara_core\extended_context;
use totara_notification\entity\notifiable_event_queue;
use totara_notification\entity\notification_queue;
use totara_notification\manager\event_queue_manager;
use totara_notification\observer\notifiable_event_observer;
use totara_notification_mock_notifiable_event as mock_event;
use totara_notification_mock_notifiable_event_resolver as mock_resolver;
use totara_notification\model\notifiable_event_preference;
use totara_notification\testing\generator;

/**
 * @group totara_notification
 */
class totara_notification_send_notification_with_virtual_users_test extends testcase {
    use \totara_notification\testing\notification_log_test_trait;

    /**
     * @return void
     */
    protected function setUp(): void {
        $generator = generator::instance();
        $generator->include_mock_notifiable_event_resolver();
        $generator->include_mock_notifiable_event();
        $generator->include_mock_virtual_recipient();
    }

    /**
     * @return void
     */
    public function test_send_notification_to_email_with_virtual_users(): void {
        global $DB;

        $generator = generator::instance();
        $extended_context = extended_context::make_system();

        mock_resolver::set_notification_available_recipients([
            totara_notification_mock_virtual_recipient::class,
        ]);

        $notification_preference = $generator->create_notification_preference(
            mock_resolver::class,
            $extended_context,
            [
                'forced_delivery_channels' => [],
                'recipient' => totara_notification_mock_virtual_recipient::class,
                'recipients' => [totara_notification_mock_virtual_recipient::class],
            ]
        );

        $event_preference = notifiable_event_preference::create(mock_resolver::class, $extended_context);
        $event_preference->set_default_delivery_channels([
            email_channel::make(true),
            popup_channel::make(true)
        ]);

        $event_preference->save();
        $event = new mock_event($extended_context->get_context_id());

        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        notifiable_event_observer::watch_notifiable_event($event);

        self::assertEquals(1, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $sink = self::redirectMessages();
        self::assertEquals(0, $sink->count());
        self::assertEmpty($sink->get_messages());

        $event_manager = new event_queue_manager();
        $event_manager->process_queues();

        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        self::assertEquals(1, $sink->count());
        $messages = $sink->get_messages();

        self::assertCount(1, $messages);
        $first_message = reset($messages);

        self::assertIsObject($first_message);
        self::assertObjectHasAttribute('totara_notification_delivery_channels', $first_message);
        self::assertEqualsCanonicalizing(
            ['email', 'popup'],
            json_decode($first_message->totara_notification_delivery_channels)
        );

        self::assertObjectHasAttribute('totara_notification_notification_preference_id', $first_message);
        self::assertEquals(
            $notification_preference->get_id(),
            $first_message->totara_notification_notification_preference_id
        );

        self::assertObjectHasAttribute('useridto', $first_message);
        self::assertEquals(-45, $first_message->useridto); // !

        // Check the logs
        $delivery_channels = json_decode($first_message->totara_notification_delivery_channels);
        self::verify_notification_logs([
            [
                'context_id' => context_system::instance()->id,
                'logs' => [
                    [
                        'preference_id' => $notification_preference->get_id(),
                        'recipients' => 1,
                        'channels' => count($delivery_channels),
                        'address' => 'example@test.com',
                    ],
                ],
            ],
        ]);
    }
}
