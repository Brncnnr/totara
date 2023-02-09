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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\rb\display;

defined('MOODLE_INTERNAL') || die();

use totara_reportbuilder\rb\display\base;
use totara_reportbuilder\rb\display\format_string;

/**
 * Class workflow_name_link
 */
class workflow_name_link extends base {

    /**
     * @inheritDoc
     */
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {

        $value = format_string::display($value, $format, $row, $column, $report);

        $isexport = ($format !== 'html');

        $extrafields = self::get_extrafields_row($row, $column);
        if (!$isexport) {
            $url = new \moodle_url('/mod/approval/workflow/edit.php', ['workflow_id' => $extrafields->workflow_id, 'sub_section' => 'approvals']);
            $value = \html_writer::link($url, $value);
        }
        return $value;
    }

    /**
     * @inheritDoc
     */
    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return false;
    }
}