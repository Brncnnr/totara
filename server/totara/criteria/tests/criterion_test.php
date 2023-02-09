<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_criteria
 */

use totara_criteria\criterion;
use totara_criteria\entity\criterion as criterion_entity;


/**
 * Test concrete criterion methods
 *
 * @group totara_competency
 */
class totara_criteria_criterion_testcase extends advanced_testcase {

    /**
     * Test setting, getting and saving a criterion with an ID number.
     */
    public function test_idnumber() {
        /** @var criterion $mock_criterion */
        $mock_criterion = $this->getMockForAbstractClass(criterion::class);
        $mock_criterion->save();

        $this->assertNull($mock_criterion->get_idnumber());

        $empty_idnumber = '';
        $mock_criterion->set_idnumber($empty_idnumber);
        $this->assertNull($mock_criterion->get_idnumber());

        $this->assertFalse($mock_criterion->is_dirty());

        $valid_idnumber = 'ABC';
        $mock_criterion->set_idnumber($valid_idnumber);
        $this->assertEquals($valid_idnumber, $mock_criterion->get_idnumber());
        $this->assertTrue($mock_criterion->is_dirty());

        $mock_criterion->save();
        $this->assertEquals($valid_idnumber, $mock_criterion->get_idnumber());
        $this->assertFalse($mock_criterion->is_dirty());

        /** @var criterion $mock_criterion2 */
        $mock_criterion2 = $this->getMockForAbstractClass(criterion::class);

        // Re-use the same id number that is already used.
        $mock_criterion2->set_idnumber($valid_idnumber);

        $this->expectExceptionMessage("ID number '{$valid_idnumber}' already exists in " . criterion_entity::TABLE);
        $mock_criterion2->save();
    }

    /**
     * Test aggregation attributes
     */
    public function test_aggregation_attributes() {
        $mock_criterion = $this->getMockForAbstractClass(criterion::class);

        $this->assertNull($mock_criterion->get_id());
        $this->assertSame(criterion::AGGREGATE_ALL, $mock_criterion->get_aggregation_method());
        $this->assertSame([], $mock_criterion->get_aggregation_params());

        // Params :-
        // Array
        $mock_criterion->set_aggregation_params(['p1' => 'param 1']);
        $this->assertSame(['p1' => 'param 1'], $mock_criterion->get_aggregation_params());
        // Json
        $mock_criterion->set_aggregation_params(json_encode(['p2' => 'param 2', 'p3' => 'param 3']));
        $this->assertSame(['p2' => 'param 2', 'p3' => 'param 3'], $mock_criterion->get_aggregation_params());
        // Null
        $mock_criterion->set_aggregation_params(null);
        $this->assertSame([], $mock_criterion->get_aggregation_params());

        // Method :-
        $mock_criterion->set_aggregation_method(criterion::AGGREGATE_ANY_N);
        $this->assertSame(criterion::AGGREGATE_ANY_N, $mock_criterion->get_aggregation_method());

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Invalid aggregation method used');
        $mock_criterion->set_aggregation_method(123);
    }

    /**
     * Test items
     */
    public function test_items() {
        $mock_criterion = $this->getMockForAbstractClass(criterion::class);

        $this->assertSame([], $mock_criterion->get_item_ids());

        // Set items :-
        $mock_criterion->set_item_ids([1, 2, 3]);
        $this->assertSame([1, 2, 3], $mock_criterion->get_item_ids());
        $mock_criterion->set_item_ids([4, 5]);
        $this->assertSame([4, 5], $mock_criterion->get_item_ids());
        $mock_criterion->set_item_ids([]);
        $this->assertSame([], $mock_criterion->get_item_ids());

        // Add items :-
        $mock_criterion->add_items([1, 2, 3]);
        $this->assertEqualsCanonicalizing([1, 2, 3], $mock_criterion->get_item_ids());

        $mock_criterion->add_items([4, 5]);
        $this->assertEqualsCanonicalizing([1, 2, 3, 4, 5], $mock_criterion->get_item_ids());
        $mock_criterion->add_items([2, 6]);
        $this->assertEqualsCanonicalizing([1, 2, 3, 4, 5, 6], $mock_criterion->get_item_ids());
        $mock_criterion->add_items([]);
        $this->assertEqualsCanonicalizing([1, 2, 3, 4, 5, 6], $mock_criterion->get_item_ids());

        // Remove items :-
        $mock_criterion->remove_items([1, 2, 3]);
        $this->assertEqualsCanonicalizing([4, 5, 6], $mock_criterion->get_item_ids());
        $mock_criterion->remove_items([1, 2, 3]);
        $this->assertEqualsCanonicalizing([4, 5, 6], $mock_criterion->get_item_ids());
        $mock_criterion->remove_items([4, 5]);
        $this->assertEqualsCanonicalizing([6], $mock_criterion->get_item_ids());
        $mock_criterion->remove_items([]);
        $this->assertEqualsCanonicalizing([6], $mock_criterion->get_item_ids());
    }

    /**
     * Test adding of invalid metadata
     */
    public function test_add_metadata_invalid_array() {
        $mock_criterion = $this->getMockForAbstractClass(criterion::class);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Criterion metadata requires a metakey / metavalue pair');
        $mock_criterion->add_metadata([123]);
    }

    /**
     * Test adding with missing metakey
     */
    public function test_add_metadata_missing_metakey() {
        $mock_criterion = $this->getMockForAbstractClass(criterion::class);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Criterion metadata requires a metakey / metavalue pair');
        $mock_criterion->add_metadata(['metavalue' => 123]);
    }

    /**
     * Test adding with missing metavalue
     */
    public function test_add_metadata_missing_metavalue() {
        $mock_criterion = $this->getMockForAbstractClass(criterion::class);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Criterion metadata requires a metakey / metavalue pair');
        $mock_criterion->add_metadata(['metakey' => 'metakey']);
    }

    /**
     * Test metadata
     */
    public function test_metadata() {
        $mock_criterion = $this->getMockForAbstractClass(criterion::class);

        $this->assertSame([], $mock_criterion->get_metadata());

        // Add :-
        // New
        $mock_criterion->add_metadata([['metakey' => 'the key', 'metavalue' => 'the value']]);
        $this->assertEquals(['the key' => 'the value'], $mock_criterion->get_metadata());

        // One new, One updated
        $mock_criterion->add_metadata([
            ['metakey' => 'the key', 'metavalue' => 'new value'],
            ['metakey' => 'key2', 'metavalue' => 'value2']
        ]);
        $this->assertEquals(['the key' => 'new value', 'key2' => 'value2'], $mock_criterion->get_metadata());

        // Nothing new
        $mock_criterion->add_metadata([['metakey' => 'the key', 'metavalue' => 'new value']]);
        $this->assertEquals(['the key' => 'new value', 'key2' => 'value2'], $mock_criterion->get_metadata());

        // Set :-
        $mock_criterion->set_metadata([['metakey' => 'the key', 'metavalue' => 'set value']]);
        $this->assertEquals(['the key' => 'set value'], $mock_criterion->get_metadata());
        // Last value is used
        $mock_criterion->set_metadata([
            ['metakey' => 'the key', 'metavalue' => 'set value'],
            ['metakey' => 'key2', 'metavalue' => 'value2'],
            ['metakey' => 'key2', 'metavalue' => 'value3']
        ]);
        $this->assertEquals(['the key' => 'set value', 'key2' => 'value3'], $mock_criterion->get_metadata());

        // Remove :-
        // Empty
        $mock_criterion->remove_metadata([]);
        $this->assertEquals(['the key' => 'set value', 'key2' => 'value3'], $mock_criterion->get_metadata());

        // Non-existing
        $mock_criterion->remove_metadata(['some key']);
        $this->assertEquals(['the key' => 'set value', 'key2' => 'value3'], $mock_criterion->get_metadata());

        // Existing
        $mock_criterion->remove_metadata(['the key']);
        $this->assertEquals(['key2' => 'value3'], $mock_criterion->get_metadata());

        // Last
        $mock_criterion->remove_metadata(['key2']);
        $this->assertEquals([], $mock_criterion->get_metadata());
    }

    /**
     * Test competency_id
     */
    public function test_competency_id() {
        $mock_criterion = $this->getMockForAbstractClass(criterion::class);

        $this->assertNull($mock_criterion->get_competency_id());

        $mock_criterion->set_competency_id(123);
        // set_competency_id is just a convenience method for adding metadata
        $this->assertEquals([criterion::METADATA_COMPETENCY_KEY => 123], $mock_criterion->get_metadata());
        $this->assertEquals(123, $mock_criterion->get_competency_id());

        // Overwrite
        $mock_criterion->set_competency_id(456);
        $this->assertEquals([criterion::METADATA_COMPETENCY_KEY => 456], $mock_criterion->get_metadata());
        $this->assertEquals(456, $mock_criterion->get_competency_id());
    }

    /**
     * Test save and fetch
     */
    public function test_save_and_fetch() {
        global $DB;

        $mock_criterion = $this->getMockForAbstractClass(criterion::class);

        // Ensure the mock criterion is stored
        $mock_criterion->save();
        $row = $DB->get_record('totara_criteria', []);
        $this->assertEquals($mock_criterion->get_id(), $row->id);
        $this->assertEquals($mock_criterion->get_plugin_type(), $row->plugin_type);
        $this->assertEquals($mock_criterion->get_aggregation_method(), $row->aggregation_method);
        $this->assertEquals('[]', $row->aggregation_params);

        // Test fetch - Not the same object but same information
        $fetched_criterion = $mock_criterion->fetch($row->id);
        // Ignoring validity
        $fetched_criterion->validate();
        $this->assertNotSame($mock_criterion, $fetched_criterion);
        $this->assertEquals($mock_criterion, $fetched_criterion);

        // Now add some items and metadata
        $mock_criterion2 = $this->getMockForAbstractClass(criterion::class);
        $mock_criterion2->method('get_items_type')
            ->willReturn('mockitem');
        $mock_criterion2->set_item_ids([1, 2, 3]);
        $mock_criterion2->set_competency_id(345);
        $mock_criterion2->set_idnumber('mock_criterion2');
        $mock_criterion2->save();
        $mock_criterion2->fetch($mock_criterion2->get_id());
        $this->assertNotSame($mock_criterion, $fetched_criterion);
        $this->assertEquals($mock_criterion, $fetched_criterion);

        // Change items and metadata
        $mock_criterion2->add_items([2, 3, 5]);
        $mock_criterion2->set_competency_id(456);
        $mock_criterion2->set_idnumber('mock_criterion2_new');
        $mock_criterion2->add_metadata([['metakey' => 'key1', 'metavalue' => 'value1']]);
        $mock_criterion2->save();
        $mock_criterion2->fetch($mock_criterion2->get_id());
        $this->assertEquals($mock_criterion, $fetched_criterion);
    }

    /**
     * Test delete
     */
    public function test_delete() {
        global $DB;

        // No items or metadata
        $mock_criterion = $this->getMockForAbstractClass(criterion::class);
        $mock_criterion->save();
        $id = $mock_criterion->get_id();

        $this->assertTrue($DB->record_exists('totara_criteria', ['id' => $id]));
        $this->assertFalse($DB->record_exists('totara_criteria_item', ['criterion_id' => $id]));
        $this->assertFalse($DB->record_exists('totara_criteria_metadata', ['criterion_id' => $id]));

        $mock_criterion->delete();
        $this->assertFalse($DB->record_exists('totara_criteria', ['id' => $id]));
        $this->assertFalse($DB->record_exists('totara_criteria_item', ['criterion_id' => $id]));
        $this->assertFalse($DB->record_exists('totara_criteria_metadata', ['criterion_id' => $id]));

        // With items and metadata
        $mock_criterion2 = $this->getMockForAbstractClass(criterion::class);
        $mock_criterion2->method('get_items_type')
            ->willReturn('mockitem');
        $mock_criterion2->set_item_ids([1, 2, 3]);
        $mock_criterion2->set_competency_id(123);
        $mock_criterion2->set_idnumber('mock_criterion2');
        $mock_criterion2->save();
        $id = $mock_criterion2->get_id();

        $this->assertTrue($DB->record_exists('totara_criteria', ['id' => $id]));
        $this->assertTrue($DB->record_exists('totara_criteria_item', ['criterion_id' => $id]));
        $this->assertTrue($DB->record_exists('totara_criteria_metadata', ['criterion_id' => $id]));

        $mock_criterion2->delete();
        $this->assertFalse($DB->record_exists('totara_criteria', ['id' => $id]));
        $this->assertFalse($DB->record_exists('totara_criteria_item', ['criterion_id' => $id]));
        $this->assertFalse($DB->record_exists('totara_criteria_metadata', ['criterion_id' => $id]));
    }
}
