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

use core\orm\collection;
use core\orm\paginator;
use core\orm\query\builder;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/tests/orm_query_builder_base.php');

/**
 * Class core_orm_paginator_testcase
 *
 * @package core
 * @group orm
 */
class core_orm_paginator_testcase extends orm_query_builder_base {

    public function test_using_paginate_on_builder() {
        $this->create_sample_records();

        $paginator = builder::table($this->table_name)
            ->order_by('name')
            ->paginate(1, 2);

        $this->assertInstanceOf(paginator::class, $paginator);

        $result = $paginator->to_array();
        $this->assertEquals([
            'items',
            'page',
            'pages',
            'per_page',
            'next',
            'prev',
            'total'
        ], array_keys($result));

        $expected_result = builder::table($this->table_name)
            ->offset(0)
            ->limit(2)
            ->order_by('name')
            ->fetch();
        $expected_result = array_values($expected_result);

        $this->assertFalse($paginator->is_simple());
        $this->assertEquals($expected_result, $paginator->get_items()->all());
        $this->assertEquals(1, $paginator->get_page());
        $this->assertEquals(2, $paginator->get_per_page());
        $this->assertEquals(3, $paginator->get_pages());
        $this->assertEquals(2, $paginator->get_next());
        $this->assertEquals(null, $paginator->get_prev());
        $this->assertEquals(5, $paginator->get_total());

        // Check that items are collection and paginator is traversable
        $this->assertInstanceOf(collection::class, $paginator->get_items());

        // Check that count works
        $this->assertEquals(2, $paginator->count());
        $this->assertEquals(2, count($paginator));

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

    public function test_using_load_more_on_builder() {
        $this->create_sample_records();

        $paginator = builder::table($this->table_name)
            ->order_by('name')
            ->load_more(1, 2);

        $this->assertInstanceOf(paginator::class, $paginator);

        $result = $paginator->to_array();
        $this->assertEquals([
            'items',
            'page',
            'per_page',
        ], array_keys($result));

        $expected_result = builder::table($this->table_name)
            ->offset(0)
            ->limit(2)
            ->order_by('name')
            ->fetch();
        $expected_result = array_values($expected_result);

        $this->assertTrue($paginator->is_simple());
        $this->assertEquals($expected_result, $paginator->get_items()->all());
        $this->assertEquals(1, $paginator->get_page());
        $this->assertEquals(2, $paginator->get_per_page());
        $this->assertEquals(null, $paginator->get_pages());
        $this->assertEquals(null, $paginator->get_next());
        $this->assertEquals(null, $paginator->get_prev());
        $this->assertEquals(null, $paginator->get_total());
    }

    public function test_creating_a_paginator_using_static_method() {
        $this->create_sample_records();

        $query = builder::table($this->table_name)
            ->order_by('name');

        $paginator = paginator::new($query, 1, 2);

        $result = $paginator->to_array();
        $this->assertEquals([
            'items',
            'page',
            'pages',
            'per_page',
            'next',
            'prev',
            'total'
        ], array_keys($result));

        $expected_result = builder::table($this->table_name)
            ->offset(0)
            ->limit(2)
            ->order_by('name')
            ->fetch();
        $expected_result = array_values($expected_result);

        $this->assertEquals($expected_result, $paginator->get_items()->all());
        $this->assertEquals(1, $paginator->get_page());
        $this->assertEquals(2, $paginator->get_per_page());
        $this->assertEquals(3, $paginator->get_pages());
        $this->assertEquals(2, $paginator->get_next());
        $this->assertEquals(null, $paginator->get_prev());
        $this->assertEquals(5, $paginator->get_total());
    }

    public function test_creating_a_paginator() {
        $this->create_sample_records();

        $query = builder::table($this->table_name)
            ->order_by('name');

        $paginator = new paginator($query, 1, 2);

        $result = $paginator->to_array();
        $this->assertEquals([
            'items',
            'page',
            'pages',
            'per_page',
            'next',
            'prev',
            'total'
        ], array_keys($result));

        $expected_result = builder::table($this->table_name)
            ->offset(0)
            ->limit(2)
            ->order_by('name')
            ->results_as_arrays()
            ->fetch();
        $expected_result = array_values($expected_result);

        $this->assertFalse($paginator->is_simple());
        $this->assertEquals($expected_result, $result['items']);
        $this->assertEquals($paginator->get_page(), $result['page']);
        $this->assertEquals($paginator->get_per_page(), $result['per_page']);
        $this->assertEquals($paginator->get_pages(), $result['pages']);
        $this->assertEquals($paginator->get_next(), $result['next']);
        $this->assertEquals($paginator->get_prev(), $result['prev']);
        $this->assertEquals($paginator->get_total(), $result['total']);

        $expected_result = builder::table($this->table_name)
            ->offset(0)
            ->limit(2)
            ->order_by('name')
            ->fetch();
        $expected_result = array_values($expected_result);

        $this->assertEquals($expected_result, $paginator->get_items()->all());
        $this->assertEquals(1, $paginator->get_page());
        $this->assertEquals(2, $paginator->get_per_page());
        $this->assertEquals(3, $paginator->get_pages());
        $this->assertEquals(2, $paginator->get_next());
        $this->assertEquals(null, $paginator->get_prev());
        $this->assertEquals(5, $paginator->get_total());
    }

    public function test_creating_a_simple_paginator() {
        $this->create_sample_records();

        $query = builder::table($this->table_name)
            ->order_by('name');

        $paginator = new paginator($query, 1, 2, true);

        $result = $paginator->to_array();
        $this->assertEquals([
            'items',
            'page',
            'per_page',
        ], array_keys($result));

        $expected_result = builder::table($this->table_name)
            ->offset(0)
            ->limit(2)
            ->order_by('name')
            ->results_as_arrays()
            ->fetch();
        $expected_result = array_values($expected_result);

        $this->assertTrue($paginator->is_simple());
        $this->assertEquals($expected_result, $result['items']);
        $this->assertEquals($paginator->get_page(), $result['page']);
        $this->assertEquals($paginator->get_per_page(), $result['per_page']);
        $this->assertNull($paginator->get_prev());
        $this->assertNull($paginator->get_next());
        $this->assertNull($paginator->get_pages());
        $this->assertNull($paginator->get_total());

        $expected_result = builder::table($this->table_name)
            ->offset(0)
            ->limit(2)
            ->order_by('name')
            ->fetch();
        $expected_result = array_values($expected_result);

        $this->assertEquals($expected_result, $paginator->get_items()->all());
        $this->assertEquals(1, $paginator->get_page());
        $this->assertEquals(2, $paginator->get_per_page());

        // second page
        $paginator = new paginator($query, 2, 3, true);
        $this->assertCount(2, $paginator->get_items()->all());
        $this->assertEquals(2, $paginator->get_page());
        $this->assertEquals(3, $paginator->get_per_page());
    }

    public function test_calculating_pages_properly() {
        $this->create_sample_records();

        $query = builder::table($this->table_name)
            ->order_by('name');

        $paginator = new paginator($query, 1, 2);

        $this->assertCount(2, $paginator->get_items()->all());
        $this->assertEquals(5, $paginator->get_total());
        $this->assertEquals(1, $paginator->get_page());
        $this->assertEquals(2, $paginator->get_per_page());
        $this->assertEquals(3, $paginator->get_pages());
        $this->assertEquals(2, $paginator->get_next());
        $this->assertEquals(null, $paginator->get_prev());

        // second page
        $paginator = new paginator($query, 2, 2);

        $this->assertCount(2, $paginator->get_items()->all());
        $this->assertEquals(5, $paginator->get_total());
        $this->assertEquals(2, $paginator->get_page());
        $this->assertEquals(2, $paginator->get_per_page());
        $this->assertEquals(3, $paginator->get_pages());
        $this->assertEquals(3, $paginator->get_next());
        $this->assertEquals(1, $paginator->get_prev());

        // third page
        $paginator = new paginator($query, 3, 2);

        $this->assertCount(1, $paginator->get_items()->all());
        $this->assertEquals(5, $paginator->get_total());
        $this->assertEquals(3, $paginator->get_page());
        $this->assertEquals(2, $paginator->get_per_page());
        $this->assertEquals(3, $paginator->get_pages());
        $this->assertEquals(null, $paginator->get_next());
        $this->assertEquals(2, $paginator->get_prev());

        // fourth page does not exist
        $paginator = new paginator($query, 4, 2);

        $this->assertCount(0, $paginator->get_items()->all());
        $this->assertEquals(5, $paginator->get_total());
        $this->assertEquals(4, $paginator->get_page());
        $this->assertEquals(2, $paginator->get_per_page());
        $this->assertEquals(3, $paginator->get_pages());
        $this->assertEquals(null, $paginator->get_next());
        $this->assertEquals(3, $paginator->get_prev());

        // fifth page does not exist too
        $paginator = new paginator($query, 5, 2);

        $this->assertCount(0, $paginator->get_items()->all());
        $this->assertEquals(5, $paginator->get_total());
        $this->assertEquals(5, $paginator->get_page());
        $this->assertEquals(2, $paginator->get_per_page());
        $this->assertEquals(3, $paginator->get_pages());
        $this->assertEquals(null, $paginator->get_next());
        $this->assertEquals(4, $paginator->get_prev());

        // get all records
        $paginator = new paginator($query, 1, 30);

        $this->assertCount(5, $paginator->get_items()->all());
        $this->assertEquals(5, $paginator->get_total());
        $this->assertEquals(1, $paginator->get_page());
        $this->assertEquals(30, $paginator->get_per_page());
        $this->assertEquals(1, $paginator->get_pages());
        $this->assertEquals(null, $paginator->get_next());
        $this->assertEquals(null, $paginator->get_prev());

        // Default items per page
        $paginator = new paginator($query, 1);

        $this->assertCount(5, $paginator->get_items()->all());
        $this->assertEquals(5, $paginator->get_total());
        $this->assertEquals(1, $paginator->get_page());
        $this->assertEquals(paginator::DEFAULT_ITEMS_PER_PAGE, $paginator->get_per_page());
        $this->assertEquals(1, $paginator->get_pages());
        $this->assertEquals(null, $paginator->get_next());
        $this->assertEquals(null, $paginator->get_prev());

        // get all with default per page
        $paginator = new paginator($query, 0);

        $this->assertCount(5, $paginator->get_items()->all());
        $this->assertEquals(5, $paginator->get_total());
        $this->assertEquals(0, $paginator->get_page());
        $this->assertEquals(5, $paginator->get_per_page());
        $this->assertEquals(1, $paginator->get_pages());
        $this->assertEquals(null, $paginator->get_next());
        $this->assertEquals(null, $paginator->get_prev());

        // get all with 0 per page
        $paginator = new paginator($query, 0, 0);

        $this->assertCount(5, $paginator->get_items()->all());
        $this->assertEquals(5, $paginator->get_total());
        $this->assertEquals(0, $paginator->get_page());
        $this->assertEquals(5, $paginator->get_per_page());
        $this->assertEquals(1, $paginator->get_pages());
        $this->assertEquals(null, $paginator->get_next());
        $this->assertEquals(null, $paginator->get_prev());
    }

    public function test_converting_results_to_json() {
        $this->create_sample_records();

        $query = builder::table($this->table_name)
            ->order_by('name');

        $paginator = new paginator($query, 1, 3);

        $this->assertEquals(json_encode($paginator->to_array()), json_encode($paginator));
        $this->assertEquals(json_encode($paginator->to_array()), (string) $paginator);
    }

    public function test_transform() {
        $this->create_sample_records();

        $query = builder::table($this->table_name)
            ->order_by('name');

        $callback = function (\stdClass $item) {
            return new class($item) {
                private $detail;
                public function __construct(stdClass $item) {
                    $this->detail = $item->name;
                }
                public function detail() {
                    return $this->detail;
                }
            };
        };

        $paginator = new paginator($query, 1, 3);
        $collection = $paginator->transform($callback);
        $this->assertCount(3, $collection);
        $this->assertSame('Basil', $collection->current()->detail());

        // Check it is still an array internally.
        $this->assertSame(4, $paginator->key());
    }


}
