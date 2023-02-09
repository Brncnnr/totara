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

use container_workspace\loader\member\non_member_loader;
use container_workspace\query\member\non_member_query;
use core_phpunit\testcase;

/**
 * @deprecated since Totara 16. container_workspace\loader\member\non_member_loader
 * is no longer used.
 *
 * @group container_workspace
 */
class container_workspace_multi_tenancy_non_member_loader_testcase extends testcase {
    /**
     * @return void
     */
    public function test_get_non_member_users_from_same_tenant(): void {
        $generator = $this->getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant_one = $tenant_generator->create_tenant();
        $tenant_two = $tenant_generator->create_tenant();

        // Create this special users within a tenant.
        $user_one = $generator->create_user();
        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_one->id);
        $user_one->tenantid = $tenant_one->id;

        // Create 5 users within tenant one and 7 users within tenant 2.
        $non_members_tenant_one = [];
        for ($i = 0; $i < 5; $i++) {
            $user = $generator->create_user();
            $non_members_tenant_one[] = $user->id;

            $tenant_generator->migrate_user_to_tenant($user->id, $tenant_one->id);
        }

        $non_members_tenant_two = [];
        for ($i = 0; $i < 7; $i++) {
            $user = $generator->create_user();
            $non_members_tenant_two[] = $user->id;

            $tenant_generator->migrate_user_to_tenant($user->id, $tenant_two->id);
        }

        // Create a workspace as user_one.
        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        // This specific workspace is a part of tenant one. Hence when we are looking for non member for thi
        // workspace - we should be expecting 5 users created from the step above.
        $workspace = $workspace_generator->create_workspace();

        $query = new non_member_query($workspace->get_id());
        $paginator = non_member_loader::get_non_members($query);
        $this->assert_deprecated();

        $this->assertEquals(5, $paginator->get_total());
        $users = $paginator->get_items()->all();

        $this->assertCount(5, $users);
        foreach ($users as $user) {
            $this->assertTrue(in_array($user->id, $non_members_tenant_one));
            $this->assertFalse(in_array($user->id, $non_members_tenant_two));
        }
    }

    /**
     * @return void
     */
    public function test_get_non_member_users_from_tenant_participant(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant_one = $tenant_generator->create_tenant();
        $tenant_two = $tenant_generator->create_tenant();

        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_one->id);
        $user_one->tenantid = $tenant_one->id;

        // Create users participant of tenants
        $participant_users = [];
        for ($i = 0; $i < 5; $i++) {
            $user = $generator->create_user();
            $participant_users[] = $user->id;

            $tenant_generator->set_user_participation($user->id, [$tenant_one->id]);
        }

        // Create system level users - without participating any tenants
        $system_level_users = [];
        for ($i = 0; $i < 2; $i++) {
            $user = $generator->create_user();
            $system_level_users[] = $user->id;
        }

        // Create tenant one users.
        $non_members_tenant_one = [];
        for ($i = 0; $i < 2; $i++) {
            $user = $generator->create_user();
            $non_members_tenant_one[] = $user->id;

            $tenant_generator->migrate_user_to_tenant($user->id, $tenant_one->id);
        }

        // Create tenant two users
        $non_members_tenant_two = [];
        for ($i = 0; $i < 5; $i++) {
            $user = $generator->create_user();
            $non_members_tenant_two[] = $user->id;

            $tenant_generator->migrate_user_to_tenant($user->id, $tenant_two->id);
        }

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $query = new non_member_query($workspace->get_id());
        $paginator = non_member_loader::get_non_members($query);
        $this->assert_deprecated();

        $this->assertEquals(7, $paginator->get_total());
        $users = $paginator->get_items()->all();

        foreach ($users as $user) {
            $this->assertTrue(in_array($user->id, array_merge($participant_users, $non_members_tenant_one)));
            $this->assertFalse(in_array($user->id, array_merge($system_level_users, $non_members_tenant_two)));
        }
    }

    /**
     * @return void
     */
    public function test_get_non_member_users_for_non_tenant_workspace(): void {
        $this->setAdminUser();
        $generator = $this->getDataGenerator();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        // This is non-tenant workspace
        $workspace = $workspace_generator->create_workspace();

        // Create 5 users for tenant one, 5 users for tenant two and 2 system level users see if
        // the workspace is able to see them all.

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant_one = $tenant_generator->create_tenant();
        $tenant_two = $tenant_generator->create_tenant();

        // 5 users for tenant one.
        $non_members = [];

        for ($i = 0; $i < 10; $i++) {
            $user = $generator->create_user();
            $non_members[] = $user->id;

            $tenant_id = ($i % 2) ? $tenant_one->id : $tenant_two->id;
            $tenant_generator->migrate_user_to_tenant($user->id, $tenant_id);
        }

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $non_members[] = $user_one->id;
        $non_members[] = $user_two->id;

        $query = new non_member_query($workspace->get_id());
        $paginator = non_member_loader::get_non_members($query);
        $this->assert_deprecated();

        $this->assertEquals(12, $paginator->get_total());

        $users = $paginator->get_items()->all();
        $this->assertCount(12, $users);

        foreach ($users as $user) {
            $this->assertTrue(in_array($user->id, $non_members));
        }
    }

    private function assert_deprecated(): void {
        $messages = [
            'Class non_member_query is deprecated; use container_workspace\data_providers\non_members instead',
            'Class non_member_loader is deprecated; use container_workspace\data_providers\non_members instead'
        ];

        $this->assertDebuggingCalled($messages, DEBUG_DEVELOPER);
    }
}
