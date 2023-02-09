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

/**
 * Display SCORM activity link
 */
class scorm_title extends \totara_reportbuilder\rb\display\base {
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        $value = format_string($value);

        if ($format !== 'html') {
            return \core_text::entities_to_utf8($value);
        }

        $extradata = self::get_extrafields_row($row, $column);

        $url = null;

        $context = \context::instance_by_id($extradata->contextid);
        if (has_capability('moodle/course:manageactivities', $context)
            && has_capability('mod/scorm:view', $context)
        ) {
            $url = new \moodle_url('/mod/scorm/view.php', ['id' => $extradata->cmid]);
        }

        if ($url) {
            $value = \html_writer::link($url, $value);
        }

        return $value;
    }

    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return false;
    }
}
