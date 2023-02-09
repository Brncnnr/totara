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
 * @author  Simon Coggins <simon.coggins@totaralearning.com>
 * @package totara_xapi
 */

use contentmarketplace_linkedin\exception\json_validation_exception;
use core\orm\query\builder;
use core\orm\query\exceptions\record_not_found_exception;
use core_phpunit\testcase;
use totara_xapi\entity\xapi_statement as xapi_statement_entity;
use totara_xapi\handler\statement_handler;
use totara_xapi\model\xapi_statement;
use totara_xapi\request\request;
use totara_oauth2\testing\generator as oauth2_generator;

/**
 * @group totara_xapi
 */
class totara_xapi_xapi_statement_handler_testcase extends testcase {
    /**
     * @var int|null
     */
    private $time_now;

    /**
     * @return void
     */
    protected function setUp(): void {
        oauth2_generator::setup_required_configuration();
        $this->time_now = time();
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        $this->time_now = null;
    }

    /**
     * @return void
     */
    public function test_create_statement_handler(): void  {

        $request = request::create_from_global();

        $statement_handler = new statement_handler($request, $this->time_now);

        self::assertInstanceOf(statement_handler::class, $statement_handler);
    }

    /**
     * @return void
     */
    public function test_authenticate_statement(): void {
        $generator = oauth2_generator::instance();
        $access_token = $generator->create_access_token(
            "some_client",
            ["expires" => $this->time_now + HOURSECS]
        );

        $client_id = \core\uuid::generate();
        $request = request::create_from_global(
            [],
            [],
            ["Authorization" => "Bearer {$access_token}"],
            ["REQUEST_METHOD" => "POST"]
        );
        $statement_handler = new statement_handler($request, $this->time_now);

        // When request can be authenticated, should return null.
        $result = $statement_handler->authenticate();
        $this->assertFalse(array_key_exists('error', $result->get_data()));
        $this->assertEquals("some_client", $result->get_data()['oauth_client_id']);

        $invalid_token_request = request::create_from_global(
            [],
            [],
            ["Authorization" => "Bearer INVALID"],
            ["REQUEST_METHOD" => "POST"]
        );
        $statement_handler = new statement_handler($invalid_token_request, $this->time_now);

        // Error returned when token is invalid.
        $response = $statement_handler->authenticate();
        $this->assertInstanceOf(totara_xapi\response\json_result::class, $response);
        $response_data = $response->get_data();
        $this->assertEquals('access_denied', $response_data['error']);
        $this->assertEquals('Access denied', $response_data['error_description']);

        $expired_access_token = $generator->create_access_token(
            "some_client",
            ["expires" => $this->time_now - HOURSECS]
        );
        $expired_request = request::create_from_global(
            [],
            [],
            ["Authorization" => "Bearer {$expired_access_token}"],
            ["REQUEST_METHOD" => "POST"]
        );
        $statement_handler = new statement_handler($expired_request, $this->time_now);

        // Error returned when token is expired.
        $response = $statement_handler->authenticate();
        $this->assertInstanceOf(totara_xapi\response\json_result::class, $response);
        $response_data = $response->get_data();
        $this->assertEquals('access_denied', $response_data['error']);
        $this->assertEquals('Access denied', $response_data['error_description']);

        $missing_token_request = request::create_from_global(
            [],
            [],
            [],
            ["REQUEST_METHOD" => "POST"]
        );
        $statement_handler = new statement_handler($missing_token_request, $this->time_now);

        // Error when no token provided.
        $response = $statement_handler->authenticate();
        $this->assertInstanceOf(totara_xapi\response\json_result::class, $response);
        $response_data = $response->get_data();
        $this->assertEquals('access_denied', $response_data['error']);
        $this->assertEquals('Access denied', $response_data['error_description']);
    }

    /**
     * @return void
     */
    public function test_validate_statement_on_valid_statement(): void {
        $request = request::create_from_global();
        $statement_handler = new statement_handler($request, $this->time_now);
        $valid_statement = json_encode($this->get_mock_response_data_by_email('bob@example.com'));
        // Just testing there is no exception thrown.
        $statement_handler->validate_structure($valid_statement);
    }

    /**
     * @return void
     */
    public function test_validate_statement_on_empty_statement(): void {
        $request = request::create_from_global();
        $statement_handler = new statement_handler($request, $this->time_now);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Cannot decode the json data due to: Syntax error');
        $statement_handler->validate_structure("");
    }

    /**
     * @return void
     */
    public function test_validate_statement_on_invalid_statement(): void {
        $request = request::create_from_global();
        $statement_handler = new statement_handler($request, $this->time_now);

        $invalid_statement = json_encode([
            "xactor" => [
                "mbox" => "mailto:bob@example.com",
                "objectType" => "Agent"
            ]
        ]);

        $this->expectException(json_validation_exception::class);
        $this->expectExceptionMessage('Failed to validate the json data: Missing field \'actor\'.');
        $statement_handler->validate_structure($invalid_statement);
    }

    /**
     * @return void
     */
    public function test_extract_user_id_from_valid_statement(): void {
        $email = 'my.EMAIL@example.COM';
        $user = self::getDataGenerator()->create_user(['email' => $email, 'deleted' => 0]);
        $statement = json_encode($this->get_mock_response_data_by_email($email));
        $request = request::create_from_global();
        $statement_handler = new statement_handler($request, $this->time_now);

        // Can extract correct ID from valid statement.
        $user_id = $statement_handler->get_user_id($statement);
        $this->assertEquals($user->id, $user_id);

        // Can extract with uppercase email.
        $statement = json_encode($this->get_mock_response_data_by_email(strtoupper($email)));
        $user_id = $statement_handler->get_user_id($statement);
        $this->assertEquals($user->id, $user_id);
    }

    /**
     * @return void
     */
    public function test_extract_user_id_from_empty_statement(): void {
        $request = request::create_from_global();
        $statement_handler = new statement_handler($request, $this->time_now);

        // Exception returned for empty statement.
        $this->expectException(JsonException::class);
        $this->expectExceptionMessage('Syntax error');
        $statement_handler->get_user_id("");
    }

    /**
     * @return void
     */
    public function test_extract_user_id_from_statement_without_user(): void {
        $request = request::create_from_global();
        $statement_handler = new statement_handler($request, $this->time_now);
        $statement = json_encode([
            "result" => [
                "completion" => true,
                "duration" => "PT4M30S",
                "extensions" => [
                    "https://w3id.org/xapi/cmi5/result/extensions/progress" => "100"
                ]
            ],
            "verb" => [
                "display" => [
                    "en-US" => "COMPLETED",
                ],
                "id" => "http://adlnet.gov/expapi/verbs/completed"
            ],
            "id" => "212tvkodls-csacx-487f-9jiv34-1i93ikkvnid",
            "object" => [
                "definition" => [
                    "type" => "http://adlnet.gov/expapi/activities/course"
                ],
                "id" => "urn:lyndaCourse:252",
                "objectType" => "Activity"
            ],
            "timestamp" => date(DATE_ISO8601, $this->time_now)
        ]);

        // Exception returned for statement where actor is not provided.
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Invalid xAPI statement, cannot find attribute \'actor\'');
        $statement_handler->get_user_id($statement);
    }

    public function test_extract_user_id_from_statement_without_matching_user(): void {
        $request = request::create_from_global();
        $statement_handler = new statement_handler($request, $this->time_now);
        $statement = json_encode([
            "actor" => [
                "mbox" => "mailto:test@example.com",
                "objectType" => "Agent"
            ],
            "result" => [
                "completion" => true,
                "duration" => "PT4M30S",
                "extensions" => [
                    "https://w3id.org/xapi/cmi5/result/extensions/progress" => "100"
                ]
            ],
            "verb" => [
                "display" => [
                    "en-US" => "COMPLETED",
                ],
                "id" => "http://adlnet.gov/expapi/verbs/completed"
            ],
            "id" => "212tvkodls-csacx-487f-9jiv34-1i93ikkvnid",
            "object" => [
                "definition" => [
                    "type" => "http://adlnet.gov/expapi/activities/course"
                ],
                "id" => "urn:lyndaCourse:252",
                "objectType" => "Activity"
            ],
            "timestamp" => date(DATE_ISO8601, $this->time_now)
        ]);

        // Exception returned for statement where user is not found.
        $this->expectException(record_not_found_exception::class);
        $this->expectExceptionMessage('Can not find data record in database');
        $statement_handler->get_user_id($statement);
    }

    /**
     * @return void
     */
    public function test_create_xapi_statement(): void {

        $user = self::getDataGenerator()->create_user(['email' => 'bob@example.com', 'deleted' => 0]);

        $generator = oauth2_generator::instance();
        $access_token = $generator->create_access_token(
            "some_client",
            ["expires" => $this->time_now + HOURSECS]
        );

        $request = request::create_from_global(
            [],
            [],
            ["Authorization" => "Bearer {$access_token}"],
            ["REQUEST_METHOD" => "POST"]
        );

        $request->set_content(
            json_encode([
                "actor" => [
                    "mbox" => "mailto:bob@example.com",
                    "objectType" => "Agent"
                ],
                "result" => [
                    "completion" => true,
                    "duration" => "PT4M30S",
                    "extensions" => [
                        "https://w3id.org/xapi/cmi5/result/extensions/progress" => "100"
                    ]
                ],
                "verb" => [
                    "display" => [
                        "en-US" => "COMPLETED",
                    ],
                    "id" => "http://adlnet.gov/expapi/verbs/completed"
                ],
                "id" => "212tvkodls-csacx-487f-9jiv34-1i93ikkvnid",
                "object" => [
                    "definition" => [
                        "type" => "http://adlnet.gov/expapi/activities/course"
                    ],
                    "id" => "urn:lyndaCourse:252",
                    "objectType" => "Activity"
                ],
                "timestamp" => date(DATE_ISO8601, $this->time_now)
            ])
        );

        $db = builder::get_db();
        // No xapi_statement entity records yet.
        self::assertFalse(
            $db->record_exists(xapi_statement_entity::TABLE, [])
        );

        $statement_handler = new statement_handler($request, $this->time_now);
        $result_response = $statement_handler->authenticate();
        $data = $result_response->get_data();
        $xapi_statement = $statement_handler->create_model_from_request($data['oauth_client_id']);

        $this->assertInstanceOf(xapi_statement::class, $xapi_statement);
        $this->assertEquals($user->id, $xapi_statement->user_id);
        $this->assertEquals('some_client', $xapi_statement->client_id);
        // xAPI_statement entity record created.
        self::assertTrue(
            $db->record_exists(xapi_statement_entity::TABLE, [])
        );
    }

    /**
     * @param string $email
     * @return array
     */
    private function get_mock_response_data_by_email(string $email): array {
        return [
            "actor" => [
                "mbox" => "mailto:{$email}",
                "objectType" => "Agent"
            ],
            "result" => [
                "completion" => true,
                "duration" => "PT4M30S",
                "extensions" => [
                    "https://w3id.org/xapi/cmi5/result/extensions/progress" => "100"
                ]
            ],
            "verb" => [
                "display" => [
                    "en-US" => "COMPLETED",
                ],
                "id" => "http://adlnet.gov/expapi/verbs/completed"
            ],
            "id" => "212tvkodls-csacx-487f-9jiv34-1i93ikkvnid",
            "object" => [
                "definition" => [
                    "type" => "http://adlnet.gov/expapi/activities/course"
                ],
                "id" => "urn:lyndaCourse:252",
                "objectType" => "Activity"
            ],
            "timestamp" => date(DATE_ISO8601, $this->time_now)
        ];
    }
}