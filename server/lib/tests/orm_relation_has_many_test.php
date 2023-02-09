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

use core\orm\collection;
use core\orm\entity\repository;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/tests/orm_entity_relation_testcase.php');

/**
 * Class core_orm_relation_has_many_test
 *
 * @package core
 * @group orm
 */
class core_orm_relation_has_many_test extends orm_entity_relation_testcase {

    public function test_it_eager_loads_has_many() {
        $records = $this->create_sample_records();

        $children = $records[sample_child_entity::class];

        $collection = sample_parent_entity::repository()
            ->with('children')
            ->order_by('id')
            ->get();

        $queries_count = $this->db()->perf_get_reads();

        foreach ($collection as $item) {
            // Let's get all the children items for this record.
            $children_items = array_filter(
                $children, function ($ci) use ($item) {
                    return $ci['parent_id'] === $item->id;
                }
            );

            // After various normalization attempts as well as trying to use assertEqualsCanonicalizing (and failing)
            // I'll stop at just comparing IDs it should be good enough.
            $this->assertEquals($item->children->pluck('id'), array_column($children_items, 'id'));
            $this->assertInstanceOf(collection::class, $item->children);
        }

        // Let's make sure that children were eager loaded and no additional query was executed...
        $this->assertEquals(
            $queries_count, $this->db()->perf_get_reads(), 'Eager loading wasn\'t so eager...'
        );
    }

    public function test_it_lazy_loads_has_many() {
        $records = $this->create_sample_records();

        $result = sample_parent_entity::repository()
            ->order_by('id')
            ->first();

        $expected_children = (new collection($records[sample_child_entity::class]))
            ->filter('parent_id', $result->id, true)->to_array();

        $this->assertFalse($result->relation_loaded('children'));

        $children = $result->children;

        $queries_count = $this->db()->perf_get_reads();

        // Let's make sure that fetched related models get cached and will not trigger extra
        // database queries on subsequent calls.
        $this->assertSame($children, $result->children);
        $this->assertEquals($queries_count, $this->db()->perf_get_reads());

        // Let's assert that children have been fetched correctly
        $this->assertEquals($children->pluck('name'), array_column($expected_children, 'name'));
    }

    public function test_it_correctly_handles_empty_sets() {
        $this->create_sample_records();

        // Let's make sure there are records.
        $this->assertGreaterThan(
            0, $this->db()->count_records(sample_child_entity::TABLE)
        );

        $empty = new sample_parent_entity([]);

        $this->assertNull($empty->children);
        $this->assertDebuggingCalled('Entity does not exist.');
        $this->assertCount(0, $empty->children()->get());

        // Let's load the one that doesn't have any items in it.
        $entity = sample_parent_entity::repository()
            ->where('name', 'Bluetooth speakers')
            ->with('children')
            ->one();

        $this->assertFalse($entity->relation_loaded('children'));
        $this->assertEmpty($entity->children->all());
        $this->assertCount(0, $entity->children()->get());
    }

    public function test_it_handles_lazy_dynamic_conditions() {
        $records = $this->create_sample_records();

        // Let's load the one that doesn't have any items in it.
        $entity = sample_parent_entity::repository()
            ->where('name', 'Calculators')
            ->one();

        $this->assertEquals(
            'Modern scientific calculator',
            $entity->children()->where('type', 7)->one()->name
        );
    }

    public function test_it_handles_eager_dynamic_conditions() {
        $this->create_sample_records();

        // Let's load the one that doesn't have any items in it.
        $entity = sample_parent_entity::repository()
            ->with([
                'children' => function (repository $repository) {
                    $repository->where('created_at', '633679200');
                }
            ])
            ->where('name', 'Personal Computers')
            ->one();

        $this->assertEquals(
            'Apple iMac PRO',
            $entity->children->first()->name
        );
    }

    public function test_it_can_filter_selected_columns_on_eager_load() {
        $this->create_sample_records();

        // Let's load the one that doesn't have any items in it.
        $entity = sample_parent_entity::repository()
            ->with([
                'children:name,created_at' => function (repository $repository) {
                    $repository->where('created_at', '946684799');
                }
            ])
            ->where('name', 'Mobile phones')
            ->one();

        // Let's make sure there is no extra queries again...
        $queries_count = $this->db()->perf_get_reads();

        // Id will be automatically prepended if not specified.
        $this->assertGreaterThan(0, $entity->children->first()->id);
        $this->assertEquals('Samsung Galaxy Note', $entity->children->first()->name);
        $this->assertEquals('946684799', $entity->children->first()->created_at);

        // Key will be automatically appended if not specified.
        $this->assertEquals($entity->id, $entity->children->first()->parent_id);

        // Let's check that fields we didn't want to select are not present.
        $this->assertFalse(isset($entity->children->first()->description));
        $this->assertFalse(isset($entity->children->first()->updated_at));


        $this->assertEquals($queries_count, $this->db()->perf_get_reads());
    }

    public function test_it_can_save_related_models() {
        $this->create_tables();

        $parent = new sample_parent_entity([
            'name' => 'Sample parent',
        ]);

        $parent->save();

        $parent->children()->save(new sample_child_entity(['name' => 'Sample child']));

        $this->assertEquals(1, sample_child_entity::repository()->count());

        $child = sample_child_entity::repository()
            ->one();

        $this->assertEquals($parent->id, $child->parent_id);
        $this->assertEquals('Sample child', $child->name);
    }

    public function test_it_can_bulk_delete_related_models() {
        $this->create_sample_records();

        $parent = sample_parent_entity::repository()
            ->order_by('id')
            ->with('children')
            ->first();

        $total = sample_child_entity::repository()
            ->count();

        $parent->children()
            ->where('name', '!=', 'Samsung Galaxy 6')
            ->delete();

        $this->assertEquals(
            $total - 2,
            sample_child_entity::repository()->count()
        );

        $child = sample_child_entity::repository()
            ->where('parent_id', $parent->id)
            ->one();

        $this->assertNotNull($child);
        $this->assertEquals('Samsung Galaxy 6', $child->name);
    }

    public function test_it_can_bulk_update_related_models() {
        $this->create_sample_records();

        $parent = sample_parent_entity::repository()
            ->order_by('id')
            ->with('children')
            ->first();

        $this->assertEmpty(
            sample_child_entity::repository()
                ->where('type', '96')
                ->get()
        );

        $parent->children()
            ->where('name', '!=', 'Samsung Galaxy 6')
            ->update([
                'type' => 96
            ]);

        $this->assertEquals(
            ['Apple iPhone X', 'Samsung Galaxy Note'],
            sample_child_entity::repository()
                ->where('type', 96)
                ->order_by('name')
                ->get()
                ->pluck('name')
        );
    }

    public function test_it_doesnt_update_related_model_on_null_key() {
        $this->create_sample_records();

        $parent = new sample_parent_entity();

        $this->assertEmpty(
            sample_child_entity::repository()
                ->where('type', '96')
                ->get()
        );

        $parent->children()
            ->update([
                'type' => 96
            ]);

        $this->assertEmpty(
            sample_child_entity::repository()
                ->where('type', '96')
                ->get()
        );
    }

    public function test_it_doesnt_delete_related_model_on_null_key() {
        $this->create_sample_records();

        $parent = new sample_parent_entity();

        $count = sample_child_entity::repository()->count();

        $parent->children()
            ->delete();

        $this->assertEquals($count, sample_child_entity::repository()->count());
    }

    public function test_it_allows_saving_related_models_only_when_parent_exists() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Your parent entity must be defined and exist...');

        (new sample_parent_entity())->children()->save(new sample_child_entity());
    }

    public function test_it_must_have_key_attribute_set_to_save_children() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Entity must have \'id\' attribute set and be other than null');

        (new sample_parent_entity([], false, true))
            ->children()
            ->save(new sample_child_entity(['parent_id' => null], false));
    }

    public function test_it_must_have_all_related_entities_to_save_matching_relation_class() {
        $this->create_tables();

        $parent = new sample_parent_entity([
            'name' => 'Sample parent',
        ]);

        $parent->save();
        $parent->refresh();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Related model must be an instance of '" . sample_child_entity::class . "'");

        $children = [
            new sample_child_entity(['name' => 'Sample child']),
            new sample_child_entity(['name' => 'Sample child']),
            new sample_passport_entity(['name' => 'Sample child']),
        ];

        $parent->children()->save($children);
    }

}