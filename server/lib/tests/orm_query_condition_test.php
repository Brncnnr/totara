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

use core\orm\query\builder;
use core\orm\query\condition;
use core\orm\query\queryable;

defined('MOODLE_INTERNAL') || die();

/**
 * Class core_orm_condition_testcase
 *
 * @package core
 * @group orm
 */
class core_orm_query_condition_testcase extends advanced_testcase {

    /**
     * @return \moodle_database
     */
    protected function db() {
        return $GLOBALS['DB'];
    }

    public function test_it_conforms_to_queryable_interface() {

        $condition = new condition();

        $this->assertInstanceOf(queryable::class, $condition, 'Condition should be queryable');
    }

    public function test_it_returns_raw_sql_query() {

        $condition = (new condition())->set_raw('myfield = :param_1', ['param_1' => 'value']);
        $this->assertEquals(['myfield = :param_1', ['param_1' => 'value']], $condition->where_sql());

        $condition = (new condition())->set_raw('myfield = "I am brave"');
        $this->assertEquals(['myfield = "I am brave"', []], $condition->where_sql());
    }

    public function test_it_generates_sql_for_db_field() {
        $operators = ['=', '<>', '!=', '>', '<', '>=', '<='];

        foreach ($operators as $operator) {
            $condition = (new condition())
                ->set_is_raw_field(true)
                ->set_field('source')
                ->set_operator($operator)
                ->set_value('target');

            // Normalizing operator
            if ($operator == '!=') {
                $operator = '<>';
            }

            [$sql, $params] = $condition->where_sql();

            $this->assertEquals("source {$operator} target", $sql);
            $this->assertEmpty($params);
        }
    }

    public function test_it_fails_for_raw_field_with_unsupported_operator() {
        $operators =  ['in', 'like', 'ilike', '!like', '!ilike', 'exists', '!exists'];

        foreach ($operators as $operator) {
            $condition = (new condition())
                ->set_is_raw_field(true)
                ->set_field('source')
                ->set_operator($operator)
                ->set_value('value');

            try {
                $condition->where_sql();
                $this->fail('Condition should fail with unsupported operator');
            } catch (Exception $e) {
                $this->assertInstanceOf(coding_exception::class, $e);
                $msg = 'Comparing fields supported only with =, !=, <, >, <=, >=.';
                $this->assertMatchesRegularExpression('/' . preg_quote($msg) . '/', $e->getMessage());
            }
        }
    }

    public function test_it_validates_allowed_operators() {
        $condition = (new condition())
            ->set_field('source')
            ->set_operator('foo')
            ->set_value('value');

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Condition must be one of the following');

        $condition->where_sql();
    }

    public function test_it_fails_with_unsupported_value_type() {
        $object = new stdClass();
        $object->foo = 'bar';

        $condition = (new condition())
            ->set_field('source')
            ->set_operator('=')
            ->set_value($object);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('The combination of operator and value is not supported.');

        $condition->where_sql();
    }

    public function test_it_generates_sql_for_equals_to_string_value() {
        $operators = ['=', '<>', '!=', '<', '>', '<=', '>='];

        foreach ($operators as $operator) {
            $condition = (new condition())
                ->set_field('source')
                ->set_operator($operator)
                ->set_value('value');

            // Normalizing operator
            if ($operator == '!=') {
                $operator = '<>';
            }

            [$sql, $params] = $condition->where_sql();

            $this->assertCount(1, $params);

            $param = array_keys($params)[0];

            $this->assertEquals('value' , $params[$param]);
            $this->assertEquals("source {$operator} :{$param}", $sql);
        }
    }

    public function test_it_generates_sql_for_like_to_string_value() {

        $operators = [
            'like' => [
                'case_sensitive' => true,
                'not' => false,
            ],
            'ilike' => [
                'case_sensitive' => false,
                'not' => false,
            ],
            '!like' => [
                'case_sensitive' => true,
                'not' => true,
            ],
            '!ilike' => [
                'case_sensitive' => false,
                'not' => true,
            ],
        ];

        foreach ($operators as $operator => $values) {
            $condition = (new condition())
                ->set_field('source')
                ->set_operator($operator)
                ->set_value('value');

            [$sql, $params] = $condition->where_sql();

            $this->assertCount(1, $params);

            $param = array_keys($params)[0];

            $like_sql = $this->db()->sql_like('source', ":{$param}", $values['case_sensitive'], true, $values['not']);

            $this->assertEquals('%value%', $params[$param]);
            $this->assertEquals($like_sql, $sql);
        }
    }

    public function test_it_generates_sql_for_like_to_integer_value() {
        $condition = (new condition())
            ->set_field('source')
            ->set_operator('like')
            ->set_value(27);

        [$sql, $params] = $condition->where_sql();

        $this->assertCount(1, $params);

        $param = array_keys($params)[0];

        $like_sql = $this->db()->sql_like('source', ":{$param}", true, true, false);

        $this->assertEquals('%27%', $params[$param]);
        $this->assertEquals($like_sql, $sql);
    }

    public function test_it_generates_sql_for_like_to_float_value() {
        $condition = (new condition())
            ->set_field('source')
            ->set_operator('like')
            ->set_value(27.1234);

        [$sql, $params] = $condition->where_sql();

        $this->assertCount(1, $params);

        $param = array_keys($params)[0];

        $like_sql = $this->db()->sql_like('source', ":{$param}", true, true, false);

        $this->assertEquals('%27.1234%', $params[$param]);
        $this->assertEquals($like_sql, $sql);
    }

    public function test_it_generates_sql_for_integer_value() {
        $operators =  ['=', '<>', '!=', '>', '<', '>=', '<='];

        foreach ($operators as $operator) {
            // Numeric string
            $condition = (new condition())
                ->set_field('source')
                ->set_operator($operator)
                ->set_value('45');

            [$sql, $params] = $condition->where_sql();

            $normalized_operator = ($operator == '!=') ? '<>' : $operator;

            $cond_param = array_keys($params)[0] ?? null;
            $this->assertEquals([$cond_param => '45'], $params);
            $this->assertEquals("source {$normalized_operator} :{$cond_param}", $sql);

            // True int value
            $condition = (new condition())
                ->set_field('source')
                ->set_operator($operator)
                ->set_value(27);

            [$sql, $params] = $condition->where_sql();

            $cond_param = array_keys($params)[0] ?? null;
            $this->assertEquals([$cond_param => 27], $params);
            $this->assertEquals("source {$normalized_operator} :{$cond_param}", $sql);
        }
    }

    public function test_it_generates_sql_for_float_value() {
        $operators =  ['=', '<>', '!=', '>', '<', '>=', '<='];

        foreach ($operators as $operator) {
            // Numeric string
            $condition = (new condition())
                ->set_field('source')
                ->set_operator($operator)
                ->set_value('45.1234');

            [$sql, $params] = $condition->where_sql();

            $normalized_operator = ($operator == '!=') ? '<>' : $operator;

            $cond_param = array_keys($params)[0] ?? null;
            $this->assertEquals([$cond_param => '45.1234'], $params);
            $this->assertEquals("source {$normalized_operator} :{$cond_param}", $sql);

            // True int value
            $condition = (new condition())
                ->set_field('source')
                ->set_operator($operator)
                ->set_value(27.1234);

            [$sql, $params] = $condition->where_sql();

            $cond_param = array_keys($params)[0] ?? null;
            $this->assertEquals([$cond_param => 27.1234], $params);
            $this->assertEquals("source {$normalized_operator} :{$cond_param}", $sql);
        }
    }

    public function test_it_generates_sql_for_boolean_value() {

        $operators = ['=', '<>', '!='];

        foreach ($operators as $operator) {
            // True
            $condition = (new condition())
                ->set_field('source')
                ->set_operator($operator)
                ->set_value(true);

            [$sql, $params] = $condition->where_sql();

            $normalized_operator = ($operator == '!=') ? '<>' : $operator;

            $this->assertEmpty($params);
            $this->assertEquals("source {$normalized_operator} 1", $sql);

            // False
            $condition = (new condition())
                ->set_field('source')
                ->set_operator($operator)
                ->set_value(false);

            [$sql, $params] = $condition->where_sql();

            $this->assertEmpty($params);
            $this->assertEquals("source {$normalized_operator} 0", $sql);
        }
    }

    public function test_it_fails_for_bool_field_with_unsupported_operator() {
        $operators =  ['in', '<', '>', '<=', '>=', 'like', 'ilike', '!like', '!ilike', 'exists', '!exists'];

        foreach ($operators as $operator) {
            $condition = (new condition())
                ->set_field('source')
                ->set_operator($operator)
                ->set_value(true);

            try {
                $condition->where_sql();
                $this->fail('Condition should fail with unsupported operator');
            } catch (Exception $e) {
                $this->assertInstanceOf(coding_exception::class, $e);
                $msg = 'Comparing boolean supported only with =, != (<>)';
                $this->assertMatchesRegularExpression('/' . preg_quote($msg) . '/', $e->getMessage());
            }
        }
    }

    public function test_it_generates_sql_for_null_value() {
        $operators =  ['=', '<>', '!='];

        foreach ($operators as $operator) {
            $condition = (new condition())
                ->set_field('source')
                ->set_operator($operator)
                ->set_value(null);

            // Normalize operator
            $operator = ($operator == '=') ? 'IS NULL' : 'IS NOT NULL';

            [$sql, $params] = $condition->where_sql();

            $this->assertEmpty($params);
            $this->assertEquals("source {$operator}", $sql);
        }
    }

    public function test_it_fails_for_null_value_with_unsupported_operator() {
        $operators =  ['<', '>', '<=', '>=', 'like', 'ilike', '!like', '!ilike', 'exists', '!exists'];

        foreach ($operators as $operator) {
            $condition = (new condition())
                ->set_field('source')
                ->set_operator($operator)
                ->set_value(null);

            try {
                $condition->where_sql();
                $this->fail('Condition should fail with unsupported operator');
            } catch (Exception $e) {
                $this->assertInstanceOf(coding_exception::class, $e);
                $msg = 'Comparing NULLs supported only with = or !=.';
                $this->assertMatchesRegularExpression('/' . preg_quote($msg) . '/', $e->getMessage());
            }
        }
    }

    public function test_it_generates_sql_for_array_values() {
        $operators = ['=', '!=', '<>', 'in'];

        // Handling empty arrays
        foreach ($operators as $operator) {
            $condition = (new condition())
                ->set_field('source_field')
                ->set_operator($operator)
                ->set_value([]);

            // Normalizing operator
            $operator = in_array($operator, ['=', 'in']) ? '1 = 2' : '1 = 1';

            [$sql, $params] = $condition->where_sql();

            $this->assertEmpty($params);
            $this->assertEquals($operator, $sql);
        }

        // Handling numeric values
        foreach ($operators as $operator) {
            $condition = (new condition())
                ->set_field('source_field')
                ->set_operator($operator)
                ->set_value([1, 2, 3, 4, 6, '7', 12, '123', 67, 89, 195, 123456]);

            // Normalizing operator
            $operator = in_array($operator, ['=', 'in']) ? 'IN' : 'NOT IN';

            [$sql, $params] = $condition->where_sql();

            $this->assertEmpty($params);
            $this->assertEquals("source_field $operator ('1','2','3','4','6','7','12','123','67','89','195','123456')", $sql);
        }

        // Handling parametrized values
        foreach ($operators as $operator) {
            $condition = (new condition())
                ->set_field('source_field')
                ->set_operator($operator)
                ->set_value(['abra', 'cadabra', ':-P', '*<8-)', '(=', '=)', '%-)']);

            // Normalizing operator
            $operator = in_array($operator, ['=', 'in']) ? 'IN' : 'NOT IN';

            [$sql, $params] = $condition->where_sql();

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

            $this->assertEquals("source_field {$operator} ({$keys})", $sql);
            $this->assertEquals(['abra', 'cadabra', ':-P', '*<8-)', '(=', '=)', '%-)'], array_values($params));
        }
    }

    public function test_it_fails_for_array_value_with_unsupported_operator() {
        $operators =  ['<', '>', '<=', '>=', 'like', 'ilike', '!like', '!ilike', 'exists', '!exists'];

        foreach ($operators as $operator) {
            $condition = (new condition())
                ->set_field('source')
                ->set_operator($operator)
                ->set_value(['abra', 'cadabra']);

            try {
                $condition->where_sql();
                $this->fail('Condition should fail with unsupported operator');
            } catch (Exception $e) {
                $this->assertInstanceOf(coding_exception::class, $e);
                $msg = 'Comparing arrays supported only with in, =, !=';
                $this->assertMatchesRegularExpression('/' . preg_quote($msg) . '/', $e->getMessage());
            }
        }
    }

    public function test_it_generates_sql_for_subqueries() {
        $operators = ['=', '!=', '<>', '>', '<', '>=', '<=', 'in'];

        // Handling simple sub-queries arrays
        foreach ($operators as $operator) {
            $sq = builder::table('sq_table')
                ->as('sq_table')
                ->select('a_field')
                ->where('another_field', '<', 69);

            $condition = (new condition())
                ->set_field('source_field')
                ->set_operator($operator)
                ->set_value($sq);

            // Normalizing operator
            $operator = ($operator == '!=') ? '<>' : $operator;

            [$sql, $params] = $condition->where_sql();

            $param_names = array_keys($params);

            $this->assertCount(1, $params);
            $this->assertEquals("source_field {$operator} (SELECT \"sq_table\".a_field FROM {sq_table} \"sq_table\" WHERE \"sq_table\".another_field < :{$param_names[0]})", $sql);
        }
    }

    public function test_is_fails_for_exists_operator_with_non_builder_value() {
        $condition = (new condition())
            ->set_field('source')
            ->set_operator('exists')
            ->set_value('value');

        $this->expectException(coding_exception::class);
        $msg = 'Comparing strings supported only with =, != (<>), <, >, <=, >= or (!)(i)like(_[raw|[starts|ends]_with]).';
        $this->expectExceptionMessage($msg);

        $condition->where_sql();
    }

    public function test_it_is_fluent() {
        $condition = new condition();

        $this->assertInstanceOf(condition::class, $condition);
        $this->assertSame($condition, $condition->set_operator('='));
        $this->assertSame($condition, $condition->set_value('value'));
        $this->assertSame($condition, $condition->set_is_raw_field(false));
        $this->assertSame($condition, $condition->set_field('field'));
        $this->assertSame($condition, $condition->set_params(['param' => 'value']));
        $this->assertSame($condition, $condition->set_raw('raw sql string', ['param' => 'value']));
        $this->assertSame($condition, $condition->set_aggregation(true));
    }

    public function test_parameter_counter_works() {

        // Real life example
        $condition1 = (new condition())
            ->set_field('field_1')
            ->set_operator('=')
            ->set_value('this is string value');

        $condition2 = (new condition())
            ->set_field('field_2')
            ->set_operator('=')
            ->set_value('this is another string value');

        [$sql1, $params1] = $condition1->where_sql();
        [$sql2, $params2] = $condition2->where_sql();

        $this->assertNotEquals($sql1, $sql2);

        $this->assertCount(1, $params1);
        $this->assertCount(1, $params2);

        $this->assertNotEquals(array_keys($params1), array_keys($params2));
    }

    public function test_it_returns_aggregation() {

        // True
        $condition = (new condition())->set_aggregation(true);
        $this->assertTrue($condition->get_aggregation());

        // False
        $condition->set_aggregation(false);
        $this->assertFalse($condition->get_aggregation());
    }

}
