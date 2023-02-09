<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package core
 */

namespace core;

use ArrayAccess;
use coding_exception;
use Countable;
use Iterator;
use JsonSerializable;

/**
 * This is a generic collection accepting an array of items. It provides a set of convenience method to work with the data.
 *
 * It acts as an iterator, is countable and is easily encoded to json.
 *
 * Examples:
 *
 * $collection = new collection([...]);
 * // Convert to array
 * $items = $collection->to_array();
 * // Convert to JSON
 * $items = json_encode($collection);
 * // Count
 * $count = $collection->count();
 * $count = count($collection);
 * // Iterate over items
 * foreach ($collection as $item) {
 *     // do something with the item
 * }
 * // Get values of a single key
 * $ids = $collection->pluck('id');
 *
 * For more examples check out the collection documentation in Confluence.
 */
class collection implements Iterator, JsonSerializable, Countable, ArrayAccess {

    /**
     * Items in the collection
     *
     * @var array
     */
    protected $items;

    /**
     * Glorified constructor
     *
     * @param array $items
     * @return static
     */
    public static function new(array $items) {
        return new static($items);
    }


    /**
     * collection constructor.
     *
     * @param array $items Items to add to the collection
     */
    public function __construct(array $items = []) {
        $this->items = $items;
    }

    /**
     * Append item to the collection
     *
     * @param mixed $item Item to add to collection
     * @return $this
     */
    public function append($item) {
        $this->items[] = $item;

        return $this;
    }

    /**
     * Set collection item by key
     *
     * @param mixed $item Item to add to collection
     * @param string $key Key to use supplying an existing key will override an existing item!
     * @return $this
     */
    public function set($item, string $key) {
        $this->items[$key] = $item;

        return $this;
    }

    /**
     * Get the keys for the collection items
     *
     * @return array
     */
    public function keys() {
        return array_keys($this->items);
    }

    /**
     * Pass items in the collection through a given callback and return a fresh copy
     *
     * @param callable $callable Callback
     * @return static
     */
    public function map(callable $callable) {
        return new static(array_map($callable, $this->items));
    }

    /**
     * Pass items in the collection through a given callback or map to a given class name
     *
     * @param callable|string $what A callable or class name to map collection items to
     * @return static
     */
    public function map_to($what) {
        $map_to = $what;

        if (is_string($what) && class_exists($what)) {
            $map_to = function ($item) use ($what) {
                return new $what($item);
            };
        }

        return $this->map($map_to);
    }

    /**
     * Transform collection items by passing through a callable
     *
     * @param callable $callable
     * @return $this
     */
    public function transform(callable $callable) {
        $this->items = $this->map($callable)->all(true);

        return $this;
    }

    /**
     * Transform collection items into a class or callable
     *
     * @param string|callable $what
     * @return $this
     */
    public function transform_to($what) {
        $this->items = $this->map_to($what)->all(true);

        return $this;
    }

    /**
     * Key collection items by a column
     *
     * @param string $column Column to key items by
     * @return $this
     */
    public function key_by(string $column) {
        $this->items = array_combine(array_column($this->items, $column), $this->items);

        return $this;
    }

    /**
     * Get values of a single column
     *
     * @param string $name Column name
     * @return array
     */
    public function pluck($name) {
        return array_column($this->items, $name);
    }

    /**
     * Filter items in the collection matching a giving column value or a callback
     *
     * @param string|callable $column Column name to compare value with or a custom callback to find the desired item
     * @param mixed|null $value Value to compare to
     * @param bool $strict_comparison Strict comparison in find method
     * @return static
     */
    public function filter($column, $value = null, $strict_comparison = false) {
        if (is_string($column)) {
            $callback = function ($item) use ($column, $value, $strict_comparison) {
                if (is_array($item)) {
                    $comp = $item[$column];
                } else if (is_object($item)) {
                    $comp = $item->{$column};
                } else {
                    $comp = $item;
                }

                return $strict_comparison ? $comp === $value : $comp == $value;
            };
        } else if (is_callable($column)) {
            $callback = $column;
        } else {
            throw new coding_exception('Column must be either callable or string');
        }

        return new static(array_filter($this->items, $callback));
    }

    /**
     * Checks if all items in collection has a column value or pass a callback condition.
     *
     * @param string|callable $column Column name to compare value with or a custom callback to find the desired item
     * @param mixed|null $value Value to compare to
     * @param bool $strict_comparison Strict comparison in find method
     * @return bool
     */
    public function has($column, $value = null, $strict_comparison = false): bool {
        return $this->find($column, $value, $strict_comparison) !== null;
    }

    /**
     * Return first item in the collection matching a giving column value or a callback
     *
     * @param string|callable $column Column name to compare value with or a custom callback to find the desired item
     * @param mixed|null $value Value to compare to
     * @param bool $strict_comparison Strict comparison in find method
     * @return mixed|null
     */
    public function find($column, $value = null, $strict_comparison = false) {
        return $this->filter($column, $value, $strict_comparison)->first();
    }

    /**
     * Sort collection items by a given callback
     *
     * @param string|callable $column name to sort by or callback for more complex operations
     * @param string|null $direction Sort direction ascending or descending
     * @param bool $keep_keys Maintain the original keys
     * @return $this
     * @throws coding_exception
     */
    public function sort($column, ?string $direction = 'asc', bool $keep_keys = true): self {
        $sort_asc = $direction !== null && strtolower($direction) !== 'desc';

        switch (true) {
            case is_string($column):
                $column = function ($a, $b) use ($sort_asc, $column) {
                    $get_field = function ($obj, $column) {
                        if (is_object($obj)) {
                            return $obj->{$column};
                        } else if (is_array($obj)) {
                            return $obj[$column];
                        }

                        return $obj;
                    };

                    return $sort_asc ?
                        $get_field($a, $column) <=> $get_field($b, $column) :
                        $get_field($b, $column) <=> $get_field($a, $column);
                };
                break;
            case is_callable($column):
                break;
            default:
                throw new coding_exception('Column must be either callable or string');
        }

        if ($keep_keys) {
            uasort($this->items, $column);
        } else {
            usort($this->items, $column);
        }

        return $this;
    }

    /**
     * Reduce the collection to a single value
     *
     * @param callable $callback Callback to apply
     * @param mixed|null $initial Initial value
     * @return mixed
     */
    public function reduce(callable $callback, $initial = null) {
        return array_reduce($this->items, $callback, $initial);
    }

    /**
     * pop the last element off the collection returning it
     *
     * @return mixed|null
     */
    public function pop() {
        return array_pop($this->items);
    }

    /**
     * Shift the first element off the collection returning it
     *
     * @return mixed|null
     */
    public function shift() {
        return array_shift($this->items);
    }

    /**
     * Returns the last item
     *
     * @return mixed|null
     */
    public function last() {
        $items = $this->all();
        return end($items) ?? null;
    }

    /**
     * Return all items in the collection
     *
     * @param bool $with_keys Preserve original keys
     * @return array
     */
    public function all(bool $with_keys = false): array {
        return $with_keys ? $this->items : array_values($this->items);
    }

    /**
     * Return first item in the collection
     *
     * @return mixed|null
     */
    public function first() {
        return $this->all()[0] ?? null;
    }

    /**
     * Get a single element identified by key from the collection
     *
     * @param string|int $key Item key
     * @return mixed|null
     */
    public function item($key) {
        return $this->items[$key] ?? null;
    }

    /**
     * Returns an array representation of this array with all first level objects normalised to arrays.
     * WARNING: key associations will not be maintained.
     *
     * @return array
     */
    public function to_array(): array {
        $output = [];

        foreach ($this->items as $item) {
            if (is_object($item) && method_exists($item, 'to_array')) {
                $output[] = $item->to_array();
            } else {
                $output[] = is_object($item) ? (array) $item : $item;
            }
        }

        return $output;
    }

    /**
     * Check whether an item in the collection is set by key
     *
     * @param string|int $name
     * @return bool
     */
    public function __isset($name) {
        return isset($this->items[$name]);
    }

    /**
     * Unset item in the collection by key
     *
     * @param string|int $name
     */
    public function __unset($name) {
        unset($this->items[$name]);
    }

    /**
     * Return the current element
     *
     * @return mixed Can return any type.
     */
    #[\ReturnTypeWillChange]
    public function current() {
        return current($this->items);
    }

    /**
     * Move forward to next element
     */
    #[\ReturnTypeWillChange]
    public function next() {
        next($this->items);
    }

    /**
     * Return the key of the current element
     *
     * @return mixed scalar on success, or null on failure.
     */
    #[\ReturnTypeWillChange]
    public function key() {
        return key($this->items);
    }

    /**
     * Checks if current position is valid
     *
     * @return boolean The return value will be casted to boolean and then evaluated.
     */
    #[\ReturnTypeWillChange]
    public function valid() {
        $key = $this->key();

        return $key !== false && !is_null($key);
    }

    /**
     * Rewind the Iterator to the first element
     *
     * @return void Any returned value is ignored.
     */
    #[\ReturnTypeWillChange]
    public function rewind() {
        reset($this->items);
    }

    /**
     * Specify data which should be serialized to JSON
     *
     * @return mixed data which can be serialized by <b>json_encode</b>,
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

    /**
     * Count elements of an object
     *
     * @return int The custom count as an integer.
     */
    public function count(): int {
        return count($this->items);
    }

    public function offsetExists($offset): bool {
        return array_key_exists($offset, $this->items);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset) {
        return $this->item($offset);
    }

    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value): void {
        $this->set($offset, $value);
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($offset): void {
        unset($this->items[$offset]);
    }
}
