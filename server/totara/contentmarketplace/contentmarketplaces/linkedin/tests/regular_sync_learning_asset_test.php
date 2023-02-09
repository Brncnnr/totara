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

use contentmarketplace_linkedin\constants;
use contentmarketplace_linkedin\config;
use contentmarketplace_linkedin\entity\learning_object as entity;
use contentmarketplace_linkedin\sync_action\sync_learning_asset;
use contentmarketplace_linkedin\testing\generator;
use core\orm\query\builder;
use core_phpunit\testcase;
use totara_contentmarketplace\token\token;
use totara_core\http\clients\simple_mock_client;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_regular_sync_learning_asset_testcase extends testcase {
    /**
     * @return void
     */
    protected function setUp(): void {
        $generator = generator::instance();
        $generator->set_up_configuration();

        $token = new token('tokenone', time() + DAYSECS);
        $generator->set_token($token);
    }

    /**
     * @return void
     */
    public function test_run_regular_sync_which_update_current_record(): void {
        $db = builder::get_db();
        $generator = generator::instance();

        // Setup the records within the database.
        self::assertEquals(0, $db->count_records(entity::TABLE));

        // Insert two records that has the same URN as the response from json.
        $learning_objects = [
            $generator->create_learning_object("urn:li:lyndaCourse:252"),
            $generator->create_learning_object("urn:li:lyndaCourse:260"),
        ];

        self::assertEquals(2, $db->count_records(entity::TABLE));

        // Start run the regularly sync and check if it will update the current records.
        $client = new simple_mock_client();
        $client->mock_queue(
            $generator->create_json_response_from_fixture('response_2')
        );

        $client->mock_queue($generator->create_json_response_from_fixture("empty_response"));

        $time_now = time();
        config::save_completed_initial_sync_learning_asset(true);
        config::save_last_time_sync_learning_asset($time_now - DAYSECS);

        self::assertNotEquals($time_now, config::last_time_sync_learning_asset());
        self::assertEquals($time_now - DAYSECS, config::last_time_sync_learning_asset());

        $sync = new sync_learning_asset(false, $time_now);
        $sync->set_api_client($client);
        $sync->set_asset_types(constants::ASSET_TYPE_COURSE);
        $sync->invoke();

        self::assertNotEquals($time_now - DAYSECS, config::last_time_sync_learning_asset());
        self::assertEquals($time_now, config::last_time_sync_learning_asset());

        $fetched_learning_objects = [
            entity::repository()->find_by_urn('urn:li:lyndaCourse:252'),
            entity::repository()->find_by_urn('urn:li:lyndaCourse:260'),
        ];

        // Compared the two objects, as they should be updated.

        /**
         * @var int    $i
         * @var entity $learning_object
         */
        foreach ($learning_objects as $i => $learning_object) {
            /** @var entity $fetched_object */
            $fetched_object = $fetched_learning_objects[$i];

            self::assertEquals($learning_object->id, $fetched_object->id);
            self::assertEquals($learning_object->urn, $fetched_object->urn);
            self::assertEquals($learning_object->locale_language, $fetched_object->locale_language);
            self::assertEquals($learning_object->locale_country, $fetched_object->locale_country);

            self::assertNotEquals($learning_object->title, $fetched_object->title);
            self::assertNotEquals($learning_object->description, $fetched_object->description);
            self::assertNotEquals($learning_object->short_description, $fetched_object->short_description);
            self::assertNotEquals($learning_object->description_include_html, $fetched_object->description_include_html);
            self::assertNotEquals($learning_object->last_updated_at, $fetched_object->last_updated_at);
            self::assertNotEquals($learning_object->published_at, $fetched_object->published_at);
        }
    }

    /**
     * @return void
     */
    public function test_create_new_learning_objects_on_regular_sync(): void {
        $db = builder::get_db();
        $generator = generator::instance();

        $generator->create_learning_object('urn:li:lyndaCourse:252');

        // One record added to the table, prior to the sync happen.
        self::assertEquals(1, $db->count_records(entity::TABLE));

        $client = new simple_mock_client();
        $client->mock_queue($generator->create_json_response_from_fixture('response_2'));
        $client->mock_queue($generator->create_json_response_from_fixture("empty_response"));

        config::save_completed_initial_sync_learning_asset(true);

        $sync = new sync_learning_asset(false);
        $sync->set_api_client($client);
        $sync->set_asset_types(constants::ASSET_TYPE_COURSE);

        // After invoke, a new learning object record had been added, and the current
        // first learning object should be updated
        $sync->invoke();

        self::assertEquals(2, $db->count_records(entity::TABLE));
        self::assertTrue($db->record_exists(entity::TABLE, ['urn' => 'urn:li:lyndaCourse:252']));

        // From response_2 file fixture.
        self::assertTrue($db->record_exists(entity::TABLE, ['urn' => 'urn:li:lyndaCourse:260']));
    }

    /**
     * @return void
     */
    public function test_run_regularly_sync_without_the_flag(): void {
        $db = builder::get_db();
        $generator = generator::instance();

        self::assertEquals(0, $db->count_records(entity::TABLE));

        $client = new simple_mock_client();
        $client->mock_queue($generator->create_json_response_from_fixture('response_1'));
        $client->mock_queue($generator->create_json_response_from_fixture("empty_response"));

        config::save_completed_initial_sync_learning_asset(false);
        self::assertNull(config::last_time_sync_learning_asset());

        $sync = new sync_learning_asset(false);
        $sync->set_api_client($client);
        $sync->set_asset_types(constants::ASSET_TYPE_COURSE);

        // Nothing is run.
        $sync->invoke();
        self::assertEquals(0, $db->count_records(entity::TABLE));
        self::assertNull(config::last_time_sync_learning_asset());
    }

    /**
     * @return void
     */
    public function test_run_sync_with_extra_fetching(): void {
        $generator = generator::instance();
        config::save_completed_initial_sync_learning_asset(true);

        $client = new simple_mock_client();
        $client->mock_queue(
            $generator->create_json_response(
                json_encode([
                    "paging" => [
                        "start" => 0,
                        "count" => 1,
                        "links" => [],
                        "total" => 1
                    ],
                    "elements" => [
                        [
                            "urn" => "urn:li:lyndaCourse:252",
                            "details" => [
                                "images" => [
                                    "primary" => "https://example.com",
                                ],
                                "descriptionIncludingHtml" => [
                                    "locale" => [
                                        "country" => "US",
                                        "language" => "en"
                                    ],
                                    "value" => "This is description"
                                ],
                                "lastUpdatedAt" => time(),
                                "publishedAt" => time(),
                                "level" => constants::DIFFICULTY_LEVEL_BEGINNER,
                                "description" => [
                                    "locale" => [
                                        "country" => "US",
                                        "language" => "en"
                                    ],
                                    "value" => "This is description"
                                ],
                                "shortDescription" => [
                                    "locale" => [
                                        "country" => "US",
                                        "language" => "en"
                                    ],
                                    "value" => "This is description"
                                ],
                                "availability" => constants::AVAILABILITY_AVAILABLE,
                                "availableLocales" => [
                                    [
                                        "country" => "US",
                                        "language" => "en"
                                    ]
                                ],
                                "relationships" => [],
                                "classifications" => [],
                                "urls" => [
                                    "webLaunch" => "https://example.com",
                                    "ssoLaunch" => "",
                                    "aiccLaunch" => ""
                                ],
                                "shortDescriptionIncludingHtml" => [
                                    "locale" => [
                                        "country" => "US",
                                        "language" => "en"
                                    ],
                                    "value" => "Short description"
                                ],
                                "contributors" => [],
                                "timeToComplete" => [
                                    "duration" => 60,
                                    "unit" => "SECOND"
                                ],
                            ],
                            "title" => [
                                "locale" => [
                                    "country" => "US",
                                    "language" => "en"
                                ],
                                "value" => "This is title"
                            ],
                            "type" => constants::ASSET_TYPE_COURSE,
                            "contents" => []
                        ]
                    ]
                ]),
            )
        );

        // Mock an empty response so that we know that it would stop the process.
        $client->mock_queue(
            $generator->create_json_response(
                json_encode([
                    "paging" => [
                        "start" => 0,
                        "count" => 1,
                        "links" => [],
                        "total" => 1
                    ],
                    "elements" => []
                ]),
            )
        );

        // 2 current mock responses at the moment, before the execution.
        self::assertEquals(2, $client->count_mock_responses());

        $sync = new sync_learning_asset(false);
        $sync->set_api_client($client);
        $sync->invoke();

        // There is 0 mock response, as we are trying to push for further syncing.
        self::assertEquals(0, $client->count_mock_responses());
    }

    /**
     * @return void
     */
    public function test_run_sync_with_extra_fetching_that_ignore_diffrent_type(): void {
        $generator = generator::instance();
        config::save_completed_initial_sync_learning_asset(true);

        $client = new simple_mock_client();
        $client->mock_queue(
            $generator->create_json_response(
                json_encode([
                    "paging" => [
                        "start" => 0,
                        "count" => 1,
                        "links" => [],
                        "total" => 1
                    ],
                    "elements" => [
                        [
                            "urn" => "urn:li:lyndaCourse:252",
                            "details" => [
                                "images" => [
                                    "primary" => "https://example.com",
                                ],
                                "descriptionIncludingHtml" => [
                                    "locale" => [
                                        "country" => "US",
                                        "language" => "en"
                                    ],
                                    "value" => "This is description"
                                ],
                                "lastUpdatedAt" => time(),
                                "publishedAt" => time(),
                                "level" => constants::DIFFICULTY_LEVEL_BEGINNER,
                                "description" => [
                                    "locale" => [
                                        "country" => "US",
                                        "language" => "en"
                                    ],
                                    "value" => "This is description"
                                ],
                                "shortDescription" => [
                                    "locale" => [
                                        "country" => "US",
                                        "language" => "en"
                                    ],
                                    "value" => "This is description"
                                ],
                                "availability" => constants::AVAILABILITY_AVAILABLE,
                                "availableLocales" => [
                                    [
                                        "country" => "US",
                                        "language" => "en"
                                    ]
                                ],
                                "relationships" => [],
                                "classifications" => [],
                                "urls" => [
                                    "webLaunch" => "https://example.com",
                                    "ssoLaunch" => "",
                                    "aiccLaunch" => ""
                                ],
                                "shortDescriptionIncludingHtml" => [
                                    "locale" => [
                                        "country" => "US",
                                        "language" => "en"
                                    ],
                                    "value" => "Short description"
                                ],
                                "contributors" => [],
                                "timeToComplete" => [
                                    "duration" => 60,
                                    "unit" => "SECOND"
                                ],
                            ],
                            "title" => [
                                "locale" => [
                                    "country" => "US",
                                    "language" => "en"
                                ],
                                "value" => "This is title"
                            ],
                            "type" => constants::ASSET_TYPE_COURSE,
                            "contents" => []
                        ]
                    ]
                ]),
            )
        );

        $client->mock_queue(
            $generator->create_json_response(
                json_encode([
                    "paging" => [
                        "start" => 0,
                        "count" => 1,
                        "links" => [],
                        "total" => 1
                    ],
                    "elements" => [
                        [
                            "urn" => "urn:li:lyndaCourse:253",
                            "details" => [
                                "images" => [
                                    "primary" => "https://example.com",
                                ],
                                "descriptionIncludingHtml" => [
                                    "locale" => [
                                        "country" => "US",
                                        "language" => "en"
                                    ],
                                    "value" => "This is description"
                                ],
                                "lastUpdatedAt" => time(),
                                "publishedAt" => time(),
                                "level" => constants::DIFFICULTY_LEVEL_BEGINNER,
                                "description" => [
                                    "locale" => [
                                        "country" => "US",
                                        "language" => "en"
                                    ],
                                    "value" => "This is description"
                                ],
                                "shortDescription" => [
                                    "locale" => [
                                        "country" => "US",
                                        "language" => "en"
                                    ],
                                    "value" => "This is description"
                                ],
                                "availability" => constants::AVAILABILITY_AVAILABLE,
                                "availableLocales" => [
                                    [
                                        "country" => "US",
                                        "language" => "en"
                                    ]
                                ],
                                "relationships" => [],
                                "classifications" => [],
                                "urls" => [
                                    "webLaunch" => "https://example.com",
                                    "ssoLaunch" => "",
                                    "aiccLaunch" => ""
                                ],
                                "shortDescriptionIncludingHtml" => [
                                    "locale" => [
                                        "country" => "US",
                                        "language" => "en"
                                    ],
                                    "value" => "Short description"
                                ],
                                "contributors" => [],
                                "timeToComplete" => [
                                    "duration" => 60,
                                    "unit" => "SECOND"
                                ],
                            ],
                            "title" => [
                                "locale" => [
                                    "country" => "US",
                                    "language" => "en"
                                ],
                                "value" => "This is video"
                            ],
                            "type" => constants::ASSET_TYPE_VIDEO,
                            "contents" => []
                        ]
                    ]
                ]),
            )
        );

        // Mock an empty response so that we know that it would stop the process.
        $client->mock_queue(
            $generator->create_json_response(
                json_encode([
                    "paging" => [
                        "start" => 0,
                        "count" => 1,
                        "links" => [],
                        "total" => 1
                    ],
                    "elements" => []
                ]),
            )
        );

        // 3 current mock responses at the moment, before the execution.
        self::assertEquals(3, $client->count_mock_responses());

        // check the database that no records for video and course existing before the sync.
        $db = builder::get_db();
        self::assertEquals(0, $db->count_records(entity::TABLE));
        self::assertFalse($db->record_exists(entity::TABLE, ["asset_type" => constants::ASSET_TYPE_COURSE]));
        self::assertFalse($db->record_exists(entity::TABLE, ["asset_type" => constants::ASSET_TYPE_VIDEO]));

        $sync = new sync_learning_asset(false);
        $sync->set_api_client($client);
        $sync->set_asset_types(constants::ASSET_TYPE_COURSE);
        $sync->invoke();

        // There is 0 mock response, as we are trying to push for further syncing.
        self::assertEquals(0, $client->count_mock_responses());

        self::assertEquals(1, $db->count_records(entity::TABLE));
        self::assertTrue($db->record_exists(entity::TABLE, ["asset_type" => constants::ASSET_TYPE_COURSE]));
        self::assertFalse($db->record_exists(entity::TABLE, ["asset_type" => constants::ASSET_TYPE_VIDEO]));
    }
}