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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package tool_usertours
 */

/**
 * Adds wildcard to the end of every matchpattern string that doesn't have it
 * This is done for maintaining backwards compatibility with setups that already have user tours with URL matching old behavior
 */
function tool_usertours_upgrade_addsuffixwildcard() {
    global $DB;

    // Get all records with pathmath not ending on '%'
    $tours = $DB->get_records_sql(
        'SELECT id, pathmatch FROM {tool_usertours_tours} WHERE ' .
        $DB->sql_like('pathmatch', ':wild', true, true, true),
        ['wild' => '%\%']
    );

    foreach ($tours as $tour) {
        if ($tour->pathmatch == 'FRONTPAGE') {
            continue;
        }
        $tour->pathmatch .= '%';
        $DB->update_record('tool_usertours_tours', $tour);
    }
}