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

namespace mod_perform\models\activity\helpers;

use coding_exception;

/**
 * Class item_ordering
 * This helper class provides the items that need their sort_order(s) updated
 * when an item is moved up/down within the list.
 *
 * @package mod_perform\models\activity\helpers
 */
class item_ordering {

    /**
     * Items to be sorted.
     *
     * @var array|object[]
     */
    private $items;

    /**
     * item_ordering constructor.
     *
     * @param array|object[] $items
     *
     * @throws coding_exception
     */
    public function __construct(array $items) {
        $this->set_items($items);
    }

    /**
     * Set property items.
     *
     * @param array $items
     * @return self
     * @throws coding_exception
     */
    private function set_items(array $items): self {
        $items_list = [];
        foreach ($items as $item) {
            $this->validate_properties_exist($item);
            $items_list[$item->sort_order] = $item;
        }

        if (count($items_list) !== count($items)) {
            throw new coding_exception("Items have duplicate sort_order property");
        }
        $this->items = $items_list;

        return $this;
    }

    /**
     * Validate sort_order and identifier properties exists in item.
     *
     * @param object $item
     *
     * @throws coding_exception
     */
    private function validate_properties_exist(object $item): void {
        if (empty($item->sort_order)) {
            throw new coding_exception("property sort_order does not exist in item(s)");
        }
        if (empty($item->id)) {
            throw new coding_exception("property id does not exist in item(s)");
        }
    }

    /**
     * Validate sort orders of the items.
     *
     * @return void
     * @throws coding_exception
     */
    public function validate_sort_orders(): void {
        if (empty($this->items)) {
            return;
        }
        $sort_orders = array_unique(array_column($this->items, 'sort_order'));

        if (count($sort_orders) != count($this->items)) {
            throw new coding_exception('Item sort orders are not unique!');
        }
        sort($sort_orders);

        if (reset($sort_orders) != 1 || end($sort_orders) != count($sort_orders)) {
            throw new coding_exception('Item sort orders are not consecutive starting at 1!');
        }
    }

    /**
     * Get new item sort_order when item is added after a specified item.
     * @param int|null $after_item_id
     *
     * @return int
     */
    public function get_new_item_sort_order_after(?int $after_item_id = null): int {
        $new_item_sort_order = 1;

        if (!is_null($after_item_id)) {
            $after_item = $this->find_item_in_list($after_item_id);
            $new_item_sort_order = $after_item === null
                ? $this->get_next_available_sort_order()
                : $this->get_sort_order_after_item($after_item);
        }

        return $new_item_sort_order;
    }

    /**
     * Get items with their new sort_orders to reorder when adding a new sort_order.
     *
     * @param int $new_item_sort_order
     *
     * @return array
     */
    public function get_items_to_reorder(int $new_item_sort_order): array {
        $new_sort_list = [];

        foreach ($this->items as $item) {
            if ($item->sort_order >= $new_item_sort_order) {
                $new_sort_list[$item->sort_order + 1] = $item;
            }
        }

        return $new_sort_list;
    }

    /**
     * Get items that need to be reordered to move item in specified position.
     * Returns an array containing the items that need their sort_orders updated
     * and the new sort_order as the key of the item.
     *
     * @param int $item_id
     * @param int|null $after_item_id
     * @return array
     */
    public function move_item_after(int $item_id, ?int $after_item_id = null): array {
        $item = $this->find_item_in_list($item_id);

        if ($after_item_id === $item_id || is_null($item)) {
            return [];
        }
        $after_item = null;

        if ($after_item_id !== null) {
            $after_item = $this->process_after_item_for_reordering($after_item_id);

            if ($item->id === $after_item->id) {
                return [];
            }
        }
        $new_sort_order = $this->process_new_sort_order_for_reordering($after_item, $item);
        $sort_logic = $this->get_sorting_logic($item, $new_sort_order);

        $items_to_be_reordered = array_filter($this->items, function($existing_item) use ($sort_logic) {
            $item_sort_order = $existing_item->sort_order;
            $item_identifier = $existing_item->id;

            return $item_sort_order >= $sort_logic['start']
                && $item_sort_order <= $sort_logic['end']
                && $item_identifier !== $sort_logic['item_id'];
        });
        $result = [
            $new_sort_order => $item,
        ];

        foreach ($items_to_be_reordered as $item) {
            $changed_sort_order = $item->sort_order + $sort_logic['change_by'];
            $result[$changed_sort_order] = $item;
        }

        return $result;
    }


    /**
     * Processes sort order for reordering.
     *
     * @param object|null $after_item
     * @param object $item
     *
     * @return int
     */
    private function process_new_sort_order_for_reordering(?object $after_item, object $item): int {
        $new_sort_order = $this->get_sort_order_after_item($after_item);

        if ($after_item !== null) {
            if ($item->sort_order < $after_item->sort_order) {
                $new_sort_order = $after_item->sort_order;
            }
        }
        return $new_sort_order;
    }

    /**
     * Get after item used for reordering.
     * If after_item_id does not exist in list, use last item in list.
     *
     * @param int $after_item_id
     *
     * @return object
     */
    private function process_after_item_for_reordering(int $after_item_id): object {
        $after_item = $this->find_item_in_list($after_item_id);

        if ($after_item === null) {
            $after_item = $this->get_last_item_in_list();
        }

        return $after_item;
    }

    /**
     * Get last item in a list.
     *
     * @return object
     */
    private function get_last_item_in_list(): object {
        $max_sort_order = max(array_keys($this->items));

        return $this->items[$max_sort_order];
    }

    /**
     * Get the new sort_order calculated by incrementing the $after_item sort_order.
     *
     * @param object|null $after_item
     *
     * @return int
     */
    private function get_sort_order_after_item(?object $after_item = null): int {
        if ($after_item === null) {
            return 1;
        }

        return $after_item->sort_order + 1;
    }

    /**
     * Find an item in the list.
     *
     * @param int $item_id
     *
     * @return object
     */
    private function find_item_in_list(int $item_id): ?object {
        $item_found = null;

        foreach ($this->items as $item) {
            if ($item->id === $item_id) {
                $item_found = $item;
                break;
            }
        }

        return $item_found;
    }

    /**
     * Get the next available sort_order.
     *
     * @return int
     */
    private function get_next_available_sort_order(): int {
        return max(array_keys($this->items)) + 1;
    }

    /**
     * Get the sorting configuration to be used to select items that need reordering.
     *
     * @param object $item
     * @param int $new_sort_order
     *
     * @return array
     */
    private function get_sorting_logic(object $item, int $new_sort_order): array {
        $item_sort_order = $item->sort_order;

        return [
            'start' => min($item_sort_order, $new_sort_order),
            'end' => max($item_sort_order, $new_sort_order),
            'item_id' => $item->id,
            'change_by' => $item_sort_order > $new_sort_order ? 1 : -1,
        ];
    }
}
