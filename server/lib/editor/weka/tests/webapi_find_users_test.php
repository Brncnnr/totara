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
 * @package editor_weka
 */
defined('MOODLE_INTERNAL') || die();

use totara_webapi\phpunit\webapi_phpunit_helper;
use editor_weka\webapi\resolver\query\users_by_pattern;
use core\entity\user;
use core\record\tenant;

class editor_weka_webapi_find_users_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @param int $number_of_users
     * @return stdClass[]
     */
    private function create_users(int $number_of_users): array {
        $generator = self::getDataGenerator();
        $users = [];

        for ($i = 0; $i < $number_of_users; $i++) {
            $users[] = $generator->create_user([
                'firstname' => uniqid($i),
                'lastname' => uniqid($i)
            ]);
        }

        return $users;
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
     * @param int $number_of_tenants
     * @return tenant[]
     */
    private function create_tenants(int $number_of_tenants): array {
        $tenant_generator = $this->get_tenant_generator();
        $tenants = [];

        for ($i = 0; $i < $number_of_tenants; $i++) {
            $tenants[] = $tenant_generator->create_tenant();
        }

        return $tenants;
    }

    /**
     * @return void
     */
    public function test_find_participant_users_as_tenant_member_in_user_context(): void {
        // User one is in tenant and user two is in system but participate to the tenant.
        [$user_one, $user_two] = $this->create_users(2);

        $tenant_generator = $this->get_tenant_generator();
        $tenant = $tenant_generator->create_tenant();

        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant->id);
        $tenant_generator->set_user_participation($user_two->id, [$tenant->id]);

        // Test check user one is able to search for the second user, within user's one context.
        $this->setUser($user_one);
        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(users_by_pattern::class),
            ['pattern' => strtolower($user_two->firstname)]
        );

        self::assertIsArray($result);
        self::assertCount(1, $result);

        // Check that user two exists in the list of result.
        $fetched_user = reset($result);

        self::assertInstanceOf(user::class, $fetched_user);
        self::assertEquals($user_two->id, $fetched_user->id);
    }

    /**
     * @return void
     */
    public function test_find_system_level_user_as_tenant_member_in_user_context(): void {
        [$user_one, $user_two] = $this->create_users(2);

        $tenant_generator = $this->get_tenant_generator();
        $tenant = $tenant_generator->create_tenant();

        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant->id);

        // Login as user one and search for user two in this user context.
        $this->setUser($user_one);
        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(users_by_pattern::class),
            ['pattern' => strtolower($user_two->firstname)]
        );

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    /**
     * @return void
     */
    public function test_find_system_level_user_as_tenant_participant_in_user_context(): void {
        [$user_one, $user_two] = $this->create_users(2);

        $tenant_generator = $this->get_tenant_generator();
        $tenant = $tenant_generator->create_tenant();

        $tenant_generator->set_user_participation($user_two->id, [$tenant->id]);

        // Log in as first user and fetch for user two, which the user one is able to see.
        $this->setUser($user_one);
        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(users_by_pattern::class),
            ['pattern' => $user_two->lastname]
        );

        self::assertIsArray($result);
        self::assertCount(1, $result);

        // Checking if the user two is in the list of returned users.
        $fetched_user = reset($result);

        self::assertInstanceOf(user::class, $fetched_user);
        self::assertEquals($user_two->id, $fetched_user->id);
    }

    /**
     * @return void
     */
    public function test_find_other_tenant_user_as_a_tenant_member_in_user_context(): void {
        [$user_one, $user_two] = $this->create_users(2);
        [$tenant_one, $tenant_two] = $this->create_tenants(2);

        $tenant_generator = $this->get_tenant_generator();

        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_one->id);
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant_two->id);

        // Log in as first user and check if this user one is able to search for user two.
        $this->setUser($user_one);
        $graphql_name = $this->get_graphql_name(users_by_pattern::class);

        $user_one_before_result = $this->resolve_graphql_query($graphql_name, ['pattern' => $user_two->lastname]);

        self::assertIsArray($user_one_before_result);
        self::assertEmpty($user_one_before_result);

        // Log in as second user and check if this user two is able to search for user one.
        $this->setUser($user_two);
        $user_two_before_result = $this->resolve_graphql_query($graphql_name, ['pattern' => $user_one->firstname]);

        self::assertIsArray($user_two_before_result);
        self::assertEmpty($user_two_before_result);

        set_config('tenantsisolated', 1);

        // Log in as user one and check if user one is able to fetch user two with isolation mode is on.
        $this->setUser($user_one);
        $user_one_after_result = $this->resolve_graphql_query($graphql_name, ['pattern' => $user_two->firstname]);

        self::assertIsArray($user_one_after_result);
        self::assertEmpty($user_one_after_result);

        // Log in as second user, and check if user one with isolation mode is on.
        $this->setUser($user_two);
        $user_two_after_result = $this->resolve_graphql_query($graphql_name, ['pattern' => $user_one->lastname]);

        self::assertIsArray($user_two_after_result);
        self::assertEmpty($user_two_after_result);
    }

    /**
     * @return void
     */
    public function test_find_tenant_user_as_system_level_user_in_user_context(): void {
        [$user_one, $user_two] = $this->create_users(2);

        $tenant_generator = $this->get_tenant_generator(2);
        $tenant = $tenant_generator->create_tenant();

        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant->id);

        // Log in as first user and check that if this user is able to search
        // for user two within this user context or not.
        $this->setUser($user_one);
        $graphql_name = $this->get_graphql_name(users_by_pattern::class);

        $before_result = $this->resolve_graphql_query($graphql_name, ['pattern' => $user_two->firstname]);

        // Without tenant isolation system level user is still able tofind the user two.
        self::assertIsArray($before_result);
        self::assertCount(1, $before_result);

        // Check if the user two is existing in the list.
        $fetched_user = reset($before_result);
        self::assertInstanceOf(user::class, $fetched_user);
        self::assertEquals($user_two->id, $fetched_user->id);

        // Set tenants isolation mode on.
        set_config('tenantsisolated', 1);
        $after_result = $this->resolve_graphql_query($graphql_name, ['pattern' => $user_two->firstname]);

        self::assertIsArray($after_result);
        self::assertEmpty($after_result);
    }
}