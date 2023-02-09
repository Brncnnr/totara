<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @package container_workspace
 */

use core\entity\user;
use totara_core\advanced_feature;
use totara_core\data_provider\provider;
use totara_tenant\testing\generator as tenant_generator;
use totara_webapi\phpunit\webapi_phpunit_helper;

require_once(__DIR__.'/non_members_testcase.php');

/**
 * @coversDefaultClass \container_workspace\webapi\resolver\query\non_members
 *
 * @group container_workspace
 */
class container_workspace_query_non_members_testcase extends container_workspace_non_members_testcase {
    private const QUERY = 'container_workspace_non_members';

    use webapi_phpunit_helper;

    /**
     * Data provider for multiple tests.
     */
    public function td_public_private(): array {
        return [
            'public workspace' => [false],
            'private workspace' => [true]
        ];
    }

    /**
     * @covers ::resolve
     *
     * @dataProvider td_public_private
     */
    public function test_default_params(bool $is_private): void {
        // Admin is also a non member; so additional member count must be one
        // less to prevent a result with multiple pages.
        $non_member_count = provider::DEFAULT_PAGE_SIZE - 1;
        $workspace = $this->create_workspace($non_member_count, 3, $is_private);

        $test_users = [
            $workspace->owner,
            $workspace->member,
            $workspace->admin
        ];

        $args = [
            'query' => [
                'filters' => ['workspace_id' => $workspace->id]
            ]
        ];

        $expected = $workspace->non_members->pluck('id');
        $expected_count = count($expected);

        foreach ($test_users as $logged_in_user) {
            $this->setUser($logged_in_user);

            $result = $this->resolve_graphql_query(self::QUERY, $args);
            $this->assert_result($result, $expected, $expected_count, false);
        }
        $this->assertDebuggingCalledCount(2);
    }

    /**
     * @covers ::resolve
     */
    public function test_non_member_public_workspace_authorisation(): void {
        $public = $this->create_workspace();

        $args = [
            'query' => [
                'filters' => ['workspace_id' => $public->id]
            ]
        ];

        $expected = $public->non_members->pluck('id');
        $expected_count = count($expected);

        $this->setUser($public->non_member);
        $result = $this->resolve_graphql_query(self::QUERY, $args);

        // Non members can access public workspace.
        $this->assert_result($result, $expected, $expected_count, false);
        $this->assertDebuggingCalledCount(2);
    }

    /**
     * @covers ::resolve
     */
    public function test_non_member_private_workspace_authorisation(): void {
        $public = $this->create_workspace();
        $private = $this->create_workspace(5, 3, true);

        $args = [
            'query' => [
                'filters' => ['workspace_id' => $private->id]
            ]
        ];

        // Non members cannot access private workspace.
        $this->assert_authorisation_failed($public->member, $args);
        $this->assertDebuggingCalledCount(4);
    }

    /**
     * @covers ::resolve
     */
    public function test_filters(): void {
        $workspace = $this->create_workspace();

        $admin_id = (int)get_admin()->id;
        $non_member_ids_wo_admin = $workspace->non_members
            ->filter(function (user $user) use ($admin_id): bool {
                return (int)$user->id !== $admin_id;
            });

        $first_non_member = $non_member_ids_wo_admin->first();
        $first_non_member_name = $first_non_member->lastname;
        $first_non_member_id = (int)$first_non_member->id;

        // Filter by id.
        $ids = [$first_non_member_id];
        $args = [
            'query' => [
                'filters' => [
                    'workspace_id' => $workspace->id,
                    'core_filters' => ['ids' => $ids]
                ]
            ]
        ];

        $result = $this->resolve_graphql_query(self::QUERY, $args);
        $this->assert_result($result, $ids, 1, false);

        // Filter by name.
        $args = [
            'query' => [
                'filters' => [
                    'workspace_id' => $workspace->id,
                    'core_filters' => ['name' => $first_non_member_name]
                ]
            ]
        ];

        $result = $this->resolve_graphql_query(self::QUERY, $args);
        $this->assert_result($result, $ids, 1, false);

        // Filter combination.
        $args = [
            'query' => [
                'filters' => [
                    'workspace_id' => $workspace->id,
                    'core_filters' => [
                        'name' => $first_non_member_name,
                        'ids' => $ids
                    ]
                ]
            ]
        ];

        $result = $this->resolve_graphql_query(self::QUERY, $args);
        $this->assert_result($result, $ids, 1, false);

        // Filter no result combination.
        $args = [
            'query' => [
                'filters' => [
                    'workspace_id' => $workspace->id,
                    'core_filters' => [
                        'name' => 'does not exist',
                        'ids' => $ids
                    ]
                ]
            ]
        ];

        $result = $this->resolve_graphql_query(self::QUERY, $args);
        $this->assert_result($result, [], 0, false);
        $this->assertDebuggingCalledCount(2);
    }

    /**
     * @covers ::resolve
     */
    public function test_sorted_pagination(): void {
        $page_size = 7;
        $non_member_count = $page_size * 2; // Exclude admin; so 3 pages in total.
        $workspace = $this->create_workspace($non_member_count);

        $order_direction = 'desc';
        $non_member_ids = $workspace->non_members // not multitenancy, so includes admin
            ->sort('id', $order_direction)
            ->pluck('id');
        $non_member_count = count($non_member_ids);

        $args = [
            'query' => [
                'filters' => ['workspace_id' => $workspace->id],
                'order_by' => 'id',
                'order_dir' => $order_direction,
                'result_size' => $page_size,
                'cursor' => null
            ]
        ];

        // 1st round.
        $results = $this->resolve_graphql_query(self::QUERY, $args);
        $this->assert_result(
            $results,
            array_slice($non_member_ids, 0, $page_size),
            $non_member_count,
            true
        );

        // 2nd round.
        $args['query']['cursor'] = $results['next_cursor'];
        $results = $this->resolve_graphql_query(self::QUERY, $args);
        $this->assert_result(
            $results,
            array_slice($non_member_ids, $page_size, $page_size),
            $non_member_count,
            true
        );

        // 3rd round.
        $args['query']['cursor'] = $results['next_cursor'];
        $results = $this->resolve_graphql_query(self::QUERY, $args);
        $this->assert_result(
            $results,
            array_slice($non_member_ids, $page_size * 2),
            $non_member_count,
            false
        );
        $this->assertDebuggingCalledCount(2);
    }

    /**
     * @covers ::resolve
     *
     * @dataProvider td_public_private
     */
    public function test_multitenancy_tenant_workspace(bool $is_private): void {
        // Tenant 1 users.
        $t1_workspace = $this->create_workspace(5, 3, $is_private, true);
        $t1_members = $t1_workspace->members;
        $t1_non_member_ids = $t1_workspace->non_members->pluck('id');

        $t1_tenant_id = $t1_workspace->tenant_id;
        $t1_tm = $this->create_tenant_mgr($t1_tenant_id, $t1_members->item(1));
        $t1_participants = $this->create_tenant_participants($t1_tenant_id, 3);
        $t1_participant_ids = array_column($t1_participants, 'id');

        // Tenant 2 users.
        $t2_workspace = $this->create_workspace(2, 4, $is_private, true);
        $t2_members = $t2_workspace->members;
        $t2_non_member_ids = $t2_workspace->non_members->pluck('id');

        $t2_tenant_id = $t2_workspace->tenant_id;
        $t2_tm = $this->create_tenant_mgr($t2_tenant_id, $t2_members->item(1));
        $t2_participants = $this->create_tenant_participants($t2_tenant_id, 3);
        $t2_participant_ids = array_column($t2_participants, 'id');

        // System users.
        $admin = $t2_workspace->admin;

        $tenancies = [
            (object) [
                'workspace_id' => $t1_workspace->id,
                'expected' => array_merge(
                    $t1_non_member_ids,
                    $t1_participant_ids
                ), // multitenancy, so excludes admin and system users
                'users' => [
                    $t1_workspace->owner,
                    $t1_members->item(0),
                    $t1_tm,
                    $admin // non member but can see private workspace membership
                ]
            ],
            (object) [
                'workspace_id' => $t2_workspace->id,
                'expected' => array_merge(
                    $t2_non_member_ids,
                    $t2_participant_ids
                ),
                'users' => [
                    $t2_workspace->owner,
                    $t2_members->item(0),
                    $t2_tm,
                    $admin
                ]
            ]
        ];

        foreach ($tenancies as $test) {
            $expected = $test->expected;
            $expected_count = count($expected);

            foreach ($test->users as $logged_in_user) {
                $this->setUser($logged_in_user);

                $args = [
                    'query' => [
                        'filters' => ['workspace_id' => $test->workspace_id]
                    ]
                ];

                $results = $this->resolve_graphql_query(self::QUERY, $args);
                $this->assert_result($results, $expected, $expected_count, false);
            }
        }
        $this->assertDebuggingCalledCount(4);
    }

    /**
     * @covers ::resolve
     */
    public function test_member_moved_to_other_tenant(): void {
        $private = $this->create_workspace(2, 1, false, true);
        $private_tenant_id = $private->member->tenantid;

        $public = $this->create_workspace(1, 2, true, true);
        $public_member = $public->member;
        $public_non_members = $public->non_members->pluck('id');

        $args = [
            'query' => [
                'filters' => ['workspace_id' => $public->id]
            ]
        ];

        // Member can see non members in original tenant.
        $this->setUser($public_member);
        $results = $this->resolve_graphql_query(self::QUERY, $args);
        $this->assert_result(
            $results, $public_non_members, count($public_non_members), false
        );

        // Moved member cannot see original tenant non members.
        tenant_generator::instance()->migrate_user_to_tenant(
            $public_member->id, $private_tenant_id
        );
        $public_member->tenantid = $private_tenant_id;
        $this->assert_authorisation_failed($public_member, $args);
        $this->assertDebuggingCalledCount(4);
    }

    /**
     * @covers ::resolve
     */
    public function test_ajax_default_params(): void {
        $workspace = $this->create_workspace();
        $non_member_ids = $workspace->non_members->pluck('id');

        $args = [
            'query' => [
                'filters' => ['workspace_id' => $workspace->id]
            ]
        ];

        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_successful($result);
        $this->assert_result(
            $this->get_webapi_operation_data($result),
            $non_member_ids,
            count($non_member_ids),
            false
        );
        $this->assertDebuggingCalledCount(2);
    }

    /**
     * @covers ::resolve
     */
    public function test_failed_ajax_query(): void {
        $workspace = $this->create_workspace();

        $valid_args = [
            'query' => [
                'filters' => ['workspace_id' => $workspace->id]
            ]
        ];

        $feature = 'container_workspace';
        advanced_feature::disable($feature);
        $result = $this->parsed_graphql_operation(self::QUERY, $valid_args);
        $this->assert_webapi_operation_failed($result, "Feature $feature is not available.");
        advanced_feature::enable($feature);

        $result = $this->parsed_graphql_operation(self::QUERY, []);
        $this->assert_webapi_operation_failed(
            $result, 'Variable "$query" of required type "container_workspace_user_query!" was not provided.'
        );

        $args = [
            'query' => [
                'filters' => [
                    'core_filters' => ['name' => 'does not exist']
                ]
            ]
        ];
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_failed(
            $result, 'Field value.filters.workspace_id of required type core_id! was not provided.'
        );

        $args = [
            'query' => [
                'filters' => ['workspace_id' => 1293]
            ]
        ];
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_failed($result, 'Can not find data record in database.');

        self::setGuestUser();
        $result = $this->parsed_graphql_operation(self::QUERY, $valid_args);
        $this->assert_webapi_operation_failed($result, 'Must be an authenticated user');

        $this->setUser();
        $result = $this->parsed_graphql_operation(self::QUERY, $valid_args);
        $this->assert_webapi_operation_failed($result, 'You are not logged in');
        $this->assertDebuggingCalledCount(2);
    }

    /**
     * Validates if an authorisation error occurs.
     *
     * @param user $logged_in_user user to masquerade as.
     * @param array $args execution arguments.
     */
    private function assert_authorisation_failed(
        user $logged_in_user,
        array $args
    ): void {
        $this->setUser($logged_in_user);

        try {
            $this->resolve_graphql_query(self::QUERY, $args);
        } catch (moodle_exception $exception) {
            $this->assertInstanceOf(moodle_exception::class, $exception);
            $this->assertEquals("You don't have permission to view this page.", $exception->getMessage());
        }
    }

    /**
     * Validates results.
     *
     * @param array $results the result from the graphql call.
     * @param int[] $expected_ids expected user ids in result.
     * @param int $expected_total total number of items retrieved.
     * @param bool $expected_cursor if true, then there should be cursor in the
     *        returned results.
     */
    private function assert_result(
        array $results,
        array $expected_ids,
        int $expected_total,
        bool $expect_cursor
    ): void {
        ['items' => $items, 'total' => $total, 'next_cursor' => $cursor] = $results;

        $expect_cursor
            ? $this->assertNotEmpty($cursor, 'empty cursor')
            : $this->assertEmpty($cursor, 'non empty cursor');

        $this->assertEquals($expected_total, $total, 'wrong total count');
        $this->assertCount(count($expected_ids), $items, 'wrong current page count');
        $this->assertEqualsCanonicalizing(
            $expected_ids, array_column($items, 'id'), 'wrong retrievals'
        );
    }
}
