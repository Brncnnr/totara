<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package container_workspace
 */
defined('MOODLE_INTERNAL') || die();

use container_workspace\member\member;
use totara_webapi\phpunit\webapi_phpunit_helper;
use container_workspace\query\member\sort as member_sort;
use core_user\profile\display_setting;
use container_workspace\exception\workspace_exception;
use container_workspace\loader\member\loader as member_loader;

class container_workspace_webapi_member_loader_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * We will have to set the email for profile card in order to produce the issue.
     * This test is to check whether the process is respect the email display setting or not.
     *
     * @return void
     */
    public function test_execute_operation_load_members_of_private_workspace_as_member(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();
        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        // Add member to this workspace.
        $members = [];
        for ($i = 0; $i < 5; $i++) {
            $member_user = $generator->create_user();
            $member = member::added_to_workspace($workspace, $member_user->id);

            $members[] = $member;
        }

        // Set the mini profile card to diplay email.
        display_setting::save_display_fields(['email', 'fullname']);

        // Now log in as any member of the workspace and try to fetch the list of members
        // via graphql - with full operation.
        /** @var member $first_member */
        $first_member = reset($members);
        $this->setUser($first_member->get_user_record());

        // Fetch members.
        $workspace_id = $workspace->get_id();
        $result = $this->execute_graphql_operation(
            'container_workspace_find_members',
            [
                'workspace_id' => $workspace_id,
                'sort' => member_sort::get_code(member_sort::RECENT_JOIN)
            ]
        );

        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);

        $this->assertArrayHasKey('members', $result->data);
        $this->assertArrayHasKey('cursor', $result->data);

        // All the members plus the owner.
        $this->assertCount(count($members) + 1, $result->data['members']);

        $member_ids = array_map(
            function (member $member): int {
                return $member->get_id();
            },
            $members
        );

        // Add the owner member.
        $owner_member = member_loader::get_for_user($user_one->id, $workspace_id);
        $member_ids[] = $owner_member->get_id();

        $member_result = $result->data['members'];
        foreach ($member_result as $single_member_result) {
            $this->assertArrayHasKey('id', $single_member_result);
            $this->assertArrayHasKey('workspace_id', $single_member_result);

            $this->assertEquals($workspace_id, $single_member_result['workspace_id']);
            $this->assertTrue(in_array($single_member_result['id'], $member_ids));
        }
    }

    /**
     * @return void
     */
    public function test_load_members_of_a_private_workspace_as_non_member(): void {
        $generator = $this->getDataGenerator();
        $this->setAdminUser();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        // Add list of members to the workspaces.
        for ($i = 0; $i < 5; $i++) {
            $user = $generator->create_user();
            member::added_to_workspace($workspace, $user->id, false);
        }

        // Log in as normal user and try to fetch the members of workspace.
        $user_one = $generator->create_user();
        $this->setUser($user_one);

        $this->expectException(workspace_exception::class);
        $this->expectExceptionMessage("You don't have permission to view this page.");

        $this->resolve_graphql_query(
            'container_workspace_members',
            [
                'workspace_id' => $workspace->get_id(),
                'sort' => member_sort::get_code(member_sort::RECENT_JOIN)
            ]
        );
    }

    /**
     * @return void
     */
    public function test_load_members_of_a_hidden_workspace_as_non_member(): void {
        $generator = $this->getDataGenerator();
        $this->setAdminUser();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_hidden_workspace();

        // Add list of members to the workspaces.
        for ($i = 0; $i < 5; $i++) {
            $user = $generator->create_user();
            member::added_to_workspace($workspace, $user->id, false);
        }

        // Log in as normal user and try to fetch the members of workspace.
        $user_one = $generator->create_user();
        $this->setUser($user_one);

        $this->expectException(workspace_exception::class);
        $this->expectExceptionMessage("You don't have permission to view this page.");

        $this->resolve_graphql_query(
            'container_workspace_members',
            [
                'workspace_id' => $workspace->get_id(),
                'sort' => member_sort::get_code(member_sort::RECENT_JOIN)
            ]
        );
    }

    /**
     * @return void
     */
    public function test_load_members_of_a_public_workspace_as_non_member(): void {
        $generator = $this->getDataGenerator();
        $this->setAdminUser();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Add list of members to the workspaces.
        for ($i = 0; $i < 5; $i++) {
            $user = $generator->create_user();
            member::added_to_workspace($workspace, $user->id, false);
        }

        // Log in as normal user and try to fetch the members of workspace.
        $user_one = $generator->create_user();
        $this->setUser($user_one);

        $result = $this->resolve_graphql_query(
            'container_workspace_members',
            [
                'workspace_id' => $workspace->get_id(),
                'sort' => member_sort::get_code(member_sort::RECENT_JOIN)
            ]
        );

        self::assertIsArray($result);

        // 6 because we are including the user admin as well as 5 other members.
        self::assertCount(6, $result);
    }

    /**
     * @return void
     */
    public function test_load_members_of_a_private_workspace_as_a_member(): void {
        $generator = $this->getDataGenerator();
        $this->setAdminUser();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        // Add list of members to the workspaces.
        for ($i = 0; $i < 3; $i++) {
            $user = $generator->create_user();
            member::added_to_workspace($workspace, $user->id, false);
        }

        // Log in as normal user and try to fetch the members of workspace.
        $user_one = $generator->create_user();

        // Added to the workspace by admin.
        member::added_to_workspace($workspace, $user_one->id, false);

        $this->setUser($user_one);
        $result = $this->resolve_graphql_query(
            'container_workspace_members',
            [
                'workspace_id' => $workspace->get_id(),
                'sort' => member_sort::get_code(member_sort::RECENT_JOIN)
            ]
        );

        self::assertIsArray($result);

        // 3 members + user admin and user one.
        self::assertCount(5, $result);
    }


    /**
     * @return void
     */
    public function test_load_members_of_a_hidden_workspace_as_a_member(): void {
        $generator = $this->getDataGenerator();
        $this->setAdminUser();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_hidden_workspace();

        // Add list of members to the workspaces.
        for ($i = 0; $i < 3; $i++) {
            $user = $generator->create_user();
            member::added_to_workspace($workspace, $user->id, false);
        }

        // Log in as normal user and try to fetch the members of workspace.
        $user_one = $generator->create_user();

        // Added to the workspace by admin.
        member::added_to_workspace($workspace, $user_one->id, false);

        $this->setUser($user_one);
        $result = $this->resolve_graphql_query(
            'container_workspace_members',
            [
                'workspace_id' => $workspace->get_id(),
                'sort' => member_sort::get_code(member_sort::RECENT_JOIN)
            ]
        );

        self::assertIsArray($result);

        // 3 members + user admin and user one.
        self::assertCount(5, $result);
    }
}