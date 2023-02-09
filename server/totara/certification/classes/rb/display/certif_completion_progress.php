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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara_certification
 */

namespace totara_certification\rb\display;

/**
 * Display Certification progress.
 *
 * @package mod_facetoface
 */
class certif_completion_progress extends \totara_reportbuilder\rb\display\base {

    /**
     * Don't access me directly, use self::get_renderer()
     * @var \totara_core_renderer $totara_renderer
     */
    private static $renderer;

    /**
     * Displays the overall status.
     *
     * @param string $value
     * @param string $format
     * @param \stdClass $row
     * @param \rb_column $column
     * @param \reportbuilder $report
     * @return string
     */
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        $extrafields = self::get_extrafields_row($row, $column);

        $percentage = totara_certification_get_percentage_complete(
            $extrafields->programid,
            $extrafields->userid,
            $extrafields->window,
            $extrafields->completion,
            $extrafields->histcompletion
        );

        if ($format !== 'html') {
            if (!empty($extrafields->stringexport)) {
                return get_string('xpercentcomplete', 'totara_core', (int)$percentage);
            }
            return $percentage;
        }

        if ($percentage === null) {
            return get_string('notassigned', 'totara_certification');
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
