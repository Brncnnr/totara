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
use contentmarketplace_linkedin\constants;
use contentmarketplace_linkedin\api\v2\service\learning_asset\query\criteria;
use contentmarketplace_linkedin\api\v2\service\learning_asset\response\collection;
use contentmarketplace_linkedin\api\v2\service\learning_asset\service;
use contentmarketplace_linkedin\config;
use contentmarketplace_linkedin\oauth\oauth_2;
use contentmarketplace_linkedin\testing\generator;
use core_phpunit\testcase;
use totara_contentmarketplace\exception\invalid_token;
use totara_contentmarketplace\oauth\oauth_2_client;
use totara_contentmarketplace\token\token;
use totara_core\http\clients\matching_mock_client;
use totara_core\http\clients\simple_mock_client;
use totara_core\http\exception\auth_exception;
use totara_core\http\method;
use totara_core\http\response;
use totara_core\http\response_code;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_fetch_learning_assets_testcase extends testcase {
    /**
     * @return void
     */
    public function test_fetch_learning_assets_by_criteria_with_valid_response(): void {
        $generator = generator::instance();
        $generator->set_up_configuration();

        $client = new simple_mock_client();

        // Mock token response
        $client->mock_queue(
            new response(
                json_encode([
                    'access_token' => 'token',
                    'expires_in' => HOURSECS,
                ]),
                response_code::OK,
                [],
                'application/json'
            )
        );

        // Mock API response.
        $client->mock_queue(
            new response(
                $generator->get_json_content_from_fixtures('response_1'),
                response_code::OK,
                [],
                'application/json'
            )
        );

        $api = api::create($client);
        $service = new service(new criteria());

        /** @var collection $collection */
        $collection = $api->execute($service);
        $elements = $collection->get_elements();

        self::assertCount(2, $elements);
        [$first_element, $second_element] = $elements;

        // These urn is from the json response fixture
        self::assertEquals('urn:li:lyndaCourse:252', $first_element->get_urn());
        self::assertEquals('urn:li:lyndaCourse:260', $second_element->get_urn());

        self::assertEquals("Excel 2007 Essential Training", $first_element->get_title_value());
        self::assertEquals('Visio 2007 Essential Training', $second_element->get_title_value());

        self::assertEquals('en_US', $first_element->get_title_locale()->__toString());
        self::assertEquals('en_US', $second_element->get_title_locale()->__toString());

        self::assertNotNull($first_element->get_description_value());
        self::assertNotEmpty($first_element->get_description_value());
        self::assertNotNull($second_element->get_description_value());
        self::assertNotEmpty($second_element->get_description_value());

        self::assertEquals('en_US', $first_element->get_title_locale()->__toString());
        self::assertEquals('en_US', $second_element->get_title_locale()->__toString());

        self::assertEquals(constants::DIFFICULTY_LEVEL_BEGINNER, $first_element->get_level());
        self::assertEquals(constants::DIFFICULTY_LEVEL_BEGINNER, $second_element->get_level());

        self::assertNotNull($first_element->get_description_include_html());
        self::assertNotEmpty($first_element->get_description_include_html());
        self::assertNotNull($second_element->get_description_include_html());
        self::assertNotEmpty($second_element->get_description_include_html());

        self::assertEquals('en_US', $first_element->get_description_include_html_locale()->__toString());
        self::assertEquals('en_US', $second_element->get_description_include_html_locale()->__toString());

        self::assertEquals(1170201600000, $first_element->get_published_at()->get_raw());
        self::assertEquals(1613522086073, $second_element->get_last_updated_at()->get_raw());

        self::assertNotNull($first_element->get_short_description_value());
        self::assertNotEmpty($first_element->get_short_description_value());
        self::assertNotNull($second_element->get_short_description_value());
        self::assertNotEmpty($second_element->get_short_description_value());

        self::assertEquals('en_US', $first_element->get_short_description_locale()->__toString());
        self::assertEquals('en_US', $second_element->get_short_description_locale()->__toString());

        self::assertNotNull($first_element->get_primary_image_url());
        self::assertNotEmpty($first_element->get_primary_image_url());
        self::assertNotNull($second_element->get_primary_image_url());
        self::assertNotEmpty($second_element->get_primary_image_url());

        self::assertNotNull($first_element->get_sso_launch_url());
        self::assertNotEmpty($first_element->get_sso_launch_url());
        self::assertNotNull($second_element->get_sso_launch_url());
        self::assertNotEmpty($second_element->get_sso_launch_url());

        self::assertNotNull($first_element->get_web_launch_url());
        self::assertNotEmpty($first_element->get_web_launch_url());
        self::assertNotNull($second_element->get_web_launch_url());
        self::assertNotEmpty($second_element->get_web_launch_url());
    }

    /**
     * @return void
     */
    public function test_fetch_learning_assets_with_unauthorized_exception(): void {
        $generator = generator::instance();
        $generator->set_up_configuration();

        $time_now = time();
        $expired_by_server_token = new token('tokenaaa', $time_now + HOURSECS);
        $generator->set_token($expired_by_server_token);

        $client = new matching_mock_client();
        $criteria = new criteria();
        $criteria->set_asset_types([constants::ASSET_TYPE_COURSE]);

        $learning_asset = new service($criteria);
        $end_point_url = new moodle_url(api::ENDPOINT . '/' . api::get_version());

        // Add the response for retrieve token.
        $client->add_response(
            config::access_token_endpoint(),
            new response(
                json_encode([
                    'access_token' => 'tokenabcde',
                    'expires_in' => DAYSECS,
                ]),
                response_code::OK,
                ['date' => date('Y-m-d H:i:s', $time_now)],
                'application/json'
            ),
            method::POST,
        );

        // Mock first API response with the auth exception.
        $query_url = $learning_asset->apply_to_url($end_point_url);
        $client->add_response(
            $query_url->out(false),
            new response(
                json_encode([
                    'message' => "Expired oauth2_access_token",
                    'serverErrorCode' => 401,
                    'status' => 401,
                ]),
                response_code::UNAUTHORIZED,
                [],
                'application/json'
            )
        );

        $client->add_response(
            $query_url->out(false),
            new response(
                $generator->get_json_content_from_fixtures('response_1'),
                response_code::OK,
                [],
                'application/json'
            )
        );

        $api = api::create($client);

        // Before fetch, check that our token is the same as the one set by configuration.
        self::assertNotEquals('tokenabcde', config::access_token());
        self::assertNotEquals($time_now + DAYSECS, config::access_token_expiry());
        self::assertEquals('tokenaaa', config::access_token());
        self::assertEquals($time_now + HOURSECS, config::access_token_expiry());

        // After the response, the access token should be updated.
        /** @var collection $collection */
        $collection = $api->execute($learning_asset);

        self::assertNotEquals('tokenaaa', config::access_token());
        self::assertNotEquals($time_now + HOURSECS, config::access_token_expiry());
        self::assertEquals('tokenabcde', config::access_token());
        self::assertEquals($time_now + DAYSECS, config::access_token_expiry());

        $elements = $collection->get_elements();
        self::assertCount(2, $elements);

        [$first_element, $last_element] = $elements;
        self::assertEquals('urn:li:lyndaCourse:252', $first_element->get_urn());
        self::assertEquals('urn:li:lyndaCourse:260', $last_element->get_urn());

        // Assertion for the exception captured.
        $exceptions = $api->get_exception_catched_on_retry();
        self::assertNotEmpty($exceptions);
        self::assertCount(1, $exceptions);

        $exception = reset($exceptions);
        self::assertInstanceOf(auth_exception::class, $exception);
    }

    /**
     * @return void
     */
    public function test_fetch_learning_asssets_with_token_expired_exception(): void {
        $generator = generator::instance();
        $generator->set_up_configuration();

        $time_now = time();
        $expired_token = new token('tokenone', ($time_now - DAYSECS));

        $generator->set_token($expired_token);

        $client = new matching_mock_client();
        $criteria = new criteria();
        $criteria->set_asset_types([constants::ASSET_TYPE_COURSE]);

        $learning_asset = new service($criteria);
        $end_point_url = new moodle_url(api::ENDPOINT . '/' . api::get_version());

        // Add the response for retrieve token.
        $client->add_response(
            config::access_token_endpoint(),
            new response(
                json_encode([
                    'access_token' => 'tokentwo',
                    'expires_in' => DAYSECS,
                ]),
                response_code::OK,
                ['date' => date('Y-m-d H:i:s', $time_now)],
                'application/json'
            ),
            method::POST,
        );

        // Mock first API response with the auth exception.
        $query_url = $learning_asset->apply_to_url($end_point_url);
        $client->add_response(
            $query_url->out(false),
            new response(
                $generator->get_json_content_from_fixtures('response_1'),
                response_code::OK,
                [],
                'application/json'
            )
        );

        // We need to mock the time now for oauth_2_client, so that it can evaluate the the
        // current token as valid still. Then by the time it reaches to the API execution, we can
        // yield the invalid token exception.
        $oauth_2_client = new oauth_2_client(
            oauth_2::create_from_config(),
            $client,
            ($time_now - DAYSECS - 1),
        );

        $api = api::create($client, $oauth_2_client);

        // Before fetch, check that our token is the same as the one set by configuration.
        self::assertNotEquals('tokentwo', config::access_token());
        self::assertNotEquals($time_now + DAYSECS, config::access_token_expiry());
        self::assertEquals('tokenone', config::access_token());
        self::assertEquals($time_now - DAYSECS, config::access_token_expiry());

        // After the response, the access token should be updated.
        /** @var collection $collection */
        $collection = $api->execute($learning_asset);

        self::assertNotEquals('tokenone', config::access_token());
        self::assertNotEquals($time_now - DAYSECS, config::access_token_expiry());
        self::assertEquals('tokentwo', config::access_token());
        self::assertEquals($time_now + DAYSECS, config::access_token_expiry());

        $elements = $collection->get_elements();
        self::assertCount(2, $elements);

        [$first_element, $last_element] = $elements;
        self::assertEquals('urn:li:lyndaCourse:252', $first_element->get_urn());
        self::assertEquals('urn:li:lyndaCourse:260', $last_element->get_urn());

        // Assertion for the exception captured.
        $exceptions = $api->get_exception_catched_on_retry();
        self::assertNotEmpty($exceptions);
        self::assertCount(1, $exceptions);

        $exception = reset($exceptions);
        self::assertInstanceOf(invalid_token::class, $exception);
    }
}