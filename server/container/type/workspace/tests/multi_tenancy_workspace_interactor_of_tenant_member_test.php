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

class container_workspace_multi_tenancy_workspace_interactor_of_tenant_member_testcase extends advanced_testcase {
    /**
     * @var stdClass|null
     */
    private $tenant_one_user;

    /**
     * @var stdClass|null
     */
    private $tenant_one_participant;

    /**
     * @return void
     */
    protected function setUp(): void {
        $generator = self::getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant = $tenant_generator->create_tenant();

        $this->tenant_one_user = $generator->create_user([
            'firstname' => uniqid('tenant_one_user_'),
            'lastname' => uniqid('tenant_one_user_'),
            'tenantid' => $tenant->id
        ]);

        $this->tenant_one_participant = $generator->create_user([
            'firstname' => uniqid('tenant_one_participant_'),
            'lastname' => uniqid('tenant_one_participant_')
        ]);

        $tenant_generator->set_user_participation(
            $this->tenant_one_participant->id,
            [$tenant->id]
        );
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        $this->tenant_one_user = null;
        $this->tenant_one_participant = null;
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
     * @return void
     */
    public function test_interactor_of_tenant_participant_public_workspace_with_isolation_as_non_member(): void {
        set_config('tenantsisolated', 1);
        $this->setUser($this->tenant_one_participant);

        $workspace_generator = $this->get_workspace_generator();
        $workspace = $workspace_generator->create_workspace();

        $interactor = new interactor($workspace, $this->tenant_one_user->id);

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
     * @return void
     */
    public function test_interactor_of_tenant_participant_public_workspace_without_isolation_as_non_member(): void {
        $this->setUser($this->tenant_one_participant);

        $workspace_generator = $this->get_workspace_generator();
        $workspace = $workspace_generator->create_workspace();

        $interactor = new interactor($workspace, $this->tenant_one_user->id);

        self::assertFalse($interactor->is_joined());
        self::assertFalse($interactor->can_manage());
        self::assertFalse($interactor->can_leave_workspace());
        self::assertTrue($interactor->can_view_discussions());
        self::assertFalse($interactor->can_share_resources());
        self::assertTrue($interactor->can_view_library());
        self::assertTrue($interactor->can_view_members());
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
     * @return void
     */
    public function test_interactor_of_tenant_participant_public_workspace_with_isolation_as_member(): void {
        $this->setUser($this->tenant_one_participant);
        $workspace_generator = $this->get_workspace_generator();

        // To test this scenario, we will have to make tenant one user as a member of this workspace
        // before the isolation mode turned on.
        $workspace = $workspace_generator->create_workspace();
        $workspace_generator->add_member(
            $workspace,
            $this->tenant_one_user->id,
            $this->tenant_one_participant->id
        );

        set_config('tenantsisolated', 1);
        $interactor = new interactor($workspace, $this->tenant_one_user->id);

        self::assertTrue($interactor->is_joined());
        self::assertFalse($interactor->can_manage());
        self::assertTrue($interactor->can_leave_workspace());
        self::assertFalse($interactor->can_view_discussions());
        self::assertFalse($interactor->can_share_resources());
        self::assertFalse($interactor->can_view_library());
        self::assertFalse($interactor->can_view_members());
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
     * @return void
     */
    public function test_interactor_of_tenant_participant_public_workspace_without_isolation_as_member(): void {
        $this->setUser($this->tenant_one_participant);
        $workspace_generator = $this->get_workspace_generator();

        // To test this scenario, we will have to make tenant one user as a member of this workspace
        // before the isolation mode turned on.
        $workspace = $workspace_generator->create_workspace();
        $workspace_generator->add_member(
            $workspace,
            $this->tenant_one_user->id,
            $this->tenant_one_participant->id
        );

        $interactor = new interactor($workspace, $this->tenant_one_user->id);

        self::assertTrue($interactor->is_joined());
        self::assertFalse($interactor->can_manage());
        self::assertTrue($interactor->can_leave_workspace());
        self::assertTrue($interactor->can_view_discussions());
        self::assertTrue($interactor->can_share_resources());
        self::assertTrue($interactor->can_view_library());
        self::assertTrue($interactor->can_view_members());
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
     * @return void
     */
    public function test_interactor_of_tenant_participant_private_workspace_with_isolation_as_non_member(): void {
        set_config('tenantsisolated', 1);
        $this->setUser($this->tenant_one_participant);

        $workspace_generator = $this->get_workspace_generator();
        $workspace = $workspace_generator->create_private_workspace();

        $interactor = new interactor($workspace, $this->tenant_one_user->id);

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
     * @return void
     */
    public function test_interactor_of_tenant_participant_private_workspace_without_isolation_as_non_member(): void {
        $this->setUser($this->tenant_one_participant);

        $workspace_generator = $this->get_workspace_generator();
        $workspace = $workspace_generator->create_private_workspace();

        $interactor = new interactor($workspace, $this->tenant_one_user->id);

        self::assertFalse($interactor->is_joined());
        self::assertFalse($interactor->can_manage());
        self::assertFalse($interactor->can_leave_workspace());
        self::assertFalse($interactor->can_view_discussions());
        self::assertFalse($interactor->can_share_resources());
        self::assertFalse($interactor->can_view_library());
        self::assertFalse($interactor->can_view_members());
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
     * @return void
     */
    public function test_interactor_of_tenant_participant_private_workspace_with_isolation_as_member(): void {
        $this->setUser($this->tenant_one_participant);
        $workspace_generator = $this->get_workspace_generator();

        // To test this scenario, we will have to make tenant one user as a member of this workspace
        // before the isolation mode turned on.
        $workspace = $workspace_generator->create_private_workspace();
        $workspace_generator->add_member(
            $workspace,
            $this->tenant_one_user->id,
            $this->tenant_one_participant->id
        );

        set_config('tenantsisolated', 1);
        $interactor = new interactor($workspace, $this->tenant_one_user->id);

        self::assertTrue($interactor->is_joined());
        self::assertFalse($interactor->can_manage());
        self::assertTrue($interactor->can_leave_workspace());
        self::assertFalse($interactor->can_view_discussions());
        self::assertFalse($interactor->can_share_resources());
        self::assertFalse($interactor->can_view_library());
        self::assertFalse($interactor->can_view_members());
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
     * @return void
     */
    public function test_interactor_of_tenant_participant_private_workspace_without_isolation_as_member(): void {
        $this->setUser($this->tenant_one_participant);
        $workspace_generator = $this->get_workspace_generator();

        // To test this scenario, we will have to make tenant one user as a member of this workspace
        // before the isolation mode turned on.
        $workspace = $workspace_generator->create_private_workspace();
        $workspace_generator->add_member(
            $workspace,
            $this->tenant_one_user->id,
            $this->tenant_one_participant->id
        );

        $interactor = new interactor($workspace, $this->tenant_one_user->id);

        self::assertTrue($interactor->is_joined());
        self::assertFalse($interactor->can_manage());
        self::assertTrue($interactor->can_leave_workspace());
        self::assertTrue($interactor->can_view_discussions());
        self::assertTrue($interactor->can_share_resources());
        self::assertTrue($interactor->can_view_library());
        self::assertTrue($interactor->can_view_members());
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
}