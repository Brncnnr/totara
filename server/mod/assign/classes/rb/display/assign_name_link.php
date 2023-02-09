<?php
/*
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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package mod_assign
 */

namespace mod_assign\rb\display;
use totara_reportbuilder\rb\display\base;

/**
 * Display assignment name with link to the activity.
 *
 * @package mod_assign
 */
class assign_name_link extends base {
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        // Extra field expected is id, the assignment course_modules id.
        $extrafields = self::get_extrafields_row($row, $column);
        $isexport = ($format !== 'html');

        $value = \totara_reportbuilder\rb\display\format_string::display($value, $format, $row, $column, $report);

        if ($isexport) {
            return $value;
        }

        $url = new \moodle_url('/mod/assign/view.php', array('id' => $extrafields->id));
        return \html_writer::link($url, $value);
    }

    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return false;
    }
}
