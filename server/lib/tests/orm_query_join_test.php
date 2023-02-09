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
use core\orm\query\join;

defined('MOODLE_INTERNAL') || die();

/**
 * Class core_orm_qb_join_testcase
 *
 * @package core
 * @group orm
 */
class core_orm_query_join_testcase extends advanced_testcase {

    public function test_it_returns_join_object() {
        $join = new join();

        $this->assertInstanceOf(join::class, $join);
    }

    public function test_it_is_fluent() {
        $join = new join();

        $this->assertSame($join, $join->set_table('table'));
        $this->assertSame($join, $join->set_type('inner'));
    }

    public function test_it_sets_table_to_join() {
        $join = new join();

        $join->set_table('table_to_join');

        [$sql] = $join->join_sql();

        $this->assertEquals("INNER JOIN {table_to_join} \"table_to_join\"", $sql);
    }

    public function test_it_sets_join_type() {
        $join = new join();

        $join->set_table('table_to_join')
            ->set_type('left');

        [$sql] = $join->join_sql();

        $this->assertEquals("LEFT JOIN {table_to_join} \"table_to_join\"", $sql);
    }

    public function test_it_has_query_builder() {
        $join = new join();

        $this->assertInstanceOf(builder::class, $join->get_builder());
    }

    public function test_it_applies_conditions_from_query_builder() {
        $join = (new join())->set_type('right')->set_table('table_to_join');

        $join->get_builder()->where('condition1', '!=', 'param');

        [$sql, $params] = $join->join_sql();


        $this->assertCount(1, $params);
        $param = array_keys($params)[0];

        $this->assertEquals("RIGHT JOIN {table_to_join} \"table_to_join\" ON \"table_to_join\".condition1 <> :{$param}", $sql);
        $this->assertEquals('param', $params[$param]);
    }

    public function test_it_throws_exception_for_invalid_type() {
        $join = new join();

        $this->expectException('coding_exception');

        $join->set_type('middle');
    }
}
