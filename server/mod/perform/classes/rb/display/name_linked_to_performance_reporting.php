<?php
/*
 * This file is part of Totara Perform
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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\rb\display;

use totara_reportbuilder\rb\display\base;

/**
 * Display class intended for showing a user's name and link to their performance reporting data.
 * When exporting, only the user's full name is displayed (without link)
 */
class name_linked_to_performance_reporting extends base {

    /**
     * Handles the display. Rules are pretty simple:
     * + Don't show link in the spreadsheet.
     * + Don't show link if actor cannot view target user profile in specific context
     * + Show link with course id if the actor can view with course context
     * + Show link with course id if the actor is viewing actor's self profile.
     * + Show link without course id if the the course is a SITE.
     * + Show link without course id if the actor is viewing self's profile but not enrolled
     *
     * @param string $value
     * @param string $format
     * @param \stdClass $row
     * @param \rb_column $column
     * @param \reportbuilder $report
     * @return string
     */
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        global $DB;

        // Extra fields expected are fields from totara_get_all_user_name_fields_join() and totara_get_all_user_name_fields_join()
        $extrafields = self::get_extrafields_row($row, $column);
        $isexport = ($format !== 'html');

        $fullname = fullname($extrafields);
        if (empty($fullname)) {
            return '';
        }

        $userid = $extrafields->id;
        if ($isexport || $userid == 0) {
            return $fullname;
        }

        if (isset($extrafields->deleted)) {
            $isdeleted = $extrafields->deleted;
        } else {
            // Grab one if needed.
            debugging(
                "For the performance speed, please include the field 'deleted' in your report builder SQL",
                DEBUG_DEVELOPER
            );

            $isdeleted = $DB->get_field('user', 'deleted', ['id' => $userid], MUST_EXIST);
        }

        if ($isdeleted) {
            // If the user is deleted, don't show link.
            return $fullname;
        }

        $url = new \moodle_url('/mod/perform/reporting/performance/user.php', ['subject_user_id' => $userid]);
        return \html_writer::link($url, $fullname);
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
