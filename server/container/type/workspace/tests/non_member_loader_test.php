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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package container_workspace
 */
defined('MOODLE_INTERNAL') || die();

use container_workspace\member\member;
use container_workspace\query\member\non_member_query;
use container_workspace\loader\member\non_member_loader;
use core_phpunit\testcase;

/**
 * @deprecated since Totara 16. container_workspace\loader\member\non_member_loader
 * is no longer used.
 *
 * @group container_workspace
 */
class container_workspace_non_member_loader_testcase extends testcase {
    /**
     * @return void
     */
    public function test_load_non_member_of_a_workspace(): void {
        $this->setAdminUser();
        $generator = $this->getDataGenerator();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Create first 5 users and add these users into the workspace.
        $member_users = [];
        for ($i = 0; $i < 5; $i ++) {
            $user = $generator->create_user();
            $member_users[] = $user->id;

            member::added_to_workspace($workspace, $user->id);
        }

        // Create another 5 users that are not added to the workspace.
        $non_member_users = [];
        for ($i = 0; $i < 5; $i++) {
            $user = $generator->create_user();
            $non_member_users[] = $user->id;
        }

        // Start loading non-members of the workspace
        $query = new non_member_query($workspace->get_id());
        $paginator = non_member_loader::get_non_members($query);
        $this->assert_deprecated();

        $this->assertEquals(5, $paginator->get_total());

        $users = $paginator->get_items()->all();
        foreach ($users as $user) {
            $this->assertTrue(in_array($user->id, $non_member_users));
            $this->assertFalse(in_array($user->id, $member_users));
        }
    }

    /**
     * @return void
     */
    public function test_load_non_members_of_workspace_with_like(): void {
        $this->setAdminUser();
        $generator = $this->getDataGenerator();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $special_user = $generator->create_user([
            'firstname' => 'User',
            'lastname' => 'One',
            'email' => 'user.one@example.com'
        ]);

        // Create a member users.
        for ($i = 0; $i < 5; $i++) {
            $user = $generator->create_user();
            member::added_to_workspace($workspace, $user->id);
        }

        // Creat non member users.
        for ($i = 0; $i < 5; $i++) {
            $generator->create_user([
                'firstname' => uniqid(),
                'lastname' => uniqid(),
                'email' => uniqid() . '@example.com'
            ]);
        }

        // Start querying.
        $query = new non_member_query($workspace->get_id());
        $query->set_search_term('user');

        $paginator = non_member_loader::get_non_members($query);
        $this->assert_deprecated();
        $this->assertEquals(1, $paginator->get_total());

        $users = $paginator->get_items()->all();
        $this->assertCount(1, $users);

        $found_user = reset($users);
        $this->assertEquals($special_user->id, $found_user->id);
        $this->assertSame('User', $found_user->firstname);
        $this->assertSame('One', $found_user->lastname);
        $this->assertSame('user.one@example.com', $found_user->email);
    }

    /**
     * @return void
     */
    public function test_load_non_members_of_a_workspace_that_had_already_a_member_of_other_workspace(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace_one = $workspace_generator->create_workspace();
        $workspace_two = $workspace_generator->create_workspace();

        $users = [];

        // Adding 5 users to the system plus adding them to the workspaces.
        for ($i = 0; $i < 5; $i++) {
            $user = $generator->create_user();
            $users[$user->id] = $user;

            member::join_workspace($workspace_one, $user->id);
        }

        $site_admin = get_admin();
        $users[$site_admin->id] = $site_admin;

        // After adding the users, try to load workspace's two non member records.
        $query = new non_member_query($workspace_two->id);
        $result = non_member_loader::get_non_members($query);
        $this->assert_deprecated();

        // All 5 users in the system for now + site admin default user.
        $this->assertEquals(6, $result->get_total());
        $user_records = $result->get_items()->all();

        foreach ($user_records as $user_record) {
            $this->assertArrayHasKey(
                $user_record->id,
                $users,
                "Expecting the non member users should exist of the list created user"
            );
        }
    }

    private function assert_deprecated(): void {
        $messages = [
            'Class non_member_query is deprecated; use container_workspace\data_providers\non_members instead',
            'Class non_member_loader is deprecated; use container_workspace\data_providers\non_members instead'
        ];

        $this->assertDebuggingCalled($messages, DEBUG_DEVELOPER);
    }
}