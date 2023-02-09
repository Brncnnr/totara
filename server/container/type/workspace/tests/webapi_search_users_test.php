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

use core\entity\user;
use totara_webapi\phpunit\webapi_phpunit_helper;

class container_workspace_webapi_search_users_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_search_for_users_in_private_workspace_without_capability(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $private_workspace = $workspace_generator->create_private_workspace();

        // Log in as second user who is not a member of this workspace.
        $user_two = $generator->create_user();
        $this->setUser($user_two);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage("Invalid access");

        $this->resolve_graphql_query(
            'container_workspace_search_users',
            ['workspace_id' => $private_workspace->id]
        );
    }

    /**
     * @return void
     */
    public function test_search_for_users_in_public_workspace_without_capability(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $public_workspace = $workspace_generator->create_workspace();

        // Log in as second user who is not a member of this workspace.
        $user_two = $generator->create_user();
        $this->setUser($user_two);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage("Invalid access");

        $this->resolve_graphql_query(
            'container_workspace_search_users',
            ['workspace_id' => $public_workspace->id]
        );
    }

    public function test_search_for_users() {
        $generator = $this->getDataGenerator();

        $this->setAdminUser();

        $owner = $generator->create_user(['firstname' => 'Franz', 'lastname' => 'Ferdinand']);

        $user1 = $generator->create_user(['firstname' => 'Bonny', 'lastname' => 'Driver']);
        $user2 = $generator->create_user(['firstname' => 'Adam', 'lastname' => 'Trip']);
        $user3 = $generator->create_user(['firstname' => 'Xavier', 'lastname' => 'Bornham']);
        $user4 = $generator->create_user(['firstname' => 'Adele', 'lastname' => 'Wert', 'deleted' => 1]);
        $user5 = $generator->create_user(['firstname' => 'Clyde', 'lastname' => 'Vera', 'confirmed' => 0]);

        $this->setUser($owner);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $private_workspace = $workspace_generator->create_private_workspace();
        $public_workspace = $workspace_generator->create_workspace();

        $users = $this->resolve_graphql_query(
            'container_workspace_search_users',
            ['workspace_id' => $private_workspace->id]
        );

        $this->assertContainsOnlyInstancesOf(user::class, $users);
        $this->assertCount(4, $users);

        $user_ids = array_column($users, 'id');
        $this->assertEquals([$user2->id, get_admin()->id, $user1->id, $user3->id], $user_ids);

        $this->setAdminUser();

        $users = $this->resolve_graphql_query(
            'container_workspace_search_users',
            ['workspace_id' => $public_workspace->id]
        );

        $this->assertContainsOnlyInstancesOf(user::class, $users);
        $this->assertCount(4, $users);

        $user_ids = array_column($users, 'id');
        $this->assertEquals([$user2->id, get_admin()->id, $user1->id, $user3->id], $user_ids);
    }
}