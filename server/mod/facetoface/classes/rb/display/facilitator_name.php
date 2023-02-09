<?php
/*
* This file is part of Totara Learn
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
* @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
* @package mod_facetoface
*/

namespace mod_facetoface\rb\display;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use mod_facetoface\facilitator_user;
use totara_reportbuilder\rb\display\base;
use totara_reportbuilder\rb\display\format_string;

class facilitator_name extends base {
    /**
     * Handles the display
     * @param string $value
     * @param string $format
     * @param \stdClass $row
     * @param \rb_column $column
     * @param \reportbuilder $report
     * @return string
     */
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        global $OUTPUT;

        if (empty($value)) {
            return '';
        }

        $value = format_string::display($value, $format, $row, $column, $report);

        $isexport = ($format !== 'html');

        $extrafields = self::get_extrafields_row($row, $column);
        if (isset($extrafields->userid) && (int)$extrafields->userid > 0) {
            $userid = (int)$extrafields->userid;
            $facilitator_user = facilitator_user::seek_by_userid($userid);
            if ($isexport) {
                $value .= " ({$facilitator_user->get_fullname()}) ";
            } else {
                $icon = '';
                if (!facilitator_user::is_userid_active($userid)) {
                    $icon = $OUTPUT->flex_icon('warning', [
                        'classes' => 'ft-size-100 ft-state-warning',
                        'alt' => get_string('facilitatoruserdeleted', 'mod_facetoface')
                    ]);
                }
                $value .= " ({$facilitator_user->get_fullname()}{$icon}) ";
            }
        }
        return $value;
    }

    /**
     * Is this column graphable?
     * @param \rb_column $column
     * @param \rb_column_option $option
     * @param \reportbuilder $report
     * @return bool
     */
    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return false;
    }

}