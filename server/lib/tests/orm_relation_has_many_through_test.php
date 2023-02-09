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
class core_orm_relation_has_many_through_test extends orm_entity_relation_testcase {

    public function test_it_eager_loads_has_many_through() {
        $this->create_sample_records();

        $parents = sample_parent_entity::repository()
            ->where('id', [3, 4, 5]) // We select the subset of records 3 and 4 have siblings 5 doesn't
            ->with('siblings')
            ->get();

        // We should have 3 parents
        $this->assertCount(3, $parents);

        // Assert siblings for this entry
        $this->assertCount(2, $siblings = $parents->find('id', 3)->siblings);
        $this->assertEqualsCanonicalizing(['Apple Mac Pro', 'Hp Workstation'], $siblings->pluck('name'));

        // Assert siblings for this entry
        $this->assertCount(3, $siblings = $parents->find('id', 4)->siblings);
        $this->assertEqualsCanonicalizing(
            [
                'Electronica vintage soviet calculator',
                'Commodore vintage calculator',
                'Sinclair Cambridge Programmable vintage calculator',
            ],
            $siblings->pluck('name')
        );

        // Assert siblings for this entry
        $this->assertCount(0, $parents->find('id', 5)->siblings);
    }

    public function test_it_lazy_loads_has_many_through() {
        $this->create_sample_records();

        $parent = new sample_parent_entity(4);

        $this->assertFalse($parent->relation_loaded('siblings'));

        $query_count = $this->db()->perf_get_reads();

        $this->assertCount(3, $parent->siblings);

        $this->assertEqualsCanonicalizing(
            [
                'Electronica vintage soviet calculator',
                'Commodore vintage calculator',
                'Sinclair Cambridge Programmable vintage calculator',
            ],
            $parent->siblings->pluck('name')
        );

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
            0, $this->db()->count_records(sample_child_entity::TABLE)
        );

        $empty = new sample_parent_entity([]);

        $this->assertNull($empty->children);
        $this->assertDebuggingCalled('Entity does not exist.');
        $this->assertCount(0, $empty->children()->get());

        $this->assertNull($empty->siblings);
        $this->assertDebuggingCalled('Entity does not exist.');
        $this->assertCount(0, $empty->siblings()->get());

        // Let's make sure we have an item without related things
        $entity = sample_parent_entity::repository()
            ->where('name', 'Bluetooth speakers')
            ->with('children')
            ->with('siblings')
            ->one();

        $this->assertFalse($entity->relation_loaded('children'));
        $this->assertEmpty($entity->children->all());
        $this->assertCount(0, $entity->children()->get());

        $this->assertFalse($entity->relation_loaded('siblings'));
        $this->assertEmpty($entity->siblings->all());
        $this->assertCount(0, $entity->siblings()->get());
    }

    public function test_it_handles_lazy_dynamic_conditions() {
        $this->create_sample_records();

        // Let's load the one that doesn't have any items in it.
        $entity = sample_parent_entity::repository()
            ->where('name', 'Calculators')
            ->one();

        $this->assertEquals(
            'Sinclair Cambridge Programmable vintage calculator',
            $entity->siblings()->where('name', 'Sinclair Cambridge Programmable vintage calculator')->one(true)->name
        );
    }

    public function test_it_handles_eager_dynamic_conditions() {
        $this->create_sample_records();

        // Let's load the one that doesn't have any items in it.
        $entity = sample_parent_entity::repository()
            ->with([
                'siblings' => function (repository $repository) {
                    $repository->where('type', '<', 1);
                }
            ])
            ->where('name', 'Calculators')
            ->one();

        $this->assertEquals(
            'Commodore vintage calculator',
            $entity->siblings->first()->name
        );
    }

    public function test_it_cant_filter_selected_columns_on_eager_load() {
        $this->create_sample_records();

        // Let's load the one that doesn't have any items in it.
        $entity = sample_parent_entity::repository()
            ->with([
                'siblings:name,type' => function (repository $repository) {
                    $repository->where('type', '>', 1);
                }
            ])
            ->where('name', 'Calculators')
            ->one();

        $this->assertDebuggingCalled('Specifying columns is not currently supported for has_many(one)_through relations');

        // Let's make sure there is no extra queries again...
        $queries_count = $this->db()->perf_get_reads();

        // Id will be automatically prepended if not specified.
        $this->assertGreaterThan(0, $entity->siblings->first()->id);
        $this->assertEquals('Electronica vintage soviet calculator', $entity->siblings->first()->name);
        $this->assertEquals('2', $entity->siblings->first()->type);
        $this->assertEquals('10', $entity->siblings->first()->child_id);

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

        $parent->siblings()->save(new sample_sibling_entity(['name' => 'Sample child']));
    }

    public function test_it_can_bulk_delete_related_models() {
        $this->create_sample_records();

        $parent = sample_parent_entity::repository()
            ->where('name', 'Calculators')
            ->with('siblings')
            ->order_by('id')
            ->first();

        $total = sample_sibling_entity::repository()
            ->count();

        $parent->siblings()
            ->where('name', '!=', 'Electronica vintage soviet calculator')
            ->delete();

        $this->assertEquals(
            $total - 2,
            sample_sibling_entity::repository()->count()
        );

        $sibling = sample_sibling_entity::repository()
            ->join([sample_child_entity::TABLE, 'children'], 'child_id', 'id')
            ->where('children.parent_id', $parent->id)
            ->one();

        $this->assertNotNull($sibling);
        $this->assertEquals('Electronica vintage soviet calculator', $sibling->name);
    }

    public function test_it_can_bulk_update_related_models() {
        $this->create_sample_records();

        $parent = sample_parent_entity::repository()
            ->order_by('id')
            ->where('name', 'Calculators')
            ->with('siblings')
            ->first();

        $this->assertEmpty(
            sample_sibling_entity::repository()
                ->where('type', '96')
                ->get()
        );

        $parent->siblings()
            ->where('name', '!=', 'Commodore vintage calculator')
            ->update([
                'type' => 96
            ]);

        $this->assertEquals(
            ['Electronica vintage soviet calculator', 'Sinclair Cambridge Programmable vintage calculator'],
            sample_sibling_entity::repository()
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
            sample_sibling_entity::repository()
                ->where('type', '96')
                ->get()
        );

        $parent->siblings()
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

        $parent->siblings()->delete();

        $this->assertEquals($count, sample_sibling_entity::repository()->count());
    }

    public function test_querying_related_table_with_reserved_words_does_not_fail() {
        $this->create_sample_records();

        /** @var sample_parent_entity $parent */
        $parent = sample_parent_entity::repository()
            ->order_by('id')
            ->first();

        $users = $parent->reserved_word_relations;
        $this->assertInstanceOf(collection::class, $users);
        $user = $users->first();
        $this->assertInstanceOf(user::class, $user);
        $this->assertEquals(1, $user->id);

        /** @var sample_parent_entity $parent */
        $parent = sample_parent_entity::repository()
            ->order_by('id')
            ->with('reserved_word_relations')
            ->first();

        $users = $parent->reserved_word_relations;
        $this->assertInstanceOf(collection::class, $users);
        $user = $users->first();
        $this->assertInstanceOf(user::class, $user);
        $this->assertEquals(1, $user->id);
    }

}
