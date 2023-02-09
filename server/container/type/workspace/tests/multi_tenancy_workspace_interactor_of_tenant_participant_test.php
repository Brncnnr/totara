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

class container_workspace_multi_tenancy_workspace_interactor_of_tenant_participant_testcase extends advanced_testcase {
    /**
     * @var stdClass|null;
     */
    private $tenant_one_user;

    /**
     * @var stdClass|null;
     */
    private $tenant_one_participant;

    /**
     * @var stdClass|null;
     */
    private $tenant_two_user;


    /**
     * @return void
     */
    protected function setUp(): void {
        $generator = self::getDataGenerator();
        $tenant_generator = $this->get_tenant_generator();

        $tenant_one = $tenant_generator->create_tenant();
        $tenant_two = $tenant_generator->create_tenant();

        $this->tenant_one_user = $generator->create_user([
            'firstname' => uniqid('tenant_one_user_'),
            'lastname' => uniqid('tenant_one_user_'),
            'tenantid' => $tenant_one->id
        ]);

        $this->tenant_two_user = $generator->create_user([
            'firstname' => uniqid('tenant_two_user_'),
            'lastname' => uniqid('tenant_two_user_'),
            'tenantid' => $tenant_two->id
        ]);

        $this->tenant_one_participant = $generator->create_user([
            'firstname' => uniqid('tenant_one_participant_'),
            'lastname' => uniqid('tenant_one_participant_')
        ]);

        $tenant_generator->set_user_participation($this->tenant_one_participant->id, [$tenant_one->id]);
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        $this->tenant_one_user = null;
        $this->tenant_one_participant = null;
        $this->tenant_two_user = null;
    }

    /**
     * @return \container_workspace\testing\generator
     */
    private function get_workspace_generator(): \container_workspace\testing\generator {
        $generator = self::getDataGenerator();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        return $workspace_generator;
    }

    /**
     * @return \totara_tenant\testing\generator
     */
    private function get_tenant_generator(): \totara_tenant\testing\generator {
        $generator = self::getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        return $tenant_generator;
    }

    /**
     * Explanations of interactor where the actor is tenant_one_participant.
     *
     * + is_joined: No - Had not joined the workspace
     * + can_manage: No - Not an admin of the workspace
     * + can_leave_workspace: No - Not a member of the workspace yet
     * + can_view_discussions: Yes - It is a public workspace
     * + can_view_members: Yes - It is a public workspace
     * + can_share_resources: No - Not a member of the workspace yet
     * + can_view_library: Yes - It is a public workspace
     * + can_view_workspace: Yes - It is a public workspace
     * + can_view_workspace_with_tenant_check: Yes - Actor participate to the same tenant as workspace
     * + can_create_discussions: No - Not a member of workspace yet
     * + can_accept_member_requests: No - Not a member and Workspace is public
     * + can_administrate: No - not an admin
     * + can_decline_member_requests: No - Not a member and workspace is public
     * + can_request_to_join: No - Workspace is public
     * + can_join: Yes - Had not joined yet.
     * + can_unshare_resources: No - Not a member, and not an owner or admin of workspace
     * + is_owner: No
     * + is_primary_owner: No
     * + can_update: No - not a member, and not admin of workspace
     * + can_delete: No - not a member, and not admin of workspace
     *
     * @return void
     */
    public function test_interactor_of_same_tenant_public_workspace_with_isolation_as_non_member(): void {
        set_config('tenantsisolated', 1);
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($this->tenant_one_user);
        $workspace = $workspace_generator->create_workspace();

        $interactor = new interactor($workspace, $this->tenant_one_participant->id);

        self::assertFalse($interactor->is_joined());
        self::assertFalse($interactor->can_manage());
        self::assertFalse($interactor->can_leave_workspace());
        self::assertTrue($interactor->can_view_discussions());
        self::assertTrue($interactor->can_view_members());
        self::assertFalse($interactor->can_share_resources());
        self::assertTrue($interactor->can_view_library());
        self::assertTrue($interactor->can_view_workspace());
        self::assertTrue($interactor->can_view_workspace_with_tenant_check());
        self::assertFalse($interactor->can_create_discussions());

        self::assertFalse($interactor->can_accept_member_request());
        self::assertFalse($interactor->can_administrate());
        self::assertFalse($interactor->can_decline_member_request());
        self::assertFalse($interactor->can_request_to_join());

        self::assertTrue($interactor->can_join());
        self::assertFalse($interactor->can_unshare_resources());
        self::assertFalse($interactor->is_owner());
        self::assertFalse($interactor->can_update());
        self::assertFalse($interactor->can_delete());
    }

    /**
     * Explanations of interactor where the actor is tenant_one_participant.
     *
     * + is_joined: No - Had not joined the workspace
     * + can_manage: No - Not an admin of the workspace
     * + can_leave_workspace: No - Not a member of the workspace yet
     * + can_view_discussions: Yes - It is a public workspace
     * + can_view_members: Yes - It is a public workspace
     * + can_share_resources: No - Not a member of the workspace yet
     * + can_view_library: Yes - It is a public workspace
     * + can_view_workspace: Yes - It is a public workspace
     * + can_view_workspace_with_tenant_check: Yes - Actor participate to the same tenant as workspace
     * + can_create_discussions: No - Not a member of workspace yet
     * + can_accept_member_requests: No - Not a member and Workspace is public
     * + can_administrate: No - not an admin
     * + can_decline_member_requests: No - Not a member and workspace is public
     * + can_request_to_join: No - Workspace is public
     * + can_join: Yes - Had not joined yet.
     * + can_unshare_resources: No - Not a member, and not an owner or admin of workspace
     * + is_owner: No
     * + is_primary_owner: No
     * + can_update: No - not a member, and not admin of workspace
     * + can_delete: No - not a member, and not admin of workspace
     *
     * @return void
     */
    public function test_interactor_of_same_tenant_public_workspace_without_isolation_as_non_member(): void {
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($this->tenant_one_user);
        $workspace = $workspace_generator->create_workspace();

        $interactor = new interactor($workspace, $this->tenant_one_participant->id);
        self::assertFalse($interactor->is_joined());
        self::assertFalse($interactor->can_manage());
        self::assertFalse($interactor->can_leave_workspace());
        self::assertTrue($interactor->can_view_discussions());
        self::assertTrue($interactor->can_view_members());
        self::assertFalse($interactor->can_share_resources());
        self::assertTrue($interactor->can_view_library());
        self::assertTrue($interactor->can_view_workspace());
        self::assertTrue($interactor->can_view_workspace_with_tenant_check());
        self::assertFalse($interactor->can_create_discussions());

        self::assertFalse($interactor->can_accept_member_request());
        self::assertFalse($interactor->can_administrate());
        self::assertFalse($interactor->can_decline_member_request());
        self::assertFalse($interactor->can_request_to_join());

        self::assertTrue($interactor->can_join());
        self::assertFalse($interactor->can_unshare_resources());
        self::assertFalse($interactor->is_owner());
        self::assertFalse($interactor->can_update());
        self::assertFalse($interactor->can_delete());
    }


    /**
     * Explanations of interactor where the actor is tenant_one_participant.
     *
     * + is_joined: Yes - Had already joined the workspace
     * + can_manage: No - Not an admin of the workspace
     * + can_leave_workspace: Yes - As a member of workspace
     * + can_view_discussions: Yes - As a member of workspace
     * + can_view_members: Yes - As a member of workspace
     * + can_share_resources: Yes - As a member of workspace
     * + can_view_library: Yes - As a member of workspace
     * + can_view_workspace: Yes - As a member of workspace
     * + can_view_workspace_with_tenant_check: Yes - Actor participate to the same tenant as workspace
     * + can_create_discussions: Yes - As a member of workspace
     * + can_accept_member_requests: No - Workspace is public
     * + can_administrate: No - not an admin
     * + can_decline_member_requests: No - Workspace is public
     * + can_request_to_join: No - Workspace is public
     * + can_join: No - Had already joined.
     * + can_unshare_resources: No - Not admin of workspace
     * + is_owner: No
     * + is_primary_owner: No
     * + can_update: No - Not admin of workspace
     * + can_delete: No -Not admin of workspace
     *
     * @return void
     */
    public function test_interactor_of_same_tenant_public_workspace_with_isolation_as_member(): void {
        set_config('tenantsisolated', true);
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($this->tenant_one_user);
        $workspace = $workspace_generator->create_workspace();

        $workspace_generator->add_member(
            $workspace,
            $this->tenant_one_participant->id,
            $this->tenant_one_user->id
        );

        $interactor = new interactor($workspace, $this->tenant_one_participant->id);

        self::assertTrue($interactor->is_joined());
        self::assertFalse($interactor->can_manage());
        self::assertTrue($interactor->can_leave_workspace());
        self::assertTrue($interactor->can_view_discussions());
        self::assertTrue($interactor->can_view_members());
        self::assertTrue($interactor->can_share_resources());
        self::assertTrue($interactor->can_view_library());
        self::assertTrue($interactor->can_view_workspace());
        self::assertTrue($interactor->can_view_workspace_with_tenant_check());
        self::assertTrue($interactor->can_create_discussions());

        self::assertFalse($interactor->can_accept_member_request());
        self::assertFalse($interactor->can_administrate());
        self::assertFalse($interactor->can_decline_member_request());
        self::assertFalse($interactor->can_request_to_join());

        self::assertFalse($interactor->can_join());
        self::assertFalse($interactor->can_unshare_resources());
        self::assertFalse($interactor->is_owner());
        self::assertFalse($interactor->can_update());
        self::assertFalse($interactor->can_delete());
    }

    /**
     * Explanations of interactor where the actor is tenant_one_participant.
     *
     * + is_joined: Yes - Had already joined the workspace
     * + can_manage: No - Not an admin of the workspace
     * + can_leave_workspace: Yes - As a member of workspace
     * + can_view_discussions: Yes - As a member of workspace
     * + can_view_members: Yes - As a member of workspace
     * + can_share_resources: Yes - As a member of workspace
     * + can_view_library: Yes - As a member of workspace
     * + can_view_workspace: Yes - As a member of workspace
     * + can_view_workspace_with_tenant_check: Yes - Actor participate to the same tenant as workspace
     * + can_create_discussions: Yes - As a member of workspace
     * + can_accept_member_requests: No - Workspace is public
     * + can_administrate: No - not an admin
     * + can_decline_member_requests: No - Workspace is public
     * + can_request_to_join: No - Workspace is public
     * + can_join: No - Had already joined.
     * + can_unshare_resources: No - Not admin of workspace
     * + is_owner: No
     * + is_primary_owner: No
     * + can_update: No - Not admin of workspace
     * + can_delete: No -Not admin of workspace
     *
     * @return void
     */
    public function test_interactor_of_same_tenant_public_workspace_without_isolation_as_member(): void {
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($this->tenant_one_user);
        $workspace = $workspace_generator->create_workspace();

        $workspace_generator->add_member(
            $workspace,
            $this->tenant_one_participant->id,
            $this->tenant_one_user->id
        );

        $interactor = new interactor($workspace, $this->tenant_one_participant->id);

        self::assertTrue($interactor->is_joined());
        self::assertFalse($interactor->can_manage());
        self::assertTrue($interactor->can_leave_workspace());
        self::assertTrue($interactor->can_view_discussions());
        self::assertTrue($interactor->can_view_members());
        self::assertTrue($interactor->can_share_resources());
        self::assertTrue($interactor->can_view_library());
        self::assertTrue($interactor->can_view_workspace());
        self::assertTrue($interactor->can_view_workspace_with_tenant_check());
        self::assertTrue($interactor->can_create_discussions());

        self::assertFalse($interactor->can_accept_member_request());
        self::assertFalse($interactor->can_administrate());
        self::assertFalse($interactor->can_decline_member_request());
        self::assertFalse($interactor->can_request_to_join());

        self::assertFalse($interactor->can_join());
        self::assertFalse($interactor->can_unshare_resources());
        self::assertFalse($interactor->is_owner());
        self::assertFalse($interactor->can_update());
        self::assertFalse($interactor->can_delete());
    }

    /**
     * Explanations of interactor where the actor is tenant_one_participant.
     *
     * + is_joined: No - Had not yet joined the workspace
     * + can_manage: No - Not an admin of the workspace
     * + can_leave_workspace: No - not a member of workspace
     * + can_view_discussions: No - Not a member of workspace, and workspace is private
     * + can_view_members: No - Not a member of workspace, and workspace is private
     * + can_share_resources: No - Not a member of workspace
     * + can_view_library: No - Not a member of workspace, and workspace is private
     * + can_view_workspace: Yes - Same tenant workspace, also workspace is private
     * + can_view_workspace_with_tenant_check: Yes - Actor participate to the same tenant as workspace
     * + can_create_discussions: Not - Not a member of workspace
     * + can_accept_member_requests: No - Not an admin of workspace
     * + can_administrate: No - not an admin
     * + can_decline_member_requests: No - Not an admin of workspace
     * + can_request_to_join: Yes - not a member, and workspace is private
     * + can_join: No - Workspace is private
     * + can_unshare_resources: No - Not admin of workspace
     * + is_owner: No
     * + is_primary_owner: No
     * + can_update: No - Not admin of workspace
     * + can_delete: No -Not admin of workspace
     *
     * @return void
     */
    public function test_interactor_of_same_tenant_private_workspace_with_isolation_as_non_member(): void {
        set_config('tenantsisolated', 1);
        $this->setUser($this->tenant_one_user);

        $workspace_generator = $this->get_workspace_generator();
        $workspace = $workspace_generator->create_private_workspace();

        $interactor = new interactor($workspace, $this->tenant_one_participant->id);

        self::assertFalse($interactor->is_joined());
        self::assertFalse($interactor->can_manage());
        self::assertFalse($interactor->can_leave_workspace());
        self::assertFalse($interactor->can_view_discussions());
        self::assertFalse($interactor->can_view_members());
        self::assertFalse($interactor->can_share_resources());
        self::assertFalse($interactor->can_view_library());
        self::assertTrue($interactor->can_view_workspace());
        self::assertTrue($interactor->can_view_workspace_with_tenant_check());
        self::assertFalse($interactor->can_create_discussions());

        self::assertFalse($interactor->can_accept_member_request());
        self::assertFalse($interactor->can_administrate());
        self::assertFalse($interactor->can_decline_member_request());
        self::assertTrue($interactor->can_request_to_join());

        self::assertFalse($interactor->can_join());
        self::assertFalse($interactor->can_unshare_resources());
        self::assertFalse($interactor->is_owner());
        self::assertFalse($interactor->can_update());
        self::assertFalse($interactor->can_delete());
    }

    /**
     * Explanations of interactor where the actor is tenant_one_participant.
     *
     * + is_joined: No - Had not yet joined the workspace
     * + can_manage: No - Not an admin of the workspace
     * + can_leave_workspace: No - not a member of workspace
     * + can_view_discussions: No - Not a member of workspace, and workspace is private
     * + can_view_members: No - Not a member of workspace, and workspace is private
     * + can_share_resources: No - Not a member of workspace
     * + can_view_library: No - Not a member of workspace, and workspace is private
     * + can_view_workspace: Yes - Same tenant workspace, also workspace is private
     * + can_view_workspace_with_tenant_check: Yes - Actor participate to the same tenant as workspace
     * + can_create_discussions: Not - Not a member of workspace
     * + can_accept_member_requests: No - Not an admin of workspace
     * + can_administrate: No - not an admin
     * + can_decline_member_requests: No - Not an admin of workspace
     * + can_request_to_join: Yes - not a member, and workspace is private
     * + can_join: No - Workspace is private
     * + can_unshare_resources: No - Not admin of workspace
     * + is_owner: No
     * + is_primary_owner: No
     * + can_update: No - Not admin of workspace
     * + can_delete: No -Not admin of workspace
     *
     * @return void
     */
    public function test_interactor_of_same_tenant_private_workspace_without_isolation_as_non_member(): void {
        $this->setUser($this->tenant_one_user);

        $workspace_generator = $this->get_workspace_generator();
        $workspace = $workspace_generator->create_private_workspace();

        $interactor = new interactor($workspace, $this->tenant_one_participant->id);

        self::assertFalse($interactor->is_joined());
        self::assertFalse($interactor->can_manage());
        self::assertFalse($interactor->can_leave_workspace());
        self::assertFalse($interactor->can_view_discussions());
        self::assertFalse($interactor->can_view_members());
        self::assertFalse($interactor->can_share_resources());
        self::assertFalse($interactor->can_view_library());
        self::assertTrue($interactor->can_view_workspace());
        self::assertTrue($interactor->can_view_workspace_with_tenant_check());
        self::assertFalse($interactor->can_create_discussions());

        self::assertFalse($interactor->can_accept_member_request());
        self::assertFalse($interactor->can_administrate());
        self::assertFalse($interactor->can_decline_member_request());
        self::assertTrue($interactor->can_request_to_join());

        self::assertFalse($interactor->can_join());
        self::assertFalse($interactor->can_unshare_resources());
        self::assertFalse($interactor->is_owner());
        self::assertFalse($interactor->can_update());
        self::assertFalse($interactor->can_delete());
    }

    /**
     * Explanations of interactor where the actor is tenant_one_participant.
     *
     * + is_joined: Yes - Had added to workspace already
     * + can_manage: No - Not an admin of the workspace
     * + can_leave_workspace: Yes - As a member of workspace
     * + can_view_discussions: Yes - AS a member of workspace
     * + can_view_members: Yes - AS a member of workspace
     * + can_share_resources: Yes - As a member of workspace
     * + can_view_library: No - Not a member of workspace, and workspace is private
     * + can_view_workspace: Yes - Same tenant workspace, also workspace is private
     * + can_view_workspace_with_tenant_check: Yes - Actor participate to the same tenant as workspace
     * + can_create_discussions: Yes - As a member of workspace
     * + can_accept_member_requests: No - Not an admin of workspace
     * + can_administrate: No - not an admin
     * + can_decline_member_requests: No - Not an admin of workspace
     * + can_request_to_join: No - Had added to workspace already
     * + can_join: No - Workspace is private
     * + can_unshare_resources: No - Not admin of workspace
     * + is_owner: No
     * + is_primary_owner: No
     * + can_update: No - Not admin of workspace
     * + can_delete: No -Not admin of workspace
     *
     * @return void
     */
    public function test_interactor_of_same_tenant_private_workspace_with_isolation_as_member(): void {
        set_config('tenantsisolated', 1);
        $this->setUser($this->tenant_one_user);

        $workspace_generator = $this->get_workspace_generator();
        $workspace = $workspace_generator->create_private_workspace();

        $workspace_generator->add_member(
            $workspace,
            $this->tenant_one_participant->id,
            $this->tenant_one_user->id
        );

        $interactor = new interactor(
            $workspace,
            $this->tenant_one_participant->id
        );

        self::assertTrue($interactor->is_joined());
        self::assertFalse($interactor->can_manage());
        self::assertTrue($interactor->can_leave_workspace());
        self::assertTrue($interactor->can_view_discussions());
        self::assertTrue($interactor->can_view_members());
        self::assertTrue($interactor->can_share_resources());
        self::assertTrue($interactor->can_view_library());
        self::assertTrue($interactor->can_view_workspace());
        self::assertTrue($interactor->can_view_workspace_with_tenant_check());
        self::assertTrue($interactor->can_create_discussions());

        self::assertFalse($interactor->can_accept_member_request());
        self::assertFalse($interactor->can_administrate());
        self::assertFalse($interactor->can_decline_member_request());
        self::assertFalse($interactor->can_request_to_join());

        self::assertFalse($interactor->can_join());
        self::assertFalse($interactor->can_unshare_resources());
        self::assertFalse($interactor->is_owner());
        self::assertFalse($interactor->can_update());
        self::assertFalse($interactor->can_delete());
    }

    /**
     * Explanations of interactor where the actor is tenant_one_participant.
     *
     * + is_joined: Yes - Had added to workspace already
     * + can_manage: No - Not an admin of the workspace
     * + can_leave_workspace: Yes - As a member of workspace
     * + can_view_discussions: Yes - AS a member of workspace
     * + can_view_members: Yes - AS a member of workspace
     * + can_share_resources: Yes - As a member of workspace
     * + can_view_library: No - Not a member of workspace, and workspace is private
     * + can_view_workspace: Yes - Same tenant workspace, also workspace is private
     * + can_view_workspace_with_tenant_check: Yes - Actor participate to the same tenant as workspace
     * + can_create_discussions: Yes - As a member of workspace
     * + can_accept_member_requests: No - Not an admin of workspace
     * + can_administrate: No - not an admin
     * + can_decline_member_requests: No - Not an admin of workspace
     * + can_request_to_join: No - Had added to workspace already
     * + can_join: No - Workspace is private
     * + can_unshare_resources: No - Not admin of workspace
     * + is_owner: No
     * + is_primary_owner: No
     * + can_update: No - Not admin of workspace
     * + can_delete: No -Not admin of workspace
     *
     * @return void
     */
    public function test_interactor_of_same_tenant_private_workspace_without_isolation_as_member(): void {
        $this->setUser($this->tenant_one_user);

        $workspace_generator = $this->get_workspace_generator();
        $workspace = $workspace_generator->create_private_workspace();

        $workspace_generator->add_member(
            $workspace,
            $this->tenant_one_participant->id,
            $this->tenant_one_user->id
        );

        $interactor = new interactor(
            $workspace,
            $this->tenant_one_participant->id
        );

        self::assertTrue($interactor->is_joined());
        self::assertFalse($interactor->can_manage());
        self::assertTrue($interactor->can_leave_workspace());
        self::assertTrue($interactor->can_view_discussions());
        self::assertTrue($interactor->can_view_members());
        self::assertTrue($interactor->can_share_resources());
        self::assertTrue($interactor->can_view_library());
        self::assertTrue($interactor->can_view_workspace());
        self::assertTrue($interactor->can_view_workspace_with_tenant_check());
        self::assertTrue($interactor->can_create_discussions());

        self::assertFalse($interactor->can_accept_member_request());
        self::assertFalse($interactor->can_administrate());
        self::assertFalse($interactor->can_decline_member_request());
        self::assertFalse($interactor->can_request_to_join());

        self::assertFalse($interactor->can_join());
        self::assertFalse($interactor->can_unshare_resources());
        self::assertFalse($interactor->is_owner());
        self::assertFalse($interactor->can_update());
        self::assertFalse($interactor->can_delete());
    }

    /**
     * Explanations of interactor where the actor is tenant_one_participant.
     *
     * + is_joined: No - Had not yet joined the workspace
     * + can_manage: No - Not an admin of the workspace
     * + can_leave_workspace: No - Not a member of workspace
     * + can_view_discussions: No - Different tenant workspace
     * + can_view_members: No - Different tenant workspace
     * + can_share_resources: No - Not a member of workspace
     * + can_view_library: No - Different tenant workspace
     * + can_view_workspace: No - Different tenant workspace
     * + can_view_workspace_with_tenant_check: No - Not participating to the same tenant as the workspace
     * + can_create_discussions: No - Not a member of workspace
     * + can_accept_member_requests: No - Workspace is public
     * + can_administrate: No - not an admin
     * + can_decline_member_requests: No - Workspace is public
     * + can_request_to_join: No - Workspace is public
     * + can_join: No - Different tenant workspace.
     * + can_unshare_resources: No - Not admin of workspace
     * + is_owner: No
     * + is_primary_owner: No
     * + can_update: No - Not admin of workspace
     * + can_delete: No -Not admin of workspace
     *
     * @return void
     */
    public function test_interactor_of_different_tenant_public_workspace_with_isolation_as_non_member(): void {
        set_config('tenantsisolated', 1);
        $this->setUser($this->tenant_two_user);

        $workspace_generator = $this->get_workspace_generator();
        $workspace = $workspace_generator->create_workspace();

        $interactor = new interactor($workspace, $this->tenant_one_participant->id);

        self::assertFalse($interactor->is_joined());
        self::assertFalse($interactor->can_manage());
        self::assertFalse($interactor->can_leave_workspace());
        self::assertFalse($interactor->can_view_discussions());
        self::assertFalse($interactor->can_view_members());
        self::assertFalse($interactor->can_share_resources());
        self::assertFalse($interactor->can_view_library());
        self::assertFalse($interactor->can_view_workspace());
        self::assertFalse($interactor->can_view_workspace_with_tenant_check());
        self::assertFalse($interactor->can_create_discussions());

        self::assertFalse($interactor->can_accept_member_request());
        self::assertFalse($interactor->can_administrate());
        self::assertFalse($interactor->can_decline_member_request());
        self::assertFalse($interactor->can_request_to_join());

        self::assertFalse($interactor->can_join());
        self::assertFalse($interactor->can_unshare_resources());
        self::assertFalse($interactor->is_owner());
        self::assertFalse($interactor->can_update());
        self::assertFalse($interactor->can_delete());
    }

    /**
     * Explanations of interactor where the actor is tenant_one_participant.
     *
     * + is_joined: No - Had not yet joined the workspace
     * + can_manage: No - Not an admin of the workspace
     * + can_leave_workspace: No - Not a member of workspace
     * + can_view_discussions: No - Different tenant workspace, system user should not be able to see tenant's workspace
     * + can_view_members: No - Different tenant workspace, system user should not be able to see tenant's workspace
     * + can_share_resources: No - Not a member of workspace
     * + can_view_library: No - Different tenant workspace, system user should not be able to see tenant's workspace
     * + can_view_workspace: No - Different tenant workspace, system user should not be able to see tenant's workspace
     * + can_view_workspace_with_tenant_check: No - Different tenant workspace, system user should not be able
     *                                              to see tenant's workspace
     * + can_create_discussions: No - Not a member of workspace
     * + can_accept_member_requests: No - Workspace is public
     * + can_administrate: No - not an admin
     * + can_decline_member_requests: No - Workspace is public
     * + can_request_to_join: No - Workspace is public
     * + can_join: Yes - Different tenant workspace, but isolation mode is off and workspace is public
     * + can_unshare_resources: No - Not admin of workspace
     * + is_owner: No
     * + is_primary_owner: No
     * + can_update: No - Not admin of workspace
     * + can_delete: No -Not admin of workspace
     *
     * @return void
     */
    public function test_interactor_of_different_tenant_public_workspace_without_isolation_as_non_member(): void {
        $this->setUser($this->tenant_two_user);

        $workspace_generator = $this->get_workspace_generator();
        $workspace = $workspace_generator->create_workspace();

        $interactor = new interactor($workspace, $this->tenant_one_participant->id);

        self::assertFalse($interactor->is_joined());
        self::assertFalse($interactor->can_manage());
        self::assertFalse($interactor->can_leave_workspace());
        self::assertFalse($interactor->can_view_discussions());
        self::assertFalse($interactor->can_view_members());
        self::assertFalse($interactor->can_share_resources());
        self::assertFalse($interactor->can_view_library());
        self::assertFalse($interactor->can_view_workspace());
        self::assertFalse($interactor->can_view_workspace_with_tenant_check());
        self::assertFalse($interactor->can_create_discussions());

        self::assertFalse($interactor->can_accept_member_request());
        self::assertFalse($interactor->can_administrate());
        self::assertFalse($interactor->can_decline_member_request());
        self::assertFalse($interactor->can_request_to_join());

        self::assertFalse($interactor->can_join());
        self::assertFalse($interactor->can_unshare_resources());
        self::assertFalse($interactor->is_owner());
        self::assertFalse($interactor->can_update());
        self::assertFalse($interactor->can_delete());
    }

    /**
     * Explanations of interactor where the actor is tenant_one_participant.
     *
     * + is_joined: Yes - Had joined the workspace
     * + can_manage: No - Not an admin of the workspace
     * + can_leave_workspace: Yes - As a member of workspace
     * + can_view_discussions: No - Different tenant workspace
     * + can_view_members: No - Different tenant workspace
     * + can_share_resources: No - Different tenant workspace
     * + can_view_library: No - Different tenant workspace
     * + can_view_workspace: No - Different tenant workspace
     * + can_view_workspace_with_tenant_check: No - Different tenant workspace
     * + can_create_discussions: No - Different tenant workspace
     * + can_accept_member_requests: No - Workspace is public
     * + can_administrate: No - not an admin
     * + can_decline_member_requests: No - Workspace is public
     * + can_request_to_join: No - Workspace is public
     * + can_join: No - Different tenant workspace, also user is already a member
     * + can_unshare_resources: No - Not admin of workspace
     * + is_owner: No
     * + is_primary_owner: No
     * + can_update: No - Not admin of workspace
     * + can_delete: No -Not admin of workspace
     *
     * @return void
     */
    public function test_interactor_of_different_tenant_public_workspace_with_isolation_as_member(): void {
        $tenant_generator = $this->get_tenant_generator();

        // To run this test, first we will need to make the tenant participant participate to the
        // tenant two, add then a member of the workspace. Afterward, we will revoke the participant
        // from tenant two, which it should help to simulate the test.
        $tenant_generator->set_user_participation(
            $this->tenant_one_participant->id,
            [
                $this->tenant_one_user->tenantid,
                $this->tenant_two_user->tenantid
            ]
        );

        $this->setUser($this->tenant_two_user);

        $workspace_generator = $this->get_workspace_generator();
        $workspace = $workspace_generator->create_workspace();

        $workspace_generator->add_member(
            $workspace,
            $this->tenant_one_participant->id,
            $this->tenant_two_user->id
        );

        $tenant_generator->set_user_participation(
            $this->tenant_one_participant->id,
            [$this->tenant_one_user->tenantid]
        );

        set_config('tenantsisolated', 1);
        $interactor = new interactor($workspace, $this->tenant_one_participant->id);

        self::assertTrue($interactor->is_joined());
        self::assertFalse($interactor->can_manage());
        self::assertTrue($interactor->can_leave_workspace());
        self::assertFalse($interactor->can_view_discussions());
        self::assertFalse($interactor->can_view_members());
        self::assertFalse($interactor->can_share_resources());
        self::assertFalse($interactor->can_view_library());
        self::assertFalse($interactor->can_view_workspace());
        self::assertFalse($interactor->can_view_workspace_with_tenant_check());
        self::assertFalse($interactor->can_create_discussions());

        self::assertFalse($interactor->can_accept_member_request());
        self::assertFalse($interactor->can_administrate());
        self::assertFalse($interactor->can_decline_member_request());
        self::assertFalse($interactor->can_request_to_join());

        self::assertFalse($interactor->can_join());
        self::assertFalse($interactor->can_unshare_resources());
        self::assertFalse($interactor->is_owner());
        self::assertFalse($interactor->can_update());
        self::assertFalse($interactor->can_delete());
    }

    /**
     * Explanations of interactor where the actor is tenant_one_participant.
     *
     * + is_joined: Yes - Had joined the workspace
     * + can_manage: No - Not an admin of the workspace
     * + can_leave_workspace: Yes - As a member of workspace
     * + can_view_discussions: No - As system user to a tenant workspace
     * + can_view_members: No - As system user to a tenant workspace
     * + can_share_resources: No - As system user to a tenant workspace
     * + can_view_library: No - As system user to a tenant workspace
     * + can_view_workspace: No - As system user to a tenant workspace
     * + can_view_workspace_with_tenant_check: No - As system user to a tenant workspace
     * + can_create_discussions: No - As system user to a tenant workspace
     * + can_accept_member_requests: No - Workspace is public
     * + can_administrate: No - not an admin
     * + can_decline_member_requests: No - Workspace is public
     * + can_request_to_join: No - Workspace is public
     * + can_join: No - Had already joined
     * + can_unshare_resources: No - Not admin of workspace
     * + is_owner: No
     * + is_primary_owner: No
     * + can_update: No - Not admin of workspace
     * + can_delete: No -Not admin of workspace
     *
     * @return void
     */
    public function test_interactor_of_different_tenant_public_workspace_without_isolation_as_member(): void {
        $tenant_generator = $this->get_tenant_generator();

        // To run this test, first we will need to make the tenant participant participate to the
        // tenant two, add then a member of the workspace. Afterward, we will revoke the participant
        // from tenant two, which it should help to simulate the test.
        $tenant_generator->set_user_participation(
            $this->tenant_one_participant->id,
            [
                $this->tenant_one_user->tenantid,
                $this->tenant_two_user->tenantid
            ]
        );

        $this->setUser($this->tenant_two_user);

        $workspace_generator = $this->get_workspace_generator();
        $workspace = $workspace_generator->create_workspace();

        $workspace_generator->add_member(
            $workspace,
            $this->tenant_one_participant->id,
            $this->tenant_two_user->id
        );

        $tenant_generator->set_user_participation(
            $this->tenant_one_participant->id,
            [$this->tenant_one_user->tenantid]
        );

        $interactor = new interactor($workspace, $this->tenant_one_participant->id);

        self::assertTrue($interactor->is_joined());
        self::assertFalse($interactor->can_manage());
        self::assertTrue($interactor->can_leave_workspace());
        self::assertFalse($interactor->can_view_discussions());
        self::assertFalse($interactor->can_view_members());
        self::assertFalse($interactor->can_share_resources());
        self::assertFalse($interactor->can_view_library());
        self::assertFalse($interactor->can_view_workspace());
        self::assertFalse($interactor->can_view_workspace_with_tenant_check());
        self::assertFalse($interactor->can_create_discussions());

        self::assertFalse($interactor->can_accept_member_request());
        self::assertFalse($interactor->can_administrate());
        self::assertFalse($interactor->can_decline_member_request());
        self::assertFalse($interactor->can_request_to_join());

        self::assertFalse($interactor->can_join());
        self::assertFalse($interactor->can_unshare_resources());
        self::assertFalse($interactor->is_owner());
        self::assertFalse($interactor->can_update());
        self::assertFalse($interactor->can_delete());
    }

    /**
     * Explanations of interactor where the actor is tenant_one_participant.
     *
     * + is_joined: No - Had not yet joined the workspace
     * + can_manage: No - Not an admin of the workspace
     * + can_leave_workspace: No - Not a member of workspace
     * + can_view_discussions: No - Different tenant workspace
     * + can_view_members: No - Different tenant workspace
     * + can_share_resources: No - Not a member of workspace
     * + can_view_library: No - Different tenant workspace
     * + can_view_workspace: No - Different tenant workspace
     * + can_view_workspace_with_tenant_check: No - Not participating to the same tenant as the workspace
     * + can_create_discussions: No - Not a member of workspace
     * + can_accept_member_requests: No - Workspace is public
     * + can_administrate: No - not an admin
     * + can_decline_member_requests: No - Workspace is public
     * + can_request_to_join: No - Workspace is public
     * + can_join: No - Different tenant workspace.
     * + can_unshare_resources: No - Not admin of workspace
     * + is_owner: No
     * + is_primary_owner: No
     * + can_update: No - Not admin of workspace
     * + can_delete: No -Not admin of workspace
     *
     * @return void
     */
    public function test_interactor_of_different_tenant_private_workspace_with_isolation_as_non_member(): void {
        set_config('tenantsisolated', 1);
        $this->setUser($this->tenant_two_user);

        $workspace_generator = $this->get_workspace_generator();
        $workspace = $workspace_generator->create_private_workspace();

        $interactor = new interactor($workspace, $this->tenant_one_participant->id);

        self::assertFalse($interactor->is_joined());
        self::assertFalse($interactor->can_manage());
        self::assertFalse($interactor->can_leave_workspace());
        self::assertFalse($interactor->can_view_discussions());
        self::assertFalse($interactor->can_view_members());
        self::assertFalse($interactor->can_share_resources());
        self::assertFalse($interactor->can_view_library());
        self::assertFalse($interactor->can_view_workspace());
        self::assertFalse($interactor->can_view_workspace_with_tenant_check());
        self::assertFalse($interactor->can_create_discussions());

        self::assertFalse($interactor->can_accept_member_request());
        self::assertFalse($interactor->can_administrate());
        self::assertFalse($interactor->can_decline_member_request());
        self::assertFalse($interactor->can_request_to_join());

        self::assertFalse($interactor->can_join());
        self::assertFalse($interactor->can_unshare_resources());
        self::assertFalse($interactor->is_owner());
        self::assertFalse($interactor->can_update());
        self::assertFalse($interactor->can_delete());
    }

    /**
     * Explanations of interactor where the actor is tenant_one_participant.
     *
     * + is_joined: No - Had not yet joined the workspace
     * + can_manage: No - Not an admin of the workspace
     * + can_leave_workspace: No - not a member of workspace
     * + can_view_discussions: No - Not a member of workspace, and workspace is private
     * + can_view_members: No - Not a member of workspace, and workspace is private
     * + can_share_resources: No - Not a member of workspace
     * + can_view_library: No - Not a member of workspace, and workspace is private
     * + can_view_workspace: No - As a system user to a tenant workspace
     * + can_view_workspace_with_tenant_check: Yes - Different tenant workspace, but isolation mode is off
     * + can_create_discussions: Not - Not a member of workspace
     * + can_accept_member_requests: No - Not an admin of workspace
     * + can_administrate: No - not an admin
     * + can_decline_member_requests: No - Not an admin of workspace
     * + can_request_to_join: No - As a system user to a tenant workspace
     * + can_join: No - Workspace is private
     * + can_unshare_resources: No - Not admin of workspace
     * + is_owner: No
     * + is_primary_owner: No
     * + can_update: No - Not admin of workspace
     * + can_delete: No -Not admin of workspace
     *
     * @return void
     */
    public function test_interactor_of_different_tenant_private_workspace_without_isolation_as_non_member(): void {
        $this->setUser($this->tenant_two_user);
        $workspace_generator = $this->get_workspace_generator();

        $workspace = $workspace_generator->create_private_workspace();
        $interactor = new interactor($workspace, $this->tenant_one_participant->id);

        self::assertFalse($interactor->is_joined());
        self::assertFalse($interactor->can_manage());
        self::assertFalse($interactor->can_leave_workspace());
        self::assertFalse($interactor->can_view_discussions());
        self::assertFalse($interactor->can_view_members());
        self::assertFalse($interactor->can_share_resources());
        self::assertFalse($interactor->can_view_library());
        self::assertFalse($interactor->can_view_workspace());
        self::assertFalse($interactor->can_view_workspace_with_tenant_check());
        self::assertFalse($interactor->can_create_discussions());

        self::assertFalse($interactor->can_accept_member_request());
        self::assertFalse($interactor->can_administrate());
        self::assertFalse($interactor->can_decline_member_request());
        self::assertFalse($interactor->can_request_to_join());

        self::assertFalse($interactor->can_join());
        self::assertFalse($interactor->can_unshare_resources());
        self::assertFalse($interactor->is_owner());
        self::assertFalse($interactor->can_update());
        self::assertFalse($interactor->can_delete());
    }

    /**
     * Explanations of interactor where the actor is tenant_one_participant.
     *
     * + is_joined: Yes - Had already added to workspace
     * + can_manage: No - Not an admin of the workspace
     * + can_leave_workspace: Yes - As a member of workspace
     * + can_view_discussions: No - Different tenant workspace
     * + can_view_members: No - Different tenant workspace
     * + can_share_resources: No - Not a member of workspace
     * + can_view_library: No - Different tenant workspace
     * + can_view_workspace: No - Different tenant workspace
     * + can_view_workspace_with_tenant_check: No - Not participating to the same tenant as the workspace
     * + can_create_discussions: No - Not a member of workspace
     * + can_accept_member_requests: No - Workspace is public
     * + can_administrate: No - not an admin
     * + can_decline_member_requests: No - Workspace is public
     * + can_request_to_join: No - Workspace is public
     * + can_join: No - Different tenant workspace.
     * + can_unshare_resources: No - Not admin of workspace
     * + is_owner: No
     * + is_primary_owner: No
     * + can_update: No - Not admin of workspace
     * + can_delete: No -Not admin of workspace
     *
     * @return void
     */
    public function test_interactor_of_different_tenant_private_workspace_with_isolation_as_member(): void {
        $tenant_generator = $this->get_tenant_generator();

        // To run this test, first we will need to make the tenant participant participate to the
        // tenant two, add then a member of the workspace. Afterward, we will revoke the participant
        // from tenant two, which it should help to simulate the test.
        $tenant_generator->set_user_participation(
            $this->tenant_one_participant->id,
            [
                $this->tenant_one_user->tenantid,
                $this->tenant_two_user->tenantid
            ]
        );

        $this->setUser($this->tenant_two_user);
        $workspace_generator = $this->get_workspace_generator();

        $workspace = $workspace_generator->create_private_workspace();
        $workspace_generator->create_private_workspace();

        // To do this test, we need to make sure that tenant participant was part of the workspace.
        // before isolation mode is turned on.
        $workspace_generator->add_member(
            $workspace,
            $this->tenant_one_participant->id,
            $this->tenant_two_user->id
        );

        $tenant_generator->set_user_participation(
            $this->tenant_one_participant->id,
            [$this->tenant_one_user->tenantid]
        );

        set_config('tenantsisolated', 1);
        $interactor = new interactor($workspace, $this->tenant_one_participant->id);

        self::assertTrue($interactor->is_joined());
        self::assertFalse($interactor->can_manage());
        self::assertTrue($interactor->can_leave_workspace());
        self::assertFalse($interactor->can_view_discussions());
        self::assertFalse($interactor->can_view_members());
        self::assertFalse($interactor->can_share_resources());
        self::assertFalse($interactor->can_view_library());
        self::assertFalse($interactor->can_view_workspace());
        self::assertFalse($interactor->can_view_workspace_with_tenant_check());
        self::assertFalse($interactor->can_create_discussions());

        self::assertFalse($interactor->can_accept_member_request());
        self::assertFalse($interactor->can_administrate());
        self::assertFalse($interactor->can_decline_member_request());
        self::assertFalse($interactor->can_request_to_join());

        self::assertFalse($interactor->can_join());
        self::assertFalse($interactor->can_unshare_resources());
        self::assertFalse($interactor->is_owner());
        self::assertFalse($interactor->can_update());
        self::assertFalse($interactor->can_delete());
    }

    /**
     * Explanations of interactor where the actor is tenant_one_participant.
     *
     * + is_joined: Yes - Had already added to workspace
     * + can_manage: No - Not an admin of the workspace
     * + can_leave_workspace: No - As a system user to the tenant workspace
     * + can_view_discussions: No - As a system user to the tenant workspace
     * + can_view_members: No - As a system user to the tenant workspace
     * + can_share_resources: No - As a system user to the tenant workspace
     * + can_view_library: No - As a system user to the tenant workspace
     * + can_view_workspace: No - As a system user to the tenant workspace
     * + can_view_workspace_with_tenant_check: No - As a system user to the tenant workspace
     * + can_create_discussions: Yes - No - As a system user to the tenant workspace
     * + can_accept_member_requests: No - Not an admin
     * + can_administrate: No - not an admin
     * + can_decline_member_requests: No - Not an admin
     * + can_request_to_join: No - Had already joined
     * + can_join: No - Had already joined
     * + can_unshare_resources: No - Not admin of workspace
     * + is_owner: No
     * + is_primary_owner: No
     * + can_update: No - Not admin of workspace
     * + can_delete: No -Not admin of workspace
     *
     * @return void
     */
    public function test_interactor_of_different_tenant_private_workspace_without_isolation_as_member(): void {
        $tenant_generator = $this->get_tenant_generator();

        // To run this test, first we will need to make the tenant participant participate to the
        // tenant two, add then a member of the workspace. Afterward, we will revoke the participant
        // from tenant two, which it should help to simulate the test.
        $tenant_generator->set_user_participation(
            $this->tenant_one_participant->id,
            [
                $this->tenant_one_user->tenantid,
                $this->tenant_two_user->tenantid
            ]
        );

        $this->setUser($this->tenant_two_user);
        $workspace_generator = $this->get_workspace_generator();

        $workspace = $workspace_generator->create_private_workspace();
        $workspace_generator->add_member(
            $workspace,
            $this->tenant_one_participant->id,
            $this->tenant_two_user->id
        );

        $tenant_generator->set_user_participation(
            $this->tenant_one_participant->id,
            [$this->tenant_one_user->tenantid]
        );

        $interactor = new interactor($workspace, $this->tenant_one_participant->id);

        self::assertTrue($interactor->is_joined());
        self::assertFalse($interactor->can_manage());
        self::assertTrue($interactor->can_leave_workspace());
        self::assertFalse($interactor->can_view_discussions());
        self::assertFalse($interactor->can_view_members());
        self::assertFalse($interactor->can_share_resources());
        self::assertFalse($interactor->can_view_library());
        self::assertFalse($interactor->can_view_workspace());
        self::assertFalse($interactor->can_view_workspace_with_tenant_check());
        self::assertFalse($interactor->can_create_discussions());

        self::assertFalse($interactor->can_accept_member_request());
        self::assertFalse($interactor->can_administrate());
        self::assertFalse($interactor->can_decline_member_request());
        self::assertFalse($interactor->can_request_to_join());

        self::assertFalse($interactor->can_join());
        self::assertFalse($interactor->can_unshare_resources());
        self::assertFalse($interactor->is_owner());
        self::assertFalse($interactor->can_update());
        self::assertFalse($interactor->can_delete());
    }
}