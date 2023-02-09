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
 * PostgreSQL specific recordset with buffered cursor fetching.
 */
final class pgsql_native_huge_recordset extends moodle_recordset {

    /** @var array fetched block of records, in reversed order */
    protected $buffer = [];
    /** @var array|false current row as array false when end reached */
    protected $current = false;
    /** @var string|null Name of cursor or null if already closed */
    protected $cursorname;

    /** @var pgsql_native_moodle_database PostgreSQL database */
    protected $db;

    /**
     * Build a new recordset to iterate over.
     *
     * @param pgsql_native_moodle_database $db Database object
     * @param string $cursorname Name of cursor
     */
    public function __construct(pgsql_native_moodle_database $db, string $cursorname) {
        $this->db = $db;
        $this->cursorname = $cursorname;
        $this->current = $this->fetch_next();
    }

    private function fetch_next() {
        if ($this->buffer) {
            return array_pop($this->buffer);
        }
        if ($this->cursorname === null) {
            // Cursor already closed.
            return false;
        }
        $this->buffer = $this->db->_fetch_from_cursor($this->cursorname);
        if (!$this->buffer) {
            // No more rows available.
            $this->db->_close_cursor($this->cursorname);
            $this->cursorname = null;
            $this->db = null;
            return false;
        }

        $maxcount = $this->db->_get_fetch_buffer_size();
        if (count($this->buffer) < $maxcount) {
            // Nothing more to fetch next time.
            $this->db->_close_cursor($this->cursorname);
            $this->cursorname = null;
            $this->db = null;
        }
        // Reverse the buffer to use cheaper array_pop() later.
        $this->buffer = array_reverse($this->buffer, true);
        return array_pop($this->buffer);
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
        $columns = get_mangled_object_vars($this->current);
        return reset($columns);
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
        // Close the cursor if still open.
        if ($this->cursorname !== null) {
            $this->db->_close_cursor($this->cursorname);
            $this->cursorname = null;
            $this->db = null;
        }
        // Release all memory.
        $this->current = false;
        $this->buffer = [];
    }

    public function __destruct() {
        try {
            $this->close();
        } catch (Throwable $e) {
            // Ignore errors, the PHP might be shutting down,
            // normally the recordset gets closed at the end of iteration
            // or when close() is called manually.
        }
    }
}
