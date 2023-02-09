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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/tests/orm_entity_relation_testcase.php');

/**
 * Class core_orm_relation_belongs_to_test
 *
 * @package core
 * @group orm
 */
class core_orm_relation_belongs_to_test extends orm_entity_relation_testcase {

    public function test_it_eager_loads_belongs_to() {
        $records = $this->create_sample_records();

        $parents = array_combine(
            array_column($records[sample_parent_entity::class], 'id'),
            $records[sample_parent_entity::class]
        );

        $collection = sample_child_entity::repository()
            ->with('parent')
            ->order_by('id')
            ->where('parent_id', '>', 0)
            ->get();

        $queries_count = $this->db()->perf_get_reads();

        foreach ($collection as $item) {
            $this->assertInstanceOf(sample_parent_entity::class, $item->parent);
            $this->assertEquals($item->parent->name, $parents[$item->parent_id]['name']);
        }

        // Let's make sure that children were eager loaded and no additional query was executed...
        $this->assertEquals(
            $queries_count, $this->db()->perf_get_reads(), 'Eager loading wasn\'t so eager...'
        );
    }

    public function test_it_lazy_loads_belongs_to() {
        $records = $this->create_sample_records();

        $child = sample_child_entity::repository()
            ->order_by('id')
            ->first();

        $parents = array_combine(
            array_column($records[sample_parent_entity::class], 'id'),
            $records[sample_parent_entity::class]
        );

        $this->assertFalse($child->relation_loaded('parent'));

        $parent = $child->parent;

        $queries_count = $this->db()->perf_get_reads();

        // Let's make sure that fetched related models get cached and will not trigger extra
        // database queries on subsequent calls.
        $this->assertSame($parent, $child->parent);
        $this->assertEquals($queries_count, $this->db()->perf_get_reads());

        // Let's assert that children have been fetched correctly
        $this->assertEquals($parent->id, $parents[$child->parent_id]['id']);
        $this->assertEquals($parent->name, $parents[$child->parent_id]['name']);
    }

    public function test_it_correctly_handles_empty_result() {
        $this->create_sample_records();

        // Let's make sure there are records.
        $this->assertGreaterThan(
            0, $this->db()->count_records(sample_child_entity::TABLE)
        );

        $empty = new sample_child_entity([]);

        $this->assertNull($empty->parent);
        $this->assertDebuggingCalled('Entity does not exist.');
        $this->assertCount(0, $empty->parent()->get());

        $queries_count_before = $this->db()->perf_get_reads();

        // Let's load the one that doesn't have any items in it.
        $entity = sample_child_entity::repository()
            ->where('name', 'The Big Unknown')
            ->with('parent')
            ->one();

        $queries_count_after = $this->db()->perf_get_reads();

        // No query for the relation got issued as it has a null value
        $this->assertEquals(1, $queries_count_after - $queries_count_before);

        $this->assertFalse($entity->relation_loaded('parent'));
        $this->assertNull($entity->parent);
        $this->assertEmpty($entity->parent()->one());

        $queries_count_before = $this->db()->perf_get_reads();

        // Now lazy load the relation and check that it does not trigger an additional query
        $parent = $entity->parent;
        $this->assertNull($parent);

        $queries_count_after = $this->db()->perf_get_reads();
        $this->assertEquals(0, $queries_count_after - $queries_count_before);
    }

    public function test_it_allows_to_disassociate_the_related_entity() {
        $this->create_sample_records();

        $entity = sample_child_entity::repository()
            ->order_by('id')
            ->first();

        $this->assertNotNull($entity->parent_id);

        $entity->parent()->disassociate();
        $this->assertNull($entity->parent_id);
    }

    public function test_it_allows_to_associate_the_related_entity() {
        $this->create_sample_records();

        $parent = sample_parent_entity::repository()
            ->order_by('id')
            ->first();

        $child = new sample_child_entity([
            'name' => 'Sample child',
            'type' => 0,
        ]);

        $this->assertNull($child->parent_id);

        $child->parent()->associate($parent);

        $this->assertNotNull($child->parent_id);

        $child->save();

        $this->assertEquals($parent, $child->parent);
    }

    public function test_it_does_sanity_check_before_associating_an_entity() {
        try {
            $parent = new sample_parent_entity(['id' => 1], false, false);

            $child = new sample_child_entity([
                'parent_id' => null,
            ], false); // Workaround this stupid attribute validation
            $child->parent()->associate($parent);
            $this->fail('Coding exception should have been thrown');
        } catch (\coding_exception $exception) {
            $this->assertStringContainsString('Entity to associate must exist and its key must not be null!', $exception->getMessage());
        }

        try {
            $parent = new sample_parent_entity(['id' => null], false, true);

            $child = new sample_child_entity([
                'parent_id' => null,
            ], false); // Workaround this stupid attribute validation
            $child->parent()->associate($parent);
            $this->fail('Coding exception should have been thrown');
        } catch (\coding_exception $exception) {
            $this->assertStringContainsString('Entity to associate must exist and its key must not be null!', $exception->getMessage());
        }
    }

    public function test_it_can_delete_related_model() {
        $this->create_sample_records();

        $passport = sample_passport_entity::repository()
            ->order_by('id')
            ->with('parent')
            ->first();

        $total = sample_parent_entity::repository()
            ->count();

        $passport->parent()->delete();

        $this->assertEquals(
            $total - 1,
            sample_parent_entity::repository()->count()
        );

        $child = sample_parent_entity::repository()
            ->where('id', $passport->parent_id)
            ->one();

        $this->assertNull($child);
    }

    public function test_it_can_update_related_model() {
        $this->create_sample_records();

        $passport = sample_passport_entity::repository()
            ->order_by('id')
            ->with('parent')
            ->first();

        $this->assertEmpty(
            sample_parent_entity::repository()
                ->where('updated_at', '12345')
                ->get()
        );

        $passport->parent()
            ->update([
                'updated_at' => 12345
            ]);

        $this->assertEquals(
            ['Calculators'],
            sample_parent_entity::repository()
                ->where('updated_at', 12345)
                ->order_by('name')
                ->get()
                ->pluck('name')
        );
    }

    public function test_it_doesnt_update_related_model_on_null_key() {
        $this->create_sample_records();

        $passport = new sample_passport_entity();

        $this->assertEmpty(
            sample_parent_entity::repository()
                ->where('updated_at', 12345)
                ->get()
        );

        $passport->parent()
            ->update([
                'updated_at' => 12345
            ]);

        $this->assertEmpty(
            sample_parent_entity::repository()
                ->where('updated_at', 12345)
                ->get()
        );
    }

    public function test_it_doesnt_delete_related_model_on_null_key() {
        $this->create_sample_records();

        $passport = new sample_passport_entity();

        $count = sample_parent_entity::repository()->count();

        $passport->parent()->delete();

        $this->assertEquals($count, sample_parent_entity::repository()->count());
    }

    public function test_it_does_not_allow_saving_related_models() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('This relation does not allow saving models...');

        (new sample_passport_entity(['parent_id' => null], false))->parent()->save([]);
    }

}
