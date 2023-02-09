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
 * @package totara_engage
 */
defined('MOODLE_INTERNAL') || die();

use totara_engage\query\user_query;
use totara_engage\loader\user_loader;
use totara_engage\query\user_tenant_query;

class totara_engage_multi_tenancy_user_loader_testcase extends advanced_testcase {
    /**
     * @param int $number_of_users
     * @return stdClass[]
     */
    private function create_users(int $number_of_users = 2): array {
        $generator = $this->getDataGenerator();
        $users = [];

        for ($i = 0; $i < $number_of_users; $i++) {
            $users[] = $generator->create_user([
                'firstname' => uniqid('firstname_'),
                'lastname' => uniqid('lastname_')
            ]);
        }

        return $users;
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
    public function test_load_tenant_users_include_system_level_users_in_user_context(): void {
        [$user_one, $user_two, $user_three] = $this->create_users(3);
        $tenant_generator = $this->get_tenant_generator();

        $tenant = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant->id);
        $tenant_generator->set_user_participation($user_two->id, [$tenant->id]);

        $user_one_context = context_user::instance($user_one->id);
        $query = user_query::create_with_exclude_guest_user($user_one_context->id);
        $query->exclude_user(get_admin()->id);

        $result = user_loader::get_users($query);
        self::assertEquals(3, $result->get_total());

        $users = $result->get_items()->all();
        self::assertCount(3, $users);

        foreach ($users as $user) {
            self::assertContainsEquals($user->id, [$user_one->id, $user_two->id, $user_three->id]);
        }
    }

    /**
     * @return void
     */
    public function test_load_tenant_users_exclude_system_users_but_include_participant_in_user_context(): void {
        [$user_one, $user_two, $user_three] = $this->create_users(3);
        $tenant_generator = $this->get_tenant_generator();

        $tenant = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant->id);
        $tenant_generator->set_user_participation($user_two->id, [$tenant->id]);

        $user_one_context = context_user::instance($user_one->id);
        $tenant_query = new user_tenant_query(false);

        $query = user_query::create_with_exclude_guest_user($user_one_context->id, $tenant_query);
        $query->exclude_user(get_admin()->id);

        $result = user_loader::get_users($query);
        self::assertEquals(2, $result->get_total());

        $users = $result->get_items()->all();
        self::assertCount(2, $users);

        foreach ($users as $user) {
            self::assertContainsEquals($user->id, [$user_one->id, $user_two->id]);
            self::assertNotEquals($user_three->id, $user->id);
        }
    }

    /**
     * @return void
     */
    public function test_load_tenant_users_include_system_users_but_exclude_participant_in_user_context(): void {
        [$user_one, $user_two, $user_three] = $this->create_users(3);
        $tenant_generator = $this->get_tenant_generator();

        $tenant = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant->id);
        $tenant_generator->set_user_participation($user_two->id, [$tenant->id]);

        $user_one_context = context_user::instance($user_one->id);
        $tenant_query = new user_tenant_query(true, false);

        $query = user_query::create_with_exclude_guest_user($user_one_context->id, $tenant_query);
        $query->exclude_user(get_admin()->id);

        $result = user_loader::get_users($query);
        self::assertEquals(2, $result->get_total());

        $users = $result->get_items()->all();
        self::assertCount(2, $users);

        foreach ($users as $user) {
            self::assertContainsEquals($user->id, [$user_one->id, $user_three->id]);
            self::assertNotEquals($user_two->id, $user->id);
        }
    }

    /**
     * @return void
     */
    public function test_load_tenant_users_with_only_system_users_in_user_context(): void {
        [$user_one, $user_two, $user_three, $user_four] = $this->create_users(4);
        $tenant_generator = $this->get_tenant_generator();

        $tenant_one = $tenant_generator->create_tenant();
        $tenant_two = $tenant_generator->create_tenant();

        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_one->id);
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant_two->id);

        $tenant_generator->set_user_participation($user_three->id, [$tenant_one->id]);

        $user_one_context = context_user::instance($user_one->id);
        $tenant_query = new user_tenant_query(true, false);

        $query = new user_query($user_one_context->id, $tenant_query);
        $query->exclude_guest_user();
        $query->exclude_user(get_admin()->id);

        $result = user_loader::get_users($query);
        self::assertEquals(2, $result->get_total());

        $users = $result->get_items()->all();
        self::assertCount(2, $users);

        foreach ($users as $user) {
            self::assertContainsEquals($user->id, [$user_one->id, $user_four->id]);
            self::assertNotEquals($user_two->id, $user->id);
            self::assertNotEquals($user_three->id, $user->id);
        }
    }

    /**
     * @return void
     */
    public function test_load_tenant_members_only_in_user_context(): void {
        [$user_one, $user_two, $user_three] = $this->create_users(3);
        $tenant_generator = $this->get_tenant_generator();

        $tenant = $tenant_generator->create_tenant();

        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant->id);
        $tenant_generator->set_user_participation($user_two->id, [$tenant->id]);

        $user_one_context = context_user::instance($user_one->id);
        $tenant_query = new user_tenant_query(false, false);

        $query = new user_query($user_one_context->id, $tenant_query);
        $query->exclude_guest_user();
        $query->exclude_user(get_admin()->id);

        $result = user_loader::get_users($query);
        self::assertEquals(1, $result->get_total());

        $users = $result->get_items()->all();
        self::assertCount(1, $users);

        $user = reset($users);
        self::assertEquals($user_one->id, $user->id);
        self::assertNotEquals($user_two->id, $user->id);
        self::assertNotEquals($user_three->id, $user->id);
    }

    /**
     * @return void
     */
    public function test_load_includde_system_user_ignored_when_isolation_mode_is_on_in_user_context(): void {
        [$user_one, $user_two] = $this->create_users();
        $tenant_generator = $this->get_tenant_generator();

        $tenant = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant->id);

        $user_one_context = context_user::instance($user_one->id);
        $query = user_query::create_with_exclude_guest_user($user_one_context->id);

        set_config('tenantsisolated', 1);
        $result = user_loader::get_users($query);

        self::assertEquals(1, $result->get_total());
        $users = $result->get_items()->all();

        self::assertCount(1, $users);
        $user = reset($users);

        self::assertEquals($user_one->id, $user->id);
        self::assertNotEquals($user_two->id, $user->id);
    }

    /**
     * @return void
     */
    public function test_load_include_participant_when_isolation_mode_is_on_in_user_context(): void {
        [$user_one, $user_two] = $this->create_users();
        $tenant_generator = $this->get_tenant_generator();

        $tenant = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant->id);
        $tenant_generator->set_user_participation($user_two->id, [$tenant->id]);

        $user_one_context = context_user::instance($user_one->id);
        $query = user_query::create_with_exclude_guest_user($user_one_context->id);

        set_config('tenantsisolated', 1);
        $result = user_loader::get_users($query);

        self::assertEquals(2, $result->get_total());
        $users = $result->get_items()->all();

        self::assertCount(2, $users);
        foreach ($users as $user) {
            self::assertContainsEquals($user->id, [$user_one->id, $user_two->id]);
        }
    }

    /**
     * @return void
     */
    public function test_load_exclude_participant_when_isolation_mode_is_on_in_user_context(): void {
        [$user_one, $user_two] = $this->create_users();
        $tenant_generator = $this->get_tenant_generator();

        $tenant = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant->id);
        $tenant_generator->set_user_participation($user_two->id, [$tenant->id]);

        $user_one_context = context_user::instance($user_one->id);
        $query = user_query::create_with_exclude_guest_user(
            $user_one_context->id,
            new user_tenant_query(true, false)
        );

        set_config('tenantsisolated', 1);
        $result = user_loader::get_users($query);

        self::assertEquals(1, $result->get_total());
        $users = $result->get_items()->all();

        $user = reset($users);
        self::assertEquals($user_one->id, $user->id);
        self::assertNotEquals($user_two->id, $user->id);
    }
}