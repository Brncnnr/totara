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
 * @package totara_xapi
 */

use contentmarketplace_linkedin\entity\user_progress;
use core\orm\query\builder;
use core_phpunit\testcase;
use totara_xapi\controller\receiver_controller;
use totara_xapi\entity\xapi_statement;
use totara_xapi\event\xapi_statement_created;
use totara_xapi\request\request;
use totara_oauth2\testing\generator as oauth2_generator;
use contentmarketplace_linkedin\model\user_progress as user_progress_model;
use contentmarketplace_linkedin\testing\generator as linkedin_generator;
use totara_xapi\response\json_result;

/**
 * @group totara_xapi
 */
class totara_xapi_receiver_controller_testcase extends testcase {
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
     * All up test of controller for successful request, authentication and storage of result.
     *
     * @return void
     */
    public function test_handling_valid_xapi_request(): void {
        $oauth2_generator = oauth2_generator::instance();
        $token = $oauth2_generator->create_access_token('12345');

        $generator = self::getDataGenerator();
        $user = $generator->create_user();

        $linkedin_generator = linkedin_generator::instance();
        $learning_object = $linkedin_generator->create_learning_object("urn:lyndaCourse:252");

        $create_xapi_statement = function (int $progress) use ($user, $learning_object): array {
            $text = "PROGRESSED";
            if (user_progress_model::PROGRESS_COMPLETE === $progress) {
                $text = "COMPLETED";
            }
            return [
                "actor" => [
                    "mbox" => "mailto:{$user->email}",
                    "objectType" => "Agent"
                ],
                "result" => [
                    "completion" => (user_progress_model::PROGRESS_COMPLETE === $progress),
                    "duration" => "PT4M30S",
                    "extensions" => [
                        "https://w3id.org/xapi/cmi5/result/extensions/progress" => $progress
                    ]
                ],
                "verb" => [
                    "display" => [
                        "en-US" => $text,
                    ],
                    "id" => sprintf("http://adlnet.gov/expapi/verbs/%s", strtolower($text)),
                ],
                "id" => "212tvkodls-csacx-487f-9jiv34-1i93ikkvnid",
                "object" => [
                    "definition" => [
                        "type" => "http://adlnet.gov/expapi/activities/course"
                    ],
                    "id" => $learning_object->urn,
                    "objectType" => "Activity"
                ],
                "timestamp" => date(DATE_ISO8601, $this->time_now)
            ];
        };


        $request = request::create_from_global(
            [],
            [],
            ["Authorization" => "Bearer {$token}"],
            ["REQUEST_METHOD" => "POST"]
        );

        $request->set_content(json_encode($create_xapi_statement(39)));

        $db = builder::get_db();
        $controller = new receiver_controller($request, $this->time_now);

        self::assertEquals(0, $db->count_records(xapi_statement::TABLE));
        self::assertEquals(0, $db->count_records(user_progress::TABLE));

        $response = $controller->action();

        // Check correct response received
        self::assertInstanceOf(json_result::class, $response);
        $response_data = $response->get_data();
        self::assertTrue($response_data['success']);

        // Check data was added to correct tables.
        self::assertEquals(1, $db->count_records(xapi_statement::TABLE));
        self::assertEquals(1, $db->count_records(user_progress::TABLE));
        $records = $db->get_records(xapi_statement::TABLE);
        $record = reset($records);
        self::assertEquals('12345', $record->client_id);

        $user_progress = user_progress_model::load_for_user_and_learning_object_urn($user->id, $learning_object->urn);
        self::assertNotNull($user_progress);
        self::assertEquals(39, $user_progress->progress);
        self::assertFalse($user_progress->completed);

        // Update again with the controller.
        $request->set_content(json_encode($create_xapi_statement(user_progress_model::PROGRESS_COMPLETE)));
        $controller = new receiver_controller($request, $this->time_now);

        $response = $controller->action();

        // Check correct response received
        self::assertInstanceOf(json_result::class, $response);
        $response_data = $response->get_data();
        self::assertTrue($response_data['success']);

        $user_progress = user_progress_model::load_for_user_and_learning_object_urn($user->id, $learning_object->urn);

        // 2 xapi statements are being recorded
        self::assertEquals(2, $db->count_records(xapi_statement::TABLE));
        self::assertEquals(1, $db->count_records(user_progress::TABLE));

        self::assertEquals(user_progress_model::PROGRESS_COMPLETE, $user_progress->progress);
        self::assertTrue($user_progress->completed);
    }

    /**
     * Test the event is created as expected. This is a separate test as capturing the event in the sink
     * means the record does not get processed by the observer.
     *
     * @return void
     */
    public function test_event_created_during_valid_xapi_request(): void {
        $oauth2_generator = oauth2_generator::instance();
        $token = $oauth2_generator->create_access_token();

        $generator = self::getDataGenerator();
        $user = $generator->create_user();

        $linkedin_generator = linkedin_generator::instance();
        $learning_object = $linkedin_generator->create_learning_object("urn:lyndaCourse:252");

        $create_xapi_statement = function (int $progress) use ($user, $learning_object): array {
            $text = "PROGRESSED";
            if (user_progress_model::PROGRESS_COMPLETE === $progress) {
                $text = "COMPLETED";
            }
            return [
                "actor" => [
                    "mbox" => "mailto:{$user->email}",
                    "objectType" => "Agent"
                ],
                "result" => [
                    "completion" => (user_progress_model::PROGRESS_COMPLETE === $progress),
                    "duration" => "PT4M30S",
                    "extensions" => [
                        "https://w3id.org/xapi/cmi5/result/extensions/progress" => $progress
                    ]
                ],
                "verb" => [
                    "display" => [
                        "en-US" => $text,
                    ],
                    "id" => sprintf("http://adlnet.gov/expapi/verbs/%s", strtolower($text)),
                ],
                "id" => "212tvkodls-csacx-487f-9jiv34-1i93ikkvnid",
                "object" => [
                    "definition" => [
                        "type" => "http://adlnet.gov/expapi/activities/course"
                    ],
                    "id" => $learning_object->urn,
                    "objectType" => "Activity"
                ],
                "timestamp" => date(DATE_ISO8601, $this->time_now)
            ];
        };


        $request = request::create_from_global(
            [],
            [],
            ["Authorization" => "Bearer {$token}"],
            ["REQUEST_METHOD" => "POST"]
        );

        $request->set_content(json_encode($create_xapi_statement(39)));

        $controller = new receiver_controller($request, $this->time_now);

        $sink = $this->redirectEvents();
        self::assertEquals(0, $sink->count());

        $controller->action();

        // Check event was triggered
        self::assertEquals(1, $sink->count());
        $events = $sink->get_events();
        $event = current($events);
        self::assertInstanceOf(xapi_statement_created::class, $event);

        // Update again with the controller.
        $request->set_content(json_encode($create_xapi_statement(user_progress_model::PROGRESS_COMPLETE)));
        $controller = new receiver_controller($request, $this->time_now);

        $controller->action();

        // Check new event triggered.
        self::assertEquals(2, $sink->count());
    }
}