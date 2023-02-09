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

use totara_webapi\phpunit\webapi_phpunit_helper;

class container_workspace_webapi_change_primary_owner_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_change_primary_owner(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Now create a new user and check if we are able to change the primary owner.
        $user_two = $generator->create_user();

        $this->assertEquals($user_one->id, $workspace->get_user_id());

        // Clear any adhoc tasks.
        $this->executeAdhocTasks();

        // Change the primary owner by the admin user.
        $this->setAdminUser();
        $this->resolve_graphql_mutation(
            'container_workspace_change_primary_owner',
            [
                'workspace_id' => $workspace->get_id(),
                'user_id' => $user_two->id
            ]
        );

        // Refresh workspace.
        $workspace->reload();
        $this->assertEquals($user_two->id, $workspace->get_user_id());

        $sink = $this->redirectMessages();
        $sink->clear();

        // After changing the owner of the workspace, there should be a message
        // sending to the $user_two - and we should be able to catch that.
        $this->executeAdhocTasks();
        $messages = $sink->get_messages();

        $this->assertCount(1, $messages);
    }

    /**
     * @return void
     */
    public function test_change_primary_owner_of_a_course(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setAdminUser();
        $course = $generator->create_course();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Cannot find workspace by id '{$course->id}'");

        $this->resolve_graphql_mutation(
            'container_workspace_change_primary_owner',
            [
                'workspace_id' => $course->id,
                'user_id' => $user_one->id
            ]
        );
    }

    /**
     * @return void
     */
    public function test_change_primary_owner_by_random_user(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $user_two = $generator->create_user();
        $user_three = $generator->create_user();

        $this->setUser($user_three);
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Coding error detected, it must be fixed by a programmer: Actor does not have ability to update workspace owner');
        $this->resolve_graphql_mutation(
            'container_workspace_change_primary_owner',
            [
                'workspace_id' => $workspace->get_id(),
                'user_id' => $user_two->id
            ]
        );
    }
}