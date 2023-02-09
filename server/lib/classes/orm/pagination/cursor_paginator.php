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

namespace core\orm\pagination;

use coding_exception;
use core\orm\entity\repository;
use core\orm\query\builder;
use core\orm\query\order;
use core\pagination\base_cursor;
use core\pagination\cursor_paginator as core_cursor_paginator;

/**
 * This paginator is used for cursor based pagination
 *
 * Usage example:
 * ```php
 * // Cursor string is the base64 encoded json string containing the limit and columns relevant for the cursor
 * $cursor = null;
 * if (!empty($cursor_string)) {
 *     $cursor = cursor::decode($cursor_string);
 * }
 *
 * $query = user::repository()
 *     ->order_by('name')
 *     ->order_by('id');
 *
 * $paginator = new cursor_paginator($query, $cursor, true);
 *
 * // Returns an array containing the items, the next cursor and optional total amount
 * $result = $paginator->get();
 *
 * // You can also iterate over the paginator
 * foreach ($paginator as $item) {
 *     echo $item->fullname;
 * }
 * ```
 *
 * The query going in here needs to be ordered. The order columns need
 * to match the columns encoded in the cursor. If they are not this will fail with
 * an exception. Also the order should be predictable.
 * If you order by a column which can have duplicates content in it add a second order
 * over a unique and sequential column to make sure if gives a consistent result across pages.
 *
 * It is the responsibility of the developer to ensure that it's ordered and it does match up.
 * For the initial cursor the columns can be omitted. In this case the paginator automatically
 * determines the correct columns for the query order and encodes it in the next cursor
 */
final class cursor_paginator extends core_cursor_paginator {

    /**
     * @param builder|repository $query
     * @param bool $include_total
     * @return base_cursor|null
     */
    protected function process_query($query, bool $include_total): ?base_cursor {
        if (!$query instanceof builder && !$query instanceof repository) {
            throw new coding_exception('Expected builder or repository');
        }

        // If you have a often changing dataset the total count is not of high value.
        // But it can still be useful if the dataset is pretty static.
        // We can return it in case someone wants to use it or want to save
        // additional queries to get the count.
        if ($include_total) {
            $this->total = $query->count();
        }

        $this->create_recursive_cursor_condition($query);

        $limit = $this->cursor->get_limit();
        if ($limit > 0) {
            $query->limit($limit + 1);
        }

        $this->items = $query->get();

        return $this->create_next_cursor($limit);
    }

    /**
     * @param builder|repository $query
     * @return array
     */
    protected function get_expected_order($query): array {
        $expected_cursor_keys = [];
        $orders = $query->get_orders();
        foreach ($orders as $order) {
            $expected_cursor_keys[$order->get_field_as_is()] = method_exists($order, 'get_direction')
                ? $order->get_direction()
                : '';
        }
        if (empty($expected_cursor_keys)) {
            throw new coding_exception('The query needs an order to use cursor based pagination.');
        }
        return $expected_cursor_keys;
    }

    /**
     * Add conditions for the query recursively.
     * Example result:
     * column1 > 'value1' OR (column1 = 'value1' AND column2 > 'value2' OR (column2 = 'value2' AND ...))
     *
     * @param builder|repository $query
     * @param array $cursor expanded cursor
     * @param array|null $current
     */
    protected function create_recursive_cursor_condition($query, array $cursor = null, array $current = null): void {
        if (is_null($cursor)) {
            // Add the outer nested condition if cursor was provided
            if ($this->cursor && !empty($this->cursor->get_columns())) {
                $query->where(function (builder $builder) {
                    $this->create_recursive_cursor_condition($builder, $this->cursor->get_columns());
                });
            }
            return;
        }

        if (is_null($current)) {
            $current = array_splice($cursor, 0, 1);
        }

        $key = key($current);
        $direction = $this->order[$key] == order::DIRECTION_ASC ? '>' : '<';

        $query->or_where($key, $direction, current($current));
        // It's not the last one, continue
        if (!empty($cursor)) {
            $query->or_where(function (builder $builder) use ($current, $cursor) {
                $builder->where(key($current), '=', current($current))
                    ->where(function (builder $builder) use ($current, $cursor) {
                        $current = array_splice($cursor, 0, 1);
                        $this->create_recursive_cursor_condition($builder, $cursor, $current);
                    });
            });
        }
    }

}