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
 * @author  Cody Finegan <cody.finegan@totaralearning.com>
 * @package totara_notification
 */

use core_phpunit\testcase;
use totara_core\extended_context;
use totara_notification\delivery\channel\delivery_channel;
use totara_notification\delivery\channel_helper;
use totara_notification\entity\notifiable_event_preference;
use totara_notification\exception\notification_exception;
use totara_notification\loader\delivery_channel_loader;
use totara_notification\testing\generator;
use totara_notification\webapi\resolver\mutation\update_default_delivery_channels;
use totara_notification_mock_delivery_channel as mock_channel_first;
use totara_notification_mock_delivery_channel_second as mock_channel_second;
use totara_notification_mock_delivery_channel_third as mock_channel_third;
use totara_notification_mock_notifiable_event_resolver as mock_resolver;
use totara_webapi\phpunit\webapi_phpunit_helper;

class totara_notification_webapi_update_notifiable_event_default_delivery_channels_testcase extends testcase {
    use webapi_phpunit_helper;

    /**
     * Confirm that we can update the delivery channels in the system context
     *
     * @return void
     */
    public function test_update_default_channels_for_system_context(): void {
        global $DB;

        // Confirm we have no existing preference
        $count = $DB->count_records('notifiable_event_preference');
        self::assertEquals(0, $count);

        $this->setAdminUser();
        $extended_context = extended_context::make_system();

        // Save the channels for the mock resolver
        /** @var delivery_channel[] $saved_channels */
        $saved_channels = $this->resolve_graphql_mutation(
            $this->get_graphql_name(update_default_delivery_channels::class),
            [
                'resolver_class_name' => mock_resolver::class,
                'default_delivery_channels' => [
                    'first',
                    'second',
                ],
            ]
        );

        self::assertIsArray($saved_channels);
        self::assertTrue($saved_channels['first']->is_enabled);
        self::assertTrue($saved_channels['second']->is_enabled);
        self::assertFalse($saved_channels['third']->is_enabled);

        $count = $DB->count_records('notifiable_event_preference');
        self::assertEquals(1, $count);

        // Run it a second time, toggling the second off
        $saved_channels = $this->resolve_graphql_mutation(
            $this->get_graphql_name(update_default_delivery_channels::class),
            [
                'resolver_class_name' => mock_resolver::class,
                'default_delivery_channels' => [
                    'first',
                ],
            ]
        );

        self::assertIsArray($saved_channels);
        self::assertFalse($saved_channels['second']->is_enabled);
        self::assertTrue($saved_channels['first']->is_enabled);
    }

    /**
     * @return void
     */
    public function test_update_delivery_channels_for_resolver_that_does_not_allow_a_user(): void {
        $generator = self::getDataGenerator();
        $user_one = $generator->create_user();

        $extended_context = extended_context::make_system();
        self::assertFalse(
            has_capability(
                'totara/notification:managenotifications',
                $extended_context->get_context(),
                $user_one->id
            ),
        );

        $notification_generator = generator::instance();
        $notification_generator->add_notifiable_event_resolver(mock_resolver::class);

        mock_resolver::set_permissions($extended_context, $user_one->id, false);
        $this->setUser($user_one);

        $this->expectException(notification_exception::class);
        $this->expectExceptionMessage(get_string('error_manage_notification', 'totara_notification'));

        $this->resolve_graphql_mutation(
            $this->get_graphql_name(update_default_delivery_channels::class),
            [
                'resolver_class_name' => mock_resolver::class,
                'default_delivery_channels' => ['first']
            ]
        );
    }

    /**
     * @return void
     */
    public function test_update_delivery_channels_for_resolver_that_does_allow_user(): void {
        global $DB;

        $generator = self::getDataGenerator();
        $user_one = $generator->create_user();

        $extended_context = extended_context::make_system();
        self::assertFalse(
            has_capability(
                'totara/notification:managenotifications',
                $extended_context->get_context(),
                $user_one->id
            ),
        );

        $notification_generator = generator::instance();
        $notification_generator->add_notifiable_event_resolver(mock_resolver::class);

        mock_resolver::set_permissions($extended_context, $user_one->id, true);
        $this->setUser($user_one);

        self::assertEquals(0, $DB->count_records(notifiable_event_preference::TABLE));

        try {
            $this->resolve_graphql_mutation(
                $this->get_graphql_name(update_default_delivery_channels::class),
                [
                    'resolver_class_name' => mock_resolver::class,
                    'default_delivery_channels' => ['first']
                ]
            );
        } catch (Throwable $e) {
            self::fail("Expecting the operation to update default delivery channels will not yield any errors");
        }

        // Confirms one record created.
        self::assertEquals(1, $DB->count_records(notifiable_event_preference::TABLE));
        $entity = notifiable_event_preference::repository()->for_context(mock_resolver::class, $extended_context);

        self::assertEquals(",first,", $entity->default_delivery_channels);
    }

    /**
     * Use mock delivery channels so these tests do not depend on the message output plugins
     * which may be missing.
     */
    protected function setUp(): void {
        global $DB;

        // We want nothing created
        $DB->execute('TRUNCATE TABLE {notifiable_event_preference}');

        $notification_generator = generator::instance();
        $notification_generator->include_mock_notifiable_event_resolver();

        $generator = generator::instance();
        $generator->include_mock_notifiable_event_resolver();
        $generator->include_mock_delivery_channels();

        // Always reset the delivery channels back to nothing
        mock_resolver::set_notification_default_delivery_channels([]);

        // Reset the mock channels
        mock_channel_first::clear();
        mock_channel_second::clear();
        mock_channel_third::clear();

        // This lets us test the delivery channels without creating a dependency on message_* plugins
        $this->set_loader_definitions([
            mock_channel_first::class,
            mock_channel_second::class,
            mock_channel_third::class,
        ]);
        $this->set_valid_channels(['first', 'second', 'third']);
        $this->set_enabled_message_outputs(['first', 'second', 'third']);
    }

    /**
     * Remove the hanging models
     */
    protected function tearDown(): void {
        mock_resolver::set_notification_default_delivery_channels([]);
        mock_channel_first::clear();
        mock_channel_second::clear();
        mock_channel_third::clear();
        $this->set_loader_definitions(null);
        $this->set_valid_channels(null);
        $this->set_enabled_message_outputs(null);
    }

    /**
     * Helper method to override the enabled message output filter - otherwise
     * our mock delivery channels will be filtered out.
     *
     * @param array|null $enabled_outputs
     */
    private function set_enabled_message_outputs(?array $enabled_outputs): void {
        $cache_property = new ReflectionProperty(delivery_channel_loader::class, 'enabled_outputs');
        $cache_property->setAccessible(true);
        $cache_property->setValue($enabled_outputs);
    }

    /**
     * Helper method to override the channel validator when we're testing.
     * Otherwise the mock tests will fail the validation.
     *
     * @param array|null $valid_channels
     */
    private function set_valid_channels(?array $valid_channels): void {
        $cache_property = new ReflectionProperty(channel_helper::class, 'valid_channels');
        $cache_property->setAccessible(true);
        $cache_property->setValue($valid_channels);
    }

    /**
     * Helper method to override the channel loader when we're testing.
     * This allows us to override and inject our own mock delivery channels for testing.
     *
     * @param array|null $definitions
     */
    private function set_loader_definitions(?array $definitions): void {
        $cache_property = new ReflectionProperty(delivery_channel_loader::class, 'definitions');
        $cache_property->setAccessible(true);
        $cache_property->setValue($definitions);
    }
}