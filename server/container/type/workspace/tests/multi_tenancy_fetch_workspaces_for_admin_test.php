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

use container_workspace\loader\workspace\loader;
use container_workspace\query\workspace\query;
use container_workspace\query\workspace\source;
use container_workspace\workspace;

defined('MOODLE_INTERNAL') || die();

class container_workspace_multi_tenancy_fetch_workspaces_for_admin_testcase extends advanced_testcase {
    /**
     * The main actor of this test case.
     * @var stdClass|null
     */
    private $admin_user;

    /**
     * @var stdClass|null
     */
    private $tenant_one_user;

    /**
     * @var stdClass|null
     */
    private $tenant_two_user;

    /**
     * @return void
     */
    protected function setUp(): void {
        $generator = self::getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant_one = $tenant_generator->create_tenant();
        $tenant_two = $tenant_generator->create_tenant();

        $this->admin_user = get_admin();
        $this->tenant_one_user = $generator->create_user([
            'firstname' => uniqid('tenant_one_user_'),
            'lastname' => uniqid('tenant_one_user_'),
            'tenantid' => $tenant_one->id,
        ]);

        $this->tenant_two_user = $generator->create_user([
            'lastname' => uniqid('tenant_two_user_'),
            'firstname' => uniqid('tenant_two_user_'),
            'tenantid' => $tenant_two->id,
        ]);
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        $this->admin_user = null;
        $this->tenant_one_user = null;
        $this->tenant_two_user = null;
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
     * @return void
     */
    public function test_fetch_tenant_workspace_as_non_member_without_isolation(): void {
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($this->tenant_one_user);
        $tenant_one_workspace = $workspace_generator->create_workspace();

        $this->setUser($this->tenant_two_user);
        $tenant_two_workspace = $workspace_generator->create_workspace();

        $this->setUser($this->admin_user);
        $query = new query(source::ALL, $this->admin_user->id);
        $result = loader::get_workspaces($query);

        self::assertEquals(2, $result->get_total());
        $workspaces = $result->get_items();

        self::assertEquals(2, $workspaces->count());

        /** @var workspace $workspace */
        foreach ($workspaces as $workspace) {
            self::assertInstanceOf(workspace::class, $workspace);
            self::assertContains(
                $workspace->get_id(),
                [$tenant_one_workspace->get_id(), $tenant_two_workspace->get_id()]
            );
        }
    }

    /**
     * @return void
     */
    public function test_fetch_tenant_workspace_as_non_member_with_isolation(): void {
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($this->tenant_one_user);
        $tenant_one_workspace = $workspace_generator->create_workspace();

        $this->setUser($this->tenant_two_user);
        $tenant_two_workspace = $workspace_generator->create_workspace();

        $this->setUser($this->admin_user);
        set_config('tenantsisolated', 1);

        $query = new query(source::ALL, $this->admin_user->id);
        $result = loader::get_workspaces($query);

        self::assertEquals(2, $result->get_total());
        $workspaces = $result->get_items();

        self::assertEquals(2, $workspaces->count());

        /** @var workspace $workspace */
        foreach ($workspaces as $workspace) {
            self::assertInstanceOf(workspace::class, $workspace);
            self::assertContains(
                $workspace->get_id(),
                [$tenant_one_workspace->get_id(), $tenant_two_workspace->get_id()]
            );
        }
    }

    /**
     * @return void
     */
    public function test_fetch_hidden_tenant_workspace_as_non_member_with_isolation(): void {
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($this->tenant_one_user);
        $tenant_one_workspace = $workspace_generator->create_hidden_workspace();

        $this->setUser($this->tenant_two_user);
        $tenant_two_workspace = $workspace_generator->create_hidden_workspace();

        $this->setUser($this->admin_user);
        set_config('tenantsisolated', 1);

        $query = new query(source::ALL, $this->admin_user->id);
        $result = loader::get_workspaces($query);

        self::assertEquals(2, $result->get_total());
        $workspaces = $result->get_items();

        self::assertEquals(2, $workspaces->count());

        /** @var workspace $workspace */
        foreach ($workspaces as $workspace) {
            self::assertInstanceOf(workspace::class, $workspace);
            self::assertContains(
                $workspace->get_id(),
                [$tenant_one_workspace->get_id(), $tenant_two_workspace->get_id()]
            );
        }
    }

    /**
     * @return void
     */
    public function test_fetch_hidden_tenant_workspace_as_non_member_without_isolation(): void {
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($this->tenant_one_user);
        $tenant_one_workspace = $workspace_generator->create_hidden_workspace();

        $this->setUser($this->tenant_two_user);
        $tenant_two_workspace = $workspace_generator->create_hidden_workspace();

        $this->setUser($this->admin_user);

        $query = new query(source::ALL, $this->admin_user->id);
        $result = loader::get_workspaces($query);

        self::assertEquals(2, $result->get_total());
        $workspaces = $result->get_items();

        self::assertEquals(2, $workspaces->count());

        /** @var workspace $workspace */
        foreach ($workspaces as $workspace) {
            self::assertInstanceOf(workspace::class, $workspace);
            self::assertContains(
                $workspace->get_id(),
                [$tenant_one_workspace->get_id(), $tenant_two_workspace->get_id()]
            );
        }
    }
}