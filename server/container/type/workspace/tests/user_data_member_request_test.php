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

use container_workspace\entity\workspace_member_request;
use container_workspace\member\member_request as model;
use container_workspace\userdata\member_request as user_data_member_request;
use totara_userdata\userdata\target_user;

class container_workspace_user_data_member_request_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_purge_member_request(): void {
        global $DB;
        $generator = $this->getDataGenerator();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $this->setAdminUser();

        $workspace = $workspace_generator->create_private_workspace();

        // Create two users and add request to the workspace.
        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $user_one_request = model::create($workspace->get_id(), $user_one->id);
        $user_two_request = model::create($workspace->get_id(), $user_two->id);

        // Delete the first user and check the purge is running properly.
        delete_user($user_one);

        $user_one = \core_user::get_user($user_one->id);
        $target_user = new target_user($user_one);

        user_data_member_request::execute_purge($target_user, context_system::instance());

        // User one request should be deleted.
        $this->assertFalse(
            $DB->record_exists(workspace_member_request::TABLE, ['id' => $user_one_request->get_id()])
        );

        // User two request should not be deleted.
        $this->assertTrue(
            $DB->record_exists(workspace_member_request::TABLE, ['id' => $user_two_request->get_id()])
        );
    }

    /**
     * @return void
     */
    public function test_export_member_request(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $this->setAdminUser();

        $request_ids = [];
        // Create 5 requests for 5 different workspaces and check the export is correcty exporting them.
        for ($i = 0; $i < 5; $i++) {
            $workspace = $workspace_generator->create_private_workspace();
            $member_request = model::create($workspace->get_id(), $user_one->id);

            $request_ids[] = $member_request->get_id();
        }

        $target_user = new target_user($user_one);
        $export = user_data_member_request::execute_export($target_user, context_system::instance());

        $this->assertNotEmpty($export->data);
        foreach ($export->data as $datum) {
            $this->assertArrayHasKey('id', $datum);
            $this->assertArrayHasKey('time_created', $datum);
            $this->assertArrayHasKey('time_cancelled', $datum);
            $this->assertArrayHasKey('time_declined', $datum);
            $this->assertArrayHasKey('time_accepted', $datum);
            $this->assertArrayHasKey('user_id', $datum);
            $this->assertArrayHasKey('course_id', $datum);

            $this->assertNotEmpty($datum['time_created']);
            $this->assertEmpty($datum['time_cancelled']);
            $this->assertEmpty($datum['time_accepted']);
            $this->assertEmpty($datum['time_declined']);
            $this->assertEquals($user_one->id, $datum['user_id']);

            $this->assertTrue(in_array($datum['id'], $request_ids));
        }
    }
}