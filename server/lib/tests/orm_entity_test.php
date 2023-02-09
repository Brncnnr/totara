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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package core_orm
 * @category test
 */

use core\orm\entity\entity;
use core\orm\entity\relations\has_one;
use core\orm\query\exceptions\record_not_found_exception;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/tests/orm_entity_testcase.php');

/**
 * Class core_orm_entity_testcase
 *
 * @package core
 * @group orm
 */
class core_orm_entity_testcase extends orm_entity_testcase {

    public function test_it_instantiates_entity() {
        $entity = new sample_entity();
        $this->assertInstanceOf(entity::class, $entity);

        $this->assertFalse($entity->exists());
    }

    public function test_it_instantiates_entity_from_array() {

        $params = [
            'id' => 123,
            'name' => 'John',
            'created_at' => '1544499389'
        ];

        $entity = new sample_entity($params, false);

        $this->assertEquals(123, $entity->id);
        $this->assertEquals('John', $entity->name);
        $this->assertEquals('1544499389', $entity->created_at);

        $this->assertTrue($entity->exists());
    }

    public function test_it_instantiates_entity_from_db() {

        $this->create_table();

        $record = $this->create_sample_record();

        $entity = new sample_entity($record['id']);

        $this->assertInstanceOf(entity::class, $entity);
        $this->assertEquals($record['id'], $entity->id);
        $this->assertEquals('John', $entity->name);
        $this->assertEquals(0, $entity->type);
        $this->assertEquals(0, $entity->parent_id);
        $this->assertTrue($entity->exists());

        // It should fail if a record does not exist
        $this->expectException(record_not_found_exception::class);
        new sample_entity(12345678);
    }

    public function test_it_returns_table_name() {
        $entity = new sample_entity();

        $this->assertEquals($this->table_name, $entity->get_table());
    }

    public function test_it_does_not_allow_entities_without_table_name() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Missing table name for entity');

        new class extends entity {

        };
    }

    public function test_it_does_not_allow_entities_constructed_from_whatever() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Invalid param \'{$id}\' for entity');

        new sample_entity(new sample_entity());
    }

    public function test_it_allows_only_repositories_extending_parent() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Custom repositories must extend the repository class.');

        (new class extends sample_entity {
            public static function repository_class_name(): string {
                return stdClass::class;
            }
        })::repository();
    }

    public function test_it_converts_to_array() {

        $record = $this->create_sample_record();

        $entity = new sample_entity($record['id']);

        $this->assertEquals($record, $entity->to_array());
    }

    public function test_it_converts_to_record() {

        $record = (object) $this->create_sample_record();

        $entity = new extended_sample_entity($record->id);

        $this->assertEquals($record, $entity->to_record(true));

        $record_with_extra_attribute = clone $record;
        $record_with_extra_attribute->capital_name = strtoupper($record->name);
        $record_with_extra_attribute->type = 1;

        $this->assertEquals($record_with_extra_attribute, $entity->to_record());
        $this->assertEquals($record_with_extra_attribute, $entity->to_record(false));
    }

    public function test_it_converts_to_json() {

        $record = $this->create_sample_record();

        $entity = new sample_entity($record['id']);

        $this->assertEquals(json_decode(json_encode($record)), json_decode(json_encode($entity)));
    }

    public function test_it_appends_extra_attributes_when_converted_to_array_or_json() {

        $record = $this->create_sample_record();

        $entity = new extended_sample_entity($record['id']);

        $this->assertFalse(isset($record['capital_name']));
        $record['capital_name'] = strtoupper($record['name']);

        // This entity has custom getter for type, so to align it, we need to align record, by adding type + 1
        $record['type'] += 1;

        $this->assertEquals($record, $entity->to_array());

        $this->assertEquals(json_decode(json_encode($record)), json_decode(json_encode($entity)));

        // Now let's check that we can add extra attributes on the go.

        $this->assertFalse(isset($record['super_extra']));
        $record['super_extra'] = 'I am super extra';

        $this->assertSame($entity, $entity->add_extra_attribute('super_extra'));
        $this->assertEquals(json_decode(json_encode($record)), json_decode(json_encode($entity)));
    }

    public function test_it_returns_whether_entity_exists() {
        // Existence of a entity is determined by several things.
        // First is the existence of id field, however entities created manually from array and with id field may explicitly
        // specify that they exist. This would prevent accidental creation of an extra record in the database if the
        // entity was fetched from the database without id field.
        // It will control whether create or update query function is used.
        // It is possible to cheat the system and slip a bogus id when creating a entity from array
        // that will lead to entity returns that it exists. It is intentional behaviour.
        // The id attribute can be set only once, an exception will be thrown if someone tries again,
        // also the entity will be marked as existing when id attribute is set.

        $record = $this->create_sample_record();

        // Real entity exists
        $entity = new sample_entity($record['id']);
        $this->assertTrue($entity->exists());

        // Fake entity constructed from a record exists
        $entity = new sample_entity([
            'id' => 12345,
            'name' => 'John',
        ], false);

        $this->assertTrue($entity->exists());

        // Fake entity constructed from a record does not exist if explicitly said so
        $entity = new sample_entity([
            'id' => 12345,
            'name' => 'John',
        ], false, false);

        $this->assertFalse($entity->exists());

        // Fake entity constructed from a record without id does not exist
        $entity = new sample_entity([
            'name' => 'John',
        ], false);

        $this->assertFalse($entity->exists());

        // New entity does not exist
        $entity = new sample_entity();

        $entity->parent_id = 233;
        $entity->updated_at = 1223345;

        $this->assertFalse($entity->exists());

        // This equals to $entity->id = 1;
        // But phpstorm treats is as error because id property documented as read only.
        $entity->set_attribute('id', 1);
        $this->assertTrue($entity->exists());

        // Test that ID can only be set once
        $this->expectException('coding_exception');
        $this->expectExceptionMessage('Id on this entity has already been set and cannot be set again.');
        $entity->set_attribute('id', 2);
    }

    public function test_it_detects_existence_on_creation() {
        // Id not present - entity does not exist
        $entity = new sample_entity();
        $this->assertFalse($entity->exists());

        $entity->id = 69;
        $this->assertTrue($entity->exists());

        // Id present - entity exists
        $entity = new sample_entity([
            'id' => 5
        ]);

        $this->assertTrue($entity->exists());

        // Id doesn't meet the conditions of is_numeric and > 0
        $entity = new sample_entity([
            'id' => 0
        ]);

        $this->assertFalse($entity->exists());

        // Id doesn't meet the conditions of is_numeric and > 0
        $entity = new sample_entity([
            'id' => 'five'
        ]);

        $this->assertFalse($entity->exists());

        // Explicitly say entity exists
        $entity = new sample_entity([
            'name' => 'Jane Doe',
        ], false, true);

        $this->assertTrue($entity->exists());

        // Explicitly say entity does not exist
        $entity = new sample_entity([
            'name' => 'Jane Doe',
            'id' => 96,
        ], false, false);

        $this->assertFalse($entity->exists());
    }

    public function test_it_plays_nicely_without_id() {
        $this->create_table();

        $entity = new sample_entity();
        $entity->name = 'John Doe';
        $entity->type = 0;
        $entity->parent_id = 0;

        $entity->save();

        $this->assertEquals(1, $this->db()->count_records($this->table_name));

        $entity->save();

        $this->assertEquals(1, $this->db()->count_records($this->table_name));

        $another_entity = new sample_entity($entity->id);

        $another_entity->save();

        $this->assertEquals(1, $this->db()->count_records($this->table_name));

        $third_entity = sample_entity::repository()
            ->select(['name', 'parent_id'])
            ->where('id', $entity->id)
            ->one();
            // Here I have to say that select only works with get
            // With find you get a full set of attributes
            // Find should be static altogether I believe.

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('To update an existing entity you must have an ID attribute present.');

        $third_entity->save();
    }

    public function test_it_reports_correctly_whether_the_entity_has_changed_since_last_saved() {
        $record = $this->create_sample_record();

        $entity = new sample_entity($record['id']);

        $this->assertFalse($entity->changed());
        $entity->parent_id = 5;

        $this->assertTrue($entity->changed());
        $entity->save();

        $this->assertFalse($entity->changed());

        $entity = new sample_entity();
        $this->assertFalse($entity->changed());

        $entity->name = 'Name';
        $this->assertTrue($entity->changed());
        $entity->type = 1;
        $entity->parent_id = 1;
        $this->assertTrue($entity->changed());
        $entity->save();
        $this->assertNotNull($entity->id);
        $this->assertFalse($entity->changed());
    }

    public function test_it_returns_id() {
        $record = $this->create_sample_record();

        $entity = (new sample_entity($record['id']));
        $this->assertSame(intval($record['id']), $entity->id);
    }

    public function test_it_returns_proper_default_values_from_db() {
        $this->create_table();

        $entity = new sample_entity();

        $this->assertEquals('John Doe', $entity->name);
        $this->assertEquals(0, $entity->is_deleted);
        $this->assertEquals(0, $entity->type);
        $this->assertNull($entity->params);
        $this->assertNull($entity->parent_id);
    }

    public function test_it_has_working_getters() {
        $record = $this->sample_record();

        $entity = new extended_sample_entity($record, false);

        // Custom getter.
        $this->assertEquals(strtoupper($record['name']), $entity->capital_name);
        $this->assertEquals(strtoupper($record['name']), $entity->get_attribute('capital_name'));

        // Built-in getters for existing attributes
        $this->assertEquals($record['name'], $entity->name);
        $this->assertEquals($record['name'], $entity->get_attribute('name'));

        $this->assertEquals($record['created_at'], $entity->created_at);
        $this->assertEquals($record['created_at'], $entity->get_attribute('created_at'));
    }

    public function test_it_has_working_setters() {
        $record = $this->sample_record();

        $entity = new extended_sample_entity($record, false);

        // Set an attributes via assigning property on a entity
        $this->assertNotEquals($entity->name, $new_name = 'This is a new name');
        $entity->name = $new_name;
        $this->assertEquals($new_name, $entity->name);

        // Set an attribute via set attribute method
        $this->assertNotEquals($entity->updated_at, $updated = intval($record['updated_at']) + 10);
        $entity->set_attribute('updated_at', $updated);
        $this->assertEquals($updated, $entity->updated_at);
    }

    public function test_it_has_working_isset_method() {
        $record = $this->sample_record();

        $entity = new extended_sample_entity($record, false);

        $this->assertTrue(isset($entity->name));
        $this->assertFalse(isset($entity->i_dont_exist));
        $this->assertTrue(isset($entity->capital_name));
    }

    public function test_it_has_working_unset_method() {
        $record = $this->sample_record();

        $entity = new extended_sample_entity($record, false);

        $this->assertTrue(isset($entity->name));
        $this->assertNotNull($entity->name);
        unset($entity->name);
        $this->assertFalse(isset($entity->name));
        $this->assertNull($entity->name);
        $this->assertDebuggingCalled();
    }

    public function test_it_sets_created_timestamp_automatically() {
        $this->create_table();
        $record = $this->sample_record();

        $record['created_at'] = null;
        $record['updated_at'] = null;

        $this->assertFalse(isset($record['created_at']));
        $this->assertFalse(isset($record['updated_at']));
        $this->assertFalse(isset($record['id']));

        // Test it doesn't touch timestamps if the field is not set.
        $entity = (new sample_entity($record, false))->update_timestamps()->save();

        $this->assertNull($entity->created_at);

        // Test that it sets the timestamp.
        $entity = new extended_sample_entity($record, false);
        $entity->save();

        $this->assertNotNull($entity->created_at);
        $this->assertNull($entity->updated_at);
        $this->assertEqualsWithDelta(floatval(time()), floatval($entity->created_at), 2, 'Created timestamp was not set properly');

        // Test that it doesn't set the timestamp if it has already been provided
        $record['created_at'] = 0;
        $entity = new extended_sample_entity($record, false);
        $entity->save();

        $this->assertNotNull($entity->created_at);
        $this->assertNull($entity->updated_at);
        $this->assertEqualsWithDelta(0, $entity->created_at, 2, 'Created timestamp was not set properly');
        $this->assertNotEqualsWithDelta(floatval(time()), $entity->created_at, 2, 'Created timestamp was not set properly');

        // Test that it doesn't set the timestamp if it has been disabled
        $record['created_at'] = 0;
        $entity = (new extended_sample_entity($record, false));
        $entity->do_not_update_timestamps()->save();

        $this->assertNotNull($entity->created_at);
        $this->assertNull($entity->updated_at);
        $this->assertEqualsWithDelta(0, $entity->created_at, 2, 'Created timestamp was not set properly');
        $this->assertNotEqualsWithDelta(floatval(time()), $entity->created_at, 2, 'Created timestamp was not set properly');
    }

    public function test_it_sets_created_timestamp_if_passed_as_parameter() {
        $this->create_table();

        $entity = new extended_sample_entity_created_updated_at_when_created();
        $entity->type = 1;
        $entity->params = 'params';
        $entity->parent_id = 0;

        $entity->set_created_timestamp(1243);
        $entity->save();

        $this->assertEquals(1243, $entity->created_at);
    }

    public function test_it_sets_updated_timestamp_automatically() {
        $this->create_table();
        $record = $this->sample_record();

        $record['created_at'] = null;
        $record['updated_at'] = null;

        $this->assertFalse(isset($record['created_at']));
        $this->assertFalse(isset($record['updated_at']));
        $this->assertFalse(isset($record['id']));

        // Test it doesn't touch timestamps if the field is not set.
        $entity = (new sample_entity($record, false))->update_timestamps()->save();

        $this->assertNull($entity->updated_at);

        // Set updated timestamp when entity is created
        $entity = new extended_sample_entity_updated_at_when_created($record);
        $entity->save();

        $this->assertNotNull($entity->updated_at);
        $this->assertNull($entity->created_at);
        $this->assertEqualsWithDelta(floatval(time()), floatval($entity->updated_at), 2, 'Updated timestamp was not set properly');

        $old_updated_at = $entity->updated_at;

        $this->waitForSecond();

        $entity->params = 'new params';
        $entity->save();

        // Updated at should now be updated
        $this->assertGreaterThan($old_updated_at, $entity->updated_at);

        // Do not set updated timestamps when entity is created, but set when it's updated
        $entity = new extended_sample_entity_updated_at($record);
        $entity->save();

        $this->assertNull($entity->updated_at);
        $this->assertNull($entity->created_at);

        $entity->save();
        $this->assertNotNull($entity->updated_at);
        $this->assertNull($entity->created_at);
        $this->assertEqualsWithDelta(floatval(time()), floatval($entity->updated_at), 2, 'Updated timestamp was not set properly');

        // Do not set updated timestamps if the functionality is disabled
        $entity = new extended_sample_entity_updated_at($record);
        $entity->do_not_update_timestamps()
            ->save();

        $this->assertNull($entity->updated_at);
        $this->assertNull($entity->created_at);

        $entity->save();
        $this->assertNull($entity->updated_at);
        $this->assertNull($entity->created_at);
    }

    public function test_it_sets_timestamps_automatically() {
        $this->create_table();
        $record = $this->sample_record();

        $new_record = $this->sample_record([
            'created_at' => null,
            'updated_at' => null,
            'params' => null,
        ]);

        $this->assertNull($new_record['created_at']);
        $this->assertNull($new_record['updated_at']);
        $this->assertNull($new_record['params']);

        $record['created_at'] = null;
        $record['updated_at'] = null;
        $record['params'] = null;

        $this->assertFalse(isset($record['created_at']));
        $this->assertFalse(isset($record['updated_at']));
        $this->assertFalse(isset($record['params']));
        $this->assertFalse(isset($record['id']));

        // Timestamps not set when creating entity
        $entity = new extended_sample_entity_created_updated_at($record);
        $entity->do_not_update_timestamps()->save();

        $this->assertNull($entity->get_attribute('created_at'));
        $this->assertNull($entity->updated_at);

        // Timestamps set when creating entity
        $entity = new extended_sample_entity_created_updated_at_when_created($record);
        $entity->update_timestamps()
            ->save();

        $this->assertNotNull($entity->created_at);
        $this->assertNotNull($entity->get_attribute('updated_at'));
        $this->assertEqualsWithDelta(floatval(time()), floatval($entity->created_at), 2, 'Created timestamp was not set properly');
        $this->assertEqualsWithDelta(floatval(time()), floatval($entity->updated_at), 2, 'Updated timestamp was not set properly');

        // Timestamps set when creating entity, but updated timestamp disabled
        $entity = new extended_sample_entity_created_updated_at($record);
        $entity->update_timestamps()
            ->save();

        $this->assertNotNull($entity->created_at);
        $this->assertNull($entity->get_attribute('updated_at'));
        $this->assertEqualsWithDelta(floatval(time()), floatval($entity->created_at), 2, 'Created timestamp was not set properly');

        $entity->save();

        $this->assertNotNull($entity->updated_at);
        $this->assertEqualsWithDelta(floatval(time()), floatval($entity->updated_at), 2, 'Updated timestamp was not set properly');

        // Any attribute can be a timestamp
        $entity = new extended_sample_entity_created_at_custom($record);
        $entity->save();

        $this->assertNotNull($entity->params);
        $this->assertNull($entity->created_at);
        $this->assertNull($entity->get_attribute('updated_at'));
        $this->assertEqualsWithDelta(floatval(time()), floatval($entity->params), 2, 'Created timestamp was not set properly');

        // Any attribute can be a timestamp
        $entity = new extended_sample_entity_updated_at_custom($record);
        $entity->save();

        $this->assertNotNull($entity->params);
        $this->assertNull($entity->created_at);
        $this->assertNull($entity->get_attribute('updated_at'));
        $this->assertEqualsWithDelta(floatval(time()), floatval($entity->params), 2, 'Created timestamp was not set properly');
    }

    public function test_it_sets_timestamps_with_different_constructor_args() {
        $this->create_table();

        // no timestamps passed on instantiation
        $entity = new extended_sample_entity_created_updated_at_when_created();
        $entity->type = 1;
        $entity->params = 'params';
        $entity->parent_id = 0;
        $entity->save();

        $this->assertGreaterThan(0, $entity->created_at);
        $this->assertEquals($entity->created_at, $entity->updated_at);

        // timestamp set manually, so we should not overwrite it automatically
        $entity = new extended_sample_entity_created_updated_at_when_created();
        $entity->type = 1;
        $entity->params = 'params';
        $entity->parent_id = 0;
        $entity->created_at = 123;
        $entity->save();

        $this->assertEquals(123, $entity->created_at);
        $this->assertEquals($entity->created_at, $entity->updated_at);

        // timestamps set manually, so we should not overwrite it automatically
        $entity = new extended_sample_entity_created_updated_at_when_created();
        $entity->type = 1;
        $entity->params = 'params';
        $entity->parent_id = 0;
        $entity->created_at = 123;
        $entity->updated_at = 321;
        $entity->save();

        $this->assertEquals(123, $entity->created_at);
        $this->assertEquals(123, $entity->updated_at);

        $entity->params = 'sadasd';
        $entity->updated_at = 789;
        $entity->do_not_update_timestamps()->save();

        $this->assertEquals(123, $entity->created_at);
        $this->assertEquals(789, $entity->updated_at);

        // timestamp set manually to null, so we should overwrite it automatically
        $entity = new extended_sample_entity_created_updated_at_when_created();
        $entity->type = 1;
        $entity->params = 'params';
        $entity->parent_id = 0;
        $entity->created_at = null;
        $entity->save();

        $this->assertGreaterThan(0, $entity->created_at);
        $this->assertEquals($entity->created_at, $entity->updated_at);

        // Updated timestamp is not set automatically
        $entity = new extended_sample_entity_created_updated_at();
        $entity->type = 1;
        $entity->params = 'params';
        $entity->parent_id = 0;
        $entity->save();

        $this->assertGreaterThan(0, $entity->created_at);
        $this->assertEmpty($entity->updated_at);

        // Make sure updated_at is different to created_at
        $this->waitForSecond();

        // Now update the record and check if updated at was correctly set
        $entity->params = 'params2';
        $entity->save();

        $this->assertGreaterThan(0, $entity->updated_at);
        $this->assertNotEquals($entity->created_at, $entity->updated_at);

        // Updated timestamp is not set automatically
        $entity = new extended_sample_entity_updated_at();
        $entity->type = 1;
        $entity->params = 'params';
        $entity->parent_id = 0;
        $entity->save();

        $this->assertFalse(isset($entity->created_at));
        // Not updated yet
        $this->assertEmpty($entity->updated_at);

        $entity->params = 'params2';
        $entity->save();

        $this->assertGreaterThan(0, $entity->updated_at);
    }

    public function test_it_resets_dirty_attributes() {
        $record = $this->sample_record();

        $entity = new sample_entity($record, false);
        $entity->name = 'changed';

        $this->assertTrue($entity->changed());

        $entity->reset_dirty();

        $this->assertFalse($entity->changed());
    }

    public function test_it_sets_attribute_dirty() {
        $record = $this->create_sample_record();

        $entity = new sample_entity($record, false);
        $entity2 = new sample_entity($record, false);

        // Creating two entities, updating the second one, then marking a property as dirty on the first one and saving
        // it. Then fetching entity from db again and comparing to the very first one, they should be identical.

        $this->assertFalse($entity->changed());
        $entity->name = 'changed';
        $this->assertTrue($entity->changed());

        $entity2->name = 'something else';
        $this->assertNotEquals($entity->name, $entity2->name);

        $entity2->save();
        $entity2 = new sample_entity($record['id']);
        $this->assertEquals('something else', $entity2->name);

        $entity->save();

        $entity2 = new sample_entity($record['id']);
        $this->assertNotEquals('something else', $entity2->name);
        $this->assertEquals('changed', $entity2->name);
    }

    public function test_it_creates_new_entity() {
        $this->create_table();

        $record = $this->sample_record();

        $this->assertCount(0, $this->db()->get_records($this->table_name));

        $entity = (new sample_entity($record))
            ->create();

        $record['id'] = $entity->id;

        $this->assertCount(1, $this->db()->get_records($this->table_name));
        $this->assertEquals($record, (array) $this->db()->get_record($this->table_name, ['id' => $entity->id]));
    }

    public function test_it_can_not_create_a_entity_when_it_already_exists_from_array() {
        $record = $this->create_sample_record();

        $this->assertCount(1, $this->db()->get_records($this->table_name));

        $entity = new sample_entity($record, false);

        $this->assertCount(1, $this->db()->get_records($this->table_name));

        $this->expectException('coding_exception');

        $entity->create();
    }

    public function test_it_can_not_create_a_entity_when_it_already_exists_from_db() {
        $record = $this->create_sample_record();

        $this->assertCount(1, $this->db()->get_records($this->table_name));

        $entity = new sample_entity($record['id']);

        $this->assertCount(1, $this->db()->get_records($this->table_name));

        $this->expectException('coding_exception');

        $entity->create();
    }

    public function test_it_can_not_update_entity_that_does_not_exist() {
        $record = $this->sample_record();

        $entity = new sample_entity($record, false);

        $this->expectException('coding_exception');

        $entity->update();
    }

    public function test_it_saves_entity_when_it_already_exists() {
        $record = $this->create_sample_record();

        $this->assertCount(1, $this->db()->get_records($this->table_name));

        // Saving entity updates the record in the db
        $entity = (new sample_entity($record, false))
            ->set_attribute('type', 5)
            ->save();

        $this->assertNotEquals($record['type'], 5);

        $record['type'] = 5;

        $this->assertCount(1, $this->db()->get_records($this->table_name));

        $record = $this->db()->get_record($this->table_name, ['id' => $entity->id]);
        $this->assertEquals($entity->to_array(), (array) $record);
    }

    public function test_it_saves_entity_when_it_does_not_exists() {
        $this->create_table();
        $record = $this->sample_record();

        $this->assertCount(0, $this->db()->get_records($this->table_name));

        // Saving entity updates the record in the db
        $entity = new sample_entity($record, false);

        $entity->save();
        $record['id'] = $entity->id;

        $this->assertCount(1, $this->db()->get_records($this->table_name));
        $this->assertEquals($record, (array) $this->db()->get_record($this->table_name, ['id' => $entity->id]));
    }

    public function test_it_updates_entity() {
        $record = $this->create_sample_record();

        $entity = new sample_entity($record['id']);

        $entity->name = 'New name';

        $this->assertSame($entity, $entity->update());

        $this->assertNotEquals($record['name'], $entity->name);

        $same_entity = new sample_entity($record['id']);

        $this->assertEquals($entity->name, $same_entity->name);
    }

    public function test_it_refreshes_entity() {
        $record = $this->create_sample_record();

        $entity = new sample_entity($record['id']);
        $same_entity = new sample_entity($record['id']);

        $this->assertEquals($same_entity->created_at, $entity->created_at);

        $same_entity->created_at = 123;

        $this->assertNotEquals($same_entity->created_at, $entity->created_at);

        $same_entity->save();
        $entity->refresh();

        $this->assertEquals($same_entity->created_at, $entity->created_at);
    }

    public function test_it_deletes_entity() {
        $record = $this->create_sample_record();
        $entity = new sample_entity($record, false);

        $id1 = $entity->id;
        $this->assertFalse($entity->deleted());
        $this->assertTrue($entity->exists());

        // Create a second record
        $record = $this->create_sample_record();
        $entity2 = new sample_entity($record, false);

        $this->assertFalse($entity2->deleted());
        $this->assertTrue($entity2->exists());

        $id2 = $entity2->id;

        $this->assertCount(2, $this->db()->get_records($this->table_name));
        $this->assertTrue($this->db()->record_exists($this->table_name, ['id' => $id1]));
        $this->assertTrue($this->db()->record_exists($this->table_name, ['id' => $id2]));

        $entity->delete();

        // entity was successfully deleted
        $this->assertCount(1, $this->db()->get_records($this->table_name));

        $this->assertNull($entity->id);
        $this->assertTrue($entity->deleted());
        $this->assertFalse($entity->exists());
        $this->assertFalse($this->db()->record_exists($this->table_name, ['id' => $id1]));

        // Second record is untouched
        $this->assertFalse($entity2->deleted());
        $this->assertTrue($entity2->exists());
        $this->assertTrue($this->db()->record_exists($this->table_name, ['id' => $id2]));
    }

    public function test_delete_unsaved_entity() {
        $this->create_table();
        $record = $this->sample_record();

        $entity = new sample_entity($record, false);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Cannot delete entity without id, entity that does not exist or already deleted.');

        $entity->delete();
    }

    public function test_save_on_deleted_entity() {
        $record = $this->create_sample_record();

        // Saving entity updates the record in the db
        $entity = new sample_entity($record, false);

        $entity->delete();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('You can not save a entity that was deleted.');

        $entity->save();
    }

    public function test_update_on_deleted_entity() {
        $record = $this->create_sample_record();
        $entity = new sample_entity($record, false);

        $entity->delete();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('You can not update a entity that does not exist yet or was deleted.');

        $entity->update();
    }

    public function test_create_on_deleted_entity() {
        $record = $this->create_sample_record();
        $entity = new sample_entity($record, false);

        $id1 = $entity->id;

        $this->assertTrue($this->db()->record_exists($this->table_name, ['id' => $id1]));

        $entity->delete();

        $this->assertTrue($entity->deleted());
        $this->assertFalse($this->db()->record_exists($this->table_name, ['id' => $id1]));

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Id on this entity has already been set and cannot be set again.');
        $entity->create();

        // TODO Should recreating a deleted entity be possible?
    }

    public function test_refresh_unsaved_entity() {
        $record = $this->sample_record();
        $entity = new sample_entity($record, false);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Cannot refresh an entity without id.');

        $entity->refresh();
    }

    public function test_refresh() {
        $record = $this->create_sample_record();
        $entity = new sample_entity($record, false);

        $old_type = $entity->name;
        $entity->name = 'changed';

        $entity2 = new sample_entity($entity->id, false);
        $entity2->name = 'changed elsewhere';
        $entity2->save();

        $this->assertNotEquals($old_type, $entity->name);

        $entity->refresh();

        $this->assertEquals($entity2->name, $entity->name);
        $this->assertFalse($entity->changed());
    }

    public function test_refresh_deleted_entity() {
        $record = $this->create_sample_record();
        $entity = new sample_entity($record, false);

        $entity2 = new sample_entity($entity->id, false);
        $entity2->delete();

        $this->expectException(record_not_found_exception::class);
        $entity->refresh();
    }

    public function test_validate_entity_attributes_on_constructor() {
        $attributes = [
            'firstname' => 'Bob',
            'lastname' => 'Jones'
        ];
        $user = new class($attributes) extends entity
        {
            public const TABLE = 'user';
        };
        $this->assertEquals('Bob', $user->firstname);
        $this->assertEquals('Jones', $user->lastname);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Invalid attribute 'blablabla' passed to the entity");

        $attributes['blablabla'] = 123;
        new class($attributes) extends entity
        {
            public const TABLE = 'user';
        };
    }

    public function test_validate_entity_attributes_on_setter() {
        $attributes = [
            'firstname' => 'Bob',
            'lastname' => 'Jones'
        ];
        $user = new class($attributes) extends entity
        {
            public const TABLE = 'user';
        };
        $this->assertEquals('Bob', $user->firstname);
        $this->assertEquals('Jones', $user->lastname);

        // Make sure you can change normal attributes
        $user->firstname = 'Robert';
        $user->email =  'myemailaddress@example.com';

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Invalid attribute 'foobar' passed to the entity");

        $user->foobar = 'mygod';
    }

    public function test_it_cat_tell_you_whether_an_attribute_exists() {
        $attributes = [
            'firstname' => 'Bob',
            'lastname' => 'Jones'
        ];
        $user = new class($attributes) extends entity
        {
            public const TABLE = 'user';

            public function get_my_existing_attribute_attribute() {
                return 5;
            }
        };

        $this->assertTrue($user->has_attribute('firstname'));
        $this->assertTrue($user->has_attribute('email'));
        $this->assertFalse($user->has_attribute('foobar'));
        $this->assertTrue($user->has_attribute('my_existing_attribute'));
    }

    public function test_unknown_attribute_access_triggers_debug_message() {
        $record = $this->create_sample_record();
        $entity = new sample_entity($record, false);

        $test = $entity->foodoesnotexist;
        $this->assertNull($test);

        $this->assertDebuggingCalled("Unknown attribute 'foodoesnotexist' of entity ".sample_entity::class);
    }

    public function test_relate_throws_exception_when_not_defined_on_entity() {
        $entity = new extended_sample_entity_defined_relations();
        $related_entity = new sample_entity();
        $this->expectException(coding_exception::class);
        $entity->relate('random_relation', $related_entity);
    }

    public function test_relate_works_when_defined_on_entity() {
        $entity = new extended_sample_entity_defined_relations();
        $related_entity = new sample_entity();
        $entity->relate('sample_relation', $related_entity);
        $this->assertInstanceOf(sample_entity::class, $entity->sample_relation);
    }

    public function test_entities_namespace_does_not_exist() {

        $entities = core_component::get_namespace_classes('entities', entity::class);
        $this->assertEmpty($entities, 'entities namespace is not allowed for entity::class use entity namespace rather.');
    }
}

/**
 * Class sample_entity used for testing a entity
 */
class extended_sample_entity_created_at_custom extends sample_entity {

    public const CREATED_TIMESTAMP = 'params';
    public const UPDATED_TIMESTAMP = '';
}

/**
 * Class sample_entity used for testing a entity
 */
class extended_sample_entity_updated_at_custom extends sample_entity {

    public const CREATED_TIMESTAMP = '';
    public const UPDATED_TIMESTAMP = 'params';
    public const SET_UPDATED_WHEN_CREATED = true;
}

/**
 * Class sample_entity used for testing a entity
 *
 * @property int $updated_at
 */
class extended_sample_entity_updated_at extends sample_entity {

    public const CREATED_TIMESTAMP = '';
    public const UPDATED_TIMESTAMP = 'updated_at';
}

/**
 * Class sample_entity used for testing a entity
 *
 * @property int $created_at
 * @property int $updated_at
 */
class extended_sample_entity_created_updated_at extends sample_entity {

    public const CREATED_TIMESTAMP = 'created_at';
    public const UPDATED_TIMESTAMP = 'updated_at';
}

/**
 * Class sample_entity used for testing a entity
 *
 * @property int $created_at
 * @property int $updated_at
 */
class extended_sample_entity_created_updated_at_when_created extends sample_entity {

    public const CREATED_TIMESTAMP = 'created_at';
    public const UPDATED_TIMESTAMP = 'updated_at';
    public const SET_UPDATED_WHEN_CREATED = true;
}

/**
 * Class sample_entity used for testing a entity
 *
 * @property int $updated_at
 */
class extended_sample_entity_updated_at_when_created extends sample_entity {

    public const CREATED_TIMESTAMP = '';
    public const UPDATED_TIMESTAMP = 'updated_at';
    public const SET_UPDATED_WHEN_CREATED = true;
}

/**
 * Class extended_sample_entity_defined_relations used for testing
 * managing relation on entity.
 */
class extended_sample_entity_defined_relations extends sample_entity {

    public function sample_relation(): has_one {
        return $this->has_one(sample_entity::class, 'sample_id');
    }
}