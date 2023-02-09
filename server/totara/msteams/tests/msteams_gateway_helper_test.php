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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_msteams
 */

use core_phpunit\testcase;
use totara_core\http\clients\simple_mock_client;
use totara_core\http\response;
use totara_core\http\response_code;
use totara_msteams\api;
use totara_msteams\msteams_gateway_helper;

class totara_msteams_msteams_gateway_helper_testcase extends testcase {
    /**
     * @return void
     */
    protected function setUp(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/totara/msteams/tests/fixtures/mock_http_request.php");
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        mock_http_request::clear();
    }

    /**
     * @return void
     */
    public function test_remote_procedure_call_success(): void {
        $domain = 'www.example.com';

        mock_http_request::add_mock_domain($domain);

        $result = msteams_gateway_helper::remote_procedure_call_success($domain);
        self::assertTrue($result);
    }

    /**
     * @return void
     */
    public function test_get_tenant_id(): void {
        $expected_id = mock_http_request::get_tenant_id();
        $response = mock_http_request::get_mock_response();

        $id = msteams_gateway_helper::get_tenant_id($response);

        self::assertEquals($expected_id, $id);
    }

    /**
     * @return void
     */
    public function test_send_gateway_request_with_signature(): void {
        global $CFG;
        $CFG->msteams_gateway_private_key = file_get_contents(
            sprintf("%s/totara/msteams/tests/fixtures/test_pr.pem", $CFG->dirroot)
        );

        $CFG->msteams_gateway_url = "https://example.com/site-registration";

        $client = new simple_mock_client();
        $client->mock_queue(
            new response(
                json_encode(["success" => true]),
                response_code::OK,
                [],
                "application/json"
            )
        );

        $api = new api($client);
        $tenant_id = sprintf("%s-wow-keyId", uniqid());

        $result = msteams_gateway_helper::call_gateway($api, $tenant_id);
        self::assertTrue($result);

        $requests = $client->get_requests();
        self::assertNotEmpty($requests);
        self::assertCount(1, $requests);

        $request = reset($requests);
        $post_data = $request->get_post_data();

        $query_parameters = [];
        parse_str($post_data, $query_parameters);

        self::assertIsArray($query_parameters);
        self::assertNotEmpty($query_parameters);

        self::assertArrayHasKey("TenantId", $query_parameters);
        self::assertEquals($tenant_id, $query_parameters["TenantId"]);

        self::assertArrayHasKey("SiteUrl",  $query_parameters);
        self::assertEquals(
            $CFG->wwwroot,
            $query_parameters["SiteUrl"]
        );

        // Verify that the public key can help to verify the signature.
        self::assertArrayHasKey("Signature", $query_parameters);
        $signature = $query_parameters["Signature"];

        $public_key_file = "{$CFG->dirroot}/totara/msteams/tests/fixtures/test_pu.pem";
        self::assertTrue(file_exists($public_key_file));

        // Memory leakable code within lower than PHP 8.0
        $public_key = openssl_pkey_get_public("file://{$public_key_file}");
        self::assertIsNotBool($public_key);

        $verification =  openssl_verify(
            json_encode([
                "TenantId" => $tenant_id,
                "SiteUrl" => $CFG->wwwroot
            ]),
            base64_decode($signature),
            $public_key,
            OPENSSL_ALGO_SHA512
        );

        self::assertEquals(1, $verification);
    }

    /**
     * @return void
     */
    public function test_send_request_without_private_key(): void {
        global $CFG;
        $CFG->msteams_gateway_url = "http://example.com";

        $client = new simple_mock_client();
        $api = new api($client);
        $tenant_id = "tenant_id";

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The gateway private key is not set");

        msteams_gateway_helper::call_gateway($api, $tenant_id);
    }

    /**
     * @return void
     */
    public function test_send_request_without_openssl_extension(): void {
        global $CFG;

        if (extension_loaded("openssl")) {
            $this->markTestSkipped("PHP OpenSSL extension is enabled, hence cannot perform the test");
        }

        $CFG->msteams_gateway_url = "https://example.com";
        $CFG->msteams_gateway_private_key = "private_key";

        $client = new simple_mock_client();
        $api = new api($client);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("PHP OpenSSL extension is not available");

        msteams_gateway_helper::call_gateway($api, "tenant_id");
    }
}