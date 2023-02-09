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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

use mod_perform\entity\activity\activity as activity_entity;
use totara_core\advanced_feature;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass \mod_perform\webapi\resolver\mutation\delete_activity
 *
 * @group perform
 */
class mod_perform_webapi_resolver_mutation_delete_activity_testcase extends advanced_testcase {
    private const MUTATION = 'mod_perform_delete_activity';

    use webapi_phpunit_helper;

    public function test_activate_delete_activity(): void {
        [$activity, $args] = $this->create_activity();
        self::assertTrue($this->container_course_exists($activity->course));

        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
        $this->assertTrue($result);
        self::assertNull(activity_entity::repository()->find($activity->id));
        self::assertFalse($this->container_course_exists($activity->course));
    }

    public function test_delete_activity_without_capability(): void {
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        [$activity, $args] = $this->create_activity($user1);
        self::assertTrue($this->container_course_exists($activity->course));

        self::setUser($user2);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Invalid activity');

        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    /**
     * Test the mutation through the GraphQL stack.
     */
    public function test_execute_query_successful(): void {
        [$activity, $args] = $this->create_activity();
        self::assertTrue($this->container_course_exists($activity->course));

        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertTrue($result);

        self::assertNull(activity_entity::repository()->find($activity->id));
        self::assertFalse($this->container_course_exists($activity->course));
    }

    public function test_failed_ajax_query(): void {
        [$activity, $args] = $this->create_activity();

        $feature = 'performance_activities';
        advanced_feature::disable($feature);
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed(
            $result,
            'Feature performance_activities is not available.'
        );
        advanced_feature::enable($feature);

        $result = $this->parsed_graphql_operation(self::MUTATION, []);
        $this->assert_webapi_operation_failed(
            $result,
            'Variable "$input" of required type "mod_perform_delete_activity_input!" was not provided.'
        );

        $args['input']['activity_id'] = 0;
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed(
            $result,
            'Invalid parameter value detected (invalid activity id)'
        );

        $activity_id = 999;
        $args['input']['activity_id'] = $activity_id;
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed(
            $result,
            "Invalid activity"
        );

        self::setGuestUser();
        $args['input']['activity_id'] = $activity->id;
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed(
            $result,
            'Invalid activity'
        );
    }

    private function container_course_exists(int $course_id): bool {
        global $DB;
        return $DB->record_exists('course', ['id' => $course_id]);
    }

    private function create_activity(?stdClass $as_user = null): array {
        if ($as_user) {
            self::setUser($as_user);
        } else {
            self::setAdminUser();
        }

        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();
        $activity = $perform_generator->create_activity_in_container();

        $args = [
            'input' => [
                'activity_id' => $activity->id
            ]
        ];

        return [$activity, $args];
    }
}
