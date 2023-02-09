<?php
/*
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
* @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
* @package totara_competency
*/

namespace totara_competency\rb\display;

use stdClass;
use rb_column;
use moodle_url;
use html_writer;
use reportbuilder;
use totara_reportbuilder\rb\display\base;
use totara_competency\helpers\capability_helper;

defined('MOODLE_INTERNAL') || die();

class assignment_activity_log_link extends base {

    /**
     * Return the Link to the assignment Activity log url value.
     *
     * @param string $value
     * @param string $format
     * @param \stdClass $row
     * @param \rb_column $column
     * @param \reportbuilder $report
     * @return string
     */
    public static function display($value, $format, stdClass $row, rb_column $column, reportbuilder $report) {
        $extrafields = self::get_extrafields_row($row, $column);
        if (capability_helper::can_view_profile($value)) {
            $url = new moodle_url(
                '/totara/competency/profile/details/index.php',
                ['user_id' => $value, 'competency_id' => $extrafields->competency_id, 'show_activity_log' => '1']
            );
            return html_writer::link($url, get_string('activity_log', 'totara_competency'));
        } else {
            return '';
        }
    }

    /**
     * Is this column graphable?
     *
     * @param rb_column $column
     * @param \rb_column_option $option
     * @param reportbuilder $report
     * @return bool
     */
    public static function is_graphable(rb_column $column, \rb_column_option $option, reportbuilder $report) {
        return false;
    }
}