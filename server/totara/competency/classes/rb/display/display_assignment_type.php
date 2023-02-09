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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\rb\display;

use rb_column;
use reportbuilder;
use stdClass;
use totara_competency\user_groups;
use totara_reportbuilder\rb\display\base;

/**
 * Display class intended for user group type
 */
class display_assignment_type extends base {

    /**
     * Handles the display
     *
     * @param string $value
     * @param string $format
     * @param stdClass $row
     * @param rb_column $column
     * @param reportbuilder $report
     * @return string
     */
    public static function display($value, $format, stdClass $row, rb_column $column, reportbuilder $report) {
        switch ($value) {
            case user_groups::POSITION:
                $type_name = get_string('user_group_type_position', 'totara_competency');
                break;
            case user_groups::ORGANISATION:
                $type_name = get_string('user_group_type_organisation', 'totara_competency');
                break;
            case user_groups::COHORT:
                $type_name = get_string('user_group_type_cohort', 'totara_competency');
                break;
            default:
                $type_name = get_string('assignment_type_'.$value, 'totara_competency');
                break;
        }

        return $type_name;
    }

    /**
     * Is this column graphable?
     *
     * @param rb_column $column
     * @param \rb_column_option $option
     * @param reportbuilder $report
     * @return bool
     */
    public static function is_graphable(rb_column $column, \rb_column_option $option, reportbuilder $report) {
        return false;
    }

}
