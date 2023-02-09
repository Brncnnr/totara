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

use container_workspace\member\member;
use core\webapi\execution_context;
use totara_webapi\graphql;

class container_workspace_multi_tenancy_access_testcase extends advanced_testcase {
    /**
     * Test to make sure that you are still able to see the system level workspace, despite of the owner
     * is living within different tenant.
     *
     * This scenario is only happening when both of the users used to be within the system level.
     *
     * @return void
     */
    public function test_get_system_level_workspace_created_by_system_user_before_moved_to_tenant(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $this->setUser($user_one);

        $workspace = $workspace_generator->create_workspace();

        // Join as member by user two.
        member::join_workspace($workspace, $user_two->id);

        // Both of the user is still within the workspace. Therefore when moving these users to tenant, it will not
        // affect their visibility.

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant_one = $tenant_generator->create_tenant();
        $tenant_two = $tenant_generator->create_tenant();

        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_one->id);
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant_two->id);

        $ec = execution_context::create('ajax', 'container_workspace_get_workspace');
        $result = graphql::execute_operation($ec, [
            'id' => $workspace->get_id(),
            'theme' => 'ventura',
        ]);

        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);

        $this->assertArrayHasKey('workspace', $result->data);
    }
}