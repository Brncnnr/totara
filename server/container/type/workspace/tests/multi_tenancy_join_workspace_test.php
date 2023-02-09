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

class container_workspace_multi_tenancy_join_workspace_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_join_workspace_as_different_user_from_different_tenant(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant_one = $tenant_generator->create_tenant();
        $tenant_two = $tenant_generator->create_tenant();

        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_one->id);
        $user_one->tenantid = $tenant_one->id;

        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant_two->id);
        $user_two->tenantid = $tenant_two->id;

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $this->setUser($user_one);
        $workspace = $workspace_generator->create_workspace();

        // Login as user two and join the workspace - which it should yield errors.
        $this->setUser($user_two);
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Cannot join the workspace that is not in the same tenant");

        member::join_workspace($workspace, $user_two->id);
    }

    /**
     * @return void
     */
    public function test_join_workspace_as_same_user_within_same_tenant(): void {
        $generator = $this->getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();
        $tenant_one = $tenant_generator->create_tenant();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_one->id);
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant_one->id);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $this->setUser($user_one);
        $workspace = $workspace_generator->create_workspace();

        // Login as user two and check if user two is able to join the workspace.
        $this->setUser($user_two);
        $user_two_member = member::join_workspace($workspace, $user_two->id);

        $this->assertNotEmpty($user_two_member->get_id());
        $this->assertTrue($user_two_member->is_active());
        $this->assertFalse($user_two_member->is_suspended());
    }
}
