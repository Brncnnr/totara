<?php
/*
 * This file is part of Totara LMS
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
 * @author David Curry <david.curry@totaralearning.com>
 * @package totara_mobile
 */

namespace totara_mobile\local;

defined('MOODLE_INTERNAL') || die();

class duedate_state {

    /**
     * Mimic but customise the duedate state calculator for mobile
     * source: ./blocks/currentlearning/classes/helper
     *
     * @param int $duedate The duedate for the item
     * @return string
     */
    public static function calculate($duedate) {
        // Handle null duedates for graphql.
        if (empty($duedate)) {
            return null;
        }

        // Mimic config with defaults pending MOB-675.
        $config = new \stdClass();
        $config->warningperiod = 30 * DAYSECS; // Warnings (yellow) at 30 days from due.
        $config->alertperiod = 0 * DAYSECS; // Alerts (red) at 0 days from due (overdue);

        if ($config->alertperiod > $config->warningperiod) {
            throw new \coding_exception('Warning period cannot be before the alert period');
        }

        $now = time();
        $alertperiod = $duedate - $config->alertperiod;
        $warningperiod = $duedate - $config->warningperiod;

        if ($warningperiod > $now) {
            // Out of warning period.
            $state = 'info';
        } else if ($alertperiod > $now && $warningperiod <= $now) {
            // Within warning period.
            $state = 'warning';
        } else if ($alertperiod <= $now) {
            // Within alert period.
            $state = 'danger';
        } else {
            // Unreachable.
            throw new \coding_exception('Logically impossible');
        }

        return $state;
    }
}
