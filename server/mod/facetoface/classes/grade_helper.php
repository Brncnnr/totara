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
* @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
* @package mod_facetoface
*/

namespace mod_facetoface;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir.'/gradelib.php');

/**
 * Additional grade functionality.
 */
final class grade_helper {
    /** Maximum compatibility with grade_update() */
    const FORMAT_GRADELIB = 0;

    /** facetoface-specific format */
    const FORMAT_FACETOFACE = 1;

    /**
     * Format grade into value with respect of course grade settings.
     * @param float|null $grade raw grade value
     * @param int $course course id
     * @return string|null locale float or empty, or null if $grade is null
     */
    public static function format(?float $grade, int $course): ?string {
        global $CFG;

        if (empty($course) || (int)$course <= 0) {
            debugging('Invalid course id', DEBUG_DEVELOPER);
            return '';
        }

        if (!empty($grade)) {
            $decimalpoints = grade_get_setting($course, 'decimalpoints', $CFG->grade_decimalpoints);
            $grade = format_float($grade, $decimalpoints);
        }
        return $grade;
    }

    /**
     * Create a grade record for the given sign-up and update activity completion status.
     *
     * @param seminar_event $seminarevent
     * @param signup        $signup
     * @return true
     * @throws \coding_exception
     */
    public static function grade_signup(seminar_event $seminarevent, signup $signup): bool {
        global $CFG;

        if ($seminarevent->get_id() !== $signup->get_sessionid()) {
            throw new \coding_exception('The signup #'.$signup->get_id().' does not belong to the seminar event #'.$seminarevent->get_id());
        }
        // Bail out before userid #0 confuses facetoface_update_grades().
        if (empty($signup->get_userid())) {
            return true;
        }

        // Necessary for facetoface_update_grades()
        require_once($CFG->dirroot . '/mod/facetoface/lib.php');

        $seminar = $seminarevent->get_seminar();
        $cm = $seminar->get_coursemodule();
        $facetoface = $seminar->get_properties();
        $facetoface->cmidnumber = $cm->idnumber;
        $facetoface->modname = $cm->modname;

        // Grade functions stay in lib file.
        facetoface_update_grades($facetoface, $signup->get_userid(), true);

        // The aggregation of activity completion state is not necessary here.
        // \completion_info::update_state() calls internal_get_state() that calls facetoface_get_completion_state(),
        // in which other criteria take account of activity completion.
        $seminar->set_completion($signup->get_userid(), COMPLETION_UNKNOWN);
        return true;
    }

    /**
     * Calculate a user's final grade.
     *
     * @param integer $userid user ID
     * @param \stdClass|seminar $facetoface seminar instance
     * @param integer $format see get_final_grades()
     * @return null|\stdClass see get_final_grades()
     */
    private static function get_final_grade_of(int $userid, $facetoface, int $format): ?\stdClass {
        global $DB;
        /** @var \moodle_database $DB */

        if (empty($userid)) {
            throw new \coding_exception('$userid must not be zero');
        }
        if ($facetoface instanceof seminar) {
            $f2fid = $facetoface->get_id();
            $grading_method = $facetoface->get_eventgradingmethod();
        } else if ($facetoface instanceof \stdClass) {
            $f2fid = $facetoface->id;
            $grading_method = $facetoface->eventgradingmethod ?? seminar::GRADING_METHOD_DEFAULT;
        } else {
            throw new \coding_exception('$facetoface must be a seminar object or a database record');
        }

        switch ($grading_method) {
            case seminar::GRADING_METHOD_GRADEHIGHEST:
                $order_column = 'sus.grade';
                $order_direction = 'DESC';
                break;
            case seminar::GRADING_METHOD_GRADELOWEST:
                $order_column = 'sus.grade';
                $order_direction = 'ASC';
                break;
            case seminar::GRADING_METHOD_EVENTFIRST:
                $order_column = 'm.mintimestart';
                $order_direction = 'ASC';
                break;
            case seminar::GRADING_METHOD_EVENTLAST:
                $order_column = 'm.maxtimefinish';
                $order_direction = 'DESC';
                break;

            default:
                throw new \coding_exception(sprintf(
                    "Grading method %d of seminar #%d is not defined",
                    $grading_method, $f2fid
                ));
        }

        $sql =
            "SELECT su.id,
                    sus.grade AS rawgrade,
                    m.maxtimefinish AS timecompleted
               FROM {facetoface_signups} su
         INNER JOIN {facetoface_signups_status} sus ON sus.signupid = su.id
         INNER JOIN {facetoface_sessions} s ON s.id = su.sessionid
          LEFT JOIN (
             SELECT sd.sessionid,
                    MIN(sd.timestart) AS mintimestart,
                    MAX(sd.timefinish) AS maxtimefinish
               FROM {facetoface_sessions_dates} sd
              WHERE (1=1)
           GROUP BY sd.sessionid
             ) m ON m.sessionid = s.id
              WHERE (s.facetoface = :f2f)
                AND (s.cancelledstatus = 0)
                AND (sus.superceded = 0)
                AND (sus.grade IS NOT NULL)
                AND (su.archived = 0 OR su.archived IS NULL)
                AND (su.userid = :uid)
           ORDER BY {$order_column} {$order_direction}";

        $records = $DB->get_records_sql($sql, ['f2f' => $f2fid, 'uid' => $userid], 0, 1);
        $record = reset($records);
        if ($record === false) {
            return null;
        }

        $object = new \stdClass();
        if ($format === self::FORMAT_FACETOFACE) {
            $object->timecompleted = $record->timecompleted;
            // Note: Add here if any other properties are necessary.
        } else {
            $object->id = $userid;
            // Note: Do not add anything here.
        }
        $object->userid = $userid;
        $object->rawgrade = grade_floatval($record->rawgrade);
        return $object;
    }

    /**
     * Calculate users' final grades.
     *
     * @param \stdClass|seminar $facetoface seminar instance
     * @param integer $userid user ID or 0 to get all grades in the seminar
     * @param integer $format Set FORMAT_GRADELIB to return records that can be passed to `grade_update()`
     * @return array|false array of objects in the following format, or false if nothing applicable
     * - if FORMAT_GRADELIB is given, [ userid => [ id, userid, rawgrade ], ... ]
     * - if FORMAT_FACETOFACE is given, [ userid => [ userid, rawgrade, timecompleted ], ... ]
     */
    public static function get_final_grades($facetoface, int $userid = 0, int $format = self::FORMAT_GRADELIB) {
        global $DB;
        /** @var \moodle_database $DB */

        if (!in_array($format, [self::FORMAT_GRADELIB, self::FORMAT_FACETOFACE])) {
            throw new \coding_exception('Unknown $format: '.$format);
        }

        if ($facetoface instanceof seminar) {
            $f2fid = $facetoface->get_id();
        } else if ($facetoface instanceof \stdClass) {
            $f2fid = $facetoface->id;
        } else {
            throw new \coding_exception('$facetoface must be a seminar object or a database record');
        }

        if (!empty($userid)) {
            $userids = [$userid];
        } else {
            $userids = $DB->get_fieldset_sql(
                'SELECT DISTINCT su.userid
                   FROM {facetoface_signups} su
             INNER JOIN {facetoface_sessions} s ON s.id = su.sessionid
              LEFT JOIN {facetoface_signups_status} sus ON sus.signupid = su.id
                  WHERE (s.facetoface = :f2f)
                    AND (s.cancelledstatus = 0)
                    AND (sus.superceded = 0)
                    AND (sus.grade IS NOT NULL)
                    AND (su.archived = 0 OR su.archived IS NULL)
                    AND (su.userid != 0)',
                ['f2f' => $f2fid]
            );
        }

        $result = [];
        foreach ($userids as $userid) {
            $object = self::get_final_grade_of($userid, $facetoface, $format);
            if ($object !== null) {
                $result[$userid] = $object;
            }
        }

        if (empty($result)) {
            return false;
        }
        return $result;
    }
}
