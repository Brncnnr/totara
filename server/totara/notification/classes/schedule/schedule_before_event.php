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
 * @author  Cody Finegan <cody.finegan@totaralearning.com>
 * @package totara_notification
 */

namespace totara_notification\schedule;

use coding_exception;
use totara_notification\local\schedule_helper;

/**
 * Represents notifications that can be delayed and scheduled after a particular event.
 */
class schedule_before_event implements notification_schedule {
    /**
     * Maximum number of days available for offsets
     */
    const MAX_OFFSET = 365;

    /**
     * Calculate the timestamp for the event, based on the provided timestamp.
     * The expectation here is the $offset is a negative value, but is intentionally not checked for.
     *
     * @param int $event_timestamp
     * @param int $offset The offset time in seconds unit.
     * @return int
     */
    public static function calculate_timestamp(int $event_timestamp, int $offset): int {
        if ($offset >= 0) {
            throw new coding_exception('Schedule before event must have a negative offset');
        }

        return $event_timestamp + $offset;
    }

    /**
     * @param int $offset
     * @return string
     */
    public static function get_label(int $offset): string {
        // Before event will have a negative offset, we need to make it positive for display.
        $offset = abs($offset);

        // Convert it to days, as the unit is seconds.
        $offset = (int) ($offset / DAYSECS);

        if ($offset === 1) {
            return get_string('schedule_label_before_event_singular', 'totara_notification', $offset);
        }

        return get_string('schedule_label_before_event', 'totara_notification', $offset);
    }

    /**
     * @return string
     */
    public static function identifier(): string {
        return 'BEFORE_EVENT';
    }

    /**
     * @param int|null $days_offset
     * @return int
     */
    public static function default_value(?int $days_offset = null): int {
        if ($days_offset === null || $days_offset <= 0) {
            throw new coding_exception('Schedule Before Event must have had a days_offset provided');
        }

        $days_offset = schedule_helper::days_to_seconds($days_offset);

        // Must be a negative value
        return $days_offset * -1;
    }

    /**
     * Validate the provided offset. Returns a simple true or false message.
     *
     * @param int|null $offset  The value is in days unit.
     * @return bool
     */
    public static function validate_offset(?int $offset = null): bool {
        return (
            clean_param($offset, PARAM_INT) === $offset
            && $offset > 0
            && $offset < self::MAX_OFFSET
        );
    }
}