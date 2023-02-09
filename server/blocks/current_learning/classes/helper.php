<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @package block_current_learning
 */

namespace block_current_learning;

defined('MOODLE_INTERNAL') || die();

class helper {

    /**
     * Calculate the state for an item according to the settings for the block.
     *
     * - If: (duedate - warning period) >= now                               : info.
     * - If: (duedate - alert period)   >= now > (duedate - warning period)  : warning.
     * - If: (duedate)                  > now > (duedate - alert period)     : danger.
     * - If: (duedate) < now            [the due date is in the past]        : danger + alert flag.
     *
     * @param int $duedate The duedate for the item
     * @param \stdClass $config The block config object
     * @param int $now The current timestamp, if null then the current time is used.
     * @return array An array with a state string and flag for warning icon
     */
    public static function get_duedate_state($duedate, $config, $now = null) {
        if ($config->alertperiod > $config->warningperiod) {
            throw new \coding_exception('Warning period cannot be before the alert period');
        }
        if ($now === null) {
            $now = time();
        }
        $alertperiod = $duedate - $config->alertperiod;
        $warningperiod = $duedate - $config->warningperiod;

        $alert = false;

        if ($warningperiod > $now) {
            // Out of warning period.
            $state = 'label-info';
        } else if ($alertperiod > $now && $warningperiod <= $now) {
            // Within warning period.
            $state = 'label-warning';
        } else if ($alertperiod <= $now && $duedate > $now) {
            // Within alert period.
            $state = 'label-danger';
        } else if ($duedate <= $now) {
            // Overdue.
            $state = 'label-danger';
            $alert = true;
        } else {
            // Unreachable.
            throw new \coding_exception('Logically impossible');
        }

        return array(
            'state' => $state,
            'alert' => $alert
        );
    }
}
