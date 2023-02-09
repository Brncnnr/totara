<?php
/*
 * This file is part of Totara LMS
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
 * @author  Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\signup\state;

use mod_facetoface\signup\transition;
use mod_facetoface\signup\condition\{event_is_not_cancelled, event_in_the_past};

defined('MOODLE_INTERNAL') || die();

/**
 * This class represents no show graded state.
 */
class no_show extends attendance_state {

    /**
     * Code of status as it is stored in DB
     * Numeric statuses are backward compatible except not_set which was not meant to be written into DB.
     * Statuses don't have to follow particular order (except must be unique of course)
     *
     * @return int
     */
    public static function get_code() : int {
        return 80;
    }

    /**
     * Get the csv code value associated with the status code 80.
     * @return int|null
     */
    public static function get_csv_code() : ?int {
        return 1;
    }

    /**
     * Message for user on entering the state
     *
     * @return string
     */
    public function get_message(): string {
        return get_string('status_no_show', 'mod_facetoface');
    }

    /**
     * Get action label for getting into state.
     *
     * @return string
     */
    public function get_action_label(): string {
        return get_string('status_no_show', 'mod_facetoface');
    }

    /**
     * Get the grade value associated with the state.
     *
     * @return int|null
     */
    public static function get_grade() : ?int {
        return 0;
    }

    /**
     * Get the no_show status string.
     *
     * @return string
     */
    public static function get_string() : string {
        return get_string('status_no_show', 'mod_facetoface');
    }
}
