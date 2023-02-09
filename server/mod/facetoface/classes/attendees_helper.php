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

use mod_facetoface\signup\state\{
    not_set,
    requestedrole,
    state,
    booked,
    waitlisted,
    declined,
    event_cancelled,
    user_cancelled,
    requestedadmin,
    requested,
    attendance_state
};

use mod_facetoface\attendance\event_attendee;

defined('MOODLE_INTERNAL') || die();


/**
 * The only difference that this class and the class \mod_facetoface\attendance\attendance_helper is that
 * attendance_helper class is meant to be retrieving the attendees of an event with specific set of attendance states.
 * Furthermore, attendance_helper will only produce a event_attendee with a bit fewer properties than this class.
 *
 * They are both producing event_attendee object, however, attendance_helper is able to load the session attendance as
 * well. Whereas this class is being introduced to replace facetoface_get_attendees() which the old function is being
 * used to get a full list of attendees (even including the free reservation spaces).
 *
 * All the code and states within signup are defined as bellow:
 *
 * @example
 *         Array(
 *              [0] => \mod_facetoface\signup\state\not_set
 *              [10] => \mod_facetoface\signup\state\user_cancelled
 *              [20] => \mod_facetoface\signup\state\event_cancelled
 *              [30] => \mod_facetoface\signup\state\declined
 *              [40] => \mod_facetoface\signup\state\requested
 *              [44] => \mod_facetoface\signup\state\requestedrole
 *              [45] => \mod_facetoface\signup\state\requestedadmin
 *              [60] => \mod_facetoface\signup\state\waitlisted
 *              [70] => \mod_facetoface\signup\state\booked
 *              [80] => \mod_facetoface\signup\state\no_show
 *              [85] => \mod_facetoface\signup\state\unable_to_attend
 *              [90] => \mod_facetoface\signup\state\partially_attended
 *              [100] => \mod_facetoface\signup\state\fully_attended
 *         )
 */
final class attendees_helper {
    /**
     * @var seminar_event
     */
    private $seminarevent;

    /**
     * event_attendee_helper constructor.
     * @param seminar_event $seminarevent
     */
    public function __construct(seminar_event $seminarevent) {
        $this->seminarevent = $seminarevent;
    }

    /**
     * Given the array of statuscodes, then this function will count the number of attendees that have those status
     * codes associated with. This functionality will not include any reservations, as that would be not a good idea
     * to be in same place here. However, we have another API for it, and developers should be using it.
     *
     * @param integer[] $statuscodes
     * @param bool $includedeleted include deleted users
     * @param bool $includereserved include reserved spaces
     *
     * @return int
     */
    public function count_attendees_with_codes(array $statuscodes, bool $includedeleted = true, bool $includereserved = false): int {
        global $DB;

        if (0 === $this->seminarevent->get_id()) {
            debugging("Cannot count the number of attendees for invalid seminar event", DEBUG_DEVELOPER);
            return 0;
        }

        [$asql, $params] = $DB->get_in_or_equal($statuscodes, SQL_PARAMS_NAMED);
        $userjoin = $includereserved ? 'LEFT' : 'INNER';
        $sql = "
            SELECT COUNT(su.id)
            FROM {facetoface_signups} su
            INNER JOIN {facetoface_signups_status} ss ON su.id = ss.signupid
            {$userjoin} JOIN {user} u ON u.id = su.userid
            WHERE su.sessionid = :sessionid
            AND ss.superceded = 0
            AND ss.statuscode {$asql}
        ";

        if (!$includedeleted) {
            $sql .= $includereserved ? " AND (u.id IS NULL OR u.deleted = 0)" : " AND u.deleted = 0";
        }

        $params['sessionid'] = $this->seminarevent->get_id();
        return (int) $DB->count_records_sql($sql, $params);
    }

    /**
     * Counting the number of reservations within an event. This will return the number of spaces that are reserved
     * but available for user to be put it.
     *
     * @return int
     * @deprecated since Totara 13
     */
    public function count_reserved_spaces(): int {
        debugging(__METHOD__ . '() is deprecated. Please use a method of ' . reservations::class . ' instead.', DEBUG_DEVELOPER);

        global $DB;

        if (0 === $this->seminarevent->get_id()) {
            debugging('Cannot count the number of reservations for invalid seminar event', DEBUG_DEVELOPER);
            return 0;
        }

        // For reservation, there will be no userid in the rows, so we just specified userid to be zero.
        $sql = "
            SELECT COUNT(su.id)
            FROM {facetoface_signups} su
            INNER JOIN {facetoface_signups_status} ss ON su.id = ss.signupid
            WHERE su.sessionid = :sessionid
            AND su.userid = 0
            AND ss.superceded = 0
            AND su.archived = 0;
        ";

        $params = ['sessionid' => $this->seminarevent->get_id()];
        return (int) $DB->count_records_sql($sql, $params);
    }

    /**
     * Returns the number of sign-up users and reserved spaces.
     *
     * @param seminar_event $seminarevent
     * @param boolean $includedeleted
     * @return integer
     */
    public static function count_signups(seminar_event $seminarevent, bool $includedeleted = true): int {
        $helper = new static($seminarevent);

        // TODO: find out why the logic is different from reservations/allocate.php and reservations/reserve.php
        if ($seminarevent->get_cancelledstatus()) {
            $statuscodes = [event_cancelled::get_code()];
        } else if ($seminarevent->is_sessions()) {
            $statuscodes = attendance_state::get_all_attendance_code_with([booked::class]);
        } else {
            $statuscodes = [waitlisted::get_code()];
        }

        // Need to include reserved spaces here. If there is any.
        $signupcount = $helper->count_attendees_with_codes($statuscodes, $includedeleted, true);
        return $signupcount;
    }

    /**
     * Given the list of status codes, this method will try to get the list of all attendees that had the status code
     * associated with.
     *
     * Returning an array of event_attendee object, with the user's id associated as key.
     *
     * @param int[] $statuscodes
     * @param bool  $includedeleted
     *
     * @return (event_attendee|\stdClass)[]
     */
    public function get_attendees_with_codes(array $statuscodes, bool $includedeleted = true): array {
        global $DB;

        if (0 === $this->seminarevent->get_id()) {
            debugging("Cannot get list of attendees for invalid seminar event", DEBUG_DEVELOPER);
            return [];
        }

        [$asql, $params] = $DB->get_in_or_equal($statuscodes, SQL_PARAMS_NAMED);
        $usernamefields = get_all_user_name_fields(true, 'u');

        $sql = "
            SELECT u.id,
            u.username,
            u.idnumber,
            {$usernamefields},
            u.email,
            u.deleted,
            u.suspended,
            su.archived,
            su.jobassignmentid,
            su.bookedby,
            su.id AS submissionid,
            s.id AS sessionid,
            f.id AS facetofaceid,
            f.course AS course,
            ss.statuscode,
            ss.grade,
            ss.timecreated,
            (
                SELECT MAX(ss2.timecreated)
                FROM {facetoface_signups_status} ss2
                WHERE ss2.signupid = ss.signupid AND ss2.statuscode IN (:status1, :status2)
            ) AS timesignedup
            FROM {facetoface} f
            INNER JOIN {facetoface_sessions} s
            ON s.facetoface = f.id
            INNER JOIN {facetoface_signups} su
            ON su.sessionid = s.id
            INNER JOIN {user} u
            ON u.id = su.userid
            INNER JOIN {facetoface_signups_status} ss
            ON ss.signupid = su.id
            WHERE ss.superceded = 0
            AND s.id = :sessionid
            AND ss.statuscode {$asql}
        ";

        if (!$includedeleted) {
            // If it is not including the deleted user, then we should exclude them out of the query here.
            $sql .= " AND u.deleted <> 1";
        }

        $params['sessionid'] = $this->seminarevent->get_id();

        // By default, the time of signed up is only count when user has these two of specific state within an event.
        $params['status1'] = booked::get_code();
        $params['status2'] = waitlisted::get_code();

        $records = $DB->get_records_sql($sql, $params);
        $attendees = [];

        foreach ($records as $record) {
            $eventattendee = new event_attendee();
            $eventattendee->from_record($record);

            $attendees[$eventattendee->id] = $eventattendee;
        }

        return $attendees;
    }

    /**
     * Returning a list of attendee or not set attendee which had been reserved by their manager or admin. This function
     * is also returning those reserved space that had been taken with a proper attendee.
     *
     * @return event_attendee[]
     */
    public function get_reservations(): array {
        global $DB;

        if (0 === $this->seminarevent->get_id()) {
            debugging("Cannot get list of reservations for invalid seminar event", DEBUG_DEVELOPER);
            return [];
        }

        $usernamefields = get_all_user_name_fields(true, 'u');
        $sql = "
            SELECT u.id,
            u.idnumber,
            u.username,
            {$usernamefields},
            u.email,
            u.deleted,
            u.suspended,
            su.jobassignmentid,
            su.bookedby,
            su.id AS submissionid,
            s.id AS sessionid,
            f.id AS facetofaceid,
            f.course AS course,
            ss.statuscode,
            ss.grade,
            ss.timecreated,
            (
                SELECT MAX(ss2.timecreated)
                FROM {facetoface_signups_status} ss2
                WHERE ss2.signupid = ss.signupid AND ss2.statuscode IN (:status1, :status2)
            ) AS timesignedup
            FROM {facetoface} f
            INNER JOIN {facetoface_sessions} s
            ON s.facetoface = f.id
            INNER JOIN {facetoface_signups} su
            ON su.sessionid = s.id
            LEFT JOIN {user} u
            ON u.id = su.userid
            LEFT JOIN {facetoface_signups_status} ss
            ON ss.signupid = su.id AND ss.superceded = 0
            WHERE s.id = :sessionid
        ";

        $params = [
            'status1' => booked::get_code(),
            'status2' => waitlisted::get_code(),
            'sessionid' => $this->seminarevent->get_id()
        ];

        $records = $DB->get_recordset_sql($sql, $params);

        $attendees = [];
        foreach ($records as $record) {
            $attendee = new event_attendee();
            $attendee->from_record($record);
            $attendees[] = $attendee;
        }

        $records->close();
        return $attendees;
    }

    /**
     * Getting the list of attendees that are waiting for approval.
     *
     * Users that are in requested state should not be deleted at all. Because, once the user is deleted, their state
     * should be moved to cancelled. However, for the past event, user might not be able to switch to different state.
     * Therefore, by default, we should leave it to exclude the deleted users.
     *
     * @param bool $includedeleted
     * @return event_attendee[] | \stdClass[]
     */
    public function get_attendees_in_requested(bool $includedeleted = false): array {
        $statuscodes = [
            requested::get_code(),
            requestedrole::get_code()
        ];

        return $this->get_attendees_with_codes($statuscodes, $includedeleted);
    }

    /**
     * Similar with get_attendees_in_requested(), except that this one is for 2 stage requested in:
     * + stage one: pending manager approval
     * + stage two: pending admin approval
     *
     * @param bool $includedeleted
     * @return event_attendee[] | \stdClass[]
     */
    public function get_attendees_in_admin_requested(bool $includedeleted = false): array {
        $statuscodes = [
            requested::get_code(),
            requestedrole::get_code(),
            requestedadmin::get_code()
        ];

        return $this->get_attendees_with_codes($statuscodes, $includedeleted);
    }

    /**
     * Returning the list of attendees that are in any kind of cancellation status.
     *
     * @param bool $includedeleted
     * @return event_attendee[] | \stdClass[]
     */
    public function get_attendees_in_cancellation(bool $includedeleted = true): array {
        $statuscodes = [
            user_cancelled::get_code(),
            event_cancelled::get_code()
        ];

        return $this->get_attendees_with_codes($statuscodes, $includedeleted);
    }

    /**
     * Only counting the user that has booked to the specific event. Furthermore, by default this will include the
     * list of status codes of attendance_state.
     *
     * @param bool $includedeleted
     * @return int
     */
    public function count_attendees(bool $includedeleted = true): int {
        $statuscodes = attendance_state::get_all_attendance_code_with([booked::class]);
        return $this->count_attendees_with_codes($statuscodes, $includedeleted);
    }

    /**
     * Get a list of status codes depending from booked state.
     * @param bool $allbooked
     * @return array
     */
    public static function get_status(bool $allbooked = false): array {

        $statecodes = \mod_facetoface\signup\state\attendance_state::get_all_attendance_code_with([ not_set::class ]);
        if ($allbooked) {
            // Look for the status of attendance states.
            $statecodes[] = booked::get_code();
        }
        $statusoptions = [];
        $states = state::get_all_states();
        foreach ($states as $state) {
            $key = $state::get_code();
            if (in_array($key, $statecodes)) {
                $statusoptions[$key] = $state::get_string();
            }
        }
        return array_reverse($statusoptions, true);
    }

    /**
     * Prepare exit as session id is missed.
     *
     * @param string $page
     * @return void
     */
    public static function process_no_sessionid(string $page = 'view'): void {
        global $PAGE, $OUTPUT;

        require_login();

        $syscontext = \context_system::instance();
        if (!has_capability('mod/facetoface:viewallsessions', $syscontext)) {
            // They can't view the sessionreport, essentially this makes s a required param.
            // As its not been set, throw the same error required_param would.
            print_error('missingparam', '', '', 's');
        }

        $PAGE->set_context($syscontext);
        $PAGE->set_url("/mod/facetoface/attendees/{$page}.php");

        echo $OUTPUT->header();
        $url = new \moodle_url('/mod/facetoface/reports/events.php');
        echo $OUTPUT->heading(get_string('selectaneventheading', 'rb_source_facetoface_sessions'));
        echo \html_writer::tag('p', \html_writer::link($url, get_string('selectanevent', 'rb_source_facetoface_sessions')));
        echo $OUTPUT->footer();
    }

    /**
     * Process JavaScript.
     *
     * @param string $action
     * @param seminar $seminar
     * @param seminar_event $seminar_event
     * @return void
     */
    public static function process_js(string $action, seminar $seminar, seminar_event $seminar_event): void {
        global $PAGE;

        local_js(
            array(
                TOTARA_JS_DIALOG,
                TOTARA_JS_TREEVIEW
            )
        );

        $PAGE->requires->string_for_js('save', 'admin');
        $PAGE->requires->string_for_js('cancel', 'moodle');
        $PAGE->requires->strings_for_js(
            array('uploadfile', 'addremoveattendees', 'approvalreqd', 'areyousureconfirmwaitlist',
                'addattendeesviaidlist', 'submitcsvtext', 'bulkaddattendeesresults', 'addattendeesviafileupload',
                'bulkaddattendeesresults', 'wait-list', 'cancellations', 'approvalreqd', 'takeattendance',
                'updateattendeessuccessful', 'updateattendeesunsuccessful', 'waitlistselectoneormoreusers',
                'confirmlotteryheader', 'confirmlotterybody', 'error:lotterynocapacity', 'updatewaitlist', 'close'
            ),
            'facetoface'
        );

        $json_action = json_encode($action);

        $jsmodule = array(
            'name' => 'totara_f2f_attendees',
            'fullpath' => '/mod/facetoface/js/attendees.js',
            'requires' => array('json', 'totara_core')
        );

        $args = array('args' => '{"sessionid":'.$seminar_event->get_id().','.
            '"action":'.$json_action.','.
            '"sesskey":"'.sesskey().'",'.
            '"selectall":'.MDL_F2F_SELECT_ALL.','.
            '"selectnone":'.MDL_F2F_SELECT_NONE.','.
            '"selectset":"'.MDL_F2F_SELECT_SET.'",'.
            '"selectnotset":"'.MDL_F2F_SELECT_NOT_SET.'",'.
            '"courseid":"'.$seminar->get_course().'",'.
            '"facetofaceid":"'.$seminar->get_id().'",'.
            '"notsetop":"'.\mod_facetoface\signup\state\not_set::get_code().'",'.
            '"approvalreqd":"'.$seminar->is_approval_required().'"}'
        );

        $PAGE->requires->js_init_call('M.totara_f2f_attendees.init', $args, false, $jsmodule);
    }

    /**
     * Get allowed actions are actions the user has permissions to do
     * Get available actions are actions that have a point.
     * e.g. view the cancellations page when there are no cancellations is not an "available" action,
     * but it maybe be an "allowed" action
     *
     * @param seminar $seminar
     * @param seminar_event $seminarevent
     * @param \context $context
     * @param null $session - deprecated, do not use
     * @return array
     */
    public static function get_allowed_available_actions(\mod_facetoface\seminar $seminar,
        \mod_facetoface\seminar_event $seminarevent, \context $context, $session = null): array {
        global $USER, $DB, $CFG;
        /**
         * Capability checks to see if the current user can view this page
         *
         * This page is a bit of a special case in this respect as there are four uses for this page.
         *
         * 1) Viewing attendee list
         *   - Requires mod/facetoface:viewattendees capability in the course
         *
         * 2) Viewing cancellation list
         *   - Requires mod/facetoface:viewcancellations capability in the course
         *
         * 3) Taking attendance
         *   - Requires mod/facetoface:takeattendance capabilities in the course
         *
         * 4) A manager approving his staff's booking requests
         *   - Manager does not neccesarily have any capabilities in this course
         *   - Show only attendees who are also the manager's staff
         *   - Show only staff awaiting approval
         *   - Show any staff who have cancelled
         *   - Shouldn't throw an error if there are previously declined attendees
         *
         * 5) A user with the specified role in the session to approve the pending requests
         *  - The user with the role does not neccesarily have any capabilities in this course
         *  - Show all users with pending requests for the session
         *  - Do not show any other tabs
         *
         * 6) A sitewide or actitivity level Approver
         *  - The approver does not neccesarily have any capabilities in this course
         *  - Show all users with pending requests for the session
         *  - Do not show any other tabs
         */

        $allowed_actions = [];
        $available_actions = [];

        $helper = new static($seminarevent);

        // Actions the user can perform
        $has_attendees = $helper->count_attendees();
        $sendmessagecapability = has_all_capabilities(
            [
                'moodle/site:sendmessage',
                'moodle/course:bulkmessaging',
                'mod/facetoface:viewattendees'
            ],
            $context
        );
        $includedeleted = has_capability('totara/core:seedeletedusers', $context);

        if (has_capability('mod/facetoface:viewallsessions', $context)) {
            $allowed_actions[] = 'event';
            $available_actions[] = 'event';
        }

        if (has_capability('mod/facetoface:viewattendees', $context)) {
            $allowed_actions[] = 'attendees';
            $allowed_actions[] = 'waitlist';

            if (empty($seminarevent->get_cancelledstatus())) {
                $available_actions[] = 'attendees';
            }

            $users = $helper->count_attendees_with_codes([waitlisted::get_code()], true, true);
            if (!empty($users)) {
                $available_actions[] = 'waitlist';
            }
        }

        if (has_capability('mod/facetoface:viewcancellations', $context)) {
            $allowed_actions[] = 'cancellations';

            $users = $helper->get_attendees_with_codes(
                [
                    declined::get_code(),
                    user_cancelled::get_code(),
                    event_cancelled::get_code()
                ],
                $includedeleted
            );

            if (!empty($seminarevent->get_cancelledstatus()) || !empty($users)) {
                $available_actions[] = 'cancellations';
            }
        }

        if (has_capability('mod/facetoface:takeattendance', $context)) {
            $allowed_actions[] = 'takeattendance';

            if ($has_attendees && $seminarevent->is_any_session_attendance_open()) {
                $available_actions[] = 'takeattendance';
            }
        }

        $attendees = array();
        $cancellations = array();
        $requests = array();

        $staff = null;
        if ($seminar->get_approvaltype() == \mod_facetoface\seminar::APPROVAL_MANAGER ||
            $seminar->get_approvaltype() == \mod_facetoface\seminar::APPROVAL_ADMIN) {
            $managersql = "1=0";
            $sqlparams = array();

            // Use job assignment API: This can fail with large amount of users managed by current user.
            $staffids = \totara_job\job_assignment::get_staff_userids($USER->id);
            if (!empty($staffids)) {
                list($staffsql, $sqlparams) = $DB->get_in_or_equal($staffids, SQL_PARAMS_NAMED);
                $managersql = "fs.userid $staffsql";
            }

            $selectjobassignmentsignupglobal = get_config(null, 'facetoface_selectjobassignmentonsignupglobal');
            if (!empty($selectjobassignmentsignupglobal) && !empty($seminar->get_selectjobassignmentonsignup())) {
                // Prioritise selecteded job assignment
                $managersql = "(selectedmanagerja.userid = :selectedmanid OR (selectedmanagerja.userid IS NULL AND $managersql))";
                $sqlparams['selectedmanid'] = $USER->id;

                if (!empty($CFG->enabletempmanagers)) {
                    $managersql = "(selectedtempmanagerja.userid = :selectedtempmanid
                            OR (selectedtempmanagerja.userid IS NULL AND $managersql))";
                    $sqlparams['selectedtempmanid'] = $USER->id;
                }
            }

            $managerselect = get_config(null, 'facetoface_managerselect');
            if ($managerselect) {
                // Prioritise selected manager.
                $managersql = "((fs.managerid = :manid) OR (fs.managerid IS NULL AND $managersql))";
                $sqlparams['manid'] = $USER->id;
            }

            // Check if the user is manager of a job assignment selected by staff signed up to this session.
            $requestssql = "SELECT DISTINCT fs.userid
                      FROM {facetoface_signups} fs
                      JOIN {facetoface_signups_status} fss
                        ON (fss.signupid = fs.id AND fss.superceded = 0)
                      LEFT JOIN {job_assignment} selectedja
                        ON fs.jobassignmentid = selectedja.id
                      LEFT JOIN {job_assignment} selectedmanagerja
                        ON selectedmanagerja.id = selectedja.managerjaid
                      LEFT JOIN {job_assignment} selectedtempmanagerja
                        ON (selectedtempmanagerja.id = selectedja.tempmanagerjaid AND selectedja.tempmanagerexpirydate > :now)
                     WHERE fs.sessionid = :sessionid
                       AND {$managersql}
                       AND fss.statuscode = :status";
            $sqlparams = array_merge($sqlparams,
                array(
                    'sessionid' => $seminarevent->get_id(),
                    'status' => \mod_facetoface\signup\state\requested::get_code(),
                    'now' => time()
                )
            );
            $staff = $DB->get_fieldset_sql($requestssql, $sqlparams);
        }

        if ($seminar->get_approvaltype() == \mod_facetoface\seminar::APPROVAL_ROLE) {
            $trainerhelper = new trainer_helper($seminarevent);
            $sessionroles = $trainerhelper->get_trainers_for_role($seminar->get_approvalrole());

            if (!empty($sessionroles) && isset($sessionroles[$USER->id])) {
                // The current user is one of the role approvers.
                $allowed_actions[] = 'approvalrequired';
                $available_actions[] = 'approvalrequired';

                // Set everyone as their staff.
                $staff = array_keys(
                    $helper->get_attendees_with_codes([requested::get_code(), requestedrole::get_code()])
                );
            }
        }

        $admin_requests = array();
        if ($seminar->get_approvaltype() == \mod_facetoface\seminar::APPROVAL_ADMIN) {
            if ($seminar->is_admin_approver($USER->id)) {
                // The current user is one of the admin approvers.
                $allowed_actions[] = 'approvalrequired';
                $available_actions[] = 'approvalrequired';
                // Set everyone in the second step as their staff.
                $requestssql = "SELECT fs.userid
                          FROM {facetoface_signups} fs
                          JOIN {facetoface_signups_status} fss
                            ON fss.signupid = fs.id AND fss.superceded = 0
                         WHERE fs.sessionid = :sessionid
                           AND (fss.statuscode = :statusadm OR fss.statuscode = :statusman)";
                $params = array(
                    'sessionid' => $seminarevent->get_id(),
                    'statusadm' => \mod_facetoface\signup\state\requestedadmin::get_code(),
                    'statusman' => \mod_facetoface\signup\state\requested::get_code()
                );
                $adminreqs = $DB->get_fieldset_sql($requestssql, $params);
                if (isset($staff)) {
                    $staff = array_merge($staff, $adminreqs); // Display both just in case they are managers & approvers.
                }
                $staff = $adminreqs;
            }
        }

        $canapproveanyrequest = has_capability('mod/facetoface:approveanyrequest', $context);
        if ($canapproveanyrequest || !empty($staff)) {
            $statuscodes = [requested::get_code(), requestedrole::get_code()];
            if ($seminar->get_approvaltype() == seminar::APPROVAL_ADMIN) {
                $statuscodes[] = requestedadmin::get_code();
            }

            // Check if any staff have requests awaiting approval.
            $get_requests = $helper->get_attendees_with_codes($statuscodes);
            if ($get_requests || !empty($admin_requests)) {
                // Calculate which requesting users are relevant to the viewer.
                $requests = ($canapproveanyrequest ? $get_requests : array_intersect_key($get_requests, array_flip($staff)));
                if ($requests) {
                    $allowed_actions[] = 'approvalrequired';
                    $available_actions[] = 'approvalrequired';
                }
            }
        }

        // Check if we are NOT already showing attendees and the user has staff.
        // If this is true then we need to show attendees but limit it to just those attendees that are also staff.
        if (!in_array('attendees', $allowed_actions) && !empty($staff)) {
            // Check if any staff are attending.
            $statuscodes = attendance_state::get_all_attendance_code_with([booked::class]);
            if ($seminarevent->get_mintimestart()) {
                $get_attendees = $helper->get_attendees_with_codes($statuscodes);
            } else {
                $statuscodes[] = waitlisted::get_code();
                $get_attendees = $helper->get_attendees_with_codes($statuscodes);
            }
            if ($get_attendees) {
                // Calculate which attendees are relevant to the viewer.
                $attendees = array_intersect_key($get_attendees, array_flip($staff));

                if ($attendees) {
                    $allowed_actions[] = 'attendees';
                    $available_actions[] = 'attendees';
                }
            }
        }

        // Check if we are NOT already showing cancellations and the user has has staff.
        // If this is true then we still need to show cancellations but limit it to just those cancellations that are also staff.
        if (!in_array('cancellations', $allowed_actions) && !empty($staff)) {
            // Check if any staff have cancelled.
            $get_cancellations = $helper->get_attendees_in_cancellation();
            if ($get_cancellations) {
                // Calculate which cancelled users are relevant to the viewer.
                $cancellations = array_intersect_key($get_cancellations, array_flip($staff));

                if ($cancellations) {
                    $allowed_actions[] = 'cancellations';
                    $available_actions[] = 'cancellations';
                }
            }
        }

        if ((in_array('attendees', $available_actions) ||
                in_array('cancellations', $available_actions) ||
                in_array('waitlist', $available_actions) ||
                in_array('takeattendance', $available_actions)) &&
            $sendmessagecapability) {
            $allowed_actions[] = 'messageusers';
            $available_actions[] = 'messageusers';
        }

        return [$allowed_actions, $available_actions, $staff, $admin_requests, $canapproveanyrequest, $cancellations, $requests,
            $attendees
        ];
    }

    /**
     * Load report builder for seminar attendees.
     *
     * @param string $shortname
     * @param array $attendancestatuses
     * @param array $extradata
     * @return \reportbuilder
     */
    public static function load_report(string $shortname, array $attendancestatuses, array $extradata = []): \reportbuilder {
        global $DB, $PAGE;

        $s = optional_param('s', 0, PARAM_INT);
        $sid = optional_param('sid', '0', PARAM_INT);
        $format = optional_param('format','',PARAM_TEXT);

        // Verify global restrictions and process report early before any output is done (required for export).
        $reportrecord = $DB->get_record('report_builder', array('shortname' => $shortname));
        $globalrestrictionset = \rb_global_restriction_set::create_from_page_parameters($reportrecord);

        $embeddata = ['sessionid' => $s, 'status' => $attendancestatuses];
        $embeddata = array_merge($embeddata, $extradata);
        $config = (new \rb_config())
            ->set_embeddata($embeddata)
            ->set_sid($sid)
            ->set_global_restriction_set($globalrestrictionset);
        if (!$report = \reportbuilder::create_embedded($shortname, $config)) {
            print_error('error:couldnotgenerateembeddedreport', 'totara_reportbuilder');
        }

        if ($format != '') {
            $report->export_data($format);
            die();
        }

        $report->include_js();
        $PAGE->set_button($report->edit_button());

        return $report;
    }

    /**
     * Print seminar customfields.
     *
     * @param seminar_event $seminarevent
     * @return void
     * @deprecated since Totara 13
     */
    public static function show_customfields(seminar_event $seminarevent): void {
        global $PAGE;

        debugging('attendees_helper::show_customfields() function has been deprecated, please use mod_facetoface_renderer::render_seminar_event()',
            DEBUG_DEVELOPER);

        /** @var mod_facetoface_renderer $seminarrenderer */
        $seminarrenderer = $PAGE->get_renderer('mod_facetoface');
        echo $seminarrenderer->render_seminar_event($seminarevent, true, false, true);
    }

    /**
     * Show message if event is overbooked.
     *
     * @param seminar_event $seminarevent
     * @param int       $status         deprecated
     * @param string        $comp           deprecated
     * @return void
     */
    public static function is_overbooked(seminar_event $seminarevent, int $status = null, string $comp = ''): void {
        global $OUTPUT;

        $items = [
            'status' => $status,
            'comp' => $comp
        ];

        // Deprecating the parameters $status, and $comp because the way we count attendees are no longer working with
        // operator like '>='. Furthermore, using operator for status code is SUPER WRONG, DO NOT ABUSE IT !!!
        foreach ($items as $item) {
            if (!empty($item)) {
                debugging(
                    "The parameter \${$item} has been deprecated, please do not use this paramater",
                    DEBUG_DEVELOPER
                );
            }
        }

        $helper = new static($seminarevent);

        // Output overbooked notifications.
        $numattendees = $helper->count_attendees();
        $overbooked = ($numattendees > $seminarevent->get_capacity());
        if ($overbooked) {
            $overbookedmessage = get_string(
                'capacityoverbookedlong',
                'mod_facetoface',
                array('current' => $numattendees, 'maximum' => $seminarevent->get_capacity())
            );
            echo $OUTPUT->notification($overbookedmessage, 'notifynotice');
        }
    }

    /**
     * Print export form.
     *
     * @param \reportbuilder $report
     * @param int $sid
     * @return void
     */
    public static function report_export_form(\reportbuilder $report, int $sid):void {
        global $PAGE, $OUTPUT;

        $renderer = $PAGE->get_renderer('totara_reportbuilder');
        $exports = [];
        $renderer->export_select($report, $sid);
        if ($exports) {
            echo $OUTPUT->container_start('actions last');
            // Action selector.
            echo \html_writer::label(get_string('attendeeactions', 'mod_facetoface'), 'menuf2f-actions', true, ['class' => 'sr-only']);
            echo \html_writer::select($exports, 'f2f-actions', '', array('' => get_string('export', 'totara_reportbuilder')));
            echo $OUTPUT->container_end();
        }
    }

    /**
     * Approve or decline attendee.
     *
     * @param \stdClass $form
     * @param seminar_event $seminarevent
     * @return \moodle_url
     */
    public static function approve_decline_user(\stdClass $form, seminar_event $seminarevent): \moodle_url {

        $baseurl = new \moodle_url('/mod/facetoface/attendees/approvalrequired.php', ['s' => $seminarevent->get_id()]);
        if (!confirm_sesskey()) {
            redirect($baseurl, get_string('confirmsesskeybad', 'error'), null, \core\notification::ERROR);
        }

        $errors = [];
        foreach ($form->requests as $uid => $value) {
            $signup = signup::create($uid, $seminarevent);

            switch ($value) {
                case 1: // Decline.
                    if ($signup->can_switch(declined::class)) {
                        $signup->switch_state(declined::class);
                        notice_sender::decline($signup);
                    } else {
                        $failures = $signup->get_failures(declined::class);
                        $errors[$uid] = current($failures);
                    }
                    break;
                case 2: // Approve.
                    if ($signup->can_switch(booked::class, waitlisted::class, requestedadmin::class)) {
                        $signup->switch_state(booked::class, waitlisted::class, requestedadmin::class);
                    } else {
                        $failures = $signup->get_failures(booked::class, waitlisted::class, requestedadmin::class);
                        $errors[$uid] = current($failures);
                    }
                    break;
                case 0:
                default:
                    continue 2;
            }
        }

        if (empty($errors)) {
            $baseurl->params(array('approved' => '1'));
        } else {
            $output = \html_writer::start_tag('ul');
            foreach ($errors as $attendeeid => $errmsg) {
                $output .= \html_writer::tag('li', $errmsg);
            }
            $output .= \html_writer::end_tag('ul');
            \core\notification::error($output);
        }
        return $baseurl;
    }
}
