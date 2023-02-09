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

class container_workspace_join_workspace_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_join_private_workspace_different_user(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        // Create user two and try to join to the workspace - which should result error.
        $user_two = $generator->create_user();
        $this->setUser($user_two);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Cannot join the non-public workspace");
        member::join_workspace($workspace, $user_two->id);
    }

    /**
     * @return void
     */
    public function test_join_private_workspace_as_admin(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        // Log in as admin user and check if the admin user is able to join the workspace.
        $this->setAdminUser();
        $member = member::join_workspace($workspace);

        $this->assertTrue($member->is_active());
        $this->assertFalse($member->is_suspended());
    }
}