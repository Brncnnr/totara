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
use mod_perform\dates\resolvers\dynamic\dynamic_source;

/**
 * Holds the details of a track schedule whose starting date depends on an
 * external triggering event.
 */
class track_schedule_start_date_dynamic {
    /**
     * @var dynamic_source event that starts off this schedule.
     */
    private $trigger = null;

    /**
     * @var date_offset starting date relative to event occurrence.
     */
    private $start = null;

    /**
     * @var date_offset ending date relative to event occurrence.
     */
    private $end = null;

    /**
     * Default constructor.
     *
     * @param dynamic_source $trigger the event that starts off this schedule.
     * @param date_offset $start start date relative to the trigger time.
     * @param date_offset|null $end ending date relative to the trigger time if any.
     */
    public function __construct(
        dynamic_source $trigger,
        date_offset $start,
        ?date_offset $end = null
    ) {
        $this->trigger = $trigger;
        $this->start = $start;
        $this->end = $end;
    }

    /**
     * Returns the trigger that sets off this schedule.
     *
     * @return dynamic_source the trigger.
     */
    public function get_trigger(): dynamic_source {
        return $this->trigger;
    }

    /**
     * Returns the trigger name.
     *
     * @return string the trigger name.
     */
    public function get_trigger_name(): string {
        return $this->trigger->get_display_name();
    }

    /**
     * Returns the start date.
     *
     * @return date_offset the start date.
     */
    public function get_start_date(): date_offset {
        return $this->start;
    }

    /**
     * Returns the formatted start date eg "123 days before".
     *
     * @return string the formatted start date.
     */
    public function get_start_date_formatted(): string {
        return $this->formatted($this->start);
    }

    /**
     * Returns the ending date.
     *
     * @return date_offset the from date. This may be null if there is no ending
     *         date.
     */
    public function get_end_date(): ?date_offset {
        return $this->end;
    }

    /**
     * Returns the formatted end date eg "123 days before".
     *
     * @return string the formatted end date or an empty string if there is no
     *         end date.
     */
    public function get_end_date_formatted(): string {
        return $this->end ? $this->formatted($this->end) : '';
    }

    /**
     * Convenience function to format relative dates.
     *
     * @param date_offset $date the relative date to format.
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
