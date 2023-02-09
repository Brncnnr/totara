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
 * Class core_orm_relation_has_one_test
 *
 * @package core
 * @group orm
 */
class core_orm_relation_has_one_test extends orm_entity_relation_testcase {

    public function test_it_eager_loads_has_one() {
        $records = $this->create_sample_records();

        $passports = array_combine(
            array_column($records[sample_passport_entity::class], 'id'),
            $records[sample_passport_entity::class]
        );

        $collection = sample_parent_entity::repository()
            ->with('passport')
            ->order_by('id')
            ->get();

        $queries_count = $this->db()->perf_get_reads();

        foreach ($collection as $item) {
            if ($item->name === 'Bluetooth speakers') {
                // This one doesn't have a related model...
                $this->assertNull($item->passport);
                continue;
            }

            $this->assertInstanceOf(sample_passport_entity::class, $item->passport);
            $this->assertEquals($item->passport->name, $passports[$item->passport->id]['name']);
            $this->assertEquals($item->id, $item->passport->parent_id);
        }

        // Let's make sure that children were eager loaded and no additional query was executed...
        $this->assertEquals(
            $queries_count,
            $this->db()->perf_get_reads(),
            'Eager loading wasn\'t so eager...'
        );
    }

    public function test_it_lazy_loads_has_one() {
        $records = $this->create_sample_records();

        $parent = sample_parent_entity::repository()
            ->order_by('id')
            ->first();

        $passports = array_combine(
            array_column($records[sample_passport_entity::class], 'id'),
            $records[sample_passport_entity::class]
        );

        $this->assertFalse($parent->relation_loaded('passport'));

        $passport = $parent->passport;

        $queries_count = $this->db()->perf_get_reads();

        // Let's make sure that fetched related models get cached and will not trigger extra
        // database queries on subsequent calls.
        $this->assertSame($passport, $parent->passport);
        $this->assertEquals($queries_count, $this->db()->perf_get_reads());

        // Let's assert that children have been fetched correctly
        $this->assertEquals($passport->name, $passports[$passport->id]['name']);
    }

    public function test_it_correctly_handles_empty_result() {
        $this->create_sample_records();

        // Let's make sure there are records.
        $this->assertGreaterThan(
            0, $this->db()->count_records(sample_passport_entity::TABLE)
        );

        $empty = new sample_parent_entity([]);

        $this->assertNull($empty->passport);
        $this->assertDebuggingCalled('Entity does not exist.');
        $this->assertCount(0, $empty->passport()->get());

        // Let's load the one that doesn't have any items in it.
        $entity = sample_parent_entity::repository()
            ->where('name', 'Bluetooth speakers')
            ->with('passport')
            ->one();

        $this->assertTrue($entity->relation_loaded('passport'));
        $this->assertNull($entity->passport);
        $this->assertEmpty($entity->passport()->one());
    }

    public function test_it_can_save_a_related_model() {
        $this->create_tables();

        $parent = new sample_parent_entity([
            'name' => 'Sample parent',
        ]);

        $parent->save();

        $parent->passport()->save(new sample_passport_entity(['name' => 'Quality passport']));

        $this->assertEquals(1, sample_passport_entity::repository()->count());

        $passport = sample_passport_entity::repository()
            ->one();

        $this->assertEquals($parent->id, $passport->parent_id);
        $this->assertEquals('Quality passport', $passport->name);
    }

    public function test_it_cannot_save_multiple_related_models() {
        $this->create_tables();

        $parent = new sample_parent_entity([
            'name' => 'Sample parent',
        ]);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Can not save more than one child for a has_one relation...');

        $parent->passport()->save([
            new sample_passport_entity(['name' => 'Quality passport']),
            new sample_passport_entity(['name' => 'Quality passport']),
        ]);
    }

    public function test_it_cannot_save_a_related_model_if_one_already_exists() {
        $this->create_tables();

        $parent = new sample_parent_entity([
            'name' => 'Sample parent',
        ]);

        $parent->save();

        $parent->passport()->save([
            new sample_passport_entity(['name' => 'Quality passport']),
        ]);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Can not save more than one child for a has_one relation...');

        $parent->passport()->save([
            new sample_passport_entity(['name' => 'Quality passport']),
        ]);
    }

    public function test_it_can_delete_related_model() {
        $this->create_sample_records();

        $parent = sample_parent_entity::repository()
            ->order_by('id')
            ->with('passport')
            ->first();

        $total = sample_passport_entity::repository()
            ->count();

        $parent->passport()->delete();

        $this->assertEquals(
            $total - 1,
            sample_passport_entity::repository()->count()
        );

        $child = sample_passport_entity::repository()
            ->where('parent_id', $parent->id)
            ->one();

        $this->assertNull($child);
    }

    public function test_it_can_update_related_model() {
        $this->create_sample_records();

        $parent = sample_parent_entity::repository()
            ->order_by('id')
            ->with('children')
            ->first();

        $this->assertEmpty(
            sample_passport_entity::repository()
                ->where('updated_at', '12345')
                ->get()
        );

        $parent->passport()
            ->update([
                'updated_at' => 12345
            ]);

        $this->assertEquals(
            ['Quality passport'],
            sample_passport_entity::repository()
                ->where('updated_at', 12345)
                ->order_by('name')
                ->get()
                ->pluck('name')
        );
    }

    public function test_it_doesnt_update_related_model_on_null_key() {
        $this->create_sample_records();

        $parent = new sample_parent_entity();

        $this->assertEmpty(
            sample_passport_entity::repository()
                ->where('updated_at', 12345)
                ->get()
        );

        $parent->passport()
            ->update([
                'updated_at' => 12345
            ]);

        $this->assertEmpty(
            sample_passport_entity::repository()
                ->where('updated_at', 12345)
                ->get()
        );
    }

    public function test_it_doesnt_delete_related_model_on_null_key() {
        $this->create_sample_records();

        $parent = new sample_parent_entity();

        $count = sample_passport_entity::repository()->count();

        $parent->passport()
            ->delete();

        $this->assertEquals($count, sample_passport_entity::repository()->count());
    }

}
