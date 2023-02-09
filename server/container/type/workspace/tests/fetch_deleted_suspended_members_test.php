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
use container_workspace\query\member\query as member_query;
use container_workspace\loader\member\loader as member_loader;

class container_workspace_fetch_deleted_suspended_members_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_fetch_deleted_user_in_workspace(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        // Create a workspace as user one.
        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Add user two to the workspace as member, then delete user to see whether the loader is
        // still fetching this user.
        $user_two = $generator->create_user();
        member::added_to_workspace($workspace, $user_two->id, false, $user_one->id);

        $member_query = new member_query($workspace->get_id());
        $before_delete_result = member_loader::get_members($member_query);

        // Including user one as well.
        self::assertEquals(2, $before_delete_result->get_total());

        /** @var member[] $before_delete_members */
        $before_delete_members = $before_delete_result->get_items()->all();

        foreach ($before_delete_members as $member) {
            self::assertContainsEquals($member->get_user_id(), [$user_one->id, $user_two->id]);
        }

        // Delete user two and fetch the list of mebmer again.
        delete_user($user_two);
        $after_delete_result = member_loader::get_members($member_query);

        self::assertEquals(1, $after_delete_result->get_total());

        /** @var member[] $after_delete_members */
        $after_delete_members = $after_delete_result->get_items()->all();

        foreach ($after_delete_members as $member) {
            self::assertEquals($user_one->id, $member->get_user_id());
            self::assertNotEquals($user_two->id, $member->get_user_id());
        }
    }

    /**
     * @return void
     */
    public function test_fetch_suspended_user_in_workspace(): void {
        global $CFG;

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        // Log in as user one to create the workspace.
        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Add user two to the workspace.
        $user_two = $generator->create_user();
        member::added_to_workspace($workspace, $user_two->id, false, $user_one->id);

        // Fetch the list of members.
        $member_query = new member_query($workspace->get_id());
        $before_suspend_result = member_loader::get_members($member_query);

        self::assertEquals(2, $before_suspend_result->get_total());
        $before_suspend_members = $before_suspend_result->get_items()->all();

        /** @var member $member */
        foreach ($before_suspend_members as $member) {
            self::assertContainsEquals($member->get_user_id(), [$user_one->id, $user_two->id]);
        }

        // Suspend the second user to see if the loader still fetching this user.
        require_once("{$CFG->dirroot}/user/lib.php");
        user_suspend_user($user_two->id);

        $after_suspend_result = member_loader::get_members($member_query);
        self::assertEquals(2, $after_suspend_result->get_total());

        $after_suspend_members = $after_suspend_result->get_items()->all();

        /** @var member $member */
        foreach ($after_suspend_members as $member) {
            self::assertContainsEquals($member->get_user_id(), [$user_one->id, $user_two->id]);
        }
    }
}