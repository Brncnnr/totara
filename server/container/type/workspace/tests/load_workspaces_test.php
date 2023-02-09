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

use container_workspace\loader\workspace\loader;
use container_workspace\query\workspace\query;
use container_workspace\query\workspace\source;
use container_workspace\query\workspace\sort;
use container_workspace\query\workspace\access;

defined('MOODLE_INTERNAL') || die();

class container_workspace_load_workspaces_testcase extends advanced_testcase {

    /**
     * @return void
     */
    public function test_load_workspaces(): void {
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

        // Login as user2.
        $this->setUser($user2);
        $query = query::from_parameters([
            'source' => source::get_code(source::ALL),
            'sort' => sort::get_code(sort::ALPHABET),
            'access' => access::get_code(access::PUBLIC)
        ], $user3->id);

        $workspaces = loader::get_workspaces($query)->get_items()->all();
        $this->assertNotEmpty($workspaces);
        $this->assertCount(4, $workspaces);

        $query = query::from_parameters([
            'source' => source::get_code(source::OWNED),
            'sort' => sort::get_code(sort::ALPHABET),
            'access' => access::get_code(access::PUBLIC)
        ], $user3->id);

        $workspaces = loader::get_workspaces($query)->get_items()->all();
        $this->assertNotEmpty($workspaces);
        $this->assertCount(3, $workspaces);

        $workspace_ids = array_column($workspaces, 'id');
        $this->assertContainsEquals($workspace1->get_id(), $workspace_ids);
        $this->assertContainsEquals($workspace2->get_id(), $workspace_ids);
        $this->assertContainsEquals($workspace3->get_id(), $workspace_ids);
    }
}