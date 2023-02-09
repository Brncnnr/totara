<?php
/*
 * This file is part of Totara Learn
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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface;
use mod_facetoface\signup\condition\event_taking_attendance;
use mod_facetoface\attendance\attendance_helper;
use mod_facetoface\signup\state\booked;

defined('MOODLE_INTERNAL') || die();

/**
 * Class seminar_session represents Seminar event session dates (aka event sessions)
 */
final class seminar_session implements seminar_iterator_item {

    use traits\crud_mapper;

    /**
     * @var int {facetoface_sessions_dates}.id
     */
    private $id = 0;
    /**
     * @var int {facetoface_sessions_dates}.sessionid
     */
    private $sessionid = 0;
    /**
     * @var string {facetoface_sessions_dates}.sessiontimezone
     */
    private $sessiontimezone = "99";
    /**
     * @var int {facetoface_sessions_dates}.timestart
     */
    private $timestart = 0;
    /**
     * @var int {facetoface_sessions_dates}.timefinish
     */
    private $timefinish = 0;
    /**
     * @var string facetoface_sessions_dates table name
     */
    const DBTABLE = 'facetoface_sessions_dates';
    /**
     * Related to seminar event here
     * @var seminar_event|null
     */
    private $seminarevent = null;

    /**
     * Session constructor.
     * @param int $id {facetoface_sessions_dates}.id If 0 - new Session will be created
     */
    public function __construct(int $id = 0) {

        $this->id = $id;
        $this->load();
    }

    /**
     * Load seminar event session dates data from DB
     *
     * @return seminar session this
     */
    public function load(): seminar_session {

        return $this->crud_load();
    }

    /**
     * Create/update {facetoface_sessions_dates}.record
     */
    public function save(): void {

        $this->crud_save();

        if (null !== $this->seminarevent) {
            // We need to clear the sessions stored in seminarevent here, because the sessions had been updated,
            // therefore, no point to keep the invalid data.
            $this->seminarevent->clear_sessions();
        }
    }

    /**
     * Map data object to class instance.
     *
     * @param \stdClass $object
     * @param boolean $strict Set false to ignore bogus properties
     * @return seminar_session
     */
    public function from_record(\stdClass $object, bool $strict = true): seminar_session {

        return $this->map_object($object, $strict);
    }

    /**
     * Return a dummy data object, that whole data associated with the table's column name in db.
     * @return \stdClass
     */
    public function to_record(): \stdClass {
        return $this->unmap_object();
    }

    /**
     * Remove seminar event session dates from database
     */
    public function delete(): void {
        global $DB;

        // Unlink ad-hoc assets/facilitators/rooms.
        asset_helper::sync($this->id);
        facilitator_helper::sync($this->id);
        room_helper::sync($this->id);

        $DB->delete_records(self::DBTABLE, ['id' => $this->id]);

        // Re-load instance with default values.
        $this->map_object((object)get_object_vars(new self()));
    }

    /**
     * Get session date id
     *
     * @return int
     */
    public function get_id(): int {
        return (int)$this->id;
    }

    /**
     * Get seminar event id (known as sessionid)
     *
     * @return int
     */
    public function get_sessionid(): int {
        return (int)$this->sessionid;
    }
    /**
     * Set seminar event id (known as sessionid)
     *
     * @param int $sessionid
     * @return seminar_session
     */
    public function set_sessionid(int $sessionid): seminar_session {
        $this->sessionid = $sessionid;
        return $this;
    }

    /**
     * Get timezone for this session date
     * @return string
     */
    public function get_sessiontimezone(): string {
        return (string)$this->sessiontimezone;
    }
    /**
     * Set timezone for this session date
     * @param string $sessiontimezone
     * @return seminar_session
     */
    public function set_sessiontimezone(string $sessiontimezone): seminar_session {
        $this->sessiontimezone = $sessiontimezone;
        return $this;
    }

    /**
     * Get start time for this session date
     *
     * @return int
     */
    public function get_timestart(): int {
        return (int)$this->timestart;
    }
    /**
     * Set start time for this session date
     *
     * @param int $timestart
     * @return seminar_session
     */
    public function set_timestart(int $timestart): seminar_session {
        $this->timestart = $timestart;
        return $this;
    }

    /**
     * Get end time for this session date
     *
     * @return int
     */
    public function get_timefinish(): int {
        return (int)$this->timefinish;
    }
    /**
     * Set end time for this session date
     *
     * @param int $timefinish
     * @return seminar_session
     */
    public function set_timefinish(int $timefinish): seminar_session {
        $this->timefinish = $timefinish;
        return $this;
    }

    /**
     * Is session date over, in the past?
     *
     * @param int $time
     * @return bool
     */
    public function is_over(int $time = 0): bool {
        if (empty($this->timefinish)) {
            return false;
        }

        if (0 >= $time) {
            $time = time();
        }

        // If the time finish is already behind the current time. Then this session is
        // obviously completed.
        return $this->timefinish < $time;
    }

    /**
     * Is session date upcoming, in the future?
     *
     * @param int $time
     * @return bool
     */
    public function is_upcoming(int $time = 0): bool {
        if (empty($this->timestart)) {
            return false;
        }

        if (0 >= $time) {
            $time = time();
        }

        // If the current time is behind the timestart of the session, then this session
        // will start in the future, actually.
        return $this->timestart > $time;
    }

    /**
     * Checking whether the session has started or not.
     *
     * @param int $time
     * @return bool
     */
    public function is_start(int $time = 0): bool {
        if (empty($this->timestart)) {
            return false;
        }

        if (0 >= $time) {
            $time = time();
        }

        // If the current time exceed the timestart, which means that the session
        // has already started
        return $this->timestart <= $time;
    }

    /**
     * Returning the time description of seminar's session.
     *
     * @param string $fullformatstring name of string to use for date and time
     * @param string $timeformatstring name of string to use for just time
     * @return string
     */
    public function get_time_description(string $fullformatstring = 'strftimerecentfull', string $timeformatstring = 'strftimetime'): string {
        if (empty($this->timestart) || empty($this->timefinish)) {
            return '';
        }

        $a = new \stdClass();
        $fullformat = get_string($fullformatstring, 'langconfig');
        $a->start = userdate($this->timestart, $fullformat);

        $startdate = date("Y-m-d", $this->timestart);
        $finishdate = date("Y-m-d", $this->timefinish);

        if ($startdate == $finishdate) {
            // If it is in a same date, then we display Date month Year Hour -> end hour
            $timeformat = get_string($timeformatstring, 'langconfig');
            $a->end = userdate($this->timefinish, $timeformat);
        } else {
            $a->end = date_format_string($this->timefinish, $fullformat);
        }

        return get_string('sessiontimedescription', 'mod_facetoface', $a);
    }

    /**
     * Get the seminar event that this session date belongs to
     *
     * @return seminar_event
     */
    public function get_seminar_event(): seminar_event {
        if (null == $this->seminarevent) {
            $this->seminarevent = new seminar_event($this->sessionid);
        }

        return $this->seminarevent;
    }

    /**
     * Check whether the session attendance taking is available or not.
     *
     * @param int $time
     * @return bool
     */
    public function is_attendance_open(int $time = 0): bool {
        return attendance_taking_status::is_available($this->get_attendance_taking_status(null, $time, false, false));
    }

    /**
     * Return attendance taking status of the seminar session.
     *
     * @param integer|null  $sessionattendance One of seminar::SESSION_ATTENDANCE_xxx, or null to load the seminar setting
     * @param integer       $time           The current timestamp
     * @param boolean       $checksaved     Set false to not check the status of each attendee
     * @param boolean       $checkattendees Set true to return NOTAVAILABLE when the session is open but no attendees
     * @return integer  One of attendance_taking_status constants
     */
    public function get_attendance_taking_status(int $sessionattendance = null, int $time = 0, bool $checksaved = true, bool $checkattendees = false): int {
        $seminarevent = $this->get_seminar_event();
        if (null === $sessionattendance) {
            $seminar = $seminarevent->get_seminar();
            $sessionattendance = $seminar->get_sessionattendance();
            if ($sessionattendance == 1) {
                // Print debugging message and apply shim.
                debugging("An old sessionattendance has been detected. Please upgrade the website!!");
                $sessionattendance = $seminar->fix_up_session_attendance_time($sessionattendance);
            }
        }
        if (seminar::SESSION_ATTENDANCE_DISABLED == $sessionattendance) {
            return attendance_taking_status::NOTAVAILABLE;
        }
        if (0 != $seminarevent->get_cancelledstatus()) {
            // The seminar event is cancelled.
            return attendance_taking_status::CANCELLED;
        }

        if (0 >= $time) {
            $time = time();
        }

        switch ($sessionattendance) {
            case seminar::SESSION_ATTENDANCE_UNRESTRICTED:
                if ($seminarevent->get_sessions()->is_empty()) {
                    // Wait listed event should not open attendance for session.
                    // This seems to be a nonsense check here, as it is in a session level and it
                    // should not be checking for wait-listed event, because this made session to
                    // check for another session, inside of this session.
                    // However, there is a case where the session/event was not saved previously
                    // and developer somehow use this checking method for their logic.
                    return attendance_taking_status::UNKNOWN;
                } else if ((int)$this->timestart <= 0 || (int)$this->timefinish <= 0) {
                    // A scenario where developer accidentally did not provide timestart or timefinish
                    // previously, and either saved it to the database or using the logic check
                    // straight away. Despite of attendance time is set to any time, but it needs
                    // times to be checked against any time.
                    return attendance_taking_status::UNKNOWN;
                }
                break;

            case seminar::SESSION_ATTENDANCE_START:
                // For attendance time start, it need to allow actor to mark attendance number of
                // minutes before the actual start time.
                $time += event_taking_attendance::UNLOCKED_SECS_PRIOR_TO_START;
                if (!$this->is_start($time)) {
                    return attendance_taking_status::CLOSED_UNTILSTART;
                }
                break;

            case seminar::SESSION_ATTENDANCE_END:
                if (!$this->is_over($time)) {
                    return attendance_taking_status::CLOSED_UNTILEND;
                }
                break;

            default:
                debugging("The session attendance time {$sessionattendance} is not valid.", DEBUG_DEVELOPER);
                return attendance_taking_status::UNKNOWN;
        }

        // Skip digging into attendees to improve performance.
        if (!$checksaved) {
            return attendance_taking_status::OPEN;
        }

        $helper = new attendance_helper();
        $attendees = $helper->get_attendees($seminarevent->get_id(), $this->get_id());

        if (empty($attendees)) {
            if ($checkattendees) {
                // Taking attendance is not open if no one attends the event.
                return attendance_taking_status::NOTAVAILABLE;
            } else {
                // Taking attendance is open if no one attends the event.
                return attendance_taking_status::OPEN;
            }
        }

        $saved = true;
        foreach ($attendees as $attendee) {
            if (!$attendee->statuscode || $attendee->statuscode == booked::get_code()) {
                $saved = false;
                break;
            }
        }

        if ($saved) {
            return attendance_taking_status::ALLSAVED;
        } else {
            return attendance_taking_status::OPEN;
        }
    }

    /**
     * Get room id for this session date
     * @return int
     * @deprecated since Totara 13
     */
    public function get_roomid(): int {
        debugging('seminar_session::get_roomid() function has been deprecated, please use room::get_id()', DEBUG_DEVELOPER);
        return 0;
    }

    /**
     * @return bool
     * @deprecated since Totara 13
     */
    public function has_room(): bool {
        debugging('seminar_session::has_room() function has been deprecated, please use room::exists()', DEBUG_DEVELOPER);
        return !empty(0);
    }

    /**
     * Set room id for this session date
     * @param int $roomid
     * @return seminar_session
     * @deprecated since Totara 13
     */
    public function set_roomid(int $roomid): seminar_session {
        debugging('seminar_session::set_roomid() function has been deprecated, please use room::__construct(id)', DEBUG_DEVELOPER);
        return $this;
    }
}
