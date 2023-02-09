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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\notification\conditions;

use DateTime;
use mod_perform\notification\condition;

/**
 * Any time after the midnight of the specific day.
 */
class after_midnight extends condition {
    /**
     * {@inheritDoc}
     *
     * For example, $base_time is 8am on 4th July,
     * the function returns true when the time is after 12.00am on 4th July.
     */
    public function pass(int $base_time): bool {
        $time = $this->get_time();
        // Round down to the nearest midnight.
        $midnight = self::get_last_midnight($base_time);
        if ($this->is_over($midnight)) {
            return false;
        }
        return $midnight <= $time;
    }

    /**
     * Get the last midnight in the server time zone.
     *
     * @param integer $time Unix timestamp in UTC
     * @return integer
     */
    public static function get_last_midnight(int $time): int {
        $tz = \core_date::get_server_timezone_object();
        $offset = $tz->getOffset(new DateTime('@'.$time));
        $time += $offset;
        $time -= ($time % DAYSECS);
        return $time - $offset;
    }
}
