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
use container_workspace\webapi\resolver\query\workspaces;
use container_workspace\query\workspace\access;
use container_workspace\workspace;

class container_workspace_webapi_multi_tenancy_find_workspaces_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @var stdClass|null
     */
    private $tenant_one_user_one;

    /**
     * @var stdClass|null
     */
    private $tenant_one_user_two;

    /**
     * @var stdClass|null
     */
    private $tenant_two_user;

    /**
     * @var stdClass|null
     */
    private $system_user;

    /**
     * @var stdClass|null
     */
    private $tenant_one_participant;

    /**
     * @return void
     */
    protected function setUp(): void {
        $generator = $this->getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant_one = $tenant_generator->create_tenant();
        $tenant_two = $tenant_generator->create_tenant();

        $this->tenant_one_user_one = $generator->create_user([
            'firstname' => uniqid('tenant_one_user_one_'),
            'lastname' => uniqid('tenant_one_user_one_'),
            'tenantid' => $tenant_one->id
        ]);

        $this->tenant_one_user_two = $generator->create_user([
            'firstname' => uniqid('tenant_one_user_two_'),
            'lastname' => uniqid('tenant_one_user_two_'),
            'tenantid' => $tenant_one->id
        ]);

        $this->tenant_two_user = $generator->create_user([
            'firstname' => uniqid('tenant_two_user_'),
            'lastname' => uniqid('tenant_two_user_'),
            'tenantid' => $tenant_two->id
        ]);

        $this->system_user = $generator->create_user([
            'firstname' => uniqid('system_user_'),
            'lastname' => uniqid('system_user_')
        ]);

        $this->tenant_one_participant = $generator->create_user([
            'firstname' => uniqid('tenant_one_participant_'),
            'lastname' => uniqid('tenant_one_participant_')
        ]);

        $tenant_generator->set_user_participation(
            $this->tenant_one_participant->id,
            [$tenant_one->id]
        );
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        $this->tenant_one_user_one = null;
        $this->tenant_one_user_two = null;
        $this->tenant_two_user = null;
        $this->system_user = null;
        $this->tenant_one_participant = null;
    }

    /**
     * @return \container_workspace\testing\generator
     */
    private function get_workspace_generator(): \container_workspace\testing\generator {
        $generator = $this->getDataGenerator();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        return $workspace_generator;
    }

    /**
     * @return void
     */
    public function test_find_tenant_two_workspaces_as_tenant_one_member_with_isolation(): void {
        set_config('tenantsisolated', 1);
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($this->tenant_two_user);
        $workspace_generator->create_workspace();

        // Log in as tenant one user and start fetch fro tenant two workspace.
        $this->setUser($this->tenant_one_user_one);
        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(workspaces::class),
            ['access' => access::get_code(access::PUBLIC)]
        );

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    /**
     * @return void
     */
    public function test_find_tenant_two_workspaces_as_tenant_one_member_without_isolation(): void {
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($this->tenant_two_user);
        $workspace_generator->create_workspace();

        // Log in as tenant one user and start fetching for tenant two workspace.
        $this->setUser($this->tenant_one_user_one);
        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(workspaces::class),
            ['access' => access::get_code(access::PUBLIC)]
        );

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    /**
     * @return void
     */
    public function test_find_system_workspaces_as_tenant_one_member_with_isolation(): void {
        set_config('tenantsisolated', 1);
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($this->system_user);
        $workspace_generator->create_workspace();

        $this->setUser($this->tenant_one_user_one);
        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(workspaces::class),
            ['access' => access::get_code(access::PUBLIC)]
        );

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    /**
     * @return void
     */
    public function test_find_system_workspaces_as_tenant_one_member_without_isolation(): void {
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($this->system_user);
        $workspace = $workspace_generator->create_workspace();

        $this->setUser($this->tenant_one_user_one);
        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(workspaces::class),
            ['access' => access::get_code(access::PUBLIC)]
        );

        self::assertIsArray($result);
        self::assertCount(1, $result);

        // We are expecting that the tenant member is able to see system workspace.
        /** @var workspace $first_result */
        $first_result = reset($result);
        self::assertInstanceOf(workspace::class, $first_result);
        self::assertEquals($workspace->get_id(), $first_result->get_id());
    }

    /**
     * @return void
     */
    public function test_find_tenant_participant_workspaces_as_tenant_member_with_isolation(): void {
        set_config('tenantsisolated', 1);
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($this->tenant_one_participant);
        $workspace_generator->create_workspace();

        $this->setUser($this->tenant_one_user_one);
        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(workspaces::class),
            ['access' => access::get_code(access::PUBLIC)]
        );

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    /**
     * @return void
     */
    public function test_find_tenant_participant_workspaces_as_tenant_member_without_isolation(): void {
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($this->tenant_one_participant);
        $workspace = $workspace_generator->create_workspace();

        $this->setUser($this->tenant_one_user_one);
        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(workspaces::class),
            ['access' => access::get_code(access::PUBLIC)]
        );

        self::assertIsArray($result);
        self::assertCount(1, $result);

        // We are expecting that the tenant member is able to see system workspace.
        /** @var workspace $first_result */
        $first_result = reset($result);
        self::assertInstanceOf(workspace::class, $first_result);
        self::assertEquals($workspace->get_id(), $first_result->get_id());
    }

    /**
     * @return void
     */
    public function test_find_tenant_workspace_as_system_user_with_isolation(): void {
        set_config('tenantsisolated', 1);
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($this->tenant_one_user_one);
        $workspace_generator->create_workspace();

        $this->setUser($this->system_user);
        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(workspaces::class),
            ['access' => access::get_code(access::PUBLIC)]
        );

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    /**
     * System user should not be able to find the tenant workspace, no matter of isolation.
     * @return void
     */
    public function test_find_tenant_workspace_as_system_user_without_isolation(): void {
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($this->tenant_one_user_one);
        $workspace_generator->create_workspace();

        $this->setUser($this->system_user);
        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(workspaces::class),
            ['access' => access::get_code(access::PUBLIC)]
        );

        self::assertIsArray($result);
        self::assertCount(0, $result);
    }

    /**
     * @return void
     */
    public function test_find_tenant_workspace_as_tenant_participant_with_isolation(): void {
        set_config('tenantsisolated', 1);
        $this->setUser($this->tenant_one_user_one);

        $workspace_generator = $this->get_workspace_generator();
        $workspace = $workspace_generator->create_workspace();

        $this->setUser($this->tenant_one_participant);
        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(workspaces::class),
            ['access' => access::get_code(access::PUBLIC)]
        );

        self::assertIsArray($result);
        self::assertCount(1, $result);

        /** @var workspace $first_result */
        $first_result = reset($result);
        self::assertInstanceOf(workspace::class, $first_result);
        self::assertEquals($workspace->get_id(), $first_result->get_id());
    }

    /**
     * @return void
     */
    public function test_find_tenant_workspace_as_tenant_participant_without_isolation(): void {
        $this->setUser($this->tenant_one_user_one);

        $workspace_generator = $this->get_workspace_generator();
        $workspace = $workspace_generator->create_workspace();

        $this->setUser($this->tenant_one_participant);
        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(workspaces::class),
            ['access' => access::get_code(access::PUBLIC)]
        );

        self::assertIsArray($result);
        self::assertCount(1, $result);

        /** @var workspace $first_result */
        $first_result = reset($result);
        self::assertInstanceOf(workspace::class, $first_result);
        self::assertEquals($workspace->get_id(), $first_result->get_id());
    }
}