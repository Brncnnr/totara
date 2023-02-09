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
use totara_notification\manager\event_queue_manager;
use totara_notification\manager\notification_queue_manager;
use totara_notification\observer\notifiable_event_observer;
use totara_notification\testing\generator;
use totara_notification_mock_notifiable_event_resolver as mock_resolver;
use totara_notification_mock_notifiable_event as mock_event;
use totara_notification\entity\notifiable_event_queue;
use totara_notification\entity\notification_queue;

class totara_notification_multi_lang_testcase extends testcase {
    /**
     * @return void
     */
    protected function setUp(): void {
        global $DB;

        // Requiring the files needed for tests.
        $generator = generator::instance();
        $generator->include_mock_notifiable_event_resolver();
        $generator->include_mock_notifiable_event();

        // Enable multi-lang filter for the tests.
        $context_system = context_system::instance();
        $record = new stdClass();
        $record->filter = 'multilang';
        $record->contextid = $context_system->id;
        $record->active = 1;

        $DB->insert_record('filter_active', $record);
    }

    /**
     * @return void
     */
    public function test_multi_lang_filter(): void {
        global $CFG;
        $content = implode(
            " ",
            [
                /** @lang text */ '<span lang="en" class="multilang">English</span>',
                /** @lang text */ '<span lang="ru" class="multilang">Russian</span>'
            ]
        );

        require_once("{$CFG->dirroot}/filter/multilang/filter.php");
        $context = context_system::instance();
        $filter = new filter_multilang($context, []);

        self::assertNotEquals($content, $filter->filter($content, []));
    }

    /**
     * Expecting that format plain would not enforce the filter text for multi lang.
     * @return void
     */
    public function test_sending_notification_with_multi_lang_filter_for_format_plain(): void {
        global $DB;
        $user_one = self::getDataGenerator()->create_user();

        $generator = generator::instance();
        $extended_context = extended_context::make_system();

        $generator->add_mock_recipient_ids_to_resolver([$user_one->id]);
        $body_message = implode(
            " ",
            [
                /** @lang text */ '<span lang="en" class="multilang">English</span>',
                /** @lang text */ '<span lang="ru" class="multilang">Russian</span>'
            ]
        );

        $custom_preference = $generator->create_notification_preference(
            mock_resolver::class,
            $extended_context,
            [
                'body' => $body_message,
                'body_format' => FORMAT_PLAIN
            ]
        );

        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $event = new mock_event($extended_context->get_context_id());
        notifiable_event_observer::watch_notifiable_event($event);

        $sink = self::redirectMessages();
        self::assertEmpty($sink->get_messages());
        self::assertEquals(0, $sink->count());

        self::assertEquals(1, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $event_manager = new event_queue_manager();
        $event_manager->process_queues();

        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $messages = $sink->get_messages();
        self::assertNotEmpty($messages);
        self::assertCount(1, $messages);
        self::assertEquals(1, $sink->count());

        $first_message = reset($messages);
        self::assertObjectHasAttribute('totara_notification_notification_preference_id', $first_message);
        self::assertEquals(
            $custom_preference->get_id(),
            $first_message->totara_notification_notification_preference_id
        );

        self::assertObjectHasAttribute('fullmessage', $first_message);
        self::assertObjectHasAttribute('fullmessagehtml', $first_message);

        self::assertEquals(s($body_message), $first_message->fullmessagehtml);
        self::assertEquals($body_message, $first_message->fullmessage);
    }

    /**
     * @return void
     */
    public function test_sending_notification_with_multi_lang_filter_for_format_moodle(): void {
        global $DB;

        // Set the language for admin user.
        $generator = self::getDataGenerator();
        $user_one = $generator->create_user();

        $notification_generator = generator::instance();
        $notification_generator->add_mock_recipient_ids_to_resolver([$user_one->id]);

        $extended_context = extended_context::make_system();
        $body_message = implode(
            ' ',
            [
                /** @lang text */ '<span lang="en" class="multilang">English</span>',
                /** @lang text */ '<span lang="vi" class="multilang">Vietnamese</span>'
            ]
        );

        $custom_preference = $notification_generator->create_notification_preference(
            mock_resolver::class,
            $extended_context,
            [
                'body' => $body_message,
                'body_format' => FORMAT_MOODLE
            ]
        );

        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $event = new mock_event($extended_context->get_context_id());
        notifiable_event_observer::watch_notifiable_event($event);

        self::assertEquals(1, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $sink = self::redirectMessages();
        self::assertEmpty($sink->get_messages());
        self::assertEquals(0, $sink->count());

        $event_manager = new event_queue_manager();
        $event_manager->process_queues();

        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $messages = $sink->get_messages();
        self::assertNotEmpty($messages);
        self::assertCount(1, $messages);
        self::assertEquals(1, $sink->count());

        $first_message = reset($messages);
        self::assertObjectHasAttribute('totara_notification_notification_preference_id', $first_message);
        self::assertEquals(
            $custom_preference->get_id(),
            $first_message->totara_notification_notification_preference_id
        );

        self::assertObjectHasAttribute('fullmessagehtml', $first_message);
        self::assertNotEquals(s($body_message), $first_message->fullmessagehtml);
        self::assertEquals(
            /** @lang text */'<div class="text_to_html">English</div>',
            $first_message->fullmessagehtml
        );

        self::assertObjectHasAttribute('fullmessage', $first_message);
        self::assertNotEquals($body_message, $first_message->fullmessage);
    }
}
