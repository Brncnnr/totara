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
use core\orm\query\subquery;

defined('MOODLE_INTERNAL') || die();

/**
 * Class core_orm_subquery_testcase
 *
 * @package core
 * @group orm
 */
class core_orm_query_subquery_testcase extends advanced_testcase {

    public function test_it_creates_subquery_object_from_builder() {

        $b = builder::table('subtable');

        $subquery = new subquery($b, null);

        $this->assertInstanceOf(subquery::class, $subquery);
    }

    public function test_it_creates_subquery_object_from_callable() {

        $x = null;

        $subquery = new subquery(function (builder $builder) use (&$x) {
            $x = $builder;
            $builder = 5;
        }, null);

        $this->assertInstanceOf(subquery::class, $subquery);
        $this->assertEquals($x, $subquery->get_subquery());
    }

    public function test_it_passes_parent_builder() {
        $parent = builder::table('parent');
        $subquery = new subquery(function (builder $builder) {}, $parent);

        $this->assertEquals($parent, $subquery->get_builder());
    }

    public function test_it_does_not_like_incorrect_argument() {
        $this->expectException(coding_exception::class);

        new subquery('I am a string', null);
    }

    public function test_it_does_not_like_when_tampering_with_builder_in_callable() {
        $this->expectException(coding_exception::class);

        new subquery(function (builder &$builder) {
            $builder = 5;
        }, null);
    }

    public function test_it_sets_as_alias() {
        $subquery = new subquery(function (builder $builder) {});

        $subquery->as('my_alias');

        $this->assertEquals('my_alias', $subquery->get_field_as());
    }

    public function test_it_returns_const_values_for_overridden_methods() {
        $sq = new subquery(new builder());

        $this->assertEmpty($sq->get_field_agg());
        $this->assertEmpty($sq->get_field_alias());
        $this->assertEmpty($sq->get_prefix());
        $this->assertEmpty($sq->get_field_column());
        $this->assertEmpty($sq->get_field_as_is());
        $this->assertEmpty($sq->sql());
        $this->assertEmpty($sq->get_params());
        $this->assertTrue($sq->validate());
    }

    public function test_it_builds_query_from_subquery() {
        $subquery = new subquery(function (builder $b) {
            $b->from('sub_table')
                ->select('max(another_field)')
                ->where('field', 'value');
        });

        $subquery->as('x');

        [$sql, $params] = $subquery->build();

        $this->assertCount(1, $params);

        $param_name = array_keys($params)[0];

        $this->assertEquals('(SELECT max({sub_table}.another_field) FROM {sub_table} WHERE {sub_table}.field = :' . $param_name . ') as x', $sql);
        $this->assertEquals($params, [$param_name => 'value']);
    }

}