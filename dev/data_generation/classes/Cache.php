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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 */
namespace degeneration;

use degeneration\items\item;

class Cache {

    /**
     * Cached items library
     *
     * @var array
     */
    protected $items = [];

    /**
     * Cache instance
     *
     * @var null|static
     */
    protected static $cache = null;

    /**
     * @var bool
     */
    protected static $is_enabled = true;

    /**
     * Cache constructor.
     */
    private function __construct() {
        static::$cache = $this;
    }

    /**
     * Enable caching
     */
    public static function enabled(): void {
        self::$is_enabled = true;
    }

    /**
     * Disable caching
     */
    public static function disable(): void {
        self::$is_enabled = false;
    }

    /**
     * Get cache instance
     *
     * @return static
     */
    public static function get() {
        if (!static::$cache) {
            return new static();
        }

        return static::$cache;
    }

    /**
     * Add created item to the cache
     *
     * @param item $item
     * @return $this
     */
    public function add(item $item) {
        if (!self::$is_enabled) {
            return $this;
        }
        if (!isset($this->items[$class = get_class($item)])) {
            $this->items[$class] = [];
        }

        $this->items[$class][$item->get_data('id')] = $item;

        return $this;
    }

    /**
     * Get an item (items) from cache
     *
     * @param string $class Item class to get
     * @param int|null $id Item id
     * @return array|mixed|null
     */
    public function fetch(string $class, ?int $id = null) {
        if (!self::$is_enabled) {
            return null;
        }
        if (is_null($id)) {
            return $this->items[$class] ?? [];
        }

        return $this->items[$class][$id] ?? null;
    }
}