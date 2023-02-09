<?php
/**
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_evidence
 */

namespace totara_evidence\rb\display;

use rb_column;
use reportbuilder;
use stdClass;
use totara_evidence\models\evidence_type;
use totara_reportbuilder\rb\display\base;

class evidence_type_location extends base {

    /**
     * Display the way the evidence was created
     *
     * @param string $location
     * @param string $format
     * @param stdClass $row
     * @param rb_column $column
     * @param reportbuilder $report
     * @return string
     */
    public static function display($location, $format, stdClass $row, rb_column $column, reportbuilder $report): string {
        switch ($location) {
            case evidence_type::LOCATION_EVIDENCE_BANK:
                return get_string('evidence_bank', 'totara_evidence');
            case evidence_type::LOCATION_RECORD_OF_LEARNING:
                return get_string('record_of_learning', 'totara_evidence');
            default:
                return '';
        }
    }

}
