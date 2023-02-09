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

namespace core\orm;

use core\orm\query\builder;
use core\pagination\base_paginator;

/**
 * The paginator can be used to generate paginated results from a query.
 * The builder instance containing the conditions need to be passed. The simple mode is used to provide a
 * load more functionality which does not need total counts.
 *
 * It extends the collection so it inherits all the convenience methods it offers. The only difference is
 * that items cannot be added or set. However, you can map and transform items.
 *
 * Example:
 *
 * $query = builder::table('user')
 *    ->where('deleted', 0);
 *
 * $paginator = new paginator($query, 1, 10);
 *
 * echo $paginator->get_total()." entries found.";
 *
 * // Convert data into wrapped array containing metadata
 * print_r($paginator->to_array());
 *
 * For more examples check out the paginator documentation in Confluence.
 */
class paginator extends base_paginator {

    /**
     * Current page
     *
     * @var int
     */
    protected $page = 0;

    /**
     * Total number of pages
     *
     * @var int
     */
    protected $pages;

    /**
     * Total number of results
     *
     * @var int
     */
    protected $total;

    /**
     * Next page
     *
     * @var int
     */
    protected $next;

    /**
     * Previous page
     *
     * @var int
     */
    protected $prev;

    /**
     * Number of items per page
     *
     * @var int
     */
    protected $per_page;

    /**
     * A flag to indicate whether it's a simple "Load more" type
     * pagination or the one with the total count of pages
     *
     * @var bool
     */
    protected $simple;

    /**
     * @param builder $builder pass a query to paginate
     * @param int $page page number, optional, starts with 1, pass 0 to load all records
     * @param int $per_page number of items per page, optional, pass 0 to use default
     * @param bool $simple use simple paginator for simple load more? optional, defaults to false
     */
    public function __construct(builder $builder, int $page = 1, int $per_page = 0, bool $simple = false) {
        if (!$per_page && $page > 0) {
            $per_page = self::DEFAULT_ITEMS_PER_PAGE;
        }

        $offset = $limit = 0;
        if ($page > 0) {
            $offset = ($page - 1) * $per_page;
            $limit = $per_page;
        }

        $total = null;
        if (!$simple) {
            [$items, $total] = $builder
                ->offset($offset)
                ->limit($limit)
                ->fetch_counted();
        } else {
            $items = $builder
                ->offset($offset)
                ->limit($limit)
                ->fetch();
        }

        // Special case: all results
        if ($page === 0) {
            $per_page = $total;
        }

        $this->page = $page;
        $this->per_page = $per_page;
        $this->total = $total;
        $this->simple = $simple;

        $this->calculate_pages();

        $this->items = new collection($items);
    }

    /**
     * Get array representing the paginated data wrapped in the metadata
     *
     * @param array $items
     * @return array
     */
    protected function get_paginated_result_for(array $items): array {
        if ($this->simple) {
            return [
                'items' => $items,
                'page' => $this->page,
                'per_page' => $this->per_page,
            ];
        } else {
            return [
                'items' => $items,
                'page' => $this->page,
                'pages' => $this->pages,
                'per_page' => $this->per_page,
                'next' => $this->next,
                'prev' => $this->prev,
                'total' => $this->total
            ];
        }
    }

    /**
     * Calculate the number of pages and previous and next page numbers
     *
     * @return paginator
     */
    protected function calculate_pages(): self {
        if ($this->simple) {
            return $this;
        }

        $this->pages = $this->page > 0 ? (int)ceil($this->total / $this->per_page) : 1;
        $this->prev = $this->page > 0 && ($this->page > 1) ? $this->page - 1 : null;
        $this->next = $this->page > 0 && ($this->page < $this->pages) ? $this->page + 1 : null;

        return $this;
    }

    /**
     * Returns true if the paginator is a simple one
     *
     * @return bool
     */
    public function is_simple(): bool {
        return $this->simple;
    }

    /**
     * Returns the current page number
     *
     * @return int
     */
    public function get_page(): int {
        return $this->page;
    }

    /**
     * Returns the total number of pages
     *
     * @return int
     */
    public function get_pages(): ?int {
        return $this->pages;
    }

    /**
     * Returns the previous page number, null if it's first page
     *
     * @return int
     */
    public function get_prev(): ?int {
        return $this->prev;
    }

    /**
     * Returns the next page number, null if it's the last page
     *
     * @return int
     */
    public function get_next(): ?int {
        return $this->next;
    }

    /**
     * Returns total number of records fdund
     *
     * @return int
     */
    public function get_total(): ?int {
        return $this->total;
    }

    /**
     * Returns number of items per page
     *
     * @return int
     */
    public function get_per_page(): int {
        return $this->per_page;
    }

    /**
     * Glorified constructor
     *
     * @param builder $builder
     * @param int $page
     * @param int $per_page
     * @param bool $simple
     * @return paginator
     */
    public static function new(builder $builder, int $page = 1, int $per_page = 0, bool $simple = false) {
        return new static($builder, $page, $per_page, $simple);
    }

}