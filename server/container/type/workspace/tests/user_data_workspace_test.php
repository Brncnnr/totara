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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package container_workspace
 */
defined('MOODLE_INTERNAL') || die();

use totara_userdata\userdata\target_user;
use container_workspace\userdata\workspace;

class container_workspace_user_data_workspace_testcase extends advanced_testcase {

    /**
     * @return void
     */
    public function test_purge_workspace(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $user1 = $generator->create_user();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        // Create workspaces for user.
        $this->setUser($user);
        $workspace_generator->create_workspace();
        $workspace_generator->create_workspace();

        // Create workspaces for user1.
        $this->setUser($user1);
        $workspace_generator->create_workspace();
        $workspace_generator->create_workspace();
        $workspace_generator->create_workspace();

        // Two workspaces created.
        $this->assertEquals(
            2,
            $DB->count_records('workspace', ['user_id' => $user->id])
        );

        $this->assertEquals(
            3,
            $DB->count_records('workspace', ['user_id' => $user1->id])
        );

        $user->deleted = 1;
        $DB->update_record('user', $user);

        $target_user = new target_user($user);
        $context = context_system::instance();

        $result = workspace::execute_purge($target_user, $context);
        $this->assertEquals(workspace::RESULT_STATUS_SUCCESS, $result);

        // Check if the current user is still being referenced in workspace
        $this->assertEquals(
            0,
            $DB->count_records('workspace', ['user_id' => $user->id])
        );

        // Admin user has to have two workspaces.
        $this->assertEquals(
            2,
            $DB->count_records('workspace', ['user_id' => null])
        );

        // User1 still has three workspaces.
        $this->assertEquals(
            3,
            $DB->count_records('workspace', ['user_id' => $user1->id])
        );

    }

    /**
     * @return void
     */
    public function test_export_workspace(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $user1 = $generator->create_user();
        $this->setUser($user);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace_generator->create_workspace();
        $workspace_generator->create_workspace();
        $workspace_generator->create_workspace();

        // Create workspaces for user1.
        $this->setUser($user1);
        $workspace_generator->create_workspace();
        $workspace_generator->create_workspace();

        $this->assertEquals(
            3,
            $DB->count_records('workspace', ['user_id' => $user->id])
        );

        $this->assertEquals(
            2,
            $DB->count_records('workspace', ['user_id' => $user1->id])
        );

        $target_user = new target_user($user);
        $context = context_system::instance();

        $export = \container_workspace\userdata\workspace::execute_export($target_user, $context);

        $this->assertNotEmpty($export->data);
        $this->assertCount(3, $export->data);

        foreach ($export->data as $record) {
            $this->assertIsArray($record);
            $this->assertArrayHasKey('full_name', $record);
            $this->assertArrayHasKey('short_name', $record);
            $this->assertArrayHasKey('summary', $record);
            $this->assertArrayHasKey('user_id', $record);
            $this->assertArrayHasKey('files', $record);

            // Assert the export data belong to user.
            $this->assertEquals($user->id, $record['user_id']);
        }
    }
}