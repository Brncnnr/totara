<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package mod_facetoface
 */

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot.'/mod/facetoface/lib.php');
require_once($CFG->dirroot.'/totara/core/searchlib.php');
require_once($CFG->dirroot.'/totara/core/utils.php');

use core\notification;
use mod_facetoface\bulk_list;
use mod_facetoface\{
    seminar_event,
    attendees_helper
};
use mod_facetoface\signup\state\{
    booked,
    waitlisted,
    attendance_state
};

define('MAX_USERS_PER_PAGE', 1000);

$s              = required_param('s', PARAM_INT); // Facetoface session ID.
$searchtext     = optional_param('searchtext', '', PARAM_RAW_TRIMMED);
$clearsearch    = optional_param('clearsearch', 0, PARAM_BOOL);
$next           = optional_param('next', false, PARAM_BOOL);
$cancel         = optional_param('cancel', false, PARAM_BOOL);
$interested     = optional_param('interested', false, PARAM_BOOL); // Declare interest.
$ignoreconflicts = optional_param('ignoreconflicts', false, PARAM_BOOL); // Ignore scheduling conflicts.
$listid         = optional_param('listid', uniqid('f2f'), PARAM_ALPHANUM); // Session key to list of users to add.

$returnurl  = new moodle_url('/mod/facetoface/attendees/view.php', array('s' => $s));
$currenturl = new moodle_url('/mod/facetoface/attendees/list/add.php', array('s' => $s, 'listid' => $listid));

$action = 'add';
$attendees = array();
$notification = '';
$userstoadd = array();

$seminarevent = new seminar_event($s);
$seminar = $seminarevent->get_seminar();
$seminareventid = $seminarevent->get_id();
$cm = $seminar->get_coursemodule();
$context = context_module::instance($cm->id);

// Check essential permissions
$list = new bulk_list($listid, $currenturl, $action);
require_login($seminar->get_course(), false, $cm);
$can_add_attendees = has_capability('mod/facetoface:addattendees', $context);
if ($seminarevent->is_over() && !has_capability('mod/facetoface:signuppastevents', $context)) {
    $can_add_attendees = false;
}
if (!$can_add_attendees) {
    $list->clean();
    redirect(
        $returnurl,
        get_string('nopermissions'),
        null,
        notification::ERROR
    );
}

$pagetitle = get_string('addattendeestep1', 'mod_facetoface');
$PAGE->set_context($context);
$PAGE->set_url($currenturl);
$PAGE->set_cm($cm);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($seminar->get_name() . ': ' . $pagetitle);

if ($frm = data_submitted()) {
    require_sesskey();
    // Process selected user list.
    if (!empty($frm->removeselect)) {
        foreach ($frm->removeselect as $adduser) {
            if (!$adduser = clean_param($adduser, PARAM_INT)) {
                continue; // invalid userid
            }
            $userstoadd[] = $adduser;
        }

        $list->set_user_ids($userstoadd);
    }
    // Continue button.
    if ($next) {
        if (empty($userstoadd)) {
            $notification = get_string('pleaseselectusers', 'mod_facetoface');
        } else {
            // signup_helper::can_signup() will happen in step two.
            // Redirect to confirmation.
            redirect(new moodle_url('/mod/facetoface/attendees/list/addconfirm.php', [
                's' => $s,
                'listid' => $listid,
                'ignoreconflicts' => $ignoreconflicts
            ]));
            return;
        }
    } else if ($clearsearch) {
        $searchtext = '';
    }
}


if ($cancel) {
    $list->clean();
    redirect($returnurl);
    return;
}

// By default, don't display the waitlist.
$waitlist = 0;
// If the date and time of the session is not known attendees are waitlisted automatically
// until a date and time is applied to the session and they are displayed in the attendees list
// rather than the waitlist. So, only enable the waitlist tab when the date and time for the
// session is known and there are attendees with the waitlist status.
$helper = new attendees_helper($seminarevent);
$hassessions = $seminarevent->is_sessions();
if ($hassessions) {
    $waitlistcount = $helper->count_attendees_with_codes([waitlisted::get_code()]);
    if ($waitlistcount > 0) {
        $waitlist = 1;
    }
}

// Setup attendees array
$statuscodes = attendance_state::get_all_attendance_code_with([booked::class]);
if ($hassessions) {
    $attendees = $helper->get_attendees_with_codes($statuscodes);
} else {
    $statuscodes[] = waitlisted::get_code();
    $attendees = $helper->get_attendees_with_codes($statuscodes);
}

// Add selected users to attendees list.
$userlist = $list->get_user_ids();
if (!empty($userlist)) {
    list($userlist_sql, $userlist_params) = $DB->get_in_or_equal($userlist, SQL_PARAMS_NAMED, 'ulist');
    $userstoadd = $DB->get_records_sql("SELECT u.*, ss.statuscode
                                    FROM {user} u
                                    LEFT JOIN {facetoface_signups} su
                                      ON u.id = su.userid
                                     AND su.sessionid = {$seminareventid}
                                    LEFT JOIN {facetoface_signups_status} ss
                                      ON su.id = ss.signupid
                                     AND ss.superceded != 1
                                   WHERE u.id {$userlist_sql}", $userlist_params);
    // Merge with numeric keys.
    $attendees = $attendees + $userstoadd;
}

// Get users waiting approval to add to the "already attending" list as we do not want to add them again
$waitingapproval = $helper->get_attendees_in_requested();

// Add the waiting-approval users - we don't want to add them again
foreach ($waitingapproval as $waiting) {
    if (!isset($attendees[$waiting->id])) {
        $attendees[$waiting->id] = $waiting;
    }
}
// Handle the POST actions sent to the page
$error = false;

$where = "username <> 'guest' AND deleted = 0 AND suspended = 0 AND confirmed = 1";
$params = array();

// Apply search terms.
$searchtext = trim($searchtext);
if ($searchtext !== '') {   // Search for a subset of remaining users.
    $fields = get_all_user_name_fields();
    $fields[] = 'email';
    $fields[] = 'idnumber';
    $fields[] = 'username';
    $keywords = totara_search_parse_keywords($searchtext);
    list($searchwhere, $searchparams) = totara_search_get_keyword_where_clause($keywords, $fields, SQL_PARAMS_NAMED, 'srch');

    $where .= ' AND ' . $searchwhere;
    $params = array_merge($params, $searchparams);
}

// All non-signed up system users.
if ($attendees) {
    list($attendee_sql, $attendee_params) = $DB->get_in_or_equal(array_keys($attendees), SQL_PARAMS_NAMED, 'attd', false);
    $where .= ' AND u.id ' . $attendee_sql;
    $params = array_merge($params, $attendee_params);
}

$joininterest = '';
if ($interested) {
    $joininterest = "
    JOIN {facetoface_interest} fit ON (fit.userid = u.id)
    JOIN {facetoface_sessions} ssn ON (ssn.facetoface = fit.facetoface AND ssn.id = :eventid)
    ";
    $params['eventid'] = $seminareventid;
}

// Restrict to tenant participants
$tenantjoin = '';
$tenantwhere = '';
if (!empty($CFG->tenantsenabled)) {
    if ($context->tenantid) {
        $tenant = \core\record\tenant::fetch($context->tenantid);
        $tenantjoin = "JOIN {cohort_members} tp ON tp.userid = u.id AND tp.cohortid = :cohortid";
        $params['cohortid'] = $tenant->cohortid;
    } else {
        if (!empty($CFG->tenantsisolated)) {
            $tenantwhere = 'AND u.tenantid IS NULL';
        }
    }
}

$usercountrow = $DB->get_record_sql("SELECT COUNT(u.id) as num
                                               FROM {user} u
                                               {$tenantjoin}
                                               LEFT JOIN {facetoface_signups} su
                                                 ON u.id = su.userid
                                                AND su.sessionid = {$seminareventid}
                                               LEFT JOIN {facetoface_signups_status} ss
                                                 ON su.id = ss.signupid
                                                AND ss.superceded != 1
                                               $joininterest
                                      WHERE {$where} {$tenantwhere} ", $params);

$usercount = $usercountrow->num;

if ($usercount <= MAX_USERS_PER_PAGE) {
    $usernamefields = get_all_user_name_fields(true, 'u');
    // This starts with a comma, as there may be no extra fields.
    $useridentityfields = get_extra_user_fields_sql(true, 'u', '', get_all_user_name_fields());
    $availableusers = $DB->get_recordset_sql("SELECT u.id, {$usernamefields} {$useridentityfields}, u.email, ss.statuscode, su.archived
                                        FROM {user} u
                                        {$tenantjoin}
                                        LEFT JOIN {facetoface_signups} su
                                          ON u.id = su.userid
                                         AND su.sessionid = {$seminareventid}
                                        LEFT JOIN {facetoface_signups_status} ss
                                          ON su.id = ss.signupid
                                         AND ss.superceded != 1
                                       $joininterest
                                       WHERE {$where} {$tenantwhere}
                                       ORDER BY u.lastname ASC, u.firstname ASC", $params);
}

local_js(array(TOTARA_JS_DIALOG));
$PAGE->requires->js_call_amd('mod_facetoface/attendees_addremove', 'init', array(array('s' => $s, 'listid' => $listid)));

echo $OUTPUT->header();
echo $OUTPUT->heading($pagetitle);

if (!empty($notification)) {
    echo $OUTPUT->notification($notification, 'notifynotice');
}

// Configure selector form.
$strusertochange = get_string('userstoadd', 'facetoface');
$stravailableusers = get_string('potentialusers', 'role', $usercount);
$strlarrow = get_string('add');
$strrarrow = get_string('remove');
require_once('addremove_html.php');

echo $OUTPUT->footer();
