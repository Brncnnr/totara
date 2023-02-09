<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package core
 * @group dml
 */

use core\collection;
use core\dml\pagination\offset_cursor_paginator;
use core\dml\sql;
use core\pagination\offset_cursor;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/tests/orm_query_builder_base.php');

class core_dml_offset_cursor_paginator_testcase extends orm_query_builder_base {

    public function test_get_all_items() {
        global $DB;

        $this->create_sample_records();

        $query = new sql("SELECT * FROM {{$this->table_name}} ORDER BY name");

        $expected_query = clone $query;

        $cursor = offset_cursor::create()
            ->set_page(1)
            ->set_limit(0);

        $paginator = new offset_cursor_paginator($query, $cursor);

        $result = $paginator->to_array();
        $this->assertEquals([
            'items',
            'total',
            'next_cursor',
        ], array_keys($result));

        $expected_result = $DB->get_records_sql($expected_query, null, 0, 0);

        $this->assertEquals($expected_result, $paginator->get_items()->all(true));
        $this->assertNull($paginator->get_next_cursor());
        $this->assertEquals($cursor, $paginator->get_current_cursor());
        $this->assertEquals(5, $paginator->get_total());

        // Check that items are collection and paginator is traversable
        $this->assertInstanceOf(collection::class, $paginator->get_items());

        // Check that count works
        $this->assertEquals(5, $paginator->count());
        $this->assertEquals(5, count($paginator));

        // Make sure we can do a foreach
        $expected_items = convert_to_array($expected_result);
        $prev_item = null;
        foreach ($paginator as $item) {
            $this->assertNotEquals($item, $prev_item);
            $this->assertInstanceOf(stdClass::class, $item);
            $this->assertContains((array)$item, $expected_items);
            $prev_item = $item;
        }
    }

    public function test_get_all_items_with_page_zero(): void {
        global $DB;
        $this->create_sample_records();

        $query = new sql("SELECT * FROM {{$this->table_name}} ORDER BY name");

        $expected_query = clone $query;

        $cursor = offset_cursor::create()
            ->set_page(0)
            ->set_limit(1);

        $paginator = new offset_cursor_paginator($query, $cursor);

        $result = $paginator->to_array();
        $this->assertEquals([
            'items',
            'total',
            'next_cursor',
        ], array_keys($result));

        $expected_result = $DB->get_records_sql($expected_query, null, 0, 0);

        $this->assertEquals($expected_result, $paginator->get_items()->all(true));
        $this->assertNull($paginator->get_next_cursor());
        $this->assertEquals($cursor, $paginator->get_current_cursor());
        $this->assertEquals(5, $paginator->get_total());

        // Check that items are collection and paginator is traversable
        $this->assertInstanceOf(collection::class, $paginator->get_items());

        // Check that count works
        $this->assertEquals(5, $paginator->count());
        $this->assertEquals(5, count($paginator));

        // Make sure we can do a foreach
        $expected_items = convert_to_array($expected_result);
        $prev_item = null;
        foreach ($paginator as $item) {
            $this->assertNotEquals($item, $prev_item);
            $this->assertInstanceOf(stdClass::class, $item);
            $this->assertContains((array)$item, $expected_items);
            $prev_item = $item;
        }
    }

    public function test_passing_invalid_cursor() {
        $query = new sql("SELECT * FROM {{$this->table_name}} ORDER BY name, id");

        $cursor = new stdClass();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Expected either null, encoded cursor or cursor object');

        new offset_cursor_paginator($query, $cursor);
    }

    public function test_passing_invalid_cursor_string() {
        $query = new sql("SELECT * FROM {{$this->table_name}} ORDER BY name, id");

        $cursor = "foobar";

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Invalid cursor given, expected array encoded as json and base64.');

        new offset_cursor_paginator($query, $cursor);
    }

    public function test_passing_no_cursor() {
        $this->create_sample_records();

        $query = new sql("SELECT * FROM {{$this->table_name}} ORDER BY name, id");

        $paginator = new offset_cursor_paginator($query);
        $this->assertCount(5, $paginator);
    }

    public function test_passing_invalid_query() {
        $query = 'thisisnoquery';

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Expected a \core\dml\sql object for paginating queries');

        new offset_cursor_paginator($query);
    }

    public function test_passing_string_cursor() {
        $this->create_sample_records();

        $query = new sql("SELECT * FROM {{$this->table_name}} ORDER BY name, id");

        $cursor = offset_cursor::create()
            ->set_page(1)
            ->set_limit(10);

        $paginator = new offset_cursor_paginator($query, $cursor->encode());

        $this->assertEquals($cursor, $paginator->get_current_cursor());
    }

    public function test_using_cursor_without_page() {
        $this->create_sample_records();

        $query = new sql("SELECT * FROM {{$this->table_name}} ORDER BY name, id");

        $cursor = offset_cursor::create()
            ->set_limit(10);

        $expected_cursor = clone $cursor;
        $expected_cursor->set_page(1);

        $paginator = new offset_cursor_paginator($query, $cursor);

        $this->assertEquals($expected_cursor, $paginator->get_current_cursor());
    }

    public function test_paged() {
        $this->create_sample_records();

        // The following order is expected:
        // - John
        // - Jane
        // - Peter
        // - Basil
        // - Roxanne

        $cursor = offset_cursor::create()
            ->set_page(1)
            ->set_limit(2);

        $query = new sql("SELECT * FROM {{$this->table_name}} ORDER BY id");

        // Let's start at the beginning, when we do not have a cursor yet
        $paginator = new offset_cursor_paginator($query, $cursor);

        // This should give us the first two records
        $this->assertEquals([
            'John',
            'Jane',
        ], $paginator->get_items()->pluck('name'));

        // Let's create the cursor we expect to come next, which is generated from the last record
        $expected_next_cursor = offset_cursor::create()
            ->set_page(2)
            ->set_limit(2);

        $this->assertEquals(2, count($paginator->get_items()));
        $this->assertEquals($cursor, $paginator->get_current_cursor());
        $this->assertEquals(5, $paginator->get_total());
        $this->assertEquals($expected_next_cursor, $paginator->get_next_cursor());

        $query = new sql("SELECT * FROM {{$this->table_name}} ORDER BY id");

        $cursor = $paginator->get_next_cursor();

        $paginator = new offset_cursor_paginator($query, $cursor);

        // Let's create the cursor we expect to come next, which is generated from the last record
        $expected_next_cursor = offset_cursor::create()
            ->set_page(3)
            ->set_limit(2);

        $this->assertEquals([
            'Peter',
            'Basil'
        ], $paginator->get_items()->pluck('name'));

        $this->assertEquals(2, count($paginator->get_items()));
        $this->assertEquals($cursor, $paginator->get_current_cursor());
        $this->assertEquals(5, $paginator->get_total());
        $this->assertEquals($expected_next_cursor, $paginator->get_next_cursor());

        $query = new sql("SELECT * FROM {{$this->table_name}} ORDER BY id");

        $cursor = $paginator->get_next_cursor();

        $paginator = new offset_cursor_paginator($query, $paginator->get_next_cursor());

        // Just one left
        $this->assertEquals(['Roxanne'], $paginator->get_items()->pluck('name'));
        $this->assertEquals(1, count($paginator->get_items()));
        $this->assertEquals($cursor, $paginator->get_current_cursor());
        $this->assertEquals(5, $paginator->get_total());
        $this->assertNull($paginator->get_next_cursor());
    }

    public function test_last_page_works() {
        $this->create_sample_records();

        // The following order is expected:
        // - Basil
        // - Jane
        // - John
        // - Peter
        // - Roxanne

        $query = new sql("SELECT * FROM {{$this->table_name}} ORDER BY name, id");

        // Let's just query the last page
        $cursor = offset_cursor::create()
            ->set_limit(2)
            ->set_page(3);

        $paginator = new offset_cursor_paginator($query, $cursor);

        $this->assertEquals([
            'Roxanne'
        ], $paginator->get_items()->pluck('name'));

        $this->assertEquals(1, count($paginator->get_items()));
        $this->assertEquals($cursor, $paginator->get_current_cursor());
        $this->assertEquals(5, $paginator->get_total());
        // It's the last page so next cursor should be null
        $this->assertNull($paginator->get_next_cursor());

        $query = new sql("SELECT * FROM {{$this->table_name}} ORDER BY name, id");

        // Let's have the last page end with the last record
        $cursor = offset_cursor::create()
            ->set_limit(1)
            ->set_page(5);

        $paginator = new offset_cursor_paginator($query, $cursor);

        $this->assertEquals([
            'Roxanne'
        ], $paginator->get_items()->pluck('name'));

        $this->assertEquals(1, count($paginator->get_items()));
        $this->assertEquals($cursor, $paginator->get_current_cursor());
        $this->assertEquals(5, $paginator->get_total());
        // It's the last page so next cursor should be null
        $this->assertNull($paginator->get_next_cursor());
    }

}
