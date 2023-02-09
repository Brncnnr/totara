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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package contentmarketplace_linkedin
 */

namespace contentmarketplace_linkedin\formatter;

use core\webapi\formatter\field\base as base_field_formatter;

class timespan_field_formatter extends base_field_formatter {

    /**
     * Used to display the time in a human readable "XXh YYm ZZs" format
     */
    public const FORMAT_HUMAN = 'HUMAN';

    /**
     * Used to display the raw number of seconds it takes to complete.
     */
    public const FORMAT_SECONDS = 'SECONDS';

    private const FORMATS = [
        self::FORMAT_HUMAN,
        self::FORMAT_SECONDS,
    ];

    /**
     * @return bool
     */
    protected function validate_format(): bool {
        return in_array($this->format, self::FORMATS);
    }

    /**
     * Return the raw time in seconds.
     *
     * @param int $total_seconds Raw number of seconds
     * @return string
     */
    protected function format_seconds(int $total_seconds): string {
        return $total_seconds;
    }

    /**
     * Return a string in the format "XXh YYm ZZs" localised to the user's language.
     * This replicates the way that the LinkedIn Learning catalogue outputs timespan values.
     * If the input time is 10 minutes or over, then seconds are not shown and instead the minute is rounded up.
     *
     * Input => Output Examples:
     *    45    => "45s"
     *    135   => "2m 15s"
     *    180   => "3m"
     *    599   => "9m 59s"
     *    600   => "10m"
     *    601   => "11m"
     *    3599  => "1h 0m"
     *    3601  => "1h 1m"
     *    36001 => "10h 1m"
     *
     * @param int $total_seconds Raw number of seconds
     * @return string
     */
    protected function format_human(int $total_seconds): string {
        $hours = (int) floor($total_seconds / HOURSECS);
        $minutes = ($total_seconds - ($hours * HOURSECS)) / MINSECS;
        $seconds = $total_seconds % MINSECS;

        if ($hours > 0 || $minutes >= 10) {
            $minutes = (int) floor($minutes);
            if ($minutes === HOURMINS) {
                $hours++;
                $minutes = 0;
            }
        } else {
            $minutes = (int) floor($minutes);
        }

        if ($hours > 0) {
            return get_string('timespan_format_hours_minutes', 'contentmarketplace_linkedin', [
                'hours' => $hours,
                'minutes' => $minutes,
            ]);
        } else if ($minutes >= 10 || ($minutes > 0 && $seconds === 0)) {
            return get_string('timespan_format_minutes', 'contentmarketplace_linkedin', [
                'minutes' => $minutes,
            ]);
        } else if ($minutes > 0) {
            return get_string('timespan_format_minutes_seconds', 'contentmarketplace_linkedin', [
                'minutes' => $minutes,
                'seconds' => $seconds,
            ]);
        } else {
            return get_string('timespan_format_seconds', 'contentmarketplace_linkedin', [
                'seconds' => $seconds,
            ]);
        }
    }

}
