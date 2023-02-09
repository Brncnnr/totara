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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\event\helper;

use totara_core\dates\date_time_setting;

/**
 * Holds the details of a track schedule with a fixed starting date to generate
 * subject instances.
 */
class track_schedule_start_date_fixed {
    /**
     * @var date_time_setting starting date.
     */
    private $start = null;

    /**
     * @var date_time_setting ending date.
     */
    private $end = null;

    /**
     * Default constructor.
     *
     * @param date_time_setting $start starting date.
     * @param date_time_setting|null $end ending date if any.
     */
    public function __construct(
        date_time_setting $start,
        ?date_time_setting $end = null
    ) {
        $this->start = $start;
        $this->end = $end;
    }

    /**
     * Returns the start date.
     *
     * @return date_time_setting the start date.
     */
    public function get_start_date(): date_time_setting {
        return $this->start;
    }

    /**
     * Returns the start date as a formatted string.
     *
     * @return string the formatted date string.
     */
    public function get_start_date_formatted(): string {
        return $this->formatted_date($this->start);
    }

    /**
     * Returns the ending date.
     *
     * @return date_time_setting the from date. This may be null if there is no
     *         ending date.
     */
    public function get_end_date(): ?date_time_setting {
        return $this->end;
    }

    /**
     * Returns the end date as as a formatted string.
     *
     * @return string the formatted date string or an empty string if there is
     *         no end date.
     */
    public function get_end_date_formatted(): string {
        return $this->end ? $this->formatted_date($this->end) : '';
    }

    /**
     * Returns the date as a formatted string.
     *
     * @param date_time_setting $date date to format.
     *
     * @return string the formatted date string.
     */
    private function formatted_date(date_time_setting $date): string {
        return $date->get_iso() . ' ' . $date->get_timezone();
    }
}
