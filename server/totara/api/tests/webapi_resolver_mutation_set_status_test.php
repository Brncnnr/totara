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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_api
 */

use totara_api\testing\generator;
use core_phpunit\testcase;
use totara_core\advanced_feature;
use totara_core\feature_not_available_exception;
use totara_webapi\phpunit\webapi_phpunit_helper;
use totara_api\model\client as client_model;
use totara_tenant\local\util as util_tenant;
use totara_api\exception\update_client_exception;
use totara_api\exception\require_manage_capability_exception;

/**
 * @group totara_api
 */
class webapi_resolver_mutation_set_status_testcase extends testcase {
    use webapi_phpunit_helper;

    private const MUTATION = 'totara_api_set_client_status';

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
     * @return void
     * @throws coding_exception
     */
    public function test_api_disabled(): void {
        self::setAdminUser();
        advanced_feature::disable('api');

        self::expectException(feature_not_available_exception::class);
        $this->resolve_graphql_mutation(self::MUTATION, ['id' => 123, 'status' => false]);
    }

    /**
     * @return void
     */
    public function test_set_status_by_admin(): void {
        self::setAdminUser();

        $client = $this->generator()->create_client();

        self::assertEquals(true, $client->status);

        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'id' => $client->id,
                'status' => false
            ]
        );
        self::assertNotNull($result);
        self::assertInstanceOf(client_model::class, $result);

        $client->refresh();
        self::assertEquals(false, $result->status);
    }


    /**
     * @return void
     */
    public function test_set_status_by_user(): void {
        $client = $this->generator()->create_client();

        $user = $this->generator->create_user();
        self::setUser($user);

        self::expectException(required_capability_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'id' => $client->id,
                'status' => false
            ]
        );
    }

    /**
     * @return void
     */
    public function test_set_status_without_parameters(): void {
        self::setAdminUser();

        self::expectException(require_manage_capability_exception::class);
        $this->resolve_graphql_mutation(self::MUTATION);
    }

    /**
     * @return void
     */
    public function test_set_status_invalid_client(): void {
        self::setAdminUser();

        self::expectException(require_manage_capability_exception::class);
        $this->resolve_graphql_mutation(self::MUTATION, ['id' => 123]);
    }

    /**
     * @return void
     */
    public function test_set_status_with_capabilities(): void {
        $role_id = $this->generator->create_role();
        assign_capability('totara/api:manageclients', CAP_ALLOW, $role_id, context_system::instance());
        $user = $this->generator->create_user();
        role_assign($role_id, $user->id, context_system::instance());
        self::setUser($user);

        $client = $this->generator()->create_client();

        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'id' => $client->id,
                'status' => false
            ]
        );
        self::assertNotNull($result);
        self::assertInstanceOf(client_model::class, $result);

        $client->refresh();
        self::assertEquals(false, $result->status);
    }

    /**
     * @return void
     */
    public function test_set_status_for_client_providers(): void {
        $user = $this->generator->create_user();
        $model = client_model::create('test client', $user->id, null, null, true, ['create_client_provider' => true]);
        self::assertTrue($model->status);

        foreach ($model->oauth2_client_providers->all() as $oauth2_client_provider) {
            self::assertEquals(true, $oauth2_client_provider->status);
        }

        $model->set_client_status(false);

        foreach ($model->oauth2_client_providers->all() as $oauth2_client_provider) {
            self::assertEquals(false, $oauth2_client_provider->status);
        }
    }

    /**
     * @return void
     */
    public function test_set_status_for_tenant_client_when_tenant_suspended_fails(): void {
        // Set up.
        global $CFG;
        self::setAdminUser();
        $original_config = $CFG->tenantsenabled;

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
        self::assertNotNull($model_client);

        // Suspend the tenant.
        util_tenant::update_tenant(['id' => $tenant->id, 'suspended' => 1]);

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
                'id' => $model_client->id,
                'status' => 1
            ]
        );
    }

    /**
     * @return generator
     */
    protected function generator(): generator {
        return generator::instance();
    }
}