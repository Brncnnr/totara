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
 * @package core
 * @group orm
 */

use core\dml\sql;
use core\orm\query\builder;
use core\orm\query\field;
use core\orm\query\properties;
use core\orm\lazy_collection;
use core\orm\query\exceptions\record_not_found_exception;
use core\orm\query\sql\query;
use core\orm\query\sql\where;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/tests/orm_query_builder_base.php');

/**
 * Class core_orm_builder_testcase
 *
 * @package core
 * @group orm
 */
class core_orm_builder_testcase extends orm_query_builder_base {

    public function test_it_sets_alias() {
        $builder = new builder();

        $this->assertEmpty($builder->get_alias());
        $this->assertEmpty($builder->get_alias_sql());

        $builder->as('my_new_alias');

        $this->assertEquals('my_new_alias', $builder->get_alias());
        $this->assertEquals('"my_new_alias"', $builder->get_alias_sql());

        $builder->where_field('field', 'another_field');

        [$sql] = where::from_builder($builder)->build();

        $this->assertEquals('"my_new_alias".field = "my_new_alias".another_field', $sql);
    }

    public function test_it_sets_table() {
        $builder = new builder();
        $builder->from($this->table_name);
        $this->assertEquals($this->table_name, $builder->get_table());
    }

    public function test_it_has_create_for_factory_method() {
        $builder = builder::table($this->table_name);

        $this->assertInstanceOf(builder::class, $builder);
        $this->assertEquals($this->table_name, $builder->get_table());
    }

    public function test_it_finds_record_by_id() {
        $records = $this->create_sample_records();

        // Record exists
        $builder = builder::table($this->table_name);
        $id = $records[3]['id'];
        $record = $builder->find($id);
        $this->assertEquals((object) $records[3], $record);

        // Record does not exist
        $this->assertNull(builder::table($this->table_name)->find(-3));
    }

    public function test_it_returns_first_record_from_results() {
        $records = $this->create_sample_records();

        // Record exists
        $builder = builder::table($this->table_name);
        $record = $builder->where('parent_id', 0)
            ->order_by('name')
            ->results_as_arrays()
            ->first();


        $this->assertEquals($records[1], $record);

        // Record does not exist
        $this->assertNull(builder::table($this->table_name)->where('id', -3)->order_by('id')->first());
    }

    public function test_it_first_with_strict_throws_exception() {

        $builder = builder::table('user')->where('username', 'frank')->order_by('id');
        $this->assertNull($builder->first());

        $this->expectException(record_not_found_exception::class);
        $this->expectExceptionMessage('Can not find data record in database');
        $builder->first(true);
    }

    public function test_it_counts_record() {
        $this->create_sample_records();

        $this->assertEquals(
            3,
            builder::table($this->table_name)
                ->where('parent_id', '>', 0)
                ->count()
        );

        $this->assertEquals(
            4,
            builder::table($this->table_name)
                ->where('parent_id', '>', 0)
                ->or_where('name', '=', 'Jane')
                ->count()
        );
    }

    public function test_it_counts_record_with_group_by() {
        $this->create_sample_records();

        $this->assertEquals(
            3,
            builder::table($this->table_name)
                ->select('type')
                ->group_by('type')
                ->count()
        );
    }

    public function test_it_fetches_records_from_the_database() {
        $records = $this->create_sample_records();

        $builder = (new builder())
            ->order_by('id')
            ->from($this->table_name);

        $fetched_records = $builder->results_as_arrays()->fetch();

        $this->assertEquals($records, array_values($fetched_records));

        $builder = (new builder())
            ->order_by('id')
            ->where('name', 'Basil')
            ->from($this->table_name);

        $fetched_records = $builder->results_as_arrays()->fetch();

        $this->assertCount(1, $fetched_records);
        $this->assertEquals($records[3], array_values($fetched_records)[0]);
    }

    public function test_it_generates_sql_to_concatenate_strings() {
        $concatenated = builder::concat('This', 'is', 'concatenated');
        $this->assertEquals($this->db()->sql_concat('This', 'is', 'concatenated'), $concatenated);
    }

    public function test_it_has_db_link() {
        $builder = new builder();

        $this->assertInstanceOf(moodle_database::class, $builder->get_db());
    }

    public function test_it_is_fluent() {
        $this->create_table();

        $builder = new builder();
        $another_builder = new builder();

        $this->assertSame($builder, $builder->from($this->table_name));
        $this->assertSame($builder, $builder->as($this->table_name . '_alias'));
        $this->assertSame($builder, $builder->update(['parent_id' => 2]));
        $this->assertSame($builder, $builder->delete());
        $this->assertSame($builder, $builder->where_field('field', 'another_filed'));
        $this->assertSame($builder, $builder->or_where_field('field', 'another_filed'));
        $this->assertSame($builder, $builder->where('field', 'value'));
        $this->assertSame($builder, $builder->where(function (builder $builder) { $builder->where('f', 'v'); }));
        $this->assertSame($builder, $builder->or_where('field', 'value'));
        $this->assertSame($builder, $builder->or_where(function (builder $builder) { $builder->where('f', 'v'); }));
        $this->assertSame($builder, $builder->where_raw('created_at > now()'));
        $this->assertSame($builder, $builder->or_where_raw('created_at > now()'));
        $this->assertSame($builder, $builder->order_by('type', 'asc'));
        $this->assertSame($builder, $builder->select('*'));
        $this->assertSame($builder, $builder->select_raw('*'));
        $this->assertSame($builder, $builder->join($this->another_table_name, 'table.id', 'another_table.id'));
        $this->assertSame($builder, $builder->join($this->another_table_name, function (builder $builder) { $builder->where_field('table.id', 'another_table.id'); }));
        $this->assertSame($builder, $builder->cross_join('another_table'));
        $this->assertSame($builder, $builder->left_join('another_table', 'table.id', 'another_table.id'));
        $this->assertSame($builder, $builder->right_join('another_table', 'table.id', 'another_table.id'));
        $this->assertSame($builder, $builder->full_join('another_table', 'table.id', 'another_table.id'));
        $this->assertSame($builder, $builder->having('x', 'y'));
        $this->assertSame($builder, $builder->having('x', function (builder $builder) { $builder->where('x', 'y'); }));
        $this->assertSame($builder, $builder->or_having('x', 'y'));
        $this->assertSame($builder, $builder->or_having('x', function (builder $builder) { $builder->where('x', 'y'); }));
        $this->assertSame($builder, $builder->having_raw('x = y'));
        $this->assertSame($builder, $builder->or_having_raw('x = y'));
        $this->assertSame($builder, $builder->union($another_builder));
        $this->assertSame($builder, $builder->union_all($another_builder));
    }

    public function test_it_loads_a_lazy_collection() {
        $created_records = $this->create_sample_records();

        $records = builder::table($this->table_name)->get_lazy();
        $this->assertInstanceOf(lazy_collection::class, $records);

        foreach ($records as $record) {
            $this->assertContainsEquals((array)$record, $created_records);
        }
    }

    public function test_it_closes_lazy_collection() {
        $this->create_sample_records();

        $records = builder::table($this->table_name)->get_lazy()->as_array();
        $this->assertInstanceOf(lazy_collection::class, $records);
        $this->assertInstanceOf(moodle_recordset::class, $records);

        $this->assertTrue($records->valid());
        foreach ($records as $record) {
            $this->assertIsArray($record);
            break;
        }
        $this->assertTrue($records->valid());

        $records->close();
        $this->assertFalse($records->valid());
    }

    public function test_it_logs_last_executed_query() {
        $this->create_sample_records();

        $builder = builder::table($this->table_name);
        $builder->get();

        $queries = $builder->get_last_executed_queries();
        $query = array_shift($queries);
        $this->assertIsArray($query);
        $this->assertMatchesRegularExpression('/SELECT .* FROM .* WHERE 1 = 1/', $query['sql']);
        $this->assertEquals([], $query['params']);
        $this->assertEmpty($query['offset']);
        $this->assertEmpty($query['limit']);

        $builder = builder::table($this->table_name);
        $builder
            ->where('id', [1, 2, 3, 4])
            ->order_by('name', 'desc')
            ->offset(3)
            ->limit(12)
            ->get();

        $queries = $builder->get_last_executed_queries();
        $query = array_shift($queries);
        $this->assertIsArray($query);
        $this->assertMatchesRegularExpression('/SELECT .* FROM .* WHERE .*id IN \(.*\) ORDER BY .* DESC/', $query['sql']);
        $this->assertEquals([1, 2, 3, 4], array_values($query['params']));
        $this->assertEquals(3, $query['offset']);
        $this->assertEquals(12, $query['limit']);
    }

    public function test_find_fails() {
        $test_record = $this->create_sample_record();

        $record = builder::table($this->table_name)->find($test_record['id']);
        $this->assertNotEmpty($record);

        $record = builder::table($this->table_name)->find(1234);
        $this->assertNull($record);

        $record = builder::table($this->table_name)->find_or_fail($test_record['id']);
        $this->assertNotEmpty($record);

        $this->expectException(record_not_found_exception::class);
        builder::table($this->table_name)->find_or_fail(1234);
    }

    public function test_it_can_be_marked_as_nested() {
        $props = new properties();
        $props->nested = false;
        $this->assertFalse($props->nested);

        new builder($props, true);

        $this->assertTrue($props->nested);
    }

    public function test_last_executed_query_is_stored() {
        $builder = builder::table('user')->select(['id', 'username'])->order_by('id', 'DESC');
        $this->assertEmpty($builder->get_last_executed_queries());

        $builder->first();
        $expected_one = [
            'sql' => 'SELECT "user".id, "user".username FROM {user} "user" WHERE 1 = 1 ORDER BY "user".id DESC',
            'params' => [],
            'offset' => 0,
            'limit' => 1,
        ];
        $this->assertSame($expected_one, $builder->get_last_executed_query());

        $builder->fetch();
        $expected_two = [
            'sql' => 'SELECT "user".id, "user".username FROM {user} "user" WHERE 1 = 1 ORDER BY "user".id DESC',
            'params' => [],
            'offset' => 0,
            'limit' => null,
        ];
        $this->assertSame($expected_two, $builder->get_last_executed_query());

        $this->assertSame([$expected_one, $expected_two], $builder->get_last_executed_queries());

        $builder->select(['id', 'deleted'])
            ->order_by('firstname', 'ASC')
            ->offset(10)
            ->limit(13);
        $builder->fetch();
        $expected_three = [
            'sql' => 'SELECT "user".id, "user".deleted FROM {user} "user" WHERE 1 = 1 ORDER BY "user".id DESC, "user".firstname ASC',
            'params' => [],
            'offset' => 10,
            'limit' => 13,
        ];
        $this->assertSame($expected_three, $builder->get_last_executed_query());

        $this->assertSame([$expected_one, $expected_two, $expected_three], $builder->get_last_executed_queries());
    }

    public function test_subquery_without_alias() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('It is required to set an alias to select from a subquery');
        $builder = builder::table(
            builder::table('test', '')
        );
        query::from_builder($builder)->build();
        $this->fail('Exception expected');
    }

    public function test_subquery_with_limit() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('It is required to set an alias to select from a subquery');
        $builder = builder::table(
            builder::table('test')->limit(10)
        );
        query::from_builder($builder)->build();
        $this->fail('Exception expected');
    }

    public function test_subquery_with_offset() {
        $builder = builder::table(
            builder::table('test')->offset(10),
            'foo'
        );
        query::from_builder($builder)->build();
        $this->assertDebuggingCalled('Can not use limits in a subquery due to database driver limitations.');
    }

    public function test_builder_create() {
        $builder = builder::create();
        $this->assertInstanceOf(builder::class, $builder);
    }

    public function test_builder_new() {
        $builder = builder::create()->new();
        $this->assertInstanceOf(builder::class, $builder);
    }

    public function test_subquery_with_cheating_limit() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Builder limit cannot be less than 0. If you want to remove the limit pass null or 0. (-10)');
        $builder = builder::table(
            builder::table('test')->offset(10)->limit(-10),
            'foo'
        );
        query::from_builder($builder)->build();
        $this->fail('Exception expected');
    }

    public function test_subquery_with_cheating_offset() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Builder offset cannot be less than 0. If you want to remove the offset pass null or 0. (-10)');
        $builder = builder::table(
            builder::table('test')->offset(-10)->limit(10),
            'foo'
        );
        query::from_builder($builder)->build();
        $this->fail('Exception expected');
    }

    public function test_get_join() {
        $builder = builder::table('foo')
            ->join('bar', 'fooid', 'id')
            ->join((new \core\orm\query\table('chi'))->as('pi'), 'fooid', 'id');

        $this->assertInstanceOf(\core\orm\query\join::class, $builder->get_join('bar'));
        $this->assertInstanceOf(\core\orm\query\join::class, $builder->get_join(new \core\orm\query\table('bar')));
        $this->assertInstanceOf(\core\orm\query\join::class, $builder->get_join('chi'));
        $this->assertInstanceOf(\core\orm\query\join::class, $builder->get_join(new \core\orm\query\table('chi')));
        $this->assertNull($builder->get_join('man'));
        $this->assertNull($builder->get_join('pi'));

        $this->assertTrue($builder->has_join('bar'));
        $this->assertTrue($builder->has_join(new \core\orm\query\table('bar')));
        $this->assertTrue($builder->has_join('chi'));
        $this->assertTrue($builder->has_join(new \core\orm\query\table('chi')));
        $this->assertFalse($builder->has_join('pi'));
        $this->assertFalse($builder->has_join('foo'));
        $this->assertFalse($builder->has_join('man'));
    }

    public function test_impossible_mapto() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('The object you map to must be a callable or a valid class name or null to cancel mapping');
        builder::create()->map_to(true);
    }

    public function test_it_does_not_clone_builder() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage(
            'Builder is a complex object with multiple references inside,' .
            'cloning is not supported. Please consider creating a new instance instead of cloning'
        );

        $builder = builder::table($this->table_name);
        // There will be no result of the cloning operation assignable to any variable as it throws an exception, but
        // we need a dummy var here to make IDE happy.
        $dummy_var = clone $builder;
    }

    public function test_it_does_remove_conditions() {
        $builder = builder::table('foo')
            ->where('normalfield', 'value');

        $conditions = $builder->get_conditions();
        $this->assertCount(1, $conditions);

        $builder->remove_where('normalfield');

        $conditions = $builder->get_conditions();
        $this->assertCount(0, $conditions);

        // Let's add a mix of conditions
        $builder = builder::table('foo')
            ->where('normalfield', 'value')
            ->or_where('normalfield', 'value2')
            ->where('otherfield', 'value3')
            ->where('otherfield2', 'value4');

        $builder->remove_where('normalfield');

        $conditions = $builder->get_conditions();
        $this->assertCount(2, $conditions);

        $expected_conditions = ['otherfield', 'otherfield2'];

        foreach ($conditions as $condition) {
            $this->assertContains($condition->get_field()->get_field_column(), $expected_conditions);
            unset($expected_conditions[array_search($condition->get_field()->get_field_column(), $expected_conditions)]);
        }

        $this->assertEmpty($expected_conditions);

        // Try an alias
        $builder = builder::table('foo')
            ->as('bla')
            ->where('bla.normalfield', 'value');

        $builder->remove_where('normalfield');

        // Nothing got removed yet
        $conditions = $builder->get_conditions();
        $this->assertCount(1, $conditions);

        $builder->remove_where('bla.normalfield');

        $conditions = $builder->get_conditions();
        $this->assertCount(0, $conditions);

        // Try a field with identifier
        $field1 = new field('myfield');
        $field1_with_id = new field('myfield');
        $field1_with_id->set_identifier('thisisit');
        $field2 = new field('anotherone');

        $builder = builder::table('foo')
            ->where($field1, 'value1')
            ->where($field1_with_id, 'value2')
            ->where($field2, 'value3');

        $builder->remove_where('normalfield');

        // Nothing got removed yet
        $conditions = $builder->get_conditions();
        $this->assertCount(3, $conditions);

        $field1_with_wrong_id = new field('myfield');
        $field1_with_wrong_id->set_identifier('thisisnotit');

        $builder->remove_where($field1_with_wrong_id);

        // Nothing got removed yet
        $conditions = $builder->get_conditions();
        $this->assertCount(3, $conditions);

        $builder->remove_where($field1_with_id);

        // The one with the identifier should be gone
        $conditions = $builder->get_conditions();
        $this->assertCount(2, $conditions);

        $expected_conditions = [$field1, $field2];

        foreach ($conditions as $condition) {
            $this->assertContains($condition->get_field(), $expected_conditions);
            unset($expected_conditions[array_search($condition->get_field(), $expected_conditions)]);
        }

        $this->assertEmpty($expected_conditions);
    }

    public function test_it_cannot_remove_closure_conditions() {
        $nested_condition = function (builder $builder) {
            $builder->where('normalfield', 'value')
                ->or_where('normalfield', 'value2');
        };

        $builder = builder::table('foo')
            ->where($nested_condition);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Removing of an nested where condition is not yet supported.');

        $builder->remove_where($nested_condition);
    }

    public function test_it_cannot_remove_sql_conditions() {
        $sql = new sql('foo = :bar', ['bar' => 'value']);

        $builder = builder::table('foo')
            ->where($sql);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Removing of a raw sql condition is not yet supported.');

        $builder->remove_where($sql);
    }

    public function test_it_cannot_remove_raw_conditions() {
        $sql = new sql('foo = :bar', ['bar' => 'value']);

        $field = new field($sql);

        $builder = builder::table('foo')
            ->where($field, 'value');

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Removing of a raw condition is not yet supported.');

        $builder->remove_where($field);
    }

}
