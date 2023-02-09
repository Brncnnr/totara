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

use container_workspace\loader\workspace\loader;
use container_workspace\query\workspace\query;
use container_workspace\query\workspace\source;
use container_workspace\workspace;

class container_workspace_multi_tenancy_fetch_workspaces_for_tenant_participant_testcase extends advanced_testcase {
    /**
     * The main actor in this test case.
     *
     * @var stdClass|null
     */
    private $tenant_participant;

    /**
     * @var stdClass|null
     */
    private $system_user;

    /**
     * @var stdClass|null
     */
    private $tenant_user;

    /**
     * @return void
     */
    protected function setUp(): void {
        $generator = self::getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant = $tenant_generator->create_tenant();

        $this->tenant_user = $generator->create_user([
            'firstname' => uniqid('tenant_user_'),
            'lastname' => uniqid('tenant_user_'),
            'tenantid' => $tenant->id,
        ]);

        $this->system_user = $generator->create_user([
            'firstname' => uniqid('system_user_'),
            'lastname' => uniqid('system_user_'),
        ]);

        $this->tenant_participant = $generator->create_user([
            'firstname' => uniqid('tenant_participant_'),
            'lastname' => uniqid('tenant_participant_'),
        ]);

        $tenant_generator->set_user_participation($this->tenant_participant->id, [$tenant->id]);
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        $this->tenant_participant = null;
        $this->system_user = null;
        $this->tenant_user = null;
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
    public function test_fetch_tenant_workspaces_as_non_member_with_isolation(): void {
        set_config('tenantsisolated', 1);
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($this->tenant_user);
        $tenant_workspace = $workspace_generator->create_workspace();

        $this->setUser($this->system_user);
        $system_workspace = $workspace_generator->create_workspace();

        $this->setUser($this->tenant_participant);

        $query_one = new query(source::MEMBER, $this->tenant_participant->id);
        $result_one = loader::get_workspaces($query_one);

        self::assertEquals(0, $result_one->get_total());
        $result_one_workspaces = $result_one->get_items()->all();
        self::assertEmpty($result_one_workspaces);

        $query_two = new query(source::OTHER, $this->tenant_participant->id);
        $result_two = loader::get_workspaces($query_two);

        self::assertEquals(2, $result_two->get_total());
        $result_two_workspaces = $result_two->get_items()->all();

        self::assertCount(2, $result_two_workspaces);

        /** @var workspace $result_two_workspace */
        foreach ($result_two_workspaces as $result_two_workspace) {
            self::assertContains(
                $result_two_workspace->get_id(),
                [$tenant_workspace->get_id(), $system_workspace->get_id()]
            );
        }
    }

    /**
     * @return void
     */
    public function test_fetch_tenant_workspaces_as_non_member_without_isolation(): void {
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($this->tenant_user);
        $tenant_workspace = $workspace_generator->create_workspace();

        $this->setUser($this->system_user);
        $system_workspace = $workspace_generator->create_workspace();

        $this->setUser($this->tenant_participant);

        $query_one = new query(source::MEMBER, $this->tenant_participant->id);
        $result_one = loader::get_workspaces($query_one);

        self::assertEquals(0, $result_one->get_total());
        $result_one_workspaces = $result_one->get_items()->all();
        self::assertEmpty($result_one_workspaces);

        $query_two = new query(source::OTHER, $this->tenant_participant->id);
        $result_two = loader::get_workspaces($query_two);

        self::assertEquals(2, $result_two->get_total());
        $result_two_workspaces = $result_two->get_items()->all();

        self::assertCount(2, $result_two_workspaces);

        /** @var workspace $result_two_workspace */
        foreach ($result_two_workspaces as $result_two_workspace) {
            self::assertContains(
                $result_two_workspace->get_id(),
                [$tenant_workspace->get_id(), $system_workspace->get_id()]
            );
        }
    }

    /**
     * @return void
     */
    public function test_fetch_tenant_workspaces_as_member_with_isolation(): void {
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($this->tenant_user);
        $tenant_workspace = $workspace_generator->create_workspace();
        $workspace_generator->add_member($tenant_workspace, $this->tenant_participant->id, $this->tenant_user->id);

        $this->setUser($this->system_user);
        $system_workspace = $workspace_generator->create_workspace();

        // Fetch member of.
        $this->setUser($this->tenant_participant);
        set_config('tenantsisolated', 1);

        $query_one = new query(source::MEMBER, $this->tenant_participant->id);
        $result_one = loader::get_workspaces($query_one);

        self::assertEquals(1, $result_one->get_total());
        $result_one_workspaces = $result_one->get_items();
        self::assertEquals(1, $result_one_workspaces->count());

        /** @var workspace $result_one_workspace */
        $result_one_workspace = $result_one_workspaces->first();
        self::assertEquals($tenant_workspace->get_id(), $result_one_workspace->get_id());

        $query_two = new query(source::OTHER, $this->tenant_participant->id);
        $result_two = loader::get_workspaces($query_two);

        self::assertEquals(1, $result_two->get_total());
        $result_two_workspaces = $result_two->get_items();
        self::assertEquals(1, $result_two_workspaces->count());

        /** @var workspace $result_two_workspace */
        $result_two_workspace = $result_two_workspaces->first();
        self::assertEquals($system_workspace->get_id(), $result_two_workspace->get_id());
    }

    /**
     * @return void
     */
    public function test_fetch_tenant_workspaces_as_member_without_isolation(): void {
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($this->tenant_user);
        $tenant_workspace = $workspace_generator->create_workspace();
        $workspace_generator->add_member($tenant_workspace, $this->tenant_participant->id, $this->tenant_user->id);

        $this->setUser($this->system_user);
        $system_workspace = $workspace_generator->create_workspace();

        // Fetch member of.
        $this->setUser($this->tenant_participant);

        $query_one = new query(source::MEMBER, $this->tenant_participant->id);
        $result_one = loader::get_workspaces($query_one);

        self::assertEquals(1, $result_one->get_total());
        $result_one_workspaces = $result_one->get_items();
        self::assertEquals(1, $result_one_workspaces->count());

        /** @var workspace $result_one_workspace */
        $result_one_workspace = $result_one_workspaces->first();
        self::assertEquals($tenant_workspace->get_id(), $result_one_workspace->get_id());

        $query_two = new query(source::OTHER, $this->tenant_participant->id);
        $result_two = loader::get_workspaces($query_two);

        self::assertEquals(1, $result_two->get_total());
        $result_two_workspaces = $result_two->get_items();
        self::assertEquals(1, $result_two_workspaces->count());

        /** @var workspace $result_two_workspace */
        $result_two_workspace = $result_two_workspaces->first();
        self::assertEquals($system_workspace->get_id(), $result_two_workspace->get_id());
    }

    /**
     * @return void
     */
    public function test_fetch_tenant_hidden_workspace_as_non_member_without_isolation(): void {
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($this->tenant_user);
        $hidden_tenant_workspace = $workspace_generator->create_hidden_workspace();

        $this->setUser($this->system_user);
        $system_workspace = $workspace_generator->create_private_workspace();

        $this->setUser($this->tenant_participant);
        $query = new query(source::ALL, $this->tenant_participant->id);
        $result = loader::get_workspaces($query);

        self::assertEquals(1, $result->get_total());

        $workspaces = $result->get_items();
        self::assertEquals(1, $workspaces->count());

        /** @var workspace $fetched_workspace */
        $fetched_workspace = $workspaces->first();
        self::assertInstanceOf(workspace::class, $fetched_workspace);
        self::assertNotEquals($hidden_tenant_workspace->get_id(), $fetched_workspace->get_id());
        self::assertEquals($system_workspace->get_id(), $fetched_workspace->get_id());
    }

    /**
     * @return void
     */
    public function test_fetch_system_hidden_workspace_as_non_member_without_isolation(): void {
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($this->tenant_user);
        $tenant_workspace = $workspace_generator->create_private_workspace();

        $this->setUser($this->system_user);
        $hidden_system_workspace = $workspace_generator->create_hidden_workspace();

        $this->setUser($this->tenant_participant);
        $query = new query(source::ALL, $this->tenant_participant->id);

        $result = loader::get_workspaces($query);
        self::assertEquals(1, $result->get_total());

        $workspaces = $result->get_items();
        self::assertEquals(1, $workspaces->count());

        /** @var workspace $fetched_workspace */
        $fetched_workspace = $workspaces->first();
        self::assertInstanceOf(workspace::class, $fetched_workspace);
        self::assertNotEquals($hidden_system_workspace->get_id(), $fetched_workspace->get_id());
        self::assertEquals($tenant_workspace->get_id(), $fetched_workspace->get_id());
    }

    /**
     * @return void
     */
    public function test_fetch_tenant_hidden_workspace_as_member_without_isolation(): void {
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($this->tenant_user);
        $hidden_tenant_workspace = $workspace_generator->create_hidden_workspace();
        $workspace_generator->add_member(
            $hidden_tenant_workspace,
            $this->tenant_participant->id,
            $this->tenant_user->id
        );

        $this->setUser($this->system_user);
        $system_workspace = $workspace_generator->create_workspace();

        $this->setUser($this->tenant_participant);
        $query = new query(source::MEMBER, $this->tenant_participant->id);
        $result = loader::get_workspaces($query);

        self::assertEquals(1, $result->get_total());
        $workspaces = $result->get_items();
        self::assertEquals(1, $workspaces->count());

        /** @var workspace $fetched_workspace */
        $fetched_workspace = $workspaces->first();
        self::assertInstanceOf(workspace::class, $fetched_workspace);
        self::assertNotEquals($system_workspace->get_id(), $fetched_workspace->get_id());
        self::assertEquals($hidden_tenant_workspace->get_id(), $fetched_workspace->get_id());
    }
}