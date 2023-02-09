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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package container_workspace
 */
defined('MOODLE_INTERNAL') || die();

use totara_webapi\phpunit\webapi_phpunit_helper;
use container_workspace\query\workspace\sort;
use container_workspace\query\workspace\source;
use container_workspace\query\workspace\access;

class container_workspace_webapi_find_workspaces_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_find_workspaces(): void {
        $generator = $this->getDataGenerator();

        $user1 = $generator->create_user();
        $user2 = $generator->create_user();

        $this->setUser($user1);
        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace1 = $workspace_generator->create_hidden_workspace('hidden by user1');
        $workspace2 = $workspace_generator->create_private_workspace('private by user1');
        $workspace3 = $workspace_generator->create_workspace('public by user1');

        $this->setUser($user2);
        $workspace4 = $workspace_generator->create_hidden_workspace('hidden by user2');
        $workspace5 = $workspace_generator->create_workspace('public by user2');

        $this->setAdminUser();
        $workspace6 = $workspace_generator->create_hidden_workspace('hidden by admin');

        // Fetching as admin users.
        $admin_fetched_workspaces = $this->resolve_graphql_query(
            'container_workspace_workspaces',
            [
                'source' => source::get_code(source::ALL),
                'sort' => sort::get_code(sort::ALPHABET),
            ]
        );

        $this->assertNotEmpty($admin_fetched_workspaces);
        $this->assertCount(6, $admin_fetched_workspaces);

        $admin_fetched_workspace_names = array_column($admin_fetched_workspaces, 'fullname');
        $admin_expected_workspace_names = [
            $workspace1->get_name(),
            $workspace2->get_name(),
            $workspace3->get_name(),
            $workspace4->get_name(),
            $workspace5->get_name(),
            $workspace6->get_name(),
        ];

        sort($admin_expected_workspace_names);
        sort($admin_fetched_workspace_names);

        $this->assertEquals($admin_expected_workspace_names, $admin_fetched_workspace_names);

        // Fetching as user one.
        $this->setUser($user1);
        $user1_fetched_workpsaces = $this->resolve_graphql_query(
            'container_workspace_workspaces',
            [
                'source' => source::get_code(source::ALL),
                'sort' => sort::get_code(sort::ALPHABET)
            ]
        );

        $this->assertNotEmpty($user1_fetched_workpsaces);
        $this->assertCount(4, $user1_fetched_workpsaces);

        $user1_fetched_workspace_ids = array_map(
            function (\container_workspace\workspace $workspace): int {
                return $workspace->get_id();
            },
            $user1_fetched_workpsaces
        );
        $this->assertNotContainsEquals($workspace4->get_id(), $user1_fetched_workspace_ids);
        $this->assertNotContainsEquals($workspace6->get_id(), $user1_fetched_workspace_ids);

        // Fetching as user 2
        $this->setUser($user2);
        $user2_fetched_workspaces = $this->resolve_graphql_query(
            'container_workspace_workspaces',
            [
                'source' => source::get_code(source::ALL),
                'sort' => sort::get_code(sort::ALPHABET)
            ]
        );
        $this->assertNotEmpty($user2_fetched_workspaces);
        $this->assertCount(4, $user2_fetched_workspaces);

        $user2_fetched_workspace_ids = array_map(
            function (\container_workspace\workspace $workspace): int {
                return $workspace->get_id();
            },
            $user2_fetched_workspaces
        );

        $this->assertNotContainsEquals($workspace1->get_id(), $user2_fetched_workspace_ids);
        $this->assertNotContainsEquals($workspace6->get_id(), $user2_fetched_workspace_ids);
    }

    /**
     * @return void
     */
    public function test_find_workspaces_with_target_user(): void {
        $generator = $this->getDataGenerator();

        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $user3 = $generator->create_user();

        $this->setUser($user1);
        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace_generator->create_hidden_workspace();
        $workspace_generator->create_private_workspace();
        $workspace_generator->create_workspace();

        $this->setUser($user3);
        $workspace1 = $workspace_generator->create_workspace();
        $workspace2 = $workspace_generator->create_workspace();
        $workspace3 = $workspace_generator->create_workspace();

        $this->setUser($user2);
        $workspace_generator->create_hidden_workspace();

        // Fetching user one workspaces with user two as actor.
        $user1_result = $this->resolve_graphql_query(
            'container_workspace_workspaces',
            [
                'user_id' => $user1->id,
                'source' => source::get_code(source::ALL),
                'sort' => sort::get_code(sort::ALPHABET)
            ]
        );

        $this->assertNotEmpty($user1_result);

        // All other workspaces that user two can see and the public workspace that was created by user one.
        $this->assertCount(4, $user1_result);

        // Fetching user three workspaces with user two as actor.
        $user3_result = $this->resolve_graphql_query(
            'container_workspace_workspaces',
            [
                'user_id' => $user3->id,
                'source' => source::get_code(source::OWNED),
                'sort' => sort::get_code(sort::ALPHABET)
            ]
        );

        $this->assertNotEmpty($user3_result);
        $this->assertCount(3, $user3_result);

        $user3_owned_workspace_ids = array_map(
            function (\container_workspace\workspace $workspace): int {
                return $workspace->get_id();
            },
            $user3_result
        );
        $this->assertContains($workspace1->get_id(), $user3_owned_workspace_ids);
        $this->assertContains($workspace2->get_id(), $user3_owned_workspace_ids);
        $this->assertContains($workspace3->get_id(), $user3_owned_workspace_ids);
    }

    /**
     * @return void
     */
    public function test_find_workspaces_with_access_set_as_non_public(): void {
        $generator = $this->getDataGenerator();

        $user1 = $generator->create_user();
        $user2 = $generator->create_user();

        $this->setUser($user1);
        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace_generator->create_hidden_workspace();
        $workspace_generator->create_private_workspace();
        $workspace_generator->create_workspace();

        $this->setUser($user2);

        $user1_result = $this->resolve_graphql_query(
            'container_workspace_workspaces',
            [
                'user_id' => $user1->id,
                'source' => source::get_code(source::ALL),
                'sort' => sort::get_code(sort::ALPHABET),
                'access' => access::get_code(access::PRIVATE)
            ]
        );

        $this->assertCount(1, $user1_result);
        $this->assertDebuggingCalled(
            ["You are not allowed to fetch target user's non public workspaces, access is reset"]
        );
    }
}