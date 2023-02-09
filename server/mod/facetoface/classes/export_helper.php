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

use mod_facetoface\output\session_time;

/**
 * Additional export functionality.
 */
final class export_helper {

    /**
     * Download data in CSV format
     * Note: this function does not return
     *
     * @param array $fields Array of column headings
     * @param array $datarows Array of data to populate table with
     * @param string|null $file Name of file for exportig
     */
    public static function download_csv(array $fields, array $datarows, string $file = null): void {
        global $CFG;

        require_once($CFG->libdir . '/csvlib.class.php');

        $csvexport = new \csv_export_writer();
        $csvexport->set_filename($file);
        $csvexport->add_data($fields);

        $numfields = count($fields);
        foreach ($datarows as $record) {
            $row = array();
            for ($j = 0; $j < $numfields; $j++) {
                $row[] = (isset($record[$j]) ? $record[$j] : '');
            }
            $csvexport->add_data($row);
        }

        $csvexport->download_file();
        die;
    }

    /**
     * Download data in ODS format
     * Note: this function does not return
     *
     * @param array $fields Array of column headings
     * @param array $datarows Array of data to populate table with
     * @param string|null $file Name of file for exportig
     */
    public static function download_ods(array $fields, array $datarows, string $file = null): void {
        global $CFG;

        require_once("$CFG->libdir/odslib.class.php");
        $filename = clean_filename($file . '.ods');

        header("Content-Type: application/download\n");
        header("Content-Disposition: attachment; filename=$filename");
        header("Expires: 0");
        header("Cache-Control: must-revalidate,post-check=0,pre-check=0");
        header("Pragma: public");

        $workbook = new \MoodleODSWorkbook('-');
        $workbook->send($filename);

        $worksheet = array();

        $worksheet[0] = $workbook->add_worksheet('');
        $row = 0;
        $col = 0;

        foreach ($fields as $field) {
            $worksheet[0]->write($row, $col, strip_tags($field));
            $col++;
        }
        $row++;

        $numfields = count($fields);

        foreach ($datarows as $record) {
            for ($col = 0; $col < $numfields; $col++) {
                if (isset($record[$col])) {
                    $value = $record[$col];
                    if (is_array($value)) {
                        if (method_exists($worksheet[0], 'write_' . $value[0])) {
                            $worksheet[0]->{'write_' . $value[0]}($row, $col, $value[1], $value[2]);
                        } else {
                            $worksheet[0]->write($row, $col, html_entity_decode($value[1], ENT_COMPAT, 'UTF-8'));
                        }
                    } else {
                        $worksheet[0]->write($row, $col, html_entity_decode($value, ENT_COMPAT, 'UTF-8'));
                    }
                }
            }
            $row++;
        }

        $workbook->close();
        die;
    }

    /**
     * Download data in XLS format
     * Note: this function does not return
     *
     * @param array $fields Array of column headings
     * @param array $datarows Array of data to populate table with
     * @param string|null $file Name of file for exportig
     */
    public static function download_xls(array $fields, array $datarows, string $file = null): void {
        global $CFG;

        require_once($CFG->libdir . '/excellib.class.php');
        $filename = clean_filename($file . '.xls');

        header("Content-Type: application/download\n");
        header("Content-Disposition: attachment; filename=$filename");
        header("Expires: 0");
        header("Cache-Control: must-revalidate,post-check=0,pre-check=0");
        header("Pragma: public");

        $workbook = new \MoodleExcelWorkbook('-');
        $workbook->send($filename);

        $worksheet = array();

        $worksheet[0] = $workbook->add_worksheet('');
        $row = 0;
        $col = 0;

        foreach ($fields as $field) {
            $worksheet[0]->write($row, $col, strip_tags($field));
            $col++;
        }
        $row++;

        $numfields = count($fields);

        foreach ($datarows as $record) {
            for ($col = 0; $col < $numfields; $col++) {
                $value = $record[$col];
                if (is_array($value)) {
                    if (method_exists($worksheet[0], 'write_' . $value[0])) {
                        $worksheet[0]->{'write_' . $value[0]}($row, $col, $value[1], $value[2]);
                    } else {
                        $worksheet[0]->write($row, $col, html_entity_decode($value[1], ENT_COMPAT, 'UTF-8'));
                    }
                } else {
                    $worksheet[0]->write($row, $col, html_entity_decode($value, ENT_COMPAT, 'UTF-8'));
                }
            }
            $row++;
        }

        $workbook->close();
        die;
    }

    /**
     * Write in the worksheet the given facetoface attendance information.
     *
     * @param \MoodleExcelWorksheet|\MoodleODSWorksheet $worksheet
     * @param seminar $seminar Seminar being exported
     * @param int|\MoodleExcelFormat $dateformat
     */
    public static function prepare($worksheet, seminar $seminar, $dateformat): void {
        global $CFG;

        $usercustomfields = explode(',', $CFG->facetoface_export_customprofilefields);
        $displaytimezones = (bool)get_config(null, 'facetoface_displaysessiontimezones');
        $coursecontext = \context_course::instance($seminar->get_course());
        $fields = static::get_fields($usercustomfields);
        $sessionsignups = static::get_sessionsignups($seminar, $usercustomfields);

        $timenow = time();
        $i = 0;

        $sessions = static::get_sessions($seminar->get_id());
        foreach ($sessions as $session) {
            $session->sessiondates = static::get_sessiondates($session, $worksheet, $displaytimezones);
            $session->roomstring = '';
            /** @var \mod_facetoface\room_list $rooms */
            $rooms = \mod_facetoface\room_list::from_session($session->dateid);
            if (!$rooms->is_empty()) {
                foreach ($rooms as $room) {
                    $session->roomstring .= (string)$room . "\n";
                }
            }
            if ($session->timestart) {
                $session->status = static::get_status($session, $timenow);
            }

            if (!empty($sessionsignups[$session->id])) {
                static::write_signups($worksheet, $sessionsignups, $session, $dateformat, $coursecontext, $fields, $i);
            } else {
                static::write_nosignups($worksheet, $session, $dateformat, $coursecontext, $fields, $i);
            }
        }
    }

    /**
     * Return an object with all values for a user's custom fields.
     * This is about 15 times faster than the custom field API.
     *
     * @param int $userid
     * @param mixed $fieldstoinclude Limit the fields returned/cached to these ones (optional)
     * @return stdClass
     */
    public static function get_user_customfields(int $userid, $fieldstoinclude = null) : \stdClass {
        global $DB;

        // Cache all lookup
        static $customfields = null;
        if (!$customfields) {
            $customfields = array();
        }

        if (!empty($customfields[$userid])) {
            return $customfields[$userid];
        }

        $ret = new \stdClass();

        $sql = 'SELECT '.$DB->sql_concat("'customfield_'", 'uif.shortname').' AS shortname, id.data
              FROM {user_info_field} uif
              JOIN {user_info_data} id ON id.fieldid = uif.id
              JOIN {user_info_category} c ON uif.categoryid = c.id
              WHERE id.userid = ? ';
        $params = array($userid);
        if (!empty($fieldstoinclude)) {
            list($insql, $inparams) = $DB->get_in_or_equal($fieldstoinclude);
            $sql .= ' AND uif.shortname '.$insql;
            $params = array_merge($params, $inparams);
        }
        $sql .= ' ORDER BY c.sortorder, uif.sortorder';

        $customfields = $DB->get_records_sql($sql, $params);
        foreach ($customfields as $field) {
            $fieldname = $field->shortname;
            $ret->$fieldname = $field->data;
        }

        $customfields[$userid] = $ret;
        return $ret;
    }

    /**
     * Get specific signups for seminar - includes signups for all sessions accross all events.
     *
     * @param int $seminarid
     * @return array
     */
    private static function get_signups(int $seminarid) : array {
        global $DB;

        $signupsql = "
        SELECT su.id AS submissionid, s.id AS sessionid, u.*, f.course AS courseid, f.selectjobassignmentonsignup,
            ss.grade, sign.timecreated, su.jobassignmentid
        FROM {facetoface} f
        JOIN {facetoface_sessions} s ON s.facetoface = f.id
        JOIN {facetoface_signups} su ON s.id = su.sessionid
        JOIN {facetoface_signups_status} ss ON su.id = ss.signupid
        JOIN {user} u ON u.id = su.userid AND u.deleted = 0
        LEFT JOIN (
            SELECT ss.signupid, MAX(ss.timecreated) AS timecreated
            FROM {facetoface_signups_status} ss
            INNER JOIN {facetoface_signups} s ON s.id = ss.signupid
            INNER JOIN {facetoface_sessions} se ON s.sessionid = se.id AND se.facetoface = $seminarid
            WHERE ss.statuscode IN (:booked,:waitlisted)
            GROUP BY ss.signupid
        ) sign ON su.id = sign.signupid
        WHERE f.id = :fid AND ss.superceded != 1 AND ss.statuscode >= :waitlisted2
        ORDER BY s.id, u.firstname, u.lastname";
        $signupparams =  array(
            'booked' => \mod_facetoface\signup\state\booked::get_code(),
            'waitlisted' => \mod_facetoface\signup\state\waitlisted::get_code(),
            'fid' => $seminarid,
            'waitlisted2' => \mod_facetoface\signup\state\waitlisted::get_code()
        );

        return $DB->get_records_sql($signupsql, $signupparams);
    }

    /**
     * Get custom formats for user.
     *
     * @param array $usercustomfields
     * @return array
     */
    private static function get_user_customformats(array $usercustomfields) : array {
        global $DB;

        list($cf_sql, $cf_param) = $DB->get_in_or_equal($usercustomfields);
        $sql = "SELECT " . $DB->sql_concat("'customfield_'", 'shortname') . " AS shortname
                FROM {user_info_field}
                WHERE shortname {$cf_sql}
                AND datatype = 'datetime'";

        return $DB->get_records_sql($sql, $cf_param);
    }

    /**
     * Get session sign-ups.
     *
     * @param seminar $seminar
     * @param array $usercustomfields
     * @return array
     */
    private static function get_sessionsignups(seminar $seminar, array $usercustomfields) : array {
        global $CFG;

        $sessionsignups = [];
        $signups = static::get_signups($seminar->get_id());
        if ($signups) {
            foreach ($signups as $signup) {
                $userid = $signup->id;

                if (!empty($CFG->facetoface_export_customprofilefields)) {
                    $customuserfields = static::get_user_customfields(
                        $userid,
                        array_map('trim', $usercustomfields)
                    );
                    foreach ($customuserfields as $fieldname => $value) {
                        if (!isset($signup->$fieldname)) {
                            $signup->$fieldname = $value;
                        }
                    }
                }

                $sessionsignups[$signup->sessionid][$signup->id] = $signup;
            }
        }

        return $sessionsignups;
    }

    /**
     * Get sessions and session dates for seminar.
     *
     * @param int $seminarid
     * @return array
     */
    private static function get_sessions(int $seminarid) : array {
        global $DB;

        $sql = "SELECT d.id as dateid, s.id, s.capacity, d.timestart, d.timefinish, d.sessiontimezone, s.cancelledstatus,
                   s.registrationtimestart, s.registrationtimefinish
              FROM {facetoface_sessions} s
              JOIN {facetoface_sessions_dates} d ON s.id = d.sessionid
             WHERE s.facetoface = :fid
          ORDER BY d.timestart";

        return $DB->get_records_sql($sql, ['fid' => $seminarid]);
    }

    /**
     * Process each session signup and write to worksheet.
     *
     * @param \MoodleExcelWorksheet|\MoodleODSWorksheet $worksheet
     * @param array $sessionsignups
     * @param \stdClass $session
     * @param int|\MoodleExcelFormat $dateformat
     * @param \context_course $coursecontext
     * @param \stdClass $fields
     * @param int $i
     */
    private static function write_signups($worksheet, array $sessionsignups, \stdClass $session, $dateformat,
                                          \context_course $coursecontext, \stdClass $fields, int &$i) : void {
        foreach ($sessionsignups[$session->id] as $attendee) {
            $i++;
            $j = 0;

            static::write_customfields($worksheet, $session, $fields->customsessionfields, $i, $j);
            static::write_dates($worksheet, $session->sessiondates, $dateformat, $session->status, $i,$j);
            $worksheet->write_string($i, $j++, $session->roomstring);
            $worksheet->write_string($i, $j++, $session->sessiondates->starttime);
            $worksheet->write_string($i, $j++, $session->sessiondates->finishtime);
            $worksheet->write_string($i, $j++, session_time::format_duration($session->timestart, $session->timefinish));
            $worksheet->write_string($i, $j++, $session->status);
            static::write_trainerroles($worksheet, $coursecontext, $session, $i, $j);
            static::write_userfields($worksheet, $fields, $attendee, $dateformat, $coursecontext, $i, $j);
            static::write_jobassignments($worksheet, $sessionsignups, $attendee, $i, $j);
            $worksheet->write_string($i,$j++,$attendee->grade);
            static::write_signupdate($worksheet, $attendee, $dateformat, $i, $j);
            if (!empty($coursename)) {
                $worksheet->write_string($i, $j++, $coursename);
            }
            if (!empty($activityname)) {
                $worksheet->write_string($i, $j++, $activityname);
            }
        }
    }

    /**
     * No one is signed-up, so let's just print the basic info.
     *
     * @param \MoodleExcelWorksheet|\MoodleODSWorksheet $worksheet
     * @param \stdClass $session
     * @param int|\MoodleExcelFormat $dateformat
     * @param \context_course $coursecontext
     * @param \stdClass $fields
     * @param int $i
     */
    private static function write_nosignups($worksheet, \stdClass $session, $dateformat, \context_course $coursecontext,
                                            \stdClass $fields, int &$i) : void {
        $i++;
        $j = 0;

        static::write_customfields($worksheet, $session, $fields->customsessionfields, $i, $j);
        static::write_dates($worksheet, $session->sessiondates, $dateformat, $session->status, $i,$j);
        $worksheet->write_string($i, $j++, $session->roomstring);
        $worksheet->write_string($i, $j++, $session->sessiondates->starttime);
        $worksheet->write_string($i, $j++, $session->sessiondates->finishtime);
        $worksheet->write_string($i, $j++, session_time::format_duration($session->timestart, $session->timefinish));
        $worksheet->write_string($i, $j++, $session->status);
        static::write_trainerroles($worksheet, $coursecontext, $session, $i, $j);

        foreach ($fields->userfields as $unused) {
            $worksheet->write_string($i,$j++,'-');
        }
        // Grade/attendance
        $worksheet->write_string($i,$j++,'-');
        // Date signed up
        $worksheet->write_string($i,$j++,'-');

        if (!empty($coursename)) {
            $worksheet->write_string($i, $j++, $coursename);
        }
        if (!empty($activityname)) {
            $worksheet->write_string($i, $j++, $activityname);
        }
    }

    /**
     * Get status of session.
     *
     * @param \stdClass $session
     * @param int $timenow
     * @return string
     */
    private static function get_status(\stdClass $session, int $timenow) : string {
        // TODO: use \mod_facetoface\seminar_event_helper::booking_status();
        if ($session->timestart < $timenow) {
            $status = get_string('sessionover', 'facetoface');
        } else {
            $signupcount = 0;
            if (!empty($sessionsignups[$session->id])) {
                $signupcount = count($sessionsignups[$session->id]);
            }

            // Before making any status changes, check mod_facetoface_renderer::session_status_table_cell first.
            if (!empty($session->cancelledstatus)) {
                $status = get_string('bookingsessioncancelled', 'facetoface');
            } else if ($signupcount >= $session->capacity) {
                $status = get_string('bookingfull', 'facetoface');
            } else if (!empty($session->registrationtimestart) && $session->registrationtimestart > $timenow) {
                $status = get_string('registrationnotopen', 'facetoface');
            } else if (!empty($session->registrationtimefinish) && $timenow > $session->registrationtimefinish) {
                $status = get_string('registrationclosed', 'facetoface');
            } else {
                $status = get_string('bookingopen', 'facetoface');
            }
        }

        return $status;
    }

    /**
     * Get session dates.
     *
     * @param \stdClass $session
     * @param \MoodleExcelWorksheet|\MoodleODSWorksheet $worksheet
     * @param bool $displaytimezones
     * @return \stdClass
     */
    private static function get_sessiondates(\stdClass $session, $worksheet, bool $displaytimezones) : \stdClass {
        $sessiondates = new \stdClass();
        $sessiondates->sessionstartdate = false;
        $sessiondates->sessionenddate = false;
        $sessiondates->starttime   = get_string('wait-listed', 'facetoface');
        $sessiondates->finishtime  = get_string('wait-listed', 'facetoface');
        $sessiondates->status      = get_string('wait-listed', 'facetoface');

        if ($session->timestart) {
            // Display only the first date
            $sessionobj = \mod_facetoface\output\session_time::format(
                $session->timestart,
                $session->timefinish,
                $session->sessiontimezone
            );
            $sessiondates->sessiontimezone = !empty($displaytimezones) ? $sessionobj->timezone : '';
            $sessiondates->starttime = $sessionobj->starttime . ' ' . $sessiondates->sessiontimezone;
            $sessiondates->finishtime = $sessionobj->endtime . ' ' . $sessiondates->sessiontimezone;

            if (method_exists($worksheet, 'write_date')) {
                // Needs the patch in MDL-20781
                $sessiondates->sessionstartdate = (int)$session->timestart;
                $sessiondates->sessionenddate = (int)$session->timefinish;
            } else {
                $sessiondates->sessionstartdate = $sessionobj->startdate;
                $sessiondates->sessionenddate = $sessionobj->enddate;
            }
        }
        return $sessiondates;
    }

    /**
     * Get room string if room exists.
     *
     * @param \stdClass $session
     * @return string
     */
    private static function get_roomstring(\stdClass $session) : string {
        $room = \mod_facetoface\room::seek($session->roomid);
        $roomstring = '';
        if ($room->exists()) {
            $roomstring = (string)$room;
        }
        return $roomstring;
    }

    /**
     * Get fields for worksheet.
     *
     * @param array $usercustomfields
     * @return \stdClass
     */
    private static function get_fields(array $usercustomfields) : \stdClass {
        $fields = new \stdClass();

        // The user fields we fetch need to be broken down into those coming from the user table
        // and those coming from custom fields so that we can validate them correctly.
        $fields->userfields = facetoface_get_userfields();

        $fields->customsessionfields = customfield_get_fields_definition('facetoface_session', array('hidden' => 0));
        $fields->customfieldshortnames = array_filter(array_keys($fields->userfields), function ($value) {
            return strpos($value, 'customfield_') === 0;
        });
        $fields->usertablefields = array_diff(array_keys($fields->userfields), $fields->customfieldshortnames);

        $datefields = array('firstaccess', 'lastaccess', 'lastlogin', 'currentlogin');
        $usercustomformats = static::get_user_customformats($usercustomfields);

        $fields->datefields = array_merge($datefields, array_keys($usercustomformats));

        return $fields;
    }

    /**
     * Write custom fields to work sheet.
     *
     * @param \MoodleExcelWorksheet|\MoodleODSWorksheet $worksheet
     * @param \stdClass $session
     * @param array $customsessionfields
     * @param int $i
     * @param int $j
     */
    private static function write_customfields($worksheet, \stdClass $session, array $customsessionfields, int $i, int &$j) : void {
        $customfieldsdata = customfield_get_data($session, 'facetoface_session', 'facetofacesession', false);
        foreach ($customsessionfields as $customfield) {
            if (empty($customfield->showinsummary)) {
                continue;
            }
            if (array_key_exists($customfield->shortname, $customfieldsdata)) {
                $data = format_string($customfieldsdata[$customfield->shortname]);
            } else {
                $data = '-';
            }
            $worksheet->write_string($i, $j++, $data);
        }
    }

    /**
     * Write dates to work sheet.
     *
     * @param \MoodleExcelWorksheet|\MoodleODSWorksheet $worksheet
     * @param \stdClass $sessiondates
     * @param int|\MoodleExcelFormat $dateformat
     * @param string $status
     * @param int $i
     * @param int $j
     */
    private static function write_dates($worksheet, \stdClass $sessiondates, $dateformat, string $status, int $i, int &$j) : void {
        if (empty($sessiondates->sessionstartdate)) {
            $worksheet->write_string($i, $j++, $status); // Session start date.
            $worksheet->write_string($i, $j++, $status); // Session end date.
        } else {
            if (method_exists($worksheet, 'write_date')) {
                $worksheet->write_date($i, $j++, $sessiondates->sessionstartdate, $dateformat);
                $worksheet->write_date($i, $j++, $sessiondates->sessionenddate, $dateformat);
            } else {
                $worksheet->write_string($i, $j++, $sessiondates->sessionstartdate);
                $worksheet->write_string($i, $j++, $sessiondates->sessionenddate);
            }
        }
    }

    /**
     * Write trainer roles to work sheet.
     *
     * @param \MoodleExcelWorksheet|\MoodleODSWorksheet $worksheet
     * @param \context_course $coursecontext
     * @param \stdClass $session
     * @param int $i
     * @param int $j
     */
    private static function write_trainerroles($worksheet, \context_course $coursecontext, \stdClass $session,
                                               int $i, int &$j) : void {
        $trainerroles = trainer_helper::get_trainer_roles($coursecontext);
        if ($trainerroles) {
            $seminarevent = new seminar_event($session->id);
            $trainerhelper = new trainer_helper($seminarevent);
            $sessiontrainers = $trainerhelper->get_trainers();
            foreach (array_keys($trainerroles) as $roleid) {
                if (!empty($sessiontrainers[$roleid])) {
                    $trainers = array();
                    foreach ($sessiontrainers[$roleid] as $trainer) {
                        $trainers[] = fullname($trainer);
                    }

                    $trainers = implode(', ', $trainers);
                } else {
                    $trainers = '-';
                }

                $worksheet->write_string($i, $j++, $trainers);
            }
        }
    }

    /**
     * Write user fields to work sheet.
     *
     * @param \MoodleExcelWorksheet|\MoodleODSWorksheet $worksheet
     * @param \stdClass $fields
     * @param \stdClass $attendee
     * @param int|\MoodleExcelFormat $dateformat
     * @param \context_course $coursecontext
     * @param int $i
     * @param int $j
     */
    private static function write_userfields($worksheet, \stdClass $fields, \stdClass $attendee, $dateformat,
                                             \context_course $coursecontext, int $i, int &$j) : void {
        $course = get_course($coursecontext->instanceid);

        // Filter out the attendee's information that the exporting user is not
        // allowed to see, based on permissions and config settings.
        // Other properties of $attendee will be used later, but this determines
        // which $userfields we'll show.
        $user = user_get_user_details($attendee, $course, $fields->usertablefields);

        foreach ($fields->userfields as $shortname => $fullname) {
            $value = '-';
            if (!empty($user[$shortname])) {
                $value = $user[$shortname];
            } else if (in_array($shortname, $fields->customfieldshortnames) && !empty($attendee->{$shortname})) {
                $value = $attendee->{$shortname};
            }

            if (in_array($shortname, $fields->datefields)) {
                if (method_exists($worksheet, 'write_date')) {
                    $worksheet->write_date($i, $j++, (int)$value, $dateformat);
                } else {
                    $worksheet->write_string($i, $j++, userdate($value, get_string('strftimedate', 'langconfig')));
                }
            } else {
                $worksheet->write_string($i,$j++,$value);
            }
        }
    }

    /**
     * Write job assignments to work sheet.
     *
     * @param \MoodleExcelWorksheet|\MoodleODSWorksheet $worksheet
     * @param array $sessionsignups
     * @param \stdClass $attendee
     * @param int $i
     * @param int $j
     */
    private static function write_jobassignments($worksheet, array $sessionsignups, \stdClass $attendee, int $i, int &$j) : void {
        $selectjobassignmentonsignupglobal = get_config(null, 'facetoface_selectjobassignmentonsignupglobal');
        $selectjobassignmentonsignupsession = $sessionsignups[$attendee->sessionid][$attendee->id]->selectjobassignmentonsignup;
        if (!empty($selectjobassignmentonsignupglobal) && !empty($selectjobassignmentonsignupsession)) {
            if (!empty($attendee->jobassignmentid)) {
                $jobassignment = \totara_job\job_assignment::get_with_id($attendee->jobassignmentid, false);
                if ($jobassignment == null || $jobassignment->userid != $attendee->id) {
                    $label = '';
                } else {
                    $label = \position::job_position_label($jobassignment);
                }
            } else {
                $label = '';
            }
            $worksheet->write_string($i, $j++, $label);
        }
    }

    /**
     * Write signup date to work sheet.
     *
     * @param \MoodleExcelWorksheet|\MoodleODSWorksheet $worksheet
     * @param \stdClass $attendee
     * @param int|\MoodleExcelFormat $dateformat
     * @param int $i
     * @param int $j
     */
    private static function write_signupdate($worksheet, \stdClass $attendee, $dateformat, int $i, int &$j) : void {
        if (method_exists($worksheet,'write_date')) {
            $worksheet->write_date($i, $j++, (int)$attendee->timecreated, $dateformat);
        } else {
            $signupdate = userdate($attendee->timecreated, get_string('strftimedatetime', 'langconfig'));
            if (empty($signupdate)) {
                $signupdate = '-';
            }
            $worksheet->write_string($i,$j++, $signupdate);
        }
    }
}