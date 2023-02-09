<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package totara_job
 */

namespace totara_job\data_provider;

use core\collection;
use core\entity\user;
use core\orm\entity\repository;
use core\orm\pagination\cursor_paginator;
use core\orm\pagination\offset_cursor_paginator;
use core\pagination\base_paginator;
use core\pagination\cursor as cursor;
use core\pagination\offset_cursor as offset_cursor;
use totara_job\job_assignment;

/**
 * Common logic for filtering, fetching and getting data for use in queries etc.
 *
 * @package totara_job\data_provider
 */
abstract class provider {

    /**
     * Array of filters to apply when fetching the data
     *
     * @var array
     */
    protected $filters = [];

    /**
     * Array of fields that the repository can be sorted by.
     *
     * @var array
     */
    protected $sort_by_fields = [];

    /**
     * Name of the function to sort by.
     *
     * @var array
     */
    protected $sort_by;

    /**
     * Return whether data has been fetched
     *
     * @var bool
     */
    protected $fetched = false;

    /**
     * @var collection
     */
    protected $items;

    /**
     * Build the base ORM query using the relevant repository.
     *
     * @return repository
     */
    abstract protected function build_query(): repository;

    /**
     * Add filters for this provider.
     *
     * @param array $filters
     * @return $this
     */
    final public function add_filters(array $filters): self {
        $this->filters = array_merge(
            $this->filters,
            array_filter($filters, static function ($filter_value) {
                return isset($filter_value);
            })
        );

        return $this;
    }

    /**
     * Sort the results in a specific way.
     *
     * @param array $sort
     * @return $this
     */
    final public function sort_by(array $sort): self {
        // Skip if empty.
        if (empty($sort)) {
            return $this;
        }

        // Enforce a structure on the sort_type array.
        if (empty($sort['column'])) {
            throw new \coding_exception("Sort parameter must have a 'column' key");
        }
        if (empty($sort['direction'])) {
            $sort['direction'] = 'ASC';
        }
        if (!in_array($sort['direction'], ['ASC', 'DESC'])) {
            throw new \coding_exception("Invalid sort direction");
        }

        // Check that column exists as a sort option.
        if (!in_array($sort['column'], $this->sort_by_fields)) {
            throw new \coding_exception("Unknown sort column");
        }

        // Set property.
        $this->sort_by = $sort;
        return $this;
    }

    /**
     * Apply filters to a given repository before it is fetched from the database.
     *
     * To add a query filter, define a method like:
     * ```php
     *     protected function filter_query_by_FILTERNAME(repository $repository, mixed $filter_value): void { ... }
     * ```
     *
     * @param repository $repository Repository to apply filters
     * @return $this
     */
    protected function apply_query_filters(repository $repository): self {
        foreach ($this->filters as $key => $value) {
            if ($this->fetched) {
                throw new \coding_exception('Must call apply_query_filters() before fetching.');
            }

            if (!method_exists($this, 'filter_query_by_' . $key)) {
                throw new \coding_exception("Filtering by '{$key}' is not supported");
            }

            $this->{'filter_query_by_' . $key}($repository, $value);
        }

        return $this;
    }

    /**
     * Apply sorting to a given repository before it is fetched from the database.
     *
     * To add a query filter, define a method like:
     * ```php
     *     protected function sort_query_by_SORTNAME(repository $repository): void { ... }
     * ```
     *
     * @param repository $repository Repository to apply sorting to
     * @return $this
     */
    protected function apply_query_sorting(repository $repository): self {
        if ($this->fetched) {
            throw new \coding_exception('Must call apply_query_sorting() before fetching.');
        }

        if (!empty($this->sort_by)) {
            $repository->order_by($this->sort_by['column'], $this->sort_by['direction'] ?? 'ASC');
        }

        if (!$repository->has_order_by()) {
            // If no order is set, then fallback to id to prevent random unit test failures (due to unpredictable sorting)
            $repository->order_by('id');
        }

        return $this;
    }

    /**
     * (Optionally) augment the fetched items before returning them with get().
     *
     * @return collection
     */
    protected function process_fetched_items(): collection {
        // Do nothing here, override in subclasses if needed.
        return $this->items;
    }

    /**
     * Run the ORM query and mark the data provider as already fetched.
     */
    public function fetch(): self {
        $this->fetched = false;

        $query = $this->build_query();
        $this->apply_query_filters($query);
        $this->apply_query_sorting($query);

        $this->items = $query->get();
        $this->fetched = true;
        $this->items = $this->process_fetched_items();

        return $this;
    }

    /**
     * Get the queried items.
     *
     * @return collection
     */
    public function get(): collection {
        if (!$this->fetched) {
            $this->fetch();
        }

        return $this->items;
    }

    /**
     * Returns a specific page of items as a stdClass paged result.
     *
     * @param int $page_size
     * @param int $page_requested
     * @return \stdClass
     */
    public function get_offset_page(int $page_size = cursor_paginator::DEFAULT_ITEMS_PER_PAGE, int $page_requested = 1): \stdClass {
        $cursor = offset_cursor::create()->set_limit($page_size)->set_page($page_requested);
        $paginator = $this->get_offset_paginator($cursor);
        $items = $paginator->get_items()->map_to([job_assignment::class, 'from_entity']);
        $next_cursor = $paginator->get_next_cursor();

        return (object)[
            'items' => $items,
            'total' => $paginator->get_total(),
            'next_cursor' => $next_cursor === null ? '' : $next_cursor->encode(),
        ];
    }

    /**
     * Move the paginator to the next set of results and return it
     * NOTE: The caller is expected to call the applicable paginator methods to obtain the items, next_cursor, etc.
     *
     * @param offset_cursor $offset_cursor Caller should initialize
     * @return offset_cursor_paginator
     */
    protected function get_offset_paginator(offset_cursor $offset_cursor): offset_cursor_paginator {
        $query = $this->build_query();
        $this->apply_query_filters($query);
        $this->apply_query_sorting($query);

        $paginator = new offset_cursor_paginator($query, $offset_cursor);
        $paginator->get();

        return $paginator;
    }

    /**
     * Returns next page of items from opaque cursor as a stdClass paged result.
     *
     * @param string $opaque_cursor
     * @param int $page_size
     * @return \stdClass
     */
    public function get_page(string $opaque_cursor = null, int $page_size = base_paginator::DEFAULT_ITEMS_PER_PAGE): \stdClass {
        if (is_null($opaque_cursor) || $opaque_cursor === '') {
            $local_cursor = cursor::create()->set_limit($page_size);
        } else {
            $local_cursor = cursor::decode($opaque_cursor);
        }
        $paginator = $this->get_paginator($local_cursor);
        $items = $paginator->get_items()->map_to([job_assignment::class, 'from_entity']);
        $next_cursor = $paginator->get_next_cursor();

        return (object)[
            'items' => $items,
            'total' => $paginator->get_total(),
            'next_cursor' => $next_cursor === null ? '' : $next_cursor->encode(),
        ];
    }

    /**
     * Move the paginator to the next set of results and return it
     * NOTE: The caller is expected to call the applicable paginator methods to obtain the items, next_cursor, etc.
     *
     * @param cursor $cursor Caller should initialize
     * @return cursor_paginator
     */
    protected function get_paginator(cursor $cursor): cursor_paginator {
        $query = $this->build_query();
        $this->apply_query_filters($query);
        $this->apply_query_sorting($query);

        $paginator = new cursor_paginator($query, $cursor, true);
        $paginator->get();

        return $paginator;
    }
}
