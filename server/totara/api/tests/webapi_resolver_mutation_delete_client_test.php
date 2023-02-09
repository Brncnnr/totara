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

use totara_api\testing\generator;
use core_phpunit\testcase;
use totara_core\advanced_feature;
use totara_core\feature_not_available_exception;
use totara_webapi\phpunit\webapi_phpunit_helper;
use core\orm\query\builder;
use totara_api\entity\client;
use totara_api\exception\require_manage_capability_exception;

/**
 * @group totara_api
 */
class webapi_resolver_mutation_delete_client_testcase extends testcase {
    use webapi_phpunit_helper;

    private const MUTATION = 'totara_api_delete_client';

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
    public function test_delete_client_by_admin(): void {
        self::setAdminUser();

        $client = $this->generator()->create_client();
        $client1 = $this->generator()->create_client();

        $db = builder::get_db();
        self::assertTrue($db->record_exists(client::TABLE, ['id' => $client->id]));

        $id = $client->id;
        $result = $this->resolve_graphql_mutation(self::MUTATION, ['id' => $id]);
        self::assertTrue($result);
        self::assertFalse($db->record_exists(client::TABLE, ['id' => $id]));

        self::assertTrue($db->record_exists(client::TABLE, ['id' => $client1->id]));
    }

    /**
     * @return void
     */
    public function test_delete_without_parameters(): void {
        self::setAdminUser();

        self::expectException(require_manage_capability_exception::class);
        $this->resolve_graphql_mutation(self::MUTATION);
    }

    /**
     * @return void
     */
    public function test_delete_invalid_client(): void {
        self::setAdminUser();

        self::expectException(require_manage_capability_exception::class);
        $this->resolve_graphql_mutation(self::MUTATION, ['id' => 123]);
    }

    /**
     * @return void
     */
    public function test_delete_client_by_user(): void {
        $user = $this->generator->create_user();
        self::setUser($user);

        $client = $this->generator()->create_client();

        self::expectException(required_capability_exception::class);
        $this->resolve_graphql_mutation(self::MUTATION, ['id' => $client->id]);
    }


    /**
     * @return void
     */
    public function test_delete_client_with_capabilities(): void {
        $role_id = $this->generator->create_role();
        assign_capability('totara/api:manageclients', CAP_ALLOW, $role_id, context_system::instance());
        $user = $this->generator->create_user();
        role_assign($role_id, $user->id, context_system::instance());
        self::setUser($user);

        $client = $this->generator()->create_client();
        $result = $this->resolve_graphql_mutation(self::MUTATION, ['id' => $client->id]);
        self::assertTrue($result);
    }

    public function test_delete_client_with_tenant_as_user(): void {
        $this->tenant_generator->enable_tenants();
        $tenant = $this->tenant_generator->create_tenant();

        $role_id = $this->generator->create_role();
        assign_capability('totara/api:manageclients', CAP_ALLOW, $role_id, context_system::instance());
        $user = $this->generator->create_user();
        role_assign($role_id, $user->id, context_coursecat::instance($tenant->categoryid));
        self::setUser($user);

        $client = $this->generator()->create_client(['tenant_id' => $tenant->id]);
        $id = $client->id;

        $result = $this->resolve_graphql_mutation(self::MUTATION, ['id' => $id]);
        self::assertTrue($result);
    }

    public function test_delete_client_with_no_tenant_as_tenant_user(): void {
        $this->tenant_generator->enable_tenants();
        $tenant = $this->tenant_generator->create_tenant();

        $role_id = $this->generator->create_role();
        assign_capability('totara/api:manageclients', CAP_ALLOW, $role_id, context_system::instance());
        $user = $this->generator->create_user();
        role_assign($role_id, $user->id, context_coursecat::instance($tenant->categoryid));
        self::setUser($user);

        $client = $this->generator()->create_client();

        $this->expectException(required_capability_exception::class);
        $this->resolve_graphql_mutation(self::MUTATION, ['id' => $client->id]);
    }

    public function test_delete_client_with_invalid_tenant_as_user(): void {
        $this->tenant_generator->enable_tenants();
        $tenant = $this->tenant_generator->create_tenant();
        $tenant2 = $this->tenant_generator->create_tenant();

        $role_id = $this->generator->create_role();
        assign_capability('totara/api:manageclients', CAP_ALLOW, $role_id, context_system::instance());
        $user = $this->generator->create_user();
        role_assign($role_id, $user->id, context_coursecat::instance($tenant->categoryid));
        self::setUser($user);

        $client = $this->generator()->create_client(['tenant_id' => $tenant2->id]);

        $this->expectException(required_capability_exception::class);
        $this->resolve_graphql_mutation(self::MUTATION, ['id' => $client->id]);
    }
}