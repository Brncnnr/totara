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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package auth_oauth2
 */

namespace auth_oauth2\rb\display;

use \totara_reportbuilder\rb\display\base;
use \core\output\flex_icon;

/**
 * Actions for linked logins.
 */
final class linked_login_actions extends base {
    /**
     * Display data.
     *
     * @param string $value
     * @param string $format
     * @param \stdClass $row
     * @param \rb_column $column
     * @param \reportbuilder $report
     * @return string
     */
    final public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        global $OUTPUT;

        if ($format !== 'html') {
            return '';
        }
        $buttons = array();

        $record = self::get_extrafields_row($row, $column);
        $record->id = $value;

        if (\auth_oauth2\api::can_delete_linked_login($record)) {
            $returnurl = self::get_return_url($report)->out_as_local_url(false);
            $actionurl = new \moodle_url('/auth/oauth2/linkedlogindelete.php', ['id' => $value, 'returnurl' => $returnurl]);
            $buttons[] = $OUTPUT->action_icon($actionurl, new flex_icon('delete', ['alt' => get_string('deletelinkedlogin', 'auth_oauth2')]));
        }

        return implode('', $buttons);
    }

    /**
     * Back to the report.
     *
     * @param \reportbuilder $report
     * @return \moodle_url
     */
    public static function get_return_url(\reportbuilder $report) {
        $returnurl = new \moodle_url($report->get_current_url());
        $spage = optional_param('spage', '', PARAM_INT);
        if ($spage) {
            $returnurl->param('spage', $spage);
        }
        $perpage = optional_param('perpage', '', PARAM_INT);
        if ($perpage) {
            $returnurl->param('perpage', $perpage);
        }
        return $returnurl;
    }
}
