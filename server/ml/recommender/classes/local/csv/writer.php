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

use coding_exception;

/**
 * Class writer Simple stream implementation of CSV writer
 */
class writer {
    /**
     * @var resource File pointer
     */
    protected $fp = null;

    /**
     * @var string CSV file path
     */
    protected $csvpath = '';

    /**
     * @var bool Flag to check if headers can be written
     */
    protected $rowsadded = false;

    /**
     * writer constructor.
     *
     * @param string $csvpath
     */
    public function __construct(string $csvpath) {
        $this->csvpath = $csvpath;
        $this->fp = fopen($this->csvpath, 'w+');
        if (!$this->fp) {
            throw new coding_exception('Could not open CSV for writing: ' . $csvpath);
        }
    }

    /**
     * Write headers for CSV (must be executed first and only once)
     * @param array $headers
     * @return void
     */
    public function add_headings(array $headers) {
        if ($this->rowsadded) {
            throw new coding_exception('Could not write headers to CSV since rows were already added');
        }
        $this->add_data($headers);
    }

    /**
     * @param array $row
     * @return void
     */
    public function add_data(array $row) {
        if (false === fputcsv($this->fp, $row)) {
            throw new coding_exception('Could not write to CSV file');
        }
        $this->rowsadded = true;
    }

    /**
     * Close CSV file
     */
    public function close() {
        if ($this->fp) {
            fclose($this->fp);
            $this->fp = null;
        }
    }

    public function __destruct() {
        $this->close();
    }
}