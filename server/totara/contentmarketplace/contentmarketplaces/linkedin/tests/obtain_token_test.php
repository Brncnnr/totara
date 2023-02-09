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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package contentmarketplace_linkedin
 */
use core_phpunit\testcase;
use totara_core\http\clients\simple_mock_client;
use contentmarketplace_linkedin\oauth\oauth_2;
use totara_contentmarketplace\oauth\oauth_2_client;
use totara_core\http\response;
use totara_core\http\response_code;
use contentmarketplace_linkedin\testing\generator;
use totara_core\http\exception\auth_exception;
use totara_contentmarketplace\exception\cannot_obtain_token;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_obtain_token_testcase extends testcase {
    /**
     * @return void
     */
    public function test_obtain_token_with_invalid_response(): void {
        $generator = generator::instance();
        $generator->set_config_client_id('clientid');
        $generator->set_config_client_secret('clientsecret');

        // This response is mocking from linkedin side.
        $response =  new response(
            json_encode([
                'error' => 'invalid_client_id',
                'error_description' => 'The passed in client id is invalid "clientid"'
            ]),
            response_code::BAD_REQUEST,
            [],
            'application/json'
        );

        $mock_client = new simple_mock_client();
        $mock_client->mock_queue($response);

        $oauth = oauth_2::create_from_config();
        $oauth_client = new oauth_2_client($oauth, $mock_client);

        try {
            $oauth_client->request_token();
            self::fail('Expecting the request token to yield exception');
        } catch (auth_exception $e) {
            self::assertStringContainsString(
                "Request failed with 400, Authentication failed ({$response->get_body()})",
                $e->getMessage()
            );
        }
    }

    /**
     * @return void
     */
    public function test_obtain_token_from_response_with_missing_token(): void {
        $generator = generator::instance();
        $generator->set_up_configuration();

        $response = new response(
            json_encode(['expires_in' => DAYSECS]),
            response_code::OK,
            [],
            'application/json'
        );

        $mock_client = new simple_mock_client();
        $mock_client->mock_queue($response);

        $oauth = oauth_2::create_from_config();
        $oauth_client = new oauth_2_client($oauth, $mock_client);

        try {
            $oauth_client->request_token();
            self::fail('Expecting the request token should yield exception');
        } catch (cannot_obtain_token $e) {
            self::assertStringContainsString(
                get_string('error:cannot_obtain_token_by_missing_field', 'totara_contentmarketplace', 'access_token'),
                $e->getMessage()
            );
        }
    }

    /**
     * @return void
     */
    public function test_obtain_token_from_response_with_missing_expires_time(): void {
        $generator = generator::instance();
        $generator->set_up_configuration();

        $response = new response(
            json_encode(['access_token' => 'sometoken']),
            response_code::OK,
            [],
            'application/json'
        );

        $mock_client = new simple_mock_client();
        $mock_client->mock_queue($response);

        $oauth = oauth_2::create_from_config();
        $oauth_client = new oauth_2_client($oauth, $mock_client);

        try {
            $oauth_client->request_token();
            self::fail('Expecting the request token should yield exception');
        } catch (cannot_obtain_token $e) {
            self::assertStringContainsString(
                get_string('error:cannot_obtain_token_by_missing_field', 'totara_contentmarketplace', 'expires_in'),
                $e->getMessage()
            );
        }
    }

    /**
     * @return void
     */
    public function test_obtain_token_with_invalid_token_value(): void {
        $generator = generator::instance();
        $generator->set_up_configuration();

        $response = new response(
            json_encode([
                'expires_in' => DAYSECS,
                'access_token' => '',
            ]),
            response_code::OK,
            [],
            'application/json'
        );

        $mock_client = new simple_mock_client();
        $mock_client->mock_queue($response);

        $oauth = oauth_2::create_from_config();
        $oauth_client = new oauth_2_client($oauth, $mock_client);

        try {
            $oauth_client->request_token();
            self::fail('Expecting the request token should yield exception');
        } catch (cannot_obtain_token $e) {
            self::assertStringContainsString(
                get_string('error:cannot_obtain_token_by_invalid_value', 'totara_contentmarketplace', 'access_token'),
                $e->getMessage()
            );
        }
    }

    /**
     * @return void
     */
    public function test_obtain_token_with_invalid_expires_value(): void {
        $generator = generator::instance();
        $generator->set_up_configuration();

        $response = new response(
            json_encode([
                'expires_in' => 'abcde',
                'access_token' => 'token_sss',
            ]),
            response_code::OK,
            [],
            'application/json'
        );

        $mock_client = new simple_mock_client();
        $mock_client->mock_queue($response);

        $oauth = oauth_2::create_from_config();
        $oauth_client = new oauth_2_client($oauth, $mock_client);

        try {
            $oauth_client->request_token();
            self::fail('Expecting the request token should yield exception');
        } catch (cannot_obtain_token $e) {
            self::assertStringContainsString(
                get_string('error:cannot_obtain_token_by_invalid_value', 'totara_contentmarketplace', 'expires_in'),
                $e->getMessage()
            );
        }
    }

    /**
     * A test to make sure that the process of oauth is respecting the response date time
     * from header rather than using the current time.
     *
     * @return void
     */
    public function test_obtain_token_with_response_time(): void {
        $time = time();

        $generator = generator::instance();
        $generator->set_up_configuration();

        $response = new response(
            json_encode([
                'expires_in' => DAYSECS,
                'access_token' => 'token',
            ]),
            response_code::OK,
            ['date' => date('d-m-Y H:i:s', ($time + DAYSECS))],
            'application/json'
        );

        $mock_client = new simple_mock_client();
        $mock_client->mock_queue($response);

        $oauth = oauth_2::create_from_config();
        $oauth_client = new oauth_2_client($oauth, $mock_client, $time);
        $token = $oauth_client->request_token();

        self::assertEquals('token', $token->get_value());
        self::assertNotEquals($time + DAYSECS, $token->get_expiry());
        self::assertEquals($time + DAYSECS + DAYSECS, $token->get_expiry());
    }

    /**
     * @return void
     */
    public function test_obtain_token_without_response_time(): void {
        $generator = generator::instance();
        $generator->set_up_configuration();

        $response = new response(
            json_encode([
                'expires_in' => DAYSECS,
                'access_token' => 'token',
            ]),
            response_code::OK,
            [],
            'application/json'
        );

        $mock_client = new simple_mock_client();
        $mock_client->mock_queue($response);

        $oauth = oauth_2::create_from_config();
        $time = time();
        $oauth_client = new oauth_2_client($oauth, $mock_client, $time);
        $token = $oauth_client->request_token();

        self::assertEquals('token', $token->get_value());
        self::assertEquals($time + DAYSECS, $token->get_expiry());
    }
}