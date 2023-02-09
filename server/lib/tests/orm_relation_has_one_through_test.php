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

use core\entity\user;
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
class core_orm_relation_has_one_through_test extends orm_entity_relation_testcase {

    public function test_it_eager_loads_has_many_through() {
        $this->create_sample_records();

        $parents = sample_parent_entity::repository()
            ->where('id', [1, 2, 3]) // We select the subset of records 3 and 4 have siblings 5 doesn't
            ->with('a_sibling')
            ->get();

        // We should have 3 parents
        $this->assertCount(3, $parents);

        // Assert first
        $this->assertEquals('Red Mi Note 7', $parents->find('id', 1)->a_sibling->name);

        // Assert second
        $this->assertEquals('Red Mi Note 7', $parents->find('id', 2)->a_sibling->name);

        // Assert third
        $this->assertEquals('Apple iPhone XS', $parents->find('id', 3)->a_sibling->name);

    }

    public function test_it_lazy_loads_has_many_through() {
        $this->create_sample_records();

        $parent = new sample_parent_entity(4);

        $this->assertFalse($parent->relation_loaded('a_sibling'));

        $query_count = $this->db()->perf_get_reads();

        $this->assertInstanceOf(sample_sibling_entity::class, $parent->a_sibling);
        $this->assertEquals('Apple iPhone XR', $parent->a_sibling->name);

        // Let's check that we are not executing unnecessary queries
        $this->assertEquals($query_count + 1, $this->db()->perf_get_reads());
    }

    public function test_it_correctly_handles_empty_sets() {
        $this->create_sample_records();

        // Let's make sure there are records.
        $this->assertGreaterThan(
            0, $this->db()->count_records(sample_sibling_entity::TABLE)
        );
        $this->assertGreaterThan(
            0, $this->db()->count_records(sample_pivot_entity::TABLE)
        );

        $empty = new sample_parent_entity([]);

        $this->assertNull($empty->a_sibling);
        $this->assertDebuggingCalled('Entity does not exist.');
        $this->assertCount(0, $empty->a_sibling()->get());

        // Let's make sure we have an item without related things
        $entity = sample_parent_entity::repository()
            ->where('name', 'Bluetooth speakers')
            ->with('a_sibling')
            ->one();

        $this->assertTrue($entity->relation_loaded('a_sibling'));
        $this->assertEmpty($entity->a_sibling()->get());
    }

    public function test_it_handles_lazy_dynamic_conditions() {
        $this->create_sample_records();

        // Let's load the one that doesn't have any items in it.
        $entity = sample_parent_entity::repository()
            ->where('name', 'Calculators')
            ->one();

        $this->assertEquals(
            'Apple iPhone XR',
            $entity->a_sibling()->where('name', 'Apple iPhone XR')->one(true)->name
        );
    }

    public function test_it_handles_eager_dynamic_conditions() {
        $this->create_sample_records();

        // Let's load the one that doesn't have any items in it.
        $parents = sample_parent_entity::repository()
            ->with([
                'a_sibling' => function (repository $repository) {
                    $repository->where('type', '>=', 1);
                }
            ])
            ->get();

        $this->assertEqualsCanonicalizing(
            [
                'Red Mi Note 7',
                'Red Mi Note 7',
                'Apple iPhone XR',
            ],
            collection::new($parents->pluck('a_sibling'))->pluck('name')
        );
    }

    public function test_it_cant_filter_selected_columns_on_eager_load() {
        $this->create_sample_records();

        // Let's load the one that doesn't have any items in it.
        $entity = sample_parent_entity::repository()
            ->with([
                'a_sibling:name,type' => function (repository $repository) {
                    $repository->where('type', '>', 1);
                }
            ])
            ->where('name', 'Tablets')
            ->one();

        $this->assertDebuggingCalled('Specifying columns is not currently supported for has_many(one)_through relations');

        // Let's make sure there is no extra queries again...
        $queries_count = $this->db()->perf_get_reads();

        // Id will be automatically prepended if not specified.
        $this->assertGreaterThan(0, $entity->a_sibling->id);
        $this->assertEquals('Red Mi Note 7', $entity->a_sibling->name);
        $this->assertEquals('3', $entity->a_sibling->type);
        $this->assertEquals('1', $entity->a_sibling->child_id);

        $this->assertEquals($queries_count, $this->db()->perf_get_reads());
    }

    public function test_it_cant_save_related_models() {
        $this->create_tables();

        $parent = new sample_parent_entity([
            'name' => 'Sample parent',
            'id' => 0,
        ]);

        $parent->save();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('This relation does not allow saving models...');

        $parent->a_sibling()->save(new sample_sibling_entity(['name' => 'Sample child']));
    }

    public function test_it_can_bulk_delete_related_models() {
        $this->create_sample_records();

        $parent = sample_parent_entity::repository()
            ->where('name', 'Tablets')
            ->with('a_sibling')
            ->order_by('id')
            ->first();

        $total = sample_sibling_entity::repository()
            ->count();

        $parent->a_sibling()
            ->delete();

        $this->assertEquals(
            $total - 1,
            sample_sibling_entity::repository()->count()
        );

        $this->assertNull($parent->a_sibling()->order_by('id')->first());
    }

    public function test_it_can_bulk_update_related_models() {
        $this->create_sample_records();

        $parent = sample_parent_entity::repository()
            ->order_by('id')
            ->where('name', 'Calculators')
            ->with('a_sibling')
            ->first();

        $this->assertEmpty(
            sample_sibling_entity::repository()
                ->where('type', '96')
                ->get()
        );

        $parent->a_sibling()
            ->update([
                'type' => 96
            ]);

        $this->assertEquals(
            'Apple iPhone XR',
            sample_sibling_entity::repository()
                ->where('type', 96)
                ->one()->name
        );
    }

    public function test_it_doesnt_update_related_model_on_null_key() {
        $this->create_sample_records();

        $parent = new sample_parent_entity();

        $this->assertEmpty(
            sample_sibling_entity::repository()
                ->where('type', '96')
                ->get()
        );

        $parent->a_sibling()
            ->update([
                'type' => 96
            ]);

        $this->assertEmpty(
            sample_sibling_entity::repository()
                ->where('type', '96')
                ->get()
        );
    }

    public function test_it_doesnt_delete_related_model_on_null_key() {
        $this->create_sample_records();

        $parent = new sample_parent_entity();

        $count = sample_sibling_entity::repository()->count();

        $parent->a_sibling()->delete();

        $this->assertEquals($count, sample_sibling_entity::repository()->count());
    }

    public function test_querying_related_table_with_reserved_word_does_not_fail() {
        $this->create_sample_records();

        /** @var sample_parent_entity $parent */
        $parent = sample_parent_entity::repository()
            ->order_by('id')
            ->first();

        $user = $parent->reserved_word_relation;
        $this->assertInstanceOf(user::class, $user);
        $this->assertEquals(1, $user->id);

        /** @var sample_parent_entity $parent */
        $parent = sample_parent_entity::repository()
            ->order_by('id')
            ->with('reserved_word_relation')
            ->first();

        $user = $parent->reserved_word_relation;
        $this->assertInstanceOf(user::class, $user);
        $this->assertEquals(1, $user->id);
    }

}
