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

use container_workspace\interactor\discussion\interactor;

class container_workspace_discussion_interactor_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_interactor_with_deleted_workspace(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        // Create a workspace as a user one, then create a discussion, then check for the
        // interactor against it.
        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $discussion = $workspace_generator->create_discussion($workspace->get_id());
        $interactor = new interactor($discussion, $user_one->id);

        // Before workspace deletion.
        self::assertTrue(
            $interactor->can_delete(),
            "User should be able to delete the discussion before the workspace deletion"
        );

        self::assertTrue(
            $interactor->can_pin(),
            "User should be able to pin the discussion before the workspace deletion"
        );

        self::assertTrue(
            $interactor->can_comment(),
            "User should be able to comment on the discussion before the workspace deletion"
        );

        self::assertTrue(
            $interactor->can_update(),
            "User should be able to update the discussion before the workspace deletion"
        );

        self::assertFalse(
            $interactor->can_report(),
            "User should not be able to report their own discussion before the workspace deletion"
        );

        self::assertFalse(
            $interactor->can_react(),
            "User should not be able to react on their own discussion before the workspace deletion"
        );

        // After the workspace deleted.
        $workspace->mark_to_be_deleted(true);

        self::assertTrue(
            $interactor->can_delete(),
            "User should be able to delete the discussion even after the workspace deletion"
        );

        self::assertFalse(
            $interactor->can_pin(),
            "User should not be able to pin the discussion after the workspace deletion"
        );

        self::assertFalse(
            $interactor->can_comment(),
            "User should not be able to comment on the discussion after the workspace deletion"
        );

        self::assertFalse(
            $interactor->can_update(),
            "User should not be able to update the discussion after the workspace deletion"
        );

        self::assertFalse(
            $interactor->can_report(),
            "User should not be able to report their own discussion after the workspace deletion"
        );

        self::assertFalse(
            $interactor->can_react(),
            "User should not be able to react on their own discussion after the workspace deletion"
        );
    }
}