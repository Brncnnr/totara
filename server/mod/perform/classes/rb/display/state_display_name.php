<?php
/**
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\rb\display;

use mod_perform\state\state_helper;
use rb_column;
use reportbuilder;
use stdClass;
use totara_reportbuilder\rb\display\base;

/**
 * Displays the name of a state when giving it's code, object type and state type.
 * The state code is provided from the report query.
 * The object and state type are provided from the column extracontext attribute.
 *
 * Column Settings Example:
 * [
 *     'dbdatatype' => 'integer',
 *     'displayfunc' => 'state_display_name',
 *     'extracontext' => [
 *         'object_type' => 'subject_instance',
 *         'state_type' => subject_instance_progress::get_type(),
 *     ],
 * ]
 *
 * @package mod_perform\rb\display
 */
class state_display_name extends base {

    /**
     * Handles the display
     *
     * @param string $code
     * @param string $format
     * @param stdClass $row
     * @param rb_column $column
     * @param reportbuilder $report
     * @return string
     */
    public static function display($code, $format, stdClass $row, rb_column $column, reportbuilder $report): string {
        $object_type = $column->extracontext['object_type'];
        $state_type = $column->extracontext['state_type'];
        return state_helper::from_code($code, $object_type, $state_type)::get_display_name();
    }

    /**
     * Is this column graphable?
     *
     * @param rb_column $column
     * @param \rb_column_option $option
     * @param reportbuilder $report
     * @return bool
     */
    public static function is_graphable(rb_column $column, \rb_column_option $option, reportbuilder $report): bool {
        return false;
    }

}
