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

use container_workspace\data_providers\non_members;
use core\entity\user;
use core\pagination\cursor;

require_once(__DIR__.'/non_members_testcase.php');

/**
 * @coversDefaultClass \container_workspace\data_providers\non_members
 *
 * @group container_workspace
 */
class container_workspace_non_members_data_provider_testcase extends container_workspace_non_members_testcase {
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
     * @covers ::fetch_paginated
     *
     * @dataProvider td_public_private
     */
    public function test_default_params(bool $is_private): void {
        // Admin is also a non member; so additional member count must be one
        // less to prevent a result with multiple pages.
        $non_member_count = non_members::DEFAULT_PAGE_SIZE - 1;
        $workspace = $this->create_workspace($non_member_count, 3, $is_private);

        // The non_members data provider does not have any authorisation checks,
        // replicating the behavior of the original non_member_loader class. That
        // means all users see the same result when they are logged in.
        //
        // FYI authorisation checks execute at the graphql layer; only then will
        // there be an error when non members try to access a private workspace.
        $test_users = [
            $workspace->owner,
            $workspace->member,
            $workspace->admin,
            $workspace->non_member
        ];

        $workspace_id = $workspace->id;
        $expected = $workspace->non_members->pluck('id');
        $expected_count = count($expected);

        foreach ($test_users as $logged_in_user) {
            $this->setUser($logged_in_user);

            $result = non_members::create_for_workspace($workspace_id)
                ->fetch_paginated();

            $this->assert_result($result, $expected, $expected_count, false);
        }
        $this->assertDebuggingCalledCount(2);
    }

    /**
     * @covers ::fetch_paginated
     *
     * @dataProvider td_public_private
     */
    public function test_multiple_workspaces(bool $is_private): void {
        $workspace1 = $this->create_workspace(3, 2, $is_private);
        $workspace1_member_ids = $workspace1->members->pluck('id');
        $workspace1_non_member_ids = $workspace1->non_members->pluck('id');

        $workspace2 = $this->create_workspace(5, 6, $is_private);
        $workspace2_member_ids = $workspace2->members->pluck('id');
        $workspace2_non_member_ids = $workspace2->non_members->pluck('id');

        $workspaces = [
            [
                $workspace1->id,
                array_unique(
                    // unique() because all non members sets have admin
                    array_merge(
                        $workspace2_member_ids,
                        $workspace2_non_member_ids,
                        $workspace1_non_member_ids
                    )
                ),
                [
                    $workspace1->owner,
                    $workspace1->member,
                    $workspace1->admin,
                    $workspace1->non_member
                ]
            ],
            [
                $workspace2->id,
                array_unique(
                    array_merge(
                        $workspace1_member_ids,
                        $workspace1_non_member_ids,
                        $workspace2_non_member_ids
                    )
                ),
                [
                    $workspace2->owner,
                    $workspace2->member,
                    $workspace2->admin,
                    $workspace2->non_member
                ]
            ]
        ];

        foreach ($workspaces as $tuple) {
            [$workspace_id, $expected, $users] = $tuple;
            $expected_count = count($expected);

            foreach ($users as $logged_in_user) {
                $this->setUser($logged_in_user);

                $result = non_members::create_for_workspace($workspace_id)
                    ->set_page_size(100)
                    ->fetch_paginated();

                $this->assert_result($result, $expected, $expected_count, false);
            }
        }
        $this->assertDebuggingCalledCount(4);
    }

    /**
     * @covers ::set_filters
     * @covers ::fetch_paginated
     */
    public function test_filters(): void {
        $workspace = $this->create_workspace();

        $admin_id = (int)$workspace->admin->id;
        $non_members_wo_admin = $workspace->non_members
            ->filter(function (user $user) use ($admin_id): bool {
                return (int)$user->id !== $admin_id;
            });

        $first_non_member = $non_members_wo_admin->first();
        $first_non_member_name = $first_non_member->lastname;
        $first_non_member_id = (int)$first_non_member->id;

        $last_non_member = $non_members_wo_admin->last();
        $last_non_member_name = $last_non_member->lastname;
        $last_non_member_id = (int)$last_non_member->id;

        $provider = non_members::create_for_workspace($workspace->id)
            ->set_page_size(100);

        // Filter by single id value.
        $this->assert_result(
            $provider
                ->set_filters(['ids' => $first_non_member_id])
                ->fetch_paginated(),
            [$first_non_member_id],
            1,
            false
        );

        // Filter by multiple id value.
        $ids = [$first_non_member_id, $last_non_member_id];
        $this->assert_result(
            $provider
                ->set_filters(['ids' => $ids])
                ->fetch_paginated(),
            $ids,
            count($ids),
            false
        );

        // Filter by name.
        $this->assert_result(
            $provider
                ->set_filters(['name' => $first_non_member_name])
                ->fetch_paginated(),
            [$first_non_member_id],
            1,
            false
        );

        $this->assert_result(
            $provider
                ->set_filters(['name' => 'Test'])
                ->fetch_paginated(),
            $non_members_wo_admin->pluck('id'),
            count($non_members_wo_admin),
            false
        );

        // Filter combination.
        $this->assert_result(
            $provider
                ->set_filters([
                    'ids' => $first_non_member_id,
                    'name' => 'Test'
                ])
                ->fetch_paginated(),
            [$first_non_member_id],
            1,
            false
        );

        $this->assert_result(
            $provider
                ->set_filters([
                    'ids' => $ids,
                    'name' => $last_non_member_name
                ])
                ->fetch_paginated(),
            [$last_non_member_id],
            1,
            false
        );

        // Filter no result combination.
        $this->assert_result(
            $provider
                ->set_filters([
                    'ids' => $first_non_member_id,
                    'name' => $last_non_member_name
                ])
                ->fetch_paginated(),
            [],
            0,
            false
        );

        // Unknown filter.
        $key = 'unknown';
        try {
            $provider
                ->set_filters([$key => '#00'])
                ->fetch_paginated();
        } catch (coding_exception $exception) {
            $this->assertMatchesRegularExpression("/$key/", $exception->getMessage());
        }
        $this->assertDebuggingCalledCount(2);
    }


    /**
     * @covers ::set_filters
     * @covers ::set_page_size
     * @covers ::fetch_paginated
     */
    public function test_filters_empty_values(): void {
        $workspace = $this->create_workspace();

        $non_member_ids = $workspace->non_members->pluck('id');
        $non_member_count = count($non_member_ids);

        $provider = non_members::create_for_workspace($workspace->id)
            ->set_page_size($non_member_count);

        $this->assert_result(
            $provider
                ->set_filters([
                    'ids' => [],    // This is a valid filter to filter everything out.
                    'name' => '  ', // This filter is ignored.
                ])
                ->fetch_paginated(),
            [],
            0,
            false
        );

        $this->assert_result(
            $provider
                ->set_filters([
                    'ids' => null,  // Now this filter will be ignored.
                    'name' => null
                ])
                ->fetch_paginated(),
            $non_member_ids,        // not multitenancy, so includes admin
            $non_member_count,
            false
        );
        $this->assertDebuggingCalledCount(2);
    }

    /**
     * @covers ::set_order
     * @covers ::set_page_size
     * @covers ::fetch_paginated
     *
     */
    public function test_sorted_pagination(): void {
        $page_size = 7;
        $non_member_count = $page_size * 2; // Exclude admin; so 3 pages total.
        $workspace = $this->create_workspace($non_member_count);

        $order_direction = 'desc';
        $non_member_ids = $workspace->non_members // includes admin
            ->sort('id', $order_direction)
            ->pluck('id');

        $non_member_count = count($non_member_ids);

        $provider = non_members::create_for_workspace($workspace->id)
            ->set_page_size($page_size)
            ->set_order('id', $order_direction);

        // 1st round.
        $results = $provider->fetch_paginated();
        $this->assert_result(
            $results,
            array_slice($non_member_ids, 0, $page_size),
            $non_member_count,
            true
        );

        // 2nd round.
        $cursor = cursor::decode($results['next_cursor']);
        $results = $provider->fetch_paginated($cursor);

        $this->assert_result(
            $results,
            array_slice($non_member_ids, $page_size, $page_size),
            $non_member_count,
            true
        );

        // 3rd round.
        $cursor = cursor::decode($results['next_cursor']);
        $results = $provider->fetch_paginated($cursor);

        $this->assert_result(
            $results,
            array_slice($non_member_ids, $page_size * 2),
            $non_member_count,
            false
        );
        $this->assertDebuggingCalledCount(2);
    }

    /**
     * @covers ::fetch_paginated
     *
     * @dataProvider td_public_private
     */
    public function test_multitenancy_tenant_workspace(bool $is_private): void {
        // Tenant 1 users.
        $t1_workspace = $this->create_workspace(5, 3, $is_private, true);
        $t1_members = $t1_workspace->members;
        $t1_non_members = $t1_workspace->non_members;
        $t1_non_member_ids = $t1_workspace->non_members->pluck('id');

        $t1_tenant_id = $t1_workspace->tenant_id;
        $t1_tm_member = $this->create_tenant_mgr($t1_tenant_id, $t1_members->item(1));
        $t1_tm_non_member = $this->create_tenant_mgr($t1_tenant_id, $t1_non_members->item(1));
        $t1_participants = $this->create_tenant_participants($t1_tenant_id, 3);
        $t1_participant_ids = array_column($t1_participants, 'id');

        // Tenant 2 users.
        $t2_workspace = $this->create_workspace(2, 4, $is_private, true);
        $t2_members = $t2_workspace->members;
        $t2_non_members = $t2_workspace->non_members;
        $t2_non_member_ids = $t2_workspace->non_members->pluck('id');

        $t2_tenant_id = $t2_workspace->tenant_id;
        $t2_tm_member = $this->create_tenant_mgr($t2_tenant_id, $t1_members->item(1));
        $t2_tm_non_member = $this->create_tenant_mgr($t2_tenant_id, $t1_non_members->item(1));
        $t2_participants = $this->create_tenant_participants($t2_tenant_id, 3);
        $t2_participant_ids = array_column($t2_participants, 'id');

        // System users.
        $admin = $t2_workspace->admin;
        $sys_users = $this->create_sys_users(3);

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
                    $t1_tm_member,
                    $t1_non_members->item(0),
                    $t1_tm_non_member,
                    $t1_participants[0],
                    $admin,
                    $sys_users[0]
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
                    $t2_tm_member,
                    $t2_non_members->item(0),
                    $t2_tm_non_member,
                    $t2_participants[0],
                    $admin,
                    $sys_users[0]
                ]
            ]
        ];

        foreach ($tenancies as $test) {
            // Remember the non_members data provider replicates the behavior of
            // the original non_member_loader: no authorisation checks. So all
            // users see the same result when they are logged in.
            $expected = $test->expected;
            $expected_count = count($expected);

            foreach ($test->users as $logged_in_user) {
                $this->setUser($logged_in_user);

                $result = non_members::create_for_workspace($test->workspace_id)
                    ->set_page_size(100)
                    ->fetch_paginated();

                $this->assert_result($result, $expected, $expected_count, false);
            }
        }
        $this->assertDebuggingCalledCount(4);
    }

    /**
     * @covers ::fetch_paginated
     *
     * @dataProvider td_public_private
     */
    public function test_multitenancy_system_workspace(bool $is_private): void {
        $workspace = $this->create_workspace(1, 1, $is_private);

        $t1_users = $this->create_tenant_members(5);
        $t2_users = $this->create_tenant_members(2);
        $sys_users = $this->create_sys_users(2);
        $expected = array_map(
            function (user $user): int {
                return $user->id;
            },
            array_merge(
                $t1_users, $t2_users, $sys_users, $workspace->non_members->all()
            )
        );
        $expected_count = count($expected);

        $users = [
            $workspace->owner,
            $workspace->member,
            $workspace->non_member,
            $workspace->admin,
            $t1_users[0]->id,
            $t2_users[0]->id,
            $sys_users[0]->id
        ];

        foreach ($users as $logged_in_user) {
            $this->setUser($logged_in_user);

            $result = non_members::create_for_workspace($workspace->id)
                ->set_page_size(100)
                ->fetch_paginated();

            $this->assert_result($result, $expected, $expected_count, false);
        }
        $this->assertDebuggingCalledCount(2);
    }

    /**
     * Validates results.
     *
     * @param array $results the result from the provider->fetch_paginated() call.
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