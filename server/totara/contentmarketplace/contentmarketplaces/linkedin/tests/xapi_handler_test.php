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

use contentmarketplace_linkedin\entity\user_progress;
use contentmarketplace_linkedin\model\user_progress as user_progress_model;
use contentmarketplace_linkedin\testing\generator as linkedin_generator;
use contentmarketplace_linkedin\totara_xapi\handler\handler;
use core\orm\query\builder;
use core_phpunit\testcase;
use totara_oauth2\testing\generator as oauth2_generator;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_xapi_handler_testcase extends testcase {
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
    public function test_handling_xapi_request_with_update_user_progress(): void {
        $generator = self::getDataGenerator();
        $user = $generator->create_user();

        $linkedin_generator = linkedin_generator::instance();
        $learning_object = $linkedin_generator->create_learning_object("urn:lyndaCourse:252");

        $db = builder::get_db();

        $statement = json_encode($this->create_xapi_statement(39, $user, $learning_object));
        $handler = new handler($statement, $user->id);

        self::assertTrue($handler->validate_statement());

        self::assertEquals(0, $db->count_records(user_progress::TABLE));

        $handler->process();

        self::assertEquals(1, $db->count_records(user_progress::TABLE));

        $user_progress = user_progress_model::load_for_user_and_learning_object_urn($user->id, $learning_object->urn);
        self::assertNotNull($user_progress);
        self::assertEquals(39, $user_progress->progress);
        self::assertFalse($user_progress->completed);

        // Update again.
        $statement = json_encode($this->create_xapi_statement(user_progress_model::PROGRESS_COMPLETE, $user, $learning_object));
        $handler = new handler($statement, $user->id);
        $handler->process();

        $user_progress = user_progress_model::load_for_user_and_learning_object_urn($user->id, $learning_object->urn);

        // Existing progress record is updated.
        self::assertEquals(1, $db->count_records(user_progress::TABLE));

        self::assertEquals(user_progress_model::PROGRESS_COMPLETE, $user_progress->progress);
        self::assertTrue($user_progress->completed);
    }

    /**
     * @return void
     */
    public function test_handling_xapi_request_with_invalid_progress_percentage(): void {
        $user = self::getDataGenerator()->create_user(['email' => 'bob@example.com']);

        $statement = json_encode([
                "actor" => [
                    "mbox" => "mailto:bob@example.com",
                    "objectType" => "Agent"
                ],
                "result" => [
                    "completion" => false,
                    "extensions" => [
                        "https://w3id.org/xapi/cmi5/result/extensions/progress" => "-69"
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

        $handler = new handler($statement, $user->id);

        self::assertFalse($handler->validate_statement());
    }

    private function create_xapi_statement(int $progress, $user, $learning_object): array {
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
    }
}