<?php
/*
 * This file is part of Totara Perform
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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\rb\display;

use mod_perform\util;
use mod_perform\rb\util as rb_util;
use totara_reportbuilder\rb\display\base;
use totara_tui\output\component;

class element_actions extends base {

    private static $potentially_has_permission;

    /**
     * Handles the display
     *
     * @param int $element_id
     * @param string $format
     * @param \stdClass $row
     * @param \rb_column $column
     * @param \reportbuilder $report
     * @return string
     */
    public static function display($element_id, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {

        if ($format !== 'html') {
            // Only applicable to the HTML format.
            return '';
        }

        // Static to prevent expensive check once per row.
        if (self::$potentially_has_permission === null) {
            self::$potentially_has_permission = util::can_potentially_report_on_subjects($report->reportfor);
        }

        if (!self::$potentially_has_permission) {
            return '';
        }

        if ($report->get_param_value('activity_id')) {
            $back_to = ['back_to_activity' => (int)$report->get_param_value('activity_id')];
        } else if ($report->get_param_value('element_identifier')) {
            $back_to = ['back_to_reporting_ids' => implode(',', $report->get_param_value('element_identifier'))];
        } else {
            $back_to = [];
        }

        $props = [
            'element-id' => (int)$element_id,
            'element-preview' => true,
            'report-params' => (object) array_merge(
                ['element_id' => (int)$element_id],
                $back_to
            ),
            'export-formats' => rb_util::export_for_props($report),
        ];

        $tui = new component(
            'mod_perform/components/report/element_response/RowActions',
            $props
        );
        return $tui->out_html();
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
}
