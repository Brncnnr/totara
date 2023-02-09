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
use contentmarketplace_linkedin\entity\classification;
use contentmarketplace_linkedin\entity\classification_relationship;
use contentmarketplace_linkedin\entity\learning_object;
use contentmarketplace_linkedin\entity\learning_object_classification;
use contentmarketplace_linkedin\testing\cache_trace;
use contentmarketplace_linkedin\testing\generator;
use core\orm\query\builder;
use core_phpunit\testcase;
use totara_contentmarketplace\plugininfo\contentmarketplace;
use totara_contentmarketplace\sync;
use totara_contentmarketplace\token\token;
use totara_core\http\clients\simple_mock_client;
use contentmarketplace_linkedin\dto\locale;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_full_sync_testcase extends testcase {
    /**
     * @return void
     */
    protected function setUp(): void {
        $generator = generator::instance();
        $generator->set_up_configuration();

        $generator->set_token(new token('token_one', time() + DAYSECS));

        // Enable content marketplace linkedin.
        contentmarketplace::plugin('linkedin')->enable();
        $generator->setup_locales_for_locales_provider(new locale("en", "US"));
    }

    /**
     * @return void
     */
    public function test_full_sync_populate_relationship_records(): void {
        $generator = generator::instance();
        $client = new simple_mock_client();

        // Mock for Library
        $client->mock_queue($generator->create_json_response_from_fixture('full_sync_1_classification_response_1'));

        // Mock for subject
        $client->mock_queue($generator->create_json_response_from_fixture('full_sync_1_classification_response_2'));

        // Mock for topic
        $client->mock_queue($generator->create_json_response_from_fixture('full_sync_1_classification_response_3'));

        // Mock for learning asset course
        $client->mock_queue($generator->create_json_response_from_fixture('full_sync_1_learning_asset'));

        // Mock for learning asset video - empty
        $client->mock_queue($generator->create_json_response_from_fixture('empty_response'));

        // Mock for learning asset learning path - empty
        $client->mock_queue($generator->create_json_response_from_fixture('empty_response'));

        // Hike to insert unused record
        $generator->create_classification('urn:li:lyndaCategory:5000');

        // Initial state of the system.
        $db = builder::get_db();
        self::assertTrue($db->record_exists(classification::TABLE, ['urn' => 'urn:li:lyndaCategory:5000']));
        self::assertEquals(1, $db->count_records(classification::TABLE));
        self::assertEquals(0, $db->count_records(classification_relationship::TABLE));
        self::assertEquals(0, $db->count_records(learning_object::TABLE));
        self::assertEquals(0, $db->count_records(learning_object_classification::TABLE));

        $sync = new sync($client, new null_progress_trace());
        $sync->execute(true);

        self::assertFalse($db->record_exists(classification::TABLE, ['urn' => 'urn:li:lyndaCategory:5000']));
        // Created a new 3 different records.
        self::assertEquals(3, $db->count_records(classification::TABLE));

        // 2 relationships of classifications are created.
        self::assertEquals(2, $db->count_records(classification_relationship::TABLE));

        // One learning object
        self::assertEquals(1, $db->count_records(learning_object::TABLE));

        // 3 learning object classification relationships are created.
        self::assertEquals(3, $db->count_records(learning_object_classification::TABLE));

        // Get the records, where type is the key.
        $classifications = $db->get_records(classification::TABLE, [], '', 'type,id');
        self::assertCount(3, $classifications);

        $library = $classifications[constants::CLASSIFICATION_TYPE_LIBRARY];
        $subject = $classifications[constants::CLASSIFICATION_TYPE_SUBJECT];
        $topic = $classifications[constants::CLASSIFICATION_TYPE_TOPIC];

        // Asset the relationships between the classifications.
        $classification_relationship_data = [
            [
                'parent_id' => $library->id,
                'child_id' => $subject->id,
                'result' => true
            ],
            [
                'parent_id' => $subject->id,
                'child_id' => $topic->id,
                'result' => true
            ],
            [
                'parent_id' => $library->id,
                'child_id' => $topic->id,
                'result' => false
            ],
            [
                'parent_id' => $subject->id,
                'child_id' => $library->id,
                'result' => false,
            ],
            [
                'parent_id' => $topic->id,
                'child_id' => $library->id,
                'result' => false
            ],
            [
                'parent_id' => $topic->id,
                'child_id' => $subject->id,
                'result' => false
            ]
        ];

        foreach ($classification_relationship_data as $relationship_datum) {
            self::assertEquals(
                $relationship_datum['result'],
                $db->record_exists(
                    classification_relationship::TABLE,
                    [
                        'parent_id' => $relationship_datum['parent_id'],
                        'child_id' => $relationship_datum['child_id']
                    ]
                )
            );

            unset($relationship_datum);
        }

        // Checks on the relationship between the learning assets and classifications.
        $learning_assets = $db->get_records(learning_object::TABLE);
        self::assertCount(1, $learning_assets);

        $learning_asset = reset($learning_assets);
        foreach ([$library, $topic, $subject] as $classification) {
            self::assertTrue(
                $db->record_exists(
                    learning_object_classification::TABLE,
                    [
                        'learning_object_id' => $learning_asset->id,
                        'classification_id' => $classification->id
                    ]
                )
            );
        }
    }

    /**
     * @return void
     */
    public function test_full_sync_with_non_existing_classifications(): void {
        $generator = generator::instance();
        $client = new simple_mock_client();

        // Mock for library - empty
        $client->mock_queue($generator->create_json_response_from_fixture('empty_classification_response'));

        // Mock for subject - empty
        $client->mock_queue($generator->create_json_response_from_fixture('empty_classification_response'));

        // Mock for topic - empty
        $client->mock_queue($generator->create_json_response_from_fixture('empty_classification_response'));

        // Mock for learning asset course
        $client->mock_queue($generator->create_json_response_from_fixture('full_sync_1_learning_asset'));

        // Mock for learning asset video - empty
        $client->mock_queue($generator->create_json_response_from_fixture('empty_response'));

        // Mock for learning asset learning path - empty
        $client->mock_queue($generator->create_json_response_from_fixture('empty_response'));

        $trace = new cache_trace();
        $db = builder::get_db();

        self::assertEquals(0, $db->count_records(classification::TABLE));
        self::assertEquals(0, $db->count_records(classification_relationship::TABLE));
        self::assertEquals(0, $db->count_records(learning_object::TABLE));
        self::assertEquals(0, $db->count_records(learning_object_classification::TABLE));

        $sync = new sync($client, $trace);

        // Turn off debugging mode.
        $sync->set_performance_debug(false);
        $sync->execute(true);

        // Created zero classifications
        self::assertEquals(0, $db->count_records(classification::TABLE));

        // 0 relationships of classifications.
        self::assertEquals(0, $db->count_records(classification_relationship::TABLE));

        // One learning object
        self::assertEquals(1, $db->count_records(learning_object::TABLE));

        // 0 learning object classification relationships.
        self::assertEquals(0, $db->count_records(learning_object_classification::TABLE));

        $all_messages = $trace->get_messages();
        self::assertNotEmpty($all_messages);

        $target_locale = new locale('en', 'US');
        $expected_outputs = [];
        $classification_types = [
            constants::CLASSIFICATION_TYPE_LIBRARY,
            constants::CLASSIFICATION_TYPE_SUBJECT,
            constants::CLASSIFICATION_TYPE_TOPIC
        ];

        foreach ($classification_types as $classification_type) {
            $expected_outputs[] = "Sync for classification type {$classification_type}";
            $expected_outputs[] = "Sync for locale {$target_locale->__toString()}";
            $expected_outputs[] = sprintf(
                "There are no records for classification %s with locale %s",
                $classification_type,
                $target_locale->__toString()
            );
        }

        // Expected outputs for learning asset.
        $expected_outputs[] = sprintf('Sync for type: %s', constants::ASSET_TYPE_COURSE);

        // URN from the learning asset response under classifications
        $expected_outputs[] = "Cannot find the classification with urn urn:li:lyndaCategory:458";
        $expected_outputs[] = "Cannot find the classification with urn urn:li:lyndaCategory:496";
        $expected_outputs[] = "Cannot find the classification with urn urn:li:lyndaCategory:456";

        // Syncing progress
        $expected_outputs[] = "Syncing 1/1";

        // Total number from the response file.
        $expected_outputs[] = "Finish syncing with the total of records: 1";

        self::assertEquals($expected_outputs, $all_messages);
    }

    /**
     * @return void
     */
    public function test_full_sync_remove_existing_classification_relationship(): void {
        $generator = generator::instance();
        $client = new simple_mock_client();
        \contentmarketplace_linkedin\config::save_completed_initial_sync_classification(true);
        \contentmarketplace_linkedin\config::save_completed_initial_sync_learning_asset(true);

        // Mock for Library
        $client->mock_queue($generator->create_json_response_from_fixture('full_sync_1_classification_response_1'));

        // Mock for subject
        $client->mock_queue($generator->create_json_response_from_fixture('full_sync_1_classification_response_2'));

        // Mock for topic
        $client->mock_queue($generator->create_json_response_from_fixture('full_sync_1_classification_response_3'));

        // Mock for learning asset course
        $client->mock_queue($generator->create_json_response_from_fixture('full_sync_1_learning_asset'));

        // Mock for learning asset video - empty
        $client->mock_queue($generator->create_json_response_from_fixture('empty_response'));

        // Mock for learning asset learning path - empty
        $client->mock_queue($generator->create_json_response_from_fixture('empty_response'));

        $learning_ob = $generator->create_learning_object('urn:li:lyndaCourse:252');
        // Hike to insert unused record
        $classification = $generator->create_classification('urn:li:lyndaCategory:5000');
        // Add an extra record to intermediate table that should be removed after the sync
        $generator->create_learning_object_classification($learning_ob->id, $classification->id);

        // Initial state of the system.
        $db = builder::get_db();
        self::assertEquals(1, $db->count_records(classification::TABLE));
        self::assertEquals(0, $db->count_records(classification_relationship::TABLE));
        self::assertEquals(1, $db->count_records(learning_object::TABLE));
        self::assertTrue($db->record_exists(
            learning_object_classification::TABLE,
            ['learning_object_id' => $learning_ob->id, 'classification_id' => $classification->id]
        ));

        $sync = new sync($client, new null_progress_trace());
        $sync->execute(false);

        // Left 3 classifications, as unused one is removed.
        self::assertEquals(3, $db->count_records(classification::TABLE));
        self::assertEquals(2, $db->count_records(classification_relationship::TABLE));
        self::assertEquals(1, $db->count_records(learning_object::TABLE));
        self::assertEquals(3, $db->count_records(learning_object_classification::TABLE));
        self::assertFalse($db->record_exists(
            learning_object_classification::TABLE,
            ['learning_object_id' => $learning_ob->id, 'classification_id' => $classification->id]
        ));

        // Get the records, where type is the key.
        $classifications = $db->get_records(classification::TABLE, [], '', 'type,id');
        self::assertCount(3, $classifications);

        $library = $classifications[constants::CLASSIFICATION_TYPE_LIBRARY];
        $subject = $classifications[constants::CLASSIFICATION_TYPE_SUBJECT];
        $topic = $classifications[constants::CLASSIFICATION_TYPE_TOPIC];

        // Checks on the relationship between the learning assets and classifications.
        $learning_assets = $db->get_records(learning_object::TABLE);
        self::assertCount(1, $learning_assets);

        $learning_asset = reset($learning_assets);
        foreach ([$library, $topic, $subject] as $classification) {
            self::assertTrue(
                $db->record_exists(
                    learning_object_classification::TABLE,
                    [
                        'learning_object_id' => $learning_asset->id,
                        'classification_id' => $classification->id
                    ]
                )
            );
        }
    }
}