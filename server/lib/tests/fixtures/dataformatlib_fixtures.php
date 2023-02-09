<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package core_dataformat
 */

namespace dataformat_mock;

use core\dataformat\base;

defined('MOODLE_INTERNAL') || die();

/**
 * Mock class to imitate a data format without HTML support
 */
class writer extends base {
    /**
     * Override & prevent any records from being written.
     *
     * @param array $record
     * @param int $rownum
     */
    public function write_record($record, $rownum) {
        // The mock method has no body
    }

    /**
     * Prevent headers from being sent & crashing the unit tests
     */
    public function send_http_headers() {
        // Prevent headers from being sent during tests
    }
}

namespace dataformat_mock_html;

/**
 * Mock class to imitate a data format with HTML support
 */
class writer extends \dataformat_mock\writer {
    public function supports_html() {
        return true;
    }
}