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

use container_workspace\exception\enrol_exception;
use totara_webapi\phpunit\webapi_phpunit_helper;
use container_workspace\member\member;

class container_workspace_webapi_add_member_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_add_members_to_private_workspace(): void {
        $this->setAdminUser();
        $generator = $this->getDataGenerator();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        // Create 5 users and added them to a workspace above.
        $user_ids = [];

        for ($i = 0; $i < 5; $i++) {
            $user = $generator->create_user();
            $user_ids[] = $user->id;
        }

        // Start the operation.
        $workspace_id = $workspace->get_id();
        $members = $this->resolve_graphql_mutation(
            'container_workspace_add_members',
            [
                'workspace_id' => $workspace_id,
                'user_ids' => $user_ids
            ]
        );

        $this->assertCount(5, $members);

        /** @var member $member */
        foreach ($members as $member) {
            $this->assertInstanceOf(member::class, $member);
            $this->assertEquals($workspace_id, $member->get_workspace_id());

            $this->assertTrue(
                in_array($member->get_user_id(), $user_ids)
            );
        }
    }

    /**
     * @return void
     */
    public function test_add_members_to_public_workspace(): void {
        $this->setAdminUser();
        $generator = $this->getDataGenerator();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Create 2 users and added them to a workspace above.
        $user_ids = [];

        for ($i = 0; $i < 2; $i++) {
            $user = $generator->create_user();
            $user_ids[] = $user->id;
        }

        // Start the operation.
        $workspace_id = $workspace->get_id();
        $members = $this->resolve_graphql_mutation(
            'container_workspace_add_members',
            [
                'workspace_id' => $workspace_id,
                'user_ids' => $user_ids
            ]
        );

        $this->assertCount(2, $members);

        /** @var member $member */
        foreach ($members as $member) {
            $this->assertInstanceOf(member::class, $member);
            $this->assertEquals($workspace_id, $member->get_workspace_id());

            $this->assertTrue(
                in_array($member->get_user_id(), $user_ids)
            );
        }
    }

    /**
     * @return void
     */
    public function test_add_members_to_hidden_workspace(): void {
        $this->setAdminUser();
        $generator = $this->getDataGenerator();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_hidden_workspace();

        // Create 6 users and added them to a workspace above.
        $user_ids = [];

        for ($i = 0; $i < 6; $i++) {
            $user = $generator->create_user();
            $user_ids[] = $user->id;
        }

        // Start the operation.
        $workspace_id = $workspace->get_id();
        $members = $this->resolve_graphql_mutation(
            'container_workspace_add_members',
            [
                'workspace_id' => $workspace_id,
                'user_ids' => $user_ids
            ]
        );

        $this->assertCount(6, $members);

        /** @var member $member */
        foreach ($members as $member) {
            $this->assertInstanceOf(member::class, $member);
            $this->assertEquals($workspace_id, $member->get_workspace_id());

            $this->assertTrue(
                in_array($member->get_user_id(), $user_ids)
            );
        }
    }

    /**
     * @return void
     */
    public function test_execute_persistence_query(): void {
        $this->setAdminUser();
        $generator = $this->getDataGenerator();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $user_ids = [];
        for ($i = 0; $i < 4; $i++) {
            $user = $generator->create_user();
            $user_ids[] = $user->id;
        }

        $workspace_id = $workspace->get_id();
        $result = $this->execute_graphql_operation(
            'container_workspace_add_members',
            [
                'workspace_id' => $workspace_id,
                'user_ids' => $user_ids
            ]
        );

        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);
        $this->assertArrayHasKey('members', $result->data);
        $this->assertCount(4, $result->data['members']);

        $result_members = $result->data['members'];
        foreach ($result_members as $result_member) {
            $this->assertArrayHasKey('id', $result_member);
            $this->assertArrayHasKey('user', $result_member);
            $this->assertArrayHasKey('workspace_id', $result_member);

            $this->assertEquals($workspace_id, $result_member['workspace_id']);
            $this->assertIsArray($result_member['user']);
            $this->assertArrayHasKey('id', $result_member['user']);
            $this->assertTrue(in_array($result_member['user']['id'], $user_ids));
        }
    }

    /**
     * @return void
     */
    public function test_add_members_to_hidden_workspace_with_exception(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();
        $user_three = $generator->create_user();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $this->setUser($user_one);

        $workspace = $workspace_generator->create_hidden_workspace();
        $workspace_id = $workspace->get_id();

        $this->setUser($user_three);
        $this->expectException("moodle_exception");
        $this->expectExceptionMessage("Cannot manual add user to workspace");
        $this->resolve_graphql_mutation(
            'container_workspace_add_members',
            [
                'workspace_id' => $workspace_id,
                'user_ids' => [$user_two->id]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_add_member_to_a_deleted_workspace(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $workspace->mark_to_be_deleted();

        // Add a member to this workspace.
        $user_two = $generator->create_user();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The workspace is deleted");

        $this->resolve_graphql_mutation(
            'container_workspace_add_members',
            [
                'workspace_id' => $workspace->get_id(),
                'user_ids' => [$user_two->id]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_add_member_by_non_owner(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Add a member to this workspace.
        $user_two = $generator->create_user();
        member::added_to_workspace($workspace, $user_two->id);

        $this->setUser($user_two);
        $this->expectException(enrol_exception::class);

        $user_three = $generator->create_user();
        $this->resolve_graphql_mutation(
            'container_workspace_add_members',
            [
                'workspace_id' => $workspace->get_id(),
                'user_ids' => [$user_three->id]
            ]
        );
    }
}