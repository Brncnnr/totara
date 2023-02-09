<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\traits;

use mod_facetoface\seminar_iterator_item;

defined('MOODLE_INTERNAL') || die();

/**
 * Class seminar_iterator, Interface for seminar iterators or objects that can be iterated themselves internally.
 *
 * WARNING: This class requires the items to have a couple of methods.
 * All items should implement seminar_iterator_item in order to ensure that they have the correct methods.
 * It is up to the utilising class to ensure this is adhered to.
 *
 * @package mod_facetoface
 */
trait seminar_iterator {

    /**
     * @var seminar_iterator_item[] seminar items, implementors choose the type.
     */
    protected $items = [];

    /**
     * Remove item from list
     * @param int $id
     */
    public function remove(int $id) {
        unset($this->items[$id]);
    }

    /**
     * Delete seminar item from item list, and from the system!
     *
     * WARNING: This deletes the item from the system as well.
     */
    public function delete() {
        foreach ($this->items as $item) {
            $this->remove($item->get_id());
            $item->delete();
        }
    }

    /**
     * Iterator interface implementation
     */

    /**
     * Return the current element
     *
     * @link http://php.net/manual/en/iterator.current.php
     * @return seminar_iterator_item
     */
    #[\ReturnTypeWillChange]
    public function current() {
        return current($this->items);
    }

    /**
     * Move forward to next element
     *
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    #[\ReturnTypeWillChange]
    public function next() {
        next($this->items);
    }

    /**
     * Return the key of the current element
     *
     * @link http://php.net/manual/en/iterator.key.php
     * @return int id on success, or null on failure.
     */
    #[\ReturnTypeWillChange]
    public function key(): ?int {
        return key($this->items);
    }

    /**
     * Checks if current position is valid
     *
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean Returns true on success or false on failure.
     */
    public function valid() : bool {
        return !empty($this->current());
    }

    /**
     * Rewind the Iterator to the first element
     *
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function rewind() {
        reset($this->items);
    }

    /**
     * Check if list contains the record for a given id
     * @param int $itemid
     * @return bool
     */
    public function contains(int $itemid) : bool {
        return isset($this->items[$itemid]);
    }

    /**
     * @return int
     */
    public function count() : int {
        return count($this->items);
    }

    /**
     * Check if the items list is empty
     * @return bool
     */
    public function is_empty() : bool {
        return empty($this->items);
    }

    /**
     * Returns an instance of the specified seminar_iterator_item matching the given id.
     *
     * If no item matches then null is returned.
     *
     * @param int $itemid
     * @return seminar_iterator_item|null
     */
    public function get(int $itemid) {
        if ($this->contains($itemid)) {
            return $this->items[$itemid];
        }
        return null;
    }

    /**
     * Get items as an array.
     *
     * @param boolean $use_keys
     * @return seminar_iterator_item[]
     */
    public function to_array(bool $use_keys = true): array {
        $items = $this->items;
        if (!$use_keys) {
            $items = array_values($items);
        }
        reset($items);
        return $items;
    }
}
