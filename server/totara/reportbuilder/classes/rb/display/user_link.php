<?php
/*
 * This file is part of Totara Learn
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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder\rb\display;

/**
 * Display class intended for showing a user's name and link to their profile
 * When exporting, only the user's full name is displayed (without link)
 *
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_reportbuilder
 */
class user_link extends base {

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
        global $PAGE, $DB;

        // Extra fields expected are fields from totara_get_all_user_name_fields_join() and totara_get_all_user_name_fields_join()
        $extrafields = self::get_extrafields_row($row, $column);
        $isexport = ($format !== 'html');

        if (isset($extrafields->user_id)) {
            debugging('Invalid extra fields detected in report source for user_link display method .', DEBUG_DEVELOPER);
            // Some ancient legacy stuff.
            return clean_string($value);
        }

        $fullname = fullname($extrafields);
        if (empty($fullname)) {
            return '';
        }

        $userid = $extrafields->id;
        if ($isexport || $userid == 0) {
            return \core_text::entities_to_utf8($fullname);
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

        if (CLI_SCRIPT && !PHPUNIT_TEST) {
            $course = null;
        } else {
            $course = $PAGE->course;
        }

        $url = user_get_profile_url($userid, $course);
        if (!$url) {
            return $fullname;
        }

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
