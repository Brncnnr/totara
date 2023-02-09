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

use core\orm\query\builder;
use core_phpunit\testcase;
use totara_core\extended_context;
use totara_notification\entity\notification_preference as entity;
use totara_notification\schedule\schedule_after_event;
use totara_notification\schedule\schedule_before_event;
use totara_notification\testing\generator;
use totara_notification_mock_scheduled_aware_event_resolver as mock_resolver;
use totara_webapi\phpunit\webapi_phpunit_helper;

class totara_notification_webapi_create_custom_notification_preference_testcase extends testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_create_custom_notification_at_system_context(): void {
        global $DB;

        $context = context_system::instance();
        $this->setAdminUser();

        $single_recipient_result = $this->execute_graphql_operation(
            'totara_notification_create_custom_notification_preference',
            [
                'context_id' => $context->id,
                'resolver_class_name' => mock_resolver::class,
                'body' => 'This is body',
                'body_format' => FORMAT_MOODLE,
                'subject' => 'This is subject',
                'title' => 'This is title',
                'subject_format' => FORMAT_PLAIN,
                'schedule_type' => schedule_before_event::identifier(),
                'schedule_offset' => 5,
                'recipient' => totara_notification_mock_recipient::class,
                'enabled' => true,
                'forced_delivery_channels' => [],
            ]
        );
        $debugging_messages = $this->getDebuggingMessages();
        $debug_message = reset($debugging_messages);
        $this->assertSame('Do not use create_notification_preference::resolve any more, use create_notification_preference_v2::resolve instead', $debug_message->message);
        $this->resetDebugging();

        $multi_recipient_result = $this->execute_graphql_operation(
            'totara_notification_create_custom_notification_preference_v2',
            [
                'context_id' => $context->id,
                'resolver_class_name' => mock_resolver::class,
                'body' => 'This is body',
                'body_format' => FORMAT_MOODLE,
                'subject' => 'This is subject',
                'title' => 'This is title',
                'subject_format' => FORMAT_PLAIN,
                'schedule_type' => schedule_before_event::identifier(),
                'schedule_offset' => 5,
                'recipients' => [totara_notification_mock_recipient::class],
                'enabled' => true,
                'forced_delivery_channels' => [],
            ]
        );

        $results = [$single_recipient_result, $multi_recipient_result];
        foreach ($results as $result) {
            self::assertEmpty($result->errors);
            self::assertNotEmpty($result->data);
            self::assertIsArray($result->data);
            self::assertArrayHasKey('notification_preference', $result->data);

            $notification_preference = $result->data['notification_preference'];
            self::assertIsArray($notification_preference);

            self::assertArrayHasKey('id', $notification_preference);
            self::assertTrue($DB->record_exists(entity::TABLE, ['id' => $notification_preference['id']]));

            self::assertArrayHasKey('title', $notification_preference);
            self::assertEquals('This is title', $notification_preference['title']);

            self::assertArrayHasKey('additional_criteria', $notification_preference);
            self::assertNull($notification_preference['additional_criteria']); // The resolver does not support additional criteria.

            self::assertArrayHasKey('subject', $notification_preference);
            self::assertEquals('This is subject', $notification_preference['subject']);

            self::assertArrayHasKey('body', $notification_preference);
            self::assertEquals('This is body', $notification_preference['body']);

            self::assertArrayHasKey('body_format', $notification_preference);
            self::assertEquals(FORMAT_MOODLE, $notification_preference['body_format']);

            self::assertArrayHasKey('extended_context', $notification_preference);
            $extended_context = $notification_preference['extended_context'];
            self::assertIsArray($extended_context);

            self::assertArrayHasKey('component', $extended_context);
            self::assertEquals('', $extended_context['component']);

            self::assertArrayHasKey('area', $extended_context);
            self::assertEquals('', $extended_context['area']);

            self::assertArrayHasKey('item_id', $extended_context);
            self::assertEquals(0, $extended_context['item_id']);

            self::assertArrayHasKey('context_id', $extended_context);
            self::assertEquals($context->id, $extended_context['context_id']);

            self::assertArrayHasKey('is_custom', $notification_preference);
            self::assertTrue($notification_preference['is_custom']);

            self::assertArrayHasKey('schedule_type', $notification_preference);
            self::assertEquals(schedule_before_event::identifier(), $notification_preference['schedule_type']);

            self::assertArrayHasKey('schedule_offset', $notification_preference);
            self::assertEquals(5, $notification_preference['schedule_offset']);

            self::assertArrayHasKey('resolver_class_name', $notification_preference);
            self::assertEquals(mock_resolver::class, $notification_preference['resolver_class_name']);

            self::assertArrayHasKey('resolver_component', $notification_preference);
            self::assertEquals('totara_notification', $notification_preference['resolver_component']);
        }
    }

    /**
     * @return void
     */
    public function test_create_custom_notification_at_lower_context(): void {
        global $DB;

        $generator = self::getDataGenerator();
        $course = $generator->create_course();

        $context_course = context_course::instance($course->id);
        $this->setAdminUser();
        mock_resolver::set_support_contexts(
            extended_context::make_with_context($context_course)
        );

        $single_recipient_result = $this->execute_graphql_operation(
            'totara_notification_create_custom_notification_preference',
            [
                'context_id' => $context_course->id,
                'resolver_class_name' => mock_resolver::class,
                'body' => 'First body',
                'body_format' => FORMAT_HTML,
                'subject' => 'First subject',
                'title' => 'First title',
                'subject_format' => FORMAT_PLAIN,
                'schedule_type' => schedule_after_event::identifier(),
                'schedule_offset' => 10,
                'recipient' => totara_notification_mock_recipient::class,
                'enabled' => true,
                'forced_delivery_channels' => [],
            ]
        );

        $debugging_messages = $this->getDebuggingMessages();
        $debug_message = reset($debugging_messages);
        $this->assertSame('Do not use create_notification_preference::resolve any more, use create_notification_preference_v2::resolve instead', $debug_message->message);
        $this->resetDebugging();

        $multi_recipient_result = $this->execute_graphql_operation(
            'totara_notification_create_custom_notification_preference_v2',
            [
                'context_id' => $context_course->id,
                'resolver_class_name' => mock_resolver::class,
                'body' => 'First body',
                'body_format' => FORMAT_HTML,
                'subject' => 'First subject',
                'title' => 'First title',
                'subject_format' => FORMAT_PLAIN,
                'schedule_type' => schedule_after_event::identifier(),
                'schedule_offset' => 10,
                'recipients' => [totara_notification_mock_recipient::class],
                'enabled' => true,
                'forced_delivery_channels' => [],
            ]
        );

        $results = [$single_recipient_result, $multi_recipient_result];

        foreach ($results as $result) {

            self::assertEmpty($result->errors);
            self::assertNotEmpty($result->data);
            self::assertIsArray($result->data);
            self::assertArrayHasKey('notification_preference', $result->data);

            $notification_preference = $result->data['notification_preference'];
            self::assertIsArray($notification_preference);

            self::assertArrayHasKey('id', $notification_preference);
            self::assertTrue($DB->record_exists(entity::TABLE, ['id' => $notification_preference['id']]));

            self::assertArrayHasKey('title', $notification_preference);
            self::assertEquals('First title', $notification_preference['title']);

            self::assertArrayHasKey('additional_criteria', $notification_preference);
            self::assertNull($notification_preference['additional_criteria']); // The resolver does not support additional criteria.

            self::assertArrayHasKey('subject', $notification_preference);
            self::assertEquals('First subject', $notification_preference['subject']);

            self::assertArrayHasKey('body', $notification_preference);
            self::assertEquals('First body', $notification_preference['body']);

            self::assertArrayHasKey('body_format', $notification_preference);
            self::assertEquals(FORMAT_HTML, $notification_preference['body_format']);

            self::assertArrayHasKey('extended_context', $notification_preference);
            $extended_context = $notification_preference['extended_context'];
            self::assertIsArray($extended_context);

            self::assertArrayHasKey('component', $extended_context);
            self::assertEquals('', $extended_context['component']);

            self::assertArrayHasKey('area', $extended_context);
            self::assertEquals('', $extended_context['area']);

            self::assertArrayHasKey('item_id', $extended_context);
            self::assertEquals(0, $extended_context['item_id']);

            self::assertArrayHasKey('context_id', $extended_context);
            self::assertEquals($context_course->id, $extended_context['context_id']);

            self::assertArrayHasKey('is_custom', $notification_preference);
            self::assertTrue($notification_preference['is_custom']);

            self::assertArrayHasKey('schedule_type', $notification_preference);
            self::assertEquals(schedule_after_event::identifier(), $notification_preference['schedule_type']);

            self::assertArrayHasKey('schedule_offset', $notification_preference);
            self::assertEquals(10, $notification_preference['schedule_offset']);

            self::assertArrayHasKey('resolver_class_name', $notification_preference);
            self::assertEquals(mock_resolver::class, $notification_preference['resolver_class_name']);

            self::assertArrayHasKey('resolver_component', $notification_preference);
            self::assertEquals('totara_notification', $notification_preference['resolver_component']);
        }
    }

    /**
     * @return void
     */
    public function test_create_custom_notification_at_invalid_context(): void {
        $this->setAdminUser();
        $single_recipient_result = $this->execute_graphql_operation(
            'totara_notification_create_custom_notification_preference',
            [
                'context_id' => 4242,
                'resolver_class_name' => mock_resolver::class,
                'title' => 'custom title',
                'body' => 'custom body',
                'subject' => 'custom subject',
                'body_format' => FORMAT_MOODLE,
                'subject_format' => FORMAT_PLAIN,
                'schedule_type' => schedule_before_event::identifier(),
                'schedule_offset' => 6,
                'recipient' => totara_notification_mock_recipient::class,
                'enabled' => true,
                'forced_delivery_channels' => []
            ]
        );

        $this->assertDebuggingCalled();

        $multi_recipient_result = $this->execute_graphql_operation(
            'totara_notification_create_custom_notification_preference_v2',
            [
                'context_id' => 4242,
                'resolver_class_name' => mock_resolver::class,
                'title' => 'custom title',
                'body' => 'custom body',
                'subject' => 'custom subject',
                'body_format' => FORMAT_MOODLE,
                'subject_format' => FORMAT_PLAIN,
                'schedule_type' => schedule_before_event::identifier(),
                'schedule_offset' => 6,
                'recipients' => [totara_notification_mock_recipient::class],
                'enabled' => true,
                'forced_delivery_channels' => []
            ]
        );

        $results = [$single_recipient_result, $multi_recipient_result];

        foreach ($results as $result) {

            self::assertEmpty($result->data);
            self::assertNotEmpty($result->errors);
            self::assertIsArray($result->errors);
            self::assertCount(1, $result->errors);

            $first_error = reset($result->errors);
            self::assertIsObject($first_error);
            // totara_notification_create_custom_notification_preference_v2 checks the context is valid.
            if (!str_contains($first_error->getMessage(), 'Invalid context')) {
                self::assertStringContainsString("Can not find data record in database.", $first_error->getMessage());
            }
        }
    }

    public function test_create_custom_notification_as_normal_user(): void {
        global $DB;
        $generator = self::getDataGenerator();
        $user_one = $generator->create_user();

        $context_system = context_system::instance();
        $this->setUser($user_one);

        $role_id = builder::table('role')->where('shortname', 'user')->value('id');
        assign_capability('totara/notification:managenotifications', CAP_ALLOW, $role_id, SYSCONTEXTID, true);

        $single_recipient_result = $this->execute_graphql_operation(
            'totara_notification_create_custom_notification_preference',
            [
                'resolver_class_name' => mock_resolver::class,
                'context_id' => $context_system->id,
                'title' => 'This is custom',
                'body' => 'This is body',
                'subject' => 'This is subject',
                'body_format' => FORMAT_MOODLE,
                'subject_format' => FORMAT_PLAIN,
                'schedule_type' => schedule_before_event::identifier(),
                'schedule_offset' => 6,
                'recipient' => totara_notification_mock_recipient::class,
                'enabled' => true,
                'forced_delivery_channels' => [],
            ]
        );

        $debugging_messages = $this->getDebuggingMessages();
        $debug_message = reset($debugging_messages);
        $this->assertSame('Do not use create_notification_preference::resolve any more, use create_notification_preference_v2::resolve instead', $debug_message->message);
        $this->resetDebugging();

        $multi_recipient_result = $this->execute_graphql_operation(
            'totara_notification_create_custom_notification_preference_v2',
            [
                'resolver_class_name' => mock_resolver::class,
                'context_id' => $context_system->id,
                'title' => 'This is custom',
                'body' => 'This is body',
                'subject' => 'This is subject',
                'body_format' => FORMAT_MOODLE,
                'subject_format' => FORMAT_PLAIN,
                'schedule_type' => schedule_before_event::identifier(),
                'schedule_offset' => 6,
                'recipients' => [totara_notification_mock_recipient::class],
                'enabled' => true,
                'forced_delivery_channels' => [],
            ]
        );

        $results = [$single_recipient_result, $multi_recipient_result];

        foreach ($results as $result) {

            self::assertEmpty($result->errors);
            self::assertNotEmpty($result->data);
            self::assertIsArray($result->data);
            self::assertArrayHasKey('notification_preference', $result->data);

            $notification_preference = $result->data['notification_preference'];
            self::assertIsArray($notification_preference);

            self::assertArrayHasKey('id', $notification_preference);
            self::assertTrue($DB->record_exists(entity::TABLE, ['id' => $notification_preference['id']]));

            self::assertArrayHasKey('title', $notification_preference);
            self::assertEquals('This is custom', $notification_preference['title']);

            self::assertArrayHasKey('additional_criteria', $notification_preference);
            self::assertNull($notification_preference['additional_criteria']); // The resolver does not support additional criteria.

            self::assertArrayHasKey('subject', $notification_preference);
            self::assertEquals('This is subject', $notification_preference['subject']);

            self::assertArrayHasKey('body', $notification_preference);
            self::assertEquals('This is body', $notification_preference['body']);

            self::assertArrayHasKey('body_format', $notification_preference);
            self::assertEquals(FORMAT_MOODLE, $notification_preference['body_format']);

            self::assertArrayHasKey('extended_context', $notification_preference);
            $extended_context = $notification_preference['extended_context'];
            self::assertIsArray($extended_context);

            self::assertArrayHasKey('component', $extended_context);
            self::assertEquals('', $extended_context['component']);

            self::assertArrayHasKey('area', $extended_context);
            self::assertEquals('', $extended_context['area']);

            self::assertArrayHasKey('item_id', $extended_context);
            self::assertEquals(0, $extended_context['item_id']);

            self::assertArrayHasKey('context_id', $extended_context);
            self::assertEquals($context_system->id, $extended_context['context_id']);

            self::assertArrayHasKey('is_custom', $notification_preference);
            self::assertTrue($notification_preference['is_custom']);

            self::assertArrayHasKey('schedule_type', $notification_preference);
            self::assertEquals(schedule_before_event::identifier(), $notification_preference['schedule_type']);

            self::assertArrayHasKey('schedule_offset', $notification_preference);
            self::assertEquals(6, $notification_preference['schedule_offset']);

            self::assertArrayHasKey('resolver_class_name', $notification_preference);
            self::assertEquals(mock_resolver::class, $notification_preference['resolver_class_name']);

            self::assertArrayHasKey('resolver_component', $notification_preference);
            self::assertEquals('totara_notification', $notification_preference['resolver_component']);
        }
    }

    /**
     * @return void
     */
    public function test_create_custom_notification_with_html_content_value_of_field_title(): void {
        $this->setAdminUser();
        $context_system = context_system::instance();

        $single_recipient_result = $this->execute_graphql_operation(
            'totara_notification_create_custom_notification_preference',
            [
                'resolver_class_name' => mock_resolver::class,
                'context_id' => $context_system->id,
                'title' => /** @lang text */ '<input type="text" value="cc"/>',
                'body' => 'cccd',
                'subject' => 'pokopkopfw',
                'body_format' => FORMAT_MOODLE,
                'subject_format' => FORMAT_PLAIN,
                'schedule_type' => schedule_before_event::identifier(),
                'schedule_offset' => 2,
                'recipient' => totara_notification_mock_recipient::class,
                'enabled' => true,
                'forced_delivery_channels' => [],
            ]
        );

        $debugging_messages = $this->getDebuggingMessages();
        $debug_message = reset($debugging_messages);
        $this->assertSame('Do not use create_notification_preference::resolve any more, use create_notification_preference_v2::resolve instead', $debug_message->message);
        $this->resetDebugging();

        $multi_recipient_result = $this->execute_graphql_operation(
            'totara_notification_create_custom_notification_preference_v2',
            [
                'resolver_class_name' => mock_resolver::class,
                'context_id' => $context_system->id,
                'title' => /** @lang text */ '<input type="text" value="cc"/>',
                'body' => 'cccd',
                'subject' => 'pokopkopfw',
                'body_format' => FORMAT_MOODLE,
                'subject_format' => FORMAT_PLAIN,
                'schedule_type' => schedule_before_event::identifier(),
                'schedule_offset' => 2,
                'recipients' => [totara_notification_mock_recipient::class],
                'enabled' => true,
                'forced_delivery_channels' => [],
            ]
        );

        $results = [$single_recipient_result, $multi_recipient_result];

        foreach ($results as $result) {

            self::assertNotEmpty($result->data);
            self::assertEmpty($result->errors);
            $notification_preference = $result->data['notification_preference'];
            self::assertEquals('<input type="text" value="cc"/>', $notification_preference['title']);
        }
    }

    /**
     * @return void
     */
    public function test_create_custom_notification_with_xss_content_value_of_field_body(): void {
        $this->setAdminUser();
        $context_system = context_system::instance();

        $single_recipient_result = $this->execute_graphql_operation(
            'totara_notification_create_custom_notification_preference',
            [
                'resolver_class_name' => mock_resolver::class,
                'context_id' => $context_system->id,
                'title' => 'This is title',
                'body' => /** @lang text */ '<script type="javascript">alert(1)</script>',
                'subject' => 'This is subject',
                'body_format' => FORMAT_MOODLE,
                'subject_format' => FORMAT_PLAIN,
                'schedule_type' => schedule_before_event::identifier(),
                'schedule_offset' => 2,
                'recipient' => totara_notification_mock_recipient::class,
                'enabled' => true,
                'forced_delivery_channels' => [],
            ]
        );

        $debugging_messages = $this->getDebuggingMessages();
        $debug_message = reset($debugging_messages);
        $this->assertSame('Do not use create_notification_preference::resolve any more, use create_notification_preference_v2::resolve instead', $debug_message->message);
        $this->resetDebugging();

        $multi_recipient_result = $this->execute_graphql_operation(
            'totara_notification_create_custom_notification_preference_v2',
            [
                'resolver_class_name' => mock_resolver::class,
                'context_id' => $context_system->id,
                'title' => 'This is title',
                'body' => /** @lang text */ '<script type="javascript">alert(1)</script>',
                'subject' => 'This is subject',
                'body_format' => FORMAT_MOODLE,
                'subject_format' => FORMAT_PLAIN,
                'schedule_type' => schedule_before_event::identifier(),
                'schedule_offset' => 2,
                'recipients' => [totara_notification_mock_recipient::class],
                'enabled' => true,
                'forced_delivery_channels' => [],
            ]
        );

        $results = [$single_recipient_result, $multi_recipient_result];

        foreach ($results as $result) {
            self::assertEmpty($result->errors);
            self::assertNotEmpty($result->data);
            self::assertIsArray($result->data);
            self::assertArrayHasKey('notification_preference', $result->data);

            $notification_preference = $result->data['notification_preference'];
            self::assertArrayHasKey('title', $notification_preference);
            self::assertEquals('This is title', $notification_preference['title']);

            self::assertArrayHasKey('subject', $notification_preference);
            self::assertEquals('This is subject', $notification_preference['subject']);

            self::assertArrayHasKey('body', $notification_preference);
            self::assertEquals('<script type="javascript">alert(1)</script>', $notification_preference['body']);
        }
    }

    /**
     * @return void
     */
    public function test_create_custom_notification_with_xss_content_value_of_field_subject(): void {
        $this->setAdminUser();
        $context_system = context_system::instance();

        $single_recipient_result = $this->execute_graphql_operation(
            'totara_notification_create_custom_notification_preference',
            [
                'resolver_class_name' => mock_resolver::class,
                'context_id' => $context_system->id,
                'title' => 'This is title',
                'body' => 'This is body',
                'subject' => /** @lang text */ '<script type="javascript">alert(1)</script>',
                'body_format' => FORMAT_MOODLE,
                'subject_format' => FORMAT_PLAIN,
                'schedule_type' => schedule_before_event::identifier(),
                'schedule_offset' => 2,
                'recipient' => totara_notification_mock_recipient::class,
                'enabled' => true,
                'forced_delivery_channels' => [],
            ]
        );

        $debugging_messages = $this->getDebuggingMessages();
        $debug_message = reset($debugging_messages);
        $this->assertSame('Do not use create_notification_preference::resolve any more, use create_notification_preference_v2::resolve instead', $debug_message->message);
        $this->resetDebugging();

        $multi_recipient_result = $this->execute_graphql_operation(
            'totara_notification_create_custom_notification_preference_v2',
            [
                'resolver_class_name' => mock_resolver::class,
                'context_id' => $context_system->id,
                'title' => 'This is title',
                'body' => 'This is body',
                'subject' => /** @lang text */ '<script type="javascript">alert(1)</script>',
                'body_format' => FORMAT_MOODLE,
                'subject_format' => FORMAT_PLAIN,
                'schedule_type' => schedule_before_event::identifier(),
                'schedule_offset' => 2,
                'recipients' => [totara_notification_mock_recipient::class],
                'enabled' => true,
                'forced_delivery_channels' => [],
            ]
        );

        $results = [$single_recipient_result, $multi_recipient_result];

        foreach ($results as $result) {
            self::assertEmpty($result->errors);
            self::assertNotEmpty($result->data);
            self::assertIsArray($result->data);
            self::assertArrayHasKey('notification_preference', $result->data);

            $notification_preference = $result->data['notification_preference'];
            self::assertArrayHasKey('title', $notification_preference);
            self::assertEquals('This is title', $notification_preference['title']);

            self::assertArrayHasKey('subject', $notification_preference);
            self::assertEquals('<script type="javascript">alert(1)</script>', $notification_preference['subject']);

            self::assertArrayHasKey('body', $notification_preference);
            self::assertEquals('This is body', $notification_preference['body']);
        }
    }

    /**
     * @return void
     */
    public function test_create_custom_notification_with_extended_context(): void {
        global $DB;

        $generator = self::getDataGenerator();
        $course = $generator->create_course();

        $context_course = context_course::instance($course->id);
        $this->setAdminUser();
        mock_resolver::set_support_contexts(
            extended_context::make_with_context($context_course, 'totara_notification', 'test_area', 1)
        );

        $single_recipient_result = $this->execute_graphql_operation(
            'totara_notification_create_custom_notification_preference',
            [
                'context_id' => $context_course->id,
                'resolver_class_name' => mock_resolver::class,
                'body' => 'First body',
                'body_format' => FORMAT_HTML,
                'subject' => 'First subject',
                'title' => 'First title',
                'subject_format' => FORMAT_PLAIN,
                'schedule_type' => schedule_after_event::identifier(),
                'schedule_offset' => 10,
                'recipient' => totara_notification_mock_recipient::class,
                'extended_context_area' => 'test_area',
                'extended_context_component' => 'totara_notification',
                'extended_context_item_id' => 1,
                'enabled' => true,
                'forced_delivery_channels' => [],
            ]
        );

        $debugging_messages = $this->getDebuggingMessages();
        $debug_message = reset($debugging_messages);
        $this->assertSame('Do not use create_notification_preference::resolve any more, use create_notification_preference_v2::resolve instead', $debug_message->message);
        $this->resetDebugging();

        $multi_recipient_result = $this->execute_graphql_operation(
            'totara_notification_create_custom_notification_preference_v2',
            [
                'context_id' => $context_course->id,
                'resolver_class_name' => mock_resolver::class,
                'body' => 'First body',
                'body_format' => FORMAT_HTML,
                'subject' => 'First subject',
                'title' => 'First title',
                'subject_format' => FORMAT_PLAIN,
                'schedule_type' => schedule_after_event::identifier(),
                'schedule_offset' => 10,
                'recipients' => [totara_notification_mock_recipient::class],
                'extended_context_area' => 'test_area',
                'extended_context_component' => 'totara_notification',
                'extended_context_item_id' => 1,
                'enabled' => true,
                'forced_delivery_channels' => [],
            ]
        );

        $results = [$single_recipient_result, $multi_recipient_result];

        foreach ($results as $result) {

            self::assertEmpty($result->errors);
            self::assertNotEmpty($result->data);
            self::assertIsArray($result->data);
            self::assertArrayHasKey('notification_preference', $result->data);

            $notification_preference = $result->data['notification_preference'];
            self::assertIsArray($notification_preference);

            self::assertArrayHasKey('id', $notification_preference);
            self::assertTrue($DB->record_exists(entity::TABLE, ['id' => $notification_preference['id']]));

            self::assertArrayHasKey('title', $notification_preference);
            self::assertEquals('First title', $notification_preference['title']);

            self::assertArrayHasKey('additional_criteria', $notification_preference);
            self::assertNull($notification_preference['additional_criteria']); // The resolver does not support additional criteria.

            self::assertArrayHasKey('subject', $notification_preference);
            self::assertEquals('First subject', $notification_preference['subject']);

            self::assertArrayHasKey('body', $notification_preference);
            self::assertEquals('First body', $notification_preference['body']);

            self::assertArrayHasKey('body_format', $notification_preference);
            self::assertEquals(FORMAT_HTML, $notification_preference['body_format']);

            self::assertArrayHasKey('extended_context', $notification_preference);
            $extended_context = $notification_preference['extended_context'];
            self::assertIsArray($extended_context);

            self::assertArrayHasKey('component', $extended_context);
            self::assertEquals('totara_notification', $extended_context['component']);

            self::assertArrayHasKey('area', $extended_context);
            self::assertEquals('test_area', $extended_context['area']);

            self::assertArrayHasKey('item_id', $extended_context);
            self::assertEquals(1, $extended_context['item_id']);

            self::assertArrayHasKey('context_id', $extended_context);
            self::assertEquals($context_course->id, $extended_context['context_id']);

            self::assertArrayHasKey('is_custom', $notification_preference);
            self::assertTrue($notification_preference['is_custom']);

            self::assertArrayHasKey('schedule_type', $notification_preference);
            self::assertEquals(schedule_after_event::identifier(), $notification_preference['schedule_type']);

            self::assertArrayHasKey('schedule_offset', $notification_preference);
            self::assertEquals(10, $notification_preference['schedule_offset']);

            self::assertArrayHasKey('resolver_component', $notification_preference);
            self::assertEquals('totara_notification', $notification_preference['resolver_component']);

            self::assertArrayHasKey('extended_context', $notification_preference);
            self::assertEquals($context_course->id, $notification_preference['extended_context']['context_id']);
            self::assertEquals(1, $notification_preference['extended_context']['item_id']);
            self::assertEquals('totara_notification', $notification_preference['extended_context']['component']);
            self::assertEquals('test_area', $notification_preference['extended_context']['area']);
        }
    }

    /**
     * @return void
     */
    protected function setUp(): void {
        $generator = generator::instance();
        $generator->include_mock_scheduled_aware_notifiable_event_resolver();
        $generator->include_mock_recipient();
    }
}