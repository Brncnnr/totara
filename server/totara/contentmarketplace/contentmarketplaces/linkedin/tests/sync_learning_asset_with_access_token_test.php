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
 * @package contentmarketplace_linkedin
 */

use contentmarketplace_linkedin\constants;
use contentmarketplace_linkedin\sync_action\sync_classifications;
use contentmarketplace_linkedin\sync_action\sync_learning_asset;
use contentmarketplace_linkedin\testing\generator;
use core_phpunit\testcase;
use totara_core\http\clients\simple_mock_client;
use totara_core\http\exception\auth_exception;
use totara_core\http\response;
use totara_core\http\response_code;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_sync_learning_asset_with_access_token_testcase extends testcase {
    /**
     * @return void
     */
    public function test_sync_learning_asset_with_authentication_exception(): void {
        $generator = generator::instance();
        $generator->set_up_configuration();

        $json_content = json_encode([
            "error" => "invalid_client",
            "error_description" => "Client authentication failed"
        ]);
        $unauthorized_response = new response(
            $json_content,
            response_code::UNAUTHORIZED,
            [],
            "application/json"
        );

        $client = new simple_mock_client();
        $client->mock_queue($unauthorized_response);
        $client->mock_queue($unauthorized_response);

        $sync = new sync_learning_asset(true);
        $sync->set_api_client($client);
        $sync->set_asset_types(constants::ASSET_TYPE_COURSE);

        try {
            $sync->invoke();
            self::fail("Expect an authentication exception to be thrown");
        } catch (auth_exception $e) {
            $message = $e->getMessage();
            self::assertStringContainsString($json_content, $message);
        }
    }

    /**
     * @return void
     */
    public function test_sync_classification_with_authentication_exception(): void {
        $generator = generator::instance();
        $generator->set_up_configuration();

        $json_content = json_encode([
            "error" => "invalid_client",
            "error_description" => "Client authentication failed"
        ]);

        $unauthorized_response = new response(
            $json_content,
            response_code::UNAUTHORIZED,
            [],
            "application/json"
        );

        $client = new simple_mock_client();
        $client->mock_queue($unauthorized_response);
        $client->mock_queue($unauthorized_response);

        $sync = new sync_classifications(true);
        $sync->set_api_client($client);
        $sync->set_classification_types(constants::CLASSIFICATION_TYPE_SUBJECT);

        try {
            $sync->invoke();
            self::fail("Expect an authentication exception to be thrown");
        } catch (auth_exception $e) {
            $message = $e->getMessage();
            self::assertStringContainsString($json_content, $message);
        }
    }
}