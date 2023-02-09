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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 * @category test
 */

use mod_perform\models\activity\track;
use mod_perform\models\activity\track_assignment;
use mod_perform\models\activity\track_assignment_type;
use totara_core\advanced_feature;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass \mod_perform\webapi\resolver\mutation\remove_track_assignments
 *
 * @group perform
 */
class mod_perform_webapi_mutation_remove_track_assignments_testcase extends advanced_testcase {
    private const MUTATION = 'mod_perform_remove_track_assignments';

    use webapi_phpunit_helper;

    /**
     * @covers ::resolve
     */
    public function test_remove_track_assignments(): void {
        $assignment_type = track_assignment_type::ADMIN;
        [$track, $args, $context] = $this->setup_env(['type' => $assignment_type]);

        $assignments = $track->assignments;
        $this->assertNotEmpty($assignments->all(), 'track has no assignments');

        $updated_track = $this->resolve_graphql_mutation('mod_perform_remove_track_assignments', $args);
        $this->assertNotNull($updated_track, 'track assignment failed');
        $this->assertInstanceOf(track::class, $updated_track, 'wrong return type');
        $this->assertEquals($track->id, $updated_track->id, 'wrong track returned');
        $this->assertEquals(0, $updated_track->assignments->count(), 'assignments not removed');
    }

    /**
     * @covers ::resolve
     */
    public function test_successful_ajax_call(): void {
        $assignment_type = track_assignment_type::ADMIN;
        [$track, $args, ] = $this->setup_env(['type' => $assignment_type]);

        $assignments = $track->assignments;
        $this->assertNotEmpty($assignments->all(), 'track has no assignments');

        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);

        $updated_track = $this->get_webapi_operation_data($result);
        $this->assertNotNull($updated_track, 'track creation failed');
        $this->assertEquals($track->id, $updated_track['id'], 'wrong track returned');
        $this->assertEmpty($updated_track['assignments'], 'assignments not removed');
    }

    /**
     * @covers ::resolve
     */
    public function test_failed_ajax_call(): void {
        [$track, $args, ] = $this->setup_env(['type' => track_assignment_type::ADMIN]);

        $feature = 'performance_activities';
        advanced_feature::disable($feature);
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'Feature performance_activities is not available.');
        advanced_feature::enable($feature);

        $result = $this->parsed_graphql_operation(self::MUTATION, []);
        $this->assert_webapi_operation_failed($result, 'Variable "$assignments" of required type "mod_perform_track_assignments_input!" was not provided.');

        $args['assignments']['track_id'] = 0;
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'Invalid parameter value detected (invalid track id)');

        $track_id = 1293;
        $args['assignments']['track_id'] = $track_id;
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, "Invalid activity");

        self::setGuestUser();
        $args['assignments']['track_id'] = $track->id;
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'Invalid activity');
    }

    /**
     * Generates test data.
     *
     * @param array $track_details details other than the track id and groups to
     *        add to the generated graphql arguments.
     *
     * @return array [generated track, graphql arguments, graphql context] tuple.
     */
    private function setup_env(array $track_details = []): array {
        $this->setAdminUser();

        $generator = \mod_perform\testing\generator::instance();
        $activity = $generator->create_activity_in_container();

        $track = $generator->create_activity_tracks($activity, 1)->first();
        $updated_track = $generator->create_track_assignments($track, 2, 2, 2, 2);

        $groups = $track->assignments->reduce(
            function (array $groups, track_assignment $assignment): array {
                $group = $assignment->group;
                $groups[] = [
                    'id' => $group->get_id(),
                    'type' => $group->get_type()
                ];

                return $groups;
            },
            []
        );

        $track_details['track_id'] = $track->id;
        $track_details['groups'] = $groups;

        $args = ['assignments' => $track_details];

        $context = $this->create_webapi_context(self::MUTATION);
        $context->set_relevant_context($activity->get_context());

        return [$updated_track, $args, $context];
    }
}
