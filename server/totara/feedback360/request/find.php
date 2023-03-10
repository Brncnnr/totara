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
 * @author David Curry <david.curry@totaralms.com>
 * @package totara
 * @subpackage totara_feedback360
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot.'/totara/core/dialogs/dialog_content.class.php');
require_once($CFG->dirroot . '/totara/feedback360/lib.php');

require_login();
feedback360::check_feature_enabled();

$userid = required_param('userid', PARAM_INT);
$selected = optional_param('selected', null, PARAM_SEQUENCE);

$formid = required_param('formid', PARAM_INT);
$nojs = optional_param('nojs', false, PARAM_BOOL);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

$systemcontext = context_system::instance();
$usercontext = context_user::instance($userid);

// Make sure the user assignment exists and that it relates to the given userid.
if (!$user_assignment = $DB->get_record('feedback360_user_assignment', array('id' => $formid, 'userid' => $userid))) {
    print_error('userassignmentnotfound', 'totara_feedback360');
}

// Check user has permission to request feedback.
if ($USER->id == $userid) {
    // This is the user editing their own feedback.
    require_capability('totara/feedback360:manageownfeedback360', $systemcontext);
} else if (\totara_job\job_assignment::is_managing($USER->id, $userid)) {
    // This is the manager editing their staff memebers feedback.
    require_capability('totara/feedback360:managestafffeedback', $usercontext);
} else {
    print_error('error:accessdenied', 'totara_feedback');
}

$PAGE->set_context($systemcontext);

// Get guest user for exclusion purposes.
$guest = guest_user();

// Exclude anyone already requested from the list of available users.
if (!empty($selected)) {
    $selectedids = explode(',', $selected);

    // Set up some things to disable unassignable users.
    list($disable_insql, $disable_params) = $DB->get_in_or_equal($selectedids);
    $disable_sql = "SELECT u.*
                    FROM {user} u
                    WHERE id {$disable_insql}";
    $disable = $DB->get_records_sql($disable_sql, $disable_params);

    foreach ($disable as $record) {
        $record->fullname = fullname($record);
    }

    // Set up some things to get the assignable users.
    list($selectedsql, $selectedparams) = $DB->get_in_or_equal($selectedids, SQL_PARAMS_QM, 'param', false);
    $notalreadyrequested = "AND u.id $selectedsql";
    $params = array($guest->id, $userid);
} else {
    $selectedids = '';

    $notalreadyrequested = '';
    $params = array($guest->id, $userid);
    $disable = array();
}

$usernamefields = totara_get_all_user_name_fields(true, 'u');
$username_extra_fields = get_extra_user_fields_sql($systemcontext, 'u', '', totara_get_all_user_name_fields());
$order = totara_get_all_user_name_fields(true, 'u', null, null, true);
$availableusers = $DB->get_records_sql(
   "
        SELECT
            u.id, {$usernamefields} {$username_extra_fields}
        FROM
            {user} u
        WHERE
            u.deleted = 0
        AND u.suspended = 0
        AND u.id != ?
        AND u.id != ?
        ORDER BY {$order}
    ",
    $params, 0, TOTARA_DIALOG_MAXITEMS + 1);

foreach ($availableusers as $user) {
    $user->fullname = fullname($user);
}

// Limit results to 1 more than the maximum number that might be displayed.
// there is no point returning any more as we will never show them.
if (!$nojs) {
    // Display the javascript version of the page.
    $dialog = new totara_dialog_content();
    $dialog->selected_items = $disable;
    $dialog->type = totara_dialog_content::TYPE_CHOICE_MULTI;
    $dialog->searchtype = 'user';
    $dialog->items = $availableusers;
    $dialog->customdata['current_user'] = $userid;
    $dialog->urlparams['userid'] = $userid;
    $dialog->urlparams['formid'] = $formid;

    if (!empty($selected)) {
        $selected_users = explode(',', $selected);
        $disable = array();
        foreach ($selected_users as $selected_user) {
            $disable[$selected_user] = $DB->get_record('user', array('id' => $selected_user));
        }
        $dialog->disabled_items = $disable;
    }

    echo $dialog->generate_markup();
} else {
    require_once($CFG->dirroot . '/totara/feedback360/lib.php');

    $PAGE->set_url(new moodle_url('/totara/feedback360/request/find.php',
            array('userid' => $userid, 'selected' => $selected, 'nojs' => 1)));
    $PAGE->set_title(get_string('requestfeedback360', 'totara_feedback360'));
    $PAGE->set_heading(get_string('requestfeedback360', 'totara_feedback360'));

    $user_assignment = $DB->get_record('feedback360_user_assignment', array('id' => $formid));
    $feedback360 = $DB->get_record('feedback360', array('id' => $user_assignment->feedback360id));

    $completedusers = array();
    if (!$feedback360->anonymous) {
        $completedusers = $DB->get_records_sql("SELECT ra.userid
                                                FROM {feedback360_resp_assignment} ra
                                                JOIN {feedback360_user_assignment} ua
                                                ON ra.feedback360userassignmentid = ua.id
                                                WHERE ra.timecompleted > 0 AND ua.userid = :uid
                                                AND ra.feedback360emailassignmentid IS NULL
                                                AND ua.id = :aid",
                                                array('uid' => $userid, 'aid' => $formid));
        $completedusers = array_keys($completedusers);
    }

    $options = array('guestid' => $guest->id, 'userid' => $userid, 'currentusers' => $selectedids, 'completedusers' => $completedusers);

    $add_user_selector = new request_feedback_potential_user_selector('addrequest', $options);
    $remove_user_selector = new request_feedback_current_user_selector('removeselect', $options);

    if (optional_param('cancel', false, PARAM_BOOL) && confirm_sesskey()) {

        $request_params = array('userid' => $userid, 'formid' => $formid, 'action' => 'users',
            'selected' => $selected, 'nojs' => 1);
        $request_url = '/totara/feedback360/request.php';

        redirect(new moodle_url($request_url, $request_params));

    }

    if (optional_param('add', false, PARAM_BOOL) && confirm_sesskey()) {
        $userstoadd = $add_user_selector->get_selected_users();

        if (!empty($userstoadd)) {
            foreach ($userstoadd as $adduser) {
                $selectedids[$adduser->id] = $adduser->id;
            }
        }

        $selected = implode(',', $selectedids);
        $url = new moodle_url('/totara/feedback360/request/find.php',
                array('userid' => $userid, 'selected' => $selected, 'nojs' => 1, 'formid' => $formid));
        redirect($url);
    }

    if (optional_param('remove', false, PARAM_BOOL) && confirm_sesskey()) {
        $userstoremove = $remove_user_selector->get_selected_users();

        if (!empty($userstoremove)) {
            foreach ($userstoremove as $removeuser) {
                if (($key = array_search($removeuser->id, $selectedids)) !== false) {
                    unset($selectedids[$key]);
                }
            }
        }

        $selected = implode(',', $selectedids);
        $url = new moodle_url('/totara/feedback360/request/find.php',
                array('userid' => $userid, 'selected' => $selected, 'nojs' => 1, 'formid' => $formid));
        redirect($url);
    }

    echo $OUTPUT->header();

    if (!empty($selectedids)) {
        $selected = implode(',', $selectedids);
    } else {
        $selected = '';
    }

    $renderer = $PAGE->get_renderer('totara_feedback360');

    // Print the form.
    echo $renderer->nojs_feedback_request_users($selected, $returnurl, $add_user_selector, $remove_user_selector, $feedback360->anonymous);

    echo $OUTPUT->footer();
}
