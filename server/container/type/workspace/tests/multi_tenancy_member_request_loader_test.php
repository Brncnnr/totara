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
use container_workspace\query\member\member_request_query;
use container_workspace\loader\member\member_request_loader;

class container_workspace_multi_tenancy_member_request_loader_testcase extends advanced_testcase {
    /**
     * Given scenarios where the one of the users who made request to join to the workspace moved
     * to a different tenant.
     *
     * @return void
     */
    public function test_fetch_member_requests_exclude_old_requests_from_different_tenant(): void {
        global $DB;

        $generator = $this->getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant_one = $tenant_generator->create_tenant();
        $user_one = $generator->create_user();

        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_one->id);
        $user_one->tenantid = $tenant_one->id;
        $this->setUser($user_one);

        // Create a workspace.
        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        // Create new two users and add request to join the workspace.
        $user_two = $generator->create_user();
        $user_three = $generator->create_user();

        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant_one->id);
        $tenant_generator->migrate_user_to_tenant($user_three->id, $tenant_one->id);

        $workspace_id = $workspace->get_id();
        $user_two_request = member_request::create($workspace_id, $user_two->id);
        $user_three_request = member_request::create($workspace_id, $user_three->id);

        $query = new member_request_query($workspace_id);
        $same_tenant_paginator = member_request_loader::get_member_requests($query);

        $this->assertEquals(2, $same_tenant_paginator->get_total());

        /** @var member_request[] $requests */
        $requests = $same_tenant_paginator->get_items()->all();
        foreach ($requests as $request) {
            $this->assertTrue(
                in_array(
                    $request->get_id(),
                    [
                        $user_two_request->get_id(),
                        $user_three_request->get_id()
                    ]
                )
            );

            $this->assertTrue(
                in_array(
                    $request->get_user_id(),
                    [
                        $user_two_request->get_user_id(),
                        $user_three_request->get_user_id()
                    ]
                )
            );
        }

        // Move user two to to different tenant.
        $tenant_two = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant_two->id);

        // Refetch after move, however we still have to make sure if the request still exist.
        $different_tenant_paginator = member_request_loader::get_member_requests($query);
        $this->assertEquals(1, $different_tenant_paginator->get_total());

        $requests = $different_tenant_paginator->get_items()->all();
        $first_request = reset($requests);

        $this->assertEquals($user_three_request->get_id(), $first_request->get_id());
        $this->assertNotEquals($user_two_request->get_id(), $first_request->get_id());

        // Make sure that the request still exist.
        $this->assertTrue(
            $DB->record_exists(
                'workspace_member_request',
                ['id' => $user_two_request->get_id()]
            )
        );
    }

    /**
     * @return void
     */
    public function test_fetch_member_requests_from_a_workspace_in_system_level(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $this->setUser($user_one);

        $workspace = $workspace_generator->create_private_workspace();

        // Create users within a tenant and back-doored the member requests.
        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();
        $tenant = $tenant_generator->create_tenant();

        $workspace_id = $workspace->get_id();
        $tenant_requests = [];

        for ($i = 0; $i < 2; $i++) {
            $user = $generator->create_user();
            $request = member_request::create($workspace_id , $user->id);

            $tenant_requests[] = $request->get_id();
            $tenant_generator->migrate_user_to_tenant($user->id, $tenant->id);
        }

        // Create several system users.
        $system_level_requests = [];
        for ($i = 0; $i < 2; $i++) {
            $user = $generator->create_user();
            $request = member_request::create($workspace_id, $user->id);

            $system_level_requests[] = $request->get_id();
        }

        $query = new member_request_query($workspace_id);
        $paginator = member_request_loader::get_member_requests($query);

        // Isolation mode is off - hence we can still see the system level users.
        $this->assertEquals(4, $paginator->get_total());

        /** @var member_request[] $requests */
        $requests = $paginator->get_items()->all();

        foreach ($requests as $request) {
            $this->assertTrue(in_array($request->get_id(), array_merge($system_level_requests, $tenant_requests)));
        }

        // However, if we turn isolation mode on - then tenant member will not be seen anymore.
        set_config('tenantsisolated', 1);
        $new_request_paginator = member_request_loader::get_member_requests($query);

        $this->assertEquals(2, $new_request_paginator->get_total());
        $requests = $new_request_paginator->get_items()->all();

        foreach ($requests as $request) {
            $this->assertTrue(in_array($request->get_id(), $system_level_requests));
            $this->assertFalse(in_array($request->get_id(), $tenant_requests));
        }
    }
}
