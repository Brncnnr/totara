<?php
/*
 * This file is part of Totara LMS
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
 * @author Brian Barnes <brian.barnes@totaralearning.com>
 * @package theme_roots
 */

require_once($CFG->dirroot . '/totara/program/renderer.php');

class theme_roots_totara_program_renderer extends totara_program_renderer {

    /**
     * Display due date for a program with task info
     *
     * @param int $duedate
     * @return string
     */
    public function display_duedate_highlight_info($duedate) {
        $now = time();
        if (!empty($duedate)) {
            $out .= html_writer::empty_tag('br');

            if ($duedate < $now) {
                    $out .= $this->notification(get_string('overdue', 'totara_plan'), 'notifyproblem');
            } else {
                $days = floor(($duedate - $now) / DAYSECS);
                if ($days == 0) {
                    $out .= $this->notification(get_string('duetoday', 'totara_plan'), 'notifyproblem');
                } else if ($days > 0 && $days < 10) {
                    $out .= $this->notification(get_string('dueinxdays', 'totara_plan', $days), 'notifynotice');
                }
            }
        }
        return $out;
    }
}