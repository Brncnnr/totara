<?php
/**
 * This file is part of Totara Learn
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

use core_phpunit\testcase;
use totara_api\pdo\client_service_account;
use totara_api\testing\generator;
use totara_core\advanced_feature;
use totara_core\feature_not_available_exception;
use totara_webapi\phpunit\webapi_phpunit_helper;
use totara_api\exception\require_manage_capability_exception;

/**
 * @group totara_api
 */
class totara_api_webapi_resolver_query_get_client_testcase extends testcase {
    use webapi_phpunit_helper;

    protected const QUERY = 'totara_api_client';

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
     */
    public function test_api_disabled(): void {
        self::setAdminUser();
        advanced_feature::disable('api');

        self::expectException(feature_not_available_exception::class);
        $this->resolve_graphql_query(self::QUERY, ['id' => 2]);
    }

    /**
     * @return void
     */
    public function test_get_client_by_admin(): void {
        self::setAdminUser();

        $client = $this->generator()->create_client();

        $model = $this->resolve_graphql_query(
            self::QUERY,
            [
                'id' => $client->id
            ]
        );

        self::assertNotEmpty($model);
        self::assertEquals($client->id, $model['client']->id);
    }

    /**
     * @return void
     */
    public function test_get_client_by_user(): void {
        $user = $this->generator->create_user();

        self::setUser($user);

        $this->generator()->create_client();

        self::expectException(require_manage_capability_exception::class);
        $this->resolve_graphql_query(
            self::QUERY,
            ['id' => 2]
        );
    }

    /**
     * @return void
     */
    public function test_get_client_with_capabilities(): void {
        $role_id = $this->generator->create_role();
        assign_capability('totara/api:manageclients', CAP_ALLOW, $role_id, context_system::instance());
        $user = $this->generator->create_user();
        role_assign($role_id, $user->id, context_system::instance());

        self::setUser($user);

        $client = $this->generator()->create_client();
        $model = $this->resolve_graphql_query(
            self::QUERY,
            [
                'id' => $client->id
            ]
        );

        self::assertNotEmpty($model);
        self::assertEquals($client->id, $model['client']->id);
    }

    /**
     * @return void
     */
    public function test_view_tenant_client_by_admin(): void {
        self::setAdminUser();

        $this->tenant_generator->enable_tenants();
        $tenant = $this->tenant_generator->create_tenant();
        $client = $this->generator()->create_client(['tenant_id' => $tenant->id]);

        $model = $this->resolve_graphql_query(
            self::QUERY,
            [
                'id' => $client->id
            ]
        );
        self::assertNotEmpty($model);
        self::assertEquals($client->id, $model['client']->id);
    }

    /**
     * @return void
     */
    public function test_view_tenant_client_by_user_with_correct_cap(): void {
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

        $model = $this->resolve_graphql_query(
            self::QUERY,
            [
                'id' => $client1->id,
            ]
        );
        self::assertNotEmpty($model);
        self::assertEquals($client1->id, $model['client']->id);
    }

    /**
     * @return void
     */
    public function test_view_tenant_client_by_user_without_correct_cap(): void {
        $this->tenant_generator->enable_tenants();
        $tenant1 = $this->tenant_generator->create_tenant();

        $this->generator()->create_client();
        $client1 = $this->generator()->create_client(['tenant_id' => $tenant1->id]);

        $user = $this->generator->create_user();
        self::setUser($user);

        self::expectException(required_capability_exception::class);
        $this->resolve_graphql_query(
            self::QUERY,
            [
                'id' => $client1->id,
            ]
        );
    }

    /**
     * @return void
     */
    public function test_get_client_with_wrong_id(): void {
        self::setAdminUser();

        self::expectException(require_manage_capability_exception::class);
        $this->resolve_graphql_query(
            self::QUERY,
            [
                'id' => 3,
            ]
        );
    }


    /**
     * @return void
     */
    public function test_get_client_without_passing_param(): void {
        self::setAdminUser();

        self::expectException(require_manage_capability_exception::class);
        $this->resolve_graphql_query(self::QUERY);
    }

    /**
     * @return void
     */
    public function test_get_client_with_valid_service_account(): void {
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

        // Create a client with an API user assigned.
        $client = $this->generator()->create_client([]);
        $client->user_id = $test_user_id;
        $client->save();

        $model = $this->resolve_graphql_query(
            self::QUERY,
            [
                'id' => $client->id
            ]
        );

        self::assertNotEmpty($model);
        self::assertEquals($client->id, $model['client']->id);
        self::assertNotNull($model['client']->service_account);
        self::assertTrue($model['client']->service_account->get_is_valid());
        self::assertEquals(client_service_account::VALID, $model['client']->service_account->get_status());
        self::assertEquals($client->user->id, $model['client']->service_account->get_user()->id);
    }
}