<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\rb\display;

use rb_column;
use reportbuilder;
use stdClass;
use totara_reportbuilder\rb\display\base;
use totara_competency\entity\competency_achievement;

class comp_record_status extends base {

    public static function display($value, $format, stdClass $row, rb_column $column, reportbuilder $report) {
        switch ($value) {
            case competency_achievement::ACTIVE_ASSIGNMENT:
                $string = get_string('active', 'totara_competency');
                break;
            case competency_achievement::ARCHIVED_ASSIGNMENT:
                $string = get_string('archived', 'totara_competency');
                break;
            case competency_achievement::SUPERSEDED:
                $string = get_string('superseded', 'totara_competency');
                break;
            default:
                $string = '';
                break;
        }

        return $string;
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