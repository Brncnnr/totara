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
use totara_notification\loader\notification_preference_loader;
use totara_notification\testing\generator;
use totara_notification_mock_notifiable_event_resolver as mock_resolver;
use totara_webapi\phpunit\webapi_phpunit_helper;

class totara_notification_webapi_override_notification_preference_v2_testcase extends testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    protected function setUp(): void {
        $notification_generator = generator::instance();

        $notification_generator->add_mock_built_in_notification_for_component();
        $notification_generator->include_mock_recipient();
        $notification_generator->include_mock_notifiable_event_resolver();
    }

    /**
     * @return void
     */
    public function test_override_a_system_built_in_at_lower_context_without_providing_ancestor_id(): void {
        $this->setAdminUser();

        $generator = self::getDataGenerator();
        $course = $generator->create_course();

        $result = $this->execute_graphql_operation(
            'totara_notification_override_notification_preference',
            [
                'context_id' => context_course::instance($course->id)->id,
                'resolver_class_name' => mock_resolver::class,
            ]
        );

        self::assertNotEmpty($result->errors);
        self::assertIsArray($result->errors);
        self::assertCount(1, $result->errors);

        $error = reset($result->errors);
        self::assertEquals(
            'Variable "$ancestor_id" of required type "param_integer!" was not provided.',
            $error->getMessage()
        );

        self::assertEmpty($result->data);
    }

    /**
     * @return void
     */
    public function test_override_a_system_built_in_at_lower_context(): void {
        $this->setAdminUser();

        $generator = self::getDataGenerator();
        $course = $generator->create_course();
        $context_course = context_course::instance($course->id);
        mock_resolver::set_support_contexts(
            extended_context::make_with_context($context_course)
        );

        $system_built_in = notification_preference_loader::get_built_in(
            totara_notification_mock_built_in_notification::class,
        );

        $result = $this->execute_graphql_operation(
            'totara_notification_override_notification_preference_v2',
            [
                'context_id' => $context_course->id,
                'resolver_class_name' => mock_resolver::class,
                'ancestor_id' => $system_built_in->get_id(),
                'subject' => 'This is subject',
                'body_format' => FORMAT_HTML,
                'body' => 'This is body',
                'recipients' => [totara_notification_mock_recipient::class],
            ]
        );

        self::assertEmpty($result->errors);
        self::assertNotEmpty($result->data);
        self::assertIsArray($result->data);
        self::assertArrayHasKey('notification_preference', $result->data);

        $notification_preference = $result->data['notification_preference'];
        self::assertArrayHasKey('id', $notification_preference);
        self::assertGreaterThan(0, $notification_preference['id']);

        self::assertArrayHasKey('title', $notification_preference);
        self::assertEquals(
            totara_notification_mock_built_in_notification::get_title(),
            $notification_preference['title']
        );

        self::assertArrayHasKey('subject', $notification_preference);
        self::assertEquals('This is subject', $notification_preference['subject']);

        self::assertArrayHasKey('body', $notification_preference);
        self::assertEquals('This is body', $notification_preference['body']);

        self::assertArrayHasKey('body_format', $notification_preference);
        self::assertEquals(FORMAT_HTML, $notification_preference['body_format']);

        self::assertArrayHasKey('overridden_body', $notification_preference);
        self::assertArrayHasKey('overridden_subject', $notification_preference);

        self::assertTrue($notification_preference['overridden_body']);
        self::assertTrue($notification_preference['overridden_subject']);

        self::assertArrayHasKey('extended_context', $notification_preference);

        self::assertArrayHasKey('resolver_name', $notification_preference);
        self::assertEquals(
            totara_notification_mock_notifiable_event_resolver::get_notification_title(),
            $notification_preference['resolver_name']
        );

        self::assertArrayHasKey('resolver_class_name', $notification_preference);
        self::assertEquals(
            totara_notification_mock_built_in_notification::get_resolver_class_name(),
            $notification_preference['resolver_class_name']
        );
    }
}