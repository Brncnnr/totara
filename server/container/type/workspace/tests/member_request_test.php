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

use container_workspace\member\member_request;
use container_workspace\entity\workspace_member_request as entity;
use container_workspace\member\member;

class container_workspace_member_request_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_create_member_request_on_private_workspace(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();

        // Log in as admin and start creating the workspace
        $this->setAdminUser();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace(
            "Workspace 101",
            null,
            null,
            null,
            true
        );

        $this->assertTrue($workspace->is_private());
        $this->assertFalse($workspace->is_public());
        $this->assertFalse($workspace->is_hidden());

        // Start creating a member request.
        $this->setUser($user);

        $request = member_request::create($workspace->get_id(), $user->id);
        $this->assertTrue(
            $DB->record_exists(entity::TABLE, ['id' => $request->get_id()])
        );

        $this->assertFalse($request->is_accepted());
        $this->assertFalse($request->is_declined());
        $this->assertFalse($request->is_cancelled());
    }

    /**
     * @return void
     */
    public function test_create_member_request_on_public_workspace(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $this->setUser($user_two);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $this->assertTrue($workspace->is_public());
        $this->assertFalse($workspace->is_private());
        $this->assertFalse($workspace->is_hidden());

        // Log in as first user and check if the user is able to create a member request.
        $this->setUser($user_one);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Workspace is a public workspace - cannot create a request");

        member_request::create($workspace->get_id(), $user_one->id);
    }

    /**
     * @return void
     */
    public function test_accept_member_request(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        // Log in as second user and create a private workspace.
        $this->setUser($user_two);
        $workspace = $workspace_generator->create_workspace(
            "This is workspace 1010",
            null,
            FORMAT_PLAIN,
            null,
            true
        );

        // Log in as second user and start requesting to workspace.
        $this->setUser($user_one);

        $request = member_request::create($workspace->get_id(), $user_one->id);
        $this->assertFalse($request->is_accepted());
        $this->assertFalse($request->is_declined());
        $this->assertFalse($request->is_cancelled());

        // Accept the request - by user two.
        $this->setUser($user_two);
        $request->accept($user_two->id);

        $this->assertTrue($request->is_accepted());
        $this->assertFalse($request->is_declined());
        $this->assertFalse($request->is_cancelled());
    }

    /**
     * @return void
     */
    public function test_decline_member_request(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        // Log in as second user and create a private workspace.
        $this->setUser($user_two);
        $workspace = $workspace_generator->create_workspace(
            'This is the workspace 201',
            null,
            FORMAT_PLAIN,
            null,
            true
        );

        // Log in as first user and request to join the workspace.
        $this->setUser($user_one);
        $member_request = member_request::create($workspace->get_id(), $user_one->id);

        $this->assertFalse($member_request->is_cancelled());
        $this->assertFalse($member_request->is_accepted());
        $this->assertFalse($member_request->is_declined());

        // Log in as the second user and decline the request.
        $this->setUser($user_two);
        $member_request->decline($user_two->id);

        $this->assertTrue($member_request->is_declined());
        $this->assertFalse($member_request->is_cancelled());
        $this->assertFalse($member_request->is_accepted());
    }

    /**
     * @return void
     */
    public function test_cancel_member_request(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        // Log in as second user and create a workspace
        $this->setUser($user_two);
        $workspace = $workspace_generator->create_workspace(
            'Name',
            null,
            null,
            null,
            true
        );

        // Log in as first user and request to join workspace.
        $this->setUser($user_one);
        $member_request = member_request::create($workspace->get_id(), $user_one->id);

        $this->assertFalse($member_request->is_accepted());
        $this->assertFalse($member_request->is_cancelled());
        $this->assertFalse($member_request->is_declined());

        // Cancel the request.
        $member_request->cancel($user_one->id);

        $this->assertTrue($member_request->is_cancelled());
        $this->assertFalse($member_request->is_declined());
        $this->assertFalse($member_request->is_accepted());
    }

    /**
     * As if user is already an active member of a workspace then when creating
     * a member request will yield error(s).
     *
     * @return void
     */
    public function test_cannot_create_member_request(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        // Log in as second user and start creating workspace
        $this->setUser($user_two);
        $workspace = $workspace_generator->create_workspace(
            "Workspace 201",
            null,
            null,
            null,
            true
        );

        // Inviting the user's one to workspace.
        member::added_to_workspace($workspace, $user_one->id);

        // Log in as user one and check if user is able to create a request or not.
        $this->setUser($user_one);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage(
            "Member is already existing for user '{$user_one->id}', cannot create another request"
        );

        member_request::create($workspace->get_id(), $user_one->id);
    }

    /**
     * @return void
     */
    public function test_cannot_create_member_request_for_course(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $course = $generator->create_course();

        // Log in as first user and check if the user is able to create a request or not.
        $this->setUser($user_one);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Cannot use the member request for different container type");

        member_request::create($course->id, $user_one->id);
    }

    /**
     * @return void
     */
    public function test_cannot_accept_declined_member_request(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        $this->setUser($user_two);
        $workspace = $workspace_generator->create_workspace(
            "Workspace 1010",
            null,
            FORMAT_PLAIN,
            null,
            true
        );

        // Log in as first user and request to join the workspace.
        $this->setUser($user_one);
        $member_request = member_request::create($workspace->get_id(), $user_one->id);

        $this->assertFalse($member_request->is_accepted());
        $this->assertFalse($member_request->is_cancelled());
        $this->assertFalse($member_request->is_declined());

        // Log in as second user and decline the request first.
        $this->setUser($user_two);

        $member_request->decline($user_two->id);
        $this->assertTrue($member_request->is_declined());
        $this->assertFalse($member_request->is_accepted());

        // Then try to accept the request.
        $reload_member_request = member_request::from_id($member_request->get_id());

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The request had already been declined or cancelled - cannot accept the request");
        $reload_member_request->accept($user_two->id);
    }

    /**
     * @return void
     */
    public function test_cannot_accept_cancelled_member_request(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        // Log in as user two - then start create the user two.
        $this->setUser($user_two);
        $workspace = $workspace_generator->create_workspace(
            'Wombo combo',
            null,
            FORMAT_PLAIN,
            null,
            true
        );

        // Log in as first user and request to the workspace
        $this->setUser($user_one);
        $member_request = member_request::create($workspace->get_id(), $user_one->id);

        $this->assertFalse($member_request->is_accepted());
        $this->assertFalse($member_request->is_cancelled());

        // Cancel the member request
        $member_request->cancel($user_one->id);

        // Log in as second user and accept the the request.
        $this->setUser($user_two);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The request had already been declined or cancelled - cannot accept the request");
        $member_request->accept($user_two->id);
    }

    /**
     * @return void
     */
    public function test_cannot_accept_member_request_by_some_one_else(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();
        $user_three = $generator->create_user();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $this->setUser($user_two);

        $workspace = $workspace_generator->create_workspace(
            "Workspace ddd hop",
            null,
            FORMAT_PLAIN,
            null,
            true
        );

        // Log in as user and create a request to join the workspace.
        $this->setUser($user_one);
        $member_request = member_request::create($workspace->get_id(), $user_one->id);

        $this->assertFalse($member_request->is_declined());
        $this->assertFalse($member_request->is_accepted());

        // Log in as third user and try to accept the request.
        $this->setUser($user_three);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Actor cannot accept the member request");

        $member_request->accept($user_three->id);
    }

    /**
     * @return void
     */
    public function test_cannot_decline_member_request_by_other_user(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        $this->setUser($user_two);
        $workspace = $workspace_generator->create_workspace(
            "This is something",
            null,
            FORMAT_PLAIN,
            null,
            true
        );

        // Log in as user then start requesting.
        $this->setUser($user_one);
        $member_request = member_request::create($workspace->get_id(), $user_one->id);

        $this->assertFalse($member_request->is_declined());

        // Log in as different user and check that if that user is able to decline the request.
        $user_three = $generator->create_user();
        $this->setUser($user_three);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Actor cannot decline the member request");

        $member_request->decline($user_three->id);
    }

    /**
     * @return void
     */
    public function test_cannot_decline_accepted_member_request(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $this->setUser($user_two);

        $workspace = $workspace_generator->create_workspace(
            "Workspace hip hop",
            null,
            FORMAT_PLAIN,
            null,
            true
        );

        // Log in as user and create a request to join the workspace.
        $this->setUser($user_one);
        $member_request = member_request::create($workspace->get_id(), $user_one->id);

        $this->assertFalse($member_request->is_declined());
        $this->assertFalse($member_request->is_accepted());

        // Log in as second user and try to accept the request, then cancel it.
        $this->setUser($user_two);

        $member_request->accept($user_two->id);
        $this->assertTrue($member_request->is_accepted());
        $this->assertFalse($member_request->is_declined());

        // Now try to cancel it.
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The request had already been declined or accepted - cannot cancel the request");
        $member_request->cancel($user_two->id);
    }

    /**
     * @return void
     */
    public function test_cannot_create_member_request_on_hidden_workspace(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $this->setUser($user_two);
        $workspace = $workspace_generator->create_hidden_workspace();

        // User one does not have ability to view hidden workspace - hence create member request will yield error.
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("User is not able to see the workspace");
        member_request::create($workspace->get_id(), $user_one->id);
    }

    /**
     * @return void
     */
    public function test_create_member_request_on_hidden_workspace(): void {
        global $DB;
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $this->setUSer($user_two);

        $workspace = $workspace_generator->create_hidden_workspace();

        $roles = get_archetype_roles('user');
        $context_system = context_system::instance();

        foreach ($roles as $role) {
            assign_capability('moodle/course:viewhiddencourses', CAP_ALLOW, $role->id, $context_system->id);
            assign_capability('container/workspace:administrate', CAP_ALLOW, $role->id, $context_system->id);
        }

        // Member request should be able to be created
        $request = member_request::create($workspace->get_id(), $user_one->id);
        $this->assertTrue($DB-> record_exists('workspace_member_request', ['id' => $request->get_id()]));
    }

    /**
     * @return void
     */
    public function test_create_member_request_with_request_message(): void {
        $generator = self::getDataGenerator();

        $user = $generator->create_user();

        self::setAdminUser();
        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        $workspace = $workspace_generator->create_workspace(
            "Workspace1",
            null,
            FORMAT_PLAIN,
            null,
            true
        );

        self::setUSer($user);

        $member_request = member_request::create($workspace->get_id(), $user->id, '<div class="text_to_html">request</div>');
        self::assertEquals('request', $member_request->get_request_content());
    }

    /**
     * @return void
     */
    public function test_create_member_request_with_decline_message(): void {
        $generator = self::getDataGenerator();

        $user = $generator->create_user();

        self::setAdminUser();
        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        $workspace = $workspace_generator->create_workspace(
            "Workspace1",
            null,
            FORMAT_PLAIN,
            null,
            true
        );

        self::setUSer($user);

        $member_request = member_request::create($workspace->get_id(), $user->id, 'request message');
        self::assertEquals('request message', $member_request->get_request_content());

        self::setAdminUser();
        $member_request->decline(get_admin()->id, time(), '<div class="text_to_html" >decline message</div>');
        self::assertEmpty($member_request->get_request_content());
        self::assertEquals('decline message', $member_request->get_decline_content());
    }
}