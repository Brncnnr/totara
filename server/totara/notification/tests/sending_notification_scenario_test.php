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
use totara_notification\builder\notification_preference_builder;
use totara_notification\entity\notification_preference;
use totara_notification\entity\notification_queue;
use totara_notification\event\notifiable_event;
use totara_notification\manager\event_queue_manager;
use totara_notification\observer\notifiable_event_observer;
use totara_notification\testing\generator;
use totara_notification_mock_built_in_notification as mock_built_in;
use totara_notification_mock_notifiable_event as mock_event;
use totara_notification_mock_notifiable_event_resolver as mock_resolver;

class totara_notification_sending_notification_scenario_testcase extends testcase {
    /**
     * @return void
     */
    protected function setUp(): void {
        $generator = generator::instance();
        $generator->include_mock_notifiable_event_resolver();
        $generator->include_mock_notifiable_event();
        $generator->include_mock_recipient();
    }

    /**
     * @param notifiable_event $event
     * @return void
     */
    private function queue_notifications(notifiable_event $event): void {
        notifiable_event_observer::watch_notifiable_event($event);
        $event_queue_manager = new event_queue_manager();
        $event_queue_manager->process_queues();
    }

    /**
     * Built-in notifications are inherited in lower contexts.
     *
     * @return void
     */
    public function test_built_in_notification_is_inherited_in_lower_context(): void {
        global $DB;
        $generator = self::getDataGenerator();

        $sink = $this->redirectMessages();
        self::assertEquals(0, $sink->count());
        self::assertCount(0, $sink->get_messages());

        $user_one = $generator->create_user();
        $course = $generator->create_course();

        $notification_generator = generator::instance();
        $system_built_in = $notification_generator->add_mock_built_in_notification_for_component();

        $notification_generator->add_mock_recipient_ids_to_resolver([$user_one->id]);
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $context_course = context_course::instance($course->id);
        $this->queue_notifications(new mock_event($context_course->id));

        $sent_messages = $sink->get_messages();
        self::assertCount(1, $sent_messages);

        $sent_message = reset($sent_messages);
        self::assertIsObject($sent_message);

        self::assertObjectHasAttribute('fullmessage', $sent_message);
        self::assertObjectHasAttribute('subject', $sent_message);
        self::assertObjectHasAttribute('fullmessageformat', $sent_message);

        self::assertEquals($system_built_in->get_subject(), $sent_message->subject);
        self::assertEquals($system_built_in->get_body(), $sent_message->fullmessage);
        self::assertEquals(FORMAT_PLAIN, $sent_message->fullmessageformat);

        self::assertObjectHasAttribute('useridto', $sent_message);
        self::assertEquals($user_one->id, $sent_message->useridto);

        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));
    }

    /**
     * Custom notifications are inherited in lower contexts.
     *
     * @return void
     */
    public function test_custom_notification_is_inherited_in_lower_contexts(): void {
        global $DB;
        $generator = self::getDataGenerator();

        $user_one = $generator->create_user();
        $course = $generator->create_course();

        $notification_generator = generator::instance();
        $notification_generator->add_mock_recipient_ids_to_resolver([$user_one->id]);

        $sink = $this->redirectMessages();

        $system_custom = $notification_generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_with_context(context_system::instance()),
            [
                'title' => 'System custom',
                'subject' => 'System subject',
                'body' => 'System body',
                'body_format' => FORMAT_PLAIN,
                'subject_format' => FORMAT_PLAIN,
                'recipient' => totara_notification_mock_recipient::class,
            ]
        );

        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $context_course = context_course::instance($course->id);
        $this->queue_notifications(new mock_event($context_course->id));

        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $sent_messages = $sink->get_messages();
        self::assertCount(1, $sent_messages);

        $sent_message = reset($sent_messages);
        self::assertIsObject($sent_message);

        self::assertObjectHasAttribute('fullmessage', $sent_message);
        self::assertObjectHasAttribute('subject', $sent_message);
        self::assertObjectHasAttribute('fullmessageformat', $sent_message);

        self::assertEquals($system_custom->get_subject(), $sent_message->subject);
        self::assertEquals($system_custom->get_body(), $sent_message->fullmessage);
        self::assertEquals($system_custom->get_body_format(), $sent_message->fullmessageformat);

        self::assertObjectHasAttribute('useridto', $sent_message);
        self::assertEquals($user_one->id, $sent_message->useridto);

        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));
    }

    /**
     * Override built-in notification in system context
     * @return void
     */
    public function test_override_built_in_notification_in_system_context(): void {
        global $DB;
        $generator = self::getDataGenerator();

        $user_one = $generator->create_user();
        $course = $generator->create_course();

        /** @var generator $notification_generator */
        $notification_generator = $generator->get_plugin_generator('totara_notification');
        $notification_generator->add_mock_recipient_ids_to_resolver([$user_one->id]);

        $sink = $this->redirectMessages();
        self::assertEquals(0, $sink->count());
        self::assertCount(0, $sink->get_messages());

        $system_built_in = $notification_generator->add_mock_built_in_notification_for_component();

        self::assertEquals(
            mock_built_in::get_default_subject()->out(),
            $system_built_in->get_subject()
        );

        $builder = notification_preference_builder::from_exist_model($system_built_in);
        $builder->set_subject('This is overridden subject');
        $builder->save();

        $system_built_in->refresh();
        self::assertNotEquals(mock_built_in::get_default_subject()->out(), $system_built_in->get_subject());
        self::assertEquals('This is overridden subject', $system_built_in->get_subject());

        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $context_course = context_course::instance($course->id);
        $this->queue_notifications(new mock_event($context_course->id));

        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $sent_messages = $sink->get_messages();
        self::assertCount(1, $sent_messages);

        $sent_message = reset($sent_messages);
        self::assertIsObject($sent_message);

        self::assertObjectHasAttribute('fullmessage', $sent_message);
        self::assertObjectHasAttribute('subject', $sent_message);
        self::assertObjectHasAttribute('fullmessageformat', $sent_message);

        self::assertNotEquals(mock_built_in::get_default_subject()->out(), $sent_message->subject);
        self::assertEquals($system_built_in->get_subject(), $sent_message->subject);
        self::assertEquals($system_built_in->get_body(), $sent_message->fullmessage);
        self::assertEquals(FORMAT_PLAIN, $sent_message->fullmessageformat);

        self::assertObjectHasAttribute('useridto', $sent_message);
        self::assertEquals($user_one->id, $sent_message->useridto);

        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));
    }

    /**
     * Remove system context override does not damage built-in notification Override.
     *
     * @return void
     */
    public function test_remove_system_context_override_does_not_damage_built_in_notification(): void {
        global $DB;
        $generator = self::getDataGenerator();

        $user_one = $generator->create_user();
        $course = $generator->create_course();

        /** @var generator $notification_generator */
        $notification_generator = $generator->get_plugin_generator('totara_notification');
        $notification_generator->add_mock_recipient_ids_to_resolver([$user_one->id]);
        $system_built_in = $notification_generator->add_mock_built_in_notification_for_component();

        $sink = $this->redirectMessages();
        self::assertEquals(0, $sink->count());
        self::assertCount(0, $sink->get_messages());

        self::assertEquals(
            mock_built_in::get_default_body()->out(),
            $system_built_in->get_body()
        );

        $builder = notification_preference_builder::from_exist_model($system_built_in);
        $builder->set_body('This is overridden body');
        $builder->save();

        $system_built_in->refresh();
        self::assertNotEquals(mock_built_in::get_default_body()->out(), $system_built_in->get_body());
        self::assertEquals('This is overridden body', $system_built_in->get_body());

        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));
        $context_course = context_course::instance($course->id);

        // Reset the subject.
        $builder->set_body(null);
        $builder->save();
        $system_built_in->refresh();

        $this->queue_notifications(new mock_event($context_course->id));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        self::assertNotEquals('This is overridden body', $system_built_in->get_body());
        self::assertEquals(mock_built_in::get_default_body()->out(), $system_built_in->get_body());

        $sent_messages = $sink->get_messages();
        self::assertCount(1, $sent_messages);

        $sent_message = reset($sent_messages);
        self::assertIsObject($sent_message);

        self::assertObjectHasAttribute('fullmessage', $sent_message);
        self::assertObjectHasAttribute('subject', $sent_message);
        self::assertObjectHasAttribute('fullmessageformat', $sent_message);

        self::assertEquals($system_built_in->get_subject(), $sent_message->subject);
        self::assertEquals($system_built_in->get_body(), $sent_message->fullmessage);
        self::assertNotEquals('This is overridden body', $sent_message->fullmessage);
        self::assertEquals(mock_built_in::get_default_body()->out(), $sent_message->fullmessage);
        self::assertEquals(FORMAT_PLAIN, $sent_message->fullmessageformat);

        self::assertObjectHasAttribute('useridto', $sent_message);
        self::assertEquals($user_one->id, $sent_message->useridto);

        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));
    }

    /**
     * Override built-in notification in lower context.
     *
     * @return void
     */
    public function test_override_built_in_notification_in_lower_context(): void {
        global $DB;
        $generator = self::getDataGenerator();

        $user_one = $generator->create_user();
        $course = $generator->create_course();
        $context_course = context_course::instance($course->id);

        /** @var generator $notification_generator */
        $notification_generator = $generator->get_plugin_generator('totara_notification');
        $system_built_in = $notification_generator->add_mock_built_in_notification_for_component();

        $sink = $this->redirectMessages();
        self::assertEquals(0, $sink->count());
        self::assertCount(0, $sink->get_messages());

        // Create overridden at the course context.
        $course_overridden = $notification_generator->create_overridden_notification_preference(
            $system_built_in,
            extended_context::make_with_context($context_course),
            [
                'subject' => 'Course subject',
                'recipient' => totara_notification_mock_recipient::class,
            ]
        );

        $notification_generator->add_mock_recipient_ids_to_resolver([$user_one->id]);
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $this->queue_notifications(new mock_event($context_course->id));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $sent_messages = $sink->get_messages();
        self::assertCount(1, $sent_messages);

        $sent_message = reset($sent_messages);
        self::assertIsObject($sent_message);
        self::assertObjectHasAttribute('fullmessage', $sent_message);
        self::assertObjectHasAttribute('subject', $sent_message);
        self::assertObjectHasAttribute('fullmessageformat', $sent_message);

        self::assertNotEquals($system_built_in->get_subject(), $sent_message->subject);
        self::assertEquals($course_overridden->get_subject(), $sent_message->subject);

        self::assertEquals($system_built_in->get_body(), $sent_message->fullmessage);
        self::assertEquals($course_overridden->get_body(), $sent_message->fullmessage);
        self::assertEquals(FORMAT_PLAIN, $sent_message->fullmessageformat);

        self::assertObjectHasAttribute('useridto', $sent_message);
        self::assertEquals($user_one->id, $sent_message->useridto);

        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));
    }

    /**
     * @return void
     */
    public function test_override_custom_notification_in_lower_context(): void {
        global $DB;
        $generator = self::getDataGenerator();

        $user_one = $generator->create_user();
        $course = $generator->create_course();

        /** @var generator $notification_generator */
        $notification_generator = $generator->get_plugin_generator('totara_notification');
        $notification_generator->add_mock_recipient_ids_to_resolver([$user_one->id]);
        $system_custom = $notification_generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_with_context(context_system::instance()),
            [
                'subject' => 'System custom subject',
                'body' => 'System custom body',
                'subject_format' => FORMAT_PLAIN,
                'body_format' => FORMAT_PLAIN,
                'recipient' => totara_notification_mock_recipient::class,
            ]
        );

        $sink = $this->redirectMessages();
        self::assertEquals(0, $sink->count());
        self::assertCount(0, $sink->get_messages());

        $context_course = context_course::instance($course->id);
        $course_custom_overridden = $notification_generator->create_overridden_notification_preference(
            $system_custom,
            extended_context::make_with_context($context_course),
            [
                'body' => 'Course custom body',
                'recipient' => totara_notification_mock_recipient::class,
            ]
        );

        self::assertNotEquals($system_custom->get_body(), $course_custom_overridden->get_body());
        self::assertEquals('Course custom body', $course_custom_overridden->get_body());
        self::assertEquals($system_custom->get_subject(), $course_custom_overridden->get_subject());
        self::assertEquals('System custom subject', $course_custom_overridden->get_subject());

        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $this->queue_notifications(new mock_event($context_course->id));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $sent_messages = $sink->get_messages();
        self::assertCount(1, $sent_messages);

        $sent_message = reset($sent_messages);
        self::assertIsObject($sent_message);
        self::assertObjectHasAttribute('fullmessage', $sent_message);
        self::assertObjectHasAttribute('subject', $sent_message);
        self::assertObjectHasAttribute('fullmessageformat', $sent_message);

        self::assertEquals($system_custom->get_subject(), $sent_message->subject);
        self::assertEquals($course_custom_overridden->get_subject(), $sent_message->subject);
        self::assertEquals('System custom subject', $sent_message->subject);

        self::assertNotEquals('System custom body', $sent_message->fullmessage);
        self::assertNotEquals($system_custom->get_body(), $sent_message->fullmessage);
        self::assertEquals($course_custom_overridden->get_body(), $sent_message->fullmessage);

        self::assertEquals($system_custom->get_body_format(), $sent_message->fullmessageformat);
        self::assertEquals($course_custom_overridden->get_body_format(), $sent_message->fullmessageformat);

        self::assertObjectHasAttribute('useridto', $sent_message);
        self::assertEquals($user_one->id, $sent_message->useridto);

        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));
    }

    /**
     * Overriding an override
     * @return void
     */
    public function test_overriding_an_overridden_for_built_in(): void {
        global $DB;
        $generator = self::getDataGenerator();

        $user_one = $generator->create_user();
        $course = $generator->create_course();

        $context_category = context_coursecat::instance($course->category);
        $context_course = context_course::instance($course->id);

        /** @var generator $notification_generator */
        $notification_generator = $generator->get_plugin_generator('totara_notification');
        $notification_generator->add_mock_recipient_ids_to_resolver([$user_one->id]);

        $sink = $this->redirectMessages();
        self::assertEquals(0, $sink->count());
        self::assertCount(0, $sink->get_messages());

        $system_built_in = $notification_generator->add_mock_built_in_notification_for_component();

        $category_overridden = $notification_generator->create_overridden_notification_preference(
            $system_built_in,
            extended_context::make_with_context($context_category),
            [
                'subject' => 'Category subject',
                'body' => 'Category body',
                'recipient' => totara_notification_mock_recipient::class,
            ]
        );

        self::assertNotEquals($system_built_in->get_body(), $category_overridden->get_body());
        self::assertNotEquals($system_built_in->get_subject(), $category_overridden->get_subject());
        self::assertEquals($system_built_in->get_body_format(), $category_overridden->get_body_format());
        self::assertEquals($system_built_in->get_title(), $category_overridden->get_title());

        // Create overridden at the course context.
        $course_overridden = $notification_generator->create_overridden_notification_preference(
            $system_built_in,
            extended_context::make_with_context($context_course),
            [
                'subject' => 'Course subject',
                'recipient' => totara_notification_mock_recipient::class,
            ]
        );

        self::assertNotEquals($category_overridden->get_subject(), $course_overridden->get_subject());
        self::assertEquals($category_overridden->get_body(), $course_overridden->get_body());
        self::assertEquals($category_overridden->get_body_format(), $course_overridden->get_body_format());
        self::assertEquals($category_overridden->get_title(), $course_overridden->get_title());

        self::assertNotEquals($system_built_in->get_body(), $course_overridden->get_body());
        self::assertNotEquals($system_built_in->get_subject(), $course_overridden->get_subject());
        self::assertEquals($system_built_in->get_body_format(), $course_overridden->get_body_format());
        self::assertEquals($system_built_in->get_title(), $course_overridden->get_title());

        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $this->queue_notifications(new mock_event($context_course->id));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $sent_messages = $sink->get_messages();
        self::assertCount(1, $sent_messages);

        $sent_message = reset($sent_messages);
        self::assertIsObject($sent_message);
        self::assertObjectHasAttribute('fullmessage', $sent_message);
        self::assertObjectHasAttribute('subject', $sent_message);
        self::assertObjectHasAttribute('fullmessageformat', $sent_message);

        self::assertNotEquals($system_built_in->get_subject(), $sent_message->subject);
        self::assertNotEquals($category_overridden->get_subject(), $sent_message->subject);
        self::assertEquals($course_overridden->get_subject(), $sent_message->subject);
        self::assertEquals('Course subject', $sent_message->subject);

        self::assertNotEquals($system_built_in->get_body(), $sent_message->fullmessage);
        self::assertEquals($category_overridden->get_body(), $sent_message->fullmessage);
        self::assertEquals($course_overridden->get_body(), $sent_message->fullmessage);
        self::assertEquals('Category body', $sent_message->fullmessage);

        self::assertEquals(FORMAT_PLAIN, $sent_message->fullmessageformat);

        self::assertObjectHasAttribute('useridto', $sent_message);
        self::assertEquals($user_one->id, $sent_message->useridto);

        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));
    }

    /**
     * @return void
     */
    public function test_custom_notification_in_lower_context_does_not_mixed_with_a_non_descendent_context(): void {
        global $DB;
        $generator = self::getDataGenerator();

        $user_one = $generator->create_user();
        $course_one = $generator->create_course();
        $course_two = $generator->create_course();

        $context_course_one = context_course::instance($course_one->id);
        $context_course_two = context_course::instance($course_two->id);

        /** @var generator $notification_generator */
        $notification_generator = $generator->get_plugin_generator('totara_notification');
        $notification_generator->add_mock_recipient_ids_to_resolver([$user_one->id]);

        $sink = $this->redirectMessages();
        self::assertEquals(0, $sink->count());
        self::assertCount(0, $sink->get_messages());

        $course_one_custom = $notification_generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_with_context($context_course_one),
            [
                'subject' => 'Course one subject',
                'body' => 'Course one body',
                'title' => 'Course one title',
                'body_format' => FORMAT_MOODLE,
                'subject_format' => FORMAT_PLAIN,
                'recipient' => totara_notification_mock_recipient::class,
            ]
        );

        $course_two_custom = $notification_generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_with_context($context_course_two),
            [
                'subject' => 'Course two subject',
                'body' => 'Course two body',
                'title' => 'Course two title',
                'body_format' => FORMAT_HTML,
                'recipient' => totara_notification_mock_recipient::class,
            ]
        );

        self::assertNotEquals($course_one_custom->get_subject(), $course_two_custom->get_subject());
        self::assertNotEquals($course_one_custom->get_body(), $course_two_custom->get_body());

        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));
        $this->queue_notifications(new mock_event($context_course_one->id));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $sent_messages = $sink->get_messages();
        self::assertCount(1, $sent_messages);

        $sent_message = reset($sent_messages);
        self::assertIsObject($sent_message);
        self::assertObjectHasAttribute('fullmessage', $sent_message);
        self::assertObjectHasAttribute('subject', $sent_message);
        self::assertObjectHasAttribute('fullmessageformat', $sent_message);

        self::assertNotEquals($course_two_custom->get_subject(), $sent_message->subject);
        self::assertNotEquals('Course two subject', $sent_message->subject);
        self::assertEquals($course_one_custom->get_subject(), $sent_message->subject);
        self::assertEquals('Course one subject', $sent_message->subject);

        self::assertNotEquals($course_two_custom->get_body(), $sent_message->fullmessage);
        self::assertNotEquals('Course two body', $sent_message->fullmessage);
        self::assertEquals($course_one_custom->get_body(), $sent_message->fullmessage);
        self::assertEquals('Course one body', $sent_message->fullmessage);

        self::assertNotEquals($course_two_custom->get_body_format(), $sent_message->fullmessageformat);
        self::assertNotEquals(FORMAT_HTML, $sent_message->fullmessageformat);
        self::assertEquals(FORMAT_PLAIN, $sent_message->fullmessageformat);

        self::assertObjectHasAttribute('useridto', $sent_message);
        self::assertEquals($user_one->id, $sent_message->useridto);

        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));
    }

    /**
     * Custom notification in lower context does not affect a non-descendent context
     *
     * @return void
     */
    public function test_custom_notification_in_lower_context_does_not_affect_a_non_descendant_context(): void {
        global $DB;
        $generator = self::getDataGenerator();

        $user_one = $generator->create_user();
        $course_one = $generator->create_course();
        $course_two = $generator->create_course();

        $context_course_one = context_course::instance($course_one->id);
        $context_course_two = context_course::instance($course_two->id);

        /** @var generator $notification_generator */
        $notification_generator = $generator->get_plugin_generator('totara_notification');
        $notification_generator->add_mock_recipient_ids_to_resolver([$user_one->id]);

        $sink = $this->redirectMessages();
        self::assertEquals(0, $sink->count());
        self::assertCount(0, $sink->get_messages());

        $course_one_custom = $notification_generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_with_context($context_course_one),
            [
                'subject' => 'Course one subject',
                'body' => 'Course one body',
                'title' => 'Course one title',
                'body_format' => FORMAT_MOODLE,
                'recipient' => totara_notification_mock_recipient::class,
            ]
        );

        self::assertTrue($DB->record_exists(notification_preference::TABLE, ['id' => $course_one_custom->get_id()]));

        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));
        $this->queue_notifications(new mock_event($context_course_two->id));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        self::assertEquals(0, $sink->count());
        self::assertCount(0, $sink->get_messages());
    }

    /**
     * Override in one context does not affect a non-descendent context
     * @return void
     */
    public function test_override_in_one_context_does_not_affect_a_non_descendent_context(): void {
        global $DB;
        $generator = self::getDataGenerator();

        $user_one = $generator->create_user();
        $course_one = $generator->create_course();
        $course_two = $generator->create_course();

        $context_course_one = context_course::instance($course_one->id);
        $context_course_two = context_course::instance($course_two->id);

        /** @var generator $notification_generator */
        $notification_generator = $generator->get_plugin_generator('totara_notification');
        $notification_generator->add_mock_recipient_ids_to_resolver([$user_one->id]);

        $sink = $this->redirectMessages();
        self::assertEquals(0, $sink->count());
        self::assertCount(0, $sink->get_messages());

        $system_built_in = $notification_generator->add_mock_built_in_notification_for_component();
        $course_one_overridden = $notification_generator->create_overridden_notification_preference(
            $system_built_in,
            extended_context::make_with_context($context_course_one),
            [
                'body' => 'Course one body',
                'subject' => 'Course one subject',
                'recipient' => totara_notification_mock_recipient::class,
            ]
        );

        self::assertNotEquals($system_built_in->get_subject(), $course_one_overridden->get_subject());
        self::assertNotEquals($system_built_in->get_body(), $course_one_overridden->get_body());

        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));
        $this->queue_notifications(new mock_event($context_course_two->id));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $sent_messages = $sink->get_messages();
        self::assertCount(1, $sent_messages);

        $sent_message = reset($sent_messages);
        self::assertIsObject($sent_message);
        self::assertObjectHasAttribute('fullmessage', $sent_message);
        self::assertObjectHasAttribute('subject', $sent_message);
        self::assertObjectHasAttribute('fullmessageformat', $sent_message);

        self::assertNotEquals($course_one_overridden->get_subject(), $sent_message->subject);
        self::assertNotEquals('Course one subject', $sent_message->subject);
        self::assertEquals($system_built_in->get_subject(), $sent_message->subject);
        self::assertEquals(mock_built_in::get_default_subject()->out(), $sent_message->subject);

        self::assertNotEquals($course_one_overridden->get_body(), $sent_message->fullmessage);
        self::assertNotEquals('Course one body', $sent_message->fullmessage);
        self::assertEquals($system_built_in->get_body(), $sent_message->fullmessage);
        self::assertEquals(mock_built_in::get_default_body(), $sent_message->fullmessage);

        self::assertObjectHasAttribute('useridto', $sent_message);
        self::assertEquals($user_one->id, $sent_message->useridto);

        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));
    }
}
