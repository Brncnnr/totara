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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_notification
 */

use core_phpunit\testcase;
use totara_core\extended_context;
use totara_notification\entity\notifiable_event_user_preference as notifiable_event_user_preference_entity;
use totara_notification\resolver\resolver_helper;
use totara_notification\testing\generator as totara_notification_generator;
use totara_webapi\phpunit\webapi_phpunit_helper;
use totara_notification_mock_notifiable_event_resolver as mock_event_resolver;


/**
 * Confirm we can update a user's notifiable_event_user_preferences
 *
 * @group totara_notification
 */
class totara_notification_webapi_update_notifiable_event_user_preference_testcase extends testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_update_notifiable_event_user_preference(): void {
        $generator = totara_notification_generator::instance();
        $generator->include_mock_notifiable_event_resolver();
        $generator->add_notifiable_event_resolver(mock_event_resolver::class);

        $user1 = self::getDataGenerator()->create_user();

        $this->setUser($user1);
        $rows = notifiable_event_user_preference_entity::repository()
            ->get()
            ->to_array();
        self::assertEmpty($rows);

        $this->execute_mutation_and_verify_for_user($user1->id, false);
        $this->execute_mutation_and_verify_for_user($user1->id, true);
    }

    /**
     * @param int $user_id
     * @param bool $set_enabled
     */
    private function execute_mutation_and_verify_for_user(int $user_id, bool $set_enabled): void {
        $context_system = context_system::instance();
        $mock_resolver_class_name = mock_event_resolver::class;
        $extended_context = extended_context::make_system();

        $result = $this->execute_graphql_operation(
            'totara_notification_update_notifiable_event_user_preference',
            [
                'user_id' => $user_id,
                'resolver_class_name' => $mock_resolver_class_name,
                'extended_context' => ['context_id' => $context_system->id],
                'is_enabled' => (int)$set_enabled,
            ]
        );

        self::assertEmpty($result->errors, 'Errors returned');
        self::assertNotEmpty($result->data);
        self::assertIsArray($result->data);
        self::assertArrayHasKey('notifiable_event_user_preference', $result->data);
        $resolver_result = $result->data['notifiable_event_user_preference'];
        self::assertIsArray($resolver_result);

        // Verify row on db
        $rows = notifiable_event_user_preference_entity::repository()
            ->filter_by_extended_context($extended_context)
            ->where('user_id', $user_id)
            ->where('resolver_class_name', $mock_resolver_class_name)
            ->where('enabled', $set_enabled)
            ->get()
            ->to_array();

        self::assertCount(1, $rows);
        $row = reset($rows);
        self::assertEquals($user_id, $row['user_id']);
        self::assertEquals($mock_resolver_class_name, $row['resolver_class_name']);
        self::assertEquals((int)$set_enabled, $row['enabled']);

        // We're not specifically testing delivery channels here, so test that they exist,
        // but drop them from the final check
        self::assertArrayHasKey('delivery_channels', $resolver_result);
        self::assertIsArray($resolver_result['delivery_channels']);
        self::assertNotEmpty($resolver_result['delivery_channels']);
        unset($resolver_result['delivery_channels']);

        // Verify results from the mutation
        $expected = [
            'user_id' => "$user_id",
            'component' => resolver_helper::get_component_of_resolver_class_name($mock_resolver_class_name),
            'plugin_name' => resolver_helper::get_human_readable_plugin_name($mock_resolver_class_name),
            'resolver_class_name' => $mock_resolver_class_name,
            'name' => resolver_helper::get_human_readable_resolver_name($mock_resolver_class_name),
            'enabled' => $set_enabled,
            'user_preference_id' => "{$row['id']}",
            'overridden_delivery_channels' => false,
        ];
        self::assertEqualsCanonicalizing($expected, $resolver_result);
    }

}