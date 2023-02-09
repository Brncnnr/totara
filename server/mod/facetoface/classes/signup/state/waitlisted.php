<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author  Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\signup\state;

use mod_facetoface\event\booking_waitlisted;
use mod_facetoface\signup\condition\{
    booking_common,
    event_has_session,
    event_is_cancelled,
    event_is_not_cancelled,
    event_not_in_the_past,
    event_registration_is_available,
    is_not_reservation,
};
use mod_facetoface\signup\restriction\actor_can_overbook;
use mod_facetoface\signup\transition;
use mod_facetoface\event\abstract_signup_event;

defined('MOODLE_INTERNAL') || die();

/**
 * This class represents wait-listed state.
 */
class waitlisted extends state implements interface_event {
    /**
     * Get conditions and validations of transitions from current state
     *
     * waitlisted -- Booking basic conditions --> booked
     * waitlisted --> user_cancelled
     * waitlisted -- Session is cancelled --> event_cancelled
     * @return array
     */
    final public function get_map() : array {
        return [
            transition::to(new booked($this->signup))->with_conditions(
                /**
                 * - waitlist everyone is disabled
                 * - event has session
                 * - event has capacity
                 * - event not in the past
                 * - registration is available
                 * - event not cancelled
                 */
                booking_common::class
            ),
            transition::to(new booked($this->signup))->with_conditions(
                event_has_session::class,
                event_not_in_the_past::class,
                event_registration_is_available::class,
                event_is_not_cancelled::class,
                is_not_reservation::class
            )->with_restrictions(
                actor_can_overbook::class
            ),
            transition::to(new user_cancelled($this->signup))->with_conditions(
                event_is_not_cancelled::class
            ),
            transition::to(new event_cancelled($this->signup))->with_conditions(
                event_is_cancelled::class
            ),
        ];
    }

    /**
     * Code of status as it is stored in DB
     * Numeric statuses are backward compatible except not_set which was not meant to be written into DB.
     * Statuses don't have to follow particular order (except must be unique of course)
     * @return integer
     */
    public static function get_code() : int {
        return 60;
    }

    /**
     * Message for user on entering the state
     * @return string
     */
    public function get_message() : string {
        return get_string('joinwaitlistcompleted', 'facetoface');
    }

    /**
     * Get action label for getting into state.
     * @return string
     */
    public function get_action_label(): string {
        return get_string('joinwaitlist', 'mod_facetoface');
    }

    /**
     * Get event to fire when entering state
     *
     * @return abstract_signup_event
     */
    public function get_event() : abstract_signup_event {
        $cm = $this->signup->get_seminar_event()->get_seminar()->get_coursemodule();
        $context = \context_module::instance($cm->id);
        return booking_waitlisted::create_from_signup($this->signup, $context);
    }

    /**
     * Get the waitlisted status string.
     * @return string
     */
    public static function get_string() : string {
        return get_string('status_waitlisted', 'mod_facetoface');
    }
}
