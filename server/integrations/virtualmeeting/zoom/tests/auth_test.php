<?php
/**
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package virtualmeeting_zoom
 */

use core\entity\user as user_entity;
use core\orm\query\exceptions\record_not_found_exception;
use totara_core\entity\virtual_meeting_auth as virtual_meeting_auth_entity;
use totara_core\http\clients\matching_mock_client;
use totara_core\http\exception\auth_exception as http_auth_exception;
use totara_core\http\exception\bad_format_exception;
use totara_core\http\exception\request_exception;
use totara_core\http\formdata;
use totara_core\http\method;
use totara_core\http\request;
use totara_core\http\response;
use totara_core\virtualmeeting\authoriser\oauth2_authoriser;
use totara_core\virtualmeeting\exception\auth_exception;
use totara_core\virtualmeeting\user_auth;
use virtualmeeting_zoom\constants;
use virtualmeeting_zoom\providers\auth;

/**
 * @coversDefaultClass virtualmeeting_zoom\providers\auth
 */
class virtualmeeting_zoom_providers_auth_testcase extends advanced_testcase {
    /** @var user_entity */
    private $user;

    public function setUp(): void {
        parent::setUp();
        $this->user = new user_entity($this->getDataGenerator()->create_user()->id);
    }

    public function tearDown(): void {
        parent::tearDown();
        $this->user = null;
    }

    /**
     * @return user_auth
     */
    private function create_user_auth(): user_auth {
        return user_auth::create('zoom', $this->user, 'k1a0rak0ut0ukat0a', 'r3fr3sh', time() + DAYSECS);
    }

    /**
     * @covers ::get_profile
     */
    public function test_get_profile_no_auth(): void {
        $client = new matching_mock_client();
        $auth = new auth($client);
        try {
            $auth->get_profile($this->user, false);
            $this->fail('record_not_found_exception expected');
        } catch (record_not_found_exception $ex) {
        }
    }

    /**
     * @covers ::get_profile
     */
    public function test_get_profile_invalid_token(): void {
        $this->create_user_auth();
        $client = new matching_mock_client();
        $client->add_response(constants::OAUTH2_TOKEN_ENDPOINT, new response('', 400, []));
        $auth = new auth($client);
        try {
            $auth->get_profile($this->user, true);
            $this->fail('auth_exception expected');
        } catch (auth_exception $ex) {
        }
    }

    /**
     * @covers ::get_profile
     */
    public function test_get_profile_error_response(): void {
        $this->create_user_auth();
        $client = new matching_mock_client();
        $auth = new auth($client);
        $client->add_response(constants::USERINFO_API_ENDPOINT, new response('', 401, []));
        try {
            $auth->get_profile($this->user, false);
            $this->fail('auth_exception expected');
        } catch (http_auth_exception $ex) { // another auth_exception!
        }
        $client->add_response(constants::USERINFO_API_ENDPOINT, new response("I'm a teapot", 418, []));
        try {
            $auth->get_profile($this->user, false);
            $this->fail('request_exception expected');
        } catch (request_exception $ex) {
        }
        $client->add_response(constants::USERINFO_API_ENDPOINT, new response('not json', 200, []));
        try {
            $auth->get_profile($this->user, false);
            $this->fail('bad_format_exception expected');
        } catch (bad_format_exception $ex) {
        }
    }

    /**
     * @covers ::get_profile
     */
    public function test_get_profile_returns(): void {
        $this->create_user_auth();
        $client = new matching_mock_client();
        $client->add_response_callable(
            function (request $request) {
                if ($request->get_method() !== method::GET) {
                    return false;
                }
                if ($request->get_url() !== constants::USERINFO_API_ENDPOINT) {
                    return false;
                }
                $has_authorization = array_filter($request->get_headers(), function ($header) {
                    return preg_match('/^Authorization:\s*Bearer\s*.+$/i', $header);
                });
                if (!$has_authorization) {
                    return false;
                }
                return true;
            },
            new response(json_encode(['email' => 'bob.bobby@example.com', 'first_name' => 'Robert', 'last_name' => 'Bobby']), 200, []));
        $auth = new auth($client);
        $this->assertEquals(['name' => 'Robert Bobby', 'email' => 'bob.bobby@example.com'], $auth->get_profile($this->user, false));
    }

    /**
     * @return array
     */
    public function data_invalid_get_requests(): array {
        return [
            'empty' => [[]],
            'no state' => [['code' => 'kiaora']],
            'no code' => [['state' => sesskey()]],
            'bogus state' => [['state' => sesskey().'!', 'code' => 'kiaora']],
        ];
    }

    /**
     * @param array $get_request
     * @covers ::authorise
     * @dataProvider data_invalid_get_requests
     */
    public function test_authorise_invalid_requests(array $get_request): void {
        $client = new matching_mock_client();
        $auth = new auth($client);
        try {
            $auth->authorise($this->user, 'dontcare', [], '', $get_request, []);
            $this->fail('auth_exception expected');
        } catch (auth_exception $ex) {
            $this->assertStringContainsString('invalid request', $ex->getMessage());
        }
    }

    /**
     * @covers ::authorise
     */
    public function test_authorise_success(): void {
        $client = new matching_mock_client();
        $auth = new auth($client);
        $client->add_response_callable(
            function (request $request) {
                parse_str($request->get_post_data(), $data);
                return $request->get_method() === method::POST
                    && $request->get_url() === constants::OAUTH2_TOKEN_ENDPOINT
                    && $data['code'] === 'kiaora';
            },
            new response(json_encode([
                'access_token' => 'aCC3s$t0kEN',
                'refresh_token' => 'R3fre$hToK3n',
            ]), 200, [])
        );
        $auth->authorise($this->user, 'dontcare', [], '', ['state' => sesskey(), 'code' => 'kiaora'], []);
        $entity = virtual_meeting_auth_entity::repository()->find_by_plugin_and_user('zoom', $this->user->id, true);
        $this->assertEquals('aCC3s$t0kEN', $entity->access_token);
        $this->assertEquals('R3fre$hToK3n', $entity->refresh_token);
    }

    /**
     * @covers ::create_authoriser
     * @covers ::params
     */
    public function test_create_authoriser(): void {
        set_config('client_id', 'klient.eyedee', 'virtualmeeting_zoom');
        set_config('client_secret', 's33krit', 'virtualmeeting_zoom');
        $client = new matching_mock_client();
        $auth = auth::create_authoriser($client);
        $prop = new ReflectionProperty(oauth2_authoriser::class, 'client');
        $prop->setAccessible(true);
        $this->assertSame($client, $prop->getValue($auth));
        $prop = new ReflectionProperty(oauth2_authoriser::class, 'token_endpoint');
        $prop->setAccessible(true);
        $this->assertEquals(constants::OAUTH2_TOKEN_ENDPOINT, $prop->getValue($auth));
        $prop = new ReflectionProperty(oauth2_authoriser::class, 'formdata');
        $prop->setAccessible(true);
        /** @var formdata */
        $formdata = $prop->getValue($auth);
        $this->assertEquals('', $formdata->as_string());
    }
}
