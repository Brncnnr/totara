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
use container_workspace\loader\workspace\loader;
use container_workspace\query\workspace\source;
use container_workspace\workspace;

class container_workspace_multi_tenancy_fetch_workspaces_for_system_users_testcase extends advanced_testcase {
    /**
     * The main actor of this test
     * @var stdClass|null
     */
    private $system_user;

    /**
     * A user that is potentially moved to a tenant.
     *
     * @var stdClass|null
     */
    private $user_one;

    /**
     * A user that is potentially participate to a tenant.
     * @var stdClass|null
     */
    private $user_two;

    /**
     * @return void
     */
    protected function setUp(): void {
        $generator = self::getDataGenerator();

        $this->system_user = $generator->create_user([
            'firstname' => uniqid('system_user_'),
            'lastname' => uniqid('system_user_')
        ]);

        $this->user_one = $generator->create_user([
            'firstname' => uniqid('user_one_'),
            'lastname' => uniqid('user_one_')
        ]);

        $this->user_two = $generator->create_user([
            'firstname' => uniqid('user_two_'),
            'lastname' => uniqid('user_two_')
        ]);
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        $this->system_user = null;
        $this->user_one = null;
        $this->user_two = null;
    }

    /**
     * @return \container_workspace\testing\generator
     */
    private function get_workspace_generator(): \container_workspace\testing\generator {
        $generator = self::getDataGenerator();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        return $workspace_generator;
    }

    /**
     * @return \totara_tenant\testing\generator
     */
    private function get_tenant_generator(): \totara_tenant\testing\generator {
        $generator = self::getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        return $tenant_generator;
    }

    /**
     * To produce the test, the workspace will have to be created before tenant and moved to the tenant.
     * So that the system user can be a part of that workspace.
     *
     * @return void
     */
    public function test_fetch_tenant_workspace_as_member_without_isolation(): void {
        $workspace_generator = $this->get_workspace_generator();
        $this->setUser($this->user_one);

        $workspace = $workspace_generator->create_workspace();

        // Add system user to the workspace.
        $workspace_generator->add_member(
            $workspace,
            $this->system_user->id,
            $this->user_one->id
        );

        // Create tenant and move user one and the workspace to this tenant.
        $tenant_generator = $this->get_tenant_generator();
        $tenant = $tenant_generator->create_tenant();

        $tenant_generator->migrate_user_to_tenant($this->user_one->id, $tenant->id);
        $category = $workspace_generator->create_category(['tenant_id' => $tenant->id]);

        $workspace_generator->move_workspace_to_category($workspace, $category->id);

        $workspace_context = $workspace->get_context();
        self::assertNotNull($workspace_context->tenantid);
        self::assertEquals($tenant->id, $workspace_context->tenantid);

        // Now check that we can fetch the workspaces which include this tenant workspace or not for the system user.
        $query = new query(source::ALL, $this->system_user->id);
        $query->set_actor_id($this->system_user->id);

        $result = loader::get_workspaces($query);
        self::assertEquals(0, $result->get_total());

        $workspaces = $result->get_items()->all();
        self::assertEmpty($workspaces);
    }

    /**
     * @return void
     */
    public function test_fetch_tenant_workspace_as_member_with_isolation(): void {
        $workspace_generator = $this->get_workspace_generator();
        $this->setUser($this->user_one);

        $workspace = $workspace_generator->create_workspace();

        // Add system user to the workspace.
        $workspace_generator->add_member(
            $workspace,
            $this->system_user->id,
            $this->user_one->id
        );

        // Create tenant and move user one and the workspace to this tenant.
        $tenant_generator = $this->get_tenant_generator();
        $tenant = $tenant_generator->create_tenant();

        $tenant_generator->migrate_user_to_tenant($this->user_one->id, $tenant->id);
        $category = $workspace_generator->create_category(['tenant_id' => $tenant->id]);

        $workspace_generator->move_workspace_to_category($workspace, $category->id);
        set_config('tenantsisolated', 1);

        $query = new query(source::ALL, $this->system_user->id);
        $query->set_actor_id($this->system_user->id);

        $result = loader::get_workspaces($query);
        self::assertEquals(0, $result->get_total());

        $workspaces = $result->get_items()->all();
        self::assertEmpty($workspaces);
    }

    /**
     * This test should make sure that the tenant workspace is filtered out.
     * @return void
     */
    public function test_fetch_tenant_workspace_and_system_workspace_as_member_of_both_without_isolation(): void {
        $workspace_generator = $this->get_workspace_generator();

        // Log in as user one to create a workspace, which to be moved to a tenant.
        $this->setUser($this->user_one);
        $tenant_workspace = $workspace_generator->create_workspace();
        $workspace_generator->add_member($tenant_workspace, $this->system_user->id, $this->user_one->id);

        // Log in as user two to create a workspace, which it should stay in the system.
        $this->setUser($this->user_two);
        $system_workspace = $workspace_generator->create_workspace();
        $workspace_generator->add_member($system_workspace, $this->system_user->id, $this->user_two->id);

        // Move the workspace from user one to a tenant.
        $tenant_generator = $this->get_tenant_generator();
        $tenant = $tenant_generator->create_tenant();

        $tenant_generator->migrate_user_to_tenant($this->user_one->id, $tenant->id);
        $category = $workspace_generator->create_category(['tenant_id' => $tenant->id]);

        $workspace_generator->move_workspace_to_category($tenant_workspace, $category->id);
        $context = $tenant_workspace->get_context();

        self::assertNotNull($context->tenantid);
        self::assertEquals($tenant->id, $context->tenantid);

        // Log in as system user to fetch all the workspaces.
        $this->setUser($this->system_user);
        $query = new query(source::ALL, $this->system_user->id);

        $result = loader::get_workspaces($query);
        self::assertEquals(1, $result->get_total());

        $workspaces = $result->get_items();
        self::assertEquals(1, $workspaces->count());

        /** @var workspace $first_workspace */
        $first_workspace = $workspaces->first();
        self::assertInstanceOf(workspace::class, $first_workspace);
        self::assertNotEquals($tenant_workspace->get_id(), $first_workspace->get_id());
        self::assertEquals($system_workspace->get_id(), $first_workspace->get_id());
    }

    /**
     * @return void
     */
    public function test_fetch_tenant_workspace_and_system_workspace_as_non_member_of_all_without_isolation(): void {
        $workspace_generator = $this->get_workspace_generator();
        $tenant_generator = $this->get_tenant_generator();

        $tenant = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($this->user_one->id, $tenant->id);
        $this->user_one->tenantid = $tenant->id;

        // Log in as user one to create a tenant workspace.
        $this->setUser($this->user_one);
        $tenant_workspace = $workspace_generator->create_workspace();

        $this->setUser($this->user_two);
        $system_workspace = $workspace_generator->create_workspace();

        // Fetch the workspace that this workspace is a member of.
        $this->setUser($this->system_user);

        $query_one = new query(source::MEMBER);
        $result_one = loader::get_workspaces($query_one);

        self::assertEquals(0, $result_one->get_total());
        $result_one_workspaces = $result_one->get_items()->all();
        self::assertEmpty($result_one_workspaces);

        // Fetch other workspaces.
        $query_two = new query(source::OTHER);
        $result_two = loader::get_workspaces($query_two);

        self::assertEquals(1, $result_two->get_total());
        $result_two_workspaces = $result_two->get_items();

        self::assertEquals(1, $result_two_workspaces->count());
        self::assertNotEmpty($result_two_workspaces->all());

        /** @var workspace $result_two_workspace */
        $result_two_workspace = $result_two_workspaces->first();
        self::assertNotEquals($tenant_workspace->get_id(), $result_two_workspace->get_id());
        self::assertEquals($system_workspace->get_id(), $result_two_workspace->get_id());
    }
}
