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

use totara_webapi\phpunit\webapi_phpunit_helper;
use container_workspace\member\member_request;
use core\orm\pagination\offset_cursor_paginator;

class container_workspace_webapi_member_request_loader_testcase extends advanced_testcase {
    use webapi_phpunit_helper;
    /**
     * @return void
     */
    public function test_fetch_members(): void {
        $this->setAdminUser();
        $generator = $this->getDataGenerator();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        // Create 5 users and make a reuqest to this workspace.
        $requests = [];
        $workspace_id = $workspace->get_id();

        for ($i = 0; $i < 5; $i++) {
            $user = $generator->create_user();
            $request = member_request::create($workspace_id, $user->id);

            $requests[] = $request->get_id();
        }

        /** @var member_request[] $member_requests */
        $member_requests = $this->resolve_graphql_query(
            'container_workspace_member_requests',
            ['workspace_id' => $workspace_id]
        );

        $this->assertNotEmpty($member_requests);
        $this->assertCount(5, $member_requests);

        foreach ($member_requests as $member_request) {
            $this->assertTrue(in_array($member_request->get_id(), $requests));
        }
    }

    /**
     * @return void
     */
    public function test_fetch_members_cursor(): void {
        $this->setAdminUser();
        $generator = $this->getDataGenerator();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        // Create 2 member requests for the workspace above.
        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $workspace_id = $workspace->get_id();

        $user_one_request = member_request::create($workspace_id, $user_one->id);
        $user_two_request = member_request::create($workspace_id, $user_two->id);

        /** @var offset_cursor_paginator $result */
        $result = $this->resolve_graphql_query(
            'container_workspace_member_request_cursor',
            ['workspace_id' => $workspace_id]
        );

        $this->assertEquals(2, $result->get_total());
        $this->assertEquals(2, $result->count());

        /** @var member_request[] $requests */
        $requests = $result->get_items()->all();
        foreach ($requests as $request) {
            $this->assertTrue(
                in_array(
                    $request->get_id(),
                    [
                        $user_one_request->get_id(),
                        $user_two_request->get_id()
                    ]
                )
            );
        }
    }

    /**
     * @return void
     */
    public function test_fetch_members_operation(): void {
        $this->setAdminUser();
        $generator = $this->getDataGenerator();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        // Create two requests for the workspace above.
        $user_ids = [];
        $workspace_id = $workspace->get_id();

        for ($i = 0; $i < 2; $i++) {
            $user = $generator->create_user();
            $user_ids[] = $user->id;

            member_request::create($workspace_id, $user->id);
        }

        $result = $this->execute_graphql_operation(
            'container_workspace_pending_member_requests',
            ['workspace_id' => $workspace_id]
        );

        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);

        $this->assertArrayHasKey('member_requests', $result->data);
        $this->assertArrayHasKey('cursor', $result->data);

        $this->assertArrayHasKey('total', $result->data['cursor']);
        $this->assertArrayHasKey('next', $result->data['cursor']);
        $this->assertEmpty($result->data['cursor']['next']);
        $this->assertEquals(2, $result->data['cursor']['total']);

        // Member requests check.
        $this->assertCount(2, $result->data['member_requests']);

        $result_requests = $result->data['member_requests'];
        foreach ($result_requests as $request) {
            $this->assertIsArray($request);
            $this->assertArrayHasKey('id', $request);
            $this->assertArrayHasKey('user', $request);
            $this->assertIsArray($request['user']);
            $this->assertArrayHasKey('id', $request['user']);
            $this->assertTrue(in_array($request['user']['id'], $user_ids));
        }
    }

    /**
     * @return void
     */
    public function test_fetch_member_requests_from_different_workspace_as_admin(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();
        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        $workspace_id = $workspace->get_id();
        $request_ids = [];

        // Create 10 member requests to the workspacae.
        for ($i = 0; $i < 2; $i++) {
            $user = $generator->create_user();
            $request = member_request::create($workspace_id, $user->id);

            $request_ids[] = $request->get_id();
        }

        // Log in as admin and start fetching the pending requests.
        $this->setAdminUser();
        $result = $this->execute_graphql_operation(
            'container_workspace_pending_member_requests',
            ['workspace_id' => $workspace_id]
        );

        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);
        $this->assertArrayHasKey('member_requests', $result->data);
        $this->assertCount(2, $result->data['member_requests']);
    }
}