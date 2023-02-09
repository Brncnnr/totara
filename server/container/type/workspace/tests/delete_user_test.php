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

class container_workspace_delete_user_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_delete_user_should_remove_user_from_workspace(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        // Log in as first user to create the workspace.
        $this->setUser($user_one);
        $user_one_workspace = $workspace_generator->create_workspace();

        // Log in as second user to create the workspace.
        $this->setUser($user_two);
        $user_two_workspace = $workspace_generator->create_workspace();

        // Delete user one and see if the workspace is updated accordingly.
        $this->setUser(null);
        delete_user($user_one);

        $user_one_workspace->reload();
        $user_two_workspace->reload();

        self::assertNull($user_one_workspace->get_user_id());
        self::assertNotNull($user_two_workspace->get_user_id());
        self::assertEquals($user_two->id, $user_two_workspace->get_user_id());
    }

    /**
     * @return void
     */
    public function test_suspend_user_should_note_remove_user_from_workspace(): void {
        global $CFG;
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        // Log in as first user to create the workspace.
        $this->setUser($user_one);
        $user_one_workspace = $workspace_generator->create_workspace();

        // Log in as second user to create the workspace.
        $this->setUser($user_two);
        $user_two_workspace = $workspace_generator->create_workspace();

        // Delete user one and see if the workspace is updated accordingly.
        $this->setUser(null);
        require_once("{$CFG->dirroot}/user/lib.php");

        user_suspend_user($user_one->id);

        $user_one_workspace->reload();
        $user_two_workspace->reload();

        self::assertNotNull($user_one_workspace->get_user_id());
        self::assertEquals($user_one->id, $user_one_workspace->get_user_id());

        self::assertNotNull($user_two_workspace->get_user_id());
        self::assertEquals($user_two->id, $user_two_workspace->get_user_id());
    }
}