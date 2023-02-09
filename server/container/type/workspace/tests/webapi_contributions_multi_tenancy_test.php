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
use totara_engage\access\access;

class container_workspace_webapi_contributions_multi_tenancy_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_fetch_contributions_of_public_workspace_by_non_member_in_different_tenant(): void {
        $generator = $this->getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant_one = $tenant_generator->create_tenant();
        $tenant_two = $tenant_generator->create_tenant();

        $user_one = $generator->create_user(['tenantid' => $tenant_one->id]);
        $user_two = $generator->create_user(['tenantid' => $tenant_two->id]);

        // Log in as user one and start creating the workspace.
        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Log in as second user and start fetching the contributions.
        $this->setUser($user_two);
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Cannot fetch the workspace's library");

        $this->resolve_graphql_query(
            'container_workspace_contributions',
            [
                'workspace_id' => $workspace->get_id(),
                'area' => 'adder',
                'filter' => [
                    'access' => access::get_code(access::PUBLIC),
                    'type' => 'totara_playlist'
                ],
                'footnotes' => []
            ]
        );
    }

    /**
     * @return void
     */
    public function test_fetch_contributions_of_public_workspace_by_non_member_in_same_tenant(): void {
        $generator = $this->getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant = $tenant_generator->create_tenant();

        $user_one = $generator->create_user(['tenantid' => $tenant->id]);
        $user_two = $generator->create_user(['tenantid' => $tenant->id]);

        // Log in as user one and start creating the workspace.
        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Log in as second user and start fetching the contributions.
        $this->setUser($user_two);

        $result = $this->resolve_graphql_query(
            'container_workspace_contributions',
            [
                'workspace_id' => $workspace->get_id(),
                'area' => 'adder',
                'filter' => [
                    'access' => access::get_code(access::PUBLIC),
                    'type' => 'totara_playlist'
                ],
                'footnotes' => []
            ]
        );

        self::assertNotEmpty($result);
        self::assertIsArray($result);

        self::assertArrayHasKey('cards', $result);
        self::assertArrayHasKey('cursor', $result);

        self::assertEmpty($result['cards']);
    }
}