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

use container_workspace\query\workspace\query;
use container_workspace\query\workspace\source;
use container_workspace\query\workspace\access;
use container_workspace\loader\workspace\loader;
use container_workspace\workspace;
use container_workspace\testing\generator as workspace_generator;

class container_workspace_workspace_loader_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_find_workspaces(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $this->setUser($user_one);

        // Create 5 public workspaces
        $public_ids = [];
        for ($i = 0; $i < 5; $i++) {
            $workspace = $workspace_generator->create_workspace();
            $public_ids[] = $workspace->get_id();
        }

        // Create 2 private workspaces.
        $private_ids = [];
        for ($i = 0; $i < 2; $i++) {
            $workspace = $workspace_generator->create_private_workspace();
            $private_ids[] = $workspace->get_id();
        }

        // Log in as admin and see if the admin is able to see those 5 public workspaces created from user one.
        $this->setAdminUser();

        $query = new query(source::ALL);
        $query->set_access(access::PUBLIC);

        $public_result = loader::get_workspaces($query);
        $this->assertEquals(5, $public_result->get_total());

        /** @var workspace[] $public_workspaces */
        $public_workspaces = $public_result->get_items()->all();
        foreach ($public_workspaces as $workspace) {
            $this->assertTrue($workspace->is_public());
            $this->assertFalse($workspace->is_private());
            $this->assertTrue(in_array($workspace->get_id(), $public_ids));
        }

        $query->set_access(access::PRIVATE);
        $private_result = loader::get_workspaces($query);
        $this->assertEquals(2, $private_result->get_total());

        /** @var workspace[] $private_workspaces */
        $private_workspaces = $private_result->get_items()->all();
        foreach ($private_workspaces as $workspace) {
            $this->assertTrue($workspace->is_private());
            $this->assertFalse($workspace->is_public());

            $this->assertTrue(in_array($workspace->get_id(), $private_ids));
        }
    }

    /**
     * @return void
     */
    public function test_fetch_hidden_workspaces(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $this->setUser($user_one);

        // Create one hidden workspaces and two private workspaces
        $private_ids = [];
        for ($i = 0; $i < 2; $i++) {
            $workspace = $workspace_generator->create_private_workspace();
            $private_ids[] = $workspace->get_id();
        }

        $hidden_workspace = $workspace_generator->create_hidden_workspace();

        // Login as second user and start fetching.
        $this->setUser($user_two);
        $user_two_query = new query(source::ALL, $user_two->id);
        $user_two_result = loader::get_workspaces($user_two_query);

        $this->assertEquals(2, $user_two_result->get_total());

        /** @var workspace[] $user_two_found_workspaces */
        $user_two_found_workspaces = $user_two_result->get_items()->all();
        foreach ($user_two_found_workspaces as $workspace) {
            $this->assertNotEquals($hidden_workspace->get_id(), $workspace->get_id());
            $this->assertTrue(in_array($workspace->get_id(), $private_ids));
        }

        // Login as admin and check if the admin is able to see hidden workspace or not.
        $this->setAdminUser();
        $admin_query = new query(source::ALL);
        $admin_result = loader::get_workspaces($admin_query);

        $this->assertEquals(3, $admin_result->get_total());

        /** @var workspace[] $admin_found_workspaces */
        $admin_found_workspaces = $admin_result->get_items()->all();
        foreach ($admin_found_workspaces as $workspace) {
            $this->assertTrue(
                in_array(
                    $workspace->get_id(),
                    array_merge($private_ids, [$hidden_workspace->get_id()])
                )
            );
        }
    }

    /**
     * @return void
     */
    public function test_fetch_hidden_workspaces_with_audience_visibility(): void {
        set_config('audiencevisibility', true);
        $generator = self::getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var workspace_generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $this->setUser($user_one);

        // Create three workspaces, one public, one private and one hidden.
        $public_workspace = $workspace_generator->create_workspace('public_workspace');
        $private_workspace = $workspace_generator->create_private_workspace('private_workspace');
        $hidden_workspace = $workspace_generator->create_hidden_workspace('hidden_workspace');

        // Add the user two to the public workspace.
        $workspace_generator->add_member($public_workspace, $user_two->id, $user_one->id);

        // Log in as user two and start fetching the workspaces which it should not return the hidden workspace.
        $this->setUser($user_two);

        $query = new query(source::ALL, $user_two->id);
        $result = loader::get_workspaces($query);

        // There should only be two records, which is one public workspace and one private workspace.
        // The hidden workspace should not be included into the result.
        self::assertEquals(2, $result->get_total());
        $workspaces = $result->get_items();

        self::assertEquals(2, $workspaces->count());

        /** @var workspace $fetched_workspace */
        foreach ($workspaces as $fetched_workspace) {
            self::assertNotEquals($hidden_workspace->get_id(), $fetched_workspace->get_id());
            self::assertContainsEquals(
                $fetched_workspace->get_id(),
                [
                    $public_workspace->get_id(),
                    $private_workspace->get_id()
                ]
            );
        }
    }
}