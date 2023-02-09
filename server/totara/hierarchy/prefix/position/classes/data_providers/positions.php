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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package hierarchy_position
 */

namespace hierarchy_position\data_providers;

use coding_exception;
use core\orm\pagination\cursor_paginator;
use core\pagination\cursor;
use hierarchy_position\entity\position;
use hierarchy_position\entity\position_filters;

defined('MOODLE_INTERNAL') || die();

/**
 * "Model" for dealing with collections of positions.
 */
class positions {
    public const DEFAULT_PAGE_SIZE = 20;

    private const VALID_ORDER_BY_FIELDS = ['id', 'fullname'];
    private const VALID_ORDER_DIRECTION = ['asc', 'desc'];

    private $page_size = self::DEFAULT_PAGE_SIZE;
    private $order_by = 'fullname';
    private $order_direction = 'asc';
    private $filters = [];
    private $mandatory_filters = [];

    /**
     * Default constructor.
     */
    public function __construct() {
        $this->mandatory_filters = ['visible' => true];
        $this->set_filters([]);
    }

    /**
     * Indicates the number of entries retrieved per page.
     *
     * @param int $page_size page size.
     *
     * @return positions this object.
     */
    public function set_page_size(int $page_size): positions {
        $this->page_size = $page_size > 0 ? $page_size : self::DEFAULT_PAGE_SIZE;
        return $this;
    }

    /**
     * Indicates the sorting parameters to use when retrieving positions.
     *
     * @param string $order_by position field on which to sort.
     * @param string $order_direction sorting order either 'ASC' or 'DESC'.
     *
     * @return positions this object.
     */
    public function set_order(string $order_by = 'fullname', string $order_direction = 'asc'): positions {
        $order_by = strtolower($order_by);
        if (!in_array($order_by, self::VALID_ORDER_BY_FIELDS)) {
            $allowed = implode(', ', self::VALID_ORDER_BY_FIELDS);
            throw new coding_exception("Ordering can only be by these fields: $allowed");
        }
        $this->order_by = $order_by;

        $order_direction = strtolower($order_direction);
        if (!in_array($order_direction, self::VALID_ORDER_DIRECTION)) {
            $allowed = implode(', ', self::VALID_ORDER_DIRECTION);
            throw new coding_exception("Order must be one of these: $allowed");
        }
        $this->order_direction = $order_direction;

        return $this;
    }

    /**
     * Indicates the filters to use when retrieving positions.
     *
     * @param array $filters mapping of position fields to search values.
     *
     * @return positions this object.
     */
    public function set_filters(array $filters): positions {
        $new_filters = [];
        foreach ($filters as $key => $value) {
            $filter_value = $this->validate_filter_value($value);
            if (is_null($filter_value)) {
                continue;
            }

            $filter = position_filters::for_key($key, $filter_value);
            if (!$filter) {
                throw new coding_exception("Unknown position filter: '$key'");
            }

            $new_filters[$key] = $filter;
        }

        $this->filters = array_merge($this->mandatory_filters, $new_filters);
        return $this;
    }

    /**
     * Checks whether the filter value is "valid". "Valid" means:
     *   - a non empty string after it has been trimmed
     *
     * @param mixed $value the value to check.
     *
     * @return mixed the filter value if it is "valid" or null otherwise.
     */
    private function validate_filter_value($value) {
        if (is_string($value)) {
            $str_value = trim($value);
            return $str_value ? $str_value : null;
        }

        return $value;
    }

    /**
     * Returns a list of positions meeting the previously set search criteria.
     *
     * @param cursor $cursor indicates which "page" of positions to retrieve.
     *
     * @return position[] the retrieved position entities.
     */
    public function fetch_paginated(?cursor $cursor = null): array {
        $repository = position::repository()
            ->set_filters($this->filters)
            ->order_by($this->order_by, $this->order_direction);

        $pages = $cursor ? $cursor : cursor::create()->set_limit($this->page_size);
        $paginator = new cursor_paginator($repository, $pages, true);

        return $paginator->get();
    }
}