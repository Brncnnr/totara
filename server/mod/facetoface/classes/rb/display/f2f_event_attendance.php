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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\rb\display;

use mod_facetoface\attendance_taking_status;
use mod_facetoface\seminar_event;
use totara_reportbuilder\rb\display\base;

defined('MOODLE_INTERNAL') || die();

/**
 * Display event attendance status.
 */
class f2f_event_attendance extends base {

    /**
     * Format the value.
     *
     * @param string $value
     * @param string $format
     * @param \stdClass $row
     * @param \rb_column $column
     * @param \reportbuilder $report
     * @return string
     *
     * @todo TL-22322 - Convert the whole business logic into SQL
     */
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        $isexport = ($format !== 'html');

        $seminarevent = new seminar_event((int)$value);
        $status = $seminarevent->get_attendance_taking_status(null, 0, true, true);
        if ($status != attendance_taking_status::OPEN && $status != attendance_taking_status::ALLSAVED) {
            return get_string('eventattendanceunavailable', 'rb_source_facetoface_summary');
        }

        if ($status == attendance_taking_status::ALLSAVED) {
            $text = get_string('eventattendancesaved', 'rb_source_facetoface_summary');
        } else {
            $text = get_string('eventattendanceongoing', 'rb_source_facetoface_summary');
        }
        if ($isexport || !$column->extracontext['link']) {
            return $text;
        } else {
            $url = new \moodle_url('/mod/facetoface/attendees/takeattendance.php', ['s' => (int)$value]);
            return \html_writer::link($url, $text);
        }
    }

    /**
     * Is the result of this display method usable for graph series?
     * @param \rb_column $column
     * @param \rb_column_option $option
     * @param \reportbuilder $report
     * @return false the result cannot be plotted on graph
     */
    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return false;
    }
}
