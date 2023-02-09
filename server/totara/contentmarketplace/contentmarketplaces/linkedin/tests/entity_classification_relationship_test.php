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

use contentmarketplace_linkedin\entity\classification_relationship;
use contentmarketplace_linkedin\testing\generator;
use core\orm\query\builder;
use core_phpunit\testcase;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_entity_classification_relationship_testcase extends testcase {
    /**
     * @return void
     */
    public function test_insert_record(): void {
        $generator = generator::instance();

        $parent_classification = $generator->create_classification();
        $child_classification = $generator->create_classification();

        $db = builder::get_db();
        self::assertEquals(0, $db->count_records(classification_relationship::TABLE));

        $map = new classification_relationship();
        $map->parent_id = $parent_classification->id;
        $map->child_id = $child_classification->id;

        $map->save();
        self::assertEquals(1, $db->count_records(classification_relationship::TABLE));
        self::assertEquals(1, $db->count_records(classification_relationship::TABLE, ['parent_id' => $parent_classification->id]));
        self::assertEquals(1, $db->count_records(classification_relationship::TABLE, ['child_id' => $child_classification->id]));

        // Test duplicate insertion.
        try {
            $new_map = new classification_relationship();
            $new_map->parent_id = $parent_classification->id;
            $new_map->child_id = $child_classification->id;

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
    public function test_insert_record_with_same_classification_id(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Cannot create a relationship record of the same classification id");

        $map = new classification_relationship();
        $map->parent_id = 42;
        $map->child_id = 42;

        $map->save();
    }

    /**
     * @return void
     */
    public function test_delete_record(): void {
        $generator = generator::instance();

        $parent_classification = $generator->create_classification('urn:li:organization:5558');
        $child_classification = $generator->create_classification('urn:li:organization:468');

        $db = builder::get_db();
        self::assertEquals(0, $db->count_records(classification_relationship::TABLE));

        $map = new classification_relationship();
        $map->parent_id = $parent_classification->id;
        $map->child_id = $child_classification->id;

        $map->save();
        self::assertEquals(1, $db->count_records(classification_relationship::TABLE));

        $map->delete();
        self::assertEquals(0, $db->count_records(classification_relationship::TABLE));
    }

    /**
     * @rerturn void
     */
    public function test_update_record(): void {
        $generator = generator::instance();

        $parent_classification = $generator->create_classification();
        $child_classification = $generator->create_classification();

        $map = new classification_relationship();
        $map->parent_id = $parent_classification->id;
        $map->child_id = $child_classification->id;
        $map->save();

        $db = builder::get_db();
        self::assertEquals(1, $db->count_records(classification_relationship::TABLE));
        self::assertEquals(1, $db->count_records(classification_relationship::TABLE, ['parent_id' => $parent_classification->id]));
        self::assertEquals(1, $db->count_records(classification_relationship::TABLE, ['child_id' => $child_classification->id]));

        $new_child_classification = $generator->create_classification();
        $map->child_id = $new_child_classification->id;
        $map->save();

        self::assertEquals(1, $db->count_records(classification_relationship::TABLE));
        self::assertEquals(1, $db->count_records(classification_relationship::TABLE, ['parent_id' => $parent_classification->id]));
        self::assertEquals(0, $db->count_records(classification_relationship::TABLE, ['child_id' => $child_classification->id]));

        self::assertEquals(1, $db->count_records(classification_relationship::TABLE, ['child_id' => $new_child_classification->id]));
    }
}