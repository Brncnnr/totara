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
use mod_perform\models\activity\track_assignment_type;
use mod_perform\user_groups\grouping;
use totara_core\advanced_feature;
use totara_job\job_assignment;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass \mod_perform\webapi\resolver\mutation\add_track_assignments
 *
 * @group perform
 */
class mod_perform_webapi_mutation_add_track_assignments_testcase extends advanced_testcase {
    private const MUTATION = 'mod_perform_add_track_assignments';

    use webapi_phpunit_helper;

    /**
     * @covers ::resolve
     */
    public function test_add_track_assignments(): void {
        $this->setAdminUser();

        $assignment_type = track_assignment_type::ADMIN;
        [$track, $args, ] = $this->setup_env(['type' => $assignment_type]);

        $this->assertEmpty($track->assignments->all(), 'track has assignments');

        $updated_track = $this->resolve_graphql_mutation('mod_perform_add_track_assignments', $args);
        $this->assertNotNull($updated_track, 'track assignment failed');
        $this->assertInstanceOf(track::class, $updated_track, 'wrong return type');
        $this->assertEquals($track->id, $updated_track->id, 'wrong track returned');
        $this->assertEquals($track->status, $updated_track->status, 'wrong track status');

        $expected = [];
        foreach ($args['assignments']['groups'] as $group) {
            $expected[$group['id']] = $group['type'];
        }

        $updated_assignments = $updated_track->assignments;
        $this->assertEquals(
            count($expected),
            $updated_assignments->count(),
            'track has no assignments'
        );

        foreach ($updated_assignments->all() as $assignment) {
            $this->assertEquals($track->id, $assignment->track_id, 'wrong track id');
            $this->assertEquals($assignment_type, $assignment->type, 'wrong track type');

            $group = $assignment->group;
            $group_id = $group->get_id();
            $expected_group_type = $expected[$group_id] ?? null;

            $this->assertNotNull($expected_group_type, "unknown group id '$group_id'");
            $this->assertEquals($expected_group_type, $group->get_type(), 'wrong group type');

            $expected_size = $expected_group_type === grouping::USER ? 1 : 5;
            $this->assertEquals($expected_size, $group->get_size(), 'wrong group size');
        }
    }

    /**
     * @covers ::resolve
     */
    public function test_successful_ajax_call(): void {
        $assignment_type = track_assignment_type::ADMIN;
        [$track, $args, ] = $this->setup_env(['type' => $assignment_type]);

        $this->assertEmpty($track->assignments->all(), 'track has assignments');

        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);

        $updated_track = $this->get_webapi_operation_data($result);
        $this->assertNotNull($updated_track, 'track creation failed');
        $this->assertEquals($track->id, $updated_track['id'], 'wrong track returned');

        $expected = [];
        foreach ($args['assignments']['groups'] as $group) {
            $expected[$group['id']] = $group['type'];
        }

        $updated_assignments = $updated_track['assignments'];
        $this->assertCount(count($expected), $updated_assignments, 'track has no assignments');

        foreach ($updated_assignments as $assignment) {
            $this->assertEquals($assignment_type, $assignment['type'], 'wrong track type');

            $group = $assignment['group'];
            $group_id = $group['id'];
            $expected_group_type = $expected[$group_id] ?? null;

            $this->assertNotNull($expected_group_type, "unknown group id '$group_id'");
            $this->assertEquals($expected_group_type, $group['type'], 'wrong group type');

            $expected_size = $expected_group_type === grouping::USER ? 1 : 5;
            $this->assertEquals($expected_size, $group['size'], 'wrong group size');
        }
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

        $cohort = $this->create_grouping(grouping::COHORT);
        $org = $this->create_grouping(grouping::ORG);
        $pos = $this->create_grouping(grouping::POS);
        $user = $this->create_grouping(grouping::USER);

        $track_details['track_id'] = $track->id;
        $track_details['groups'] = [$cohort, $org, $pos, $user];

        $args = ['assignments' => $track_details];

        $context = $this->create_webapi_context(self::MUTATION);
        $context->set_relevant_context($activity->get_context());

        return [$track, $args, $context];
    }

    /**
     * Generates a test grouping.
     *
     * @param string|null $type grouping type. One of the grouping enums.
     *
     * @return array (id, type) tuple.
     */
    private function create_grouping(?string $type=null): array {
        $generator = $this->getDataGenerator();
        $hierarchies = $generator->get_plugin_generator('totara_hierarchy');

        $group_users = [];
        for ($i = 0; $i < 5; $i++) {
            $group_users[] = $generator->create_user()->id;
        }

        $grouping = null;
        switch ($type) {
            case grouping::COHORT:
                $cohort = $generator->create_cohort([
                    'name' => 'My testing cohort'
                ])->id;

                foreach ($group_users as $user) {
                    cohort_add_member($cohort, $user);
                }

                $grouping = grouping::cohort($cohort);
                break;

            case grouping::ORG:
                $org = $hierarchies->create_org([
                    'frameworkid' => $hierarchies->create_org_frame([])->id,
                    'shortname' => 'My short org name',
                    'fullname' => 'My really long org name'
                ])->id;

                foreach ($group_users as $user) {
                    job_assignment::create([
                        'userid' => $user,
                        'idnumber' => "$user",
                        'organisationid' => $org
                    ]);
                }

                $grouping = grouping::org($org);
                break;

            case grouping::POS:
                $pos = $hierarchies->create_pos([
                    'frameworkid' => $hierarchies->create_pos_frame([])->id,
                    'shortname' => 'My short pos name',
                    'fullname' => 'My really long pos name'
                ])->id;

                foreach ($group_users as $user) {
                    job_assignment::create([
                        'userid' => $user,
                        'idnumber' => "$user",
                        'positionid' => $pos
                    ]);
                }

                $grouping = grouping::pos($pos);
                break;

            default:
                $user = $generator->create_user([
                    'firstname' => 'Tester',
                    'middlename' => 'Number',
                    'lastname' => 'Two'
                ])->id;

                $grouping = grouping::user($user);
        }

        return ['id' => $grouping->get_id(), 'type' => $grouping->get_type()];
    }
}
