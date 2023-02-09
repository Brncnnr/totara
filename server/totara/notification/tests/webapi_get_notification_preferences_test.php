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
use core\orm\query\builder;
use totara_notification\exception\notification_exception;
use totara_notification\factory\notifiable_event_resolver_factory;
use totara_notification\loader\notification_preference_loader;
use totara_notification\model\notification_preference as model;
use totara_notification\testing\generator;
use totara_notification\webapi\resolver\query\notification_preferences;
use totara_notification_mock_built_in_notification as mock_built_in;
use totara_notification_mock_notifiable_event_resolver as mock_resolver;
use totara_webapi\phpunit\webapi_phpunit_helper;

class totara_notification_webapi_get_notification_preferences_testcase extends testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    protected function setUp(): void {
        $generator = generator::instance();
        $generator->add_mock_built_in_notification_for_component();
        $generator->include_mock_recipient();
        $generator->include_mock_notifiable_event_resolver();
    }

    /**
     * @return void
     */
    public function test_get_notifications_at_context_only(): void {
        $generator = self::getDataGenerator();
        $course = $generator->create_course();
        $context_course = context_course::instance($course->id);

        $notification_generator = generator::instance();
        $custom_notification = $notification_generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_with_context($context_course),
            ['recipient' => totara_notification_mock_recipient::class, 'recipients' => [totara_notification_mock_recipient::class]]
        );

        $this->setAdminUser();
        $preferences = $this->resolve_graphql_query(
            $this->get_graphql_name(notification_preferences::class),
            [
                'extended_context' => [
                    'context_id' => $context_course->id,
                ],
                'at_context_only' => true,
            ]
        );
        $this->assertDebuggingCalled(['Do not use notification_preferences::resolve any more as it has been deprecated']);

        $system_built_in = notification_preference_loader::get_built_in(mock_built_in::class);
        self::assertCount(1, $preferences);

        /** @var model $preference */
        $preference = reset($preferences);
        self::assertInstanceOf(model::class, $preference);
        self::assertNotEquals($system_built_in->get_id(), $preference->get_id());
        self::assertEquals($custom_notification->get_id(), $preference->get_id());
    }

    /**
     * @return void
     */
    public function test_get_notifications_at_context_only_with_notifiable_event(): void {
        $generator = self::getDataGenerator();
        $course = $generator->create_course();
        $context_course = context_course::instance($course->id);

        $all_resolvers = notifiable_event_resolver_factory::get_resolver_classes();
        self::assertNotEmpty($all_resolvers);

        $first_resolver = reset($all_resolvers);

        $notification_generator = generator::instance();
        $custom_one_notification = $notification_generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_with_context($context_course),
            ['recipient' => totara_notification_mock_recipient::class, 'recipients' => [totara_notification_mock_recipient::class]]
        );

        $custom_two_notification = $notification_generator->create_notification_preference(
            $first_resolver,
            extended_context::make_with_context($context_course),
            ['recipient' => totara_notification_mock_recipient::class, 'recipients' => [totara_notification_mock_recipient::class]]
        );

        $this->setAdminUser();

        // Now fetch the notification preferences at specific context, and only at the context.
        // However, we will narrow it down to just a specific event.
        $preferences = $this->resolve_graphql_query(
            $this->get_graphql_name(notification_preferences::class),
            [
                'extended_context' => [
                    'context_id' => $context_course->id,
                ],
                'resolver_class_name' => $first_resolver,
                'at_context_only' => true,
            ]
        );
        $this->assertDebuggingCalled(['Do not use notification_preferences::resolve any more as it has been deprecated']);

        self::assertNotEmpty($preferences);
        self::assertCount(1, $preferences);

        /** @var model $preference */
        $preference = reset($preferences);

        self::assertNotEquals($custom_one_notification->get_id(), $preference->get_id());
        self::assertEquals($custom_two_notification->get_id(), $preference->get_id());
    }

    /**
     * @return void
     */
    public function test_get_notification_at_context(): void {
        $generator = self::getDataGenerator();
        $course = $generator->create_course();
        $context_course = context_course::instance($course->id);

        $all_resolvers = notifiable_event_resolver_factory::get_resolver_classes();
        self::assertNotEmpty($all_resolvers);

        $first_resolver = reset($all_resolvers);

        $notification_generator = generator::instance();
        $custom_one_notification = $notification_generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_with_context($context_course),
            ['recipient' => totara_notification_mock_recipient::class, 'recipients' => [totara_notification_mock_recipient::class]]
        );

        $custom_two_notification = $notification_generator->create_notification_preference(
            $first_resolver,
            extended_context::make_with_context($context_course),
            ['recipient' => totara_notification_mock_recipient::class, 'recipients' => [totara_notification_mock_recipient::class]]
        );

        $this->setAdminUser();

        // Now fetch the notification preferences at specific context, without specific at_only_context.
        $preferences = $this->resolve_graphql_query(
            $this->get_graphql_name(notification_preferences::class),
            [
                'extended_context' => ['context_id' => $context_course->id]
            ]
        );
        $this->assertDebuggingCalled(['Do not use notification_preferences::resolve any more as it has been deprecated']);

        // Note that it is not reliable to do the exact count number.
        // However we can make sure that all the notification preferences that we
        // want it to appear in the list would appear in the list.
        self::assertNotEmpty($preferences);
        $preference_ids = array_map(
            function (model $preference): int {
                return $preference->get_id();
            },
            $preferences
        );

        $system_built_in = notification_preference_loader::get_built_in(mock_built_in::class);

        self::assertContainsEquals($custom_one_notification->get_id(), $preference_ids);
        self::assertContainsEquals($custom_two_notification->get_id(), $preference_ids);
        self::assertContainsEquals($system_built_in->get_id(), $preference_ids);
    }

    /**
     * @return void
     */
    public function test_get_notification_at_context_with_notifiable_event(): void {
        $generator = self::getDataGenerator();
        $course = $generator->create_course();
        $context_course = context_course::instance($course->id);

        $all_resolvers = notifiable_event_resolver_factory::get_resolver_classes();
        self::assertNotEmpty($all_resolvers);

        $first_resolver = reset($all_resolvers);
        $system_built_in = notification_preference_loader::get_built_in(mock_built_in::class);

        $notification_generator = generator::instance();
        $system_overridden = $notification_generator->create_overridden_notification_preference(
            $system_built_in,
            extended_context::make_with_context($context_course),
            ['recipient' => totara_notification_mock_recipient::class, 'recipients' => [totara_notification_mock_recipient::class]]
        );

        $custom_two_notification = $notification_generator->create_notification_preference(
            $first_resolver,
            extended_context::make_with_context($context_course),
            ['recipient' => totara_notification_mock_recipient::class, 'recipients' => [totara_notification_mock_recipient::class]]
        );

        $this->setAdminUser();

        // Now fetch the notification preferences at specific context, without specific at_only_context.
        // But with specified event name.
        $preferences = $this->resolve_graphql_query(
            $this->get_graphql_name(notification_preferences::class),
            [
                'extended_context' => [
                    'context_id' => $context_course->id,
                ],
                'resolver_class_name' => totara_notification_mock_notifiable_event_resolver::class,
            ]
        );
        $this->assertDebuggingCalled(['Do not use notification_preferences::resolve any more as it has been deprecated']);

        self::assertNotEmpty($preferences);
        self::assertCount(1, $preferences);

        /** @var model $preference */
        $preference = reset($preferences);

        self::assertNotEquals($system_built_in->get_id(), $preference->get_id());
        self::assertNotEquals($custom_two_notification->get_id(), $preference->get_id());
        self::assertEquals($system_overridden->get_id(), $preference->get_id());
    }

    /**
     * @return void
     */
    public function test_user_cannot_get_notifications_without_manage_capability(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $course = $this->getDataGenerator()->create_course();
        $context_course = context_course::instance($course->id);

        try {
            $this->resolve_graphql_query(
                $this->get_graphql_name(notification_preferences::class),
                [
                    'extended_context' => [
                        'context_id' => $context_course->id,
                    ],
                    'at_context_only' => true,
                ]
            );
            $this->fail('Exception is expected but not thrown');
        } catch (notification_exception $ex) {
            $this->assertDebuggingCalled(['Do not use notification_preferences::resolve any more as it has been deprecated']);
            $this->assertSame(get_string('error_manage_notification', 'totara_notification'), $ex->getMessage());
        }
    }

    /**
     * @return void
     */
    public function test_user_can_get_notifications_with_manage_capability(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $course = $this->getDataGenerator()->create_course();
        $context_course = context_course::instance($course->id);

        $notification_generator = generator::instance();
        $notification_generator->include_mock_notifiable_event_resolver();

        $custom_notification = $notification_generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_with_context($context_course),
            ['recipient' => totara_notification_mock_recipient::class, 'recipients' => [totara_notification_mock_recipient::class]]
        );

        $role_id = builder::table('role')->where('shortname', 'user')->value('id');
        assign_capability('totara/notification:managenotifications', CAP_ALLOW, $role_id, SYSCONTEXTID, true);

        $preferences = $this->resolve_graphql_query(
            $this->get_graphql_name(notification_preferences::class),
            [
                'extended_context' => [
                    'context_id' => $context_course->id
                ],
                'at_context_only' => true,
            ]
        );
        $this->assertDebuggingCalled(['Do not use notification_preferences::resolve any more as it has been deprecated']);

        self::assertCount(1, $preferences);

        /** @var model $preference */
        $preference = reset($preferences);
        self::assertInstanceOf(model::class, $preference);

        self::assertEquals($custom_notification->get_id(), $preference->get_id());
    }
}