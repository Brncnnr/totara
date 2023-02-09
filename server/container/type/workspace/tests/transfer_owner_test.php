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
use container_workspace\interactor\workspace\interactor;
use container_workspace\local\workspace_helper;

class container_workspace_transfer_owner_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_transfer_owner_to_a_member(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        member::join_workspace($workspace, $user_two->id);

        $user_one_interactor = new interactor($workspace, $user_one->id);
        $user_two_interactor = new interactor($workspace, $user_two->id);

        $this->assertFalse($user_two_interactor->can_delete());
        $this->assertFalse($user_two_interactor->can_update());

        $this->assertTrue($user_one_interactor->can_update());
        $this->assertTrue($user_one_interactor->can_delete());

        // Start transfering transaction.
        workspace_helper::update_workspace_primary_owner($workspace, $user_two->id);

        $this->assertTrue($user_two_interactor->can_delete());
        $this->assertTrue($user_two_interactor->can_update());

        $this->assertFalse($user_one_interactor->can_update());
        $this->assertFalse($user_one_interactor->can_delete());
    }

    /**
     * @return void
     */
    public function test_transfer_owner_to_outsider(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $user_one_interactor = new interactor($workspace, $user_one->id);
        $user_two_interactor = new interactor($workspace, $user_two->id);

        $this->assertFalse($user_two_interactor->can_delete());
        $this->assertFalse($user_two_interactor->can_update());
        $this->assertFalse($user_two_interactor->is_joined());

        $this->assertTrue($user_one_interactor->can_update());
        $this->assertTrue($user_one_interactor->can_delete());

        // Start transfering transaction.
        workspace_helper::update_workspace_primary_owner($workspace, $user_two->id);

        $this->assertTrue($user_two_interactor->can_delete());
        $this->assertTrue($user_two_interactor->can_update());
        $this->assertTrue($user_two_interactor->is_joined());

        $this->assertFalse($user_one_interactor->can_update());
        $this->assertFalse($user_one_interactor->can_delete());
    }
}