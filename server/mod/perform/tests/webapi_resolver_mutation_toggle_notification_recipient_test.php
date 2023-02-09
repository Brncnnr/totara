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

use mod_perform\constants;
use mod_perform\models\activity\activity as activity_model;
use mod_perform\models\activity\notification as notification_model;
use mod_perform\models\activity\section_relationship;
use totara_core\advanced_feature;
use totara_core\relationship\relationship;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass \mod_perform\webapi\resolver\mutation\toggle_notification_recipient
 *
 * @group perform
 */
class mod_perform_webapi_resolver_mutation_toggle_notification_recipient_testcase extends advanced_testcase {
    private const MUTATION = 'mod_perform_toggle_notification_recipient';

    use webapi_phpunit_helper;

    private function create_test_data(): notification_model {
        self::setAdminUser();

        $data_generator = self::getDataGenerator();
        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = $data_generator->get_plugin_generator('mod_perform');

        /** @var activity_model $activity */
        $activity = $perform_generator->create_activity_in_container([]);
        return notification_model::load_by_activity_and_class_key($activity, 'instance_created');
    }

    public function create_test_relationship(notification_model $notification): relationship {
        $data_generator = self::getDataGenerator();
        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = $data_generator->get_plugin_generator('mod_perform');

        $activity = $notification->get_activity();
        $section = $perform_generator->create_section($activity);
        $rel_id = $perform_generator->get_core_relationship(constants::RELATIONSHIP_SUBJECT)->id;
        return section_relationship::create($section->get_id(), $rel_id, true)->core_relationship;
    }

    public function data_true_false(): array {
        return [[true], [false]];
    }

    /**
     * @param boolean $active
     * @dataProvider data_true_false
     */
    public function test_toggle_notification_recipient(bool $active): void {
        $notification = $this->create_test_data();
        $relationship = $this->create_test_relationship($notification);
        $args = [
            'input' => [
                'notification_id' => $notification->id,
                'relationship_id' => $relationship->id,
                'active' => $active
            ]
        ];

        /** @type notification_model $result */
        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);

        $notification = $result->notification;

        $this->assertNotEmpty($notification->id);
        $this->assertCount(1, $notification->recipients);
        $recipient = $notification->recipients->shift();
        $this->assertNotEmpty($recipient->core_relationship_id);
        $this->assertSame(get_string('relationship_name_subject', 'totara_core'), $recipient->relationship->name);
        $this->assertEquals($active, $recipient->active);
    }

    /**
     * @param boolean $active
     * @dataProvider data_true_false
     */
    public function test_toggle_notification_recipient_for_non_admin_user(bool $active): void {
        $notification = $this->create_test_data();
        $relationship = $this->create_test_relationship($notification);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $args = [
            'input' => [
                'notification_id' => $notification->id,
                'relationship_id' => $relationship->id,
                'active' => $active
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

    public function test_toggle_notification_recipient_with_missing_input_property(): void {
        $notification = $this->create_test_data();
        $relationship = $this->create_test_relationship($notification);
        $args = [
            'notification_id' => $notification->id,
            'relationship_id' => $relationship->id,
            'active' => true
        ];

        // Note: the middleware catches this before it ever gets to the resolver, because it can't find the notification_id.
        try {
            $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('invalid_parameter_exception expected');
        } catch (invalid_parameter_exception $ex) {
            $this->assertStringContainsString('Invalid parameter value detected (invalid notification id)', $ex->getMessage());
        }
    }

    public function test_toggle_notification_recipient_with_missing_notification_id(): void {
        $this->setAdminUser();
        $args = [
            'input' => [
                'active' => true
            ]
        ];

        try {
            $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('invalid_parameter_exception expected');
        } catch (invalid_parameter_exception $ex) {
            $this->assertStringContainsString('Invalid parameter value detected (invalid notification id)', $ex->getMessage());
        }
    }

    public function test_toggle_notification_recipient_with_invalid_notification_id(): void {
        $notification = $this->create_test_data();
        $relationship = $this->create_test_relationship($notification);
        $args = [
            'input' => [
                'notification_id' => 1138,
                'relationship_id' => $relationship->id,
                'active' => true
            ]
        ];

        try {
            $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('moodle_exception expected');
        } catch (moodle_exception $ex) {
            $this->assertStringContainsString('Invalid activity', $ex->getMessage());
        }
    }

    public function test_toggle_notification_recipient_with_missing_relationship_id(): void {
        $notification = $this->create_test_data();
        $args = [
            'input' => [
                'notification_id' => $notification->id,
                'active' => true
            ]
        ];

        $this->expectException(invalid_parameter_exception::class);
        $this->expectExceptionMessageMatches('/Invalid relationship_id specified or recipient record for relationship with ID/');
        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    public function test_toggle_notification_recipient_with_invalid_relationship_id(): void {
        $notification = $this->create_test_data();
        $args = [
            'input' => [
                'notification_id' => $notification->id,
                'relationship_id' => -1,
                'active' => true
            ]
        ];

        $this->expectException(invalid_parameter_exception::class);
        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    public function test_toggle_notification_with_missing_active(): void {
        $notification = $this->create_test_data();
        $relationship = $this->create_test_relationship($notification);
        $args = [
            'input' => [
                'notification_id' => $notification->id,
                'relationship_id' => $relationship->id,
            ]
        ];

        try {
            $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('invalid_parameter_exception expected');
        } catch (invalid_parameter_exception $ex) {
            $this->assertStringContainsString('Invalid parameter value detected (active not set as part of input)', $ex->getMessage());
        }
    }

    /**
     * @param boolean $active
     * @dataProvider data_true_false
     * @covers ::resolve
     */
    public function test_successful_ajax_call(bool $active): void {
        $notification = $this->create_test_data();
        $relationship = $this->create_test_relationship($notification);
        $args = [
            'input' => [
                'notification_id' => $notification->id,
                'relationship_id' => $relationship->id,
                'active' => $active
            ]
        ];

        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertNotEmpty($result, 'notification creation failed');

        $notification = $result['notification'];
        $this->assertNotEmpty($notification['id']);
        $this->assertCount(1, $notification['recipients']);
        $this->assertNotEmpty($notification['recipients'][0]['relationship_id']);
        $this->assertSame(get_string('relationship_name_subject', 'totara_core'), $notification['recipients'][0]['name']);
        $this->assertEquals($active, $notification['recipients'][0]['active']);

        // Check your work.
        $actual_notification = notification_model::load_by_id($notification['id']);
        $actual_recipient = $actual_notification->recipients->shift();
        $this->assertEquals($actual_recipient->core_relationship_id, $notification['recipients'][0]['relationship_id']);
        $this->assertSame($active, $notification['recipients'][0]['active']);
    }

    /**
     * @covers ::resolve
     */
    public function test_failed_ajax_call(): void {
        $notification = $this->create_test_data();
        $relationship = $this->create_test_relationship($notification);
        $args = [
            'input' => [
                'notification_id' => $notification->id,
                'relationship_id' => $relationship->id,
                'active' => true
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
