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

use core\json_editor\helper\document_helper;
use core\json_editor\node\paragraph;
use core\json_editor\node\text;
use core_phpunit\testcase;
use totara_core\extended_context;
use core_user\totara_notification\placeholder\user;
use totara_notification\entity\notifiable_event_queue;
use totara_notification\entity\notification_queue;
use totara_notification\json_editor\node\placeholder;
use totara_notification\manager\event_queue_manager;
use totara_notification\observer\notifiable_event_observer;
use totara_notification\placeholder\placeholder_option;
use totara_notification\testing\generator;
use totara_notification_mock_notifiable_event as mock_notifiable_event;
use totara_notification_mock_notifiable_event_resolver as mock_notifiable_event_resolver;

/**
 * @group totara_notification
 */
class totara_notification_send_notification_with_placeholder_testcase extends testcase {
    /**
     * @return void
     */
    protected function setUp(): void {
        $generator = generator::instance();

        $generator->include_mock_notifiable_event();
        $generator->include_mock_single_placeholder();
        $generator->include_mock_notifiable_event_resolver();
    }

    /**
     * @return void
     */
    public function test_send_custom_notification_with_placeholder(): void {
        global $DB;
        $generator = self::getDataGenerator();

        // This is the owner
        $user_one = $generator->create_user();

        // This is the author
        $user_two = $generator->create_user();
        $user_two->fullname = fullname($user_two);

        /** @var generator $notification_generator */
        $notification_generator = $generator->get_plugin_generator('totara_notification');
        $context_system = context_system::instance();

        mock_notifiable_event_resolver::add_placeholder_options(
            placeholder_option::create(
                'owner',
                user::class,
                $notification_generator->give_my_mock_lang_string('Owner'),
                function (array $event_data): user {
                    return user::from_id($event_data['owner_id']);
                }
            ),

            placeholder_option::create(
                'author',
                user::class,
                $notification_generator->give_my_mock_lang_string('Author'),
                function (array $event_data): user {
                    return user::from_id($event_data['author_id']);
                }
            )
        );

        $notification_generator->add_mock_recipient_ids_to_resolver([$user_one->id]);
        $notification_generator->create_notification_preference(
            mock_notifiable_event_resolver::class,
            extended_context::make_with_context(context_system::instance()),
            [
                'title' => 'This is custom notification',
                'subject' => 'A notification for [owner:first_name]',
                'body' =>
                    'Hello [owner:first_name], a user [author:full_name] had make your item ' .
                    'to this his/her timezone [author:time_zone]',
                'body_format' => FORMAT_MOODLE,
                'recipient' => totara_notification_mock_recipient::class,
            ]
        );

        $event = new mock_notifiable_event(
            $context_system->id,
            [
                'owner_id' => $user_one->id,
                'author_id' => $user_two->id,
                'expected_context_id' => $context_system->id,
            ]
        );

        notifiable_event_observer::watch_notifiable_event($event);
        self::assertEquals(1, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $sink = self::redirectMessages();
        self::assertEquals(0, $sink->count());
        self::assertEmpty($sink->get_messages());

        $queue_manager = new event_queue_manager();
        $queue_manager->process_queues();

        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        self::assertEquals(1, $sink->count());
        $messages = $sink->get_messages();

        self::assertCount(1, $messages);
        $message = reset($messages);

        self::assertObjectHasAttribute('fullmessage', $message);
        self::assertEquals(
            "Hello {$user_one->firstname}, a user {$user_two->fullname} had make your item to this his/her timezone " .
            core_date::get_localised_timezone(core_date::get_user_timezone($user_two)),
            $message->fullmessage
        );

        self::assertObjectHasAttribute('subject', $message);
        self::assertEquals("A notification for {$user_one->firstname}", $message->subject);

        self::assertObjectHasAttribute('userto', $message);
        self::assertIsObject($message->userto);
        self::assertEquals($user_one->id, $message->userto->id);
    }

    /**
     * @return void
     */
    public function test_send_custom_notification_with_placeholder_at_lower_context(): void {
        global $DB;
        $generator = self::getDataGenerator();

        // Author user
        $user_one = $generator->create_user();
        $user_one->fullname = fullname($user_one);

        // Commenter user
        $user_two = $generator->create_user();
        $user_two->fullname = fullname($user_two);

        /** @var generator $notification_generator */
        $notification_generator = $generator->get_plugin_generator('totara_notification');
        $notification_generator->add_mock_recipient_ids_to_resolver([$user_one->id]);

        $custom_notification = $notification_generator->create_notification_preference(
            mock_notifiable_event_resolver::class,
            extended_context::make_with_context(context_system::instance()),
            [
                'subject' => 'Hello [author:first_name], a new notification for you',
                'body' => 'Hello [author:full_name], user [commenter:full_name] had created a new comemnt in your code',
                'body_format' => FORMAT_MOODLE,
                'recipient' => totara_notification_mock_recipient::class,
            ]
        );

        $course = $generator->create_course();
        $context_course = context_course::instance($course->id);

        $notification_generator->create_overridden_notification_preference(
            $custom_notification,
            extended_context::make_with_context($context_course),
            ['body' => 'User [commenter:full_name] had created a new comment in [author:first_name]\'s code']
        );

        mock_notifiable_event_resolver::add_placeholder_options(
            placeholder_option::create(
                'author',
                user::class,
                $notification_generator->give_my_mock_lang_string('Author'),
                function (array $event_data): user {
                    return user::from_id($event_data['author_id']);
                }
            ),

            placeholder_option::create(
                'commenter',
                user::class,
                $notification_generator->give_my_mock_lang_string('Commenter'),
                function (array $event_data): user {
                    return user::from_id($event_data['commenter_id']);
                }
            )
        );

        // Now start sending a message out to the user one.
        $event = new mock_notifiable_event(
            $context_course->id,
            [
                'author_id' => $user_one->id,
                'commenter_id' => $user_two->id,
                'expected_context_id' => $context_course->id,
            ]
        );

        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        notifiable_event_observer::watch_notifiable_event($event);

        self::assertEquals(1, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $sink = $this->redirectMessages();
        self::assertEquals(0, $sink->count());
        self::assertEmpty($sink->get_messages());

        $event_queue_manager = new event_queue_manager();
        $event_queue_manager->process_queues();

        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        self::assertEquals(1, $sink->count());
        $messages = $sink->get_messages();

        self::assertCount(1, $messages);
        $message = reset($messages);

        self::assertIsObject($message);

        self::assertObjectHasAttribute('fullmessage', $message);
        self::assertNotEquals(
            "Hello {$user_one->fullname}, user {$user_two->fullname} had created a new comment in your code",
            $message->fullmessage
        );

        self::assertEquals(
            "User {$user_two->fullname} had created a new comment in {$user_one->firstname}'s code",
            $message->fullmessage
        );

        self::assertObjectHasAttribute('fullmessagehtml', $message);
        self::assertNotEquals(
            text_to_html("Hello {$user_one->fullname}, user {$user_two->fullname} had created a new comment in your code"),
            $message->fullmessagehtml
        );

        self::assertEquals(
            text_to_html("User {$user_two->fullname} had created a new comment in {$user_one->firstname}'s code"),
            $message->fullmessagehtml
        );

        self::assertObjectHasAttribute('subject', $message);
        self::assertEquals(
            "Hello {$user_one->firstname}, a new notification for you",
            $message->subject
        );
    }

    /**
     * @return void
     */
    public function test_send_notification_with_json_editor_for_subject_and_body(): void {
        global $DB;
        $generator = self::getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var generator $notification_generator */
        $notification_generator = $generator->get_plugin_generator('totara_notification');
        $notification_generator->add_mock_recipient_ids_to_resolver([$user_one->id]);

        $context_system = context_system::instance();
        $notification_generator->create_notification_preference(
            mock_notifiable_event_resolver::class,
            extended_context::make_with_context(context_system::instance()),
            [
                'subject_format' => FORMAT_JSON_EDITOR,
                'subject' => document_helper::json_encode_document(
                    document_helper::create_document_from_content_nodes([
                        paragraph::create_json_node_with_content_nodes([
                            text::create_json_node_from_text('Hello '),
                            placeholder::create_node_from_key_and_label('user_one:first_name', 'User\'s first name'),
                        ]),
                    ])
                ),
                'body_format' => FORMAT_JSON_EDITOR,
                'body' => document_helper::json_encode_document(
                    document_helper::create_document_from_content_nodes([
                        paragraph::create_json_node_from_text('Boom user'),
                        paragraph::create_json_node_with_content_nodes([
                            text::create_json_node_from_text('User\'s two full name from first name and last name is '),
                            placeholder::create_node_from_key_and_label('user_two:first_name', 'User\'s first name'),
                            text::create_json_node_from_text(' '),
                            placeholder::create_node_from_key_and_label('user_two:last_name', 'User\'s last name'),
                        ]),
                    ])
                ),
                'recipient' => totara_notification_mock_recipient::class,
            ]
        );

        mock_notifiable_event_resolver::add_placeholder_options(
            placeholder_option::create(
                'user_one',
                user::class,
                $notification_generator->give_my_mock_lang_string('User one'),
                function (array $event_data): user {
                    return user::from_id($event_data['user_one_id']);
                }
            ),
            placeholder_option::create(
                'user_two',
                user::class,
                $notification_generator->give_my_mock_lang_string('User two'),
                function (array $event_data): user {
                    return user::from_id($event_data['user_two_id']);
                }
            )
        );

        $event = new mock_notifiable_event($context_system->id, [
            'user_one_id' => $user_one->id,
            'user_two_id' => $user_two->id,
            'expected_context_id' => $context_system->id,
        ]);

        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        notifiable_event_observer::watch_notifiable_event($event);

        self::assertEquals(1, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $sink = $this->redirectMessages();
        self::assertEquals(0, $sink->count());
        self::assertEmpty($sink->get_messages());

        $event_queue_manager = new event_queue_manager();
        $event_queue_manager->process_queues();

        self::assertEquals(1, $sink->count());
        $messages = $sink->get_messages();

        self::assertNotEmpty($messages);
        self::assertCount(1, $messages);

        $message = reset($messages);
        self::assertIsObject($message);

        self::assertObjectHasAttribute('subject', $message);
        self::assertEquals(
            "Hello {$user_one->firstname}",
            $message->subject
        );

        self::assertObjectHasAttribute('fullmessage', $message);

        // Note that the html_to_text is not provided with the length for optional, hence we have a result of first name and
        // last name are separated from each other.
        self::assertEquals(
            "Boom user\n\nUser's two full name from first name and last name is {$user_two->firstname} {$user_two->lastname}\n",
            $message->fullmessage
        );

        self::assertObjectHasAttribute('fullmessagehtml', $message);
        self::assertEquals(
            implode(
                '',
                [
                    /** @lang text */ "<p>Boom user</p>",
                    /** @lang text */ "<p>User&#039;s two full name from first name and last name is ",
                    /** @lang text */ '<span data-key="user_two:first_name" data-label="User&#039;s first name">',
                    /** @lang text */ "{$user_two->firstname}</span> ",
                    /** @lang text */ '<span data-key="user_two:last_name" data-label="User&#039;s last name">',
                    /** @lang text */ "{$user_two->lastname}</span>",
                    /** @lang text */ "</p>",
                ]
            ),
            $message->fullmessagehtml
        );
    }
}
