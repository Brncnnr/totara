<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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

use core\entity\user;
use core_phpunit\testcase;
use totara_api\global_api_config;
use totara_api\testing\generator;
use totara_core\advanced_feature;
use totara_core\feature_not_available_exception;
use totara_webapi\phpunit\webapi_phpunit_helper;
use totara_api\model\client;

/**
 * @group totara_api
 */
class totara_api_webapi_resolver_query_client_settings_testcase extends testcase {
    use webapi_phpunit_helper;

    protected const QUERY = 'totara_api_client_settings';

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
    public function test_client_settings_by_admin(): void {
        self::setAdminUser();

        $client_settings = $this->generator()->create_client_settings_model(['client_rate_limit' => 100, 'default_token_expiry_time' => 1000]);

        $result = $this->resolve_graphql_query(
            self::QUERY,
            [
                'client_id' => $client_settings->client_id
            ]
        );

        self::assertIsArray($result);
        self::assertEquals(100, $result['client_settings']->client_rate_limit);
        self::assertEquals(1000,$result['client_settings']->default_token_expiry_time);
        self::assertEquals(global_api_config::get_default_token_expiration(),$result['global_settings']['default_token_expiry_time']);
        self::assertEquals(global_api_config::get_max_query_complexity(),$result['global_settings']['max_complexity_cost']);
        self::assertEquals(global_api_config::get_client_rate_limit(),$result['global_settings']['client_rate_limit']);
        self::assertEquals(global_api_config::get_site_rate_limit(),$result['global_settings']['site_rate_limit']);
    }

    /**
     * @return void
     */
    public function test_client_settings_by_authenticated_user(): void {
        $user = $this->generator->create_user();
        self::setUser($user);

        self::expectExceptionMessage('Sorry, but you do not currently have permissions to do that (Manage API clients');
        self::expectException(required_capability_exception::class);
        $client_settings = $this->generator()->create_client_settings_model(['client_rate_limit' => 100, 'default_token_expiry_time' => 1000]);
        $result = $this->resolve_graphql_query(
            self::QUERY,
            [
                'client_id' => $client_settings->client_id
            ]
        );
    }

    /**
     * @return void
     */
    public function test_client_settings_with_cap_assigned(): void {
        $this->assign_caps();
        $user = $this->generator->create_user();
        self::setUser($user);

        $client_settings = $this->generator()->create_client_settings_model(['client_rate_limit' => 100, 'default_token_expiry_time' => 1000]);
        $result = $this->resolve_graphql_query(
            self::QUERY,
            ['client_id' => $client_settings->client_id]
        );

        self::assertIsArray($result);
        self::assertEquals(100, $result['client_settings']->client_rate_limit);
        self::assertEquals(1000,$result['client_settings']->default_token_expiry_time);
        self::assertEquals(global_api_config::get_default_token_expiration(),$result['global_settings']['default_token_expiry_time']);
        self::assertEquals(global_api_config::get_max_query_complexity(),$result['global_settings']['max_complexity_cost']);
        self::assertEquals(global_api_config::get_client_rate_limit(),$result['global_settings']['client_rate_limit']);
        self::assertEquals(global_api_config::get_site_rate_limit(),$result['global_settings']['site_rate_limit']);
    }

    public function test_client_settings_with_tenant(): void {
        $this->tenant_generator->enable_tenants();
        $tenant1 = $this->tenant_generator->create_tenant();
        $tenant2 = $this->tenant_generator->create_tenant();

        $role_id = $this->generator->create_role();
        assign_capability('totara/api:manageclients', CAP_ALLOW, $role_id, context_system::instance());

        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);

        $user2 = $this->generator->create_user();

        role_assign($role_id, $user1->id, context_coursecat::instance($tenant1->categoryid));
        role_assign($role_id, $user2->id, context_coursecat::instance($tenant2->categoryid));

        // Login as user1
        self::setUser($user1);
        $client = client::create('name', $user1->id,'', $tenant1->id, true, ['create_client_provider' => true]);

        // Login as user2
        self::setUser($user2);

        self::expectException(required_capability_exception::class);
        $this->resolve_graphql_query(
            self::QUERY,
            ['client_id' => $client->id]
        );
    }

    /**
     * @return void
     */
    private function assign_caps(): void {
        global $DB;

        $userrole = $DB->get_record('role', ['shortname' => 'user']);
        assign_capability('totara/api:manageclients', CAP_ALLOW, $userrole->id, context_system::instance()->id);
        assign_capability('totara/api:managesettings', CAP_ALLOW, $userrole->id, context_system::instance()->id);
    }
}