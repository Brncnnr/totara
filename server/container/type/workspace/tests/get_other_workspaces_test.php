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

use container_workspace\query\workspace\query;
use container_workspace\loader\workspace\loader;
use container_workspace\query\workspace\source;
use container_workspace\workspace;
use container_workspace\member\member;

/**
 * Test suite to assure that we are able to load other workspaces that the user/actor is not a part of.
 */
class container_workspace_get_other_workspaces_testcase extends advanced_testcase {
    /**
     * First we need to create a few workspaces from one user. Then we create one workspaces for other user.
     * After all the workspaces are all set up. Act as the second user and try to fetch the other workspaces that
     * user was not in. And make sure that the list of workspace does not contain his workspace.
     *
     * @return void
     */
    public function test_get_other(): void {
        $gen = $this->getDataGenerator();
        $user_one = $gen->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $gen->get_plugin_generator('container_workspace');

        for ($i = 0; $i < 5; $i++) {
            $workspace_generator->create_workspace();
        }

        $user_two = $gen->create_user();
        $this->setUser($user_two);

        $user_two_workspace = $workspace_generator->create_workspace();

        // Now time to fetch other workspaces
        $query = new query(source::OTHER, $user_two->id);
        $paginator = loader::get_workspaces($query);

        $this->assertEquals(5, $paginator->get_total());
        $workspaces = $paginator->get_items()->all();

        /** @var workspace $workspace */
        foreach ($workspaces as $workspace) {
            $this->assertNotEquals($workspace->get_id(), $user_two_workspace->get_id());
        }
    }

    /**
     * Test to get all other workspaces that actor is not a member of it.
     *
     * @return void
     */
    public function test_get_other_exclude_member(): void {
        $gen = $this->getDataGenerator();
        $user_one = $gen->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_gen */
        $workspace_gen = $gen->get_plugin_generator('container_workspace');
        $joined_workspaces = [];

        for ($i = 0; $i < 2; $i++) {
            $workspace = $workspace_gen->create_workspace();
            $joined_workspaces[$workspace->get_id()] = $workspace;
        }

        // Now make the user_two join the workspace. Then create new workspaces.
        $user_two = $gen->create_user();
        foreach ($joined_workspaces as $workspace) {
            member::join_workspace($workspace, $user_two->id);
        }

        // Create new workspaces.
        for ($i = 0; $i < 2; $i++) {
            $workspace_gen->create_workspace();
        }

        $query = new query(source::OTHER, $user_two->id);
        $paginator = loader::get_workspaces($query);

        $this->assertEquals(2, $paginator->get_total());
        $workspaces = $paginator->get_items()->all();

        foreach ($workspaces as $workspace) {
            $workspace_id = $workspace->get_id();
            $this->assertFalse(isset($joined_workspaces[$workspace_id]));
        }
    }
}
