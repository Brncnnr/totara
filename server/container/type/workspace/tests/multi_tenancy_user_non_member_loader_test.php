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

use core\entity\user;
use container_workspace\query\member\non_member_query;
use container_workspace\loader\member\non_member_loader;
use core_phpunit\testcase;


/**
 * @deprecated since Totara 16. container_workspace\loader\member\non_member_loader
 * is no longer used.
 *
 * @group container_workspace
 */
class container_workspace_multi_tenancy_user_non_member_loader_testcase extends testcase {
    /**
     * @var user|null
     */
    private $tenant_one_user_one;

    /**
     * @var user|null
     */
    private $tenant_one_user_two;

    /**
     * @var user|null
     */
    private $tenant_two_user;

    /**
     * @var user|null
     */
    private $system_user;

    /**
     * @var user|null
     */
    private $tenant_one_participant;

    /**
     * @return void
     */
    protected function setUp(): void {
        $generator = $this->getDataGenerator();

        $tenant_generator = $this->get_tenant_generator();
        $tenant_one = $tenant_generator->create_tenant();
        $tenant_two = $tenant_generator->create_tenant();

        $tenant_one_user_one = $generator->create_user([
            'firstname' => uniqid('tenant_one_user_one'),
            'lastname' => uniqid('tenant_one_user_one'),
            'tenantid' => $tenant_one->id
        ]);

        $tenant_one_user_two = $generator->create_user([
            'firstname' => uniqid('tenant_one_user_two'),
            'lastname' => uniqid('tenant_one_user_two'),
            'tenantid' => $tenant_one->id
        ]);

        $tenant_two_user = $generator->create_user([
            'firstname' => uniqid('tennat_two_user'),
            'lastname' => uniqid('tenant_two_user'),
            'tenantid' => $tenant_two->id
        ]);

        $system_user = $generator->create_user([
            'firstname' => uniqid('system_user'),
            'lastname' => uniqid('system_user')
        ]);

        $tenant_one_participant = $generator->create_user([
            'firstname' => uniqid('tenant_participant'),
            'lastname' => uniqid('tenant_participant')
        ]);

        $tenant_generator->set_user_participation(
            $tenant_one_participant->id,
            [$tenant_one->id]
        );

        $this->tenant_one_user_one = new user($tenant_one_user_one);
        $this->tenant_one_user_two = new user($tenant_one_user_two);
        $this->tenant_two_user = new user($tenant_two_user);
        $this->system_user = new user($system_user);
        $this->tenant_one_participant = new user($tenant_one_participant);
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
     * @return \totara_tenant\testing\generator
     */
    private function get_tenant_generator(): \totara_tenant\testing\generator {
        $generator = $this->getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        return $tenant_generator;
    }

    /**
     * @return void
     */
    public function test_load_non_member_users_exclude_system_users_in_tenant_workspace(): void {
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($this->tenant_one_user_one->to_record());
        $workspace = $workspace_generator->create_workspace();

        $query = new non_member_query($workspace->get_id());
        $before_result = non_member_loader::get_non_members($query);
        $this->assert_loader_and_query_deprecated();

        self::assertEquals(2, $before_result->get_total());
        $before_fetch_users = $before_result->get_items()->all();

        self::assertCount(2, $before_fetch_users);

        foreach ($before_fetch_users as $before_fetch_user) {
            self::assertContainsEquals(
                $before_fetch_user->id,
                [
                    $this->tenant_one_user_two->id,
                    $this->tenant_one_participant->id
                ]
            );

            self::assertNotEquals($this->system_user->id, $before_fetch_user->id);
            self::assertNotEquals($this->tenant_two_user->id, $before_fetch_user);
        }


        set_config('tenantsisolated', 1);
        $after_result = non_member_loader::get_non_members($query);
        $this->assert_loader_deprecated();

        self::assertEquals(2, $after_result->get_total());
        $after_fetch_users = $after_result->get_items()->all();

        self::assertCount(2, $after_fetch_users);
        foreach ($after_fetch_users as $after_fetch_user) {
            self::assertContainsEquals(
                $after_fetch_user->id,
                [
                    $this->tenant_one_user_two->id,
                    $this->tenant_one_participant->id
                ]
            );

            self::assertNotEquals($this->system_user->id, $after_fetch_user->id);
            self::assertNotEquals($this->tenant_two_user->id, $after_fetch_user);
        }
    }

    /**
     * @return void
     */
    public function test_search_for_system_user_in_tenant_workspace(): void {
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($this->tenant_one_user_one->to_record());
        $workspace = $workspace_generator->create_workspace();

        $query = new non_member_query($workspace->get_id());
        $query->set_search_term($this->system_user->firstname);

        $before_result = non_member_loader::get_non_members($query);
        $this->assert_loader_and_query_deprecated();
        self::assertEquals(0, $before_result->get_total());

        $before_fetch_users = $before_result->get_items()->all();
        self::assertEmpty($before_fetch_users);

        set_config('tenantsisolated', 1);
        $after_result = non_member_loader::get_non_members($query);
        $this->assert_loader_deprecated();

        self::assertEquals(0, $after_result->get_total());
        $after_fetch_users = $after_result->get_items()->all();

        self::assertEmpty($after_fetch_users);
    }

    /**
     * @return void
     */
    public function test_search_tenant_participant_in_tenant_workspace(): void {
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($this->tenant_one_user_one->to_record());
        $workspace = $workspace_generator->create_workspace();

        $query = new non_member_query($workspace->get_id());
        $query->set_search_term($this->tenant_one_participant->firstname);

        $before_result = non_member_loader::get_non_members($query);
        $this->assert_loader_and_query_deprecated();

        self::assertEquals(1, $before_result->get_total());
        $before_fetch_users = $before_result->get_items()->all();

        self::assertCount(1, $before_fetch_users);
        $before_fetch_user = reset($before_fetch_users);

        self::assertEquals($this->tenant_one_participant->id, $before_fetch_user->id);
        self::assertNotEquals($this->tenant_one_user_two->id, $before_fetch_user->id);
        self::assertNotEquals($this->system_user->id, $before_fetch_user->id);
        self::assertNotEquals($this->tenant_two_user->id, $before_fetch_user);

        set_config('tenantsisolated', 1);
        $after_result = non_member_loader::get_non_members($query);
        $this->assert_loader_deprecated();

        self::assertEquals(1, $after_result->get_total());
        $after_fetch_users = $after_result->get_items()->all();

        self::assertCount(1, $after_fetch_users);
        $after_fetch_user = reset($after_fetch_users);

        self::assertEquals($this->tenant_one_participant->id, $after_fetch_user->id);
        self::assertNotEquals($this->tenant_one_user_two->id, $after_fetch_user->id);
        self::assertNotEquals($this->system_user->id, $after_fetch_user->id);
        self::assertNotEquals($this->tenant_two_user->id, $after_fetch_user);
    }

    /**
     * @return void
     */
    public function test_search_different_tenant_user_in_tenant_workspace(): void {
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($this->tenant_one_user_one->to_record());
        $workspace = $workspace_generator->create_workspace();

        $query = new non_member_query($workspace->get_id());
        $query->set_search_term($this->tenant_two_user->firstname);

        $before_result = non_member_loader::get_non_members($query);
        $this->assert_loader_and_query_deprecated();
        self::assertEquals(0, $before_result->get_total());

        $before_fetch_users = $before_result->get_items()->all();
        self::assertEmpty($before_fetch_users);

        set_config('tenantsisolated', 1);
        $after_result = non_member_loader::get_non_members($query);
        $this->assert_loader_deprecated();

        self::assertEquals(0, $after_result->get_total());
        $after_fetch_users = $after_result->get_items()->all();

        self::assertEmpty($after_fetch_users);
    }

    /**
     * @return void
     */
    public function test_search_for_tenant_member_in_same_tenant_workspace(): void {
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($this->tenant_one_user_one->to_record());
        $workspace = $workspace_generator->create_workspace();

        $query = new non_member_query($workspace->get_id());
        $query->set_search_term($this->tenant_one_user_two->firstname);

        $before_result = non_member_loader::get_non_members($query);
        $this->assert_loader_and_query_deprecated();

        self::assertEquals(1, $before_result->get_total());
        $before_fetch_users = $before_result->get_items()->all();

        self::assertCount(1, $before_fetch_users);
        $before_fetch_user = reset($before_fetch_users);

        self::assertEquals($this->tenant_one_user_two->id, $before_fetch_user->id);
        self::assertNotEquals($this->tenant_one_participant->id, $before_fetch_user->id);
        self::assertNotEquals($this->system_user->id, $before_fetch_user->id);
        self::assertNotEquals($this->tenant_two_user->id, $before_fetch_user);

        set_config('tenantsisolated', 1);
        $after_result = non_member_loader::get_non_members($query);
        $this->assert_loader_deprecated();

        self::assertEquals(1, $after_result->get_total());
        $after_fetch_users = $after_result->get_items()->all();

        self::assertCount(1, $after_fetch_users);
        $after_fetch_user = reset($after_fetch_users);

        self::assertEquals($this->tenant_one_user_two->id, $after_fetch_user->id);
        self::assertNotEquals($this->tenant_one_participant->id, $after_fetch_user->id);
        self::assertNotEquals($this->system_user->id, $after_fetch_user->id);
        self::assertNotEquals($this->tenant_two_user->id, $after_fetch_user);
    }

    /**
     * @return void
     */
    public function test_load_non_users_in_system_workspace(): void {
        $workspace_generator = $this->get_workspace_generator();
        $this->setUser($this->system_user->to_record());

        $admin_user = get_admin();

        $workspace = $workspace_generator->create_workspace();
        $query = new non_member_query($workspace->get_id());

        $before_result = non_member_loader::get_non_members($query);
        $this->assert_loader_and_query_deprecated();

        // Including admin.
        self::assertEquals(5, $before_result->get_total());
        $before_fetch_users = $before_result->get_items()->all();

        self::assertCount(5, $before_fetch_users);
        foreach ($before_fetch_users as $before_fetch_user) {
            self::assertContainsEquals(
                $before_fetch_user->id,
                [
                    $admin_user->id,
                    $this->tenant_one_user_one->id,
                    $this->tenant_one_user_two->id,
                    $this->tenant_one_participant->id,
                    $this->tenant_two_user->id
                ]
            );
        }

        set_config('tenantsisolated', 1);
        $after_result = non_member_loader::get_non_members($query);
        $this->assert_loader_deprecated();

        // Including admin and tenant participant only
        self::assertEquals(2, $after_result->get_total());
        $after_fetch_users = $after_result->get_items()->all();

        self::assertCount(2, $after_fetch_users);
        foreach ($after_fetch_users as $after_fetch_user) {
            self::assertContainsEquals(
                $after_fetch_user->id,
                [
                    $admin_user->id,
                    $this->tenant_one_participant->id,
                ]
            );

            self::assertNotEquals($this->tenant_one_user_one->id, $after_fetch_user->id);
            self::assertNotEquals($this->tenant_one_user_two->id, $after_fetch_user->id);
            self::assertNotEquals($this->tenant_two_user->id, $after_fetch_user->id);
        }
    }

    private function assert_loader_deprecated(): void {
        $messages = [
            'Class non_member_loader is deprecated; use container_workspace\data_providers\non_members instead'
        ];

        $this->assertDebuggingCalled($messages, DEBUG_DEVELOPER);
    }

    private function assert_query_deprecated(): void {
        $messages = [
            'Class non_member_query is deprecated; use container_workspace\data_providers\non_members instead',
        ];

        $this->assertDebuggingCalled($messages, DEBUG_DEVELOPER);
    }

    private function assert_loader_and_query_deprecated(): void {
        $messages = [
            'Class non_member_query is deprecated; use container_workspace\data_providers\non_members instead',
            'Class non_member_loader is deprecated; use container_workspace\data_providers\non_members instead'
        ];

        $this->assertDebuggingCalled($messages, DEBUG_DEVELOPER);
    }
}