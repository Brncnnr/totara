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

use contentmarketplace_linkedin\entity\learning_object_classification;
use core\orm\query\builder;
use core_phpunit\testcase;
use contentmarketplace_linkedin\testing\generator;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_entity_learning_object_classification_testcase extends testcase {
    /**
     * @return void
     */
    public function test_insert_learning_object_parent(): void {
        $generator = generator::instance();

        $classification = $generator->create_classification();
        $learning_object = $generator->create_learning_object('urn:li:lyndaCourse:496');

        $db = builder::get_db();
        self::assertEquals(0, $db->count_records(learning_object_classification::TABLE));

        $map = new learning_object_classification();
        $map->learning_object_id = $learning_object->id;
        $map->classification_id = $classification->id;
        $map->save();

        self::assertEquals(1, $db->count_records(learning_object_classification::TABLE));
        self::assertEquals(1, $db->count_records(learning_object_classification::TABLE, ['classification_id' => $classification->id]));
        self::assertEquals(1, $db->count_records(learning_object_classification::TABLE, ['learning_object_id' => $learning_object->id]));
    }

    /**
     * @return void
     */
    public function test_insert_same_parent_map(): void {
        $generator = generator::instance();

        $classification = $generator->create_classification();
        $learning_object = $generator->create_learning_object('urn:li:lyndaCourse:458');

        $db = builder::get_db();
        self::assertEquals(0, $db->count_records(learning_object_classification::TABLE));

        $map = new learning_object_classification([
            'learning_object_id' => $learning_object->id,
            'classification_id' => $classification->id
        ]);

        $map->save();
        self::assertEquals(1, $db->count_records(learning_object_classification::TABLE));

        try {
            $new_map = new learning_object_classification();
            $new_map->learning_object_id = $learning_object->id;
            $new_map->classification_id = $classification->id;

            $new_map->save();
        } catch (dml_write_exception $e) {
            // Each db vendor will produce a different message.
            // Hence we have to check against partially of the message
            $message = $e->getMessage();

            self::assertStringContainsString('Error writing to database', $message);
            self::assertStringContainsString('duplicate', strtolower($message));
        }
    }

    /**
     * @return void
     */
    public function test_delete_record(): void {
        $generator = generator::instance();
        $learning_object = $generator->create_learning_object('urn:li:lyndaCourse:458');
        $classification = $generator->create_classification();

        $db = builder::get_db();
        self::assertEquals(0, $db->count_records(learning_object_classification::TABLE));

        $map = new learning_object_classification([
            'learning_object_id' => $learning_object->id,
            'classification_id' => $classification->id
        ]);

        $map->save();
        self::assertEquals(1, $db->count_records(learning_object_classification::TABLE));

        $map->delete();
        self::assertEquals(0, $db->count_records(learning_object_classification::TABLE));
    }

    /**
     * @return void
     */
    public function test_update_record(): void {
        $generator = generator::instance();

        $classification = $generator->create_classification();
        $learning_object_one = $generator->create_learning_object('urn:li:lyndaCourse:458');

        $map = new learning_object_classification();
        $map->learning_object_id = $learning_object_one->id;
        $map->classification_id = $classification->id;
        $map->save();

        $db = builder::get_db();

        self::assertEquals(1, $db->count_records(learning_object_classification::TABLE, ['classification_id' => $classification->id]));
        self::assertEquals(1, $db->count_records(learning_object_classification::TABLE, ['learning_object_id' => $learning_object_one->id]));
        self::assertEquals(1, $db->count_records(learning_object_classification::TABLE));

        $learning_object_two = $generator->create_learning_object('urn:li:lyndaCourse:469');
        $map->learning_object_id = $learning_object_two->id;
        $map->save();

        self::assertEquals(1, $db->count_records(learning_object_classification::TABLE));
        self::assertEquals(1, $db->count_records(learning_object_classification::TABLE, ['classification_id' => $classification->id]));
        self::assertEquals(0, $db->count_records(learning_object_classification::TABLE, ['learning_object_id' => $learning_object_one->id]));

        self::assertEquals(1, $db->count_records(learning_object_classification::TABLE, ['learning_object_id' => $learning_object_two->id]));
    }
}