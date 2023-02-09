<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_perform
 */

use mod_perform\models\activity\activity as activity_model;
use mod_perform\models\activity\notification as notification_model;
use totara_core\advanced_feature;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass \mod_perform\webapi\resolver\mutation\update_notification_triggers
 *
 * @group perform
 */
class mod_perform_webapi_resolver_update_notification_triggers extends advanced_testcase {
    private const MUTATION = 'mod_perform_update_notification_triggers';

    use webapi_phpunit_helper;

    private function create_test_data(): notification_model {
        self::setAdminUser();

        $data_generator = self::getDataGenerator();
        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = $data_generator->get_plugin_generator('mod_perform');

        /** @var activity_model $activity */
        $activity = $perform_generator->create_activity_in_container([]);
        return notification_model::load_by_activity_and_class_key($activity, 'overdue_reminder');
    }

    public function data_triggers(): array {
        return [
            [[], []],
            [[3, 1, 4], [1, 3, 4]],
        ];
    }

    /**
     * @param array $input
     * @param array $expected
     * @dataProvider data_triggers
     */
    public function test_update_triggers(array $input, array $expected): void {
        $notification = $this->create_test_data();
        $args = [
            'input' => [
                'notification_id' => $notification->id,
                'values' => $input,
            ]
        ];

        /** @var notification_model $result */
        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);

        $notification = $result->notification;

        $this->assertNotEmpty($notification->id);
        $this->assertSame(get_string('notification_broker_overdue_reminder', 'mod_perform'), $notification->name);
        $this->assertEquals($expected, $notification->triggers);
    }

    /**
     * @param array $input
     * @param array $unused
     * @dataProvider data_triggers
     */
    public function test_update_triggers_for_non_admin_user(array $input, array $unused): void {
        $notification = $this->create_test_data();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $args = [
            'input' => [
                'notification_id' => $notification->id,
                'values' => $input
            ]
        ];

        /** @var notification_model $result */
        try {
            $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('moodle_exception expected');
        } catch (moodle_exception $ex) {
            $this->assertStringContainsString('Invalid activity', $ex->getMessage());
        }
    }

    public function test_update_triggers_with_missing_input_property(): void {
        $notification = $this->create_test_data();
        $args = [
            'notification_id' => $notification->id,
            'values' => []
        ];

        // Note: the middleware catches this before it ever gets to the resolver, because it can't find the notification_id.
        try {
            $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('invalid_parameter_exception expected');
        } catch (invalid_parameter_exception $ex) {
            $this->assertStringContainsString('Invalid parameter value detected (invalid notification id)', $ex->getMessage());
        }
    }

    public function test_update_triggers_with_missing_notification_id(): void {
        $this->setAdminUser();
        $args = [
            'input' => [
                'values' => []
            ]
        ];

        try {
            $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('invalid_parameter_exception expected');
        } catch (invalid_parameter_exception $ex) {
            $this->assertStringContainsString('Invalid parameter value detected (invalid notification id)', $ex->getMessage());
        }
    }

    public function test_update_triggers_with_invalid_notification_id(): void {
        $this->setAdminUser();
        $args = [
            'input' => [
                'notification_id' => 1138,
                'values' => []
            ]
        ];

        try {
            $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('moodle_exception expected');
        } catch (moodle_exception $ex) {
            $this->assertStringContainsString('Invalid activity', $ex->getMessage());
        }
    }

    public function test_update_triggers_with_missing_triggers(): void {
        $notification = $this->create_test_data();
        $args = [
            'input' => [
                'notification_id' => $notification->id,
            ]
        ];

        try {
            $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('invalid_parameter_exception expected');
        } catch (invalid_parameter_exception $ex) {
            $this->assertStringContainsString('Invalid parameter value detected (values not set as part of input)', $ex->getMessage());
        }
    }

    /**
     * @param array $input
     * @param array $expected
     * @dataProvider data_triggers
     * @covers ::resolve
     */
    public function test_successful_ajax_call(array $input, array $expected): void {
        $notification = $this->create_test_data();
        $args = [
            'input' => [
                'notification_id' => $notification->id,
                'values' => $input
            ]
        ];

        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertNotEmpty($result, 'notification creation failed');

        $notification = $result['notification'];
        $this->assertNotEmpty($notification['id']);
        $this->assertSame(get_string('notification_broker_overdue_reminder', 'mod_perform'), $notification['name']);
        $this->assertEquals($expected, $notification['triggers']);

        // Check your work.
        $actual_notification = notification_model::load_by_id($notification['id']);
        $this->assertEquals($actual_notification->id, $notification['id']);
        $this->assertSame($actual_notification->name, $notification['name']);
        $this->assertSame(count($actual_notification->triggers), count($notification['triggers']));
    }

    /**
     * @covers ::resolve
     */
    public function test_failed_ajax_call(): void {
        $notification = $this->create_test_data();
        $args = [
            'input' => [
                'notification_id' => $notification->id,
                'values' => []
            ]
        ];

        $feature = 'performance_activities';
        advanced_feature::disable($feature);
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'Feature performance_activities is not available.');
        advanced_feature::enable($feature);

        self::setGuestUser();
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'Invalid activity');
    }
}
