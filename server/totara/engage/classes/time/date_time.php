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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_engage
 */
namespace totara_engage\time;

final class date_time {
    /**
     * Total seconds in a day.
     * @var int
     */
    public const SECONDS_IN_A_DAY = 86400;

    /**
     * Total seconds in an hour.
     * @var int
     */
    public const SECONDS_IN_AN_HOUR = 3600;

    /**
     * Total seconds in a minute.
     * @var int
     */
    public const SECONDS_IN_A_MINUTE = 60;

    /**
     * Total seconds in seven days.
     * @var int
     */
    public const SECONDS_IN_7_DAYS = 604800;

    /**
     * The time created or time updated.
     * @var int
     */
    private $time;

    /**
     * date_time constructor.
     * @param int $time
     */
    public function __construct(int $time) {
        $this->time = $time;
    }

    /**
     * Giving the timestamp of now, then this function is able to calculate the string that we want to display
     * for the screen. For example: if the time $now is about an hour later after the value $time then this
     * function will try to return something like '45 minutes ago'
     *
     * @param int|null $now
     * @return string
     */
    public function get_readable_string(?int $now = null): string {
        if (null == $now) {
            $now = time();
        }

        $diff = $now - $this->time;
        if (static::SECONDS_IN_7_DAYS > $diff) {
            // Within 7 days.
            if (static::SECONDS_IN_A_DAY > $diff) {
                if (static::SECONDS_IN_AN_HOUR > $diff) {
                    // Within an hour, we need to convert it into minutes ago.
                    $minutes = floor($diff / static::SECONDS_IN_A_MINUTE);

                    if (0 == $minutes || 1 == $minutes) {
                        return get_string('minuteago', 'totara_engage', 1);
                    }

                    return get_string('minutesago', 'totara_engage', $minutes);
                }

                // Within a day. Convert it to hours ago.
                $hours = floor($diff / static::SECONDS_IN_AN_HOUR);
                if (1 == $hours) {
                    return get_string('hourago', 'totara_engage', $hours);
                }

                return get_string('hoursago', 'totara_engage', $hours);
            }

            // Within 7 days convert to day.
            $days = floor($diff / static::SECONDS_IN_A_DAY);
            if (1 == $days) {
                return get_string('dayago', 'totara_engage', $days);
            }

            return get_string('daysago', 'totara_engage', $days);
        }

        // Over 7 days, we need a proper string.
        return userdate($this->time, get_string('strftimedatetime', 'langconfig'));
    }
}