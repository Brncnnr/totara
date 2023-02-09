<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2014 onwards Totara Learning Solutions LTD
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
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\event;
defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when users cancel their bookings.
 *
 * @property-read array $other {
 * Extra information about the event.
 *
 * - sessionid Session's ID.
 *
 * }
 *
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @package mod_facetoface
 */
class booking_cancelled extends abstract_signup_event {

    /** @var bool Flag for prevention of direct create() call. */
    protected static $preventcreatecall = true;

    /**
     * Create instance of event.
     *
     * @param \stdClass $session
     * @param \context_module $context
     * @return booking_cancelled
     *
     * @deprecated since Totara 13.0
     */
    public static function create_from_session(\stdClass $session, \context_module $context) {

        debugging('booking_cancelled::create_from_session() function has been deprecated, please use booking_cancelled::create_from_signup() instead',
            DEBUG_DEVELOPER);

        $data = array(
            'context' => $context,
            'other'  => array('sessionid' => $session->id)
        );

        self::$preventcreatecall = false;
        /** @var booking_cancelled $event */
        $event = self::create($data);
        self::$preventcreatecall = true;

        return $event;
    }

    /**
     * Init method
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventbookingcancelled', 'mod_facetoface');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        if (!empty($this->relateduserid)) {
            if ((int)$this->userid == (int)$this->relateduserid) {
                return "User with id {$this->userid} has cancelled their booking for Seminar Event with the id {$this->other['sessionid']}.";
            } else {
                return "User with id {$this->relateduserid} has been cancelled for Seminar Event with the id {$this->other['sessionid']} by user with id {$this->userid}.";
            }
        } else {
            return "User with id {$this->userid} cancelled a signup for another user with the signupid {$this->other['signupid']} in Seminar Event with the id {$this->other['sessionid']}";
        }
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        $params = array('s' => $this->other['sessionid']);
        return new \moodle_url('/mod/facetoface/attendees/cancellations.php', $params);
    }

    /**
     * Return the legacy event log data.
     *
     * @return array
     */
    public function get_legacy_logdata() {
        return array($this->courseid, 'facetoface', 'cancel booking', "cancelsignup.php?s={$this->other['sessionid']}",
            $this->other['sessionid'], $this->contextinstanceid);
    }
}
