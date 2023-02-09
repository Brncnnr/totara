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
 * @author Riana ROssouw <riana.rossouw@totaralearning.com>
 * @package totara_notification
 */

use core_phpunit\testcase;
use totara_core\extended_context;
use totara_notification\entity\notifiable_event_preference as notifiable_event_preference_entity;
use totara_notification\entity\notifiable_event_user_preference as notifiable_event_user_preference_entity;
use totara_notification\model\notifiable_event_preference as notifiable_event_preference_model;
use totara_notification\model\notifiable_event_user_preference as notifiable_event_user_preference_model;
use totara_notification\resolver\resolver_helper;
use totara_notification\testing\generator as totara_notification_generator;
use totara_webapi\phpunit\webapi_phpunit_helper;
use totara_notification_mock_notifiable_event_resolver as mock_event_resolver;
use totara_notification\exception\notification_exception;


/**
 * Note that this test is about testing the persist query rather than
 * the query resolver/handler. We are doing this because the resolver
 * only giving us the list of event class name.
 * Once we are upgrading the resolver to actually do DB look ups then it would be
 * the right time to have a test for the resolver.
 *
 * @group totara_notification
 */
class totara_notification_webapi_get_notifiable_event_user_preferences_testcase extends testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_get_notifiable_event_user_preferences(): void {
        $generator = totara_notification_generator::instance();
        $generator->include_mock_notifiable_event_resolver();
        $generator->add_notifiable_event_resolver(mock_event_resolver::class);
    
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
    
        $this->setAdminUser();
        $mock_resolver_class_name = mock_event_resolver::class;

        // First all enabled
        $this->setUser($user1);
        $this->execute_query_and_verify_for_user($user1->id, true, true);
        $this->setUser($user2);
        $this->execute_query_and_verify_for_user($user2->id, true, true);

        // Now disable mock event for user2 - should have effect on user1
        $user2_preference = $this->disable_notifiable_event_for_user($user2->id, $mock_resolver_class_name);
        $this->setUser($user1);
        $this->execute_query_and_verify_for_user($user1->id, true, true, null);
        $this->setUser($user2);
        $this->execute_query_and_verify_for_user($user2->id, true, false, $user2_preference->id);
        
        // Now disable mock event on system level - Not available for any user
        $this->disable_notifiable_event_on_system($mock_resolver_class_name);
        $this->setUser($user1);
        $this->execute_query_and_verify_for_user($user1->id, false, false, null);
        $this->setUser($user2);
        $this->execute_query_and_verify_for_user($user2->id, false, false, null);
    }

    /**
     * @return void
     */
    public function test_get_notifiable_event_user_preferences_fail(): void {
        $generator = totara_notification_generator::instance();
        $generator->include_mock_notifiable_event_resolver();
        $generator->add_notifiable_event_resolver(mock_event_resolver::class);

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        $this->setAdminUser();
        $mock_resolver_class_name = mock_event_resolver::class;

        $context_system = context_system::instance();
        $mock_resolver_class_name = mock_event_resolver::class;

        // User1 should see their own user preferences.
        $this->setUser($user1);
        $result_user1 = $this->execute_graphql_operation(
            'totara_notification_notifiable_event_user_preferences',
            [
                'user_id' => $user1->id,
                'extended_context' => ['context_id' => $context_system->id],
            ]
        );
        self::assertNotEmpty($result_user1->data['notifiable_event_user_preferences']);

        // User2 should not be able to see other user preferences.
        $this->setUser($user2);

        $this->expectException(notification_exception::class);
        $this->expectExceptionMessage(get_string('error_manage_notification', 'totara_notification'));

        $this->resolve_graphql_query(
            'totara_notification_notifiable_event_user_preferences',
            [
                'user_id' => $user1->id,
                'extended_context' => ['context_id' => $context_system->id],
            ]
        );
    }

    /**
     * @param int $user_id
     * @param bool $expect_mock
     * @param bool $expect_enabled
     * @param int|null $user_prefence_id
     */
    private function execute_query_and_verify_for_user(int $user_id, bool $expect_mock = true, bool $expect_enabled = false,
        ?int $user_prefence_id = null
    ): void {
        $context_system = context_system::instance();
        $mock_resolver_class_name = mock_event_resolver::class;
        
        $result = $this->execute_graphql_operation(
            'totara_notification_notifiable_event_user_preferences',
            [
                'user_id' => $user_id,
                'extended_context' => ['context_id' => $context_system->id],
            ]
        );
    
        self::assertEmpty($result->errors, 'Errors returned');
        self::assertNotEmpty($result->data);
        self::assertIsArray($result->data);
        self::assertArrayHasKey('notifiable_event_user_preferences', $result->data);
        $resolver_results = $result->data['notifiable_event_user_preferences'];
        self::assertIsArray($resolver_results);

        // Expecting mock custom event along side with all of the other events
        // from the system.
        self::assertGreaterThan(1, count($resolver_results));
        $mock_resolver_results = array_filter(
            $resolver_results,
            function (array $resolver): bool {
                return $resolver['resolver_class_name'] === mock_event_resolver::class;
            }
        );

        if ($expect_mock) {
            self::assertCount(1, $mock_resolver_results);
            $mock_resolver_result = reset($mock_resolver_results);

            // We're not specifically testing delivery channels here, so test that they exist,
            // but drop them from the final check
            self::assertArrayHasKey('delivery_channels', $mock_resolver_result);
            self::assertIsArray($mock_resolver_result['delivery_channels']);
            self::assertNotEmpty($mock_resolver_result['delivery_channels']);
            unset($mock_resolver_result['delivery_channels']);

            $expected = [
                'user_id' => "$user_id",
                'component' => resolver_helper::get_component_of_resolver_class_name($mock_resolver_class_name),
                'plugin_name' => resolver_helper::get_human_readable_plugin_name($mock_resolver_class_name),
                'resolver_class_name' => $mock_resolver_class_name,
                'name' => resolver_helper::get_human_readable_resolver_name($mock_resolver_class_name),
                'enabled' => $expect_enabled,
                'user_preference_id' => $user_prefence_id === null ? null : "$user_prefence_id",
                'overridden_delivery_channels' => false,
            ];
            self::assertEqualsCanonicalizing($expected, $mock_resolver_result);
        } else {
            self::assertCount(0, $mock_resolver_results);
        }
    }

    /**
     * @param int $user_id
     * @param string $resolver_class_name
     * @return notifiable_event_user_preference_entity
     */
    private function disable_notifiable_event_for_user(int $user_id, string $resolver_class_name): notifiable_event_user_preference_entity {
        $model = notifiable_event_user_preference_model::create($user_id, $resolver_class_name, extended_context::make_system(), false);
        return new notifiable_event_user_preference_entity($model->get_id());
    }
    
    /**
     * @param string $resolver_class_name
     * @return notifiable_event_preference_entity
     */
    private function disable_notifiable_event_on_system(string $resolver_class_name): notifiable_event_preference_entity {
        $model = notifiable_event_preference_model::create($resolver_class_name, extended_context::make_system(), false);
        return new notifiable_event_preference_entity($model->get_id());
    }
    
}