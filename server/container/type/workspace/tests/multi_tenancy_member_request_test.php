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

use container_workspace\member\member_request;

class container_workspace_multi_tenancy_member_request_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_accept_member_request_from_different_tenant(): void {
        global $DB;
        $generator = $this->getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant_one = $tenant_generator->create_tenant();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_one->id);
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant_one->id);

        // Log in as user one and create a private workspace.
        $user_one->tenantid = $tenant_one->id;
        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        $member_request = member_request::create($workspace->get_id(), $user_two->id);

        $this->assertTrue($DB->record_exists('workspace_member_request', ['id' => $member_request->get_id()]));
        $this->assertEquals($user_two->id, $member_request->get_user_id());

        // Now move user two to different tenant.
        $tenant_two = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant_two->id);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The requester is not able to see the workspace anymore");
        $member_request->accept($user_one->id);
    }

    /**
     * @return void
     */
    public function test_create_member_request_from_different_tenant(): void {
        $generator = $this->getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant_one = $tenant_generator->create_tenant();
        $tenant_two = $tenant_generator->create_tenant();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_one->id);
        $user_one->tenantid = $tenant_one->id;
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant_two->id);
        $user_two->tenantid = $tenant_two->id;

        // Log in as first user and create a private workspace
        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        // Log in as second user and check if the user is able to create the request or not.
        $this->setUser($user_two);
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("User is not able to see the workspace");

        member_request::create($workspace->get_id());
    }

    /**
     * @return void
     */
    public function test_create_member_request_with_system_level_workspace(): void {
        global $DB;

        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant->id);

        // Log in as first user and create a system level workspace.
        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        $member_request = member_request::create($workspace->get_id(), $user_two->id);
        $this->assertEquals($user_two->id, $member_request->get_user_id());
        $this->assertTrue($DB->record_exists('workspace_member_request', ['id' => $member_request->get_id()]));
    }

    /**
     * @return void
     */
    public function test_accept_member_request_with_system_level_workspace(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant->id);

        // Log in as first user and create a system level workspace.
        $this->setUser($user_one);
        $workspace = $workspace_generator->create_private_workspace();

        $member_request = member_request::create($workspace->get_id(), $user_two->id);
        $this->assertFalse($member_request->is_accepted());
        $this->assertFalse($member_request->is_declined());

        $member_request->accept();
        $this->assertTrue($member_request->is_accepted());
    }

    /**
     * @return void
     */
    public function test_create_member_request_with_system_level_workspace_and_isolation_mode_on(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant->id);

        set_config('tenantsisolated', 1);

        // Log in as first user and create a system level workspace.
        $this->setUser($user_one);
        $workspace = $workspace_generator->create_private_workspace();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("User is not able to see the workspace");
        member_request::create($workspace->get_id(), $user_two->id);
    }

    /**
     * @return void
     */
    public function test_accept_member_request_with_system_level_workspace_and_isolation_mode_on(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant->id);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $this->setUser($user_one);

        $workspace = $workspace_generator->create_private_workspace();
        $member_request = member_request::create($workspace->get_id(), $user_two->id);

        $this->assertFalse($member_request->is_accepted());
        $this->assertEquals($user_two->id, $member_request->get_user_id());
        // Turn on isolation mode and check if the request is able to be approved or not.

        set_config('tenantsisolated', 1);
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The requester is not able to see the workspace anymore");

        $member_request->accept();
    }
}
