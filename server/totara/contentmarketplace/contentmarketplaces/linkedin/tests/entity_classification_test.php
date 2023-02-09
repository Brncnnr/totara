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
use core_phpunit\testcase;
use core\orm\query\builder;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_entity_classification_testcase extends testcase {
    /**
     * @return void
     */
    public function test_insert_classification(): void {
        $db = builder::get_db();
        self::assertEquals(0, $db->count_records(classification::TABLE));

        $classification = new classification();
        $classification->urn = 'urn:li:organization:1337';
        $classification->name = 'First name';
        $classification->locale_language = 'en';
        $classification->type = constants::CLASSIFICATION_TYPE_SUBJECT;

        $classification->save();

        self::assertEquals(1, $db->count_records(classification::TABLE));
        self::assertTrue($db->record_exists(classification::TABLE, ['id' => $classification->id]));
    }

    /**
     * @return void
     */
    public function test_insert_duplicate_classifications(): void {
        $db = builder::get_db();
        self::assertEquals(0, $db->count_records(classification::TABLE));

        $classification = new classification();
        $classification->urn = 'urn:li:organization:1338';
        $classification->name = 'Second name';
        $classification->locale_language = 'en';
        $classification->type = constants::CLASSIFICATION_TYPE_SUBJECT;

        $classification->save();

        self::assertEquals(1, $db->count_records(classification::TABLE));
        self::assertEquals(1, $db->count_records(classification::TABLE, ['urn' => 'urn:li:organization:1338']));

        try {
            $second_classification = new classification();
            $second_classification->urn = 'urn:li:organization:1338';
            $classification->name = 'Third name';
            $second_classification->locale_language = 'en';
            $second_classification->type = constants::CLASSIFICATION_TYPE_LIBRARY;

            $second_classification->save();
            self::fail("Expect an exception to be yield from the saving process");
        } catch (dml_write_exception $e) {
            // Each db vendor will produce a different message.
            // Hence we have to check against partially of the message
            $message = $e->getMessage();
            self::assertStringContainsString('Error writing to database', $message);
            self::assertStringContainsString('duplicate', strtolower($message));
        }

        self::assertEquals(1, $db->count_records(classification::TABLE));
    }

    /**
     * @return void
     */
    public function test_update_classification(): void {
        $classification = new classification();
        $classification->urn = 'urn:li:organization:1339';
        $classification->name = 'SSS';
        $classification->locale_language = 'en';
        $classification->type = constants::CLASSIFICATION_TYPE_SUBJECT;

        $classification->save();
        $db = builder::get_db();
        self::assertTrue(
            $db->record_exists(classification::TABLE, ['name' => 'SSS']),
        );

        $classification->name = 'XXX';
        $classification->save();

        self::assertFalse($db->record_exists(classification::TABLE, ['name' => 'SSS']));
        self::assertTrue($db->record_exists(classification::TABLE, ['name' => 'XXX']));
    }

    /**
     * @return void
     */
    public function test_update_classification_with_duplicate_urn(): void {
        $classification = new classification();
        $classification->urn = 'urn:li:organization:1339';
        $classification->locale_language = 'en';
        $classification->type = constants::CLASSIFICATION_TYPE_TOPIC;
        $classification->name = 'Microsoft Office';

        $classification->save();
        $db = builder::get_db();

        self::assertEquals(1, $db->count_records(classification::TABLE));

        $second_classification = new classification();
        $second_classification->urn = 'urn:li:organization:1340';
        $second_classification->locale_language = 'de';
        $second_classification->name = 'Doctor What';
        $second_classification->type = constants::CLASSIFICATION_TYPE_SUBJECT;

        $second_classification->save();
        self::assertEquals(2, $db->count_records(classification::TABLE));
        self::assertEquals(1, $db->count_records(classification::TABLE, ['urn' => 'urn:li:organization:1339']));

        // Start updating the second classification URN to the same as the first one.
        try {
            $second_classification->urn = 'urn:li:organization:1339';
            $second_classification->save();

            self::fail("Expect an exception to be yield from the saving process");
        } catch (dml_write_exception $e) {
            // Each db vendor will produce a different message.
            // Hence we have to check against partially of the message
            $message = $e->getMessage();
            self::assertStringContainsString('Error writing to database', $message);
            self::assertStringContainsString('duplicate', strtolower($message));
        }

        // There should only still one record.
        self::assertEquals(1, $db->count_records(classification::TABLE, ['urn' => 'urn:li:organization:1339']));
    }

    /**
     * @return void
     */
    public function test_delete_classification(): void {
        $db = builder::get_db();
        self::assertEquals(0, $db->count_records(classification::TABLE));

        $classification = new classification();
        $classification->urn = 'urn:li:organizaation:1339';
        $classification->locale_language = 'en';
        $classification->name = 'doctor whoo';
        $classification->type = constants::CLASSIFICATION_TYPE_LIBRARY;

        $classification->save();
        self::assertEquals(1, $db->count_records(classification::TABLE));

        $classification->delete();
        self::assertEquals(0, $db->count_records(classification::TABLE));
    }
}