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

use core\collection;
use core\orm\entity\repository;
use core\orm\query\builder;

/**
 * This paginator is used for cursor based pagination
 */
abstract class base_cursor_paginator extends base_paginator {

    /**
     * @var cursor
     */
    protected $next_cursor;

    /**
     * @var cursor
     */
    protected $cursor;

    protected $total = null;

    /**
     * @var collection
     */
    protected $items;

    /**
     * @param mixed $query pass a query to paginate
     * @param cursor|string $cursor encoded cursor or cursor object, omit for the first query
     * @param bool $include_total defaults to false
     */
    public function __construct($query, $cursor = null, bool $include_total = false) {
        $cursor = static::normalise_cursor($cursor);
        $this->validate_cursor($query, $cursor);
        $this->cursor = $cursor;
        $this->items = new collection();

        $this->next_cursor = $this->process_query($query, $include_total);
    }

    /**
     * Query the database and return next cursor. 
     *
     * @param mixed $query
     * @param bool $include_total
     * @return base_cursor|null if there are no more results to come the next cursor can be null
     */
    abstract protected function process_query($query, bool $include_total): ?base_cursor;

    /**
     * Normalises the cursor, no matter if the cursor was omitted or provided as a string
     * it will return a cursor instance. It sets defaults on the cursor if needed.
     *
     * @param string|base_cursor|null $cursor
     * @return base_cursor
     */
    abstract protected static function normalise_cursor($cursor): base_cursor;

    /**
     * Validates the cursor to make sure it's valid before getting used
     *
     * @param builder|repository $query
     * @param base_cursor $cursor
     * @return void
     */
    abstract protected function validate_cursor($query, base_cursor $cursor): void;

    /**
     * Export paginator object to array but keeping the items as they are
     *
     * @return array
     */
    public function get(): array {
        return $this->get_paginated_result_for($this->items->all());
    }

    /**
     * @param array $items
     * @return array
     */
    protected function get_paginated_result_for(array $items): array {
        $result = [
            'items' => $items
        ];

        if (isset($this->total)) {
            $result['total'] = $this->total;
        }

        $result['next_cursor'] = $this->next_cursor ? $this->next_cursor->encode() : "";

        return $result;
    }

    /**
     * Returns the next cursor
     *
     * @return base_cursor|null
     */
    public function get_next_cursor(): ?base_cursor {
        return $this->next_cursor;
    }

    /**
     * Returns the current cursor
     *
     * @return base_cursor|null
     */
    public function get_current_cursor(): ?base_cursor {
        return $this->cursor;
    }

    /**
     * Returns the total number of items, can be null if total is not used
     *
     * @return int|null
     */
    public function get_total(): ?int {
        return $this->total;
    }

    /**
     * Shortcut function
     *
     * @param builder|repository $builder
     * @param base_cursor $cursor
     * @param bool $include_total
     * @return static
     */
    public static function new($builder, $cursor = null, bool $include_total = false) {
        return new static($builder, $cursor, $include_total);
    }

}