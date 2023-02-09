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
use core\orm\query\sql\where;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/tests/orm_query_builder_base.php');

/**
 * Class core_orm_builder_exists_sql_testcase
 *
 * @package core
 * @group orm
 */
class core_orm_query_builder_exists_sql_testcase extends orm_query_builder_base {

    public function test_it_generates_where_clause_for_exists() {
        $subquery_builder = (new builder())
            ->from('subquery')
            ->as('sub')
            ->where_raw('id = main.id');

        $builder = $this->new_test_where_builder('main')
            ->where('', 'exists', $subquery_builder);

        [$sql, $params] = where::from_builder($builder)->build();

        $this->assertCount(0, $params);

        $this->assertEquals("1 = 1 AND EXISTS (SELECT \"sub\".* FROM {subquery} \"sub\" WHERE id = main.id)", $sql);

        $builder = $this->new_test_where_builder('main')
            ->where('', '!exists', $subquery_builder);

        [$sql, $params] = where::from_builder($builder)->build();

        $this->assertCount(0, $params);

        $this->assertEquals("1 = 1 AND NOT EXISTS (SELECT \"sub\".* FROM {subquery} \"sub\" WHERE id = main.id)", $sql);

        $builder = $this->new_test_where_builder('main')
            ->or_where('', 'exists', $subquery_builder);

        [$sql, $params] = where::from_builder($builder)->build();

        $this->assertCount(0, $params);

        $this->assertEquals("1 = 1 OR EXISTS (SELECT \"sub\".* FROM {subquery} \"sub\" WHERE id = main.id)", $sql);

        $builder = $this->new_test_where_builder('main')
            ->or_where('', '!exists', $subquery_builder);

        [$sql, $params] = where::from_builder($builder)->build();

        $this->assertCount(0, $params);

        $this->assertEquals("1 = 1 OR NOT EXISTS (SELECT \"sub\".* FROM {subquery} \"sub\" WHERE id = main.id)", $sql);
    }

    public function test_it_generates_where_clause_for_exists_using_shortcut() {
        $subquery_builder = (new builder())
            ->from('subquery')
            ->as('sub')
            ->where_raw('id = main.id');

        $builder = $this->new_test_where_builder('main')
            ->where_exists(function (builder $b) {
                $b->from('subquery')
                    ->as('sub')
                    ->where_raw('id = main.id');
            });

        [$sql, $params] = where::from_builder($builder)->build();

        $this->assertCount(0, $params);

        $this->assertEquals("1 = 1 AND EXISTS (SELECT \"sub\".* FROM {subquery} \"sub\" WHERE id = main.id)", $sql);

        $builder = $this->new_test_where_builder('main')
            ->where_not_exists($subquery_builder);

        [$sql, $params] = where::from_builder($builder)->build();

        $this->assertCount(0, $params);

        $this->assertEquals("1 = 1 AND NOT EXISTS (SELECT \"sub\".* FROM {subquery} \"sub\" WHERE id = main.id)", $sql);

        $builder = $this->new_test_where_builder('main')
            ->or_where_exists($subquery_builder);

        [$sql, $params] = where::from_builder($builder)->build();

        $this->assertCount(0, $params);

        $this->assertEquals("1 = 1 OR EXISTS (SELECT \"sub\".* FROM {subquery} \"sub\" WHERE id = main.id)", $sql);

        $builder = $this->new_test_where_builder('main')
            ->or_where_not_exists($subquery_builder);

        [$sql, $params] = where::from_builder($builder)->build();

        $this->assertCount(0, $params);

        $this->assertEquals("1 = 1 OR NOT EXISTS (SELECT \"sub\".* FROM {subquery} \"sub\" WHERE id = main.id)", $sql);
    }

    public function test_where_exists_only_accepts_valid_builder() {

        $subquery_builder = (new builder())
            ->from('subquery', 'sub')
            ->where_raw('id = main.id');
        // Check it accepts a builder.
        $builder = (builder::table('main'))->where_exists($subquery_builder);
        [$sql, $params] = where::from_builder($builder)->build();
        $this->assertCount(0, $params);
        $this->assertEquals('EXISTS (SELECT "sub".* FROM {subquery} "sub" WHERE id = main.id)', $sql);

        // Check it accepts a builder.
        $builder = (builder::table('main'))
            ->where_exists(function (builder $b) {
                $b->from('subquery')
                    ->as('sub')
                    ->where_raw('id = main.id');
            });
        [$sql, $params] = where::from_builder($builder)->build();
        $this->assertCount(0, $params);
        $this->assertEquals('EXISTS (SELECT "sub".* FROM {subquery} "sub" WHERE id = main.id)', $sql);

        // Check it does not accept a string.
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Either a builder instance of a callback should be passed to where_exists.');
        (builder::table('main'))->where_exists('SELECT "sub".* FROM {subquery} "sub" WHERE id = main.id)');
    }

}
