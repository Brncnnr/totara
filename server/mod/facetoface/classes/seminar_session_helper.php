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
 * @author  Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface;

defined('MOODLE_INTERNAL') || die();

/**
 * Additional seminar_session functionality.
 */
final class seminar_session_helper {

    /**
     * Return Event session status as a localised string.
     * Use get_status_from() if possible.
     * @param \stdClass $session    {facetoface_sessions} database record
     * @param \stdClass|null $date  {facetoface_sessions_dates} database record
     * @param integer $timenow      The timestamp to calculate status
     * @return string               Non-empty string if success
     *                              Empty string if the event is waitlisted or the function fails
     */
    public static function get_status(\stdClass $session, ?\stdClass $date, int $timenow = 0): string {
        $seminarevent = (new seminar_event())->from_record($session, false);
        $seminarsession = $date !== null ? (new seminar_session())->from_record($date, false) : null;
        return self::get_status_from($seminarevent, $seminarsession, $timenow);
    }

    /**
     * Return Event session status as a localised string.
     * @param seminar_event $seminarevent
     * @param seminar_session|null $seminarsession
     * @param integer $timenow      The timestamp to calculate status
     * @return string               Non-empty string if success
     *                              Empty string if the event is waitlisted or the function fails
     */
    public static function get_status_from(seminar_event $seminarevent, ?seminar_session $seminarsession, int $timenow = 0): string {
        if ($timenow <= 0) {
            $timenow = time();
        }
        if (!empty($seminarevent->get_cancelledstatus())) {
            $status = 'cancelled';
        } else if ($seminarsession === null) {
            // Empty for wait-listed events.
            return '';
        } else if ($seminarsession->is_over($timenow)) {
            $status = 'over';
        } else if ($seminarsession->is_start($timenow) && !$seminarsession->is_over($timenow)) {
            $status = 'inprogress';
        } else if (!$seminarsession->is_start($timenow)) {
            $status = 'upcoming';
        } else {
            debugging(
                "Logically impossible session times (start={$date->timestart} end={$date->timefinish} now={$timenow})",
                DEBUG_DEVELOPER
            );
            return '';
        }
        return get_string('sessionstatus:' . $status, 'mod_facetoface');
    }

    /**
     * Get the attendance taking status.
     *
     * @param seminar_event $seminarevent
     * @param seminar_session $seminarsession
     * @param integer $attendancetime one of seminar::SESSION_ATTENDANCE_xxx
     * @param integer $timenow any timestamp or 0 for current time
     * @return array of [state, icon, text, url] that need to pass through set_state(), set_icon(), set_text() and set_link() respectively
     */
    public static function get_attendance_taking_status(seminar_event $seminarevent, seminar_session $seminarsession, int $attendancetime, int $timenow = 0): array {
        if (!empty($seminarevent->get_cancelledstatus())) {
            return ['cancelled', '', '', ''];
        }

        $helper = new attendees_helper($seminarevent);
        if (!$helper->count_attendees()) {
            return ['none', '', get_string('attendancetracking:noattendees', 'mod_facetoface'), ''];
        }

        $status = $seminarsession->get_attendance_taking_status($attendancetime, $timenow);
        switch ($status) {
            case attendance_taking_status::CLOSED_UNTILEND:
                return ['locked', '', get_string('attendancetracking:openatend', 'mod_facetoface'), ''];

            case attendance_taking_status::CLOSED_UNTILSTART:
                return ['locked', '', get_string('attendancetracking:openatstart', 'mod_facetoface'), ''];

            case attendance_taking_status::OPEN:
                // no break

            case attendance_taking_status::ALLSAVED:
                $url = new \moodle_url('/mod/facetoface/attendees/takeattendance.php', ['s' => $seminarevent->get_id(), 'sd' => $seminarsession->get_id()]);
                if ($status == attendance_taking_status::ALLSAVED) {
                    return ['saved', 'check-circle-success', get_string('attendancetracking:saved', 'mod_facetoface'), $url];
                } else {
                    return ['open', '', get_string('attendancetracking:open', 'mod_facetoface'), $url];
                }
        }

        throw new \coding_exception("Invalid attendance taking status: {$status}");
    }
}
