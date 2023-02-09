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
 * @group paginator
 */

namespace core\pagination;

use coding_exception;

/**
 * This paginator is used for cursor based pagination
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
abstract class cursor_paginator extends base_cursor_paginator {

    protected $order = [];

    /**
     * @inheritDoc
     */
    protected static function normalise_cursor($cursor): base_cursor {
        if (is_null($cursor)) {
            $cursor = new cursor();
        }
        if (is_string($cursor)) {
            $cursor = cursor::decode($cursor);
        }
        if (!$cursor instanceof cursor) {
            throw new coding_exception('Expected either null, encoded cursor or cursor object');
        }
        // Set default limit if not provided
        if ($cursor->get_limit() === null) {
            $cursor->set_limit(static::DEFAULT_ITEMS_PER_PAGE);
        }

        return $cursor;
    }

    /**
     * @inheritDoc
     */
    protected function validate_cursor($query, base_cursor $cursor): void {
        $this->order = $this->get_expected_order($query);
        if (!is_null($cursor->get_columns())) {
            if (array_keys($this->order) !== array_keys($cursor->get_columns())) {
                throw new coding_exception('Order of query does not match given cursor');
            }
        }
    }

    /**
     * Get the an array of the column names this this query is ordered by
     *
     * @param mixed $query
     * @return array
     */
    abstract protected function get_expected_order($query): array;

    /**
     * Create new next cursor based on the last item of the result or return null if we reached the end of the results
     *
     * @param $limit
     * @return cursor|null
     */
    protected function create_next_cursor($limit): ?cursor {
        $next_cursor = null;
        if ($limit > 0 && count($this->items) > $limit) {
            // remove the last item as it was only to check if more results are coming
            $this->items->pop();
            if ($this->items->count() > 0) {
                $last_item = $this->items->last();
                $next_cursor = [
                    'limit' => $limit,
                    'columns' => []
                ];

                if (!empty($this->cursor->get_columns())) {
                    $cursor_keys = array_keys($this->cursor->get_columns());
                } else {
                    $cursor_keys = array_keys($this->order);
                }

                foreach ($cursor_keys as $key) {
                    $actual_field = str_replace('.', '_', $key);

                    if (!isset($last_item->$actual_field) && strpos($key, '.') !== false) {
                        throw new coding_exception("To support cursor based pagination on queries that order by a joined column, you need to include a select statement like 'SELECT {$key} AS {$actual_field}'.", DEBUG_DEVELOPER);
                    }

                    $next_cursor['columns'][$key] = $last_item->$actual_field;
                }

                $next_cursor = new cursor($next_cursor);
            }
        }
        return $next_cursor;
    }

}