<?php
/*
 * This file is part of Totara Learn
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\attendance;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("{$CFG->dirroot}/lib/moodlelib.php");

use mod_facetoface\signup\state\{attendance_state, booked};
use stdClass;
use moodle_recordset;
use mod_facetoface\{seminar_session, session_status, signup, seminar_event};
use mod_facetoface\signup\state\{state, not_set};

/**
 * A class that help to retrieve all the attendees of a seminar's event/session(s).
 * Whether attendees in the seminar's event or in event's session. If the attendees of event's
 * session are not populated yet, then it should take the attendees from seminar's event to return.
 *
 * Also, this class contains static methods that associate with database, and relates to the
 * attendance.
 *
 * Class attendance_helper
 * @package mod_facetoface\attendance
 */
final class attendance_helper {
    /**
     * @var int[]
     */
    private $statuses;

    /**
     * attendance_helper constructor.
     *
     * @param int[] $statuses
     */
    public function __construct(array $statuses = []) {
        if (empty($statuses)) {
            $statuses = attendance_state::get_all_attendance_code_with([booked::class]);
            if (!in_array(not_set::get_code(), $statuses)) {
                // This is for those attendance that are being saved as not_set in the
                // table facetoface_signups_dates_status. And we need to include it here
                $statuses[] = not_set::get_code();
            }
        }


        $this->statuses = $statuses;
    }

    /**
     * Base SQL to collect the attendances of an event or event session depends on usage of caller.
     *
     * @param string $beforeid  This will be used to determine whether the sql needs to have any
     *                          other id in front of the current user's id or not. It helps to avoid
     *                          the possibility of duplicated user's id.
     *
     * @return string
     */
    private function get_base_sql(string $beforeid = ""): string {
        $usernamefields = get_all_user_name_fields(true, 'u');
        return "
            SELECT
            {$beforeid}
            u.id,
            u.username,
            u.idnumber,
            {$usernamefields},
            u.email,
            u.deleted,
            u.suspended,
            su.id AS submissionid,
            su.id AS signupid,
            su.archived,
            {%extra_select%}
            f.id AS facetofaceid,
            f.course AS course
            FROM {facetoface} f
            INNER JOIN {facetoface_sessions} s ON s.facetoface = f.id
            INNER JOIN {facetoface_signups} su ON su.sessionid = s.id
            INNER JOIN {user} u ON u.id = su.userid
            {%extra_join%}
        ";
    }

    /**
     * Retrieving those attendees that are in the seminar's event level. If the list is empty,
     * then there are no attendees at all.
     *
     * Each object within the list returned should have the attributes specified as below:
     * + id: int
     * + idnumber: string
     * + email: string
     * + deleted: int (0, 1)
     * + suspended: int (0, 1)
     * + submissionid: int
     * + facetofaceid: int
     * + course: int
     * + statuscode: int
     * + grade: float|null
     * + [usernamefields] : string
     *
     * @param int $seminareventid
     * @param bool $includedeleted  Set this true, to include the deleted user in the query, otherwise, leave it as FALSE, if we
     *                              would not expect deleted users to appear in the list.
     *
     * @return stdClass[]
     */
    public function get_event_attendees(int $seminareventid, bool $includedeleted = false): array {
        global $DB;

        $sql = $this->get_base_sql();
        $sql = str_replace(
            ['{%extra_select%}', '{%extra_join%}'],
            [
                " ss.statuscode, ss.grade, ",
                " INNER JOIN {facetoface_signups_status} ss ON ss.signupid = su.id "
            ],
            $sql
        );

        [$statussql, $params] = $DB->get_in_or_equal($this->statuses);

        $sql .= "
            WHERE s.id = ?
            AND ss.statuscode {$statussql}
            AND ss.superceded <> 1
        ";

        if (!$includedeleted) {
            $sql .= " AND u.deleted <> 1 ";
        }

        $sql .= " ORDER BY u.firstname, u.lastname ASC";

        array_unshift($params, $seminareventid);
        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Retrieving those attendees that are in event's session. If the attendees list is empty, this
     * could mean that the record for session date attendance were never populated, therefore, it
     * need to bring the records from seminar's event level.
     *
     * There is also another scenario where the new user is added into the list of attendees, and
     * it does not populate the table yet. Therefore, the sql does bring those user in, and make
     * sure that user will be appearing in the record.
     *
     * Each object within the list returned should have the attributes specified as below:
     * + id: int
     * + idnumber: string
     * + email: string
     * + deleted: int (0, 1)
     * + suspended: int (0, 1)
     * + submissionid: int
     * + facetofaceid: int
     * + course: int
     * + statuscode: int
     * + [usernamefields] : string
     *
     * @param int   $seminareventid
     * @param int   $sessionid
     * @param bool  $includedeleted     Flag this to true, if we would want the query to include the deleted users.
     *
     * @return stdClass[]
     */
    public function get_session_attendees(int $seminareventid, int $sessionid, bool $includedeleted = false): array {
        global $DB;

        [$statussql, $params] = $DB->get_in_or_equal($this->statuses, SQL_PARAMS_NAMED, 'ssparam');

        // Table {facetoface_signups_status} is needed, because we do not want most of the attendees that have been
        // cancelled the event to be appearing inside the session attendance. Furthermore, if there is any new attendee
        // then this join will pick that attendee up straight away.
        $sql = $this->get_base_sql();
        $sql = str_replace(
            ['{%extra_select%}', '{%extra_join%}'],
            [
                ' sds.attendancecode as statuscode, sd.sessiontimezone, sd.timestart, sd.timefinish, ',
                " INNER JOIN {facetoface_sessions_dates} sd ON sd.sessionid = s.id
                  LEFT JOIN {facetoface_signups_dates_status} sds ON sds.signupid = su.id
                    AND sds.sessiondateid = sd.id
                    AND sds.superceded <> 1
                  LEFT JOIN {facetoface_signups_status} ss ON ss.signupid = su.id
                    AND ss.superceded <> 1
                "
            ],
            $sql
        );

        $sql .= "
            WHERE s.id = :seminareventid
            AND sd.id = :sessiondateid
            AND ss.statuscode {$statussql}
        ";

        if (!$includedeleted) {
            $sql .= " AND u.deleted <> 1 ";
        }

        $sql .= " ORDER BY u.firstname, u.lastname ASC";

        $params['seminareventid'] = $seminareventid;
        $params['sessiondateid']  = $sessionid;

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * This will call either get_session_attendees or get_event_attendees, and it depends on the
     * parameter $sessiondateid, as if this parameter is being set to greater than zero, which
     * means that the caller want to retrieve the session attendees base on the session date id.
     * Otherwise, it will return the event's attendees by default.
     *
     * The list of attendees will include the deleted users if the viewer/actor does have the ability
     * to view deleted users.
     *
     * @param int $seminareventid
     * @param int $sessiondateid
     *
     * @return event_attendee[]
     */
    public function get_attendees(int $seminareventid, int $sessiondateid = 0): array {
        global $PAGE;
        $includedeleted = has_capability('totara/core:seedeletedusers', $PAGE->context);

        if ($sessiondateid > 0) {
            $array = $this->get_session_attendees($seminareventid, $sessiondateid, $includedeleted);
        } else {
            $array = $this->get_event_attendees($seminareventid, $includedeleted);
        }

        return array_map(
            function ($e) {
                return event_attendee::map_from_record($e);
            }
            , $array
        );
    }

    /**
     * Loading a record set of attendances statuses, for a single session.
     *
     * A single object of the return list should have attributes as below
     * + statuscode: int
     * + total: int         -> The sum number of all session that this user attended to, and it
     *                          is sum base on the status code.
     * + userid: int        -> The user id of that state
     *
     * The recordset will only include the deleted user's record(s) if the viewer/actor does have the ability
     * to view deleted user
     *
     * @param int $seminareventid
     *
     * @return moodle_recordset
     */
    public function load_session_attendance_status(int $seminareventid): moodle_recordset {
        global $DB, $PAGE;
        [$statussql, $params] = $DB->get_in_or_equal($this->statuses, SQL_PARAMS_NAMED);

        $code = not_set::get_code();

        // The first query is about attendances of those session date, that the attendee has
        // data populated. Whereas the second query is about those session date, that the attendee
        // does not has populated yet. Which it should be counted as missing.
        $sql = "SELECT
            sds.attendancecode AS statuscode,
            su.userid AS userid,
            COUNT(sds.id) AS total
            FROM {facetoface_signups_dates_status} sds
            INNER JOIN {facetoface_signups} su ON su.id = sds.signupid
            %extra_join%
            INNER JOIN {facetoface_sessions_dates} sd ON sd.id = sds.sessiondateid
            INNER JOIN {facetoface_sessions} s ON s.id = sd.sessionid
            AND su.sessionid = s.id
            WHERE s.id = :sessionid1
            AND sds.attendancecode {$statussql}
            AND sds.superceded <> 1
            GROUP BY su.userid, sds.attendancecode
            UNION ALL
            SELECT
            {$code} AS statuscode,
            su.userid AS userid,
            COUNT(sd.id) AS total
            FROM {facetoface_sessions_dates} sd
            INNER JOIN {facetoface_sessions} s ON s.id = sd.sessionid
            INNER JOIN {facetoface_signups} su ON su.sessionid = s.id
            %extra_join%
            LEFT JOIN {facetoface_signups_dates_status} sds ON sds.signupid = su.id
            AND sds.sessiondateid = sd.id
            WHERE sds.id IS NULL
            AND s.id = :sessionid2
            GROUP BY su.userid
            ORDER BY statuscode";

        $replacement = "";
        if (!has_capability('totara/core:seedeletedusers', $PAGE->context)) {
            // If the user does not have the ability to view the deleted users, we should removed the deleted
            // users out of the query.
            $replacement = " INNER JOIN {user} u ON u.id = su.userid AND u.deleted <> 1";
        }

        $sql = str_replace('%extra_join%', $replacement,  $sql);
        $additional = [
            'sessionid1' => $seminareventid,
            'sessionid2' => $seminareventid,
        ];

        $params = array_merge($params, $additional);

        return $DB->get_recordset_sql($sql, $params);
    }

    /**
     * Running load_session_attendance_status internally, then after the data is returned, it
     * will loops thru the recordset and format the data as same as example.
     *
     * Returning an array that will look something similar like this:
     * userid: int -> [ statuscode: int -> total (attendances): int ]
     *
     * @example
     * [
     *      15 => [
     *          70 => 3
     *      ],
     *      16 => [
     *          70 => 1,
     *          85 => 2
     *      ]
     * ]
     *
     * @param int $seminareventid
     *
     * @return array
     */
    public function get_calculated_session_attendance_status(int $seminareventid): array {
        $set = $this->load_session_attendance_status($seminareventid);
        $data = [];

        /** @var stdClass $s */
        foreach ($set as $s) {
            if (!isset($data[$s->userid])) {
                $data[$s->userid] = [];
            }

            if (!isset($data[$s->userid][$s->statuscode])) {
                $data[$s->userid][$s->statuscode] = $s->total;
            } else {
                $data[$s->userid][$s->statuscode] += $s->total;
            }
        }

        $set->close();
        return $data;
    }

    /**
     * Process the attendance records for seminar's event with a session level.
     * This populated data in table {facetoface_signups_dates_status}
     *
     * @param array $attendance     Array<submissionid, statuscode> Where submission-id represents for signup.id
     *                              within table {facetoface_signups}.
     * @param int   $sessiondateid
     *
     * @return bool
     */
    public static function process_session_attendance(array $attendance, int $sessiondateid): bool {
        global $DB;
        if (empty($attendance)) {
            // For now, if no attendance are submitted, just return true here.
            return true;
        }

        $session = new seminar_session($sessiondateid);
        if (!$session->is_attendance_open()) {
            return false;
        }

        $trans = $DB->start_delegated_transaction();

        foreach ($attendance as $signupid => $value) {
            $state = state::from_code($value);
            $signup = new signup($signupid);

            $ss = session_status::from_signup($signup, $sessiondateid);

            if ($ss->get_attendancecode() == $state::get_code()) {
                // Skip updating record if the state is remaining the same.
                continue;
            }

            $ss->set_attendance_status($state);
            $ss->save();
        }

        $trans->allow_commit();
        return true;
    }
}