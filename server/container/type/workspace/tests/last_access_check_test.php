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
use container_workspace\tracker\tracker;

class container_workspace_last_access_check_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_get_last_access_workspace_that_exclude_to_be_deleted(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Visit the workspace.
        $tracker = new tracker($user_one->id);
        $tracker->visit_workspace($workspace);

        $last_visit_workspace_id = $tracker->get_last_visit_workspace();
        self::assertNotNull($last_visit_workspace_id);
        self::assertEquals($workspace->get_id(), $last_visit_workspace_id);

        // Update flag to be deleted and check if tracker still give us the same value.
        $workspace->mark_to_be_deleted();

        $update_last_visit_workspace_id = $tracker->get_last_visit_workspace();
        self::assertNull($update_last_visit_workspace_id);
    }

    /**
     * @return void
     */
    public function test_get_last_time_visit_workspace_that_not_exclude_to_be_deleted(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Track the workspace.
        $tracker = new tracker($user_one->id);
        $tracker->visit_workspace($workspace);

        // Last time visit of the workspace.
        $workspace_id = $workspace->get_id();
        $last_time_visit = $tracker->get_last_time_visit_workspace($workspace_id);

        self::assertNotNull($last_time_visit);

        // Update the flag to be deleted, which it should not prevent ability to fetch the last time
        // that user has visit the workspace.
        $workspace->mark_to_be_deleted();

        $updated_last_time_visit = $tracker->get_last_time_visit_workspace($workspace_id);
        self::assertNotNull($updated_last_time_visit);
        self::assertEquals($last_time_visit, $updated_last_time_visit);
    }

    /**
     * @return void
     */
    public function test_visit_workspace_that_is_flagged_to_delete(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $tracker = new tracker($user_one->id);
        $tracker->visit_workspace($workspace);

        $this->assertDebuggingNotCalled();

        // Flag the workspace to be deleted and check if tracker is complaining.
        $workspace->mark_to_be_deleted();
        $tracker->visit_workspace($workspace);

        $this->assertDebuggingCalled("Workspace is deleted, cannot track workspace anymore");
    }

    public function test_get_last_accessed_excludes_private_hidden_workspaces() {
        global $CFG, $DB;

        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        // Create a private workspace.
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace(null, null, null, null, true, true);

        // Track the workspace. This always succeeds.
        $tracker = new tracker($user_one->id);
        $tracker->visit_workspace($workspace);

        // Confirm the user cannot access the workspace
        $workspace_interactor = new interactor($workspace, $user_one->id);
        self::assertFalse($workspace_interactor->can_view_workspace());

        // Confirm we have lastaccess data for the user in the workspace they cant access.
        self::assertTrue($DB->record_exists('user_lastaccess', ['userid' => $user_one->id, 'courseid' => $workspace->id]));

        // With multitenancy on
        $CFG->tenantsenabled = true;

        // Confirm that the workspace tracker doesn't tell us that the last accessed workspace was the workspace they can't access now.
        self::assertNull($tracker->get_last_visit_workspace());

        // With multitenancy on
        $CFG->tenantsenabled = false;

        // Confirm that the workspace tracker doesn't tell us that the last accessed workspace was the workspace they can't access now.
        self::assertNull($tracker->get_last_visit_workspace());
    }
}