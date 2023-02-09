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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_perform
 */

use mod_perform\constants;
use mod_perform\models\activity\activity as activity;
use mod_perform\state\activity\active;
use mod_perform\state\activity\draft;
use totara_core\advanced_feature;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass \mod_perform\webapi\resolver\mutation\activate_activity
 *
 * @group perform
 */
class mod_perform_webapi_resolver_mutation_activate_activity_testcase extends advanced_testcase {
    private const MUTATION = 'mod_perform_activate_activity';

    use webapi_phpunit_helper;

    public function test_activate_draft_activity(): void {
        [$activity, $args] = $this->create_valid_activity();

        /** @var activity $result */
        ['activity' => $result] = $this->resolve_graphql_mutation('mod_perform_activate_activity', $args);
        $this->assertEquals($activity->id, $result->id);
        $this->assertEquals(active::get_code(), $result->status);
    }

    public function test_activate_draft_activity_which_does_not_satisfy_conditions(): void {
        [, $args] = $this->create_activity();

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Cannot activate this activity due to invalid state or conditions are not satisfied.');

        $this->resolve_graphql_mutation('mod_perform_activate_activity', $args);
    }

    public function test_activate_active_activity(): void {
        [$activity, $args] = $this->create_valid_activity(active::get_code());

        /** @var activity $result */
        ['activity' => $result] = $this->resolve_graphql_mutation('mod_perform_activate_activity', $args);
        $this->assertEquals($activity->id, $result->id);
        $this->assertEquals(active::get_code(), $result->status);
    }

    public function test_activate_activity_without_capability(): void {
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        [, $args] = $this->create_valid_activity(null, $user1);

        $this->setUser($user2);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Invalid activity');
        $this->resolve_graphql_mutation('mod_perform_activate_activity', $args);
    }

    public function test_activate_nonexisting_activity(): void {
        $root_key = 'input';
        $activity_id_key = 'activity_id';
        $args = [
            $root_key => [
                $activity_id_key => 999
            ]
        ];

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Invalid activity');

        $this->resolve_graphql_mutation('mod_perform_activate_activity', $args);
    }

    /**
     * Test the mutation through the GraphQL stack.
     */
    public function test_execute_query_successful_on_draft_activity() {
        [$activity, $args, ] = $this->create_valid_activity();

        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertNotEmpty($result, 'result empty');

        $activity_result = $result['activity'] ?? null;
        $this->assertNotEmpty($activity_result, 'result empty');
        $this->assertEquals($activity->id, $activity_result['id']);
        $this->assertEquals($activity->name, $activity_result['name']);
        $this->assertEquals(active::get_code(), $activity_result['state_details']['code']);
    }

    /**
     * Test the mutation through the GraphQL stack.
     */
    public function test_execute_query_successful_on_active_activity() {
        [$activity, $args, ] = $this->create_valid_activity();

        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertNotEmpty($result, 'result empty');

        $activity_result = $result['activity'] ?? null;
        $this->assertNotEmpty($activity_result, 'result empty');
        $this->assertEquals($activity->id, $activity_result['id']);
        $this->assertEquals($activity->name, $activity_result['name']);
        $this->assertEquals(active::get_code(), $activity_result['state_details']['code']);
    }

    /**
     * @covers ::resolve
     */
    public function test_failed_ajax_call(): void {
        [, $args, ] = $this->create_valid_activity();

        $feature = 'performance_activities';
        advanced_feature::disable($feature);
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'Feature performance_activities is not available.');
        advanced_feature::enable($feature);

        self::setGuestUser();
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'Invalid activity');
    }

    /**
     * Create a basic activity without any sections or questions in it
     *
     * @param int|null $status defaults to draft
     * @param stdClass|null $as_user user that creates the activity.
     * @return array [activity, graphql args] tuple.
     */
    protected function create_activity(int $status = null, ?stdClass $as_user = null): array {
        if ($as_user) {
            self::setUser($as_user);
        } else {
            self::setAdminUser();
        }

        $data_generator = $this->getDataGenerator();
        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = $data_generator->get_plugin_generator('mod_perform');

        $activity = $perform_generator->create_activity_in_container([
            'activity_name' => 'User1 One',
            'activity_status' => $status ?? draft::get_code(),
            'create_section' => false,
            'create_track' => false
        ]);

        $args = [
            'input' => [
                'activity_id' => $activity->id
            ]
        ];

        return [$activity, $args];
    }

    /**
     * Creates an activity with one section, one question and one relationship
     *
     * @param int|null $status defaults to draft
     * @return array [activity, graphql args] tuple.
     */
    protected function create_valid_activity(int $status = null): array {
        $data_generator = $this->getDataGenerator();
        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = $data_generator->get_plugin_generator('mod_perform');

        [$activity, $args] = $this->create_activity($status);

        $section = $perform_generator->create_section($activity, ['title' => 'Test section 1']);

        $perform_generator->create_section_relationship(
            $section,
            ['relationship' => constants::RELATIONSHIP_MANAGER]
        );

        $element = $perform_generator->create_element(['title' => 'Question one']);
        $perform_generator->create_section_element($section, $element);

        $perform_generator->create_single_activity_track_and_assignment($activity);

        return [$activity, $args];
    }
}
