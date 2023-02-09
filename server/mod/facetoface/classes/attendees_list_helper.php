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
    attendance_state,
    booked,
    waitlisted,
    user_cancelled
};
use mod_facetoface\event\attendees_updated;
use \totara_job\job_assignment;
use \context_module;
use \core\notification;

defined('MOODLE_INTERNAL') || die();

/**
 * Additional attendees list functionality.
 */
final class attendees_list_helper {

    /**
     * Add attendees to seminar event via html form.
     *
     * @param \stdClass $data submitted users to add to seminar event:
     *      - s seminar event id
     *      - listid list id
     *      - isapprovalrequired
     *      - enablecustomfields
     *      - ignoreconflicts
     *      - is_notification_active
     *      - notifyuser
     *      - notifymanager
     *      - ignoreapproval
     *      customfields optional
     */
    public static function add(\stdClass $data): void {
        global $USER, $DB, $CFG;

        $seminarevent = new seminar_event($data->s);
        $seminar = $seminarevent->get_seminar();
        $helper = new attendees_helper($seminarevent);
        $list = new bulk_list($data->listid);

        if (empty($_SESSION['f2f-bulk-results'])) {
            $_SESSION['f2f-bulk-results'] = array();
        }

        $added  = array();
        $errors = array();

        $signedupstates = attendance_state::get_all_attendance_code_with([ booked::class ]);
        if (empty($seminarevent->get_sessions())) {
            $signedupstates[] = waitlisted::get_code();
        }

        $original = $helper->get_attendees_with_codes($signedupstates);

        // Get users waiting approval to add to the "already attending" list as we do not want to add them again.
        $waitingapproval = $helper->get_attendees_in_requested();
        // Add those awaiting approval.
        foreach ($waitingapproval as $waiting) {
            if (!isset($original[$waiting->id])) {
                $original[$waiting->id] = $waiting;
            }
        }

        // Adding new attendees.
        $userlist = $list->get_user_ids();
        // Check if we need to add anyone.
        $users = attendees_list_helper::get_user_list($userlist);
        $attendeestoadd = array_diff_key($users, $original);

        // Confirm that new attendess have job assignments when required.
        if (!empty($seminar->get_forceselectjobassignment())) {
            // Current page number.
            $page   = optional_param('page', 0, PARAM_INT);
            $currenturl = new \moodle_url(
                '/mod/facetoface/attendees/list/addconfirm.php',
                [
                    's' => $seminarevent->get_id(),
                    'listid' => $list->get_list_id(),
                    'page' => $page
                ]
            );
            foreach ($attendeestoadd as $attendeetoadd) {
                $userdata = $list->get_user_data($attendeetoadd->id);
                if (empty($userdata['jobassignmentid'])) {
                    notification::error(get_string('error:nojobassignmentselectedlist', 'mod_facetoface'));
                    redirect($currenturl);
                }
            }
        }

        if (!empty($attendeestoadd)) {
            $clonefromform = serialize($data);
            $cm = $seminar->get_coursemodule();
            $context = \context_module::instance($cm->id);
            foreach ($attendeestoadd as $attendee) {

                // Prevent adding non-participants as attendees in tenant contexts via CSV.
                if ($tenanterror = self::user_in_tenant_context_validation($attendee, $context)) {
                    $errors[] = $tenanterror;
                    continue;
                }

                // Look for active enrolments here, otherwise we could get errors trying to see if the user can signup.
                $internalenrol = false;
                if (!is_enrolled($context, $attendee, '', true)) {
                    $defaultlearnerrole = $DB->get_record('role', array('id' => $CFG->learnerroleid));
                    if (!enrol_try_internal_enrol($seminar->get_course(), $attendee->id, $defaultlearnerrole->id, time())) {
                        $errors[] = [
                            'idnumber' => $attendee->idnumber,
                            'name' => fullname($attendee),
                            'result' => get_string('error:enrolmentfailed', 'mod_facetoface', fullname($attendee))
                        ];
                        continue;
                    } else {
                        // We've enrolled the attendee internally and will unenrol if signup fails, see below.
                        $internalenrol = true;
                    }
                }

                $signup = signup::create($attendee->id, $seminarevent);
                if (!empty($data->ignoreapproval)) {
                    $signup->set_skipapproval($data->ignoreapproval);
                }
                if (!empty($data->ignoreconflicts)) {
                    $signup->set_ignoreconflicts($data->ignoreconflicts);
                }
                if (empty($data->notifyuser)) {
                    $signup->set_skipusernotification();
                }
                if (empty($data->notifymanager)) {
                    $signup->set_skipmanagernotification();
                }
                if ($attendee->id != $USER->id) {
                    $signup->set_bookedby($USER->id);
                } else {
                    $signup->set_bookedby(null);
                }
                $userdata = $list->get_user_data($attendee->id);
                if (!empty($userdata['jobassignmentid'])) {
                    $signup->set_jobassignmentid($userdata['jobassignmentid']);
                } else {
                    $signup->set_jobassignmentid(null);
                }
                $signup->set_managerid(null);
                if (signup_helper::can_signup($signup)) {
                    signup_helper::signup($signup);
                    $added[] = [
                        'idnumber' => $attendee->idnumber,
                        'name' => fullname($attendee),
                        'result' => get_string('addedsuccessfully', 'mod_facetoface')
                    ];

                    // Store customfields.
                    $customdata = $list->has_user_data() ? (object)$list->get_user_data($attendee->id) : $data;
                    $customdata->id = $signup->get_id();
                    customfield_save_data($customdata, 'facetofacesignup', 'facetoface_signup');
                    // Values of multi-select are changing after edit_save_data func.
                    $data = unserialize($clonefromform);
                } else {
                    $failures = signup_helper::get_failures($signup);
                    // Unenrol attendee if he/she enrolled above.
                    if ($internalenrol) {
                        $enrol = enrol_get_plugin('manual');
                        $instance = $DB->get_record(
                            'enrol',
                            ['enrol' => 'manual', 'courseid' => $seminar->get_course(), 'roleid' => $defaultlearnerrole->id],
                            '*',
                            MUST_EXIST
                        );
                        $enrol->unenrol_user($instance, $attendee->id);
                    }
                    $errors[] = [
                        'idnumber' => $attendee->idnumber,
                        'name' => fullname($attendee),
                        'result' => current($failures)
                    ];
                }
            }
        }
        // Log that users were edited.
        if (count($added) > 0 || count($errors) > 0) {
            attendees_updated::create_from_seminar_event($seminarevent, $context)->trigger();
        }
        $_SESSION['f2f-bulk-results'][$seminarevent->get_id()] = [
            $added,
            array_merge($errors, $list->get_validation_results())
        ];
        self::set_bulk_result_notification(
            [$added, array_merge($errors, $list->get_validation_results())]
        );

        $list->clean();
    }

    /**
     * Add attendees to seminar event via file.
     *
     * @param \stdClass $formdata users to add to seminar event via file
     *      - s seminar event id
     *      - listid list id
     *      - requiredcfnames
     *      data via file
     */
    public static function add_file(\stdClass $formdata): void {
        global $DB;

        $importid = optional_param('importid', '', PARAM_INT);

        $listid = $formdata->listid;
        $requiredcfnames = $formdata->requiredcfnames;
        $seminarevent = new seminar_event($formdata->s);
        $seminar = $seminarevent->get_seminar();
        $currenturl = new \moodle_url(
            '/mod/facetoface/attendees/list/addfile.php',
            array(
                's' => $seminarevent->get_id(),
                'listid' => $listid
            )
        );
        $list = new bulk_list($listid, $currenturl, 'addfile');

        // Large files are likely to take their time and memory. Let PHP know
        // that we'll take longer, and that the process should be recycled soon
        // to free up memory.
        \core_php_time_limit::raise(0);
        @raise_memory_limit(MEMORY_EXTRA);

        $errors = array();
        $validationerrors = [];
        if (!$importid) {
            $importid = \csv_import_reader::get_new_iid('uploaduserlist');
            $cir = new \csv_import_reader($importid, 'uploaduserlist');
            $delimiter = import_helper::csv_detect_delimiter($formdata);
            if (!$delimiter) {
                $errors[] = get_string('error:delimiternotfound', 'mod_facetoface');
            } else {
                $readcount = $cir->load_csv_content($formdata->content, $formdata->encoding, $delimiter);
                if (!$readcount) {
                    $errors[] = $cir->get_error();
                }
            }
            unset($content);
        } else {
            $cir = new \csv_import_reader($listid, 'uploaduserlist');
        }

        $headers = $cir->get_columns();
        if (!$headers) {
            $errors[] = get_string('error:csvcannotparse', 'mod_facetoface');
        }

        $cir->init();

        // Get headers and id column.
        $idfield = '';
        $erridstr = '';
        if (empty($errors)) {
            // Validate user identification fields.
            foreach ($headers as $header) {
                if (in_array($header, array('idnumber', 'username', 'email'))) {
                    if ($idfield != '') {
                        $errors[] = get_string('error:csvtoomanyidfields', 'mod_facetoface');
                        break;
                    }
                    $idfield = $header;
                    switch ($idfield) {
                        case 'idnumber':
                        case 'email':
                        case 'username':
                            $erridstr = "error_{$idfield}_not_found";
                            break;
                        default:
                            print_error(get_string('error:unknownuserfield', 'mod_facetoface'));
                    }
                }
            }
            if (empty($idfield)) {
                $errors[] = get_string('error:csvnoidfields', 'mod_facetoface');
            }
        }

        // Check that all required customfields are provided.
        if (empty($errors)) {
            $notfoundcf = array_diff($requiredcfnames, $headers);
            if (!empty($notfoundcf)) {
                $errors[] = get_string('error:csvnorequiredcf', 'mod_facetoface', implode('\', \'', $notfoundcf));
            }
        }

        // Convert headers to field names required for data storing.
        if (empty($errors)) {
            $fieldnames = array();
            foreach ($headers as $header) {
                $fieldnames[] = $header;
            }
        }

        // Prepare add users information.
        if (empty($errors)) {
            $inconsistentlines = array();
            $usersnotexist = array();
            $iter = 0;
            while ($attempt = $cir->next()) {
                $iter++;

                $data = array_combine($fieldnames, $attempt);
                if (!$data) {
                    $inconsistentlines[] = $iter;
                    continue;
                }

                // Custom fields validate.
                $data['id'] = 0;
                if (count($headers) > 1) { // Custom field(s) exists.
                    // If $cfparams requires for changes, change $cfparams in attendees_add_file form too.
                    $cfparams = array('hidden' => '0', 'locked' => '0');
                    list($cferrors, $data) =
                        customfield_validation_filedata((object)$data, 'facetofacesignup', 'facetoface_signup', $cfparams);
                    if (!empty($cferrors)) {
                        $errors = array_merge($errors, $cferrors);
                        continue;
                    }
                }

                // Check that user exists.
                if ($idfield === 'idnumber') {
                    $user = $DB->get_record('user', array($idfield => $data[$idfield]));
                } else {
                    $user = $DB->get_record_select('user', "LOWER({$idfield}) = LOWER(:value)", ['value' => $data[$idfield]]);
                }
                if (!$user) {
                    $usersnotexist[] = $data[$idfield];
                    continue;
                }

                // signup_helper::can_signup() will happen in step two

                // Add job assignments info.
                if ($seminar->get_selectjobassignmentonsignup()) {
                    if (!empty($data['jobassignmentidnumber'])) {
                        try {
                            $jobassignment = job_assignment::get_with_idnumber($user->id, $data['jobassignmentidnumber'], true);
                            $data['jobassignmentid'] = $jobassignment->id;
                        } catch (\dml_missing_record_exception $e) {
                            $a = new \stdClass();
                            $a->user = fullname($user);
                            $a->idnumber = $data['jobassignmentidnumber'];
                            $validationerrors[] = [
                                'name' => $a->user,
                                'result' => get_string('error:xinvalidjaidnumber', 'mod_facetoface', $a)
                            ];
                            continue;
                        }
                    }
                }
                $addusers[$user->id] = $data;
            }

            if (!empty($inconsistentlines)) {
                $errors[] = get_string('error:csvinconsistentrows', 'mod_facetoface', implode(', ', $inconsistentlines));
            }
            foreach ($usersnotexist as $i => $item) {
                $validationerrors[] = ['name' => $item, 'result' => get_string($erridstr, 'mod_facetoface', $item)];
            }
        }
        if (!empty($errors)) {
            $errors = array_unique($errors);
            foreach ($errors as $error) {
                notification::error($error);
            }
        } else {
            if (!empty($validationerrors)) {
                $list->set_validaton_results($validationerrors);
            }
            $list->set_all_user_data($addusers);
            redirect(
                new \moodle_url(
                    '/mod/facetoface/attendees/list/addconfirm.php',
                    [
                        's' => $seminarevent->get_id(),
                        'listid' => $listid,
                        'ignoreconflicts' => $formdata->ignoreconflicts
                    ]
                )
            );
        }
    }

    /**
     * Add attendees to seminar event via textarea input.
     *
     * @param \stdClass $data submitted users to add to seminar event via textarea input
     *      - s seminar event id
     *      - listid list id
     *      - csvinput textarea input
     */
    public static function add_list(\stdClass $data): void {
        global $DB;

        $seminarevent = new seminar_event($data->s);
        $listid = $data->listid;
        $currenturl = new \moodle_url(
            '/mod/facetoface/attendees/list/addlist.php',
            array(
                's' => $seminarevent->get_id(),
                'listid' => $listid
            )
        );
        $list = new bulk_list($listid, $currenturl, 'addlist');

        // Handle data.
        $rawinput = $data->csvinput;

        // Replace commas with newlines and remove carriage returns.
        $rawinput = str_replace(array("\r\n", "\r", ","), "\n", $rawinput);

        $addusers = clean_param($rawinput, PARAM_NOTAGS);
        $addusers = explode("\n", $addusers);
        $addusers = array_map('trim', $addusers);
        $addusers = array_filter($addusers);

        // Validate list and fetch users.
        switch ($data->idfield) {
            case 'idnumber':
            case 'email':
            case 'username':
                $field = $data->idfield;
                $errstr = "error_{$field}_not_found";
                break;
            default:
                print_error(get_string('error:unknownuserfield', 'mod_facetoface'));
        }

        // Validate every user.
        $added = array();
        $notfound = array();
        $userstoadd = array();

        if (!empty($addusers)) {
            list($insql, $params) = $DB->get_in_or_equal($addusers, SQL_PARAMS_NAMED, 'f2fuser');
            if ($field === 'idnumber') {
                $availableusers = $DB->get_records_sql("SELECT * FROM {user} WHERE {$field} " . $insql, $params);
            } else {
                // Sort names in descending order to prevent ":uq_f2fuser_12" from ending up with "LOWER(:uq_f2fuser_1)2".
                $param_names = array_keys($params);
                rsort($param_names);
                $insql = preg_replace('/:(' . implode('|', array_map(function ($x) {
                    return preg_quote($x);
                }, $param_names)) . ')/', 'LOWER(:$1)', $insql);
                $availableusers = $DB->get_records_sql("SELECT * FROM {user} WHERE LOWER({$field}) " . $insql, $params);
            }
            foreach ($availableusers as $id => $user) {
                $added[] = $user->{$field};
                $userstoadd[] = $user->id;
                // signup_helper::can_signup() will happen in step two.
            }
            $notfound = array_diff($addusers, $added);
        }

        $validationerrors = [];
        foreach ($notfound as $i => $item) {
            $validationerrors[] = ['name' => $item, 'result' => get_string($errstr, 'mod_facetoface', $item)];
        }

        // Check for data.
        if (empty($addusers)) {
            \core\notification::error(get_string('error:nodatasupplied', 'mod_facetoface'));
        } else {
            if (!empty($validationerrors)) {
                $list->set_validaton_results($validationerrors);
            }
            $list->set_user_ids($userstoadd);
            $list->set_form_data($data);
            redirect(
                new \moodle_url(
                    '/mod/facetoface/attendees/list/addconfirm.php',
                    [
                        's' => $seminarevent->get_id(),
                        'listid' => $listid,
                        'ignoreconflicts' => $data->ignoreconflicts
                    ]
                )
            );
        }
    }

    /**
     * Remove attendees from seminar event.
     *
     * @param \stdClass $data submitted remove users confirmation form data
     *      - s seminar event id
     *      - listid list id
     *      - notifyuser
     *      - notifymanager
     *      customfields optional
     */
    public static function remove(\stdClass $data): void {
        global $CFG;
        require_once($CFG->dirroot . '/mod/facetoface/lib.php');

        $listid = $data->listid;
        $seminarevent = new seminar_event($data->s);
        $helper = new attendees_helper($seminarevent);
        $list = new bulk_list($listid);

        if (empty($_SESSION['f2f-bulk-results'])) {
            $_SESSION['f2f-bulk-results'] = array();
        }

        $removed  = array();
        $errors = array();

        $statuscodes = attendance_state::get_all_attendance_code_with([booked::class]);
        // Original booked attendees plus those awaiting approval
        if ($seminarevent->is_sessions()) {
            $original = $helper->get_attendees_with_codes($statuscodes);
        } else {
            $statuscodes[] = waitlisted::get_code();
            $original = $helper->get_attendees_with_codes($statuscodes);
        }

        // Get users waiting approval to add to the "already attending" list as we might want to remove them as well.
        $waitingapproval = $helper->get_attendees_in_requested();
        // Add those awaiting approval
        foreach ($waitingapproval as $waiting) {
            if (!isset($original[$waiting->id])) {
                $original[$waiting->id] = $waiting;
            }
        }

        // Removing old attendees.
        // Check if we need to remove anyone.
        $attendeestoremove = array_intersect_key($original, $data->users);
        if (!empty($attendeestoremove)) {
            $clonefromform = serialize($data);
            foreach ($attendeestoremove as $attendee) {
                $result = array();
                $result['idnumber'] = $attendee->idnumber;
                $result['name'] = fullname($attendee);

                $signup = signup::create($attendee->id, $seminarevent);
                if (signup_helper::can_user_cancel($signup)) {
                    if (empty($data->notifyuser)) {
                        $signup->set_skipusernotification();
                    }
                    if (empty($data->notifymanager)) {
                        $signup->set_skipmanagernotification();
                    }

                    signup_helper::user_cancel($signup);
                    notice_sender::signup_cancellation($signup);

                    // Store customfields.
                    $customdata = $list->has_user_data() ? (object)$list->get_user_data($attendee->id) : $data;
                    $customdata->id = $signup->get_id();
                    customfield_save_data($customdata, 'facetofacecancellation', 'facetoface_cancellation');
                    // Values of multi-select are changed after edit_save_data func.
                    $data = unserialize($clonefromform);

                    $result['result'] = get_string('removedsuccessfully', 'mod_facetoface');
                    $removed[] = $result;
                } else {
                    // Reload signup with including archived entries for more meaningful error information.
                    $signup = signup::create($attendee->id, $seminarevent, MDL_F2F_BOTH, true);
                    $failures = $signup->get_failures(user_cancelled::class);
                    $result['result'] = current($failures);
                    $errors[] = $result;
                }
            }
        }

        // Log that users were edited.
        if (count($removed) > 0 || count($errors) > 0) {
            $cm = $seminarevent->get_seminar()->get_coursemodule();
            $context = context_module::instance($cm->id);
            attendees_updated::create_from_session($seminarevent->to_record(), $context)->trigger();
        }
        $_SESSION['f2f-bulk-results'][$seminarevent->get_id()] = array($removed, $errors);

        self::set_bulk_result_notification(array($removed, $errors), 'bulkremove');

        $list->clean();
    }

    /**
     * Get a list of status codes depending from booked state.
     * @param bool $allbooked
     * @return array
     *
     * @deprecated since Totara 13
     */
    public static function get_status(bool $allbooked = false): array {

        debugging('attendees_list_helper::get_status() function has been deprecated, please use attendees_helper::get_status()',
            DEBUG_DEVELOPER);

        return attendees_helper::get_status($allbooked);
    }

    /**
     * Prepare exit as session id is missed.
     * @param string $page
     *
     * @deprecated since Totara 13
     */
    public static function process_no_sessionid(string $page = 'view'): void {
        debugging('attendees_list_helper::process_no_sessionid() function has been deprecated, please use attendees_helper::process_no_sessionid()',
            DEBUG_DEVELOPER);

        attendees_helper::process_no_sessionid($page);
    }

    /**
     * @param mixed $action
     * @param seminar $seminar
     * @param seminar_event $seminar_event
     *
     * @deprecated since Totara 13
     */
    public static function process_js($action, \mod_facetoface\seminar $seminar, \mod_facetoface\seminar_event $seminar_event): void {
        debugging('attendees_list_helper::process_js() function has been deprecated, please use attendees_helper::process_js()',
            DEBUG_DEVELOPER);

        attendees_helper::process_js($action, $seminar, $seminar_event);
    }

    /**
     * Get allowed actions are actions the user has permissions to do
     * Get available actions are actions that have a point.
     * e.g. view the cancellations page when there are no cancellations is not an "available" action,
     * but it maybe be an "allowed" action
     *
     * @param seminar $seminar
     * @param seminar_event $seminarevent
     * @param mixed $context
     * @param mixed $session
     * @return array
     *
     * @deprecated since Totara 13
     */
    public static function get_allowed_available_actions(seminar $seminar, seminar_event $seminarevent, $context, $session): array {

        debugging('attendees_list_helper::get_allowed_available_actions() function has been deprecated, please use attendees_helper::get_allowed_available_actions()',
            DEBUG_DEVELOPER);

        return \mod_facetoface\attendees_helper::get_allowed_available_actions($seminar, $seminarevent, $context, $session);
    }

    /**
     * Sets totara_set_notification message describing bulk import results
     * @param array $results
     * @param string $type
     */
    public static function set_bulk_result_notification(array $results, string $type = 'bulkadd'): void {
        $added          = $results[0];
        $errors         = $results[1];
        $result_message = '';

        // Generate messages
        if ($errors) {
            $noticetype = \core\notification::WARNING;
            $result_message .= get_string($type.'attendeeserror', 'mod_facetoface') . ' - ';
            if (count($errors) == 1 && is_string($errors[0])) {
                $result_message .= $errors[0];
            } else {
                $result_message .= get_string('xerrorsencounteredduringimport', 'mod_facetoface', count($errors));
            }
        } else if ($added) {
            $noticetype = \core\notification::SUCCESS;
            $result_message .= get_string($type.'attendeessuccess', 'mod_facetoface') . ' - ';
            if ($type == 'bulkremove') {
                $result_message .= get_string('successfullyremovedxattendees', 'mod_facetoface', count($added));
            } else {
                $result_message .= get_string('successfullyaddededitedxattendees', 'mod_facetoface', count($added));
            }
        }

        if ($result_message != '') {
            $result_message .= ' ' . \html_writer::link(
                '#',
                get_string('viewresults', 'mod_facetoface'),
                ['class' => 'f2f-import-results']
            );
            notification::add($result_message, $noticetype);
        }
    }

    /**
     * Get user list by their ids
     * @param mixed $userlist
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public static function get_user_list($userlist, int $offset = 0, int $limit = 0): array {
        global $DB;

        $usernamefields = get_all_user_name_fields(true, 'u');
        list($idsql, $params) = $DB->get_in_or_equal($userlist, SQL_PARAMS_NAMED);
        $users = $DB->get_records_sql("
                    SELECT id, $usernamefields, email, idnumber, username, tenantid
                      FROM {user} u
                     WHERE id " . $idsql . "
                  ORDER BY u.firstname, u.lastname", $params, $offset * $limit, $limit);

        return $users;
    }

    /**
     * Returns a users name for selection in a seminar.
     *
     * This function allows for viewing user identity information as configured for the site.
     *
     * Taken from \user_selector_base::output_user
     * At some point this needs to be converted to a proper user selector.
     *
     * @param object $user
     * @param array|null $extrafields Extra fields to display next to the users name, if null the user identity fields are used.
     * @param bool $fullnameoverride Passed through to the fullname function as the override arg.
     * @return string
     */
    public static function output_user_for_selection($user, array $extrafields = null, bool $fullnameoverride = false): string {

        $out = fullname($user, $fullnameoverride);
        if ($extrafields) {
            $displayfields = array();
            foreach ($extrafields as $field) {
                if (!empty($user->{$field})) {
                    // TOTARA - Escape potential XSS in extra identity fields.
                    $displayfields[] = s($user->{$field});
                }
            }
            // This little bit of hardcoding is pretty bad, but its consistent with how Seminar was working and as this
            // change was made right before release we wanted to keep it consistent.
            if (!empty($displayfields)) {
                $out .= ', ' . implode(', ', $displayfields);
            }
        }
        return $out;
    }

    /**
     * Checks if the user needs to be evaluated in the context based on multitenancy settings and
     * returns an array with the error (if found) resulting after the validation.
     *
     * @param object $user
     * @param \context $context.
     * @return array
     */
    public static function user_in_tenant_context_validation($user, \context $context): array {
        global $DB, $CFG;

        $validationerrors = array();
        if (!empty($CFG->tenantsenabled)) {
            if ($context->tenantid) {
                $tenant = \core\record\tenant::fetch($context->tenantid);
                $sql = "SELECT id FROM {cohort_members} WHERE userid = ? AND cohortid = ?";
                $params = [$user->id, $tenant->cohortid];
                if (!$DB->record_exists_sql($sql, $params)) {
                    $error = get_string('nottenantparticipant', 'mod_facetoface');
                    $validationerrors = ['idnumber' => $user->idnumber, 'name' => fullname($user), 'result' => $error];
                }
            } else {
                if (!empty($CFG->tenantsisolated) && ($user->tenantid != NULL)) {
                    $error = get_string('tenantmemberisolationmode', 'mod_facetoface');
                    $validationerrors = ['idnumber' => $user->idnumber, 'name' => fullname($user), 'result' => $error];
                }
            }
        }

        return $validationerrors;
    }
}
