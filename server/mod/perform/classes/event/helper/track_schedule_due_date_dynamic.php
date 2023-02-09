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

use mod_perform\dates\date_offset;

/**
 * Holds the details of a track schedule whose completion date depends on an
 * duration relative to the start date.
 */
class track_schedule_due_date_dynamic {
    /**
     * @var date_offset due date relative to start date.
     */
    private $date = null;

    /**
     * Default constructor.
     *
     * @param date_offset $date due date relative to the start date.
     */
    public function __construct(date_offset $date) {
        $this->date = $date;
    }

    /**
     * Returns the due date.
     *
     * @return date_offset the due date.
     */
    public function get_date(): date_offset {
        return $this->date;
    }

    /**
     * Returns the formatted due date eg "123 days before".
     *
     * @return string the formatted due date.
     */
    public function get_formatted(): string {
        return $this->formatted($this->date);
    }

    /**
     * Convenience function to format relative dates.
     *
     * @param date_offset $date
     * @return string the formatted relative date.
     */
    private function formatted(date_offset $date): string {
        $count = $date->get_count();
        $unit = strtolower($date->get_unit());
        $direction = strtolower($date->get_direction());
        $s = $count === 1 ? '' : 's';

        return "$count $unit$s $direction";
    }
}
