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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package core
 * @group orm
 */

use core\orm\query\builder;
use core\orm\query\order;
use core\orm\query\raw_field;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/tests/orm_query_builder_base.php');

/**
 * @package core
 * @group orm
 */
class core_orm_builder_order_testcase extends orm_query_builder_base {

    public function test_order_by_sets_correct_values() {
        $this->create_sample_records();

        $builder = builder::table($this->table_name)
            ->order_by('id', order::DIRECTION_DESC);

        $orders = $builder->get_orders();

        $this->assertCount(1, $orders);
        $order = array_shift($orders);

        $this->assertInstanceOf(order::class, $order);
        $this->assertEquals('id', $order->get_field_column());
        $this->assertEquals(order::DIRECTION_DESC, $order->get_direction());

        $builder->order_by('name', order::DIRECTION_ASC);

        $orders = $builder->get_orders();
        $this->assertCount(2, $orders);
        $order = array_pop($orders);

        $this->assertInstanceOf(order::class, $order);
        $this->assertEquals('name', $order->get_field_column());
        $this->assertEquals(order::DIRECTION_ASC, $order->get_direction());

        $builder->get();

        $query = $builder->get_last_executed_query();

        $this->assertStringContainsString(
            sprintf("ORDER BY %s.id DESC, %s.name ASC", $builder->get_alias_sql(), $builder->get_alias_sql()),
            $query['sql']
        );
    }

    public function test_multiple_order_by_on_same_column_and_same_values() {
        $builder = builder::table($this->table_name)
            ->order_by('id', order::DIRECTION_DESC);

        $builder->order_by('id', order::DIRECTION_DESC);

        $orders = $builder->get_orders();

        // Was only added once
        $this->assertCount(1, $orders);
        $order = array_shift($orders);

        $this->assertInstanceOf(order::class, $order);
        $this->assertEquals('id', $order->get_field_column());
        $this->assertEquals(order::DIRECTION_DESC, $order->get_direction());
    }

    public function test_multiple_order_by_on_same_column_with_different_values_fails() {
        $builder = builder::table($this->table_name)
            ->order_by('id', order::DIRECTION_DESC);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Order for field \'id\' is already set with a different configuration.');

        $builder->order_by('id', order::DIRECTION_ASC);
    }

    public function test_order_by_raw() {
        $this->create_sample_records();

        $builder = builder::table($this->table_name)
            ->order_by_raw('id desc');

        $orders = $builder->get_orders();

        $this->assertCount(1, $orders);
        $order = array_shift($orders);

        $this->assertInstanceOf(raw_field::class, $order);
        $this->assertTrue($order->is_raw());
        $this->assertEquals('id desc', $order->sql());

        $builder->get();

        $query = $builder->get_last_executed_query();

        $this->assertStringContainsString(
            "ORDER BY id desc",
            $query['sql']
        );
    }

    public function test_multiple_raw_orders_on_same_column_and_same_values() {
        $builder = builder::table($this->table_name)
            ->order_by('name', order::DIRECTION_DESC)
            ->order_by_raw('id desc');

        $builder->order_by_raw('id desc');
        $builder->order_by_raw('type asc');

        $orders = $builder->get_orders();

        // Was only added once
        $this->assertCount(3, $orders);
        $order = array_shift($orders);

        $this->assertInstanceOf(order::class, $order);
        $this->assertEquals('name', $order->get_field_column());
        $this->assertEquals(order::DIRECTION_DESC, $order->get_direction());

        $order = array_shift($orders);

        $this->assertInstanceOf(raw_field::class, $order);
        $this->assertTrue($order->is_raw());
        $this->assertEquals('id desc', $order->sql());

        $order = array_shift($orders);

        $this->assertInstanceOf(raw_field::class, $order);
        $this->assertTrue($order->is_raw());
        $this->assertEquals('type asc', $order->sql());
    }

}
