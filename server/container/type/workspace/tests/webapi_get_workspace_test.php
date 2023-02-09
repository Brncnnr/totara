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
use totara_userdata\userdata\target_user;
use container_workspace\userdata\workspace as user_data_workspace;
use container_workspace\workspace;
use container_workspace\exception\workspace_exception;

class container_workspace_webapi_get_workspace_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_get_workspace_when_owner_is_null(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Log in as admin and delete the workspace.
        $this->setAdminUser();
        delete_user($user_one);

        $user_one = core_user::get_user($user_one->id);
        $target_user = new target_user($user_one);

        user_data_workspace::execute_purge($target_user, context_system::instance());

        // Now the workspace does not have any owner - test if we are able to fetch the workspace.
        $result = $this->execute_graphql_operation(
            'container_workspace_get_workspace',
            [
                'id' => $workspace->get_id(),
                'theme' => 'ventura',
            ]
        );

        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);
        $this->assertArrayHasKey('workspace', $result->data);

        $workspace_data = $result->data['workspace'];

        $this->assertArrayHasKey('owner', $workspace_data);
        $this->assertNull($workspace_data['owner']);

        $this->assertArrayHasKey('name', $workspace_data);
        $this->assertEquals($workspace->get_name(), $workspace_data['name']);

        $this->assertArrayHasKey('id', $workspace_data);
        $this->assertEquals($workspace->get_id(), $workspace_data['id']);
    }

    /**
     * @return void
     */
    public function test_get_public_workspace_as_different_user(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Log in as different user and check if you are able to get the workspace or not.
        $user_two = $generator->create_user();
        $this->setUser($user_two);
        $workspace_id = $workspace->get_id();

        /** @var workspace $fetched_workspace */
        $fetched_workspace = $this->resolve_graphql_query('container_workspace_workspace', ['id' => $workspace_id]);

        $this->assertNotNull($fetched_workspace);
        $this->assertInstanceOf(workspace::class, $fetched_workspace);
        $this->assertEquals($workspace_id, $fetched_workspace->get_id());
        $this->assertEquals($workspace->get_name(), $fetched_workspace->get_name());
    }

    /**
     * @return void
     */
    public function test_get_workspace_from_course_id(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $course = $generator->create_course();
        $this->setUser($user_one);

        $this->expectException(coding_exception::class);
        $this->resolve_graphql_query('container_workspace_workspace', ['id' => $course->id]);
    }

    /**
     * @return void
     */
    public function test_get_workspace_with_no_session_user(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace(
            'workspace_101',
            null,
            null,
            $user_one->id
        );

        // Start fetching the workspace
        $this->expectException(require_login_exception::class);
        $this->resolve_graphql_query(
            'container_workspace_workspace',
            [
                'id' => $workspace->get_id()
            ]
        );
    }

    /**
     * @return void
     */
    public function test_get_hidden_workspace_by_user_without_permission(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_hidden_workspace();

        // Log in as second user and check if the user is able to fetch the workspace or not.
        $user_two = $generator->create_user();
        $this->setUser($user_two);

        $this->expectException(workspace_exception::class);
        $this->expectExceptionMessage(
            get_string('error:view_workspace', 'container_workspace')
        );

        // Fetch workspace.
        $this->resolve_graphql_query(
            'container_workspace_workspace',
            ['id' => $workspace->get_id()]
        );
    }

    /**
     * @return void
     */
    public function test_get_private_workspace_by_other_user(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        // Set the actor to user two and run the graphql query.
        $user_two = $generator->create_user();
        $this->setUser($user_two);

        /** @var workspace $fetched_workspace */
        $fetched_workspace = $this->resolve_graphql_query(
            'container_workspace_workspace',
            ['id' => $workspace->get_id()]
        );

        $this->assertInstanceOf(workspace::class, $fetched_workspace);
        $this->assertEquals($workspace->get_id(), $fetched_workspace->get_id());
    }
}