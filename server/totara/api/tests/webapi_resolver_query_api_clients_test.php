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
use totara_api\data_provider\client as data_provider;

/**
 * @group totara_api
 */
class totara_api_webapi_resolver_query_api_clients_testcase extends testcase {
    use webapi_phpunit_helper;

    protected const QUERY = 'totara_api_clients';

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
        $this->resolve_graphql_query(self::QUERY);
    }

    /**
     * @return void
     */
    public function test_clients_by_admin(): void {
        self::setAdminUser();

        $client = $this->generator()->create_client();

        $result = $this->resolve_graphql_query(
            self::QUERY
        );

        $result = $result['items'];
        self::assertNotEmpty($result);
        $model = $result[0];
        self::assertEquals($client->id, $model->id);
        self::assertEquals(true, $model->status);
    }

    public function test_clients_pagination(): void {
        self::setAdminUser();

        $clients = [];
        for ($i = 0; $i < data_provider::DEFAULT_PAGE_SIZE; ++$i) {
            // We need to change time created to make the pagination work
            $clients[] = $this->generator()->create_client();
        }
        // Make sure the item is at the bottom of array.
        $clients[] = $this->generator()->create_client(['name' => 'zzz']);

        $result = $this->resolve_graphql_query(
            self::QUERY
        );
        self::assertEquals(data_provider::DEFAULT_PAGE_SIZE, count($result['items']));
        $next = $result['next_cursor'];
        self::assertNotEmpty($next);

        $result = $this->resolve_graphql_query(
            self::QUERY,
            [
                'input' => [
                    'pagination' => [
                        'cursor' => $next
                    ]
                ]
            ]
        );

        self::assertEquals(1, count($result['items']));
        self::assertEquals($clients[data_provider::DEFAULT_PAGE_SIZE]->id, $result['items'][0]->id);
    }

    /**
     * @return void
     */
    public function test_clients_by_user(): void {
        $user = $this->generator->create_user();

        self::setUser($user);

        $this->generator()->create_client();

        self::expectException(required_capability_exception::class);
        $this->resolve_graphql_query(
            self::QUERY
        );
    }

    /**
     * @return void
     */
    public function test_clients_with_capabilities(): void {
        $role_id = $this->generator->create_role();
        assign_capability('totara/api:manageclients', CAP_ALLOW, $role_id, context_system::instance());
        $user = $this->generator->create_user();
        role_assign($role_id, $user->id, context_system::instance());

        self::setUser($user);

        $this->generator()->create_client();

        $result = $this->resolve_graphql_query(
            self::QUERY
        );

        $result = $result['items'];
        self::assertNotEmpty($result);
        self::assertEquals(1, count($result));
    }

    public function test_clients_tenant_filter_as_admin(): void {
        self::setAdminUser();

        $this->tenant_generator->enable_tenants();
        $tenant = $this->tenant_generator->create_tenant();

        $this->generator()->create_client();
        $client = $this->generator()->create_client(['tenant_id' => $tenant->id]);

        $result = $this->resolve_graphql_query(
            self::QUERY,
            [
                'input' => [
                    'tenant_id' => $tenant->id
                ]
            ]
        );
        self::assertEquals(1, count($result['items']));
        self::assertEquals($client->id, $result['items'][0]->id);
    }

    public function test_clients_default_tenant_filter_as_admin(): void {
        self::setAdminUser();

        $this->tenant_generator->enable_tenants();
        $tenant = $this->tenant_generator->create_tenant();

        $client = $this->generator()->create_client();
        $this->generator()->create_client(['tenant_id' => $tenant->id]);

        $result = $this->resolve_graphql_query(self::QUERY);
        self::assertEquals(1, count($result['items']));
        self::assertEquals($client->id, $result['items'][0]->id);
    }

    public function test_clients_tenant_filter_as_user(): void {
        $this->tenant_generator->enable_tenants();
        $tenant1 = $this->tenant_generator->create_tenant();
        $tenant2 = $this->tenant_generator->create_tenant();

        $this->generator()->create_client();
        $client1 = $this->generator()->create_client(['tenant_id' => $tenant1->id]);
        $this->generator()->create_client(['tenant_id' => $tenant2->id]);

        $role_id = $this->generator->create_role();
        assign_capability('totara/api:manageclients', CAP_ALLOW, $role_id, context_system::instance());
        $user = $this->generator->create_user();
        role_assign($role_id, $user->id, context_coursecat::instance($tenant1->categoryid));
        self::setUser($user);

        $result = $this->resolve_graphql_query(
            self::QUERY,
            [
                'input' => [
                    'tenant_id' => $tenant1->id
                ]
            ]
        );
        self::assertEquals(1, count($result['items']));
        self::assertEquals($client1->id, $result['items'][0]->id);
    }

    /**
     * @return void
     */
    public function test_clients_with_valid_service_account(): void {
        self::setAdminUser();

        $gen = self::getDataGenerator();
        // Create a test API user.
        $user = $gen->create_user(['username' => 'user' . (string)uniqid()]);
        $test_user_id = $user->id;
        // Give the API user the required capabilities through a role.
        $role_id = $gen->create_role();
        assign_capability('totara/hierarchy:assignuserposition', CAP_ALLOW, $role_id, context_system::instance());
        assign_capability('moodle/user:viewalldetails', CAP_ALLOW, $role_id, context_system::instance());
        role_assign($role_id, $test_user_id, context_system::instance());

        $client = $this->generator()->create_client([]);
        $client->user_id = $test_user_id;
        $client->save();

        $result = $this->resolve_graphql_query(
            self::QUERY
        );

        $result = $result['items'];
        self::assertNotEmpty($result);
        $model = $result[0];
        self::assertNotNull($model->service_account);
        self::assertTrue($model->service_account->get_is_valid());
        self::assertEquals(client_service_account::VALID, $model->service_account->get_status());
        self::assertEquals($client->user->id, $model->service_account->get_user()->id);
    }
}