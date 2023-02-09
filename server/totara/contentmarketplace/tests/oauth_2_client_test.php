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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_contentmarketplace
 */
use core_phpunit\testcase;
use totara_contentmarketplace\oauth\oauth_2_client;
use totara_contentmarketplace\testing\mock\oauth_2;
use totara_core\http\clients\simple_mock_client;
use totara_contentmarketplace\testing\generator;

/**
 * Test the OAUTH2 API with mock data and classes
 *
 * @group totara_contentmarketplace
 */
class totara_contentmarketplace_oauth_2_client_testcase extends testcase {
    /**
     * @return void
     */
    public function test_request_and_refresh_token(): void {
        $generator = generator::instance();
        $client = new simple_mock_client();
        $client->mock_queue(
            $generator->create_json_response([
                oauth_2::TOKEN_KEY => 'first_token',
                oauth_2::EXPIRY_KEY => time() + HOURSECS
            ])
        );

        $client->mock_queue(
            $generator->create_json_response([
                oauth_2::TOKEN_KEY => 'second_token',
                oauth_2::EXPIRY_KEY => time() + HOURSECS + HOURSECS
            ])
        );

        $oauth_client = new oauth_2_client(
            new oauth_2(),
            $client
        );

        $token = $oauth_client->request_token();
        self::assertFalse($token->is_expired());
        self::assertEquals('first_token', $token->get_value());

        // Request another token should yield the current valid token.
        $second_requested_token = $oauth_client->request_token();
        self::assertEquals($token, $second_requested_token);
        self::assertTrue($second_requested_token->is_expired(time() + HOURSECS + 1));

        // Refresh token.
        $refreshed_token = $oauth_client->refresh_token();
        self::assertNotEquals($refreshed_token, $second_requested_token);
        self::assertFalse($refreshed_token->is_expired(time() + HOURSECS + 1));
        self::assertEquals('second_token', $refreshed_token->get_value());

        // Request another token should yield the current valid token.
        $third_requested_token = $oauth_client->request_token();
        self::assertEquals($refreshed_token, $third_requested_token);
        self::assertFalse($refreshed_token->is_expired(time() + HOURSECS + 1));
        self::assertEquals('second_token', $refreshed_token->get_value());
    }
}