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

final class sqlsrv_native_huge_recordset extends moodle_recordset {
    protected $rsrc;
    protected $current;

    public function __construct($rsrc) {
        $this->rsrc = $rsrc;
        $this->current = $this->fetch_next();
    }

    private function fetch_next() {
        if (!$this->rsrc) {
            return false;
        }
        if (!$row = sqlsrv_fetch_array($this->rsrc, SQLSRV_FETCH_ASSOC)) {
            sqlsrv_free_stmt($this->rsrc);
            $this->rsrc = null;
            return false;
        }
        $row = array_change_key_case($row, CASE_LOWER);
        // Moodle expects everything from DB as strings.
        foreach ($row as $k => $v) {
            if (is_null($v)) {
                continue;
            }
            if (!is_string($v)) {
                $row[$k] = (string)$v;
            }
        }
        return $row;
    }

    #[\ReturnTypeWillChange]
    public function current() {
        return (object)$this->current;
    }

    #[\ReturnTypeWillChange]
    public function key() {
        // Return first column value as key.
        if (!$this->current) {
            return false;
        }
        $key = reset($this->current);
        return $key;
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
        if ($this->rsrc) {
            sqlsrv_free_stmt($this->rsrc);
            $this->rsrc = null;
        }
        $this->current = null;
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
