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

use mod_perform\notification\condition;

/**
 * Calculate N day after the specific time.
 */
class days_after extends condition {
    /**
     * {@inheritDoc}
     *
     * For example, if the trigger is set to 4 days and the $base_time is 8am on 1st July,
     * the function returns true when the time is between 8.00am on 4th July and 7.59am on 5th July.
     */
    public function pass(int $base_time): bool {
        $time = $this->get_time();
        $triggers = $this->get_sorted_triggers(self::ASC);
        // Look up the next earliest trigger since the last run time.
        foreach ($triggers as $trigger) {
            $trigger_at = $base_time + $trigger;
            if ($this->is_over($trigger_at)) {
                continue;
            }
            if ($trigger_at <= $time && $time < $trigger_at + DAYSECS) {
                return true;
            }
        }
        return false;
    }
}
