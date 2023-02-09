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
use editor_weka\webapi\resolver\query\users_by_pattern;
use core\entity\user;
use container_workspace\workspace;
use container_workspace\discussion\discussion;

class container_workspace_find_users_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

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
     * @param int $number_of_users
     * @return stdClass[]
     */
    private function create_users(int $number_of_users = 2): array {
        $generator = $this->getDataGenerator();
        $users = [];

        for ($i = 0; $i < $number_of_users; $i++) {
            $users[] = $generator->create_user([
                'firstname' => uniqid('first_name_'),
                'lastname' => uniqid('last_name_')
            ]);
        }

        return $users;
    }

    /**
     * @return void
     */
    public function test_find_system_level_users_as_tenant_user_in_workspace_context(): void {
        [$user_one, $user_two] = $this->create_users();
        $tenant_generator = $this->get_tenant_generator();

        $tenant = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant->id);
        $user_one->tenantid = $tenant->id;

        $this->setUser($user_one);
        $workspace_generator = $this->get_workspace_generator();
        $workspace = $workspace_generator->create_workspace();

        // Search for user two within workspace context.
        $graphql_name = $this->get_graphql_name(users_by_pattern::class);
        $before_result = $this->resolve_graphql_query(
            $graphql_name,
            [
                'pattern' => $user_two->firstname,
                'contextid' => $workspace->get_context()->id,
                'component' => workspace::get_type(),
                'area' => discussion::AREA
            ]
        );

        // As a result, user_two within system workspace is not able to search for the system level user.
        self::assertIsArray($before_result);
        self::assertEmpty($before_result);

        set_config('tenantsisolated', 1);
        $after_result = $this->resolve_graphql_query(
            $graphql_name,
            [
                'pattern' => $user_two->lastname,
                'contextid' => $workspace->get_context()->id,
                'component' => workspace::get_type(),
                'area' => discussion::AREA
            ]
        );

        self::assertIsArray($after_result);
        self::assertEmpty($after_result);
    }

    /**
     * @return void
     */
    public function test_find_system_level_user_as_tenant_participant_user_in_workspace_context(): void {
        [$user_one, $user_two, $user_three] = $this->create_users(3);
        $tenant_generator = $this->get_tenant_generator();

        $tenant = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant->id);
        $user_two->tenantid = $tenant->id;
        $tenant_generator->set_user_participation($user_three->id, [$tenant->id]);

        // Log in as user to create a workspace within a tenant.
        $this->setUser($user_two);
        $workspace_generator = $this->get_workspace_generator();
        $workspace = $workspace_generator->create_workspace();

        $workspace_generator->add_member($workspace, $user_three->id);

        // Log in as user three - as this user is a tenant participant user.
        // And try to search for the system level user.
        $graphql_name = $this->get_graphql_name(users_by_pattern::class);
        $workspace_context = $workspace->get_context();

        $this->setUser($user_three);

        $before_result = $this->resolve_graphql_query(
            $graphql_name,
            [
                'pattern' => $user_one->firstname,
                'contextid' => $workspace_context->id,
                'component' => workspace::get_type(),
                'area' => discussion::AREA
            ]
        );

        self::assertIsArray($before_result);
        self::assertEmpty($before_result);

        set_config('tenantsisolated', 1);
        $after_result = $this->resolve_graphql_query(
            $graphql_name,
            [
                'pattern' => $user_one->firstname,
                'contextid' => $workspace_context->id,
                'component' => workspace::get_type(),
                'area' => discussion::AREA
            ]
        );

        self::assertIsArray($after_result);
        self::assertEmpty($after_result);
    }

    /**
     * @return void
     */
    public function test_find_tenant_member_as_participant_in_workspace_context(): void {
        [$user_one, $user_two] = $this->create_users();

        $tenant_generator = $this->get_tenant_generator();
        $tenant = $tenant_generator->create_tenant();

        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant->id);
        $tenant_generator->set_user_participation($user_two->id, [$tenant->id]);

        $this->setUser($user_one);

        $workspace_generator = $this->get_workspace_generator();
        $workspace = $workspace_generator->create_workspace();

        $workspace_generator->add_member($workspace, $user_two->id, $user_one->id);

        // Log in as tenant participant and search for user one within workspace's context.
        $this->setUser($user_two->id);
        $query_name = $this->get_graphql_name(users_by_pattern::class);

        $context = $workspace->get_context();

        $before_result = $this->resolve_graphql_query(
            $query_name,
            [
                'pattern' => $user_two->firstname,
                'contextid' => $context->id,
                'component' => workspace::get_type(),
                'area' => discussion::AREA
            ]
        );

        self::assertIsArray($before_result);
        self::assertNotEmpty($before_result);

        // We make sure that user two is within the list.
        $before_fetched_user = reset($before_result);

        self::assertInstanceOf(user::class, $before_fetched_user);
        self::assertEquals($user_two->id, $before_fetched_user->id);

        // Turn on isolation mode and check if the participant is still able to fetch the tenant member.
        set_config('tenantsisolated', 1);

        $after_result = $this->resolve_graphql_query(
            $query_name,
            [
                'pattern' => $user_two->lastname,
                'contextid' => $context->id,
                'component' => workspace::get_type(),
                'area' => discussion::AREA
            ]
        );

        self::assertIsArray($after_result);
        self::assertNotEmpty($after_result);

        $after_fetched_user = reset($after_result);

        self::assertInstanceOf(user::class, $before_fetched_user);
        self::assertEquals($user_two->id, $after_fetched_user->id);
    }

    /**
     * @return void
     */
    public function test_fetching_other_tenant_user_within_system_level_workspace_context(): void {
        [$user_one, $user_two, $user_three] = $this->create_users(3);
        $tenant_generator = $this->get_tenant_generator();

        $tenant_one = $tenant_generator->create_tenant();
        $tenant_two = $tenant_generator->create_tenant();

        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_one->id);
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant_two->id);

        // Log in as user three - system level user and create a workspace then add the other two users.
        $this->setUser($user_three);
        $workspace_generator = $this->get_workspace_generator();
        $workspace = $workspace_generator->create_workspace();

        $workspace_generator->add_member($workspace, $user_two->id);
        $workspace_generator->add_member($workspace, $user_one->id);

        $query_name = $this->get_graphql_name(users_by_pattern::class);
        $context = $workspace->get_context();

        // Log in as user one and search for user two.
        $this->setUser($user_one);
        $user_one_before_result = $this->resolve_graphql_query(
            $query_name,
            [
                'pattern' => $user_two->firstname,
                'contextid' => $context->id,
                'component' => workspace::get_type(),
                'area' => discussion::AREA
            ]
        );

        self::assertIsArray($user_one_before_result);
        self::assertNotEmpty($user_one_before_result);
        self::assertCount(1, $user_one_before_result);

        // Checking that user two is within the list.
        $before_fetched_user_two = reset($user_one_before_result);

        self::assertInstanceOf(user::class, $before_fetched_user_two);
        self::assertEquals($user_two->id, $before_fetched_user_two->id);

        // Log in as second user and search for user two.
        $this->setUser($user_two);
        $user_two_before_result = $this->resolve_graphql_query(
            $query_name,
            [
                'pattern' => $user_one->lastname,
                'contextid' => $context->id,
                'component' => workspace::get_type(),
                'area' => discussion::AREA
            ]
        );

        self::assertIsArray($user_two_before_result);
        self::assertNotEmpty($user_two_before_result);
        self::assertCount(1, $user_two_before_result);

        // Checking that the user one is within the list.
        $before_fetched_user_one = reset($user_two_before_result);
        self::assertInstanceOf(user::class, $before_fetched_user_one);
        self::assertEquals($user_one->id, $before_fetched_user_one->id);
    }

    /**
     * @return void
     */
    public function test_fetching_other_tenant_within_system_level_context_on_isolated_mode(): void {
        [$user_one, $user_two] = $this->create_users();

        $tenant_generator = $this->get_tenant_generator();
        $tenant = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant->id);

        $this->setUser($user_one);

        // Create a workspace.
        $workspace_generator = $this->get_workspace_generator();
        $workspace = $workspace_generator->create_workspace();

        $workspace_generator->add_member($workspace, $user_two->id);

        // Set isolation mode on and log in as user two and check if the user two is able to
        // fetch user one.
        set_config('tenantsisolated', 1);
        $this->setUser($user_two);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage(
            "User with id '{$user_two->id}' cannot access context"
        );

        $this->resolve_graphql_query(
            $this->get_graphql_name(users_by_pattern::class),
            [
                'pattern' => $user_one->firstname,
                'contextid' => $workspace->get_context()->id,
                'component' => workspace::get_type(),
                'area' => discussion::AREA
            ]
        );
    }

    /**
     * @return void
     */
    public function test_operation_fetch_system_level_user_as_tenant_member_in_system_workspace_context(): void {
        [$user_one, $user_two] = $this->create_users();

        $tenant_generator = $this->get_tenant_generator();
        $tenant = $tenant_generator->create_tenant();

        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant->id);

        // Log in as user one to create a workspace.
        $this->setUser($user_one);
        $workspace_generator = $this->get_workspace_generator();
        $workspace = $workspace_generator->create_workspace();

        $workspace_generator->add_member($workspace, $user_two->id, $user_one->id);
        $context = $workspace->get_context();

        // Log in as second user and fetch for user one.
        $this->setUser($user_two);
        $result = $this->execute_graphql_operation(
            'editor_weka_find_users_by_pattern',
            [
                'pattern' => $user_one->firstname,
                'contextid' => $context->id,
                'component' => workspace::get_type(),
                'area' => discussion::AREA
            ]
        );

        self::assertIsArray($result->errors);
        self::assertEmpty($result->errors);

        self::assertIsArray($result->data);
        self::assertNotEmpty($result->data);
        self::assertArrayHasKey('users', $result->data);

        $users = $result->data['users'];
        self::assertCount(1, $users);

        $fetched_user_data = reset($users);
        self::assertArrayHasKey('id', $fetched_user_data);
        self::assertEquals($user_one->id, $fetched_user_data['id']);
    }

    /**
     * @return void
     */
    public function test_fetch_tenant_member_in_system_level_workspace_context_with_isolation_mode_on(): void {
        [$user_one, $user_two] = $this->create_users();

        $tenant_generator = $this->get_tenant_generator();
        $tenant = $tenant_generator->create_tenant();

        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant->id);

        // Log in as first user - system level user and create a workspace.
        $this->setUser($user_one);
        $workspace_generator = $this->get_workspace_generator();
        $workspace = $workspace_generator->create_workspace();

        $workspace_generator->add_member($workspace, $user_two->id, $user_one->id);

        // Turn on isolation mode and check if this user one is able fetch user two.
        set_config('tenantsisolated', 1);

        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(users_by_pattern::class),
            [
                'pattern' => $user_two->firstname,
                'contextid' => $workspace->get_context()->id,
                'component' => workspace::get_type(),
                'area' => discussion::AREA
            ]
        );

        self::assertIsArray($result);
        self::assertEmpty($result);
    }
}
