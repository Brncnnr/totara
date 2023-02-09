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

use container_workspace\member\member_request;
use container_workspace\query\member\member_request_query;
use container_workspace\loader\member\member_request_loader;

class container_workspace_fetch_deleted_suspended_member_request_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_fetch_deleted_user_in_member_requests(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        // Login as user one and create a private workspace.
        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        // Make user two create join request.
        $user_two = $generator->create_user();
        $workspace_id = $workspace->get_id();

        member_request::create($workspace_id, $user_two->id);

        // Load the member request.
        $query = new member_request_query($workspace_id);
        $before_delete_result = member_request_loader::get_member_requests($query);

        self::assertEquals(1, $before_delete_result->get_total());

        $before_delete_member_requests = $before_delete_result->get_items()->all();

        /** @var member_request $before_delete_member_request */
        $before_delete_member_request = reset($before_delete_member_requests);
        self::assertEquals($user_two->id, $before_delete_member_request->get_user_id());

        // Delete the user two and check if the request is still there.
        delete_user($user_two);

        $after_delete_member_requests = member_request_loader::get_member_requests($query);
        self::assertEquals(0, $after_delete_member_requests->get_total());
    }

    /**
     * @return void
     */
    public function test_fetch_suspended_user_in_member_requests(): void {
        global $CFG;

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        // Log in as first user and create a private workspace.
        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        // Register to workspace as member request for user two.
        $user_two = $generator->create_user();
        $workspace_id = $workspace->get_id();

        member_request::create($workspace_id, $user_two->id);

        // Load the member request.
        $query = new member_request_query($workspace_id);
        $before_suspend_result = member_request_loader::get_member_requests($query);

        self::assertEquals(1, $before_suspend_result->get_total());

        $before_suspend_member_requests = $before_suspend_result->get_items()->all();

        /** @var member_request $before_suspend_member_request */
        $before_suspend_member_request = reset($before_suspend_member_requests);
        self::assertEquals($user_two->id, $before_suspend_member_request->get_user_id());

        // Suspend the user and see if the result still has it.
        require_once("{$CFG->dirroot}/user/lib.php");
        user_suspend_user($user_two->id);

        $after_suspend_member_requests = member_request_loader::get_member_requests($query);
        self::assertEquals(0, $after_suspend_member_requests->get_total());
    }
}