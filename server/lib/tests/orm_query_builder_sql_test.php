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
use core\orm\query\order;
use core\orm\query\sql\query;
use core\orm\query\sql\select;
use core\orm\query\sql\where;
use core\orm\query\table;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/tests/orm_query_builder_base.php');

/**
 * Class core_orm_builder_sql_testcase
 *
 * @package core
 * @group orm
 */
class core_orm_builder_sql_testcase extends orm_query_builder_base {

    public function test_it_adds_raw_where_clause() {

        $original_params = ['param_123' => 'param_123 value'];

        $builder = $this->new_test_where_builder()
                ->where_raw('one_field is like another field :param_123', $original_params);

        // AND
        [$sql, $params] = where::from_builder($builder)->build();

        $this->assertEquals($original_params, $params);
        $this->assertEquals('1 = 1 AND one_field is like another field :param_123', $sql);

        $builder = $this->new_test_where_builder()
                ->or_where_raw('one_field is like another field :param_123', $original_params);

        // OR
        [$sql, $params] = where::from_builder($builder)->build();

        $this->assertEquals($original_params, $params);
        $this->assertEquals('1 = 1 OR one_field is like another field :param_123', $sql);
    }

    public function test_it_adds_stuff_conditionally() {
        $builder = $this->new_test_where_builder()
            ->when(true, function (builder $builder) {
                $builder->where_field('a', '>', 'b');
            });

        [$sql, $params] = where::from_builder($builder)->build();
        $this->assertEmpty($params);
        $this->assertEquals('1 = 1 AND "test__qb".a > "test__qb".b', $sql);


        $builder = $this->new_test_where_builder()
            ->when(false, function (builder $builder) {
                $builder->where_field('a', '>', 'b');
            });

        [$sql, $params] = where::from_builder($builder)->build();
        $this->assertEmpty($params);
        $this->assertEquals('1 = 1', $sql);


        $builder = $this->new_test_where_builder()
            ->when(false, function (builder $builder) {
                $builder->where_field('a', '>', 'b');
            }, function (builder $builder) {
                $builder->select('column');
            });

        [$sql, $params] = select::from_builder($builder)->build();
        $this->assertEmpty($params);
        $this->assertEquals('"test__qb".column', $sql);

        [$sql, $params] = where::from_builder($builder)->build();
        $this->assertEmpty($params);
        $this->assertEquals('1 = 1', $sql);


        $builder = $this->new_test_where_builder()
            ->unless(false, function (builder $builder) {
                $builder->select('column');
            }, function (builder $builder) {
                $builder->where_field('a', '>', 'b');
            });

        [$sql, $params] = select::from_builder($builder)->build();
        $this->assertEmpty($params);
        $this->assertEquals('"test__qb".column', $sql);

        [$sql, $params] = where::from_builder($builder)->build();
        $this->assertEmpty($params);
        $this->assertEquals('1 = 1', $sql);
    }

    public function test_it_has_where_shortcuts() {

        $builder = $this->new_test_where_builder()->where_null('a');

        [$sql, $params] = where::from_builder($builder)->build();
        $this->assertEmpty($params);
        $this->assertEquals('1 = 1 AND "test__qb".a IS NULL', $sql);


        $builder = $this->new_test_where_builder()
            ->where_not_null('x')
            ->or_where_not_null('y');

        [$sql, $params] = where::from_builder($builder)->build();
        $this->assertEmpty($params);
        $this->assertEquals('1 = 1 AND "test__qb".x IS NOT NULL OR "test__qb".y IS NOT NULL', $sql);

        // That's where mock used to be, the following doesn't actually test the full range of what's going on as
        // there is different way of generating sql and we can't test it doing the following if the params are involved
        // as they will be different for each query.
        // Bringing mocking back should be beneficial, that would need a library import for final classes.

        $this->assertEquals(
            where::from_builder($this->new_test_where_builder()
                ->where('source_field', [1, 2, 3, 4, 6, '7', 12, '123', 67, 89, 195, 123456])
                ->where('source_field', '!=', [1, 2, 3, 4, 6, '7', 12, '123', 67, 89, 195, 123456])
                ->or_where('source_field', [1, 2, 3, 4, 6, '7', 12, '123', 67, 89, 195, 123456])
                ->or_where('source_field', '!=', [1, 2, 3, 4, 6, '7', 12, '123', 67, 89, 195, 123456])
            )->build(),

            where::from_builder($this->new_test_where_builder()
                ->where_in('source_field', [1, 2, 3, 4, 6, '7', 12, '123', 67, 89, 195, 123456])
                ->where_not_in('source_field', [1, 2, 3, 4, 6, '7', 12, '123', 67, 89, 195, 123456])
                ->or_where_in('source_field', [1, 2, 3, 4, 6, '7', 12, '123', 67, 89, 195, 123456])
                ->or_where_not_in('source_field', [1, 2, 3, 4, 6, '7', 12, '123', 67, 89, 195, 123456])
            )->build());
    }

    public function test_it_adds_where_clause_by_another_field() {

        $operators = ['=', '<>', '!=', '>', '<', '>=', '<='];

        foreach ($operators as $operator) {
            $normalized_operator = ($operator == '!=') ? '<>' : $operator;

            $builder = $this->new_test_where_builder()
                    ->where_field('source', $operator, 'target');

            // And condition
            [$sql, $params] = where::from_builder($builder)->build();

            $this->assertEmpty($params);
            $this->assertEquals("1 = 1 AND \"{$this->table_name}\".source $normalized_operator \"{$this->table_name}\".target", $sql);

            $builder = $this->new_test_where_builder()
                    ->or_where_field('source', $operator, 'target');

            // Or condition
            [$sql, $params] = where::from_builder($builder)->build();

            $this->assertEmpty($params);
            $this->assertEquals("1 = 1 OR \"{$this->table_name}\".source $normalized_operator \"{$this->table_name}\".target", $sql);
        }

        // Test shorthand syntax with omitting '=' sign

        $builder = $this->new_test_where_builder()
                ->where_field('source', 'target');

        // And condition
        [$sql, $params] = where::from_builder($builder)->build();

        $this->assertEmpty($params);
        $this->assertEquals("1 = 1 AND \"{$this->table_name}\".source = \"{$this->table_name}\".target", $sql);

        $builder = $this->new_test_where_builder()
                ->or_where_field('source', 'target');

        // Or condition
        [$sql, $params] = where::from_builder($builder)->build();

        $this->assertEmpty($params);
        $this->assertEquals("1 = 1 OR \"{$this->table_name}\".source = \"{$this->table_name}\".target", $sql);

        // Parenthesis on condition
        [$sql, $params] = where::from_builder($builder)->build(true);

        $this->assertEmpty($params);
        $this->assertEquals("(1 = 1 OR \"{$this->table_name}\".source = \"{$this->table_name}\".target)", $sql);
    }

    public function test_it_adds_simple_where_clause_for_array_values() {
        // Test it works with a shorthand '=' omitted

        $builder = $this->new_test_where_builder()
                ->where('source_field', [1, 2, 3, 4, 6, '7', 12, '123', 67, 89, 195, 123456]);

        // AND
        [$sql, $params] = where::from_builder($builder)->build();

        $this->assertEmpty($params);
        $this->assertEquals("1 = 1 AND \"{$this->table_name}\".source_field IN ('1','2','3','4','6','7','12','123','67','89','195','123456')", $sql);

        $builder = $this->new_test_where_builder()
                ->or_where('source_field', [1, 2, 3, 4, 6, '7', 12, '123', 67, 89, 195, 123456]);

        // OR
        [$sql, $params] = where::from_builder($builder)->build();

        $this->assertEmpty($params);
        $this->assertEquals("1 = 1 OR \"{$this->table_name}\".source_field IN ('1','2','3','4','6','7','12','123','67','89','195','123456')", $sql);

        // Different operators now
        $operators = ['=', '!=', '<>'];

        // Handling empty arrays
        foreach ($operators as $operator) {
            // Comparison SQL
            $in = ($operator == '=') ? '1 = 2' : '1 = 1';

            $builder = $this->new_test_where_builder()
                    ->where('source_field', $operator, []);

            // AND
            [$sql, $params] = where::from_builder($builder)->build();

            $this->assertEmpty($params);
            $this->assertEquals("1 = 1 AND {$in}", $sql);

            $builder = $this->new_test_where_builder()
                    ->or_where('source_field', $operator, []);

            // OR
            [$sql, $params] = where::from_builder($builder)->build();

            $this->assertEmpty($params);
            $this->assertEquals("1 = 1 OR {$in}", $sql);
        }

        // Handling numeric values
        foreach ($operators as $operator) {
            // Comparison SQL
            $in = ($operator == '=') ? 'IN' : 'NOT IN';

            $builder = $this->new_test_where_builder()
                    ->where('source_field', $operator, [1, 2, 3, 4, 6, '7', 12, '123', 67, 89, 195, 123456]);

            // AND
            [$sql, $params] = where::from_builder($builder)->build();

            $this->assertEmpty($params);
            $this->assertEquals("1 = 1 AND \"{$this->table_name}\".source_field {$in} ('1','2','3','4','6','7','12','123','67','89','195','123456')", $sql);

            $builder = $this->new_test_where_builder()
                    ->or_where('source_field', $operator, [1, 2, 3, 4, 6, '7', 12, '123', 67, 89, 195, 123456]);

            // OR
            [$sql, $params] = where::from_builder($builder)->build();

            $this->assertEmpty($params);
            $this->assertEquals("1 = 1 OR \"{$this->table_name}\".source_field {$in} ('1','2','3','4','6','7','12','123','67','89','195','123456')", $sql);
        }

        // Handling parametrized values
        foreach ($operators as $operator) {
            // Comparison SQL
            $in = ($operator == '=') ? 'IN' : 'NOT IN';

            $builder = $this->new_test_where_builder()
                    ->where('in_for_a_field', $operator, ['abra', 'cadabra', ':-P', '*<8-)', '(=', '=)', '%-)']);

            // AND
            [$sql, $params] = where::from_builder($builder)->build();

            $this->assertCount(7, $params);

            // Getting param names
            $keys = implode(
                ',',
                array_map(
                    function ($param) {
                        return ":{$param}";
                    },
                    array_keys($params)
                )
            );

            $this->assertEquals("1 = 1 AND \"{$this->table_name}\".in_for_a_field {$in} ({$keys})", $sql);
            $this->assertEquals(['abra', 'cadabra', ':-P', '*<8-)', '(=', '=)', '%-)'], array_values($params));

            // OR
            $builder = $this->new_test_where_builder()
                ->or_where('in_for_a_field', $operator, ['abra', 'cadabra', ':-P', '*<8-)', '(=', '=)', '%-)']);

            [$sql, $params] = where::from_builder($builder)->build();

            $this->assertCount(7, $params);

            // Getting param names
            $keys = implode(
                ',',
                array_map(
                    function ($param) {
                        return ":{$param}";
                    },
                    array_keys($params)
                )
            );

            $this->assertEquals("1 = 1 OR \"{$this->table_name}\".in_for_a_field {$in} ({$keys})", $sql);
            $this->assertEquals(['abra', 'cadabra', ':-P', '*<8-)', '(=', '=)', '%-)'], array_values($params));
        }
    }

    public function test_it_adds_simple_where_clause_for_null_values() {
        // Shorthand '=' syntax
        // AND
        $builder = $this->new_test_where_builder()
            ->where('some_field', null);

        [$sql, $params] = where::from_builder($builder)->build();

        $this->assertEmpty($params);
        $this->assertEquals("1 = 1 AND \"{$this->table_name}\".some_field IS NULL", $sql);

        // OR
        $builder = $this->new_test_where_builder()
            ->or_where('some_field', null);

        [$sql, $params] = where::from_builder($builder)->build();

        $this->assertEmpty($params);
        $this->assertEquals("1 = 1 OR \"{$this->table_name}\".some_field IS NULL", $sql);

        $operators =  ['=', '<>', '!='];

        foreach ($operators as $operator) {
            // Comparison SQL
            $null = ($operator == '=') ? 'IS NULL' : 'IS NOT NULL';

            // AND
            $builder = $this->new_test_where_builder()
                ->where('some_field', $operator, null);

            [$sql, $params] = where::from_builder($builder)->build();

            $this->assertEmpty($params);
            $this->assertEquals("1 = 1 AND \"{$this->table_name}\".some_field {$null}", $sql);

            // OR
            $builder = $this->new_test_where_builder()
                ->or_where('some_field', $operator, null);

            [$sql, $params] = where::from_builder($builder)->build();

            $this->assertEmpty($params);
            $this->assertEquals("1 = 1 OR \"{$this->table_name}\".some_field {$null}", $sql);
        }
    }

    public function test_it_adds_simple_where_clause_for_int_values() {
        $operators =  ['=', '<>', '!=', '>', '<', '>=', '<='];

        foreach ($operators as $operator) {
            $values = [
                '4' => '4',
                '-42' => '4',
                '324234.43' => '324234.43',
                '-124' => '-124',
                154 => '154',
                -12 => '-12',
            ];

            foreach ($values as $value) {
                $normalized_operator = ($operator == '!=') ? '<>' : $operator;

                // AND
                $builder = $this->new_test_where_builder()
                    ->where('numeric', $operator, $value);

                [$sql, $params] = where::from_builder($builder)->build();

                $cond_param = array_keys($params)[0] ?? null;
                $this->assertEquals([$cond_param => $value], $params);
                $this->assertEquals("1 = 1 AND \"{$this->table_name}\".numeric {$normalized_operator} :{$cond_param}", $sql);

                // OR
                $builder = $this->new_test_where_builder()
                    ->or_where('numeric', $operator, $value);

                [$sql, $params] = where::from_builder($builder)->build();

                $cond_param = array_keys($params)[0] ?? null;
                $this->assertEquals([$cond_param => $value], $params);
                $this->assertEquals("1 = 1 OR \"{$this->table_name}\".numeric {$normalized_operator} :{$cond_param}", $sql);
            }
        }
    }

    public function test_it_adds_simple_where_clause_for_bool_values() {
        $operators =  ['=', '<>'];

        foreach ($operators as $operator) {
            $values = [
                true,
                false
            ];

            foreach ($values as $value) {
                $normalized_operator = ($operator == '!=') ? '<>' : $operator;

                // AND
                $builder = $this->new_test_where_builder()
                    ->where('boolean', $operator, $value);

                $expected_value = (int)$value;

                [$sql, $params] = where::from_builder($builder)->build();

                $this->assertEmpty($params);
                $this->assertEquals("1 = 1 AND \"{$this->table_name}\".boolean {$normalized_operator} $expected_value", $sql);

                // OR
                $builder = $this->new_test_where_builder()
                    ->or_where('boolean', $operator, $value);

                [$sql, $params] = where::from_builder($builder)->build();

                $this->assertEmpty($params);
                $this->assertEquals("1 = 1 OR \"{$this->table_name}\".boolean {$normalized_operator} $expected_value", $sql);
            }
        }
    }

    public function test_it_adds_simple_where_clause_for_strings() {
        $operators = ['=', '<>', '!='];

        foreach ($operators as $operator) {
            $normalized_operator = ($operator == '!=') ? '<>' : $operator;

            // AND
            $builder = $this->new_test_where_builder()
                ->where('all_text_fields', $operator, 'This is a string that I am comparing to');

            [$sql, $params] = where::from_builder($builder)->build();

            $this->assertCount(1, $params);

            $param = array_keys($params)[0];

            $this->assertEquals('This is a string that I am comparing to' , $params[$param]);
            $this->assertEquals("1 = 1 AND \"{$this->table_name}\".all_text_fields {$normalized_operator} :{$param}", $sql);

            // OR
            $builder = $this->new_test_where_builder()
                ->or_where('all_text_fields', $operator, 'This is a string that I am comparing to');

            [$sql, $params] = where::from_builder($builder)->build();

            $this->assertCount(1, $params);

            $param = array_keys($params)[0];

            $this->assertEquals('This is a string that I am comparing to' , $params[$param]);
            $this->assertEquals("1 = 1 OR \"{$this->table_name}\".all_text_fields {$normalized_operator} :{$param}", $sql);
        }
    }

    public function test_it_adds_nested_where_clause() {
        $builder = $this->new_test_where_builder()
            ->nested_where(
                function (builder $builder) {
                    $builder->where('zzz', 'Dreamy Sleepy Nighty Snoozy Snooze')
                        ->or_where('party', null);
                }
            )->where(
                function (builder $builder) {
                    // I am an empty closure, I should be handled gracefully
                }
            )->or_where(
                function (builder $builder) {
                    $builder->where(
                        function (builder $builder) {
                            $builder->where('parent_id', '!=', false)
                                ->or_where_field('type', 'my_type')
                                ->or_where('extra', 'value');
                        }
                    )->where('one_field', true);
                }
            )->where_raw("raw_column = :p_zero", ['p_zero' => 'RAW value']);

        [$sql, $params] = where::from_builder($builder)->build();

        $this->assertCount(3, $params);

        $table = "\"{$this->table_name}\".";

        $param_names = array_keys($params);

        $expected_sql = "1 = 1 AND ({$table}zzz = :{$param_names[0]} OR {$table}party IS NULL) AND (1 = 1) OR (({$table}parent_id <> 0 OR {$table}type = {$table}my_type OR {$table}extra = :{$param_names[1]}) AND {$table}one_field = 1) AND raw_column = :p_zero";

        $this->assertEquals(array_values($params), ['Dreamy Sleepy Nighty Snoozy Snooze', 'value', 'RAW value']);
        $this->assertEquals($expected_sql, $sql);
    }

    public function test_it_adds_order_by() {
        $table = "\"{$this->table_name}\"";

        $builder = $this->new_test_where_builder()
            ->order_by('column_to_sort');

        // Sorts by a column in ascending order
        [$sql] = query::from_builder($builder)->build();

        $this->assertEquals("SELECT {$table}.* FROM {{$this->table_name}} {$table} WHERE 1 = 1 ORDER BY {$table}.column_to_sort ASC", $sql);

        // Sorts by a column in ascending order
        $builder = $this->new_test_where_builder()
            ->order_by('column_to_sort', 'desc');

        [$sql] = query::from_builder($builder)->build();

        $this->assertEquals("SELECT {$table}.* FROM {{$this->table_name}} {$table} WHERE 1 = 1 ORDER BY {$table}.column_to_sort DESC", $sql);

        // Sorts by raw columns in descending order
        $builder = $this->new_test_where_builder()
            ->order_by_raw('raw_column DESC');

        [$sql] = query::from_builder($builder)->build();

        $this->assertEquals("SELECT {$table}.* FROM {{$this->table_name}} {$table} WHERE 1 = 1 ORDER BY raw_column DESC", $sql);

        // Sorts by raw columns in ascending order
        $builder = $this->new_test_where_builder()
            ->order_by_raw('raw_column1 ASC, raw_column2 DESC');

        [$sql] = query::from_builder($builder)->build();

        $this->assertEquals("SELECT {$table}.* FROM {{$this->table_name}} {$table} WHERE 1 = 1 ORDER BY raw_column1 ASC, raw_column2 DESC", $sql);

        // It does not add ORDER BY statement if order is not specified
        [$sql] = query::from_builder($this->new_test_where_builder())->build();

        $this->assertEquals("SELECT {$table}.* FROM {{$this->table_name}} {$table} WHERE 1 = 1", $sql);
    }

    public function test_it_adds_multiple_order_by_statements() {
        $table = "\"{$this->table_name}\"";

        //
        // Multiple order by statements
        //
        $builder = $this->new_test_where_builder()
            ->order_by('column_to_sort')
            ->order_by('another_one', 'desc');

        [$sql] = query::from_builder($builder)->build();

        $this->assertEquals("SELECT {$table}.* FROM {{$this->table_name}} {$table} WHERE 1 = 1 ORDER BY {$table}.column_to_sort ASC, {$table}.another_one DESC", $sql);

        //
        // Reset order
        //
        $builder = $this->new_test_where_builder()
            ->order_by('column_to_sort')
            ->order_by('another_one', 'desc')
            ->reset_order_by();

        [$sql] = query::from_builder($builder)->build();

        $this->assertEquals("SELECT {$table}.* FROM {{$this->table_name}} {$table} WHERE 1 = 1", $sql);

        //
        // Converting arbitrary objects passed to string as a last resort
        //
        $order_str_class = new class {
            public function __toString() {
                return 'my_order ASC';
            }
        };

        $builder = $this->new_test_where_builder()
            ->order_by_raw('column_to_sort DESC')
            ->order_by_raw($order_str_class)
            ->order_by('another_column');

        [$sql] = query::from_builder($builder)->build();

        $this->assertEquals("SELECT {$table}.* FROM {{$this->table_name}} {$table} WHERE 1 = 1 ORDER BY column_to_sort DESC, my_order ASC, {$table}.another_column ASC", $sql);

        //
        // Passing field object
        //
        $field1 = new order('c1');
        $field2 = new order('c2');
        $builder = $this->new_test_where_builder()
            ->order_by($field1)
            ->order_by_raw($field2);

        $this->assertEquals($builder, $field1->get_builder());
        $this->assertEquals($builder, $field2->get_builder());

        // Sorts by a column in ascending order
        [$sql] = query::from_builder($builder)->build();

        $this->assertEquals("SELECT {$table}.* FROM {{$this->table_name}} {$table} WHERE 1 = 1 ORDER BY {$table}.c1 ASC, {$table}.c2 ASC", $sql);

        //
        // Passing field object with a builder set, does not override it
        //
        $field1 = new order('c1', 'desc', builder::table('table'));
        $field2 = new order('c2', 'desc', builder::table('table'));
        $builder = $this->new_test_where_builder()
            ->order_by($field1)
            ->order_by_raw($field2);

        $this->assertNotEquals($builder, $field1->get_builder());
        $this->assertNotEquals($builder, $field2->get_builder());

        // Sorts by a column in ascending order
        [$sql] = query::from_builder($builder)->build();

        $this->assertEquals("SELECT {$table}.* FROM {{$this->table_name}} {$table} WHERE 1 = 1 ORDER BY \"table\".c1 DESC, \"table\".c2 DESC", $sql);

        //
        // Order by raw sql
        //
        $builder = $this->new_test_where_builder()
            ->order_by(new sql('f1 ASC'));

        $this->assertNotEquals($builder, $field1->get_builder());

        // Sorts by a column in ascending order
        [$sql] = query::from_builder($builder)->build();

        $this->assertEquals("SELECT {$table}.* FROM {{$this->table_name}} {$table} WHERE 1 = 1 ORDER BY f1 ASC", $sql);

        // Reset order by
        $builder->order_by(null);
        [$sql] = query::from_builder($builder)->build();
        $this->assertEquals("SELECT {$table}.* FROM {{$this->table_name}} {$table} WHERE 1 = 1", $sql);
    }

    public function test_it_can_select_from_subquery() {
        $table = "\"{$this->table_name}\"";

        $builder = new builder();
        $builder->from(builder::table($this->table_name)->where('a' , '>', 1), 'sub')
            ->where('b', '>', 2);

        [$sql, $params] = query::from_builder($builder)->build();

        $this->assertCount(2, $params);
        $param_names = array_keys($params);

        $this->assertEquals("SELECT \"sub\".* FROM (SELECT {$table}.* FROM {test__qb} {$table} WHERE {$table}.a > :{$param_names[1]}) \"sub\" WHERE \"sub\".b > :{$param_names[0]}", $sql);
        $this->assertEquals([2, 1], array_values($params));
    }

    public function tests_it_returns_proper_limits() {
        $table = "\"{$this->table_name}\"";

        // It sets proper limits
        $builder = $this->new_test_where_builder()
            ->limit(27)
            ->offset(3);

        [$sql, $params, $from, $count] = query::from_builder($builder)->build();

        $this->assertEquals("SELECT {$table}.* FROM {{$this->table_name}} {$table} WHERE 1 = 1", $sql);
        $this->assertEmpty($params);
        $this->assertEquals($from, 3);
        $this->assertEquals($count, 27);

        // It doesn't set limits when not specified
        [$sql, $params, $from, $count] = query::from_builder($this->new_test_where_builder())->build();

        $this->assertEquals("SELECT {$table}.* FROM {{$this->table_name}} {$table} WHERE 1 = 1", $sql);
        $this->assertEmpty($params);
        $this->assertEquals($from, 0);
        $this->assertEquals($count, 0);
    }

    public function test_it_adds_fields_to_select() {
        $table = "\"{$this->table_name}\"";

        // It adds select all fields for the current table if select is not specified
        [$sql] = query::from_builder($this->new_test_where_builder())->build();
        $this->assertEquals("SELECT {$table}.* FROM {{$this->table_name}} {$table} WHERE 1 = 1", $sql);

        // I select certain fields only
        $builder = $this->new_test_where_builder()
            ->select(['field1', 'field2'])
            ->when(true, function (builder $builder) {
                $builder->add_select(builder::table('t1')
                    ->select('one')
                    ->where_field('one', new field('field3', $builder))
                );
            })
            ->add_select('field3')
            ->add_select_raw('field4 as :p1', $p1 = ['p1' => 'p-val one'])
            ->add_select_raw('field5 as :p2', $p2 = ['p2' => 'p-val two'])
            ->add_select(['field6', 'not_prefixed.field7'])
            ->add_select('not_prefixed.field8');

        [$sql, $params] = query::from_builder($builder)->build();

        $this->assertEquals(array_merge($p1, $p2), $params);
        $this->assertEquals("SELECT {$table}.field1, {$table}.field2, (SELECT \"t1\".one FROM {t1} \"t1\" WHERE \"t1\".one = {$table}.field3), {$table}.field3, field4 as :p1, field5 as :p2, {$table}.field6, not_prefixed.field7, not_prefixed.field8 FROM {{$this->table_name}} {$table} WHERE 1 = 1", $sql);
    }

    public function test_it_can_reset_select() {
        $builder = $this->new_test_where_builder()
            ->select(['id', 'username'])->where('firstname', 'frank');
        [$sql, $params] = query::from_builder($builder)->build();
        $this->assertStringStartsWith('SELECT "test__qb".id, "test__qb".username FROM {test__qb} "test__qb" WHERE 1 = 1 AND "test__qb".firstname = ', $sql);
        $this->assertSame(['frank'], array_values($params));

        $builder->reset_select();
        [$sql, $params] = query::from_builder($builder)->build();
        $this->assertStringStartsWith('SELECT "test__qb".* FROM {test__qb} "test__qb" WHERE 1 = 1 AND "test__qb".firstname = ', $sql);
        $this->assertSame(['frank'], array_values($params));

        $builder->select('username');
        [$sql, $params] = query::from_builder($builder)->build();
        $this->assertStringStartsWith('SELECT "test__qb".username FROM {test__qb} "test__qb" WHERE 1 = 1 AND "test__qb".firstname = ', $sql);
        $this->assertSame(['frank'], array_values($params));

        $builder->reset_select();
        [$sql, $params] = query::from_builder($builder)->build();
        $this->assertStringStartsWith('SELECT "test__qb".* FROM {test__qb} "test__qb" WHERE 1 = 1 AND "test__qb".firstname = ', $sql);
        $this->assertSame(['frank'], array_values($params));
    }

    public function test_it_generates_sql_for_various_join_types() {
        $table = "\"{$this->table_name}\"";

        // Inner join
        $builder = $this->new_test_where_builder()
            ->join($this->another_table_name, 'id', 'parent_id');

        [$sql] = query::from_builder($builder)->build();

        $this->assertEquals("SELECT {$table}.* FROM {{$this->table_name}} {$table} INNER JOIN {{$this->another_table_name}} \"{$this->another_table_name}\" ON {$table}.id = \"{$this->another_table_name}\".parent_id WHERE 1 = 1", $sql);

        // Cross join
        $builder = $this->new_test_where_builder()
            ->cross_join($this->another_table_name);

        [$sql] = query::from_builder($builder)->build();

        $this->assertEquals("SELECT {$table}.* FROM {{$this->table_name}} {$table} CROSS JOIN {{$this->another_table_name}} \"{$this->another_table_name}\" WHERE 1 = 1", $sql);

        // Left join
        $builder = $this->new_test_where_builder()
            ->left_join($this->another_table_name, 'price', '>', 'margin');

        [$sql] = query::from_builder($builder)->build();

        $this->assertEquals("SELECT {$table}.* FROM {{$this->table_name}} {$table} LEFT JOIN {{$this->another_table_name}} \"{$this->another_table_name}\" ON {$table}.price > \"{$this->another_table_name}\".margin WHERE 1 = 1", $sql);

        // Right join
        $builder = $this->new_test_where_builder()
            ->right_join(
                $this->another_table_name,
                function (builder $builder) {
                    $builder->where(
                        function (builder $builder) {
                            $builder->where_field("\"{$this->table_name}\".a_field", 'the_field')
                                ->or_where('c_field', '=', true);
                        }
                    )->where_raw('abracadabra = :param', ['param' => 'Surprise! Woo-hoo']);
                }
            );

        [$sql, $params] = query::from_builder($builder)->build();

        $this->assertEquals(['param' => 'Surprise! Woo-hoo'], $params);
        $this->assertEquals("SELECT {$table}.* FROM {{$this->table_name}} {$table} RIGHT JOIN {{$this->another_table_name}} \"{$this->another_table_name}\" ON (\"{$this->table_name}\".a_field = \"{$this->another_table_name}\".the_field OR \"{$this->another_table_name}\".c_field = 1) AND abracadabra = :param WHERE 1 = 1", $sql);

        // Full join
        $builder = $this->new_test_where_builder()
            ->full_join($this->another_table_name, 'id', '<', 'scone')
            ->where('scones', '!=', null)
            ->order_by('scones');

        [$sql] = query::from_builder($builder)->build();

        $this->assertEquals("SELECT {$table}.* FROM {{$this->table_name}} {$table} FULL JOIN {{$this->another_table_name}} \"{$this->another_table_name}\" ON {$table}.id < \"{$this->another_table_name}\".scone WHERE 1 = 1 AND {$table}.scones IS NOT NULL ORDER BY {$table}.scones ASC", $sql);
    }

    public function test_it_generates_sql_for_having_clause() {
        $table = "\"{$this->table_name}\"";

        $p1 = null;

        // Over complicated having thing to cover having, having_raw, or_having, or_having_raw and variations with
        // condition passed as closure as well.
        $builder = $this->new_test_where_builder()
            ->having('sum(price)', '=', true)
            ->having(
                function (builder $builder) use (&$p1) {
                    $builder->where('sum(margin)', false)
                        ->or_where_raw('something = :else', $p1 = ['else' => 'something'])
                        ->where('useless_field', null);
                }
            )
            ->having_raw('one_field < another_field')
            ->or_having(
                function (builder $builder) {
                    $builder->where('x', true);
                }
            )
            ->or_having('z', [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11])
            ->or_having_raw('oh = :really', $p2 = [':really' => 'grumpy cat picture'])
            ->where_raw('x = y')
            ->order_by('avg(price)');

        [$sql, $params] = query::from_builder($builder)->build();

        $this->assertCount(2, $params);
        $this->assertEquals(array_merge($p1, $p2), $params);
        $this->assertEquals("SELECT {$table}.* FROM {{$this->table_name}} {$table} WHERE 1 = 1 AND x = y HAVING sum({$table}.price) = 1 AND (sum({$table}.margin) = 0 OR something = :else AND {$table}.useless_field IS NULL) AND one_field < another_field OR ({$table}.x = 1) OR {$table}.z IN ('1','2','3','4','5','6','7','8','9','10','11') OR oh = :really ORDER BY avg({$table}.price) ASC", $sql);
    }

    public function test_it_doesnt_permit_nested_having() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Cannot have having on a nested builder');
        builder::table('user')->nested_where(
            function (builder $builder) {
                $builder->having('deleted', 0);
            }
        );
    }

    public function test_it_generates_sql_for_union_queries() {

        $united = builder::table($this->another_table_name)
            ->where('x', false)
            ->or_where('field', 'param')
            ->order_by('oo_made_up_column');

        $united_all = builder::table($made_up_table = $this->table_name . '2')
            ->where(
                function (builder $builder) {
                    $builder->where_raw('2 = 2')
                        ->or_where('z', true);
                }
            )
            ->where('of', 'op')
            ->order_by('made_up_column');

        $builder = $this->new_test_where_builder()
            ->union($united)
            ->union_all($united_all)
            ->where('another_field', 'another_value')
            ->order_by('oc');

        [$sql, $params] = query::from_builder($builder)->build();

        $param_names = array_keys($params);

        $this->assertCount(3, $params);
        $this->assertEquals(array_values($params), ['another_value', 'param', 'op']);
        $this->assertEquals(
            "SELECT \"{$this->table_name}\".* FROM " .
            "{{$this->table_name}} \"{$this->table_name}\" WHERE 1 = 1 AND \"{$this->table_name}\".another_field = :{$param_names[0]} " .
            "UNION SELECT \"{$this->another_table_name}\".* FROM {{$this->another_table_name}} \"{$this->another_table_name}\" WHERE \"{$this->another_table_name}\".x = 0 OR \"{$this->another_table_name}\".field = :{$param_names[1]} " .
            "UNION ALL SELECT \"{$made_up_table}\".* FROM {{$made_up_table}} \"{$made_up_table}\" WHERE (2 = 2 OR \"{$made_up_table}\".z = 1) AND \"{$made_up_table}\".of = :{$param_names[2]} " .
            "ORDER BY \"{$this->table_name}\".oc ASC",
            $sql
        );
    }

    public function test_union_valid_arguments() {
        $builder = builder::table('foo');
        $this->assertSame('SELECT "foo".* FROM {foo} "foo" WHERE 1 = 1', query::from_builder($builder)->build()[0]);

        // Test callable that introduces a union.
        $builder->union(function(builder $builder) {
            $builder->select('*')->from('bar');
        });
        $this->assertSame('SELECT "foo".* FROM {foo} "foo" WHERE 1 = 1 UNION SELECT {bar}.* FROM {bar} WHERE 1 = 1', query::from_builder($builder)->build()[0]);

        // Test null resets unions while we're here.
        $builder->union(null);
        $this->assertSame('SELECT "foo".* FROM {foo} "foo" WHERE 1 = 1', query::from_builder($builder)->build()[0]);

        // Test we can't use strings for the union.
        $builder->union(null);
        $this->assertSame('SELECT "foo".* FROM {foo} "foo" WHERE 1 = 1', query::from_builder($builder)->build()[0]);
    }

    public function test_union_callback_empty() {
        // Test callable that does nothing.
        $builder = builder::table('foo');
        $this->assertSame('SELECT "foo".* FROM {foo} "foo" WHERE 1 = 1', query::from_builder($builder)->build()[0]);
        $builder->union(function() {});

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Table name can not be empty');
        query::from_builder($builder)->build();
    }

    public function test_union_string() {
        // Test callable that does nothing.
        $builder = builder::table('foo');
        $this->assertSame('SELECT "foo".* FROM {foo} "foo" WHERE 1 = 1', query::from_builder($builder)->build()[0]);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage(' You must pass an instance of builder or a callable to the union functions, string given');
        $builder->union('SELECT {bar}.* FROM {bar} WHERE 1 = 1');
    }

    public function test_union_callback_resetting_builder() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage(' The callback must not reset builder');
        $builder = builder::table('foo');
        $builder->union(function(&$builder) {
            $builder = new \stdClass;
        });
    }

    public function test_it_generates_unnecessary_complex_query() {
        $united1 = builder::table($this->another_table_name)
            ->where('u1_f1', '>', 69)
            ->or_where('u1_f2', 'p1_u1_f2');

        $united2 = builder::table($this->another_table_name)
            ->where('u2_f1', '<', '123')
            ->where('u2_f2', 'p1_u2_f2');

        $builder = (new builder())
            ->from($this->table_name)
            ->add_select('*')
            ->add_select('field as another_field')
            ->add_select('a.b')
            ->add_select('sum(price) as sp')
            ->add_select('sum(margin)')
            ->add_select('normal')
            ->add_select_raw('(select sum(fs) FROM {t} WHERE sf LIKE :spi as f', ['spi' => 'val'])
            ->where(
                function (builder $builder) {
                    $builder->where('nf1', 'nv1')
                        ->or_where_field('nf2', 'nf3');
                }
            )
            ->where('f', 'v')
            ->where(
                function (builder $builder) {
                    $builder->where_raw('field IN (1, 2, 3, 4, 5)')
                        ->where('f3', '<>', 96)
                        ->where(
                            function (builder $builder) {
                                $builder->where('nf', null)
                                    ->or_where('nnf', '!=', null)
                                    ->or_where('x', 'z')
                                    ->or_where_null('wnn')
                                    ->where_raw('')
                                    ->cross_join('not_joined');
                            }
                        );
                }
            )
            ->join($this->another_table_name, 'field', '!=', 'another_field')
            ->right_join(
                $this->another_table_name . '2',
                function (builder $builder) {
                    $builder->where('jf', '<>', false)
                        ->or_where_field('jf2', 'jf3')
                        ->or_where_field('sum(margin)', '>', 'avg(price)');
                }
            )
            ->having(
                function (builder $builder) {
                    $builder->where('sum(price)', '>', 1000)
                        ->or_where('sum(margin)', '<', '40');
                }
            )
            ->or_having('field', 'value')
            ->group_by(['will', 'not', 'be', 'there'])
            ->reset_group_by()
            ->group_by(['f1', 'f2', 'p.f3'])
            ->group_by('f4')
            ->group_by_raw('f5')
            ->order_by('oc', 'desc')
            ->union_all($united1)
            ->reset_union()
            ->union_all($united1)
            ->union($united2)
            ->offset(10)
            ->limit(20);

        [$sql, $params, $from, $count] = query::from_builder($builder)->build();

        $param_names = array_keys($params);
        $this->assertCount(12, $params);
        $this->assertEquals(['val', 'nv1', 'v', 96, 'z', 69, 'p1_u1_f2', '123', 'p1_u2_f2', 1000, '40', 'value'], array_values($params));

        // To avoid one extremely long line, let's reproduce it piece by piece
        $select = "{test__qb}.*, {test__qb}.field as another_field, a.b, sum({test__qb}.price) as sp, sum({test__qb}.margin), {{$this->table_name}}.normal, (select sum(fs) FROM {t} WHERE sf LIKE :spi as f";

        $u1_sql = "UNION ALL SELECT \"{$this->another_table_name}\".* FROM {{$this->another_table_name}} \"{$this->another_table_name}\" WHERE \"{$this->another_table_name}\".u1_f1 > :{$param_names[5]} OR \"{$this->another_table_name}\".u1_f2 = :{$param_names[6]}";

        $u2_sql = "UNION SELECT \"{$this->another_table_name}\".* FROM {{$this->another_table_name}} \"{$this->another_table_name}\" WHERE \"{$this->another_table_name}\".u2_f1 < :{$param_names[7]} AND \"{$this->another_table_name}\".u2_f2 = :{$param_names[8]}";

        $joins = "INNER JOIN {{$this->another_table_name}} \"{$this->another_table_name}\" ON {{$this->table_name}}.field <> \"{$this->another_table_name}\".another_field " .
            "RIGHT JOIN {{$this->another_table_name}2} \"{$this->another_table_name}2\" ON \"{$this->another_table_name}2\".jf <> 0 OR \"{$this->another_table_name}2\".jf2 = \"{$this->another_table_name}2\".jf3 OR sum(\"{$this->another_table_name}2\".margin) > avg(\"{$this->another_table_name}2\".price)";

        $where = "WHERE ({{$this->table_name}}.nf1 = :{$param_names[1]} OR {{$this->table_name}}.nf2 = {{$this->table_name}}.nf3) AND {{$this->table_name}}.f = :{$param_names[2]} AND " .
            "(field IN (1, 2, 3, 4, 5) AND {{$this->table_name}}.f3 <> :{$param_names[3]} AND ({{$this->table_name}}.nf IS NULL OR {{$this->table_name}}.nnf IS NOT NULL OR {{$this->table_name}}.x = :{$param_names[4]} OR {{$this->table_name}}.wnn IS NULL AND 1 = 1))";

        $having = "HAVING (sum({test__qb}.price) > :{$param_names[9]} OR sum({test__qb}.margin) < :{$param_names[10]}) OR {{$this->table_name}}.field = :{$param_names[11]}";

        $group_by = "GROUP BY {{$this->table_name}}.f1, {{$this->table_name}}.f2, p.f3, {{$this->table_name}}.f4, f5";

        $this->assertEquals(10, $from);
        $this->assertEquals(20, $count);

        $this->assertEquals("SELECT {$select} FROM {{$this->table_name}} ${joins} {$where} {$group_by} {$having} {$u1_sql} {$u2_sql} ORDER BY {{$this->table_name}}.oc DESC", $sql);
    }

    public function test_it_generates_count_sql() {
        $builder = $this->new_test_where_builder('al')
            ->select('whhaaaat')
            ->where('attr', 'value')
            ->order_by('col')
            ->having((new field('avg(mycount)'))->do_not_prefix(), '<>', true);

        [$sql, $params] = query::from_builder($builder)->build(true);

        $param_names = array_keys($params);
        $this->assertEquals(['value'], array_values($params));
        $this->assertEquals(
            "SELECT COUNT(*) FROM (SELECT \"al\".whhaaaat FROM {test__qb} \"al\" WHERE 1 = 1 AND \"al\".attr = :{$param_names[0]} HAVING avg(mycount) <> 1) cnt",
            $sql
        );
    }

    public function test_it_generates_count_sql_with_group_by() {
        $builder = $this->new_test_where_builder('al')
            ->select('whhaaaat')
            ->where('attr', 'value')
            ->group_by('grouped')
            ->order_by('col')
            ->having('avg(val)', '<>', true);

        [$sql, $params] = query::from_builder($builder)->build(true);

        $param_names = array_keys($params);
        $this->assertEquals(['value'], array_values($params));
        $this->assertEquals(
            "SELECT COUNT(*) FROM (SELECT \"al\".whhaaaat FROM {{$this->table_name}} \"al\" WHERE 1 = 1 AND \"al\".attr = :{$param_names[0]} GROUP BY \"al\".grouped HAVING avg(\"al\".val) <> 1) cnt",
            $sql
        );

        // Reset group by
        $builder->group_by(null);
        [$sql, $params] = query::from_builder($builder)->build(true);
        $param_names = array_keys($params);
        $this->assertEquals(['value'], array_values($params));
        $this->assertEquals("SELECT COUNT(*) FROM (SELECT \"al\".whhaaaat FROM {test__qb} \"al\" WHERE 1 = 1 AND \"al\".attr = :{$param_names[0]} HAVING avg(\"al\".val) <> 1) cnt", $sql);
    }

    public function test_it_generates_count_sql_with_distinct_keyword() {
        $builder = $this->new_test_where_builder('al')
            ->select_raw('DISTINCT whhaaaat')
            ->where('attr', 'value')
            ->order_by('col');

        [$sql, $params] = query::from_builder($builder)->build(true);

        $param_names = array_keys($params);
        $this->assertEquals(['value'], array_values($params));
        $this->assertEquals(
            "SELECT COUNT(*) FROM (SELECT DISTINCT whhaaaat FROM {{$this->table_name}} \"al\" WHERE 1 = 1 AND \"al\".attr = :{$param_names[0]}) cnt",
            $sql
        );
    }

    public function test_it_generates_count_sql_with_distinct_and_space_keyword() {
        $builder = $this->new_test_where_builder('john')
            ->select_raw(" DISTINCT john.cena");

        [$sql,] = query::from_builder($builder)->build(true);
        $this->assertEquals(
            "SELECT COUNT(*) FROM (SELECT  DISTINCT john.cena FROM {test__qb} \"john\" WHERE 1 = 1) cnt",
            $sql
        );
    }

    public function test_it_generates_count_sql_with_distinct_in_field() {
        $builder = $this->new_test_where_builder('al')
            ->select_raw('distinctive_mushroom')
            ->where('attr', 'value')
            ->order_by('col')
            ->having('avg(val)', '<>', true);

        [$sql, $params] = query::from_builder($builder)->build(true);

        $param_names = array_keys($params);
        $this->assertEquals(['value'], array_values($params));
        $this->assertEquals(
            "SELECT COUNT(*) FROM (SELECT distinctive_mushroom FROM {test__qb} \"al\" WHERE 1 = 1 AND \"al\".attr = :{$param_names[0]} HAVING avg(\"al\".val) <> 1) cnt",
            $sql
        );
    }

    public function test_it_throws_exception_on_bad_joins() {
        $builder = $this->new_test_where_builder()
            ->join('table_1', 'field_1', 'field_2')
            ->join('table_1', 'field_1', 'field_2');

        $this->expectException('coding_exception');
        query::from_builder($builder)->build();
    }

    public function test_it_joins_tables_with_different_aliases() {
        $builder = $this->new_test_where_builder()
            ->join((new table('table_1'))->as('tbl_1'), 'field_1', 'field_2')
            ->right_join('table_1', 'field_1', 'field_2');

        [$sql, $params] = query::from_builder($builder)->build();

        $this->assertEmpty($params);
        $this->assertEquals("SELECT \"{$this->table_name}\".* FROM {{$this->table_name}} \"{$this->table_name}\" INNER JOIN {table_1} \"tbl_1\" ON \"{$this->table_name}\".field_1 = \"tbl_1\".field_2 RIGHT JOIN {table_1} \"table_1\" ON \"{$this->table_name}\".field_1 = \"table_1\".field_2 WHERE 1 = 1", $sql);

        $builder = $this->new_test_where_builder()
            ->join(['table_1', 't1'], 'field_1', 'field_2')
            ->right_join('table_1', 'field_1', 'field_2');

        [$sql, $params] = query::from_builder($builder)->build();

        $this->assertEmpty($params);
        $this->assertEquals("SELECT \"{$this->table_name}\".* FROM {{$this->table_name}} \"{$this->table_name}\" INNER JOIN {table_1} \"t1\" ON \"{$this->table_name}\".field_1 = \"t1\".field_2 RIGHT JOIN {table_1} \"table_1\" ON \"{$this->table_name}\".field_1 = \"table_1\".field_2 WHERE 1 = 1", $sql);
    }

    public function test_it_joins_subqueries() {
        $sub_query = $this->new_test_where_builder();

        $builder = $this->new_test_where_builder()
            ->join((new table($sub_query))->as('tbl_1'), 'field_1', 'field_2')
            ->right_join('table_1', 'field_1', 'field_2');

        [$sql, $params] = query::from_builder($builder)->build();

        $this->assertEmpty($params);
        $this->assertEquals("SELECT \"{$this->table_name}\".* FROM {{$this->table_name}} \"{$this->table_name}\" INNER JOIN (SELECT \"{$this->table_name}\".* FROM {{$this->table_name}} \"{$this->table_name}\" WHERE 1 = 1) \"tbl_1\" ON \"{$this->table_name}\".field_1 = \"tbl_1\".field_2 RIGHT JOIN {table_1} \"table_1\" ON \"{$this->table_name}\".field_1 = \"table_1\".field_2 WHERE 1 = 1", $sql);
    }

    public function test_table_alias_invalid_characters() {
        $valid_alias = 'my_Alias133';
        $invalid_alias = 'my-alias !@$';

        $table = new table('test_table');
        $table->as($valid_alias);

        $this->assertEquals($valid_alias, $table->get_alias());

        try {
            $table->as($invalid_alias);
            $this->fail('Setting alias with disallowed characters should fail.');
        } catch (Exception $exception) {
            $this->assertInstanceOf(coding_exception::class, $exception);
            $this->assertMatchesRegularExpression('/Table aliases can only be alpha numeric with underscores/', $exception->getMessage());
        }

        $builder = builder::table('test_table');
        $builder->as($valid_alias);

        $this->assertEquals($valid_alias, $builder->get_alias());
        try {
            $builder->as($invalid_alias);
            $this->fail('Setting alias with disallowed characters should fail.');
        } catch (Exception $exception) {
            $this->assertInstanceOf(coding_exception::class, $exception);
            $this->assertMatchesRegularExpression('/Table aliases can only be alpha numeric with underscores/', $exception->getMessage());
        }
    }

    public function test_table_can_have_alias() {
        $table = new table('table');

        $this->assertEquals('{table}', $table->sql());
        $this->assertEmpty($table->get_alias());
        $this->assertFalse($table->has_alias());
        $this->assertEquals('{table} "a"', $table->as('a')->sql());
        $this->assertTrue($table->has_alias());
        $this->assertEquals('a', $table->get_alias());

        // It shouldn't allow weird table names
        $this->expectException(coding_exception::class);
        new table('$$$');
    }

    public function test_it_throws_exception_when_table_name_is_invalid() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Table name can only be alpha numeric with underscores');

        builder::table('weird table');
    }

    public function test_it_generates_group_concat_sql() {
        $field = 'field';
        $separator = '; ';
        $order_by = 'f ASC';

        $this->assertEquals($this->db()->sql_group_concat($field, $separator, $order_by), builder::group_concat($field, $separator, $order_by));
    }

    public function test_I_cant_change_an_alias_once_set() {
        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage('Can not reset an alias which has already been set');
        $builder = builder::table('foo', 'bar')->as('foo');
        query::from_builder($builder)->build();
        $this->fail('Exception expected');
    }

    public function test_I_can_change_an_alias_from_default() {
        $builder = builder::table('foo')->as('bar');
        query::from_builder($builder)->build();
        [$sql, $params] = query::from_builder($builder)->build();
        $this->assertSame('SELECT "bar".* FROM {foo} "bar" WHERE 1 = 1', $sql);
        $this->assertSame([], $params);
    }

    public function test_I_cant_change_an_alias_when_set_manually_to_default() {
        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage('Can not reset an alias which has already been set');
        $builder = builder::table('foo', 'foobar')->as('bar');
        query::from_builder($builder)->build();
        $this->fail('Exception expected');
    }

    public function test_I_can_change_an_alias_to_the_current_alias() {
        $builder = builder::table('foo', 'bar')->as('bar');
        [$sql, $params] = query::from_builder($builder)->build();
        $this->assertSame('SELECT "bar".* FROM {foo} "bar" WHERE 1 = 1', $sql);
        $this->assertSame([], $params);
    }

    public function test_alias_applies_to_having() {
        $builder = builder::table('foo')
            ->having('deleted', 1)
            ->as('bar');
        [$sql, $params] = query::from_builder($builder)->build();
        $this->assertSame('SELECT "bar".* FROM {foo} "bar" WHERE 1 = 1 HAVING "bar".deleted = :'.key($params), $sql);
        $this->assertSame([1], array_values($params));

        $builder = builder::create()
            ->having('deleted', 1)
            ->from('foo', 'bar');
        [$sql, $params] = query::from_builder($builder)->build();
        $this->assertSame('SELECT "bar".* FROM {foo} "bar" WHERE 1 = 1 HAVING "bar".deleted = :'.key($params), $sql);
        $this->assertSame([1], array_values($params));
    }
}
