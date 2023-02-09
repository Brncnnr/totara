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

use core\orm\query\sql\where;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/tests/orm_query_builder_base.php');

/**
 * Class core_orm_builder_like_sql_testcase
 *
 * @package core
 * @group orm
 */
class core_orm_query_builder_like_sql_testcase extends orm_query_builder_base {

    /**
     * @dataProvider like_condition_data_provider
     * @param string $condition
     * @param string $value
     * @param string $expected_value
     */
    public function test_where_like(string $condition, string $value, string $expected_value) {
        $builder = $this->new_test_where_builder();
        $builder->where('column', $condition, $value);

        [$sql, $params] = where::from_builder($builder)->build();
        
        $cond_param = array_keys($params)[0] ?? null;
        $expected_params = [$cond_param => $expected_value];
        $this->assertEquals($expected_params, $params);

        $like_sql = $this->db()->sql_like(
            "\"{$this->table_name}\".column",
            ":{$cond_param}",
            strpos($condition, 'ilike') === false,
            true,
            strpos($condition, '!') === 0
        );

        $this->assertEquals("1 = 1 AND $like_sql", $sql);
    }

    /**
     * @dataProvider like_condition_data_provider
     * @param string $condition
     * @param string $value
     * @param string $expected_value
     */
    public function test_or_where_like(string $condition, string $value, string $expected_value) {
        $builder = $this->new_test_where_builder();
        $builder->or_where('column', $condition, $value);

        [$sql, $params] = where::from_builder($builder)->build();

        $cond_param = array_keys($params)[0] ?? null;
        $expected_params = [$cond_param => $expected_value];
        $this->assertEquals($expected_params, $params);

        $like_sql = $this->db()->sql_like(
            "\"{$this->table_name}\".column",
            ":{$cond_param}",
            strpos($condition, 'ilike') === false,
            true,
            strpos($condition, '!') === 0
        );

        $this->assertEquals("1 = 1 OR $like_sql", $sql);
    }

    /**
     * @dataProvider like_condition_shortcut_data_provider
     * @param string $condition
     * @param string $value
     * @param string $expected_value
     */
    public function test_where_like_shortcuts(string $condition, string $value, string $expected_value) {
        $method = 'where_'.$condition;

        $builder = $this->new_test_where_builder();
        $builder->$method('column', $value);

        [$sql, $params] = where::from_builder($builder)->build();

        $cond_param = array_keys($params)[0] ?? null;
        $expected_params = [$cond_param => $expected_value];
        $this->assertEquals($expected_params, $params);

        $like_sql = $this->db()->sql_like(
            "\"{$this->table_name}\".column",
            ":{$cond_param}",
            true,
            true,
            false
        );

        $this->assertEquals("1 = 1 AND $like_sql", $sql);
    }

    /**
     * @dataProvider like_condition_shortcut_data_provider
     * @param string $condition
     * @param string $value
     * @param string $expected_value
     */
    public function test_where_like_or(string $condition, string $value, string $expected_value) {
        $method = 'or_where_'.$condition;

        $builder = $this->new_test_where_builder();
        $builder->$method('column', $value);

        [$sql, $params] = where::from_builder($builder)->build();
        
        $cond_param = array_keys($params)[0] ?? null;
        $expected_params = [$cond_param => $expected_value];
        $this->assertEquals($expected_params, $params);

        $like_sql = $this->db()->sql_like(
            "\"{$this->table_name}\".column",
            ":{$cond_param}",
            true,
            true,
            false
        );

        $this->assertEquals("1 = 1 OR $like_sql", $sql);
    }

    /**
     * @return array
     */
    public function like_condition_data_provider() {
        return array_merge($this->like_condition_shortcut_data_provider(), [
            ['ilike', 'value', '%value%'],
            ['ilike', 'val%u_e', '%val\%u\_e%'],
            ['ilike_raw', 'val%ue', 'val%ue'],
            ['ilike_starts_with', 'value', 'value%'],
            ['ilike_starts_with', 'val%u_e', 'val\%u\_e%'],
            ['ilike_ends_with', 'value', '%value'],
            ['ilike_ends_with', 'val%u_e', '%val\%u\_e'],
            ['!like', 'value', '%value%'],
            ['!like_raw', 'val%ue', 'val%ue'],
            ['!like', 'val%u_e', '%val\%u\_e%'],
            ['!like_starts_with', 'value', 'value%'],
            ['!like_starts_with', 'val%u_e', 'val\%u\_e%'],
            ['!like_ends_with', 'value', '%value'],
            ['!like_ends_with', 'val%u_e', '%val\%u\_e'],
            ['!ilike', 'value', '%value%'],
            ['!ilike_raw', 'val%ue', 'val%ue'],
            ['!ilike', 'val%u_e', '%val\%u\_e%'],
            ['!ilike_starts_with', 'value', 'value%'],
            ['!ilike_starts_with', 'val%u_e', 'val\%u\_e%'],
            ['!ilike_ends_with', 'value', '%value'],
            ['!ilike_ends_with', 'val%u_e', '%val\%u\_e'],
        ]);
    }

    /**
     * @return array
     */
    public function like_condition_shortcut_data_provider() {
        return [
            ['like', 'value', '%value%'],
            ['like', '123456', '%123456%'],
            ['like', 'val%u_e', '%val\%u\_e%'],
            ['like_raw', 'val%ue', 'val%ue'],
            ['like_starts_with', 'value', 'value%'],
            ['like_starts_with', 'val%u_e', 'val\%u\_e%'],
            ['like_ends_with', 'value', '%value'],
            ['like_ends_with', 'val%u_e', '%val\%u\_e'],
        ];
    }

}
