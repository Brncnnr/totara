<?php
/*
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_webapi
 */

use core\webapi\execution_context;
use GraphQL\Executor\ExecutionResult;
use totara_webapi\graphql;
use totara_webapi\request;
use totara_webapi\server;
use totara_webapi\endpoint_type\factory as endpoint_type_factory;

defined('MOODLE_INTERNAL') || die();

class totara_webapi_server_test extends advanced_testcase {

    public function test_handle_successful_request() {
        $server = new server(execution_context::create(graphql::TYPE_AJAX, 'totara_webapi_status_nosession'));
        $server->set_debug(true);

        $request_params = [
            'operationName' => 'totara_webapi_status_nosession',
            'variables' => []
        ];
        $request = new request(
            endpoint_type_factory::get_instance(graphql::TYPE_AJAX),
            $request_params
        );

        $result = $server->handle_request($request);
        $this->assertInstanceOf(ExecutionResult::class, $result);

        $result = $server->format_response($result);
        $this->assertArrayHasKey('data', $result);
        $data = $result['data'];
        $this->assertArrayHasKey('totara_webapi_status',  $data);
        $data = $data['totara_webapi_status'];
        $this->assertEquals('ok',  $data['status']);
        $this->assertArrayHasKey('status',  $data);
        $this->assertEquals('ok',  $data['status']);
        $this->assertArrayHasKey('timestamp',  $data);
        $this->assertGreaterThan(0,  $data['timestamp']);
    }

    public function test_handle_invalid_request() {
        $server = new server(execution_context::create(graphql::TYPE_AJAX));
        $server->set_debug(true);

        $request_params = [
            'variables' => []
        ];
        $request = new request(
            endpoint_type_factory::get_instance(graphql::TYPE_AJAX),
            $request_params
        );

        $result = $server->handle_request($request);
        $this->assertInstanceOf(ExecutionResult::class, $result);

        $result = $server->format_response($result);
        $this->assertArrayHasKey('errors', $result);

        $errors = $result['errors'];
        $this->assertIsArray($errors);
        $this->assertCount(1, $errors);
        $error = $errors[0];
        $this->assertArrayHasKey('debugMessage',  $error);
        $this->assertEquals('Invalid request, expecting at least operationName and variables',  $error['debugMessage']);
        $this->assertArrayHasKey('message',  $error);
        $this->assertEquals('Internal server error',  $error['message']);
    }

    public function test_batched_queries() {
        $server = new server(execution_context::create(graphql::TYPE_AJAX));
        $server->set_debug(true);

        $request_params = [
            [
                'operationName' => 'totara_webapi_status_nosession',
                'variables' => []
            ],
            [
                'operationName' => 'totara_webapi_status_nosession',
                'variables' => []
            ],
            [
                'operationName' => 'totara_webapi_status_nosession',
                'variables' => []
            ],
        ];
        $request = new request(
            endpoint_type_factory::get_instance(graphql::TYPE_AJAX),
            $request_params
        );

        $results = $server->handle_request($request);
        $this->assertIsArray($results);
        $this->assertContainsOnlyInstancesOf(ExecutionResult::class, $results);
        $this->assertCount(3, $results);

        foreach ($results as $result) {
            $result = $server->format_response($result);
            $this->assertArrayHasKey('data', $result);
            $data = $result['data'];
            $this->assertArrayHasKey('totara_webapi_status', $data);
            $data = $data['totara_webapi_status'];
            $this->assertEquals('ok',  $data['status']);
            $this->assertArrayHasKey('status',  $data);
            $this->assertEquals('ok',  $data['status']);
            $this->assertArrayHasKey('timestamp',  $data);
            $this->assertGreaterThan(0,  $data['timestamp']);

            $this->assertArrayNotHasKey('extensions', $result);
        }
    }

    public function test_batched_queries_with_performance_metrics() {
        // Activate performance config
        set_config('perfdebug', 15);

        $server = new server(execution_context::create(graphql::TYPE_AJAX));
        $server->set_debug(true);

        $request_params = [
            [
                'operationName' => 'totara_webapi_status_nosession',
                'variables' => []
            ],
            [
                'operationName' => 'totara_webapi_status_nosession',
                'variables' => []
            ],
            [
                'operationName' => 'totara_webapi_status_nosession',
                'variables' => []
            ],
        ];
        $request = new request(
            endpoint_type_factory::get_instance(graphql::TYPE_AJAX),
            $request_params
        );

        $results = $server->handle_request($request);
        $this->assertIsArray($results);
        $this->assertContainsOnlyInstancesOf(ExecutionResult::class, $results);
        $this->assertCount(3, $results);

        foreach ($results as $result) {
            $result = $server->format_response($result);
            $this->assertArrayHasKey('data', $result);
            $this->assertArrayHasKey('extensions', $result);
            $this->assertArrayHasKey('performance_data', $result['extensions']);
        }
    }

    public function test_debug() {
        // First run without debug
        $server = new server(execution_context::create(graphql::TYPE_AJAX));
        $server->set_debug(false);

        $request = new request(
            endpoint_type_factory::get_instance(graphql::TYPE_AJAX),
            ['variables' => []]
        );

        $result = $server->handle_request($request);
        $this->assertInstanceOf(ExecutionResult::class, $result);
        $response = $server->format_response($result);
        $this->assertArrayNotHasKey('debugMessage', $response['errors'][0]);

        // Then run with debug
        $server = new server(execution_context::create(graphql::TYPE_AJAX));
        $server->set_debug(true);

        $request = new request(
            endpoint_type_factory::get_instance(graphql::TYPE_AJAX),
            ['variables' => []]
        );

        $result = $server->handle_request($request);
        $this->assertInstanceOf(ExecutionResult::class, $result);
        $response = $server->format_response($result);
        $this->assertArrayHasKey('debugMessage', $response['errors'][0]);
    }

    public function test_automatic_debug() {
        global $CFG;

        // Test debug derived from $CFG->debugdeveloper

        // First run without debug
        $CFG->debugdeveloper = false;
        $server = new server(execution_context::create(graphql::TYPE_AJAX));

        $request = new request(
            endpoint_type_factory::get_instance(graphql::TYPE_AJAX),
            ['variables' => []]
        );

        $result = $server->handle_request($request);
        $this->assertInstanceOf(ExecutionResult::class, $result);
        $response = $server->format_response($result);
        $this->assertArrayNotHasKey('debugMessage', $response['errors'][0]);
        $this->assertArrayNotHasKey('trace', $response['errors'][0]);

        // Then run with debug
        $CFG->debugdeveloper = true;
        $server = new server(execution_context::create(graphql::TYPE_AJAX));

        $request = new request(
            endpoint_type_factory::get_instance(graphql::TYPE_AJAX),
            ['variables' => []]
        );

        $result = $server->handle_request($request);
        $this->assertInstanceOf(ExecutionResult::class, $result);
        $response = $server->format_response($result);
        $this->assertArrayHasKey('debugMessage', $response['errors'][0]);
        $this->assertArrayHasKey('trace', $response['errors'][0]);
    }

    public function test_send_response() {
        $server = new server(execution_context::create(graphql::TYPE_AJAX, 'totara_webapi_status_nosession'));

        $request_params = [
            'operationName' => 'totara_webapi_status_nosession',
            'variables' => []
        ];
        $request = new request(
            endpoint_type_factory::get_instance(graphql::TYPE_AJAX),
            $request_params
        );

        $result = $server->handle_request($request);

        $expected_result = "/\\{\"data\"\:\{\"totara_webapi_status\"\:\{\"status\"\:\"ok\"\,\"timestamp\"\:\"[0-9]+\",\"date\"\:\"[0-9]{1,2}\/[0-9]{2}\/[0-9]{4}\"\}\}\}/";

        $this->expectOutputRegex($expected_result);
        $server->send_response($result, false);
    }

    public function test_dev_type_support_introspection_when_disable_introspection(): void {
        //Disabled introspection.
        set_config('enable_introspection', 0, 'totara_api');

        $server = new server(execution_context::create(graphql::TYPE_DEV));
        $params = [
            'operationName' => null,
            'query' => 'query{ __schema{queryType{name}}}'
        ];

        $request = new request(
            endpoint_type_factory::get_instance(graphql::TYPE_DEV),
            $params
        );
        $result = $server->handle_request($request);
        self::assertNotEmpty($result->data);
        self::assertArrayHasKey('__schema', $result->data);
    }
}