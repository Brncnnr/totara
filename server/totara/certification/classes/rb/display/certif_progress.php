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
 * @package totara_certification
 */

namespace totara_certification\rb\display;
use totara_reportbuilder\rb\display\base;

/**
 * Display class intended for certification progress
 *
 * @deprecated since Totara 13
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_certification
 */
class certif_progress extends base {

    /**
     * Don't access me directly, use self::get_renderer();
     * @var \totara_core_renderer
     */
    private static $renderer;

    /**
     * Handles the display
     *
     * @param string $value
     * @param string $format
     * @param \stdClass $row
     * @param \rb_column $column
     * @param \reportbuilder $report
     * @return string
     */
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        static $debuggingshown = false;

        if (!$debuggingshown) {
            // Debugging shown
            $debuggingshown = true;
            debugging(__CLASS__ . ' has been deprecated please use \totara_certification\rb\display\certif_completion_progress');
        }

        $extrafields = self::get_extrafields_row($row, $column);
        $isexport = ($format !== 'html');

        $percentage = totara_program_get_user_percentage_complete($extrafields->programid, $extrafields->userid);

        if ($isexport) {
            if ($percentage === null) {
                if (!empty($extrafields->stringexport)) {
                    return get_string('notassigned', 'totara_program');
                }
                return '';
            }
            if (!empty($extrafields->stringexport)) {
                return get_string('xpercentcomplete', 'totara_core', $percentage);
            }
            return $percentage;
        }

        if ($percentage === null) {
            return get_string('notassigned', 'totara_program');
        }
        if (!empty($extrafields->stringexport)) {
            return get_string('xpercentcomplete', 'totara_core', $percentage);
        }

        return self::get_renderer()->progressbar($percentage, 'medium', false, 'DEFAULTTOOLTIP', $extrafields->fullname ?? null);
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

    /**
     * Returns a totara core renderer.
     *
     * @return \renderer_base|\totara_core_renderer
     */
    private static function get_renderer() {
        global $PAGE;
        if (self::$renderer === null) {
            self::$renderer = $PAGE->get_renderer('totara_core');
        }
        return self::$renderer;
    }
}
