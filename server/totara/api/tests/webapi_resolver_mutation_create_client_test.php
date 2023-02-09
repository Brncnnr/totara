<?php
/**
 * This file is part of Totara Core
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author  Michael Ivanov <michael.ivanov@totaralearning.com>
 * @package totara_api
 */

use core\entity\user;
use core\orm\query\builder;
use core_phpunit\testcase;
use totara_api\exception\create_client_exception;
use totara_api\model\client;
use totara_api\pdo\client_service_account;
use totara_api\testing\generator;
use totara_core\advanced_feature;
use totara_core\feature_not_available_exception;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group totara_api
 */
class totara_api_webapi_resolver_mutation_create_client_testcase extends testcase {
    use webapi_phpunit_helper;

    protected const MUTATION = 'totara_api_create_client';

    /** @var \core\testing\generator */
    protected $generator;

    /** @var \totara_tenant\testing\generator */
    protected $tenant_generator;

    /**
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();

        $this->generator = self::getDataGenerator();
        $this->tenant_generator = $this->generator->get_plugin_generator('totara_tenant');
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        $this->tenant_generator = null;
        $this->generator = null;

        parent::tearDown();
    }

    /**
     * @return generator
     */
    protected function generator(): generator {
        return generator::instance();
    }

    /**
     * @return void
     */
    public function test_api_disabled(): void {
        self::setAdminUser();
        advanced_feature::disable('api');

        self::expectException(feature_not_available_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'name' => 'test name'
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_client_by_admin(): void {
        self::setAdminUser();
        $user = $this->generator->create_user();

        $name = 'test name';

        $db = builder::get_db();
        self::assertFalse($db->record_exists('totara_api_client', ['name' => $name]));
        /** @var client $result */
        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'name' => $name,
                    'user_id' => $user->id
                ]
            ]
        );

        self::assertNotNull($result);
        self::assertEquals($name, $result->name);
        self::assertTrue($db->record_exists('totara_api_client', ['name' => $name]));
        self::assertNotNull($result->oauth2_client_providers->first());
        self::assertEquals(1, $result->status);

        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'name' => $name,
                    'status' => false,
                    'user_id' => $user->id
                ]
             ]
        );

        self::assertEquals(false, $result->status);

    }

    /**
     * @return void
     */
    public function test_create_client_by_user(): void {
        $user = $this->generator->create_user();
        self::setUser($user);

        self::expectException(required_capability_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'name' => 'test name'
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_client_with_exception(): void {
        self::setAdminUser();
        $user = $this->generator->create_user();

        self::expectException(create_client_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'name' => str_repeat('a', 100),
                    'user_id' => $user->id
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_client_with_capabilities(): void {
        $role_id = $this->generator->create_role();
        assign_capability('totara/api:manageclients', CAP_ALLOW, $role_id, context_system::instance());
        $user1 = $this->generator->create_user();
        $user2 = $this->generator->create_user();

        role_assign($role_id, $user1->id, context_system::instance());
        self::setUser($user1);

        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'name' => 'test name',
                    'user_id' => $user2->id
                ]
            ]
        );

        self::assertNotNull($result);
        self::assertEquals('test name', $result->name);
    }

    /**
     * @return void
     */
    public function test_create_client_with_invalid_description(): void {
        self::setAdminUser();
        $user = $this->generator->create_user();

        self::expectExceptionMessage('Description must not exceed 1024 characters.');
        self::expectException(create_client_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'name' => 'test name',
                    'user_id' => $user->id,
                    'description' => str_repeat('a', 1100)
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_client_with_tenant(): void {
        self::setAdminUser();

        $this->tenant_generator->enable_tenants();
        $tenant = $this->tenant_generator->create_tenant();

        $user = $this->getDataGenerator()->create_user(['tenantid' => $tenant->id]);

        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'name' => 'test name',
                    'user_id' => $user->id,
                    'tenant_id' => $tenant->id
                ]
            ]
        );
        self::assertNotNull($result);
        self::assertEquals('test name', $result->name);
        self::assertEquals($tenant->id, $result->tenant_id);
    }

    /**
     * @return void
     */
    public function test_create_client_with_invalid_tenant(): void {
        self::setAdminUser();

        self::expectException(\totara_api\exception\require_manage_capability_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'name' => 'test name',
                    'tenant_id' => 123
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_client_with_tenant_as_user(): void {
        $this->tenant_generator->enable_tenants();
        $tenant = $this->tenant_generator->create_tenant();

        $role_id = $this->generator->create_role();
        assign_capability('totara/api:manageclients', CAP_ALLOW, $role_id, context_system::instance());
        $user = $this->generator->create_user();
        role_assign($role_id, $user->id, context_coursecat::instance($tenant->categoryid));

        $user_entity = user::repository()->find_or_fail($user->id);
        $user_entity->tenantid = $tenant->id;
        $user_entity->save();
        $user_entity->refresh();
        self::setUser($user_entity);

        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'name' => 'test name',
                    'user_id' => $user->id,
                    'tenant_id' => $tenant->id
                ]
            ]
        );
        self::assertNotNull($result);
        self::assertEquals('test name', $result->name);
        self::assertEquals($tenant->id, $result->tenant_id);
    }

    /**
     * @return void
     */
    public function test_create_client_without_tenant_as_user(): void {
        $this->tenant_generator->enable_tenants();
        $tenant = $this->tenant_generator->create_tenant();

        $role_id = $this->generator->create_role();
        assign_capability('totara/api:manageclients', CAP_ALLOW, $role_id, context_system::instance());
        $user = $this->generator->create_user();
        role_assign($role_id, $user->id, context_coursecat::instance($tenant->categoryid));
        self::setUser($user);

        self::expectException(required_capability_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'name' => 'test name'
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_client_with_wrong_tenant_as_user(): void {
        $this->tenant_generator->enable_tenants();
        $tenant = $this->tenant_generator->create_tenant();
        $tenant2 = $this->tenant_generator->create_tenant();

        $role_id = $this->generator->create_role();
        assign_capability('totara/api:manageclients', CAP_ALLOW, $role_id, context_system::instance());
        $user = $this->generator->create_user();
        role_assign($role_id, $user->id, context_coursecat::instance($tenant->categoryid));
        self::setUser($user);

        self::expectException(required_capability_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'name' => 'test name',
                    'tenant_id' => $tenant2->id
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_client_with_service_account_user(): void {
        self::setAdminUser();

        // Create a test API user with capabilities.
        $role_id = $this->generator->create_role();
        assign_capability('totara/api:manageclients', CAP_ALLOW, $role_id, context_system::instance());
        $user = $this->generator->create_user();
        role_assign($role_id, $user->id, context_system::instance());

        $name = 'test name';
        $db = builder::get_db();
        self::assertFalse($db->record_exists('totara_api_client', ['name' => $name]));

        // Operate
        /** @var client $result */
        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'name' => $name,
                    'user_id' => $user->id
                ]
            ]
        );

        // Assert
        self::assertNotNull($result);
        $service_account = $result->get_service_account();
        self::assertNotNull($service_account);
        self::assertTrue($service_account->get_is_valid());
        self::assertEquals(client_service_account::VALID, $service_account->get_status());
        self::assertEquals($user->id, $service_account->get_user()->id);
    }

    /**
     * @return void
     */
    public function test_create_client_with_suspended_tenant(): void {
        self::setAdminUser();
        $user = $this->generator->create_user();

        $this->tenant_generator->enable_tenants();
        $tenant = $this->tenant_generator->create_tenant(['suspended' => 1]);

        self::expectException(create_client_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'name' => 'test name',
                    'user_id' => $user->id,
                    'tenant_id' => $tenant->id
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_client_with_invalid_service_account_user(): void {
        self::setAdminUser();

        self::expectExceptionMessage('No required parameters being passed');
        self::expectException(coding_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'name' => 'test name',
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_client_with_service_account_user_and_empty_tenant(): void {
        self::setAdminUser();
        $user = $this->generator->create_user();

        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'name' => 'test name',
                    'user_id' => $user->id,
                    'tenant_id' => 0
                ]
            ]
        );

        self::assertNull($result->tenant_id);
    }
}