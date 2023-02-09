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

/**
 * All schedule definitions implement this class.
 *
 * @package totara_notification\schedule
 */
interface notification_schedule {
    /**
     * Calculate the schedule timestamp based on the provided event_timestamp and offset.
     * Must return in UTC-based unix timestamp.
     *
     * @param int $event_timestamp The timestamp to base off of.
     * @param int $offset The number of days that have been set by the preference.
     * @return int
     */
    public static function calculate_timestamp(int $event_timestamp, int $offset): int;

    /**
     * Return the specific human-readable label for the specific schedule instance.
     * Note that the $offset is in seconds unit.
     *
     * @param int $offset
     * @return string
     */
    public static function get_label(int $offset): string;

    /**
     * What's the unique identifier for this type of schedule
     * (maps to the GraphQL enum totara_notification_schedule_type)
     * @return string
     */
    public static function identifier(): string;

    /**
     * Pass in the number of days for the schedule, and it'll be transformed
     * into the offset to store/persist. Used so the -1<0>1 logic isn't spread
     * throughout the events.
     *
     * @param int|null $days_offset
     * @return int
     */
    public static function default_value(?int $days_offset = null): int;

    /**
     * Validate the provided offset. Returns a simple true or false.
     *
     * @param int|null $offset
     * @return bool
     */
    public static function validate_offset(?int $offset = null): bool;
}