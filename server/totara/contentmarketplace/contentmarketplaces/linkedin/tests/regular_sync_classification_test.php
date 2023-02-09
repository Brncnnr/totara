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
use contentmarketplace_linkedin\sync_action\sync_classifications;
use core\orm\query\builder;
use core_phpunit\testcase;
use contentmarketplace_linkedin\testing\generator;
use totara_contentmarketplace\token\token;
use totara_core\http\clients\simple_mock_client;
use contentmarketplace_linkedin\dto\locale;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_regulary_sync_classification_testcase extends testcase {
    /**
     * @return void
     */
    protected function setUp(): void {
        $generator = generator::instance();
        $generator->set_up_configuration();

        $token = new token("token_two", time() + DAYSECS);
        $generator->set_token($token);

        // Set this flag up so that all the regular sync can be executed.
        config::save_completed_initial_sync_classification(true);
    }

    /**
     * @return void
     */
    public function test_regular_sync_update_record(): void {
        $generator = generator::instance();
        $classification = $generator->create_classification('urn:li:lyndaCategory:457');

        $client = new simple_mock_client();
        $client->mock_queue(
            $generator->create_json_response(
                json_encode([
                    'paging' => [
                        'count' => 100,
                        'total' => 1,
                        'links' => [],
                        'start' => 0
                    ],
                    'elements' => [
                        [
                            'name' => [
                                'locale' => [
                                    'country' => 'US',
                                    'language' => 'en'
                                ],
                                'value' => 'Something else'
                            ],
                            'urn' => 'urn:li:lyndaCategory:457',
                            'type' => constants::CLASSIFICATION_TYPE_TOPIC,
                            'owner' => [
                                'urn' => 'urn:li:organization:1337',
                                'name' => [
                                    'locale' => [
                                        'country' => 'US',
                                        'language' => 'en'
                                    ],
                                    'value' => 'LinkedIn'
                                ]
                            ]
                        ]
                    ]
                ])
            )
        );

        $db = builder::get_db();
        self::assertEquals(1, $db->count_records(classification::TABLE));

        $action = new sync_classifications(false, new null_progress_trace(), $client);
        $action->set_classification_types(constants::CLASSIFICATION_TYPE_TOPIC);
        $action->set_locales(new locale("en", "US"));
        $action->invoke();

        // Nothing is added, but one classification is updated.
        self::assertEquals(1, $db->count_records(classification::TABLE));
        $updated_classification = classification::repository()->find_by_urn('urn:li:lyndaCategory:457');

        self::assertNotNull($updated_classification);
        self::assertEquals($classification->urn, $updated_classification->urn);
        self::assertNotEquals($classification->name, $updated_classification->name);

        // Language is not changed.
        self::assertEquals($classification->locale_language, $updated_classification->locale_language);
        self::assertEquals($classification->locale_country, $updated_classification->locale_country);
    }

    /**
     * @return void
     */
    public function test_regular_sync_add_new_record(): void {
        $generator = generator::instance();
        $json_response = $generator->get_json_content_from_fixtures('classification_response_3');

        $collection = collection::create(json_decode($json_response));
        $elements = $collection->get_elements();

        self::assertCount(1, $elements);

        $client = new simple_mock_client();
        $client->mock_queue($generator->create_json_response($json_response));

        $db = builder::get_db();

        $first_element = reset($elements);
        self::assertFalse($db->record_exists(classification::TABLE, ['urn' => $first_element->get_urn()]));
        // There should be zero records when we run the sync.
        self::assertEquals(0, $db->count_records(classification::TABLE));

        $action = new sync_classifications(false, new null_progress_trace(), $client);
        $action->set_classification_types(constants::CLASSIFICATION_TYPE_TOPIC);
        $action->set_locales(new locale("en", "US"));
        $action->invoke();

        self::assertEquals(1, $db->count_records(classification::TABLE));
        self::assertTrue($db->record_exists(classification::TABLE, ['urn' => $first_element->get_urn()]));
    }

    /**
     * @return void
     */
    public function test_regular_sync_without_completed_flag(): void {
        config::save_completed_initial_sync_classification(false);
        $generator = generator::instance();

        $client = new simple_mock_client();
        $client->mock_queue($generator->create_json_response_from_fixture('classification_response_1'));

        $db = builder::get_db();
        self::assertEquals(0, $db->count_records(classification::TABLE));

        $action = new sync_classifications(false, new null_progress_trace(), $client);
        $action->invoke();

        // Nothing should be added to the database, as the initial sync had not yet run.
        self::assertEquals(0, $db->count_records(classification::TABLE));
    }
}