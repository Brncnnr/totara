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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package core
 * @group orm
 */

namespace core\orm;

use coding_exception;
use Iterator;
use moodle_recordset;

/**
 * Class lazy_collection
 *
 * A wrapper over a moodle_recordset to provide mapping functionality
 *
 * @package core\orm
 */
class lazy_collection extends moodle_recordset implements Iterator {

    /**
     * Items in the collection
     *
     * @var moodle_recordset
     */
    protected $recordset;

    /**
     * @var string
     */
    protected $map_to;

    /**
     * @var bool
     */
    protected $as_array = false;

    /**
     * Glorified constructor to improve code flow
     *
     * @param moodle_recordset $recordset Recordset to create a collection from
     * @return lazy_collection
     */
    public static function create(moodle_recordset $recordset) {
        return new static($recordset);
    }

    /**
     * lazy_collection constructor.
     *
     * @param moodle_recordset $recordset Recordset to create a collection from
     * @param string|callable|null $map_to Map all the collection results to a class or callable
     */
    public function __construct(moodle_recordset $recordset, $map_to = null) {
        $this->recordset = $recordset;
        $this->map_to = $map_to;
    }

    /**
     * Map the recordset items to a given something
     *
     * @param string|callable|null $what Map all the collection results to a class or callable
     * @return $this
     */
    public function map_to($what) {
        if (!is_null($what) && !is_callable($what) && !class_exists($what)) {
            throw new coding_exception('Cannot map to something that is not callable or a class');
        }

        $this->map_to = $what;

        return $this;
    }

    /**
     * Convert stdClass to array
     *
     * @param bool $as_array
     * @return $this
     */
    public function as_array(bool $as_array = true) {
        $this->as_array = $as_array;

        return $this;
    }

    /**
     * Process result and pass it through array conversion or a callback if given
     *
     * @param $item
     * @return array
     */
    protected function map_result($item) {
        if ($this->as_array) {
            $item = (array) $item;
        }

        $map = $this->map_to;

        if (is_null($map)) {
            return $item;
        }

        if (is_callable($map)) {
            return $map($item);
        }

        if (class_exists($map)) {
            return new $map($item);
        }

        return $item;
    }

    /**
     * Return the current element
     *
     * @return array|mixed an instance
     */
    #[\ReturnTypeWillChange]
    public function current() {
        return $this->map_result($this->recordset->current());
    }

    /**
     * Move forward to next element
     * @return void Any returned value is ignored.
     */
    #[\ReturnTypeWillChange]
    public function next() {
        $this->recordset->next();
    }

    /**
     * Return the key of the current element
     * @return mixed scalar on success, or null on failure.
     */
    #[\ReturnTypeWillChange]
    public function key() {
        return $this->recordset->key();
    }

    /**
     * Checks if current position is valid
     * @return boolean The return value will be casted to boolean and then evaluated.
     */
    #[\ReturnTypeWillChange]
    public function valid() {
        return $this->recordset->valid();
    }

    /**
     * Rewind the Iterator to the first element
     * @return void Any returned value is ignored.
     */
    #[\ReturnTypeWillChange]
    public function rewind() {
        // will be ignored.
        $this->recordset->rewind();
    }

    /**
     * Frees up the memory
     *
     * @return void
     */
    public function close() {
        $this->recordset->close();
    }

    /**
     *  Close the recordset when collection object is destroyed
     */
    public function __destruct() {
        $this->close();
    }

}