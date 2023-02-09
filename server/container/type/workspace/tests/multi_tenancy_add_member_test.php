
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

use container_workspace\member\member;

class container_workspace_multi_tenancy_add_member_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_add_member_from_different_tenant(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant_one = $tenant_generator->create_tenant();
        $tenant_two = $tenant_generator->create_tenant();

        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_one->id);
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant_two->id);

        // Login as first user and create workspace then add user to the workspace.
        $user_one->tenantid = $tenant_one->id;
        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Add user two to this workspace.
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Target user is not able to see the workspace');

        member::added_to_workspace($workspace, $user_two->id);
    }

    /**
     * @return void
     */
    public function test_add_member_as_participant(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant_one = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_one->id);
        $tenant_generator->set_user_participation($user_two->id, [$tenant_one->id]);

        // Login as first user and add the user participant.
        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        $user_two_member = member::added_to_workspace($workspace, $user_two->id);

        $this->assertTrue($user_two_member->is_active());
        $this->assertFalse($user_two_member->is_suspended());
        $this->assertNotEmpty($user_two_member->get_id());
        $this->assertEquals($user_two->id, $user_two_member->get_user_id());
    }

    /**
     * @return void
     */
    public function test_add_member_to_a_system_workspace(): void {
        $this->setAdminUser();
        $generator = $this->getDataGenerator();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        // Create two users from two different tenants.
        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant_one = $tenant_generator->create_tenant();
        $tenant_two = $tenant_generator->create_tenant();

        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_one->id);
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant_two->id);

        // Add these two users to the workspace.
        $user_one_member = member::added_to_workspace($workspace, $user_one->id);
        $this->assertTrue($user_one_member->is_active());
        $this->assertNotEmpty($user_one_member->get_id());
        $this->assertEquals($user_one->id, $user_one_member->get_user_id());

        $user_two_member = member::added_to_workspace($workspace, $user_two->id);
        $this->assertTrue($user_two_member->is_active());
        $this->assertNotEmpty($user_two_member->get_id());
        $this->assertEquals($user_two->id, $user_two_member->get_user_id());
    }

    /**
     * @return void
     */
    public function test_add_members_to_system_level_when_isolation_mode_is_on(): void {
        $this->setAdminUser();
        $generator = $this->getDataGenerator();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        // Create a user from a tenant
        $user_one = $generator->create_user();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant_one = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_one->id);

        set_config('tenantsisolated', 1);
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Target user is not able to see the workspace');

        member::added_to_workspace($workspace, $user_one->id);
    }
}
