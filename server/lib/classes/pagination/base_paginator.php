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
use Countable;
use Iterator;
use JsonSerializable;

/**
 * The base class for paginators providing some common functionality true for all paginators
 */
abstract class base_paginator implements Iterator, JsonSerializable, Countable {

    /**
     * Number of items returned per page
     *
     * @var int
     */
    const DEFAULT_ITEMS_PER_PAGE = 20;

    /**
     * @var collection
     */
    protected $items;

    /**
     * Export paginator object to array
     *
     * @return array
     */
    public function to_array(): array {
        $items = $this->items->to_array();
        return $this->get_paginated_result_for($items);
    }

    /**
     * Get array representing the paginated data wrapped in the metadata
     *
     * @param array $items
     * @return array
     */
    abstract protected function get_paginated_result_for(array $items): array;

    /**
     * Transom paginated items into, this is added based on usage patterns
     *
     * @param callable $callable
     * @return $this
     */
    public function transform(callable $callable): self {
        $this->items->transform_to($callable);

        return $this;
    }

    /**
     * Get the item collection
     *
     * @return collection
     */
    public function get_items(): collection {
        return $this->items;
    }

    /**
     * Return the current element
     *
     * @return mixed Can return any type.
     */
    #[\ReturnTypeWillChange]
    public function current() {
        return $this->items->current();
    }

    /**
     * Move forward to next element
     */
    #[\ReturnTypeWillChange]
    public function next() {
        $this->items->next();
    }

    /**
     * Return the key of the current element
     *
     * @return mixed scalar on success, or null on failure.
     */
    #[\ReturnTypeWillChange]
    public function key() {
        return $this->items->key();
    }

    /**
     * Checks if current position is valid
     *
     * @return boolean The return value will be casted to boolean and then evaluated.
     */
    #[\ReturnTypeWillChange]
    public function valid() {
        return $this->items->valid();
    }

    /**
     * Rewind the Iterator to the first element
     */
    #[\ReturnTypeWillChange]
    public function rewind() {
        $this->items->rewind();
    }

    /**
     * Count elements of an object
     *
     * @return int The custom count as an integer.
     */
    #[\ReturnTypeWillChange]
    public function count() {
        return $this->items->count();
    }

    /**
     * Specify data which should be serialized to JSON
     *
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize() {
        return $this->to_array();
    }

    /**
     * Convert the collection to string will result in JSON representation of the collection
     *
     * @return false|string
     */
    public function __toString() {
        return json_encode($this, JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

}