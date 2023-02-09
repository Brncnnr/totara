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
 * @package totara_oauth2
 */

use core\orm\query\builder;
use core_phpunit\testcase;
use totara_api\entity\global_rate_limit as global_rate_limit_entity;
use totara_api\exception\create_client_exception;
use totara_api\model\global_rate_limit;
use totara_webapi\client_aware_exception;
use totara_webapi\controllers\api_controller;
use GraphQL\Error\DebugFlag;
use totara_webapi\controllers\external;
use totara_webapi\request;
use totara_webapi\server;
use totara_oauth2\testing\generator as oauth2_generator;
use totara_api\model\client_rate_limit;
use totara_api\model\client;
use totara_oauth2\entity\client_provider;
use totara_api\entity\client_rate_limit as client_rate_limit_entity;

/**
 * @group totara_oauth2
 */
class totara_oauth2_rate_limit_middleware_testcase extends testcase {
    /**
     * @return void
     */
    public function test_global_rate_limit(): void {
        $global_rate_limit = global_rate_limit::create(100, 100, time()-40, 500);

        self::assertEquals(100, $global_rate_limit->current_window_value);
        $this->set_headers();
        ob_start();
        $this->get_external_instance()->process('graphql_request');
        $response = ob_get_clean();
        $this->assertEquals(
            '{"data":{"totara_webapi_status":{"status":"ok"}}}',
            $response
        );

        $global_rate_limit = global_rate_limit::load_by_id($global_rate_limit->id);
        self::assertEquals(106, $global_rate_limit->current_window_value);
    }

    /**
     * @return void
     */
    public function test_client_rate_limit(): void {
        $user = self::getDataGenerator()->create_user();
        global $DB;
        $role = $DB->get_record('role', ['archetype' => 'apiuser'], 'id');
        role_assign($role->id, $user->id, context_system::instance());
        $client = client::create(
            'test client',
            $user->id,
            '',
            null,
            true,
            ['create_client_provider' => true]
        );

        $client_rate_limit = client_rate_limit::create($client->id, 0, 0, time(), 500);

        self::assertEquals(0, $client_rate_limit->current_window_value);
        $this->set_headers($client);

        ob_start();
        $this->get_external_instance()->process('graphql_request');
        $response = ob_get_clean();
        $this->assertEquals(
            '{"data":{"totara_webapi_status":{"status":"ok"}}}',
            $response
        );

        $client_rate_limit = client_rate_limit::load_by_id($client_rate_limit->id);
        self::assertEquals(6, $client_rate_limit->current_window_value);
    }

    /**
     * @return void
     */
    public function test_global_rate_limit_on_value_rotation(): void {
        $time = mktime(10, 1, 0, 1, 1, 2011);
        $global_rate_limit = global_rate_limit::create(400, 400, $time, 500);

        self::assertEquals(400, $global_rate_limit->current_window_value);
        $this->set_headers();
        ob_start();
        $this->get_external_instance()->process('graphql_request');
        $response = ob_get_clean();
        $this->assertEquals(
            '{"data":{"totara_webapi_status":{"status":"ok"}}}',
            $response
        );

        $global_rate_limit = global_rate_limit::load_by_id($global_rate_limit->id);
        self::assertEquals(6, $global_rate_limit->current_window_value);
    }

    /**
     * @return void
     */
    public function test_client_rate_limit_on_value_rotation(): void {
        $user = self::getDataGenerator()->create_user();
        global $DB;
        $role = $DB->get_record('role', ['archetype' => 'apiuser'], 'id');
        role_assign($role->id, $user->id, context_system::instance());
        $client = client::create(
            'test client',
            $user->id,
            '',
            null,
            true,
            ['create_client_provider' => true]
        );

        $time = mktime(10, 1, 0, 1, 1, 2022);
        $client_rate_limit = client_rate_limit::create($client->id, 400, 400, $time, 500);

        self::assertEquals(400, $client_rate_limit->current_window_value);
        $this->set_headers($client);

        ob_start();
        $this->get_external_instance()->process('graphql_request');
        $response = ob_get_clean();
        $this->assertEquals(
            '{"data":{"totara_webapi_status":{"status":"ok"}}}',
            $response
        );

        $client_rate_limit = client_rate_limit::load_by_id($client_rate_limit->id);
        self::assertEquals(6, $client_rate_limit->current_window_value);
    }


    /**
     * @return void
     */
    public function test_rate_limit_with_exception(): void {
        set_config('max_query_complexity',2, 'totara_api');
        $global_rate_limit = global_rate_limit::create(0, 0, time(), 20);
        self::assertEquals(0, $global_rate_limit->current_window_value);

        $this->set_headers();
        ob_start();
        $this->get_external_instance()->process('graphql_request');
        $response = ob_get_clean();
        $this->assertEquals(
            '{"errors":[{"message":"Query complexity exceeded maximum allowed complexity of 2.",'.
            '"extensions":{"category":"query_complexity"},"locations":[{"line":1,"column":8}],'.
            '"path":["totara_webapi_status"]}],"data":{"totara_webapi_status":null},"error":"Query complexity '.
            'exceeded maximum allowed complexity of 2."}',
            $response
        );

        $global_rate_limit = global_rate_limit::load_by_id($global_rate_limit->id);
        self::assertEquals(5, $global_rate_limit->current_window_value);
    }

    /**
     * @return void
     */
    public function test_rate_limit_without_creation(): void {
        $records = builder::get_db()->get_records(global_rate_limit_entity::TABLE);
        self::assertEmpty($records);

        $user = self::getDataGenerator()->create_user();
        global $DB;
        $role = $DB->get_record('role', ['archetype' => 'apiuser'], 'id');
        role_assign($role->id, $user->id, context_system::instance());
        $client = client::create(
            'test client',
            $user->id,
            '',
            null,
            true,
            ['create_client_provider' => true]
        );
        $this->set_headers($client);

        $records = builder::get_db()->get_records(client_rate_limit_entity::TABLE);
        self::assertEmpty($records);

        ob_start();
        $this->get_external_instance()->process('graphql_request');
        $response = ob_get_clean();
        $this->assertEquals(
            '{"data":{"totara_webapi_status":{"status":"ok"}}}',
            $response
        );

        $records = builder::get_db()->get_records(global_rate_limit_entity::TABLE);
        self::assertNotEmpty($records);
        self::assertEquals(1, count($records));

        $records = builder::get_db()->get_records(client_rate_limit_entity::TABLE);
        self::assertNotEmpty($records);
        self::assertEquals(1, count($records));

    }

    /**
     * @return void
     */
    public function test_site_rate_limit_with_429_response(): void {
        $global_rate_limit = global_rate_limit::create(100, 100, time()-40, 1);
        self::assertEquals(100, $global_rate_limit->current_window_value);

        $this->set_headers();
        ob_start();
        $this->get_external_instance()->process('graphql_request');
        $response = ob_get_clean();
        $this->assertEquals(
            '{"errors":[{"debugMessage":"Coding error detected, it must be fixed by a programmer: '.
            'Too many requests","message":"Internal server error","extensions":{"category":"internal"},'.
            '"locations":[{"line":1,"column":8}],"path":["totara_webapi_status"]}],"data":'.
            '{"totara_webapi_status":null},"error":"Internal server error"}',
            $response
        );
    }

    /**
     * @return void
     */
    public function test_client_rate_limit_with_429_response(): void {
        // As global per client setting rate limit setting is not lower than per client setting,
        // Set it to 0, forcely client setting apply this setting.
        set_config('client_rate_limit', 0, 'totara_api');
        $this->set_headers();
        ob_start();
        $this->get_external_instance()->process('graphql_request');
        $response = ob_get_clean();
        $this->assertStringContainsString(
            'message":"Too many requests',
            $response
        );
    }

    /**
     * @return void
     */
    public function test_execution_context_has_global_rate_limit_model(): void {
        $global_rate_limit = global_rate_limit::create(100, 100, time()-40, 500);
        $this->set_headers();
        ob_start();
        $external_instance = $this->get_external_instance();
        $external_instance->process('graphql_request');
        ob_get_clean();

        $ec_global_rate_limit_model =
            $external_instance->last_execution_context->get_variable('global_rate_limit_model');
        $this->assertInstanceOf(
            global_rate_limit::class,
            $ec_global_rate_limit_model
        );
        $this->assertEquals($global_rate_limit->id, $ec_global_rate_limit_model->id);
    }

    /**
     * @return void
     */
    public function test_execution_context_has_client_rate_limit_model(): void {
        $api_client = $this->create_api_client();
        $client_rate_limit = client_rate_limit::create($api_client->id, 100, 100, time()-40, 500);
        $this->set_headers($api_client);
        ob_start();
        $external_instance = $this->get_external_instance();
        $external_instance->process('graphql_request');
        ob_get_clean();

        $ec_client_rate_limit_model =
            $external_instance->last_execution_context->get_variable('client_rate_limit_model');
        $this->assertInstanceOf(
            client_rate_limit::class,
            $ec_client_rate_limit_model
        );
        $this->assertEquals($client_rate_limit->id, $ec_client_rate_limit_model->id);
    }

    /**
     * @return void
     * @throws coding_exception
     */
    public function test_different_instances_of_global_rate_limit_not_overwriting(): void {
        $global_rate_limit1 = global_rate_limit::create(100, 100, time() - 40, 500);
        $global_rate_limit2 = global_rate_limit::load_by_id($global_rate_limit1->id);
        $global_rate_limit1->add_value(10);
        $global_rate_limit2->add_value(20);
        $this->assertEquals(110, $global_rate_limit1->current_window_value);
        $this->assertEquals(130, $global_rate_limit2->current_window_value);
        $global_rate_limit1->refresh();
        $this->assertEquals(130, $global_rate_limit1->current_window_value);
    }

    /**
     * @return void
     * @throws create_client_exception
     * @throws coding_exception
     * @throws dml_exception
     */
    public function test_different_instances_of_client_rate_limit_not_overwriting(): void {
        $api_client = $this->create_api_client();
        $client_rate_limit1 = client_rate_limit::create($api_client->id, 100, 100, time() - 40, 500);
        $client_rate_limit2 = client_rate_limit::load_by_id($client_rate_limit1->id);
        $client_rate_limit1->add_value(20);
        $client_rate_limit2->add_value(30);
        $this->assertEquals(120, $client_rate_limit1->current_window_value);
        $this->assertEquals(150, $client_rate_limit2->current_window_value);
        $client_rate_limit1->refresh();
        $this->assertEquals(150, $client_rate_limit1->current_window_value);
    }

    /**
     * @param client|null $api_client
     * @return void
     */
    private function set_headers(client $api_client = null): void {
        $oauth2_generator = oauth2_generator::instance();
        if (empty($api_client)) {
            $api_client = $this->create_api_client();
        }
        /** @var \totara_oauth2\model\client_provider $client_provider */
        $client_provider = $api_client->oauth2_client_providers->first();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Linux x86_64; rv:100.0) Gecko/20100101 Firefox/100.0';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $_SERVER['CONTENT_LENGTH'] = '101';
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer " . $oauth2_generator->create_access_token_from_client_provider(
                $client_provider->get_entity_copy(),
                time() + HOURSECS
            );
    }

    /**
     * @return api_controller
     */
    private function get_external_instance(): api_controller {
        $class = new class() extends external {
            public $last_execution_context;

            public function action_graphql_request(): void {
                $execution_context = $this->get_execution_context();
                $this->last_execution_context = $execution_context;
                $request = new request(
                    $execution_context->get_endpoint_type(),
                    [
                        'operationName' => null,
                        'query' => 'query {totara_webapi_status {status}}',
                    ]
                );
                $server = new server($execution_context, DebugFlag::INCLUDE_DEBUG_MESSAGE);
                $result = $server->handle_request($request);
                $server->send_response($result, false);
            }
        };

        return new $class(false);
    }

    /**
     * @return client
     * @throws create_client_exception
     * @throws coding_exception
     * @throws dml_exception
     */
    private function create_api_client(): client {
        $user = self::getDataGenerator()->create_user();
        global $DB;
        $role = $DB->get_record('role', ['archetype' => 'apiuser'], 'id');
        role_assign($role->id, $user->id, context_system::instance());
        return client::create(
            '123',
            $user->id,
            null,
            null,
            true,
            ['create_client_provider' => true]
        );
    }

}