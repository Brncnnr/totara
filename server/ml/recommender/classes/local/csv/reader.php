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
 * @author  Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package ml_recommender
 */

namespace ml_recommender\local\csv;

/**
 * Class reader Simple stream implementation of CSV parser
 * @deprecated since Totara 17.0 ml_recommender importer has been deprecated.
 */
class reader implements \Iterator {
    /**
     * @var resource File pointer
     */
    protected $fp = null;

    /**
     * @var string CSV file path
     */
    protected $csvpath = '';

    /**
     * @var array CSV Headers
     */
    protected $headers = [];

    /**
     * @var int Iterator key
     */
    protected $key = 0;

    /**
     * @var array Current Element
     */
    protected $current = [];

    /**
     * @var bool If current element is valid
     */
    protected $valid = true;

    public function __construct(string $csvpath) {
        $this->csvpath = $csvpath;
        $this->rewind();
    }

    /**
     * Loads file headers (assumes that File pointer is in the beginning of the file)
     */
    protected function fetch_headers() {
        $this->headers = fgetcsv($this->fp);
    }

    #[\ReturnTypeWillChange]
    public function current() {
        return $this->current;
    }

    #[\ReturnTypeWillChange]
    public function next() {
        $next = fgetcsv($this->fp);
        if (!$next) {
            $this->current = [];
            $this->valid = false;
            $this->key = 0;
            $this->close();
            return;
        }
        $this->key++;
        $this->current = array_combine($this->headers, $next);
    }

    #[\ReturnTypeWillChange]
    public function key() {
        return $this->key;
    }

    #[\ReturnTypeWillChange]
    public function valid() {
        return $this->valid;
    }

    #[\ReturnTypeWillChange]
    public function rewind() {
        if (!$this->fp) {
            $this->fp = fopen($this->csvpath, 'r');
            if (!$this->fp) {
                throw new \coding_exception('Could not open CSV file for reading ' . $this->csvpath);
            }
        }
        rewind($this->fp);
        $this->fetch_headers();
        $this->valid = true;
        $this->key = 0;
        $this->next();
    }

    /**
     * Close CSV file
     */
    public function close() {
        if ($this->fp) {
            fclose($this->fp);
        }
        $this->fp = null;
    }

    public function __destruct() {
        $this->close();
    }
}