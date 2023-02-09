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
use core_phpunit\testcase;
use totara_oauth2\controller\grant_token_controller;
use totara_oauth2\grant_type;
use totara_oauth2\io\request;
use totara_oauth2\testing\generator;

/**
 * @group totara_oauth2
 */
class totara_oauth2_grant_token_controller_testcase extends testcase {
    /**
     * @return void
     */
    protected function setUp(): void {
        generator::setup_required_configuration();
    }

    /**
     * @return void
     */
    public function test_grant_token(): void {
        $generator = generator::instance();
        $client = $generator->create_client_provider();

        $request = request::create_from_global(
            [],
            [
                "grant_type" => grant_type::get_client_credentials(),
                "client_id" => $client->client_id,
                "client_secret" => $client->client_secret
            ]
        );

        $controller = new grant_token_controller($request, time());
        $response = $controller->action();

        self::assertIsString($response);
        $parameters = json_decode($response, true);

        self::assertNotEmpty($parameters);
        self::assertIsArray($parameters);

        // JWT value
        self::assertArrayHasKey("access_token", $parameters);
        self::assertArrayHasKey("token_type", $parameters);
        self::assertEquals("Bearer", $parameters["token_type"]);
    }

    /**
     * @return void
     */
    public function test_cannot_grant_token(): void {
        $request = request::create_from_global(
            [],
            [
                "grant_type" => grant_type::get_client_credentials(),
                "client_id" => "client_id",
                "client_secret" => "client_secret"
            ]
        );

        $controller = new  grant_token_controller($request, time());
        $response = $controller->action();

        self::assertIsString($response);
        $parameters = json_decode($response, true);

        self::assertNotEmpty($parameters);
        self::assertIsArray($parameters);

        self::assertArrayHasKey("error", $parameters);
        self::assertArrayHasKey("error_description", $parameters);

        self::assertEquals("invalid_client", $parameters["error"]);
        self::assertEquals("Client authentication failed", $parameters["error_description"]);
    }
}