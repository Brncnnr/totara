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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package totara_reportedcontent
 */

namespace totara_reportedcontent\rb\display;

use \totara_reportbuilder\rb\display\base;
use \totara_reportbuilder\rb\display\format_string;

/**
 * Display class intended to convert a url into a link
 */
class reportedcontent_link extends base {

    /**
     * Handles the display
     *
     * @param string $value
     * @param string $format
     * @param \stdClass $row
     * @param \rb_column $column
     * @param \reportbuilder $report
     * @return string
     */
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) : string {
        $url = format_string::display($value, $format, $row, $column, $report);
        if ($format !== 'html') {
            return $url;
        }

        // If the link's over 40 characters long, we'll truncate it
        $link_text = $url;
        if (strlen($link_text) >= 40) {
            $link_text = substr($link_text, 0, 40) . '...';
        }

        // Make it into an a link, text & url are both the same
        return \html_writer::link($url, $link_text);
    }

    /**
     * Is this column graphable?
     *
     * @param \rb_column $column
     * @param \rb_column_option $option
     * @param \reportbuilder $report
     * @return bool
     */
    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return false;
    }
}
