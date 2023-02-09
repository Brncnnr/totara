<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

use mod_perform\models\activity\helpers\item_ordering;

/**
 * @group perform
 */
class mod_perform_item_ordering_testcase extends advanced_testcase {

    public function test_reorder_an_item_that_does_not_exist() {
        $items = $this->generate_items(10);
        $non_existing_item = $this->generate_item(50);
        $item_ordering = new item_ordering($items);
        $result = $item_ordering->move_item_after($non_existing_item->id, $items[0]->id);
        $this->assertEmpty($result);
    }

    public function test_reorder_an_item_to_after_itself() {
        $items = $this->generate_items(10);
        $item_ordering = new item_ordering($items);

        $first_item = $items[0];
        $result = $item_ordering->move_item_after($first_item->id, $first_item->id);
        $this->assertEmpty($result);
    }

    public function test_reorder_an_item_to_its_same_position() {
        $items = $this->generate_items(10);
        $item_ordering = new item_ordering($items);

        $result = $item_ordering->move_item_after($items[5]->id, $items[4]->id);
        $this->assertCount(1, $result);
        $item = reset($result);
        $this->assertEquals(6, $item->id);
        $this->assertEquals(6, $item->sort_order);
    }

    public function test_reorder_an_item_after_an_item_that_does_not_exist() {
        $items = $this->generate_items(10);
        $item_ordering = new item_ordering($items);

        $first_item = $items[0];
        $non_existing_item = $this->generate_item(11);
        $result = $item_ordering->move_item_after($first_item->id, $non_existing_item->id);

        $expected_item_to_sort_orders = [
            1 => 10,
            2 => 1,
            3 => 2,
            4 => 3,
            5 => 4,
            6 => 5,
            7 => 6,
            8 => 7,
            9 => 8,
            10 => 9,
        ];
        foreach ($result as $sort_order => $item) {
            $this->assertEquals($expected_item_to_sort_orders[$item->id], $sort_order);
        }

        // test when item to reorder is the last item on the list as well.
        $first_item = $items[9];
        $non_existing_item = $this->generate_item(11);
        $result = $item_ordering->move_item_after($first_item->id, $non_existing_item->id);
        $this->assertEmpty($result);
    }

    public function test_reorder_an_item_to_bottom_of_list() {
        $items = $this->generate_items(5);
        $item_ordering = new item_ordering($items);

        // moving an item down the list. e.g first item to 5th position.
        $second_item = $items[1];
        $last_item = $items[4];
        $items_with_sort_as_index = $item_ordering->move_item_after($second_item->id, $last_item->id);
        $this->assertCount(4, $items_with_sort_as_index);
        $expected_id_to_sort_values = [
            2 => 5,
            3 => 2,
            4 => 3,
            5 => 4,
        ];

        foreach ($items_with_sort_as_index as $new_sort_order => $sorted_item) {
            $this->assertEquals($expected_id_to_sort_values[$sorted_item->id], $new_sort_order);
        }
    }

    public function test_reorder_an_item_down_the_list() {
        $items = $this->generate_items(5);
        $item_ordering = new item_ordering($items);

        // moving an item down the list. e.g first item to 5th position.
        $second_item = $items[1];
        $fourth_item = $items[3];
        $items_with_sort_as_index = $item_ordering->move_item_after($second_item->id, $fourth_item->id);
        $this->assertCount(3, $items_with_sort_as_index);
        $expected_id_to_sort_values = [
            2 => 4,
            3 => 2,
            4 => 3,
        ];

        foreach ($items_with_sort_as_index as $new_sort_order => $sorted_item) {
            $this->assertEquals($expected_id_to_sort_values[$sorted_item->id], $new_sort_order);
        }
    }

    public function test_reorder_an_item_to_top_of_the_list() {
        $items = $this->generate_items(10);
        $item_ordering = new item_ordering($items);

        // moving an item to top of the list. e.g eight item to 1st position.
        $eight_item = $items[7];
        $after_element_id = null;
        $expected_new_position = 1;
        $items_with_sort_as_index = $item_ordering->move_item_after($eight_item->id, $after_element_id);
        $this->assertCount(8, $items_with_sort_as_index);

        foreach ($items_with_sort_as_index as $sort_order => $item) {
            $this->assertTrue($sort_order <= $eight_item->sort_order);
            $this->assertTrue($sort_order >= $expected_new_position);

            if ($item->id === $eight_item->id) {
                $this->assertEquals($expected_new_position, $sort_order);
                continue;
            }
            $this->assertEquals(1, $sort_order - $item->sort_order);
        }
    }

    public function test_reorder_an_item_up_the_list() {
        $items = $this->generate_items(10);
        $item_ordering = new item_ordering($items);

        // moving an item up the list. e.g eight item to 2nd position.
        $eight_item = $items[7];
        $after_element = $items[0];
        $new_position = $after_element->sort_order + 1;
        $items_with_sort_as_index = $item_ordering->move_item_after($eight_item->id, $after_element->id);
        $this->assertCount(7, $items_with_sort_as_index);

        foreach ($items_with_sort_as_index as $sort_order => $item) {
            $this->assertTrue($sort_order <= $eight_item->sort_order);
            $this->assertTrue($sort_order >= $new_position);

            if ($item->id === $eight_item->id) {
                $this->assertEquals($new_position, $sort_order);
                continue;
            }
            $this->assertEquals(1, $sort_order - $item->sort_order);
        }
    }

    public function test_validate_sort_orders_for_nonconsecutive_items() {
        $items = $this->generate_items(10);
        $items[] = $this->generate_item(20);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Item sort orders are not consecutive starting at 1!");
        $item_ordering = new item_ordering($items);
        $item_ordering->validate_sort_orders();
    }

    public function test_validate_sort_orders_for_items_with_repeated_sort_orders() {
        $items = $this->generate_items(10);
        $item = $this->generate_item(2);
        $item->id = 11;
        $items[] = $item;

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Items have duplicate sort_order property");
        $item_ordering = new item_ordering($items);
        $item_ordering->validate_sort_orders();
    }

    public function test_action_data_to_add_item_at_top_of_list() {
        $items = $this->generate_items(10);
        $item_ordering = new item_ordering($items);
        $new_item_sort_order = $item_ordering->get_new_item_sort_order_after();
        $items_to_reorder = $item_ordering->get_items_to_reorder($new_item_sort_order);
        $this->assertEquals(1, $new_item_sort_order);

        foreach ($items_to_reorder as $new_sort_order => $item) {
            $this->assertEquals($item->sort_order + 1, $new_sort_order);
        }
    }

    public function test_action_data_to_add_item_after_another_element() {
        $items = $this->generate_items(10);
        $item_ordering = new item_ordering($items);
        $new_item_sort_order = $item_ordering->get_new_item_sort_order_after(5);
        $items_to_reorder = $item_ordering->get_items_to_reorder($new_item_sort_order);
        $this->assertEquals(6, $new_item_sort_order);
        $this->assertCount(5, $items_to_reorder);
    }

    public function test_action_data_to_add_item_after_another_element_that_does_not_exist() {
        $items = $this->generate_items(10);
        $item_ordering = new item_ordering($items);
        $non_existing_id = 123;
        $new_item_sort_order = $item_ordering->get_new_item_sort_order_after($non_existing_id);
        $items_to_reorder = $item_ordering->get_items_to_reorder($new_item_sort_order);
        $this->assertEmpty($items_to_reorder);
        $this->assertEquals(11, $new_item_sort_order);
    }

    public function test_action_data_to_add_item_at_bottom_of_list() {
        $items = $this->generate_items(10);
        $item_ordering = new item_ordering($items);
        $new_item_sort_order = $item_ordering->get_new_item_sort_order_after(10);
        $items_to_reorder = $item_ordering->get_items_to_reorder($new_item_sort_order);
        $this->assertEquals(11, $new_item_sort_order);
        $this->assertCount(0, $items_to_reorder);
    }

    /**
     * @param int $no_of_items
     * @return array
     */
    private function generate_items(int $no_of_items): array {
        $items = [];

        for ($i = 1; $i <= $no_of_items; $i++) {
            $items[] = $this->generate_item($i);
        }
        return $items;
    }

    private function generate_item($sort_order): object {
        return (object) [
            'id' => $sort_order,
            'sort_order' => $sort_order,
        ];
    }
}