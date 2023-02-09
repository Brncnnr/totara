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
use container_workspace\tracker\tracker;

class container_workspace_leave_workspace_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_leave_workspace_that_delete_tracker(): void {
        global $DB;

        $this->setAdminUser();
        $generator = $this->getDataGenerator();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_hidden_workspace();

        // Create user one and add to workspace as member.
        $user_one = $generator->create_user();
        $user_one_member = member::added_to_workspace($workspace, $user_one->id);

        $this->assertTrue($user_one_member->is_active());
        $this->assertFalse($user_one_member->is_suspended());
        $this->assertEquals($user_one->id, $user_one_member->get_user_id());

        $workspace_id = $workspace->get_id();

        $tracker = new tracker($user_one->id);
        $tracker->visit_workspace($workspace);

        $this->assertTrue(
            $DB->record_exists('user_lastaccess', ['courseid' => $workspace_id, 'userid' => $user_one->id])
        );

        // Leave the workspace should result in removing the record of table user_lastaccess.
        $user_one_member->leave($user_one->id);

        $this->assertTrue($user_one_member->is_suspended());
        $this->assertFalse($user_one_member->is_active());
        $this->assertEquals($user_one->id, $user_one_member->get_user_id());

        $this->assertFalse(
            $DB->record_exists('user_lastaccess', ['courseid' => $workspace_id, 'userid' => $user_one->id])
        );
    }
}