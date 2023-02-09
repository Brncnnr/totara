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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package totara_notification
 */

use core_phpunit\testcase;
use totara_core\extended_context;
use totara_notification\entity\notifiable_event_user_preference as notifiable_event_user_preference_entity;
use totara_notification\loader\delivery_channel_loader;
use totara_notification\testing\generator as totara_notification_generator;
use totara_notification_mock_notifiable_event_resolver as mock_event_resolver;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * Confirm we can update a user's notifiable_event_user_preferences
 *
 * @group totara_notification
 */
class totara_notification_webapi_update_notifiable_event_user_delivery_channels_testcase extends testcase {
    use webapi_phpunit_helper;

    /**
     * @var stdClass
     */
    private $user;

    /**
     * @var array
     */
    private $default_states;

    /**
     * @return array
     */
    public function provide_test_data(): array {
        return [
            [['email'], ',email,'],
            [[], ',,'],
            [['popup', 'msteams'], ',popup,msteams,'],
            [null, null],
        ];
    }

    /**
     * @dataProvider provide_test_data
     * @param array|null $test_data
     * @param string|null $expected_db
     */
    public function test_update_notifiable_event_user_delivery_channels(?array $test_data, ?string $expected_db): void {
        global $DB;
        $DB->delete_records(notifiable_event_user_preference_entity::TABLE);

        $extended_context = extended_context::make_system();
        $result = $this->execute_graphql_operation(
            'totara_notification_update_user_delivery_channels',
            [
                'user_id' => $this->user->id,
                'resolver_class_name' => mock_event_resolver::class,
                'extended_context' => ['context_id' => $extended_context->get_context_id()],
                'delivery_channels' => $test_data,
            ]
        );

        self::assertEmpty($result->errors, 'Errors returned');
        self::assertNotEmpty($result->data);
        self::assertIsArray($result->data);
        self::assertArrayHasKey('notifiable_event_user_preference', $result->data);
        $resolver_result = $result->data['notifiable_event_user_preference'];
        self::assertIsArray($resolver_result);
        self::assertArrayHasKey('user_preference_id', $resolver_result);

        // Check the DB result matches what we expect
        $delivery_channels = $DB->get_field(
            notifiable_event_user_preference_entity::TABLE,
            'delivery_channels',
            ['id' => $resolver_result['user_preference_id']]
        );
        self::assertSame($expected_db, $delivery_channels);

        // Check the returned results are in the states we check
        if ($test_data === null) {
            // Check against the defaults
            foreach ($resolver_result['delivery_channels'] as $delivery_channel) {
                $component = $delivery_channel['component'];
                self::assertSame($this->default_states[$component], $delivery_channel['is_enabled']);
            }
        } else {
            // Check against the test data
            foreach ($resolver_result['delivery_channels'] as $delivery_channel) {
                $component = $delivery_channel['component'];
                if (in_array($component, $test_data)) {
                    self::assertTrue($delivery_channel['is_enabled'], "{$component} was not enabled");
                } else {
                    self::assertFalse($delivery_channel['is_enabled'], "{$component} was not disabled");
                }
            }
        }

        // Try submitting the result again - it should not crash
        $result = $this->execute_graphql_operation(
            'totara_notification_update_user_delivery_channels',
            [
                'user_id' => $this->user->id,
                'resolver_class_name' => mock_event_resolver::class,
                'extended_context' => ['context_id' => $extended_context->get_context_id()],
                'delivery_channels' => $test_data,
            ]
        );

        self::assertEmpty($result->errors, 'Errors returned after second create');
        self::assertNotEmpty($result->data);
        self::assertIsArray($result->data);
        self::assertArrayHasKey('notifiable_event_user_preference', $result->data);
        $resolver_result2 = $result->data['notifiable_event_user_preference'];
        self::assertIsArray($resolver_result2);
        self::assertArrayHasKey('user_preference_id', $resolver_result2);
        self::assertSame($resolver_result['user_preference_id'], $resolver_result2['user_preference_id']);
    }

    /**
     * Include the mock resolvers
     */
    protected function setUp(): void {
        $generator = totara_notification_generator::instance();
        $generator->include_mock_notifiable_event_resolver();
        $generator->add_notifiable_event_resolver(mock_event_resolver::class);

        $this->user = self::getDataGenerator()->create_user();
        $this->setUser($this->user);

        $default_channels = delivery_channel_loader::get_for_event_resolver(mock_event_resolver::class);
        $this->default_states = [];
        foreach ($default_channels as $delivery_channel) {
            $this->default_states[$delivery_channel->component] = $delivery_channel->is_enabled;
        }
    }

    /**
     * Cleanup
     */
    protected function tearDown(): void {
        $this->user = null;
        $this->default_states = null;
    }
}