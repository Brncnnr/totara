<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\internal;

use mod_facetoface\seminar_event;
use mod_facetoface\seminar_event_helper;
use mod_facetoface\signup;

defined('MOODLE_INTERNAL') || die();

/**
 * Provide type-safe session and/or booking data required by renderer functions.
 *
 * {facetoface_sessions} fields
 * @property-read integer $id
 * @property-read integer $facetoface
 * @property-read integer $capacity
 * @property-read integer $allowoverbook
 * @property-read integer $waitlisteveryone
 * @property-read string  $details
 * @property-read integer $normalcost
 * @property-read integer $discountcost
 * @property-read integer $allowcancellations
 * @property-read integer $cancellationcutoff
 * @property-read integer $timecreated
 * @property-read integer $timemodified
 * @property-read integer $usermodified
 * @property-read integer $selfapproval
 * @property-read integer $mincapacity
 * @property-read integer $cutoff
 * @property-read integer $sendcapacityemail
 * @property-read integer $registrationtimestart
 * @property-read integer $registrationtimefinish
 * @property-read integer $cancelledstatus
 *
 * @property-read integer $mintimestart
 * @property-read integer $maxtimefinish
 * @property-read bool    $isstarted
 * @property-read bool    $isprogress
 * @property-read integer $cntdates
 *
 * @property-read session_date_data[] $sessiondates
 * @property-read session_signup_data|null $bookedsession
 */
final class session_data extends \stdClass {
    /**
     * Create a session_data object from seminar_event and signup.
     *
     * @param seminar_event $seminarevent
     * @param signup|null $signup
     * @param boolean $sortbyascending Set true to sort sessions by past first
     * @param integer $timenow Current timestamp
     * @return self
     */
    public static function from_seminar_event(seminar_event $seminarevent, ?signup $signup, bool $sortbyascending = true, int $timenow = 0): self {
        return seminar_event_helper::get_sessiondata($seminarevent, $signup, $sortbyascending);
    }

    /**
     * Map object to a seminar_event instance and the list of seminar_session objects.
     *
     * @return seminar_event
     */
    public function to_seminar_event(): seminar_event {
        // strictness needs to be false because of mintimestart, maxtimefinish, etc
        return (new seminar_event())->from_record_with_dates($this, false);
    }
}
