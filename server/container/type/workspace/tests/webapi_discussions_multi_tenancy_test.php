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

use totara_webapi\phpunit\webapi_phpunit_helper;
use container_workspace\query\discussion\sort as discussion_sort;
use container_workspace\member\member;

class container_workspace_webapi_discussions_multi_tenancy_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_fetch_discussions_from_public_workspace_as_non_member_from_different_tenant(): void {
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

        // Create a workspace by user one.
        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $workspace_id = $workspace->get_id();

        // Generate 5 discussions.
        for ($i = 0; $i < 5; $i++) {
            $workspace_generator->create_discussion($workspace_id);
        }

        // Log in as second user and check if user two is able to fetch discussions.
        $this->setUser($user_two);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Cannot get the list of discussions');

        $this->resolve_graphql_query(
            'container_workspace_discussions',
            [
                'workspace_id' => $workspace_id,
                'sort' => discussion_sort::get_code(discussion_sort::DATE_POSTED),
            ]
        );
    }

    /**
     * @return void
     */
    public function test_fetch_discussions_from_public_workspace_as_an_old_member_moved_to_different_tenant(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant_one = $tenant_generator->create_tenant();

        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_one->id);
        $user_one->tenantid = $tenant_one->id;
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant_one->id);
        $user_two->tenantid = $tenant_one->id;

        // Create a workspace by user one.
        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $workspace_id = $workspace->get_id();

        // Generate 5 discussions.
        for ($i = 0; $i < 5; $i++) {
            $workspace_generator->create_discussion($workspace_id);
        }

        // Log in as second user and check if user two is able to fetch discussions.
        $this->setUser($user_two);
        $member = member::added_to_workspace($workspace, $user_two->id, false, $user_one->id);

        // Fetch the list of discussions when user two is still in the same tenant.
        $result = $this->resolve_graphql_query(
            'container_workspace_discussions',
            [
                'workspace_id' => $workspace_id,
                'sort' => discussion_sort::get_code(discussion_sort::DATE_POSTED)
            ]
        );

        $this->assertCount(5, $result);

        // Move user two to new tenant, and check if user two is still access to the discussions or not.
        $tenant_two = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant_two->id);

        // Even being moved to different tenant, user two is still an active member of a workspace.
        $member->reload();
        $this->assertTrue($member->is_active());
        $this->assertFalse($member->is_suspended());

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Cannot get the list of discussions");

        $this->resolve_graphql_query(
            'container_workspace_discussions',
            [
                'workspace_id' => $workspace_id,
                'sort' => discussion_sort::get_code(discussion_sort::RECENT)
            ]
        );
    }
}
