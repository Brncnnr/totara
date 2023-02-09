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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_webapi
 */

defined('MOODLE_INTERNAL') || die();

use core\webapi\execution_context;
use core_phpunit\testcase;
use GraphQL\Error\DebugFlag;
use totara_api\global_api_config;
use totara_api\response_debug;
use totara_core\advanced_feature;
use totara_webapi\controllers\ajax;
use totara_webapi\controllers\api_controller;
use totara_webapi\controllers\dev;
use totara_webapi\controllers\external;
use totara_webapi\graphql;
use totara_webapi\request;
use totara_webapi\server;
use totara_oauth2\testing\generator as oauth2_generator;
use totara_api\model\client as api_client_model;

class totara_webapi_api_testcase extends testcase {

    /**
     * @return api_controller
     */
    private function get_ajax_instance(): api_controller {
        $class = new class() extends ajax {
            public function action_graphql_request(): void {
                $execution_context = $this->get_execution_context();
                $request = new request(
                    $execution_context->get_endpoint_type(),
                    [
                        'operationName' => 'totara_webapi_status',
                        'variables' => [],
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
     * @string|null $query
     * @return api_controller
     */
    private function get_external_instance(?string $query = null): api_controller {
        $class = new class() extends external {
            /** @var string */
            protected $query;

            /** @var server */
            public $server;

            public function __construct(?bool $stop_execution = true, ?string $query = null)
            {
                $this->query = $query;
                parent::__construct($stop_execution);
            }

            public function action_graphql_request(): void {
                $execution_context = $this->get_execution_context();
                $request = new request(
                    $execution_context->get_endpoint_type(),
                    [
                        'operationName' => null,
                        'query' => $this->query ?? 'query {totara_webapi_status {status}}',
                    ]
                );
                $this->server = new server($execution_context, DebugFlag::INCLUDE_DEBUG_MESSAGE);
                $result = $this->server->handle_request($request);
                $this->server->send_response($result, false);
            }
        };

        return new $class(false, $query);
    }

    /**
     * @return void
     */
    public function test_valid_ajax(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        ob_start();
        $this->get_ajax_instance()->process('graphql_request');
        $response = ob_get_clean();
        $this->assertStringContainsString(
            '{"data":{"totara_webapi_status":{"status":"ok"',
            $response
        );
    }

    /**
     * @return void
     */
    public function test_ajax_invalid_request_method(): void {
        $_SERVER['REQUEST_METHOD'] = '';
        ob_start();
        $this->get_ajax_instance()->process('graphql_request');
        $response = ob_get_clean();
        $this->assertEquals(
            '{"errors":[{"message":"Invalid webapi request, only POST method is allowed"}],"error":'
            . '"Invalid webapi request, only POST method is allowed"}',
            $response
        );
    }

    /**
     * @return void
     */
    public function test_ajax_invalid_action(): void {
        global $CFG;

        // Test invalid action.
        $old_log = ini_get('error_log');
        $new_log = "$CFG->dataroot/testlog.log";
        ini_set('error_log', $new_log); // Prevent standard logging.

        $_SERVER['REQUEST_METHOD'] = 'POST';
        ob_start();
        $this->get_ajax_instance()->process('does_not_exist');
        $response = ob_get_clean();
        $this->assertEquals(
            '{"errors":[{"message":"Unknown internal error"}],"error":"Unknown internal error"}',
            $response
        );
        ini_set('error_log', $old_log);

        $error = file_get_contents($new_log);
        $this->assertStringContainsString(
            'API error: exception during set up stage - Missing action method action_does_not_exist',
            $error
        );
    }

    /**
     * @return void
     */
    public function test_dev(): void {
        self::markTestSkipped('Skip this test as it is failing in CI system on mariadb/mysql');
        $class = new class() extends dev {
            public function action_graphql_request(): void {
                $execution_context = $this->get_execution_context();
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

        ob_start();
        (new $class(false))->process('graphql_request');
        $response = ob_get_clean();
        $this->assertEquals(
            '{"data":{"totara_webapi_status":{"status":"ok"}}}',
            $response
        );
    }

    /**
     * @return void
     */
    public function test_external_no_headers(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        ob_start();
        $this->get_external_instance()->process('graphql_request');
        $response = ob_get_clean();
        $this->assertStringContainsString(
            'Missing or invalid HTTP request headers',
            $response
        );
    }

    /**
     * @return void
     */
    public function test_external_require_token(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Linux x86_64; rv:100.0) Gecko/20100101 Firefox/100.0';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $_SERVER['CONTENT_LENGTH'] = '101';

        ob_start();
        $this->get_external_instance()->process('graphql_request');
        $response = ob_get_clean();
        $this->assertStringContainsString(
            'The request did not contain the required Authorization header.',
            $response
        );
    }

    /**
     * @return void
     */
    public function test_external_valid_token_no_api_client(): void {
        $generator = oauth2_generator::instance();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Linux x86_64; rv:100.0) Gecko/20100101 Firefox/100.0';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $_SERVER['CONTENT_LENGTH'] = '101';
        $access_token = $generator->create_access_token_from_client_provider(
            $generator->create_client_provider(),
            time() + HOURSECS
        );
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer " . $access_token;

        ob_start();
        $this->get_external_instance()->process('graphql_request');
        $response = ob_get_clean();
        $this->assertStringContainsString(
            'Couldn\'t identify API client for this request',
            $response
        );
    }

    /**
     * @return void
     */
    public function test_external_valid_token(): void {
        $generator = oauth2_generator::instance();
        $user = self::getDataGenerator()->create_user();
        global $DB;
        $role = $DB->get_record('role', ['archetype' => 'apiuser'], 'id');
        role_assign($role->id, $user->id, context_system::instance());
        $api_client = \totara_api\model\client::create(
            '123',
            $user->id,
            null,
            null,
            1,
            ['create_client_provider' => true]
        );
        /** @var \totara_oauth2\model\client_provider $client_provider */
        $client_provider = $api_client->oauth2_client_providers->first();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Linux x86_64; rv:100.0) Gecko/20100101 Firefox/100.0';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $_SERVER['CONTENT_LENGTH'] = '101';
        $access_token = $generator->create_access_token_from_client_provider(
            $client_provider->get_entity_copy(),
            time() + HOURSECS
        );
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer " . $access_token;

        ob_start();
        $this->get_external_instance()->process('graphql_request');
        $response = ob_get_clean();
        $this->assertEquals(
            '{"data":{"totara_webapi_status":{"status":"ok"}}}',
            $response
        );
    }

    /**
     * @return void
     */
    public function test_external_validate_user(): void {
        $generator = oauth2_generator::instance();
        $user = self::getDataGenerator()->create_user();
        global $DB;
        $role = $DB->get_record('role', ['archetype' => 'apiuser'], 'id');
        role_assign($role->id, $user->id, context_system::instance());
        $api_client = \totara_api\model\client::create(
            '123',
            $user->id,
            null,
            null,
            1,
            ['create_client_provider' => true]
        );
        /** @var \totara_oauth2\model\client_provider $client_provider */
        $client_provider = $api_client->oauth2_client_providers->first();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Linux x86_64; rv:100.0) Gecko/20100101 Firefox/100.0';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $_SERVER['CONTENT_LENGTH'] = '101';
        $access_token = $generator->create_access_token_from_client_provider(
            $client_provider->get_entity_copy(),
            time() + HOURSECS
        );
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer " . $access_token;

        ob_start();
        $this->get_external_instance()->process('graphql_request');
        ob_end_clean();
        $this->assertEquals($user->id, $GLOBALS['USER']->id);
    }

    /**
     * @return void
     */
    public function test_external_invalid_token(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Linux x86_64; rv:100.0) Gecko/20100101 Firefox/100.0';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $_SERVER['CONTENT_LENGTH'] = '101';
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer iNvAlIdTokEN";

        ob_start();
        $this->get_external_instance()->process('graphql_request');
        $response = ob_get_clean();
        $this->assertStringContainsString(
            'Missing, expired or invalid access token',
            $response
        );
    }

    /**
     * @return void
     * @throws coding_exception
     */
    public function test_external_feature_disabled(): void {
        advanced_feature::disable('api');
        $_SERVER['REQUEST_METHOD'] = 'POST';

        ob_start();
        $this->get_external_instance()->process('graphql_request');
        $response = ob_get_clean();
        $this->assertEquals(
            '{"errors":[{"message":"Feature api is not available."}],"error":"Feature api is not available."}',
            $response
        );
    }

    /**
     * @return void
     */
    public function test_external_log_origin_cli(): void {
        $this->setAdminUser();
        $this->create_user();
        $this->redirectEvents();

        // Confirm that the log entries have 'cli' as origin.
        $this->validate_log_entries(1, 'cli');
    }

    /**
     * @return void
     */
    public function test_external_log_origin_api(): void {
        $this->setRequestOrigin('EXTERNAL_API');
        $this->setAdminUser();
        $this->create_user();

        // Confirm that the log entries have 'api' as origin.
        $this->validate_log_entries(1, 'api');
        $this->setRequestOrigin(null);
    }

    /**
     * @return void
     */
    private function create_user(): void {
        global $CFG;

        // Enable logstore_standard.
        set_config('enabled_stores', 'logstore_standard', 'tool_log');

        // Create new user.
        require_once($CFG->dirroot . '/user/lib.php');
        $data = new stdClass();
        $data->username = 'user1';
        $data->firstname = 'user';
        $data->lastname = 'one';
        $data->email = 'user1@example.com';
        user_create_user($data);
    }

    /**
     * @param int $count
     * @param string $origin
     * @return void
     */
    private function validate_log_entries(int $count, string $origin): void {
        global $DB;

        $manager = get_log_manager(true);
        $this->assertCount(1, $manager->get_readers());
        $log_entries = $DB->get_records('logstore_standard_log', array(), 'id ASC');
        $this->assertCount(1, $log_entries);
    }

    /**
     * @return void
     */
    public function test_action_graphql_request_from_api_controller(): void {
        $api_controller = new class(false) extends api_controller {
            protected function get_execution_context(): execution_context {
                return execution_context::create(graphql::TYPE_AJAX);
            }
        };
        $_SERVER['REQUEST_METHOD'] = 'POST';
        ob_start();
        $api_controller->process('graphql_request');
        $response = ob_get_clean();

        // No request is passed into action_graphql_request() from api_controller class.
        self::assertStringContainsString('Invalid request, request cannot be empty', $response);
    }

    /**
     * A helper method to make an external API controller instance, as similar as we can to a real one with a request
     * coming from the web. This case is for a an update user request that will always throw an error (no user is found
     * to update).
     * @return api_controller
     */
    private function helper_get_external_api_instance_for_bad_request(): api_controller {
        $class = new class() extends external {
            public function action_graphql_request(): void {
                $execution_context = $this->get_execution_context();
                $nonsense_id_number = 'nonsensenonsense' . uniqid();
                $request = new request(
                    $execution_context->get_endpoint_type(),
                    [
                        'operationName' => null,
                        'query' => 'mutation {
                            core_user_update_user(
                                target_user: {
                                    id: 99999
                                    idnumber: "' . $nonsense_id_number . '"
                                }
                                input: {
                                    firstname: "newbob updated"
                                }
                            ) {
                                user {
                                    id
                                    firstname
                                 }
                            }
                        }'
                    ]
                );
                $server = new server($execution_context, global_api_config::get_response_debug());
                $result = $server->handle_request($request);
                $server->send_response($result, false);
            }
        };
        return new $class(false);
    }

    /**
     * @return void
     * @throws \totara_api\exception\create_client_exception
     * @throws coding_exception
     * @throws dml_exception
     */
    public function test_external_api_invalid_error_response_levels_for_user_update(): void {
        global $DB;
        self::setAdminUser();
        $original_response_debug_setting = global_api_config::get_response_debug();

        // Set up.
        // Create an (API client) service account user with capabilities.
        $service_account_user = self::getDataGenerator()->create_user();
        $role = $DB->get_record('role', ['archetype' => 'apiuser'], 'id');
        role_assign($role->id, $service_account_user->id, context_system::instance());

        // Create an API client with the user assigned.
        $api_client = api_client_model::create('123', $service_account_user->id, null, null, 1,
            ['create_client_provider' => true]
        );

        // Set auth. in request headers.
        $client_provider_model = $api_client->oauth2_client_providers->first();
        $oauth2_generator = oauth2_generator::instance();
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer " . $oauth2_generator->create_access_token_from_client_provider(
            $client_provider_model->get_entity_copy(), time() + HOURSECS
        );

        // request 1
        set_config('response_debug', response_debug::ERROR_RESPONSE_LEVEL_NONE, 'totara_api');
        // Make an External API request with our query.
        ob_start();
        $external_controller = $this->helper_get_external_api_instance_for_bad_request();
        $external_controller->process('graphql_request'); // operate
        $response = ob_get_clean();
        $response_arr_level_none = json_decode($response, true);

        $this->assertArrayNotHasKey('debugMessage', $response_arr_level_none['errors'][0]);

        // request 2
        set_config('response_debug', response_debug::ERROR_RESPONSE_LEVEL_NORMAL, 'totara_api');
        // Make an External API request with our query.
        ob_start();
        $external_controller = $this->helper_get_external_api_instance_for_bad_request();
        $external_controller->process('graphql_request'); // operate
        $response = ob_get_clean();
        $response_arr_level_normal = json_decode($response, true);

        // request 3
        set_config('response_debug', response_debug::ERROR_RESPONSE_LEVEL_DEVELOPER, 'totara_api');
        // Make an External API request with our query.
        ob_start();
        $external_controller = $this->helper_get_external_api_instance_for_bad_request();
        $external_controller->process('graphql_request'); // operate
        $response = ob_get_clean();
        $response_arr_level_dev = json_decode($response, true);

        // Assert.
        // All error response levels should contain a basic message.
        $responses_for_basic_checks = [
            $response_arr_level_none,
            $response_arr_level_normal,
            $response_arr_level_dev
        ];
        foreach ($responses_for_basic_checks as $response_arr) {
            $this->assertNotEmpty($response_arr['errors']);
            $this->assertEquals('Internal server error', $response_arr['errors'][0]['message']);
        }

        // The 'none' response level should not contain a 'debugMessage' or a 'trace'.
        $this->assertArrayNotHasKey('debugMessage', $response_arr_level_none['errors'][0]);
        $this->assertArrayNotHasKey('trace', $response_arr_level_none['errors'][0]);

        // The 'normal' response level should contain a 'debugMessage' but not a 'trace'.
        $this->assertArrayHasKey('debugMessage', $response_arr_level_normal['errors'][0]);
        $this->assertEquals('There was a problem finding a single user record match or you do not have sufficient capabilities.',
            $response_arr_level_normal['errors'][0]['debugMessage']
        );
        $this->assertArrayNotHasKey('trace', $response_arr_level_none['errors'][0]);

        // The 'developer' response level should contain a 'trace'.
        $this->assertArrayHasKey('trace', $response_arr_level_dev['errors'][0]);
        $this->assertNotEmpty($response_arr_level_dev['errors'][0]['trace']);
        $this->assertArrayHasKey('line', $response_arr_level_dev['errors'][0]['trace'][0]);

        // Tear down.
        set_config('response_debug', $original_response_debug_setting, 'totara_api');
    }

    /**
     * @return void
     */
    public function test_external_api_client_debug_level(): void {
        $generator = oauth2_generator::instance();
        $user = self::getDataGenerator()->create_user();
        global $DB;
        $role = $DB->get_record('role', ['archetype' => 'apiuser'], 'id');
        role_assign($role->id, $user->id, context_system::instance());
        $api_client = \totara_api\model\client::create(
            '123',
            $user->id,
            null,
            null,
            1,
            ['create_client_provider' => true]
        );

        $args = ['client_rate_limit' => null,
            'default_token_expiry_time' => 3600,
            'response_debug' => response_debug::ERROR_RESPONSE_LEVEL_NORMAL
        ];

        $api_client->client_settings->update($args);
        /** @var \totara_oauth2\model\client_provider $client_provider */
        $client_provider = $api_client->oauth2_client_providers->first();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Linux x86_64; rv:100.0) Gecko/20100101 Firefox/100.0';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $_SERVER['CONTENT_LENGTH'] = '101';
        $access_token = $generator->create_access_token_from_client_provider(
            $client_provider->get_entity_copy(),
            time() + HOURSECS
        );
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer " . $access_token;

        ob_start();
        $instance = $this->get_external_instance();
        $instance->process('graphql_request');
        ob_get_clean();
        $server_reflection = new ReflectionObject($instance->server);
        $debug_level = $server_reflection->getProperty('debug');
        $debug_level->setAccessible(true);
        $this->assertEquals(
            global_api_config::get_response_debug_flag(response_debug::ERROR_RESPONSE_LEVEL_NORMAL),
            $debug_level->getValue($instance->server)
        );

        $args = ['client_rate_limit' => null,
            'default_token_expiry_time' => 3600,
            'response_debug' => response_debug::ERROR_RESPONSE_LEVEL_DEVELOPER
        ];
        $api_client->client_settings->update($args);
        ob_start();
        $instance = $this->get_external_instance();
        $instance->process('graphql_request');
        ob_get_clean();
        $this->assertEquals(
            global_api_config::get_response_debug_flag(response_debug::ERROR_RESPONSE_LEVEL_DEVELOPER),
            $debug_level->getValue($instance->server)
        );

        $args['response_debug'] =  response_debug::ERROR_RESPONSE_LEVEL_NONE;
        $api_client->client_settings->update($args);
        ob_start();
        $instance = $this->get_external_instance();
        $instance->process('graphql_request');
        ob_get_clean();
        $this->assertEquals(
            global_api_config::get_response_debug_flag(response_debug::ERROR_RESPONSE_LEVEL_NONE),
            $debug_level->getValue($instance->server)
        );
    }

    /**
     * @return void
     */
    public function test_external_api_client_debug_level_fallback_to_global(): void {
        $generator = oauth2_generator::instance();
        $user = self::getDataGenerator()->create_user();
        global $DB;
        $role = $DB->get_record('role', ['archetype' => 'apiuser'], 'id');
        role_assign($role->id, $user->id, context_system::instance());
        $api_client = \totara_api\model\client::create(
            '123',
            $user->id,
            null,
            null,
            1,
            ['create_client_provider' => true]
        );

        $args = ['client_rate_limit' => null,
            'default_token_expiry_time' => 3600,
            'response_debug' => null
        ];
        $api_client->client_settings->update($args);
        /** @var \totara_oauth2\model\client_provider $client_provider */
        $client_provider = $api_client->oauth2_client_providers->first();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Linux x86_64; rv:100.0) Gecko/20100101 Firefox/100.0';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $_SERVER['CONTENT_LENGTH'] = '101';
        $access_token = $generator->create_access_token_from_client_provider(
            $client_provider->get_entity_copy(),
            time() + HOURSECS
        );
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer " . $access_token;

        ob_start();
        $instance = $this->get_external_instance();
        $instance->process('graphql_request');
        ob_get_clean();
        $server_reflection = new ReflectionObject($instance->server);
        $debug_level = $server_reflection->getProperty('debug');
        $debug_level->setAccessible(true);
        $this->assertEquals(global_api_config::get_response_debug_flag(), $debug_level->getValue($instance->server));
    }

    public function test_api_response_performance_data(): void {
        global $CFG;

        $CFG->perfdebug = 15;
        $server = new server(execution_context::create(graphql::TYPE_EXTERNAL));
        $request = new request(
            \totara_webapi\endpoint_type\factory::get_instance(graphql::TYPE_EXTERNAL),
            [
                'operationName' => null,
                'query' => 'query {totara_webapi_status {status}}'
            ]
        );
        $result = $server->handle_request($request);
        $this->assertArrayHasKey('complexity_data', $result->extensions);
    }

    /**
     * @return void
     */
    public function test_disable_introspection(): void {
        global $DB;

        $generator = oauth2_generator::instance();
        $user = self::getDataGenerator()->create_user();
        $role = $DB->get_record('role', ['archetype' => 'apiuser'], 'id');
        role_assign($role->id, $user->id, context_system::instance());
        $api_client = \totara_api\model\client::create(
            '123',
            $user->id,
            null,
            null,
            1,
            ['create_client_provider' => true]
        );
        /** @var \totara_oauth2\model\client_provider $client_provider */
        $client_provider = $api_client->oauth2_client_providers->first();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Linux x86_64; rv:100.0) Gecko/20100101 Firefox/100.0';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $_SERVER['CONTENT_LENGTH'] = '101';
        $access_token = $generator->create_access_token_from_client_provider(
            $client_provider->get_entity_copy(),
            time() + HOURSECS
        );
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer " . $access_token;

        ob_start();
        $this->get_external_instance("query{ __schema{queryType{name}}}")->process('graphql_request');
        $response = ob_get_clean();
        $this->stringContains(
            '"error":"GraphQL introspection is not enabled."',
            $response
        );

        //Enabled introspection.
        set_config('enable_introspection', 1, 'totara_api');
        ob_start();
        $this->get_external_instance("query{ __schema{queryType{name}}}")->process('graphql_request');
        $response = ob_get_clean();
        $this->assertEquals(
            '{"data":{"__schema":{"queryType":{"name":"Query"}}}}',
            $response
        );
    }

    /**
     * @return void
     */
    public function test_query_introspection_with_invalid_token(): void {
        //Enabled introspection.
        set_config('enable_introspection', 1, 'totara_api');

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Linux x86_64; rv:100.0) Gecko/20100101 Firefox/100.0';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $_SERVER['CONTENT_LENGTH'] = '101';
        $_SERVER['HTTP_AUTHORIZATION'] = "invalid token";

        ob_start();
        $this->get_external_instance("query{ __schema{queryType{name}}}")->process('graphql_request');
        $response = ob_get_clean();
        $this->assertStringContainsString(
            'Missing, expired or invalid access token. Ensure the Authorization header is set to a valid Bearer token.',
            $response
        );
    }

    /**
     * Test the query complexity is under limit, and the type complexity is under limit, but together in one request, it goes over the limit.
     *
     * @return void
     */
    public function test_max_query_complexity_on_exception_with_combination_of_resolvers(): void {
        global $DB;

        $generator = oauth2_generator::instance();
        $user = self::getDataGenerator()->create_user();
        $role = $DB->get_record('role', ['archetype' => 'apiuser'], 'id');
        role_assign($role->id, $user->id, context_system::instance());
        $api_client = \totara_api\model\client::create(
            '123',
            $user->id,
            null,
            null,
            1,
            ['create_client_provider' => true]
        );
        /** @var \totara_oauth2\model\client_provider $client_provider */
        $client_provider = $api_client->oauth2_client_providers->first();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Linux x86_64; rv:100.0) Gecko/20100101 Firefox/100.0';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $_SERVER['CONTENT_LENGTH'] = '101';
        $access_token = $generator->create_access_token_from_client_provider(
            $client_provider->get_entity_copy(),
            time() + HOURSECS
        );
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer " . $access_token;

        // Override the setting with exactly 1 under limit on a complex query.
        set_config('max_query_complexity', 29, 'totara_api');

        ob_start();
        $this->get_external_instance(
            $this->get_query_sample()
        )->process('graphql_request');
        $response = ob_get_clean();

        $this->assertStringContainsString(
            '"core_user_users":{"total":3,"items":',
            $response
        );

        // Override the setting for error case.
        set_config('max_query_complexity', 28, 'totara_api');

        ob_start();
        $this->get_external_instance(
            $this->get_query_sample()
        )->process('graphql_request');
        $response = ob_get_clean();

        $this->assertStringContainsString(
            'Query complexity exceeded maximum allowed complexity of 28.',
            $response
        );
    }

    /**
     * @return string
     */
    private function get_query_sample(): string {
        return 'query {core_user_users(query:{}) {
                    total
                    items
                    items{
                      idnumber
                      email
                      country
                      lastname
                      card_display {
                        profile_picture_alt
                        profile_picture_url
                      }
                      custom_fields {
                        shortname
                        data
                      }
                    } 
                }}';
    }
}