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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\models;

use coding_exception;
use core_date;
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Convenience class to handle due dates and relevant calculations.
 */
class due_date {
    // Convenience enums.
    public const INTERVAL_IN_DAYS = 1;
    public const INTERVAL_IN_WEEKS = 2;
    public const INTERVAL_IN_MONTHS = 3;

    /**
     * @var int due date in UTC.
     */
    private $utc_due_date;

    /**
     * Default constructor.
     *
     * @param int $utc_due_date due date in UTC.
     */
    public function __construct(int $utc_due_date) {
        $this->utc_due_date = $utc_due_date;
    }

    /**
     * Returns the absolute interval count to/past the due date.
     *
     * Note: this currently returns the interval in days but in the future, this
     * method could be enhanced to return the most appropriate interval count eg
     * in weeks if the interval is > 7 days but < 1 month, etc. This is why the
     * method returns a tuple rather than just the interval.
     *
     * @return array a [interval count, self::INTERVAL_IN_XYZ] tuple.
     */
    public function get_interval_to_or_past_due_date(): array {
        $interval = $this->interval_from_now()->days;
        $type = self::INTERVAL_IN_DAYS;

        return [$interval, $type];
    }

    /**
     * Returns the absolute interval count to/past the due date.
     *
     * @return int an interval count.
     */
    public function get_interval_to_or_past_due_date_units(): int {
        [$interval, ] = $this->get_interval_to_or_past_due_date();
        return $interval;
    }

    /**
     * Returns the type of interval to/past the due date.
     *
     * @return string a localised string representing the type.
     */
    public function get_interval_to_or_past_due_date_type(): string {
        [$units, $type] = $this->get_interval_to_or_past_due_date();

        $lang_string_key = '';
        switch ($type) {
            case due_date::INTERVAL_IN_DAYS:
                $lang_string_key = 'day';
                break;

            case due_date::INTERVAL_IN_WEEKS:
                $lang_string_key = 'week';
                break;

            case due_date::INTERVAL_IN_MONTHS:
                $lang_string_key = 'month';
                break;

            default:
                throw new coding_exception("unknown units to due date type: $type");
        }

        $lang_string_key = $units === 1 ? $lang_string_key : "{$lang_string_key}s";
        return get_string($lang_string_key, 'core');
    }

    /**
     * Checks if the end of today is past the end of day of the due date.
     *
     * @return bool true of the time is past the due date.
     */
    public function is_overdue(): bool {
        return $this->interval_from_now()->invert;
    }

    /**
     * Returns the due date. This is the UTC due date adjusted to 23:59:59 of
     * that day, in the current user's timezone.
     *
     * @return int the due date in seconds since the Epoch.
     */
    public function get_due_date(): int {
        return $this->end_of_due_date()->getTimeStamp();
    }


    /**
     * Returns the interval to/past the due date.
     *
     * @return DateInterval the duration to/past the due date.
     */
    private function interval_from_now(): DateInterval {
        $beginning_of_today = usergetmidnight(time());
        $end_of_today = (new DateTimeImmutable("@$beginning_of_today"))
            ->setTime(23, 59, 59);

        $end_of_due_day = $this->end_of_due_date();

        return $end_of_today->diff($end_of_due_day);
    }
    /**
     * Returns the due date adjusted to 23:59:59 of that day, in the user's time
     * zone.
     *
     * @return DateTimeImmutable the adjusted due date.
     */
    private function end_of_due_date(): DateTimeImmutable {
        $beginning_of_due_day = usergetmidnight($this->utc_due_date);
        return (new DateTimeImmutable("@$beginning_of_due_day"))
            ->setTime(23, 59, 59);
    }
}
