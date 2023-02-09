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

use contentmarketplace_linkedin\api\v2\api;
use contentmarketplace_linkedin\api\v2\service\learning_asset\query\criteria;
use contentmarketplace_linkedin\api\v2\service\learning_asset\service;
use contentmarketplace_linkedin\config;
use contentmarketplace_linkedin\oauth\oauth_2;
use core_phpunit\testcase;
use contentmarketplace_linkedin\testing\generator;
use totara_contentmarketplace\exception\invalid_token;
use totara_contentmarketplace\oauth\oauth_2_client;
use totara_contentmarketplace\token\token;
use totara_core\http\clients\simple_mock_client;
use totara_core\http\exception\auth_exception;
use totara_core\http\response;
use totara_core\http\response_code;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_fetch_learning_asset_with_error_testcase extends testcase {
    /**
     * @return void
     */
    public function test_fetch_learning_assets_with_auth_exception_and_stop_on_retry(): void {
        $generator = generator::instance();
        $generator->set_up_configuration();

        $time_now = time();
        $client = new simple_mock_client();

        // Add first mock response for the token
        $client->mock_queue(
            new response(
                json_encode([
                    'access_token' => 'tokenone',
                    'expires_in' => DAYSECS
                ]),
                response_code::OK,
                ['date' => date('Y-m-d H:i:s', $time_now)],
                'application/json'
            )
        );

        $fail_response = json_encode([
            'message' => 'Empty oauth_2_access_token',
            'serviceErrorCode' => 401,
            'status' => 401
        ]);
        $client->mock_queue(
            new response(
                $fail_response,
                response_code::UNAUTHORIZED,
                [],
                'application/json'
            )
        );

        $api = api::create($client);
        $api->set_retry_after_auth(false);

        $this->expectException(auth_exception::class);
        $this->expectExceptionMessage(
            "authexception: Request failed with 401, Authentication failed ({$fail_response})"
        );

        $service = new service(new criteria());
        $api->execute($service);
    }

    /**
     * @return void
     */
    public function test_fetch_learning_assets_with_auth_exception(): void {
        $generator = generator::instance();
        $generator->set_up_configuration();

        $time_now = time();
        $token = new token('tokenone', ($time_now + DAYSECS));
        $generator->set_token($token);

        $client = new simple_mock_client();
        $fail_response = json_encode([
            'message' => 'Empty oauth_2_access_token',
            'serviceErrorCode' => 401,
            'status' => 401
        ]);

        $client->mock_queue(
            new response(
                $fail_response,
                response_code::UNAUTHORIZED,
                ['date' => date('Y-m-d H:i:s', $time_now + MINSECS)],
                'application/json'
            )
        );

        $client->mock_queue(
            new response(
                $fail_response,
                response_code::UNAUTHORIZED,
                ['date' => date('Y-m-d H:i:s', ($time_now + (MINSECS * 2)))],
                'application/json'
            )
        );

        $api = api::create($client);

        try {
            $service = new service(new criteria());
            $api->execute($service);

            self::fail('Expects the API called to throw auth exception');
        } catch (auth_exception $auth_exception) {
            self::assertStringContainsString(
                "authexception: Request failed with 401, Authentication failed ({$fail_response})",
                $auth_exception->getMessage()
            );
        }

        $exceptions = $api->get_exception_catched_on_retry();
        self::assertNotEmpty($exceptions);
        self::assertCount(1, $exceptions);

        $e = reset($exceptions);
        self::assertInstanceOf(auth_exception::class, $e);
    }

    /**
     * @return void
     */
    public function test_fetch_learning_assets_on_invalid_token(): void {
        $generator = generator::instance();
        $generator->set_up_configuration();

        $time_now = time();

        // Create an expired token, so that our process can fall to invalid token exception.
        $token = new token('tokenone', ($time_now - DAYSECS));
        $generator->set_token($token);

        $client = new simple_mock_client();

        // Mock a token that will expired within an hour.
        $client->mock_queue(
            new response(
                json_encode([
                    'access_token' => 'tokentwo',
                    'expires_in' => DAYSECS
                ]),
                response_code::OK,
                ['date' => date('Y-m-d H:i:s', ($time_now - (DAYSECS + HOURSECS)))],
                'application/json'
            )
        );

        $oauth_time_now = ($time_now - DAYSECS - HOURSECS);
        $oauth = oauth_2:: create_from_config();
        $oauth->set_time_now($oauth_time_now);

        $oauth_2_client = new oauth_2_client($oauth, $client, $oauth_time_now);
        $api = api::create($client, $oauth_2_client);

        self::assertNotEquals('tokentwo', config::access_token());
        self::assertEquals('tokenone', config::access_token());

        try {
            $service = new service(new criteria());
            $api->execute($service);

            self::fail(
                'Expects the API call to throw an exception'
            );
        } catch (invalid_token $invalid_token) {
            self::assertStringContainsString(
                get_string('error:invalid_token', 'totara_contentmarketplace'),
                $invalid_token->getMessage()
            );
        }

        self::assertNotEquals('tokenone', config::access_token());
        self::assertEquals('tokentwo', config::access_token());
        $exceptions = $api->get_exception_catched_on_retry();

        self::assertNotEmpty($exceptions);
        self::assertCount(1, $exceptions);

        $e = reset($exceptions);
        self::assertInstanceOf(invalid_token::class, $e);
    }
}