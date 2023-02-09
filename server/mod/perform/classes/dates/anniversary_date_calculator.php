<?php
/*
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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\dates;

use DateTimeZone;

/**
 * Class anniversary_date_calculator
 *
 * @package mod_perform\dates
 */
class anniversary_date_calculator {

    /**
     * Calculate the next anniversary of a date. If the date is after the cut off date, the original date is returned.
     *
     * @param int $date
     * @param int $cut_off_date
     * @return int
     */
    public function calculate(int $date, int $cut_off_date): int {
        $date_time = (new \DateTimeImmutable("@{$date}"))->setTime(0, 0, 0);
        $now_date_time = (new \DateTimeImmutable("@{$cut_off_date}"))->setTime(0, 0, 0);

        if ($date_time > $now_date_time) {
            return $date;
        }

        [$month, $day] = explode('-', $date_time->format('m-d'));
        $now_year = $now_date_time->format('Y');

        $anniversary = "{$now_year}-{$month}-{$day}";
        $anniversary_date_time = new \DateTimeImmutable($anniversary, new DateTimeZone('+00:00'));

        if ($now_date_time > $anniversary_date_time) {
            $anniversary_date_time =  $anniversary_date_time->modify('+1 year');
        }

        return $anniversary_date_time->getTimestamp();
    }

}