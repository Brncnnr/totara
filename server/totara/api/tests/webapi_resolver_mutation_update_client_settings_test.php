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
use totara_api\testing\generator;
use totara_core\advanced_feature;
use totara_core\feature_not_available_exception;
use totara_webapi\phpunit\webapi_phpunit_helper;
use totara_api\exception\require_manage_capability_exception;
use totara_api\response_debug;
use totara_api\entity\client_settings as entity_client_settings;

/**
 * @group totara_api
 */
class totara_api_webapi_resolver_mutation_update_client_settings_test extends testcase {
    use webapi_phpunit_helper;

    protected const MUTATION = 'totara_api_update_client_settings';

    /** @var \core\testing\generator */
    protected $generator;

    protected function setUp(): void {
        parent::setUp();

        $this->generator = self::getDataGenerator();
    }

    protected function tearDown(): void {
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
        self::resolve_graphql_mutation(self::MUTATION, ['id' => 123]);
    }

    /**
     * @return void
     */
    public function test_update_client_by_admin(): void {
        self::setAdminUser();

        /** @var \totara_api\model\client_settings $client_settings **/
        $client_settings = $this->generator()->create_client_settings_model();

        // Default value from DB
        self::assertEquals(250000, $client_settings->client_rate_limit);
        self::assertEquals(86400, $client_settings->default_token_expiry_time);

        $client_settings = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'client_id' => $client_settings->client_id,
                    'client_rate_limit' => 100,
                    'default_token_expiry_time' => 10,
                    'response_debug' => 'DEVELOPER'
                ]
            ]
        );

        self::assertEquals(100, $client_settings->client_rate_limit);
        self::assertEquals(10, $client_settings->default_token_expiry_time);
    }

    /**
     * @return void
     */
    public function test_update_client_by_authenticated_user(): void {
        $client = $this->generator()->create_client();

        $user = $this->generator->create_user();
        self::setUser($user);

        self::expectException(required_capability_exception::class);
        $client_settings = $this->generator()->create_client_settings_model();

        $client_settings = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'client_id' => $client_settings->client_id,
                    'client_rate_limit' => 100,
                    'default_token_expiry_time' => 10,
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
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'client_id' => 1
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_update_invalid_client_settings(): void {
        self::setAdminUser();

        self::expectException(require_manage_capability_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'id' => 123,
                    'client_id' => 2,
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_update_client_with_exception(): void {
        self::setAdminUser();

        $client = $this->generator()->create_client();

        /** @var \totara_api\model\client_settings $client_settings **/
        $client_settings = $this->generator()->create_client_settings_model();

        self::expectException(\totara_api\exception\update_client_settings_exception::class);
        $client_settings = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'client_id' => $client_settings->client_id,
                    'client_rate_limit' => -100,
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_update_client_with_capabilities(): void {
        $user = $this->generator->create_user();
        self::setUser($user);

        $this->assign_caps();
        /** @var \totara_api\model\client_settings $client_settings **/
        $client_settings = $this->generator()->create_client_settings_model();

        $client_settings = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'client_id' => $client_settings->client_id,
                    'client_rate_limit' => 100,
                    'default_token_expiry_time' => 10,
                    'response_debug' => 'DEVELOPER'
                ]
            ]
        );

        self::assertEquals(100, $client_settings->client_rate_limit);
        self::assertEquals(10, $client_settings->default_token_expiry_time);
        self::assertEquals(response_debug::ERROR_RESPONSE_LEVEL_DEVELOPER, $client_settings->response_debug);
    }

    /**
     * @return void
     */
    public function test_for_optional_field(): void {
        $api_user = $this->generator->create_user();
        self::setUser($api_user);
        $this->assign_caps();

        // Make a test client_settings and set its optional 'response_debug' value.
        $cs_model = $this->generator()->create_client_settings_model();

        $args = ['client_rate_limit' => $cs_model->client_rate_limit,
            'default_token_expiry_time' => $cs_model->default_token_expiry_time,
            'response_debug' => response_debug::ERROR_RESPONSE_LEVEL_NORMAL
        ];
        $cs_model->update($args);

        $client_settings_entity = entity_client_settings::repository()->find($cs_model->id);
        $this->assertEquals(response_debug::ERROR_RESPONSE_LEVEL_NORMAL, $client_settings_entity->response_debug);

        // Make an AJAX API request and do NOT pass in a 'response_debug' value.
        $client_settings_result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'client_id' => $cs_model->client_id,
                    'default_token_expiry_time' => 10,
                    'client_rate_limit' => 100
                ]
            ]
        );

        self::assertEquals(response_debug::ERROR_RESPONSE_LEVEL_NORMAL, $client_settings_result->response_debug);
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