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

use core_phpunit\testcase;
use totara_api\pdo\client_service_account;
use totara_api\testing\generator;
use totara_core\advanced_feature;
use totara_core\feature_not_available_exception;
use totara_webapi\phpunit\webapi_phpunit_helper;
use totara_api\model\client as client_model;
use totara_tenant\local\util as util_tenant;
use totara_api\exception\update_client_exception;
use totara_api\entity\client as client_entity;
use totara_api\exception\require_manage_capability_exception;

/**
 * @group totara_api
 */
class totara_api_webapi_resolver_mutation_update_client_testcase extends testcase {
    use webapi_phpunit_helper;

    protected const MUTATION = 'totara_api_update_client';

    /** @var \core\testing\generator */
    protected $generator;

    /** @var \totara_tenant\testing\generator */
    protected $tenant_generator;

    protected function setUp(): void {
        parent::setUp();

        $this->generator = self::getDataGenerator();
        $this->tenant_generator = $this->generator->get_plugin_generator('totara_tenant');
    }

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
     * @throws coding_exception
     */
    public function test_api_disabled(): void {
        self::setAdminUser();
        advanced_feature::disable('api');

        self::expectException(feature_not_available_exception::class);
        $this->resolve_graphql_mutation(self::MUTATION, ['id' => 123]);
    }

    /**
     * @return void
     */
    public function test_update_client_by_admin(): void {
        self::setAdminUser();
        $name = 'test name';

        $client = $this->generator()->create_client();

        self::assertEquals(true, $client->status);

        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'id' => $client->id,
                    'name' => $name,
                    'status' => false
                ]
            ]
        );

        self::assertNotNull($result);
        self::assertInstanceOf(client_model::class, $result);
        self::assertEquals($name, $result->name);
        $client->refresh();
        self::assertEquals($name, $client->name);
        self::assertEquals(false, (bool)$client->status);
    }

    /**
     * @return void
     */
    public function test_update_client_by_user(): void {
        $client = $this->generator()->create_client();

        $user = $this->generator->create_user();
        self::setUser($user);

        self::expectException(required_capability_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'id' => $client->id,
                    'name' => 'test name'
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_update_without_parameters(): void {
        self::setAdminUser();

        self::expectException(require_manage_capability_exception::class);
        $this->resolve_graphql_mutation(self::MUTATION);
    }

    /**
     * @return void
     */
    public function test_update_invalid_client(): void {
        self::setAdminUser();

        self::expectException(require_manage_capability_exception::class);
        $this->resolve_graphql_mutation(self::MUTATION, ['id' => 123]);
    }

    /**
     * @return void
     */
    public function test_update_client_with_exception(): void {
        self::setAdminUser();

        $client = $this->generator()->create_client();

        self::expectException(update_client_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'id' => $client->id,
                    'name' => str_repeat('a', 100)
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_update_client_with_capabilities(): void {
        $role_id = $this->generator->create_role();
        assign_capability('totara/api:manageclients', CAP_ALLOW, $role_id, context_system::instance());
        $user = $this->generator->create_user();
        role_assign($role_id, $user->id, context_system::instance());
        self::setUser($user);

        $client = $this->generator()->create_client();

        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'id' => $client->id,
                    'name' => 'test name'
                ]
            ]
        );

        self::assertNotNull($result);
        self::assertInstanceOf(client_model::class, $result);
        self::assertEquals('test name', $result->name);
    }

    /**
     * @return void
     */
    public function test_update_client_with_invalid_description(): void {
        self::setAdminUser();

        $client = $this->generator()->create_client();

        self::expectExceptionMessage('Description must not exceed 1024 characters.');
        self::expectException(update_client_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'id' => $client->id,
                    'description' => str_repeat('a', 1100)
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_update_client_tenant(): void {
        self::setAdminUser();

        $client = $this->generator()->create_client();

        $this->tenant_generator->enable_tenants();
        $tenant = $this->tenant_generator->create_tenant();

        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'id' => $client->id,
                    'tenant_id' => $tenant->id,
                ]
            ]
        );
        self::assertNotNull($result);
        self::assertInstanceOf(client_model::class, $result);
        self::assertNull($result->tenant_id);
        $client->refresh();
        self::assertNull($client->tenant_id);
    }

    /**
     * @return void
     */
    public function test_update_client_with_tenant_as_user(): void {
        $this->tenant_generator->enable_tenants();
        $tenant = $this->tenant_generator->create_tenant();

        $role_id = $this->generator->create_role();
        assign_capability('totara/api:manageclients', CAP_ALLOW, $role_id, context_system::instance());
        $user = $this->generator->create_user();
        role_assign($role_id, $user->id, context_coursecat::instance($tenant->categoryid));
        self::setUser($user);

        $client = $this->generator()->create_client(['tenant_id' => $tenant->id]);

        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'id' => $client->id,
                    'name' => 'test name'
                ]
            ]
        );
        self::assertNotNull($result);
        self::assertInstanceOf(client_model::class, $result);
        self::assertEquals('test name', $result->name);
    }

    /**
     * @return void
     */
    public function test_update_client_without_tenant_as_user(): void {
        $this->tenant_generator->enable_tenants();
        $tenant = $this->tenant_generator->create_tenant();

        $role_id = $this->generator->create_role();
        assign_capability('totara/api:manageclients', CAP_ALLOW, $role_id, context_system::instance());
        $user = $this->generator->create_user();
        role_assign($role_id, $user->id, context_coursecat::instance($tenant->categoryid));
        self::setUser($user);

        $client = $this->generator()->create_client();

        self::expectException(required_capability_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'id' => $client->id,
                    'name' => 'test name'
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_update_client_with_wrong_tenant_as_user(): void {
        $this->tenant_generator->enable_tenants();
        $tenant = $this->tenant_generator->create_tenant();
        $tenant2 = $this->tenant_generator->create_tenant();

        $role_id = $this->generator->create_role();
        assign_capability('totara/api:manageclients', CAP_ALLOW, $role_id, context_system::instance());
        $user = $this->generator->create_user();
        role_assign($role_id, $user->id, context_coursecat::instance($tenant->categoryid));
        self::setUser($user);

        $client = $this->generator()->create_client(['tenant_id' => $tenant2->id]);

        self::expectException(required_capability_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'id' => $client->id,
                    'name' => 'test name'
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_update_client_status(): void {
        self::setAdminUser();
        $user = $this->generator->create_user();

        $model = client_model::create('test client', $user->id, null, null, true, ['create_client_provider' => true]);

        self::assertEquals(true, $model->status);

        foreach ($model->oauth2_client_providers->all() as $oauth2_client_provider) {
            self::assertEquals(1, $oauth2_client_provider->status);
        }

        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'id' => $model->id,
                    'name' => 'name',
                    'status' => false
                ]
            ]
        );

        self::assertNotNull($result);
        self::assertInstanceOf(client_model::class, $result);
        foreach ($result->oauth2_client_providers->all() as $oauth2_client_provider) {
            self::assertEquals(0, $oauth2_client_provider->status);
        }
    }

    /**
     * @return void
     */
    public function test_update_client_and_check_service_account(): void {
        self::setAdminUser();
        $client = $this->generator()->create_client([]);
        self::assertNull($client->user_id);

        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'id' => $client->id,
                    'name' => 'test name'
                ]
            ]
        );

        self::assertNotNull($result);
        $service_account = $result->service_account;
        self::assertNotNull($service_account);
        self::assertFalse($service_account->get_is_valid());
        self::assertEquals(client_service_account::NOUSER, $service_account->get_status());
        self::assertNull($service_account->get_user());
    }

    /**
     * @return array
     */
    private function helper_create_tenant_user_and_client() : array {
        $tenant_generator = $this->getDataGenerator()->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        // Create a tenant.
        $tenant = $this->tenant_generator->create_tenant();

        // Create an API client tenant user with capabilities.
        $gen = self::getDataGenerator();
        // Create a test API user.
        $user = $gen->create_user(['username' => 'user' . (string)uniqid(), 'tenantid' => $tenant->id]);
        $test_user_id = $user->id;
        // Give the API user the required capabilities through a role.
        $role_id = $gen->create_role();
        assign_capability('totara/hierarchy:assignuserposition', CAP_ALLOW, $role_id, context_tenant::instance($tenant->id));
        assign_capability('moodle/user:viewalldetails', CAP_ALLOW, $role_id, context_tenant::instance($tenant->id));
        role_assign($role_id, $test_user_id, context_system::instance());

        // Create an api_client on the tenant.
        $name = 'test';
        $description = 'description_test';
        $model_client = client_model::create($name,  $test_user_id, $description, $tenant->id, true, [
            'create_client_provider' => true
        ]);
        return [$tenant->id, $model_client];
    }

    /*
     * @return void
     */
    public function test_update_status_for_tenant_client_when_tenant_suspended_fails(): void {
        // Set up.
        global $CFG;
        self::setAdminUser();
        $original_config = $CFG->tenantsenabled;

        list($tenant_id, $model_client) = $this->helper_create_tenant_user_and_client();
        self::assertNotNull($model_client);

        // Suspend the tenant.
        util_tenant::update_tenant(['id' => $tenant_id, 'suspended' => 1]);

        // Disable the API_client.
        $model_client->set_client_status(false);

        // Tear down pre-emptive.
        set_config('tenantsenabled', $original_config);

        // Assert beforehand.
        self::expectExceptionMessage('A client in a suspended tenant can not be enabled.');
        self::expectException(update_client_exception::class);

        // Operate - try to update the API_client for a suspended tenant, with the API_client status set to disabled too.
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'id' => $model_client->id,
                    'name' => 'test name updated',
                    'status' => 1
                ]
            ]
        );
    }

    /*
     * @return void
     */
    public function test_update_status_for_tenant_client_when_tenant_valid_succeeds(): void {
        // Set up.
        global $CFG;
        self::setAdminUser();
        $original_config = $CFG->tenantsenabled;

        list($tenant_id, $model_client) = $this->helper_create_tenant_user_and_client();

        // Disable the API_client.
        $model_client->set_client_status(false);

        // Operate - try to update the API_client.
        $updated_name = 'test name updated';
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'id' => $model_client->id,
                    'name' => $updated_name,
                    'status' => 1
                ]
            ]
        );

        $client_updated = client_entity::repository()->find($model_client->id);
        self::assertEquals($updated_name, $client_updated->name);
        self::assertEquals(1, $client_updated->status);

        // Tear down.
        set_config('tenantsenabled', $original_config);
    }

    /**
     * @return void
     */
    public function test_update_service_account(): void {
        self::setAdminUser();
        $user1 = $this->generator->create_user();
        $user2 = $this->generator->create_user();
        $client = $this->generator()->create_client(['user_id' => (int)$user1->id]);

        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'id' => $client->id,
                    'user_id' => $user2->id
                ]
            ]
        );

        self::assertNotNull($result);
        self::assertInstanceOf(client_model::class, $result);
        $service_account = $result->get_service_account();
        self::assertEquals($user2->id, $service_account->get_user()->id);
    }
}