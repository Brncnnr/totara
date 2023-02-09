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
 * Holds the details of a track schedule that has a fixed completion date.
 */
class track_schedule_due_date_fixed {
    /**
     * @var date_time_setting due date.
     */
    private $date = null;

    /**
     * Default constructor.
     *
     * @param date_time_setting $date the due date.
     */
    public function __construct(date_time_setting $date) {
        $this->date = $date;
    }

    /**
     * Returns the due date.
     *
     * @return date_time_setting the due date.
     */
    public function get_date(): date_time_setting {
        return $this->date;
    }

    /**
     * Returns the due date as a formatted string.
     *
     * @return string the formatted date string.
     */
    public function get_date_formatted(): string {
        return $this->date->get_iso() . ' ' . $this->date->get_timezone();
    }
}
