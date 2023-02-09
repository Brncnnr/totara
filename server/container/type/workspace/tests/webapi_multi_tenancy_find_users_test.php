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
use container_workspace\discussion\discussion;
use container_workspace\workspace;
use editor_weka\webapi\resolver\query\users_by_pattern;
use core\entity\user;
use totara_comment\comment;

class container_workspace_webapi_multi_tenancy_find_users_testcase extends advanced_testcase {
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
    private $tenant_one_participant;

    /**
     * @var stdClass|null
     */
    private $system_user;

    /**
     * @return void
     */
    protected function setUp(): void {
        $tenant_generator = $this->get_tenant_generators();

        $tenant_one = $tenant_generator->create_tenant();
        $tenant_two = $tenant_generator->create_tenant();

        $generator = $this->getDataGenerator();

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

        $this->tenant_one_participant = $generator->create_user([
            'firstname' => uniqid('tenant_one_participant_'),
            'lastname' => uniqid('tenant_one_participant_'),
        ]);

        $tenant_generator->set_user_participation(
            $this->tenant_one_participant->id,
            [$tenant_one->id]
        );

        $this->system_user = $generator->create_user([
            'firstname' => uniqid('system_user_'),
            'lastname' => uniqid('system_user_')
        ]);
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        $this->tenant_one_user_one = null;
        $this->tenant_one_user_two = null;
        $this->tenant_two_user = null;
        $this->tenant_one_participant = null;
        $this->system_user = null;
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
    private function get_tenant_generators(): \totara_tenant\testing\generator {
        $generator = $this->getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        return $tenant_generator;
    }

    /**
     * @return void
     */
    public function test_find_system_users_as_tenant_user_in_tenant_public_workspace_when_add_new_discussion(): void {
        $workspace_generator = $this->get_workspace_generator();
        $this->setUser($this->tenant_one_user_one);

        $workspace = $workspace_generator->create_workspace();

        $query_name = $this->get_graphql_name(users_by_pattern::class);
        $parameters = [
            'contextid' => $workspace->get_context()->id,
            'pattern' => $this->system_user->firstname,
            'component' => workspace::get_type(),
            'area' => discussion::AREA
        ];

        $before_result = $this->resolve_graphql_query($query_name, $parameters);
        self::assertIsArray($before_result);
        self::assertEmpty($before_result);

        set_config('tenantsisolated', 1);

        $after_result = $this->resolve_graphql_query($query_name, $parameters);
        self::assertIsArray($after_result);
        self::assertEmpty($after_result);
    }

    /**
     * @return void
     */
    public function test_find_same_tenant_member_as_tenant_member_in_private_workspace_when_add_new_discussion(): void {
        $workspace_generator = $this->get_workspace_generator();
        $this->setUser($this->tenant_one_user_one);

        $workspace = $workspace_generator->create_private_workspace();
        $query_name = $this->get_graphql_name(users_by_pattern::class);
        $parameters = [
            'contextid' => $workspace->get_context()->id,
            'pattern' => $this->tenant_one_user_two->firstname,
            'component' => workspace::get_type(),
            'area' => discussion::AREA
        ];

        $before_result = $this->resolve_graphql_query($query_name, $parameters);
        self::assertIsArray($before_result);
        self::assertNotEmpty($before_result);
        self::assertCount(1, $before_result);

        $before_fetch_user = reset($before_result);
        self::assertInstanceOf(user::class, $before_fetch_user);
        self::assertEquals($this->tenant_one_user_two->id, $before_fetch_user->id);

        set_config('tenantsisolated', 1);

        $after_result = $this->resolve_graphql_query($query_name, $parameters);
        self::assertIsArray($after_result);
        self::assertNotEmpty($after_result);
        self::assertCount(1, $after_result);

        $after_fetch_user = reset($after_result);
        self::assertInstanceOf(user::class, $after_fetch_user);
        self::assertEquals($this->tenant_one_user_two->id, $after_fetch_user->id);
    }

    /**
     * @return void
     */
    public function test_find_participant_as_tenant_member_in_public_workspace_when_add_discussion(): void {
        $workspace_generator = $this->get_workspace_generator();
        $this->setUser($this->tenant_one_user_one);

        $workspace = $workspace_generator->create_workspace();
        $query_name = $this->get_graphql_name(users_by_pattern::class);
        $parameters = [
            'contextid' => $workspace->get_context()->id,
            'pattern' => $this->tenant_one_participant->lastname,
            'component' => workspace::get_type(),
            'area' => discussion::AREA
        ];

        $before_result = $this->resolve_graphql_query($query_name, $parameters);
        self::assertIsArray($before_result);
        self::assertNotEmpty($before_result);
        self::assertCount(1, $before_result);

        $before_fetch_user = reset($before_result);
        self::assertInstanceOf(user::class, $before_fetch_user);
        self::assertEquals($this->tenant_one_participant->id, $before_fetch_user->id);

        set_config('tenantsisolated', 1);

        $after_result = $this->resolve_graphql_query($query_name, $parameters);
        self::assertIsArray($after_result);
        self::assertNotEmpty($after_result);
        self::assertCount(1, $after_result);

        $after_fetch_user = reset($after_result);
        self::assertInstanceOf(user::class, $after_fetch_user);
        self::assertEquals($this->tenant_one_participant->id, $after_fetch_user->id);
    }

    /**
     * @return void
     */
    public function test_find_for_same_tenant_member_as_tenant_member_in_hidden_workspace_when_add_discussion(): void {
        $workspace_generator = $this->get_workspace_generator();
        $this->setUser($this->tenant_one_user_one);

        $workspace = $workspace_generator->create_hidden_workspace();
        $query_name = $this->get_graphql_name(users_by_pattern::class);
        $parameters = [
            'contextid' => $workspace->get_context()->id,
            'pattern' => $this->tenant_one_user_two->firstname,
            'component' => workspace::get_type(),
            'area' => discussion::AREA
        ];

        $result_one = $this->resolve_graphql_query($query_name, $parameters);
        self::assertIsArray($result_one);
        self::assertEmpty($result_one);

        // Add to user two to the workspace.
        $workspace_generator->add_member(
            $workspace,
            $this->tenant_one_user_two->id,
            $this->tenant_one_user_one->id
        );

        $result_two = $this->resolve_graphql_query($query_name, $parameters);
        self::assertIsArray($result_two);
        self::assertNotEmpty($result_two);
        self::assertCount(1, $result_two);

        $result_two_user = reset($result_two);
        self::assertInstanceOf(user::class, $result_two_user);
        self::assertEquals($this->tenant_one_user_two->id, $result_two_user->id);
    }

    /**
     * Within tenant workspace, we are not able to search for system users.
     * @return void
     */
    public function test_find_system_user_as_tenant_member_in_public_workspace_when_add_comment(): void {
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($this->tenant_one_user_one);
        $workspace = $workspace_generator->create_workspace();

        $query_name = $this->get_graphql_name(users_by_pattern::class);
        $parameters = [
            'contextid' => $workspace->get_context()->id,
            'pattern' => $this->system_user->firstname,
            'component' => comment::get_component_name(),
            'area' => comment::COMMENT_AREA
        ];

        $result_one = $this->resolve_graphql_query($query_name, $parameters);
        self::assertIsArray($result_one);
        self::assertEmpty($result_one);

        set_config('tenantsisolated', 1);
        $result_two = $this->resolve_graphql_query($query_name, $parameters);

        self::assertIsArray($result_two);
        self::assertEmpty($result_two);
    }

    /**
     * @return void
     */
    public function test_find_participant_user_as_tenant_member_in_public_workspace_when_add_comment(): void {
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($this->tenant_one_user_one);
        $workspace = $workspace_generator->create_workspace();

        $query_name = $this->get_graphql_name(users_by_pattern::class);
        $parameters = [
            'contextid' => $workspace->get_context()->id,
            'pattern' => $this->tenant_one_participant->firstname,
            'component' => comment::get_component_name(),
            'area' => comment::COMMENT_AREA
        ];

        $result_one = $this->resolve_graphql_query($query_name, $parameters);
        self::assertIsArray($result_one);
        self::assertNotEmpty($result_one);
        self::assertCount(1, $result_one);

        $result_one_user = reset($result_one);
        self::assertInstanceOf(user::class, $result_one_user);
        self::assertEquals($this->tenant_one_participant->id, $result_one_user->id);

        set_config('tenantsisolated', 1);
        $result_two = $this->resolve_graphql_query($query_name, $parameters);

        self::assertIsArray($result_two);
        self::assertNotEmpty($result_two);
        self::assertCount(1, $result_two);

        $result_two_user = reset($result_two);
        self::assertInstanceOf(user::class, $result_two_user);
        self::assertEquals($this->tenant_one_participant->id, $result_two_user->id);
    }
}