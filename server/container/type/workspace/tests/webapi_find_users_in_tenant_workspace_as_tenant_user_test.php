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

use container_workspace\workspace;
use container_workspace\discussion\discussion;
use totara_webapi\phpunit\webapi_phpunit_helper;
use editor_weka\webapi\resolver\query\users_by_pattern;
use core\entity\user;

class container_workspace_webapi_find_users_in_tenant_workspace_as_tenant_user_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @var stdClass|null
     */
    private $tenant_user;

    /**
     * @var stdClass|null
     */
    private $tenant_participant;

    /**
     * @var stdClass|null
     */
    private $system_user;

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
            'tenantid' => $tenant->id
        ]);

        $this->system_user = $generator->create_user([
            'firstname' => uniqid('system_user_'),
            'lastname' => uniqid('system_user_')
        ]);

        $this->tenant_participant = $generator->create_user([
            'firstname' => uniqid('tenant_participant_'),
            'lastname' => uniqid('tenant_participant_')
        ]);

        $tenant_generator->set_user_participation($this->tenant_participant->id, [$tenant->id]);
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        $this->tenant_user = null;
        $this->tenant_participant = null;
        $this->system_user = null;
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
    public function test_find_system_user_in_public_workspace_with_isolation(): void {
        set_config('tenatnsisolated', 1);
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($this->tenant_user);
        $workspace = $workspace_generator->create_workspace();

        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(users_by_pattern::class),
            [
                'component' => workspace::get_type(),
                'area' => discussion::AREA,
                'contextid' => $workspace->get_context()->id,
                'pattern' => $this->system_user->lastname
            ]
        );

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    /**
     * @return void
     */
    public function test_find_system_user_in_public_workspace_without_isolation(): void {
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($this->tenant_user);
        $workspace = $workspace_generator->create_workspace();

        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(users_by_pattern::class),
            [
                'component' => workspace::get_type(),
                'area' => discussion::AREA,
                'contextid' => $workspace->get_context()->id,
                'pattern' => $this->system_user->lastname
            ]
        );

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    /**
     * @return void
     */
    public function test_find_system_user_in_private_workspace_with_isolation(): void {
        set_config('tenatnsisolated', 1);
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($this->tenant_user);
        $workspace = $workspace_generator->create_private_workspace();

        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(users_by_pattern::class),
            [
                'component' => workspace::get_type(),
                'area' => discussion::AREA,
                'contextid' => $workspace->get_context()->id,
                'pattern' => $this->system_user->lastname
            ]
        );

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    /**
     * @return void
     */
    public function test_find_system_user_in_private_workspace_without_isolation(): void {
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($this->tenant_user);
        $workspace = $workspace_generator->create_private_workspace();

        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(users_by_pattern::class),
            [
                'component' => workspace::get_type(),
                'area' => discussion::AREA,
                'contextid' => $workspace->get_context()->id,
                'pattern' => $this->system_user->lastname
            ]
        );

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    /**
     * @return void
     */
    public function test_find_system_user_in_hidden_workspace_with_isolation(): void {
        set_config('tenatnsisolated', 1);
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($this->tenant_user);
        $workspace = $workspace_generator->create_hidden_workspace();

        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(users_by_pattern::class),
            [
                'component' => workspace::get_type(),
                'area' => discussion::AREA,
                'contextid' => $workspace->get_context()->id,
                'pattern' => $this->system_user->lastname
            ]
        );

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    /**
     * @return void
     */
    public function test_find_system_user_in_hidden_workspace_without_isolation(): void {
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($this->tenant_user);
        $workspace = $workspace_generator->create_hidden_workspace();

        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(users_by_pattern::class),
            [
                'component' => workspace::get_type(),
                'area' => discussion::AREA,
                'contextid' => $workspace->get_context()->id,
                'pattern' => $this->system_user->lastname
            ]
        );

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    /**
     * @return void
     */
    public function test_find_tenant_participant_in_public_workspace_with_isolation(): void {
        set_config('tenatnsisolated', 1);
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($this->tenant_user);
        $workspace = $workspace_generator->create_workspace();

        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(users_by_pattern::class),
            [
                'component' => workspace::get_type(),
                'area' => discussion::AREA,
                'contextid' => $workspace->get_context()->id,
                'pattern' => $this->tenant_participant->lastname
            ]
        );

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertCount(1, $result);

        $result_record = reset($result);
        self::assertInstanceOf(user::class, $result_record);
        self::assertEquals($this->tenant_participant->id, $result_record->id);
    }


    /**
     * @return void
     */
    public function test_find_tenant_participant_in_public_workspace_without_isolation(): void {
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($this->tenant_user);
        $workspace = $workspace_generator->create_workspace();

        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(users_by_pattern::class),
            [
                'component' => workspace::get_type(),
                'area' => discussion::AREA,
                'contextid' => $workspace->get_context()->id,
                'pattern' => $this->tenant_participant->lastname
            ]
        );

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertCount(1, $result);

        $result_record = reset($result);
        self::assertInstanceOf(user::class, $result_record);
        self::assertEquals($this->tenant_participant->id, $result_record->id);
    }

    /**
     * @return void
     */
    public function test_find_tenant_participant_in_private_workspace_with_isolation(): void {
        set_config('tenatnsisolated', 1);
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($this->tenant_user);
        $workspace = $workspace_generator->create_private_workspace();

        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(users_by_pattern::class),
            [
                'component' => workspace::get_type(),
                'area' => discussion::AREA,
                'contextid' => $workspace->get_context()->id,
                'pattern' => $this->tenant_participant->lastname
            ]
        );

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertCount(1, $result);

        $result_record = reset($result);
        self::assertInstanceOf(user::class, $result_record);
        self::assertEquals($this->tenant_participant->id, $result_record->id);
    }


    /**
     * @return void
     */
    public function test_find_tenant_participant_in_private_workspace_without_isolation(): void {
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($this->tenant_user);
        $workspace = $workspace_generator->create_private_workspace();

        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(users_by_pattern::class),
            [
                'component' => workspace::get_type(),
                'area' => discussion::AREA,
                'contextid' => $workspace->get_context()->id,
                'pattern' => $this->tenant_participant->lastname
            ]
        );

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertCount(1, $result);

        $result_record = reset($result);
        self::assertInstanceOf(user::class, $result_record);
        self::assertEquals($this->tenant_participant->id, $result_record->id);
    }

    /**
     * @return void
     */
    public function test_find_tenant_participant_as_non_member_in_hidden_workspace_with_isolation(): void {
        set_config('tenatnsisolated', 1);
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($this->tenant_user);
        $workspace = $workspace_generator->create_hidden_workspace();

        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(users_by_pattern::class),
            [
                'component' => workspace::get_type(),
                'area' => discussion::AREA,
                'contextid' => $workspace->get_context()->id,
                'pattern' => $this->tenant_participant->lastname
            ]
        );

        self::assertIsArray($result);
        self::assertEmpty($result);
    }


    /**
     * @return void
     */
    public function test_find_tenant_participant_as_non_member_in_hidden_workspace_without_isolation(): void {
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($this->tenant_user);
        $workspace = $workspace_generator->create_hidden_workspace();

        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(users_by_pattern::class),
            [
                'component' => workspace::get_type(),
                'area' => discussion::AREA,
                'contextid' => $workspace->get_context()->id,
                'pattern' => $this->tenant_participant->lastname
            ]
        );

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    /**
     * @return void
     */
    public function test_find_tenant_participant_as_member_in_hidden_workspace_with_isolation(): void {
        set_config('tenatnsisolated', 1);
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($this->tenant_user);
        $workspace = $workspace_generator->create_hidden_workspace();

        $workspace_generator->add_member(
            $workspace,
            $this->tenant_participant->id,
            $this->tenant_user->id
        );

        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(users_by_pattern::class),
            [
                'component' => workspace::get_type(),
                'area' => discussion::AREA,
                'contextid' => $workspace->get_context()->id,
                'pattern' => $this->tenant_participant->lastname
            ]
        );

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertCount(1, $result);

        $result_record = reset($result);
        self::assertInstanceOf(user::class, $result_record);
        self::assertEquals($this->tenant_participant->id, $result_record->id);
    }


    /**
     * @return void
     */
    public function test_find_tenant_participant_as_member_in_hidden_workspace_without_isolation(): void {
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($this->tenant_user);
        $workspace = $workspace_generator->create_hidden_workspace();
        $workspace_generator->add_member(
            $workspace,
            $this->tenant_participant->id,
            $this->tenant_user->id
        );

        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(users_by_pattern::class),
            [
                'component' => workspace::get_type(),
                'area' => discussion::AREA,
                'contextid' => $workspace->get_context()->id,
                'pattern' => $this->tenant_participant->lastname
            ]
        );

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertCount(1, $result);

        $result_record = reset($result);
        self::assertInstanceOf(user::class, $result_record);
        self::assertEquals($this->tenant_participant->id, $result_record->id);
    }
}