<?php
/**
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
 * @author Angela Kuznetsova <angela.kuznetsova@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\rb\display;

use mod_approval\output\workflow_type_report_actions;
use totara_reportbuilder\rb\display\base;
use stdClass;
use rb_column;
use reportbuilder;

/**
 * Class workflow_type_actions
 */
final class workflow_type_actions extends base {
    /**
     * @param int|string $id
     * @param string $format
     * @param stdClass $row
     * @param rb_column $column
     * @param reportbuilder $report
     *
     * @return string
     */
    public static function display($id, $format, stdClass $row, rb_column $column, reportbuilder $report): string {
        global $OUTPUT;

        $extrafields = self::get_extrafields_row($row, $column);

        $widget = workflow_type_report_actions::create($id, $report, $extrafields);

        return $OUTPUT->render($widget);
    }
}