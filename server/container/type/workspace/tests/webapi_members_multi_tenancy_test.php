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
use totara_webapi\phpunit\webapi_phpunit_helper;
use container_workspace\exception\workspace_exception;
use container_workspace\query\member\sort as member_sort;

class container_workspace_webapi_members_multi_tenancy_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_fetch_members_of_public_workspace_as_non_members_from_different_tenant(): void {
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

        // Log in as first user and create a workspace.
        $this->setUser($user_one->id);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Create list of members for the workspace.
        for ($i = 0; $i < 3; $i++) {
            $user = $generator->create_user();
            $tenant_generator->migrate_user_to_tenant($user->id, $tenant_one->id);

            member::added_to_workspace($workspace, $user->id, false, $user_one->id);
        }

        // Log in as user two and check if user two is able to fetch the list of members.
        $this->setUser($user_two);

        $this->expectException(workspace_exception::class);
        $this->expectExceptionMessage("You don't have permission to view this page.");

        $this->resolve_graphql_query(
            'container_workspace_members',
            [
                'workspace_id' => $workspace->get_id(),
                'sort' => member_sort::get_code(member_sort::RECENT_JOIN)
            ]
        );
    }

    /**
     * @return void
     */
    public function test_fetch_members_of_public_workspace_as_non_member_in_same_tenant(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant_one = $tenant_generator->create_tenant();

        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_one->id);
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant_one->id);

        // Log in as first user and create a workspace.
        $this->setUser($user_one->id);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Create list of members for the workspace.
        for ($i = 0; $i < 3; $i++) {
            $user = $generator->create_user();
            $tenant_generator->migrate_user_to_tenant($user->id, $tenant_one->id);

            member::added_to_workspace($workspace, $user->id, false, $user_one->id);
        }

        // Log in as user two and check if user two is able to fetch the list of members.
        $this->setUser($user_two);
        $result = $this->resolve_graphql_query(
            'container_workspace_members',
            [
                'workspace_id' => $workspace->get_id(),
                'sort' => member_sort::get_code(member_sort::RECENT_JOIN)
            ]
        );

        // 3 normal users plus user one as the owner.
        self::assertCount(4, $result);
    }

    /**
     * @return void
     */
    public function test_fetch_members_of_private_workspace_as_non_member_in_same_tenant(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant_one = $tenant_generator->create_tenant();

        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_one->id);
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant_one->id);

        // Log in as first user and create a workspace.
        $this->setUser($user_one->id);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        // Create list of members for the workspace.
        for ($i = 0; $i < 3; $i++) {
            $user = $generator->create_user();
            $tenant_generator->migrate_user_to_tenant($user->id, $tenant_one->id);

            member::added_to_workspace($workspace, $user->id, false, $user_one->id);
        }

        // Log in as user two and check if user two is able to fetch the list of members.
        $this->setUser($user_two);

        $this->expectException(workspace_exception::class);
        $this->expectExceptionMessage("You don't have permission to view this page.");

        $this->resolve_graphql_query(
            'container_workspace_members',
            [
                'workspace_id' => $workspace->get_id(),
                'sort' => member_sort::get_code(member_sort::RECENT_JOIN)
            ]
        );
    }
}