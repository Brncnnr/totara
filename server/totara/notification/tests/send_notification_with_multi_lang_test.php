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

use core\entity\user;
use core\json_editor\helper\document_helper;
use core\orm\query\builder;
use core_phpunit\testcase;
use core\json_editor\node\paragraph;
use totara_core\extended_context;
use totara_notification\observer\notifiable_event_observer;
use totara_notification\placeholder\placeholder_option;
use jsoneditor_simple_multi_lang\json_editor\node\lang_blocks;
use jsoneditor_simple_multi_lang\json_editor\node\lang_block;
use totara_notification\testing\generator;
use totara_notification_mock_notifiable_event_resolver as mock_resolver;
use totara_notification_mock_notifiable_event as mock_event;
use totara_notification\entity\notifiable_event_queue;
use totara_notification\entity\notification_queue;
use totara_notification\manager\event_queue_manager;
use core\json_editor\node\text;
use totara_notification\json_editor\node\placeholder;
use core_user\totara_notification\placeholder\user as user_placeholder;

class totara_notification_send_notification_with_multi_lang_testcase extends testcase {
    /**
     * @return void
     */
    protected function setUp(): void {
        global $CFG;
        require_once("{$CFG->libdir}/filterlib.php");

        $generator = generator::instance();
        $generator->include_mock_notifiable_event_resolver();
        $generator->include_mock_notifiable_event();
    }

    /**
     * @return void
     */
    public function test_send_message_with_multi_lang_content(): void {
        $db = builder::get_db();
        $extended_context = extended_context::make_system();

        // Enable multi lang filter at system cotnext
        $record = new stdClass();
        $record->filter = 'multilang';
        $record->contextid = $extended_context->get_context_id();
        $record->active = TEXTFILTER_ON;
        $db->insert_record('filter_active', $record);

        $generator = self::getDataGenerator();
        $user_one = $generator->create_user(['lang' => 'en']);

        $document = document_helper::create_document_from_content_nodes([
            paragraph::create_json_node_from_text('This is a test paragraph'),
            lang_blocks::create_raw_node([
                lang_block::create_raw_json_node('en', 'An english text'),
                lang_block::create_raw_json_node('fr', 'A french text')
            ])
        ]);

        $notification_generator = generator::instance();
        $notification_generator->add_mock_recipient_ids_to_resolver([$user_one->id]);

        $preference = $notification_generator->create_notification_preference(
            mock_resolver::class,
            $extended_context,
            [
                'body' => json_encode($document),
                'body_format' => FORMAT_JSON_EDITOR,
                'subject' => json_encode($document),
                'subject_format' => FORMAT_JSON_EDITOR
            ]
        );

        self::assertEquals(0, $db->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $db->count_records(notification_queue::TABLE));

        $sink = self::redirectMessages();
        self::assertEquals(0, $sink->count());
        self::assertEmpty($sink->get_messages());

        $event = new mock_event($extended_context->get_context_id());
        notifiable_event_observer::watch_notifiable_event($event);

        self::assertEquals(1, $db->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $db->count_records(notification_queue::TABLE));

        $event_queue_manager = new event_queue_manager();
        $event_queue_manager->process_queues();

        self::assertEquals(0, $db->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $db->count_records(notification_queue::TABLE));

        $messages = $sink->get_messages();
        self::assertCount(1, $messages);

        $first_message = reset($messages);
        self::assertIsObject($first_message);

        self::assertObjectHasAttribute('totara_notification_notification_preference_id', $first_message);
        self::assertEquals(
            $preference->get_id(),
            $first_message->totara_notification_notification_preference_id
        );

        self::assertObjectHasAttribute('fullmessagehtml', $first_message);
        self::assertObjectHasAttribute('fullmessage', $first_message);

        self::assertStringNotContainsString(
            "A french text",
            $first_message->fullmessagehtml
        );

        self::assertEquals(
            /** @lang text */
            "<p>This is a test paragraph</p><p>An english text</p>",
            $first_message->fullmessagehtml
        );

        self::assertEquals(
            "This is a test paragraph\n\nAn english text",
            trim($first_message->fullmessage)
        );

        self::assertObjectHasAttribute('subject', $first_message);
        self::assertEquals('This is a test paragraph An english text', $first_message->subject);
    }

    /**
     * @return void
     */
    public function test_send_notification_with_multi_lang_and_placeholder(): void {
        $db = builder::get_db();
        $extended_context = extended_context::make_system();

        // Enable multi lang filter at system cotnext
        $record = new stdClass();
        $record->filter = 'multilang';
        $record->contextid = $extended_context->get_context_id();
        $record->active = TEXTFILTER_ON;
        $db->insert_record('filter_active', $record);

        $generator = self::getDataGenerator();
        $user_one = $generator->create_user();

        $notification_generator = generator::instance();
        $notification_generator->include_mock_notifiable_event();
        $notification_generator->include_mock_notifiable_event_resolver();
        $notification_generator->add_mock_recipient_ids_to_resolver([$user_one->id]);

        mock_resolver::add_placeholder_options(
            placeholder_option::create(
                'target',
                user_placeholder::class,
                $notification_generator->give_my_mock_lang_string('Target user'),
                function (array $event_data): user_placeholder {
                    $user = new user($event_data['user_id']);
                    return new user_placeholder($user);
                }
            )
        );

        $custom_preference = $notification_generator->create_notification_preference(
            mock_resolver::class,
            $extended_context,
            [
                'body' => json_encode(
                    document_helper::create_document_from_content_nodes([
                        paragraph::create_json_node_from_text('This is text'),
                        lang_blocks::create_raw_node([
                            lang_block::create_raw_json_node_from_paragraph_nodes(
                                'en',
                                [
                                    paragraph::create_json_node_with_content_nodes([
                                        text::create_json_node_from_text('Hello user '),
                                        placeholder::create_node_from_key_and_label('target:first_name', 'Random label'),
                                    ]),
                                    paragraph::create_json_node_from_text('From english')
                                ]
                            ),
                            lang_block::create_raw_json_node_from_paragraph_nodes(
                                'fr',
                                [
                                    paragraph::create_json_node_with_content_nodes([
                                        text::create_json_node_from_text('Hello user '),
                                        placeholder::create_node_from_key_and_label('target:last_name', 'Random label'),
                                    ]),
                                    paragraph::create_json_node_from_text('From french')
                                ]
                            )
                        ]),
                        paragraph::create_json_node_with_content_nodes([
                            text::create_json_node_from_text('User full name is '),
                            placeholder::create_node_from_key_and_label('target:full_name', 'Random label')
                        ])
                    ])
                ),
                'body_format' => FORMAT_JSON_EDITOR
            ]
        );

        self::assertEquals(0, $db->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $db->count_records(notification_queue::TABLE));

        $sink = self::redirectMessages();
        self::assertEmpty($sink->get_messages());

        $event = new mock_event(
            $extended_context->get_context_id(),
            ['user_id' => $user_one->id]
        );
        notifiable_event_observer::watch_notifiable_event($event);

        self::assertEquals(1, $db->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $db->count_records(notification_queue::TABLE));

        $event_manager = new event_queue_manager();
        $event_manager->process_queues();

        self::assertEquals(0, $db->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $db->count_records(notification_queue::TABLE));

        $messages = $sink->get_messages();
        self::assertCount(1, $messages);

        $first_message = reset($messages);
        self::assertIsObject($first_message);

        self::assertObjectHasAttribute('totara_notification_notification_preference_id', $first_message);
        self::assertEquals(
            $custom_preference->get_id(),
            $first_message->totara_notification_notification_preference_id
        );

        self::assertObjectHasAttribute('fullmessage', $first_message);
        self::assertObjectHasAttribute('fullmessagehtml', $first_message);

        $user_one->fullname = fullname($user_one);
        self::assertEquals(
            "This is text\n\nHello user {$user_one->firstname}\n\nFrom english\n\nUser full name is {$user_one->fullname}\n",
            $first_message->fullmessage
        );

        self::assertEquals(
            implode(
                '',
                [
                    /** @lang text */ '<p>This is text</p>',
                    /** @lang text */ '<p>Hello user ',
                    /** @lang text */ '<span data-key="target:first_name" data-label="Random label">',
                    /** @lang text */ "{$user_one->firstname}</span></p>",
                    /** @lang text */ '<p>From english</p>',
                    /** @lang text */ '<p>User full name is ',
                    /** @lang text */ '<span data-key="target:full_name" data-label="Random label">',
                    /** @lang text */ "{$user_one->fullname}</span></p>"
                ]
            ),
            $first_message->fullmessagehtml
        );
    }

    /**
     * @return void
     */
    public function test_send_notification_with_multi_lang_disabled_at_lower_context(): void {
        $db = builder::get_db();
        $system = extended_context::make_system();

        $system_record = new stdClass();
        $system_record->filter = 'multilang';
        $system_record->contextid = $system->get_context_id();
        $system_record->active = TEXTFILTER_ON;
        $db->insert_record('filter_active', $system_record);

        $generator = self::getDataGenerator();
        $user_one = $generator->create_user();
        $course = $generator->create_course();

        $context_course = extended_context::make_with_context(context_course::instance($course->id));
        $course_record = new stdClass();
        $course_record->filter = 'multilang';
        $course_record->contextid = $context_course->get_context_id();
        $course_record->active = TEXTFILTER_OFF;
        $db->insert_record('filter_active', $course_record);

        $notification_generator = generator::instance();
        $notification_generator->add_mock_recipient_ids_to_resolver([$user_one->id]);
        $notification_generator->include_mock_notifiable_event();
        $notification_generator->include_mock_notifiable_event_resolver();

        $custom_preference = $notification_generator->create_notification_preference(
            mock_resolver::class,
            $context_course,
            [
                'body_format' => FORMAT_JSON_EDITOR,
                'body' => json_encode(
                    document_helper::create_document_from_content_nodes([
                        paragraph::create_json_node_from_text('Da ta a'),
                        lang_blocks::create_raw_node([
                            lang_block::create_raw_json_node('en', 'This is english'),
                            lang_block::create_raw_json_node('fr', 'This is french')
                        ])
                    ])
                )
            ]
        );

        self::assertEquals(0, $db->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $db->count_records(notification_queue::TABLE));

        $sink = self::redirectMessages();
        self::assertEmpty($sink->get_messages());

        $event = new mock_event(
            $context_course->get_context_id(),
            ['user_id' => $user_one->id]
        );

        notifiable_event_observer::watch_notifiable_event($event);

        self::assertEquals(1, $db->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $db->count_records(notification_queue::TABLE));

        $event_manager = new event_queue_manager();
        $event_manager->process_queues();

        self::assertEquals(0, $db->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $db->count_records(notification_queue::TABLE));

        $messages = $sink->get_messages();
        self::assertCount(1, $messages);

        $first_message = reset($messages);
        self::assertIsObject($first_message);

        self::assertObjectHasAttribute('totara_notification_notification_preference_id', $first_message);
        self::assertEquals(
            $custom_preference->get_id(),
            $first_message->totara_notification_notification_preference_id
        );

        self::assertObjectHasAttribute('fullmessage', $first_message);
        self::assertObjectHasAttribute('fullmessagehtml', $first_message);

        $user_one->fullname = fullname($user_one);
        self::assertEquals(
            "Da ta a\n\nThis is english\n\nThis is french\n",
            $first_message->fullmessage
        );

        self::assertStringContainsString(
            /** @lang text */ "<p>Da ta a</p>",
            $first_message->fullmessagehtml
        );

        self::assertStringContainsString(
            /** @lang text */ '<span class="multilang" lang="en"><p>This is english</p></span>',
            $first_message->fullmessagehtml
        );

        self::assertStringContainsString(
            /** @lang text */ '<span class="multilang" lang="fr"><p>This is french</p></span>',
            $first_message->fullmessagehtml
        );
    }
}
