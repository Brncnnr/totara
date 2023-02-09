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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package mod_perform
 */

use mod_perform\entity\activity\activity_type;
use mod_perform\state\activity\active;
use totara_core\advanced_feature;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass \mod_perform\webapi\resolver\query\paginated_activities
 *
 * @group perform
 */
class mod_perform_webapi_resolver_query_paginated_activities_testcase extends advanced_testcase {
    private const QUERY = 'mod_perform_paginated_activities';

    use webapi_phpunit_helper;

    public function test_get_paginated_activities_with_filters_and_sorting(): void {
        $activities = [
            [
                'activity_name' => 'Team morale survey',
                'activity_type' => 'check-in',
                'activity_status' => 'ACTIVE',
                'created_at' => '2018-01-01',
            ],
            [
                'activity_name' => 'End year performance',
                'activity_type' => 'feedback',
                'activity_status' => 'ACTIVE',
                'created_at' => '2019-01-01',
            ],
            [
                'activity_name' => "Now that's what I call an appraisal!",
                'activity_type' => 'appraisal',
                'activity_status' => 'DRAFT',
                'created_at' => '2020-01-01',
            ],
            [
                'activity_name' => 'Confidential feedback',
                'activity_type' => 'feedback',
                'activity_status' => 'DRAFT',
                'created_at' => '2021-01-01',
            ],
        ];
        $this->create_test_data($activities);


        // Test without filters applied
        $result = $this->resolve_graphql_query(self::QUERY, $this->get_query_options());
        // Default sorting is created_by, with newer activities first.
        $this->assertEquals(
            array_reverse(
                array_column($activities, 'activity_name')
            ),
            array_column($result['items'], 'name')
        );


        // Test with sort_by = name, with pagination
        $result = $this->resolve_graphql_query(self::QUERY, $this->get_query_options(null, 3, null, 'name'));
        $this->assertEquals(
            [
                'Confidential feedback',
                'End year performance',
                "Now that's what I call an appraisal!",
            ],
            array_column($result['items'], 'name')
        );
        // next page
        $result = $this->resolve_graphql_query(self::QUERY, $this->get_query_options($result['next_cursor'], null, null, 'name'));
        $this->assertEquals(
            ['Team morale survey'],
            array_column($result['items'], 'name')
        );


        // Filter by type, with pagination
        $result = $this->resolve_graphql_query(self::QUERY, $this->get_query_options(null, 1, [
            'type' => activity_type::repository()->where('name', 'feedback')->one()->id,
        ]));
        $this->assertEquals(
            ['Confidential feedback'],
            array_column($result['items'], 'name')
        );
        // next page
        $result = $this->resolve_graphql_query(self::QUERY, $this->get_query_options($result['next_cursor'], null, [
            'type' => activity_type::repository()->where('name', 'feedback')->one()->id,
        ]));
        $this->assertEquals(
            ['End year performance'],
            array_column($result['items'], 'name')
        );


        // Filter by status, with pagination & sorting
        $result = $this->resolve_graphql_query(self::QUERY, $this->get_query_options(null, 1, [
            'status' => active::get_name(),
        ], 'name'));
        $this->assertEquals(
            ['End year performance'],
            array_column($result['items'], 'name')
        );
        // next page.
        $result = $this->resolve_graphql_query(self::QUERY, $this->get_query_options($result['next_cursor'], null, [
            'status' => active::get_name(),
        ], 'name'));
        $this->assertEquals(
            ['Team morale survey'],
            array_column($result['items'], 'name')
        );


        // Filter by name (case insensitive)
        $result = $this->resolve_graphql_query(self::QUERY, $this->get_query_options(null, null, [
            'name' => "That's",
        ]));
        $this->assertEquals(
            ["Now that's what I call an appraisal!"],
            array_column($result['items'], 'name')
        );


        // Multiple filters
        $result = $this->resolve_graphql_query(self::QUERY, $this->get_query_options(null, null, [
            'status' => active::get_name(),
            'name' => "That's",
        ]));
        $this->assertEmpty($result['items']);
    }

    public function test_user_needs_view_manage_activities_capability() {
        global $DB;

        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);

        $user_role = $DB->get_record('role', ['shortname' => 'user']);
        assign_capability('mod/perform:view_manage_activities', CAP_ALLOW, $user_role->id, SYSCONTEXTID, true);

        /** @var array $activities */
        $activities = $this->resolve_graphql_query(self::QUERY, $this->get_query_options(null));

        // There are not activities but also no exceptions
        $this->assertEmpty($activities['items']);

        unassign_capability('mod/perform:view_manage_activities', $user_role->id, SYSCONTEXTID);
        $result = $this->parsed_graphql_operation(
            self::QUERY,
            [
                'query_options_input' => $this->get_query_options()['query_options']
            ]
        );
        $this->assert_webapi_operation_failed($result, '(Access the performance activities management interface)');
    }

    public function test_users_can_view_reports_or_manage_other_users_activities() {
        global $DB;

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);

        $activities = [
            ['activity_name' => 'Mid year performance'],
            ['activity_name' => 'End year performance'],
        ];
        $this->create_test_data($activities);

        $user_role = $DB->get_record('role', ['shortname' => 'user']);
        assign_capability('mod/perform:view_manage_activities', CAP_ALLOW, $user_role->id, SYSCONTEXTID, true);

        /** @var array $activities */
        $result = $this->resolve_graphql_query(self::QUERY, $this->get_query_options());

        $this->assertCount(count($activities), $result['items']);
        // Users who created activities can always manage and view reporting
        foreach ($result['items'] as $activity) {
            $this->assertTrue($activity->can_manage());
            $this->assertTrue($activity->can_view_participation_reporting());
        }

        $this->setUser($user2);

        $result = $this->resolve_graphql_query(self::QUERY, $this->get_query_options());
        $this->assertEmpty($result['items']);

        // Let the other user manage only
        assign_capability('mod/perform:manage_activity', CAP_ALLOW, $user_role->id, SYSCONTEXTID, true);

        $result = $this->resolve_graphql_query(self::QUERY, $this->get_query_options());

        foreach ($result['items'] as $activity) {
            $this->assertTrue($activity->can_manage());
            $this->assertFalse($activity->can_view_participation_reporting());
        }

        // Let the user also see view the reports
        assign_capability('mod/perform:view_participation_reporting', CAP_ALLOW, $user_role->id, SYSCONTEXTID, true);

        $result = $this->resolve_graphql_query(self::QUERY, $this->get_query_options());

        foreach ($result['items'] as $activity) {
            $this->assertTrue($activity->can_manage());
            $this->assertTrue($activity->can_view_participation_reporting());
        }

        // Let the user just view reports
        unassign_capability('mod/perform:manage_activity', $user_role->id, SYSCONTEXTID);

        foreach ($result['items'] as $activity) {
            $this->assertFalse($activity->can_manage());
            $this->assertTrue($activity->can_view_participation_reporting());
        }
    }

    public function test_get_empty_activities() {
        $this->setAdminUser();

        $activities = $this->resolve_graphql_query(self::QUERY, $this->get_query_options());

        $this->assertCount(0, $activities['items']);
    }

    public function test_get_activities_non_admin() {
        $this->setGuestUser();

        $this->expectException(required_capability_exception::class);

        $this->resolve_graphql_query(self::QUERY, $this->get_query_options());
    }

    public function test_ajax_query() {
        $activities = [
            ['activity_name' => 'Mid year performance'],
            ['activity_name' => 'End year performance'],
        ];
        $this->create_test_data($activities);

        $result = $this->parsed_graphql_operation(self::QUERY,
            [
                'query_options_input' => $this->get_query_options()['query_options']
            ]
        );
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertCount(count($activities), $result['items'], 'wrong count');
        $this->assertEmpty($result['next_cursor']);
        $this->assertEquals(count($activities), $result['total']);
    }

    public function test_failed_ajax_query(): void {
        $feature = 'performance_activities';
        advanced_feature::disable($feature);
        $result = $this->parsed_graphql_operation(self::QUERY,
            [
                'query_options_input' => $this->get_query_options()['query_options']
            ]
        );
        $this->assert_webapi_operation_failed($result, 'Feature performance_activities is not available.');
        advanced_feature::enable($feature);
    }

    private function get_query_options($cursor = null, $limit = 10, $filters = null, $sort_by = null): array {
        $options = [
            'query_options' => [
                'pagination' => [
                    'limit' => $limit,
                    'cursor' => $cursor,
                ],
            ]
        ];
        if (isset($filters)) {
            $options['query_options']['filters'] = $filters;
        }
        if (isset($sort_by)) {
            $options['query_options']['sort_by'] = $sort_by;
        }
        return $options;
    }

    private function create_test_data(array $activities) {
        $this->setAdminUser();

        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();
        foreach ($activities as $activity) {
            $perform_generator->create_activity_in_container($activity);
        }
    }
}
