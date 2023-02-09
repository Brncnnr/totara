<?php
/**
 * This file is part of Totara Core
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_oauth2
 */

use core\orm\query\builder;
use core_phpunit\testcase;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use totara_oauth2\entity\access_token;
use totara_oauth2\facade\response_interface;
use totara_oauth2\grant_type;
use totara_oauth2\io\request;
use totara_oauth2\server;
use totara_oauth2\testing\generator;
use totara_api\model\client as model_client;
use totara_oauth2\model\client_provider as model_client_provider;
use totara_oauth2\facade\request_interface;

/**
 * @group totara_oauth2
 */
class totara_oauth2_server_testcase extends testcase {
    /**
     * @return void
     */
    protected function setUp(): void {
        generator::setup_required_configuration();
    }

    /**
     * @param model_client_provider $client_provider
     * @return request_interface
     */
    private function helper_get_test_request(model_client_provider $client_provider) : request_interface {
        // Set up a test auth request object
        $request = request::create_from_global(
            [],
            [
                "grant_type" => grant_type::get_client_credentials(),
                "client_id" => $client_provider->client_id,
                "client_secret" => $client_provider->client_secret
            ],
            [],
            ["REQUEST_METHOD" => "POST"]
        );

        return $request;
    }

    /**
     * @return void
     */
    public function test_request_token(): void {
        $generator = generator::instance();
        $client = $generator->create_client_provider();

        $server = server::create();
        $request = request::create_from_global(
            [],
            [
                "grant_type" => grant_type::get_client_credentials(),
                "client_id" => $client->client_id,
                "client_secret" => $client->client_secret
            ],
            [],
            ["REQUEST_METHOD" => "POST"]
        );

        $db = builder::get_db();
        self::assertEquals(0, $db->count_records(access_token::TABLE, ["client_provider_id" => $client->id]));

        // Once response is being processed, then we will get a new record of access token.
        $response = $server->handle_token_request($request);
        self::assertEquals(1, $db->count_records(access_token::TABLE, ["client_provider_id" => $client->id]));

        self::assertInstanceOf(response_interface::class, $response);
        $body = $response->getBody()->__toString();
        $parameters = json_decode($body, true);

        self::assertIsArray($parameters);

        self::assertArrayHasKey("access_token", $parameters);
        self::assertNotNull($parameters["access_token"]);

        $jwt_configuration = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText('')
        );

        $token = $jwt_configuration->parser()->parse($parameters["access_token"]);
        $token_entity = access_token::repository()->find_by_identifier($token->claims()->get("jti"));

        self::assertNotNull($token_entity);

        self::assertArrayHasKey("token_type", $parameters);
        self::assertEquals("Bearer", $parameters["token_type"]);
    }

    /**
     * @return void
     */
    public function test_verify_token(): void {
        $generator = generator::instance();
        $time_now = time();

        $client = $generator->create_client_provider();
        $token = $generator->create_access_token_from_client_provider($client, $time_now + HOURSECS);

        $request = request::create_from_global(
            [],
            [],
            ["AUTHORIZATION" => "Bearer {$token}"],
        );

        $server = server::create($time_now);
        $result = $server->is_request_verified($request);

        self::assertTrue($result);
    }

    /**
     * @return void
     */
    public function test_cannot_verify_token_due_to_expired(): void {
        $time_now = time();
        $generator = generator::instance();

        $client = $generator->create_client_provider();
        $token = $generator->create_access_token_from_client_provider($client, $time_now - HOURSECS);

        $request = request::create_from_global(
            [],
            [],
            ["AUTHORIZATION" => "Bearer {$token}"]
        );

        $server = server::create($time_now + (HOURSECS * 2));
        $result = $server->is_request_verified($request);

        self::assertFalse($result);
    }

    /**
     * @return void
     */
    public function test_token_expired_set_using_client_settings() : void {
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        // Set up a test API client, client_settings & client provider
        $name = 'test';
        $description = 'description_test';
        // this automatically creates client_settings & client_provider records too
        $client_model = model_client::create($name, $user->id, $description, null, true, ['create_client_provider' => true]);

        $args = ['client_rate_limit' => 1000,
            'default_token_expiry_time' => 7500
        ];
        $client_model->client_settings->update($args);

        $client_provider = $client_model->oauth2_client_providers->first();

        $request = $this->helper_get_test_request($client_provider);

        // Operate
        $server = server::create();
        $response = $server->handle_token_request($request);
        $response_data = json_decode($response->getBody(), 1);

        $this->assertEqualsWithDelta(7500, $response_data['expires_in'], 1);
    }

    /**
     * @return void
     */
    public function test_token_expired_set_using_global_settings() : void {
        // Set up a test API client & client provider. But act like there's NO client settings.default_token_expiry_time
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();

        $name = 'test';
        $description = 'description_test';
        // this automatically creates client_settings & client_provider records too
        $client_model = model_client::create($name, $user->id, $description, null, true, ['create_client_provider' => true]);

        $client_settings_model = $client_model->client_settings;

        $args = ['client_rate_limit' => $client_settings_model->client_rate_limit,
            'default_token_expiry_time' => 0
        ];
        $client_settings_model->update($args);

        $client_provider = $client_model->oauth2_client_providers->first();

        $request = $this->helper_get_test_request($client_provider);
        set_config('default_token_expiration', 4500, 'totara_api');

        // Operate
        $server = server::create();
        $response = $server->handle_token_request($request);
        $response_data = json_decode($response->getBody(), 1);

        $this->assertEqualsWithDelta(4500, $response_data['expires_in'], 1);
    }

    /**
     * @return void
     */
    public function test_token_expired_set_using_oauth2_library_default() : void {
        // Set up a test API client & client provider. But act like there's NO client settings.default_token_expiry_time
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();

        $name = 'test';
        $description = 'description_test';
        // this automatically creates client_settings & client_provider records too
        $client_model = model_client::create($name, $user->id, $description, null, true, ['create_client_provider' => true]);

        $client_settings_model = $client_model->client_settings;

        $args = ['client_rate_limit' => $client_settings_model->client_rate_limit,
            'default_token_expiry_time' => 0
        ];
        $client_settings_model->update($args);

        $client_provider = $client_model->oauth2_client_providers->first();

        $request = $this->helper_get_test_request($client_provider);
        set_config('default_token_expiration', null, 'totara_api');

        // Operate
        $server = server::create();
        $response = $server->handle_token_request($request);
        $response_data = json_decode($response->getBody(), 1);

        $this->assertEqualsWithDelta(3600, $response_data['expires_in'], 1);
    }

    /**
     * @return void
     */
    public function test_grant_token_when_api_feature_off() : void {
        // Disabled api feature.
        \totara_core\advanced_feature::disable('api');

        $user = self::getDataGenerator()->create_user();
        $client_model = model_client::create('client', $user->id, null, null, true, ['create_client_provider' => true]);

        $client_provider = $client_model->oauth2_client_providers->first();

        $request = $this->helper_get_test_request($client_provider);

        // Operate
        $server = server::create();
        $response = $server->handle_token_request($request);
        $response_data = json_decode($response->getBody(), 1);

        self::assertNotEmpty($response_data);
        self::assertEquals("'totara_api' feature is not enabled.", $response_data['hint']);
        self::assertEquals('access_denied', $response_data['error']);
    }
}