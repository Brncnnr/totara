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
 * @package mod_scorm
 */

namespace mod_scorm\rb\display;

use core\output\flex_icon;

/**
 * Trusted packages actions.
 */
class trusted_actions extends \totara_reportbuilder\rb\display\base {
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        global $OUTPUT;

        if ($format !== 'html') {
            return '';
        }

        $context = \context_system::instance();
        if (!has_capability('mod/scorm:managetrustedpackages', $context)) {
            return '';
        }

        $buttons = [];

        $actionurl = new \moodle_url('/mod/scorm/trusted_delete.php', array('contenthash' => $value, 'reportid' => $report->_id));
        $buttons[] = $OUTPUT->action_icon($actionurl, new flex_icon('delete', ['alt' => get_string('deleterecord', 'totara_reportbuilder', $value)]));

        return implode('', $buttons);

    }

    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return false;
    }
}
