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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_notification
 */
namespace totara_notification\resolver\abstraction;

use moodle_recordset;
use totara_notification\schedule\schedule_on_event;

/**
 * For the resolver that support those events that are scheduled to send out the notifications.
 */
interface scheduled_event_resolver {
    /**
     * Get all the events that happened/will happen within the time frame.
     *
     * It is critical that the events returned INCLUDE those with the $min_time but
     * EXCLUDE those with the $max_time. E.g. where $min_time <= $event_time < $max_time.
     *
     * The return type should be moodle_recordset or some subclass, such as lazy_collection
     * or array_recordset (a wrapper for non-recordset data). The recordset will be closed
     * after use.
     *
     * @param int $min_time
     * @param int $max_time
     *
     * @return moodle_recordset of events of type array or stdClass
     */
    public static function get_scheduled_events(int $min_time, int $max_time): moodle_recordset;

    /**
     * Returns an array of available timing for this event (concrete class).
     * Note that you can return {@see schedule_on_event} within the result here
     * to tell that you are supporting on_event queue as well.
     *
     * @return string[]
     */
    public static function get_notification_available_schedules(): array;

    /**
     * Returns the time in seconds of how|when the actual event to be|had been occurred.
     * Note: please do not return zero or negative number, the system does not like these two values.
     *
     * @return int
     */
    public function get_fixed_event_time(): int;
}