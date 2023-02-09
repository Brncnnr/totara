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

use container_workspace\interactor\workspace\interactor;
use container_workspace\member\member;
use totara_comment\comment_helper;
use container_workspace\workspace;
use container_workspace\discussion\discussion;

class container_workspace_has_seen_workspace_check_testcase extends advanced_testcase {
    /**
     * This test to assure that the record of tracker to not populate on the creation process
     * but only when user navigated to see workspace. And most of the time after the creation is complete
     * user will be navigated to this page by javascript.
     *
     * @return void
     */
    public function test_check_owner_has_not_seen_workspace(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Check has seen on initial state.
        $user_one_workspace_interactor = new interactor($workspace, $user_one->id);
        $this->assertFalse($user_one_workspace_interactor->has_seen());
    }

    /**
     * @return void
     */
    public function test_check_user_has_not_seen_workspace_when_updated_by_other(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Create a discussion.
        $discussion = $workspace_generator->create_discussion($workspace->get_id());

        $user_one_workspace_interactor = new interactor($workspace, $user_one->id);
        $this->assertTrue($user_one_workspace_interactor->has_seen());

        // Log out and log in as user_two.
        $user_two = $generator->create_user();
        $this->setUser($user_two);

        member::join_workspace($workspace, $user_two->id);
        // Wait for a second until the user two comments - otherwise this test is pretty fragile if the computer
        // is a superman one.
        $this->waitForSecond();

        // Add a comment to the discussion created above.
        comment_helper::create_comment(
            workspace::get_type(),
            discussion::AREA,
            $discussion->get_id(),
            "This is a content"
        );

        $user_two_workspace_interactor = new interactor($workspace, $user_two->id);
        $this->assertTrue($user_two_workspace_interactor->has_seen());

        // Reload cache for user one's interactor data.
        $user_one_workspace_interactor->reload_workspace();
        $this->assertFalse($user_one_workspace_interactor->has_seen());
    }
}