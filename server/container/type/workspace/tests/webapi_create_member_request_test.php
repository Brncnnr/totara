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
use container_workspace\member\member_request;
use core\orm\query\builder;

class container_workspace_webapi_create_member_request_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_create_member_request(): void {
        $generator = $this->getDataGenerator();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        // Log in as admin and create a workspace.
        $this->setAdminUser();
        $workspace = $workspace_generator->create_private_workspace();

        // Log in as different user and check if user is able to join to the workspace or not.
        $user_one = $generator->create_user();
        $this->setUser($user_one);

        /** @var member_request $member_request */
        $member_request = $this->resolve_graphql_mutation(
            'container_workspace_create_member_request',
            ['workspace_id' => $workspace->get_id()]
        );

        $this->assertInstanceOf(member_request::class, $member_request);

        $this->assertFalse($member_request->is_cancelled());
        $this->assertFalse($member_request->is_declined());
        $this->assertFalse($member_request->is_accepted());
    }

    /**
     * @return void
     */
    public function test_create_member_request_graphql(): void {
        global $DB;

        $generator = $this->getDataGenerator();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        $this->setAdminUser();
        $workspace = $workspace_generator->create_private_workspace('Something else');

        $user_one = $generator->create_user();
        $this->setUser($user_one);

        $result = $this->execute_graphql_operation(
            'container_workspace_request_to_join',
            ['workspace_id' => $workspace->get_id()]
        );

        $this->assertEmpty($result->errors);
        $this->assertIsArray($result->data);
        $this->assertNotEmpty($result->data);
        $this->assertArrayHasKey('member_request', $result->data);

        $request = $result->data['member_request'];

        $this->assertArrayHasKey('id', $request);
        $this->assertArrayHasKey('workspace_id', $request);
        $this->assertEquals($workspace->get_id(), $request['workspace_id']);
        $this->assertArrayHasKey('user', $request);
        $this->assertEquals($user_one->id, $request['user']['id']);
        $this->assertTrue(
            $DB->record_exists('workspace_member_request', ['id' => $request['id']])
        );
    }

    /**
     * @return void
     */
    public function test_create_same_member_request(): void {
        $generator = $this->getDataGenerator();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        $this->setAdminUser();
        $workspace = $workspace_generator->create_private_workspace();

        $user_one = $generator->create_user();
        $this->setUser($user_one);

        $workspace_id = $workspace->get_id();
        $old_member_request = member_request::create($workspace_id, $user_one->id);

        $this->assertFalse($old_member_request->is_accepted());
        $this->assertFalse($old_member_request->is_declined());
        $this->assertFalse($old_member_request->is_cancelled());

        // Now start with the graphql resolver.
        /** @var member_request $new_member_request */
        $new_member_request = $this->resolve_graphql_mutation(
            'container_workspace_create_member_request',
            ['workspace_id' => $workspace_id]
        );

        $this->assertInstanceOf(member_request::class, $new_member_request);
        $this->assertSame($new_member_request->get_id(), $old_member_request->get_id());
    }

    /**
     * @return void
     */
    public function test_create_member_request_with_public_workspace(): void {
        $generator = $this->getDataGenerator();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $this->setAdminUser();

        $public_workspace = $workspace_generator->create_workspace();
        $this->assertTrue($public_workspace->is_public());
        $this->assertFalse($public_workspace->is_private());

        // Test create member_request.
        $user_one = $generator->create_user();
        $this->setUser($user_one);

        $result = $this->execute_graphql_operation(
            'container_workspace_request_to_join',
            ['workspace_id' => $public_workspace->get_id()]
        );

        $this->assertEmpty($result->data);
        $this->assertNotEmpty($result->errors);
    }

    /**
     * @return void
     */
    public function test_create_member_request_to_workspace_that_is_deleted(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        // Flag the workspace for deletion.
        $workspace->mark_to_be_deleted(true);

        // Log in as second user and update the request to join the workspace.
        $this->setUser($user_two);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The workspace is deleted");

        $this->resolve_graphql_mutation(
            'container_workspace_create_member_request',
            ['workspace_id' => $workspace->get_id()]
        );
    }

    /**
     * @return void
     */
    public function test_create_member_request_with_request_content(): void {
        $generator = self::getDataGenerator();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        self::getDataGenerator();

        self::setAdminUser();
        $workspace = $workspace_generator->create_private_workspace();

        $user_one = $generator->create_user();
        self::setUser($user_one);

        $result = $this->resolve_graphql_mutation(
            'container_workspace_create_member_request',
            [
                'workspace_id' => $workspace->get_id(),
                'request_content' => '<div class="text_to_html">request</div>'
            ]
        );

        self::assertNotEmpty($result);
        self::assertEquals('request', $result->get_request_content());
    }

    /**
     * @return void
     */
    public function test_join_hidden_workspace_permissions(): void {
        global $DB;
        $generator = $this->getDataGenerator();
        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        // Remove the workspace:administrate capability from the Site Manager role. Give the role to an API user.
        $this->setAdminUser();

        $system_context_id = context_system::instance()->id;
        $workspace = $workspace_generator->create_hidden_workspace('private & hidden workspace');

        $site_manager_role = builder::get_db()->get_record('role', ['shortname' => 'manager'], 'id', MUST_EXIST);
        unassign_capability('container/workspace:administrate', $site_manager_role->id, $system_context_id);

        $api_user = $generator->create_user();
        role_assign($site_manager_role->id, $api_user->id, $system_context_id);

        // 1. Make a request to join a private & hidden workspace, as a user WITHOUT workspace:administrate capability.
        // Should fail & not reveal information.
        $this->setUser($api_user);
        $result = $this->execute_graphql_operation(
            'container_workspace_request_to_join',
            ['workspace_id' => $workspace->get_id(), 'request_content' => 'abc']
        );
        $this->assertEmpty($result->data);

        // 2. Make a request to join a private & hidden workspace, as a user WITH workspace:administrate capability.
        // Should succeed & reveal information.
        $this->setAdminUser();
        assign_capability('container/workspace:administrate', CAP_ALLOW, $site_manager_role->id, $system_context_id);
        $this->setUser($api_user);

        $result = $this->execute_graphql_operation(
            'container_workspace_request_to_join',
            ['workspace_id' => $workspace->get_id(), 'request_content' => 'abc']
        );

        $this->assertIsArray($result->data);
        $this->assertArrayHasKey('member_request', $result->data);

        $request = $result->data['member_request'];
        $this->assertArrayHasKey('id', $request);
        $this->assertArrayHasKey('workspace_id', $request);
        $this->assertEquals($workspace->get_id(), $request['workspace_id']);
        $this->assertTrue($DB->record_exists('workspace_member_request', ['id' => $request['id']]));
    }
}
