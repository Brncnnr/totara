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

use container_workspace\local\workspace_helper;

class container_workspace_multi_tenancy_update_primary_owner_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_update_primary_owner_as_someone_from_different_tenant(): void {
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

        // Log in as first user and create a workspace.
        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Start update to user's two. And check the error it return.
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("New owner does not have ability to access the workspace");

        workspace_helper::update_workspace_primary_owner($workspace, $user_two->id);
    }
}
