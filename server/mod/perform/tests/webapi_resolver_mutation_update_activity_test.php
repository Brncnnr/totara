<?php
/*
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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 */

use core_phpunit\testcase;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\activity_setting;
use mod_perform\models\activity\activity_type as activity_type_model;
use mod_perform\models\activity\settings\visibility_conditions\all_responses;
use mod_perform\models\activity\settings\visibility_conditions\own_response;
use mod_perform\state\activity\draft;
use mod_perform\testing\generator;
use totara_core\advanced_feature;
use totara_core\relationship\relationship;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group perform
 * Tests the mutation to create assignments for self or other
 */
class mod_perform_webapi_resolver_mutation_update_activity_testcase extends testcase {
    private const MUTATION = 'mod_perform_update_activity';

    use webapi_phpunit_helper;

    public function test_user_cannot_update_without_permission(): void {
        [, $args] = $this->create_activity();

        $user = self::getDataGenerator()->create_user();
        self::setUser($user);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Invalid activity');

        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    public function test_update_success(): void {
        [, $args] = $this->create_activity();

        /** @var activity $activity */
        ['activity' => $activity] = $this->resolve_graphql_mutation(self::MUTATION, $args);

        // Return values should be updated
        $this->assert_base_update_result($args, $activity);
    }

    public function test_activity_must_belong_to_user(): void {
        $data_generator = self::getDataGenerator();

        $user1 = $data_generator->create_user();
        $user2 = $data_generator->create_user();

        [, $args] = $this->create_activity($user1);

        /** @type activity $returned_activity */
        ['activity' => $returned_activity] = $this->resolve_graphql_mutation(self::MUTATION, $args);

        $this->assert_base_update_result($args, $returned_activity);

        self::setUser($user2);
        $this->expectException(moodle_exception::class);

        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    /**
     * Tests updating manual relationship selector relationships for an activity.
     *
     * @return void
     */
    public function test_update_activity_manual_relationships_selectors(): void {
        [$activity, $args] = $this->create_activity();
        $args['with_relationships'] = true;
        $args['relationships'] = [];
        $manager_relationship = relationship::load_by_idnumber('manager');
        foreach ($activity->manual_relationships as $manual_relationship) {
            $args['relationships'][] = [
                'manual_relationship_id' => $manual_relationship->manual_relationship_id,
                'selector_relationship_id' => $manager_relationship->id,
            ];
        }

        /** @var activity $updated_activity */
        ['activity' => $updated_activity] = $this->resolve_graphql_mutation(self::MUTATION, $args);
        $this->assertDebuggingCalled(
            'Using the mod_perform_update_activity mutation to update relationships is deprecated',
            DEBUG_DEVELOPER
        );

        // Return values should be updated
        $this->assert_base_update_result($args, $updated_activity);
        foreach ($updated_activity->manual_relationships as $manual_relationship) {
            $this->assertEquals($manual_relationship->selector_relationship_id, $manager_relationship->id);
        }
    }

    /**
     * Test update visibility condition for an activity
     */
    public function test_update_visibility_control(): void {
        [$activity, $args] = $this->create_activity();
        $args['visibility_condition'] = all_responses::VALUE;
        $this->assertEquals(0, $activity->get_settings()->get()->count());

        $this->resolve_graphql_mutation(self::MUTATION, $args);

        $activity_setting = activity_setting::load_by_name($activity->get_id(), activity_setting::VISIBILITY_CONDITION);
        $this->assertEquals($args['visibility_condition'], $activity_setting->value);
    }

    /**
     * Test update invalid visibility condition will throw exception
     */
    public function test_update_invalid_visibility_control_value_should_throw_exception() {
        [, $args] = $this->create_activity();
        $args['anonymous_responses'] = false;
        $args['visibility_condition'] = 5;

        $this->expectExceptionMessage("invalid visibility condition value: 5");
        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    /**
     * Test turn on anonymous responses will set visibility control to all responses closed
     */
    public function test_turn_on_anonymous_responses_should_set_visibility_control_to_all_responses_closed() {
        [$activity, $args] = $this->create_activity();
        $args['visibility_condition'] = own_response::VALUE;

        $this->resolve_graphql_mutation(self::MUTATION, $args);
        $activity_setting = activity_setting::load_by_name($activity->get_id(), activity_setting::VISIBILITY_CONDITION);

        $this->assertEquals(all_responses::VALUE, $activity_setting->value);
    }

    public function test_successful_ajax_call(): void {
        [, $args] = $this->create_activity();

        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertNotNull($result, 'null result');

        $result = $result['activity'];
        $this->assert_base_update_result($args, $result);

        $actual_type_display_name = $result['type']['display_name'];
        $expected_type_display_name = activity_type_model::load_by_id($args['type_id'])->get_display_name();
        $this->assertEquals($actual_type_display_name, $expected_type_display_name);
    }

    public function test_failed_ajax_query(): void {
        [, $args] = $this->create_activity();

        $feature = 'performance_activities';
        advanced_feature::disable($feature);
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'Feature performance_activities is not available.');
        advanced_feature::enable($feature);

        $result = $this->parsed_graphql_operation(self::MUTATION, []);
        $this->assert_webapi_operation_failed($result, 'activity_id');
    }

    private function create_activity(?stdClass $as_user = null): array {
        if ($as_user) {
            self::setUser($as_user);
        } else {
            self::setAdminUser();
        }

        $perform_generator = generator::instance();
        $activity = $perform_generator->create_activity_in_container(['activity_status' => draft::get_code()]);
        $new_type_id = 3;

        $args = [
            'activity_id' => $activity->id,
            'name' => "Activity-1",
            'description' => "Description of Activity 1",
            'type_id' => $new_type_id,
            'anonymous_responses' => true,
            'with_relationships' => false,
        ];

        return [$activity, $args];
    }

    /**
     * @param array $args
     * @param array|object|activity $returned_activity
     */
    private function assert_base_update_result(array $args, $returned_activity): void {
        if (is_array($returned_activity)) {
            $returned_activity = (object) $returned_activity;
            $returned_activity->type = (object) $returned_activity->type;
        }

        self::assertEquals($returned_activity->id, $args['activity_id']);
        self::assertEquals($returned_activity->name, $args['name']);
        self::assertEquals($returned_activity->description, $args['description']);
        self::assertEquals($returned_activity->type->id, $args['type_id']);
        self::assertTrue($returned_activity->anonymous_responses);
    }

}
