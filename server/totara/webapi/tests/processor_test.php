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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

use core\webapi\execution_context;
use core_phpunit\testcase;
use core\testing\generator;
use GraphQL\Error\DebugFlag;
use GraphQL\Executor\ExecutionResult;
use totara_api\global_api_config;
use totara_api\model\client;
use totara_oauth2\model\client_provider;
use totara_webapi\processor;
use totara_webapi\graphql;
use totara_webapi\request;
use totara_webapi\endpoint_type\factory as endpoint_type_factory;
use totara_oauth2\testing\generator as oauth2_generator;
use totara_webapi\server;

class totara_webapi_processor_testcase extends testcase {

    /** @var generator */
    protected $generator;

    /** @var generator */
    protected $processor_generator;

    /**
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->generator = self::getDataGenerator();
        $this->processor_generator = $this->generator->get_plugin_generator('totara_webapi');
    }
    /**
     * @return void
     */
    protected function tearDown(): void {
        $this->processor_generator = null;
        $this->generator = null;
        parent::tearDown();
    }

    public function test_handle_successful_request() {
        $processor = processor::instance(execution_context::create(graphql::TYPE_AJAX, 'totara_webapi_status_nosession'));

        $request_params = [
            'operationName' => 'totara_webapi_status_nosession',
            'variables' => []
        ];
        $request = new request(
            endpoint_type_factory::get_instance(graphql::TYPE_AJAX),
            $request_params
        );

        $result = $processor->process_request($request);
        $this->assertInstanceOf(ExecutionResult::class, $result);

        $result = $result->toArray(DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE);
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

    public function test_batched_queries() {
        $processor = processor::instance(execution_context::create(graphql::TYPE_AJAX));

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

        $results = $processor->process_request($request);
        $this->assertIsArray($results);
        $this->assertContainsOnlyInstancesOf(ExecutionResult::class, $results);
        $this->assertCount(3, $results);

        foreach ($results as $result) {
            $result = $result->toArray(DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE);
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

    public function test_invalid_type() {
        $types = graphql::get_available_types();

        foreach ($types as $type) {
            // This should not throw an exception
            processor::instance(execution_context::create($type));
        }

        try {
            processor::instance(execution_context::create('foobar'));
            $this->fail("Invalid type should not be allowed");
        } catch (coding_exception $e) {
            $this->assertEquals(
                "Coding error detected, it must be fixed by a programmer: Invalid type 'foobar'",
                $e->getMessage()
            );
        }
    }

    public function test_execute_introspection_query() {
        $processor = processor::instance(execution_context::create(graphql::TYPE_DEV));

        $request_params = [
            'query' => self::get_introspection_query(),
            'variables' => [],
            'operationName' => null
        ];
        $request = new request(
            endpoint_type_factory::get_instance(graphql::TYPE_DEV),
            $request_params
        );

        $result = $processor->process_request($request);
        $this->assertInstanceOf(ExecutionResult::class, $result);
        $this->assertEmpty($result->errors, 'Unexpected errors found in request');
    }

    private static function get_introspection_query(): string {
        // Not getting the types to keep performance impact of this as low as possible.
        // It should still be enough to test that introspection works.
        return '
            query IntrospectionQuery {
                __schema {
                    queryType { name }
                    mutationType { name }
                    subscriptionType { name }
                    directives {
                        name
                        description
                        locations
                        args {
                            ...InputValue
                        }
                    }
                }
            }
        
            fragment InputValue on __InputValue {
                name
                description
                type { ...TypeRef }
                defaultValue
            }
        
            fragment TypeRef on __Type {
                kind
                name
            }
        ';
    }

    /**
     * For the external API, validation rules should be applied (i.e. query's max query depth must be <=
     * global_api_config::get_max_query_depth() ).
     * @dataProvider get_max_depth_test_params_for_external
     * @param int $max_query_depth
     * @param bool $expect_errors
     * @param string $expected_error_message
     * @return void
     * @throws coding_exception
     */
    public function test_max_query_depth_external_api(int $max_query_depth, bool $expect_errors, string $expected_error_message)
    : void {
        // Set up
        $original_config = global_api_config::get_max_query_depth();
        // Set this to a low value so we can see clearly the config is not being used for the AJAX API.
        set_config('max_query_depth', $max_query_depth, 'totara_api');
        $user = self::getDataGenerator()->create_user();
        global $DB;
        $role = $DB->get_record('role', ['archetype' => 'apiuser'], 'id');
        role_assign($role->id, $user->id, context_system::instance());

        $oauth2_generator = oauth2_generator::instance();
        $api_client = client::create(
            '123',
            $user->id,
            null,
            null,
            1,
            ['create_client_provider' => true]
        );
        /** @var client_provider $client_provider_model */
        $client_provider_model = $api_client->oauth2_client_providers->first();
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer " . $oauth2_generator->create_access_token_from_client_provider(
            $client_provider_model->get_entity_copy(),
            time() + HOURSECS
        );

        // Try a test mutation/query that has a higher query depth than the maximum.
        $query = 'mutation {
            core_user_create_user(
                input: {
                    username: "bob"
                    email: "bob@t.com"
                    password: "Abc-1234"
                    firstname: "bob"
                    lastname: "last"  
                }
            ) {
                user {
                    firstname 
                    card_display {
                        profile_picture_url 
                    } 
                } 
            } 
        }';

        ob_start();
        $this->processor_generator->create_external_instance($query)->process('graphql_request');
        $response = ob_get_clean();
        $response_arr = json_decode($response, true);

        // Check there are some validation rules for external API
        $exec_context = execution_context::create(graphql::TYPE_EXTERNAL);
        $this->assertNotEmpty($exec_context->get_endpoint_type()->get_validation_rules());

        // Check validation rules get applied when condition is met
        if ($expect_errors) {
            $this->assertNotEmpty($response_arr['error']);
            $this->assertEquals($expected_error_message, $response_arr['error']);

        } else {
            $this->assertNotEmpty($response_arr['data']['core_user_create_user']);
        }

        // Tear down
        set_config('max_query_depth', $original_config, 'totara_api');
    }

    /**
     * For the Dev API, max_query_depth validation rules should NOT be applied.
     * @dataProvider get_max_depth_test_params_for_dev
     * @param int $max_query_depth
     * @param bool $expect_errors
     * @param string $expected_error_message
     * @return void
     * @throws coding_exception
     */
    public function test_max_query_depth_dev_api(int $max_query_depth, bool $expect_errors) : void {
        // Set up
        $original_config = global_api_config::get_max_query_depth();
        // Set this to a low value so we can see clearly the config is not being used for the AJAX API.
        set_config('max_query_depth', $max_query_depth, 'totara_api');
        $this->setAdminUser();

        $oauth2_generator = oauth2_generator::instance();
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer " . $oauth2_generator->create_access_token_from_client_provider(
            $oauth2_generator->create_client_provider(),
            time() + HOURSECS
        );

        // Try a test mutation/query that has a higher query depth than the maximum.
        $query = 'mutation {
            totara_api_create_client(input: {
                name: "test_client8abcfromapi3"
                description: "description 8fromapi3"
                status: true
            }) {
                id
                name
                description
                tenant_id
                status
                oauth2_client_providers {
                    id
                    client_id
                    client_secret
                }
            }
        }';

        ob_start();

        // Make a request for the Dev API in a different way, for the test environment runner to succeed.
        $exec_context = execution_context::create(graphql::TYPE_DEV);
        $request = new request(
            $exec_context->get_endpoint_type(),
            [
                'operationName' => null,
                'query' => $query
            ]
        );
        $server = new server($exec_context, DebugFlag::INCLUDE_DEBUG_MESSAGE);
        $result = $server->handle_request($request);
        $server->send_response($result, false);

        $response = ob_get_clean();

        // Check there are no validation rules for the dev API
        $this->assertEmpty($exec_context->get_endpoint_type()->get_validation_rules());

        $response_arr = json_decode($response, true);
        if ($expect_errors) {
            $this->assertNotEmpty($response_arr['data']['totara_api_create_client']);
        }

        // Tear down
        set_config('max_query_depth', $original_config, 'totara_api');
    }

    /**
     * For the AJAX API, max_query_depth validation rules should NOT be applied.
     * @dataProvider get_max_depth_test_params_for_dev
     * @param int $max_query_depth
     * @param bool $expect_errors
     * @param string $expected_error_message
     * @return void
     * @throws coding_exception
     */
    public function test_max_query_depth_ajax_api(int $max_query_depth, bool $expect_errors) : void {
        // Set up
        $original_config = global_api_config::get_max_query_depth();
        // Set this to a low value so we can see clearly the config is not being used for the AJAX API.
        set_config('max_query_depth', $max_query_depth, 'totara_api');
        self::setAdminUser();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['CONTENT_TYPE'] = 'application/json';

        // Do a test for an operation that we know has a depth.
        $operation = 'core_users';
        $exec_context = execution_context::create(graphql::TYPE_AJAX, $operation);
        $response = graphql::execute_operation(
            $exec_context,
            [ "lang" => "en" ]
        )->toArray(true);

        // Check there are no validation rules for the AJAX API
        $this->assertEmpty($exec_context->get_endpoint_type()->get_validation_rules());

        if (!$expect_errors) {
            $this->assertNotEmpty($response['data'][$operation]);
        }

        // Tear down
        set_config('max_query_depth', $original_config, 'totara_api');
    }

    /**
     * Data provider for test parameters / results.
     * @return array[]
     */
    public function get_max_depth_test_params_for_external() : array {

        return [
            [
                'max_query_depth' =>  2,
                'expect_errors' => false,
                'expected_error_message' => '',
            ],
            [
                'max_query_depth' =>  1,
                'expect_errors' => true,
                'expected_error_message' => "Max query depth should be 1 but got 2.",
            ],
        ];
    }

    /**
     * Data provider for test parameters / results.
     * @return array[]
     */
    public function get_max_depth_test_params_for_dev() : array {
        return [
            [
                'max_query_depth' =>  2,
                'expect_errors' => false,
            ],
            [
                'max_query_depth' =>  1,
                'expect_errors' => false,
            ],
        ];
    }

}