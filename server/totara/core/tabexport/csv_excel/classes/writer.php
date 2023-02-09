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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package tabexport_csv_excel
 */

namespace tabexport_csv_excel;

use totara_core\tabexport_source;

defined('MOODLE_INTERNAL') || die();

/**
 * Export data in Excel compatible CSV format.
 *
 * Final as we don't want multiple dependencies to be formed unintentionally.
 */
final class writer extends \tabexport_csv\writer {
    public function __construct(tabexport_source $source) {
        $this->addbom = true;
        parent::__construct($source);
    }

    /**
     * The character to use as the escape character.
     *
     * Testing in Excel, Libre Office and Google sheets.
     * Tab gave the most consistent outcome, and the best looking outcome.
     * All four did not show any space at the start of the data when observing the cell.
     * All four when you entered the cell showed the tab in front of equations within the text field for editing the call.
     *
     * @var string
     */
    private static $formula_escape_char = "\t";

    /**
     * Adds the row to the CSV file.
     *
     * This function has been overridden to escape formula.
     *
     * @param resource $handle
     * @param array $row
     */
    protected function add_row($handle, $row) {
        // Add single quote in front of all values starting with one of =@+-
        // https://www.contextis.com/en/blog/comma-separated-vulnerabilities
        $row = array_map([static::class, 'escape_formula'], $row);
        parent::add_row($handle, $row);
    }

    /**
     * Adds an escape character to the data if the data appears to be a formula.
     *
     * @param string $data String data to escape, if it appears to be a formula.
     * @return string
     */
    private static function escape_formula(string $data) {
        if (strpos("=@+-", \core_text::substr($data, 0, 1)) !== false) {
            // Simply having one of the above characters is enough to be considered a formula.
            // It may not be e.g. -20.
            // However for consistency sake anything that starts with these characters is escaped.
            return self::$formula_escape_char . $data;
        }
        return $data;
    }
}
