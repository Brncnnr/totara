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

use container_workspace\discussion\discussion_helper;

class container_workspace_delete_discussions_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_delete_all_discussions(): void {
        global $DB;
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $workspace_id = $workspace->get_id();
        // Create 50 of discussions to the workspace.
        for ($i = 0; $i < 50; $i++) {
            $workspace_generator->create_discussion(
                $workspace_id,
                uniqid(),
                null,
                FORMAT_PLAIN
            );
        }

        self::assertEquals(50, $DB->count_records('workspace_discussion', ['course_id' => $workspace_id]));

        // Delete the discussions of this workspace should not leaving any trailing discussions.
        discussion_helper::delete_discussions_of_workspace($workspace, $user_one->id, 10);
        self::assertEquals(0, $DB->count_records('workspace_discussion', ['course_id' => $workspace_id]));
    }
}