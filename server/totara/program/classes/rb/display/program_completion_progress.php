<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_reportbuilder
 */

namespace totara_program\rb\display;

/**
 * Class describing column display formatting.
 *
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_reportbuilder
 */
class program_completion_progress extends \totara_reportbuilder\rb\display\base {

    /**
     * Don't access me directly, use self::get_renderer();
     * @var \totara_core_renderer $renderer
     */
    private static $renderer;

    /**
     * Displays the program completion progress.
     *
     * @param string $value - program status expected
     * @param string $format
     * @param \stdClass $row
     * @param \rb_column $column
     * @param \reportbuilder $report
     * @return string
     */
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {

        $isexport = ($format !== 'html');
        $extrafields = self::get_extrafields_row($row, $column);

        $percentage = totara_program_get_user_percentage_complete(
            $extrafields->programid,
            $extrafields->userid
        );

        if ($percentage === null) {
            if ($isexport && empty($extrafields->stringexport)) {
                return '';
            }
            // Can't calculate progress, use status instead
            if ($value) {
                return get_string('complete', 'totara_program');
            } else {
                return get_string('incomplete', 'totara_program');
            }
        }

        if ($isexport) {
            if (!empty($extrafields->stringexport)) {
                return get_string('xpercentcomplete', 'totara_core', $percentage);
            } else {
                return $percentage;
            }
        }

        return self::get_renderer()->progressbar((int) $percentage, 'medium', false, 'DEFAULTTOOLTIP', $extrafields->fullname ?? null);
    }

    /**
     * Returns an instance of a totara_core_renderer
     *
     * @return \totara_core_renderer
     */
    private static function get_renderer(): \totara_core_renderer {
        global $PAGE;
        if (self::$renderer === null) {
            self::$renderer = $PAGE->get_renderer('totara_core');
        }
        return self::$renderer;
    }

    /**
     * Is this column graphable? No!
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