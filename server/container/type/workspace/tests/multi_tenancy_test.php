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

use container_workspace\query\workspace\query;
use container_workspace\query\workspace\source;
use container_workspace\loader\workspace\loader;

/**
 * Tests to make sure the integration between multi-tenancy and workspaces
 * are working correctly.
 */
class container_workspace_multi_tenancy_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_get_workspaces_from_other_tenants(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant_one = $tenant_generator->create_tenant();
        $tenant_two = $tenant_generator->create_tenant();

        // Add user one to tenant one and user two to tenant two
        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_one->id);
        $user_one->tenantid = $tenant_one->id;
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant_two->id);
        $user_two->tenantid = $tenant_two->id;

        // Now start creating the several workspaces for user_one.
        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $this->setUser($user_one);

        for ($i = 0; $i < 5; $i++) {
            $workspace_generator->create_workspace();
        }

        $this->executeAdhocTasks();

        // Log in as user two and see that if user two is able to see the workspaces from tenant_one
        // created by user one or not.
        $this->setUser($user_two);

        $query = new query(source::OTHER, $user_two->id);
        $paginator = loader::get_workspaces($query);

        // Nope user oen should not not able to see any workspaces, as the current workspaces are only existing within
        // different tenants.
        $this->assertEmpty($paginator->get_items()->all());
        $this->assertEquals(0, $paginator->get_total());
    }

    /**
     * Test to assure that a normal user is able to see the workspace(s) that leave outside of the
     * tenants categories.
     *
     * @return void
     */
    public function test_get_workspaces_from_non_tenants_when_isolation_mode_is_off(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant->id);

        // Set as user two so that we can create a workspace by this user and then check
        // if the user one is able to see the workspace created by this user.
        $this->setUser($user_two);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        for ($i = 0; $i < 5; $i++) {
            $workspace_generator->create_workspace();
        }

        // Now start checking that if user one is able to see other workspaces.
        // As user one should be able, because the workspaces are living outside of tenant.
        $this->setUser($user_one);

        $query = new query(source::OTHER, $user_one->id);
        $paginator = loader::get_workspaces($query);

        $this->assertCount(5, $paginator->get_items()->all());
        $this->assertEquals(5, $paginator->get_total());
    }

    /**
     * Test to assure that a normal user is NOT able to see the workspace(s) that leave outside
     * of the tenants categories.
     *
     * @return void
     */
    public function test_get_workspaces_from_non_tenants_when_isolation_mode_is_on(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        // Assign user one to this tenant
        $tenant = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant->id);

        // Now start creating workspaces that live outside of tenants by user_two.
        $this->setUser($user_two);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        for ($i = 0; $i < 5; $i++) {
            $workspace_generator->create_workspace();
        }

        // Now check that if the user_one is still able to see the workspaces
        // that live outside tenants or not.
        set_config('tenantsisolated', 1);
        $this->setUser($user_one);

        $query = new query(source::OTHER, $user_one->id);
        $paginator = loader::get_workspaces($query);

        $this->assertEmpty($paginator->get_items()->all());
        $this->assertEquals(0, $paginator->get_total());
    }
}
