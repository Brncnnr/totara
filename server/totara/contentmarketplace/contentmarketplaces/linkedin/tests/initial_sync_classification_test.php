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

use contentmarketplace_linkedin\api\v2\service\learning_classification\response\collection;
use contentmarketplace_linkedin\config;
use contentmarketplace_linkedin\constants;
use contentmarketplace_linkedin\entity\classification;
use contentmarketplace_linkedin\entity\classification_relationship;
use contentmarketplace_linkedin\sync_action\sync_classifications;
use contentmarketplace_linkedin\testing\generator;
use core\orm\query\builder;
use core_phpunit\testcase;
use totara_contentmarketplace\token\token;
use totara_core\http\clients\simple_mock_client;
use totara_core\http\response;
use totara_core\http\response_code;
use contentmarketplace_linkedin\dto\locale;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_sync_classification_testcase extends testcase {
    /**
     * @return void
     */
    protected function setUp(): void {
        $generator = generator::instance();
        $generator->set_up_configuration();
        $token = new token('token_one', time() + DAYSECS);
        $generator->set_token($token);
    }

    /**
     * @return void
     */
    public function test_initial_sync_classification(): void {
        $db = builder::get_db();
        self::assertEquals(0, $db->count_records(classification::TABLE));

        $generator = generator::instance();

        $client = new simple_mock_client();
        $client->mock_queue($generator->create_json_response_from_fixture('classification_response_1'));

        // Initial state had not yet been set.
        self::assertFalse(config::completed_initial_sync_classification());

        $action = new sync_classifications(true, new null_progress_trace(), $client);
        $action->set_classification_types(constants::CLASSIFICATION_TYPE_LIBRARY);
        $action->set_locales(new locale("en", "US"));
        $action->invoke();

        // After the sync happened.
        self::assertTrue(config::completed_initial_sync_classification());
        self::assertEquals(3, $db->count_records(classification::TABLE));

        self::assertEquals(0, $db->count_records(classification_relationship::TABLE));
    }

    /**
     * The initial sync of classification does not help to populate the relationship.
     * The relationship should be populated when learning asset is sync. This is here
     * to make sure that when the API is changed, we are aware of it.
     *
     * @return void
     */
    public function test_initial_sync_without_populating_relationship(): void {
        $db = builder::get_db();
        self::assertEquals(0, $db->count_records(classification::TABLE));
        self::assertEquals(0, $db->count_records(classification_relationship::TABLE));

        $generator = generator::instance();

        $client = new simple_mock_client();
        $client->mock_queue($generator->create_json_response_from_fixture('classification_response_1'));

        $action = new sync_classifications(true, new null_progress_trace(), $client);
        $action->set_classification_types(constants::CLASSIFICATION_TYPE_LIBRARY);
        $action->set_locales(new locale("en", "US"));
        $action->invoke();

        self::assertEquals(3, $db->count_records(classification::TABLE));
        self::assertEquals(0, $db->count_records(classification_relationship::TABLE));
    }

    /**
     * @return void
     */
    public function test_initial_sync_classification_with_flag_completed(): void {
        $db = builder::get_db();
        self::assertEquals(0, $db->count_records(classification::TABLE));

        config::save_completed_initial_sync_classification(true);
        $generator = generator::instance();

        $client = new simple_mock_client();
        $client->mock_queue($generator->create_json_response_from_fixture('classification_response_1'));

        $action = new sync_classifications(true, new null_progress_trace(), $client);
        $action->invoke();

        self::assertEquals(0, $db->count_records(classification::TABLE));
    }

    /**
     * @return void
     */
    public function test_initial_sync_classification_with_duplicated_record(): void {
        $generator = generator::instance();
        $generator->create_classification("urn:li:lyndaCategory:496");

        $client = new simple_mock_client();
        $client->mock_queue(
            new response(
                json_encode([
                    'paging' => [
                        'start' => 0,
                        'count' => 100,
                        'links' => [],
                        'total' => 1
                    ],
                    'elements' => [
                        [
                            'urn' => 'urn:li:lyndaCategory:496',
                            'owner' => [
                                'urn' => 'urn:li:company:458',
                                'name' => [
                                    'locale' => [
                                        'country' => 'US',
                                        'language' => 'en'
                                    ],
                                    'value' => 'LinkedIn'
                                ],
                            ],
                            'name' => [
                                'locale' => [
                                    'country' => 'US',
                                    'language' => 'en'
                                ],
                                'value' => 'Audio Post-Production'
                            ],
                            'type' => constants::CLASSIFICATION_TYPE_TOPIC
                        ]
                    ],
                ]),
                response_code::OK,
                [],
                'application/json'
            )
        );

        $action = new sync_classifications(true, new null_progress_trace(), $client);
        $action->set_classification_types(constants::CLASSIFICATION_TYPE_TOPIC);

        // There is one record within the database already prior to the sync.
        $db = builder::get_db();
        self::assertEquals(1, $db->count_records(classification::TABLE));

        try {
            $action->invoke();
            self::fail("Expecting the invoke of sync action should yield error");
        } catch (dml_write_exception $e) {
            $message = $e->getMessage();

            // Different db vendor will yield different errors, this is the best we can assert for now.
            self::assertStringContainsString("error writing to database", strtolower($message));
            self::assertStringContainsString("duplicate", strtolower($message));
        }

        self::assertEquals(1, $db->count_records(classification::TABLE));
    }

    /**
     * @return void
     */
    public function test_initial_sync_classification_with_pagination(): void {
        $generator = generator::instance();
        $client = new simple_mock_client();

        $json_response_1 = $generator->get_json_content_from_fixtures('classification_current');
        $json_response_2 = $generator->get_json_content_from_fixtures('classification_next');

        $collection_1 = collection::create(json_decode($json_response_1));
        $collection_2 = collection::create(json_decode($json_response_2));

        $elements_1 = $collection_1->get_elements();
        self::assertCount(1, $elements_1);

        $elements_2 = $collection_2->get_elements();
        self::assertCount(1, $elements_2);

        // First response
        $client->mock_queue($generator->create_json_response($json_response_1));

        // Second response.
        $client->mock_queue($generator->create_json_response($json_response_2));
        $db = builder::get_db();

        self::assertEquals(0, $db->count_records(classification::TABLE));
        $action = new sync_classifications(true, new null_progress_trace(), $client);
        $action->set_classification_types(constants::CLASSIFICATION_TYPE_TOPIC);
        $action->set_locales(new locale("en", "US"));
        $action->invoke();

        self::assertEquals(2, $db->count_records(classification::TABLE));

        $first_element = reset($elements_1);
        self::assertTrue(
            $db->record_exists(classification::TABLE, ['urn' => $first_element->get_urn()])
        );

        $second_element = reset($elements_2);
        self::assertTrue(
            $db->record_exists(classification::TABLE, ['urn' => $second_element->get_urn()])
        );
    }

}