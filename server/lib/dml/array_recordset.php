<?php
/*
 * This file is part of Totara LMS
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package core
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/moodle_recordset.php');

/**
 * Recorset on top of simple array.
 */
final class array_recordset extends moodle_recordset {

    /** @var array fetched block of records, in reversed order */
    protected $buffer;
    /** @var array|false current row as array false when end reached */
    protected $current = false;

    /**
     * @param array $data.
     */
    public function __construct(array $data) {
        $this->buffer = array_reverse($data, true);
        $this->current = $this->fetch_next();
    }

    private function fetch_next() {
        if ($this->buffer) {
            return array_pop($this->buffer);
        }
        return false;
    }

    #[\ReturnTypeWillChange]
    public function current() {
        if (!$this->current) {
            return false;
        }
        return (object)$this->current;
    }

    #[\ReturnTypeWillChange]
    public function key() {
        // return first column value as key
        if (!$this->current) {
            return false;
        }
        // Totara: From PHP 8.1 objects and arrays are not interchangeable
        $current = is_object($this->current) ? get_object_vars($this->current) : $this->current;
        return reset($current);
    }

    #[\ReturnTypeWillChange]
    public function next() {
        $this->current = $this->fetch_next();
    }

    #[\ReturnTypeWillChange]
    public function valid() {
        return !empty($this->current);
    }

    public function close() {
        // Release all memory.
        $this->current = false;
        $this->buffer = [];
    }

    public function __destruct() {
        $this->close();
    }
}
