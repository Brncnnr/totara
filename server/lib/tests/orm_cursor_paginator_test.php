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
 * @group orm
 */

use core\orm\collection;
use core\orm\entity\entity;
use core\orm\pagination\cursor_paginator;
use core\orm\query\builder;
use core\pagination\cursor;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/tests/orm_query_builder_base.php');

class core_orm_cursor_paginator_testcase extends orm_query_builder_base {

    public function test_get_all_items() {
        $this->create_sample_records();

        $query = builder::table($this->table_name)
            ->order_by('name');

        $expected_query = builder::table($this->table_name)
            ->order_by('name');

        $cursor = cursor::create()->set_limit(0);

        // no cursor and no limit means all items
        $paginator = new cursor_paginator($query, $cursor, true);

        $result = $paginator->to_array();
        $this->assertEquals([
            'items',
            'total',
            'next_cursor',
        ], array_keys($result));

        $expected_result = $expected_query->fetch();
        $expected_result = array_values($expected_result);

        $this->assertEquals($expected_result, $paginator->get_items()->all());
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

    public function test_order_is_required() {
        $query = builder::table($this->table_name);

        $cursor = cursor::create()
            ->set_limit(2)
            ->set_columns([
                'name' => 'Foo',
                'id' => '1'
            ]);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('The query needs an order to use cursor based pagination');

        new cursor_paginator($query, $cursor, true);
    }

    public function test_passing_invalid_cursor() {
        $query = builder::table($this->table_name)
            ->order_by('name')
            ->order_by('id');

        $cursor = new stdClass();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Expected either null, encoded cursor or cursor object');

        new cursor_paginator($query, $cursor, true);
    }

    public function test_passing_string_cursor() {
        $this->create_sample_records();

        $query = builder::table($this->table_name)
            ->order_by('name')
            ->order_by('id');

        $cursor = cursor::create([
            'limit' => 10,
            'columns' => null
        ]);

        $paginator = new cursor_paginator($query, $cursor->encode(), true);

        $this->assertEquals($cursor, $paginator->get_current_cursor());
    }

    public function test_order_by_raw() {
        $this->create_sample_records();

        $query = builder::table($this->table_name)
            ->order_by_raw('name asc, id asc');

        $cursor = cursor::create([
            'limit' => 10,
            'columns' => null
        ]);

        $paginator = new cursor_paginator($query, $cursor->encode(), true);

        $this->assertEquals($cursor, $paginator->get_current_cursor());
    }

    public function test_using_different_order_as_cursor_missing_key() {
        $query = builder::table($this->table_name)
            ->order_by('name')
            ->order_by('id');

        $cursor = cursor::create()
            ->set_limit(2)
            ->set_columns([
                'name' => 'Foo',
            ]);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Order of query does not match given cursor');

        new cursor_paginator($query, $cursor, true);
    }

    public function test_using_different_order_as_cursor() {
        $query = builder::table($this->table_name)
            ->order_by('name')
            ->order_by('id');

        $cursor = cursor::create()
            ->set_limit(2)
            ->set_columns([
                'id' => '1',
                'name' => 'Foo',
            ]);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Order of query does not match given cursor');

        new cursor_paginator($query, $cursor, true);
    }

    public function test_using_one_sized_cursor() {
        $records = $this->create_sample_records();
        $records = collection::new($records)->sort('id');

        // The following order is expected:
        // - John
        // - Jane
        // - Peter
        // - Basil
        // - Roxanne

        $query = builder::table($this->table_name)
            ->order_by('id');

        $cursor = cursor::create()
            ->set_limit(2);

        // Let's start at the beginning, when we do not have a cursor yet
        $paginator = new cursor_paginator($query, $cursor, true);

        // This should give us the first two records
        $this->assertEquals([
            'John',
            'Jane',
        ], $paginator->get_items()->pluck('name'));

        // Let's create the cursor we expect to come next, which is generated from the last record
        $record = $records->filter('name', 'Jane')->first();
        $expected_next_cursor = cursor::create()
            ->set_limit(2)
            ->set_columns([
                'id' => (string)$record['id']
            ]);

        $this->assertEquals(2, count($paginator->get_items()));
        $this->assertEquals($cursor, $paginator->get_current_cursor());
        $this->assertEquals(5, $paginator->get_total());
        $this->assertEquals($expected_next_cursor, $paginator->get_next_cursor());

        $query = builder::table($this->table_name)
            ->order_by('id');

        $cursor = $paginator->get_next_cursor();

        $paginator = new cursor_paginator($query, $cursor, true);

        // Let's create the cursor we expect to come next, which is generated from the last record
        $record = $records->filter('name', 'Basil')->first();
        $expected_next_cursor = cursor::create()
            ->set_limit(2)
            ->set_columns([
                'id' => (string)$record['id']
            ]);

        $this->assertEquals([
            'Peter',
            'Basil'
        ], $paginator->get_items()->pluck('name'));

        $this->assertEquals(2, count($paginator->get_items()));
        $this->assertEquals($cursor, $paginator->get_current_cursor());
        $this->assertEquals(5, $paginator->get_total());
        $this->assertEquals($expected_next_cursor, $paginator->get_next_cursor());

        $query = builder::table($this->table_name)
            ->order_by('id');

        $cursor = $paginator->get_next_cursor();

        $paginator = new cursor_paginator($query, $paginator->get_next_cursor(), true);

        // Just one left
        $this->assertEquals(['Roxanne'], $paginator->get_items()->pluck('name'));
        $this->assertEquals(1, count($paginator->get_items()));
        $this->assertEquals($cursor, $paginator->get_current_cursor());
        $this->assertEquals(5, $paginator->get_total());
        $this->assertNull($paginator->get_next_cursor());
    }

    public function test_using_two_sized_cursor() {
        $records = $this->create_sample_records();
        $records = collection::new($records)->sort('name');

        // The following order is expected:
        // - Basil
        // - Jane
        // - John
        // - Peter
        // - Roxanne

        $query = builder::table($this->table_name)
            ->order_by('name')
            ->order_by('id');

        $cursor = cursor::create()
            ->set_limit(2);

        // Let's start at the beginning, when we do not have a cursor yet
        $paginator = new cursor_paginator($query, $cursor, true);

        // This should give us the first two records
        $this->assertEquals([
            'Basil',
            'Jane'
        ], $paginator->get_items()->pluck('name'));

        // Let's create the cursor we expect to come next, which is generated from the last record
        $record = $records->filter('name', 'Jane')->first();
        $expected_next_cursor = cursor::create()
            ->set_limit(2)
            ->set_columns([
                'name' => $record['name'],
                'id' => (string)$record['id']
            ]);

        $this->assertEquals(2, count($paginator->get_items()));
        $this->assertEquals($cursor, $paginator->get_current_cursor());
        $this->assertEquals(5, $paginator->get_total());
        $this->assertEquals($expected_next_cursor, $paginator->get_next_cursor());

        $query = builder::table($this->table_name)
            ->order_by('name')
            ->order_by('id');

        $cursor = $paginator->get_next_cursor();

        $paginator = new cursor_paginator($query, $cursor, true);

        // Let's create the cursor we expect to come next, which is generated from the last record
        $record = $records->filter('name', 'Peter')->first();
        $expected_next_cursor = cursor::create()
            ->set_limit(2)
            ->set_columns([
                'name' => $record['name'],
                'id' => (string)$record['id']
            ]);

        $this->assertEquals([
            'John',
            'Peter'
        ], $paginator->get_items()->pluck('name'));

        $this->assertEquals(2, count($paginator->get_items()));
        $this->assertEquals($cursor, $paginator->get_current_cursor());
        $this->assertEquals(5, $paginator->get_total());
        $this->assertEquals($expected_next_cursor, $paginator->get_next_cursor());

        // Use the next cursor

        $query = builder::table($this->table_name)
            ->order_by('name')
            ->order_by('id');

        $cursor = $paginator->get_next_cursor();

        $paginator = new cursor_paginator($query, $cursor, true);

        // Just one left
        $this->assertEquals(['Roxanne'], $paginator->get_items()->pluck('name'));
        $this->assertEquals(1, count($paginator->get_items()));
        $this->assertEquals($cursor, $paginator->get_current_cursor());
        $this->assertEquals(5, $paginator->get_total());
        $this->assertNull($paginator->get_next_cursor());
    }

    public function test_using_three_sized_cursor() {
        $records = collection::new($this->create_sample_records());

        // The following order is expected:
        // - Peter
        // - John
        // - Jane
        // - Basil
        // - Roxanne

        $query = builder::table($this->table_name)
            ->order_by('type')
            ->order_by('is_deleted', 'desc')
            ->order_by('name');

        $cursor = cursor::create()
            ->set_limit(2);

        // Let's start at the beginning, when we do not have a cursor yet
        $paginator = new cursor_paginator($query, $cursor, true);

        // This should give us the first two records
        $this->assertEquals([
            'Peter',
            'John',
        ], $paginator->get_items()->pluck('name'));

        // Let's create the cursor we expect to come next, which is generated from the last record
        $record = $records->find('name', 'John');
        $expected_next_cursor = cursor::create()
            ->set_limit(2)
            ->set_columns([
                'type' => $record['type'],
                'is_deleted' => (string)$record['is_deleted'],
                'name' => $record['name'],
            ]);

        $this->assertEquals(2, count($paginator->get_items()));
        $this->assertEquals($cursor, $paginator->get_current_cursor());
        $this->assertEquals(5, $paginator->get_total());
        $this->assertEquals($expected_next_cursor, $paginator->get_next_cursor());

        $query = builder::table($this->table_name)
            ->order_by('type')
            ->order_by('is_deleted', 'desc')
            ->order_by('name');

        $cursor = $paginator->get_next_cursor();

        $paginator = new cursor_paginator($query, $cursor, true);

        // Let's create the cursor we expect to come next, which is generated from the last record
        $record = $records->find('name', 'Basil');
        $expected_next_cursor = cursor::create()
            ->set_limit(2)
            ->set_columns([
                'type' => $record['type'],
                'is_deleted' => (string)$record['is_deleted'],
                'name' => $record['name'],
            ]);

        $this->assertEquals([
            'Jane',
            'Basil'
        ], $paginator->get_items()->pluck('name'));

        $this->assertEquals(2, count($paginator->get_items()));
        $this->assertEquals($cursor, $paginator->get_current_cursor());
        $this->assertEquals(5, $paginator->get_total());
        $this->assertEquals($expected_next_cursor, $paginator->get_next_cursor());

        // Use the next cursor

        $query = builder::table($this->table_name)
            ->order_by('type')
            ->order_by('is_deleted', 'desc')
            ->order_by('name');

        $cursor = $paginator->get_next_cursor();

        $paginator = new cursor_paginator($query, $cursor, true);

        // Just one left
        $this->assertEquals(['Roxanne'], $paginator->get_items()->pluck('name'));
        $this->assertEquals(1, count($paginator->get_items()));
        $this->assertEquals($cursor, $paginator->get_current_cursor());
        $this->assertEquals(5, $paginator->get_total());
        $this->assertNull($paginator->get_next_cursor());
    }

    public function test_last_page_works() {
        $records = $this->create_sample_records();
        $records = collection::new($records)->sort('name');

        // The following order is expected:
        // - Basil
        // - Jane
        // - John
        // - Peter
        // - Roxanne

        $query = builder::table($this->table_name)
            ->order_by('name')
            ->order_by('id');

        $record = $records->filter('name', 'John')->first();
        // Let's just query the last two records
        $cursor = cursor::create()
            ->set_limit(2)
            ->set_columns([
                'name' => $record['name'],
                'id' => (string)$record['id']
            ]);

        $paginator = new cursor_paginator($query, $cursor, true);

        $this->assertEquals([
            'Peter',
            'Roxanne'
        ], $paginator->get_items()->pluck('name'));

        $this->assertEquals(2, count($paginator->get_items()));
        $this->assertEquals($cursor, $paginator->get_current_cursor());
        $this->assertEquals(5, $paginator->get_total());
        // It's the last page so next cursor should be null
        $this->assertNull($paginator->get_next_cursor());
    }

    public function test_paginator_works_with_repository_as_well() {
        $records = $this->create_sample_records();
        $records = collection::new($records)->sort('name');

        // The following order is expected:
        // - Basil
        // - Jane
        // - John
        // - Peter
        // - Roxanne

        $query = sample_record_entity::repository()
            ->order_by('name')
            ->order_by('id');

        $paginator = new cursor_paginator($query, null, true);

        $expected_cursor = cursor::create()
            ->set_limit(cursor_paginator::DEFAULT_ITEMS_PER_PAGE);

        $this->assertEquals(5, count($paginator->get_items()));
        $this->assertEquals($expected_cursor, $paginator->get_current_cursor());
        $this->assertEquals(5, $paginator->get_total());
        $this->assertNull($paginator->get_next_cursor());

        $query = sample_record_entity::repository()
            ->order_by('name')
            ->order_by('id');

        $record = $records->filter('name', 'John')->first();
        // Let's just query the last two records
        $cursor = cursor::create()
            ->set_limit(2)
            ->set_columns([
                'name' => $record['name'],
                'id' => (string)$record['id']
            ]);

        $paginator = new cursor_paginator($query, $cursor, true);

        $this->assertEquals([
            'Peter',
            'Roxanne'
        ], $paginator->get_items()->pluck('name'));

        $this->assertEquals(2, count($paginator->get_items()));
        $this->assertEquals($cursor, $paginator->get_current_cursor());
        $this->assertEquals(5, $paginator->get_total());
        // It's the last page so next cursor should be null
        $this->assertNull($paginator->get_next_cursor());
    }

    public function test_using_descending_order() {
        $records = $this->create_sample_records();
        $records = collection::new($records)->sort('name', 'desc');

        // The following order is expected:
        // - Roxanne
        // - Peter
        // - John
        // - Jane
        // - Basil

        $query = builder::table($this->table_name)
            ->order_by('name', 'desc')
            ->order_by('id');

        $cursor = cursor::create()
            ->set_limit(2);

        // Let's start at the beginning, when we do not have a cursor yet
        $paginator = new cursor_paginator($query, $cursor, true);

        // This should give us the first two records
        $this->assertEquals([
            'Roxanne',
            'Peter'
        ], $paginator->get_items()->pluck('name'));

        // Let's create the cursor we expect to come next, which is generated from the last record
        $record = $records->filter('name', 'Peter')->first();
        $expected_next_cursor = cursor::create()
            ->set_limit(2)
            ->set_columns([
                'name' => $record['name'],
                'id' => (string)$record['id']
            ]);

        $this->assertEquals(2, count($paginator->get_items()));
        $this->assertEquals($cursor, $paginator->get_current_cursor());
        $this->assertEquals(5, $paginator->get_total());
        $this->assertEquals($expected_next_cursor, $paginator->get_next_cursor());

        $query = builder::table($this->table_name)
            ->order_by('name', 'desc')
            ->order_by('id');

        $cursor = $paginator->get_next_cursor();

        $paginator = new cursor_paginator($query, $cursor, true);

        $this->assertEquals([
            'John',
            'Jane'
        ], $paginator->get_items()->pluck('name'));

        // Let's create the cursor we expect to come next, which is generated from the last record
        $record = $records->filter('name', 'Jane')->first();
        $expected_next_cursor = cursor::create()
            ->set_limit(2)
            ->set_columns([
                'name' => $record['name'],
                'id' => (string)$record['id']
            ]);

        $this->assertEquals(2, count($paginator->get_items()));
        $this->assertEquals($cursor, $paginator->get_current_cursor());
        $this->assertEquals(5, $paginator->get_total());
        $this->assertEquals($expected_next_cursor, $paginator->get_next_cursor());

        $query = builder::table($this->table_name)
            ->order_by('name', 'desc')
            ->order_by('id');

        $cursor = $paginator->get_next_cursor();

        $paginator = new cursor_paginator($query, $cursor, true);

        $this->assertEquals([
            'Basil',
        ], $paginator->get_items()->pluck('name'));

        $this->assertEquals(1, count($paginator->get_items()));
        $this->assertEquals($cursor, $paginator->get_current_cursor());
        $this->assertEquals(5, $paginator->get_total());
        $this->assertEquals(null, $paginator->get_next_cursor());
    }

}

/**
 * Class sample_entity used for testing a entity
 *
 * @property string $name
 * @property int $type
 * @property string $parent_id
 * @property bool $is_deleted
 * @property string $params
 * @property int $created_at
 * @property int $updated_at
 */
class sample_record_entity extends entity {

    public const TABLE = 'test__qb';

}

