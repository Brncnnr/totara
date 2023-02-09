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
 * @author Francois Marier <francois@catalyst.net.nz>
 * @author Aaron Barnes <aaronb@catalyst.net.nz>
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_facetoface
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot.'/mod/facetoface/lib.php');
require_once($CFG->libdir.'/totaratablelib.php');
require_once($CFG->dirroot . '/totara/core/js/lib/setup.php');

use \mod_facetoface\attendees_helper;
use \mod_facetoface\signup;
use \mod_facetoface\signup_helper;
use \mod_facetoface\signup\state\{requested, requestedadmin, declined};
use \mod_facetoface\seminar;
use \mod_facetoface\seminar_event;

/**
 * Load and validate base data
 */
// Face-to-face session ID
$s = optional_param('s', 0, PARAM_INT);
// Action being performed, a proper default will be set shortly, require for attendees.js
$action = optional_param('action', 'approvalrequired', PARAM_ALPHA);
// If approval requests have been updated, show a success message.
$approved = optional_param('approved', 0, PARAM_INT);

// If there's no sessionid specified.
if (!$s) {
    attendees_helper::process_no_sessionid('approvalrequired');
    exit;
}

$seminarevent = new seminar_event($s);
$seminar = $seminarevent->get_seminar();
$cm = $seminar->get_coursemodule();
$context = context_module::instance($cm->id);

$helper = new \mod_facetoface\attendees_helper($seminarevent);

// Allow managers to be able to approve staff without being enrolled in the course.
require_login();
/**
 * Print page header
 */
// Setup urls
$baseurl = new moodle_url('/mod/facetoface/attendees/approvalrequired.php', array('s' => $seminarevent->get_id()));
$PAGE->set_context($context);
$PAGE->set_url($baseurl);

list($allowed_actions, $available_actions, $staff, $admin_requests, $canapproveanyrequest, $cancellations, $requests, $attendees)
    = \mod_facetoface\attendees_helper::get_allowed_available_actions($seminar, $seminarevent, $context);

// $allowed_actions is already set, so we can now know if the current action is allowed.
$actionallowed = in_array($action, $allowed_actions);

/**
 * Handle actions
 */
$heading_message = '';
$params = array('sessionid' => $s);
$cols = array();
$actions = array();
$exports = array();

/**
 * Handle submitted data
 */
if (($form = data_submitted())) {
    // Approve requests
    if (!empty($form->requests) && $actionallowed) {
        // Site admin is allowing to approve user request.
        if (!$canapproveanyrequest) {
            // Leave the users which are required to approve and remove the rest.
            $form->requests = array_intersect_key($form->requests, array_flip($staff));
        }
        $baseurl = attendees_helper::approve_decline_user($form, $seminarevent);
        redirect($baseurl);
    }
}

$pagetitle = format_string($seminar->get_name());
$PAGE->set_cm($cm);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($pagetitle);

attendees_helper::process_js($action, $seminar, $seminarevent);

/**
 * Print page content
 */
echo $OUTPUT->header();
echo $OUTPUT->heading($pagetitle);

require_once($CFG->dirroot.'/mod/facetoface/attendees/tabs.php'); // If needed include tabs
echo $OUTPUT->container_start('f2f-attendees-table');

/**
 * Print unapproved requests (if user able to view)
 */
if ($approved == 1) {
    echo $OUTPUT->notification(get_string('attendancerequestsupdated', 'facetoface'), 'notifysuccess');
}

echo html_writer::empty_tag('br', array('id' => 'unapproved'));
$numattendees = $helper->count_attendees();
$numwaiting = count($requests);
$availablespaces = $seminarevent->get_capacity() - $numattendees;
$allowoverbook = $seminarevent->get_allowoverbook();
$canoverbook = has_capability('mod/facetoface:signupwaitlist', $context);

// Are there more users waiting than spaces available?
// Note this does not apply to people with overbook capability (see seminar_event::has_capacity).
if (!$canoverbook && ($numwaiting > $availablespaces)) {
    $stringmodifier = ($availablespaces > 0) ? 'over' : 'no';
    $stringidentifier = ($allowoverbook) ? "approval{$stringmodifier}capacitywaitlist" : "approval{$stringmodifier}capacity";
    $overcapacitymessage = get_string(
        $stringidentifier,
        'mod_facetoface',
        array('waiting' => $numwaiting, 'available' => $availablespaces)
    );
    echo $OUTPUT->notification($overcapacitymessage, 'notifynotice');
}
// If they cannot overbook and no spaces are available, disable the ability to approve more requests.
$approvaldisabled = array();
if (!$canoverbook && ($availablespaces <= 0 && !$allowoverbook)) {
    $approvaldisabled['disabled'] = 'disabled';
}

echo html_writer::start_tag('form', array('action' => $baseurl, 'method' => 'post'));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => $USER->sesskey));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 's', 'value' => $s));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'action', 'value' => 'approvalrequired'));

$headings = array();
$headings[] = get_string('name');
$headings[] = get_string('timerequested', 'facetoface');
$viewattendeesnote = has_capability('mod/facetoface:viewattendeesnote', $context);
$manageattendeesnote = has_capability('mod/facetoface:manageattendeesnote', $context);
if ($viewattendeesnote) {
    // The user has to hold specific permissions to view this.
    $headings[] = get_string('attendeenote', 'facetoface');
}

// Additional approval columns for the approval tab.
if ($seminar->get_approvaltype() == seminar::APPROVAL_MANAGER ||
    $seminar->get_approvaltype() == seminar::APPROVAL_ADMIN) {
    $headings[] = get_string('header:managername', 'facetoface');
    if ($seminar->get_approvaltype() == seminar::APPROVAL_ADMIN) {
        $headings[] = get_string('header:approvalstate', 'facetoface');
        $headings[] = get_string('header:approvaltime', 'facetoface');
    }
}

$headings[] = get_string('decidelater', 'facetoface');
$headings[] = get_string('decline', 'facetoface');
$headings[] = get_string('approve', 'facetoface');

$table = new html_table();
$table->summary = get_string('requeststablesummary', 'facetoface');
$table->head = $headings;
$table->align = array('left', 'center', 'center', 'center', 'center', 'center');
$pix = new pix_icon('t/edit', get_string('edit'));
$params = ['s' => $seminarevent->get_id(), 'sesskey' => sesskey(), 'return' => $action];
$course = get_course($seminar->get_course());
foreach ($requests as $attendee) {
    $attendeefullname = format_string(fullname($attendee));
    $data = array();
    $attendee_link = user_get_profile_url($attendee->id, $course);
    if ($attendee_link) {
        $data[] = html_writer::link($attendee_link, $attendeefullname);
    } else {
        $data[] = $attendeefullname;
    }
    $data[] = userdate($attendee->timecreated, get_string('strftimedatetime'));

    // Get signup note.
    $icon = $note = '';
    $signupstatus = new stdClass();
    $signupstatus->id = $attendee->submissionid;
    $signupnote = customfield_get_data($signupstatus, 'facetoface_signup', 'facetofacesignup', false);
    if ($manageattendeesnote && !empty($signupnote)) {
        $params['userid'] = $attendee->id;
        $url = new moodle_url('/mod/facetoface/attendees/ajax/signup_notes.php', $params);
        $icon = $OUTPUT->action_icon($url, $pix, null, array('class' => 'action-icon attendee-add-note pull-right'));
    }
    if ($viewattendeesnote) {
        if (!empty($signupnote)) {
            // Currently it is possible to delete signupnote custom field easly so we must check if cf is exists.
            $signupnotetext = isset($signupnote['signupnote']) ? $signupnote['signupnote'] : '';
            $note = html_writer::span($signupnotetext, 'note' . $attendee->id, array('id' => 'usernote' . $attendee->id));
        }
        $data[] = $icon . $note;
    }

    // Additional approval columns for the approval tab.
    if ($seminar->get_approvaltype() == seminar::APPROVAL_MANAGER ||
        $seminar->get_approvaltype() == seminar::APPROVAL_ADMIN) {
        $signup = signup::create($attendee->id, $seminarevent);
        $managers = signup_helper::find_managers_from_signup($signup);

        $managernames = array();
        $state = '';
        $time = '';
        foreach ($managers as $manager) {
            $managernames[] =  $manager->fullname;
        }
        if ($seminar->get_approvaltype() == seminar::APPROVAL_ADMIN) {
            switch ($attendee->statuscode) {
                case requested::get_code():
                    $state = get_string('none', 'mod_facetoface');
                    $time = '';
                    break;
                case requestedadmin::get_code():
                    $state = get_string('approved', 'mod_facetoface');
                    $time = userdate($attendee->timecreated);
                    break;
                default:
                    print_error('error:invalidstatus', 'mod_facetoface');
                    break;
            }
        }
        $managernamestr = implode(', ', $managernames);
        $data[] = html_writer::span($managernamestr, 'managername' . $attendee->id, array('id' => 'managername' . $attendee->id));
        if ($seminar->get_approvaltype() == seminar::APPROVAL_ADMIN) {
            $data[] = html_writer::span($state, 'approvalstate' . $attendee->id, array('id' => 'approvalstate' . $attendee->id));
            $data[] = html_writer::span($time, 'approvaltime' . $attendee->id, array('id' => 'approvaltime' . $attendee->id));
        }
    }

    $id = 'requests_' . $attendee->id . '_noaction';
    $label = html_writer::label(get_string('decideuserlater', 'mod_facetoface', $attendeefullname), $id, '', ['class' => 'sr-only']);
    $radio = html_writer::empty_tag(
        'input',
        array_merge(
            $approvaldisabled,
            array('type' => 'radio', 'name' => 'requests['.$attendee->id.']', 'value' => '0', 'checked' => 'checked', 'id' => $id)
        )
    );
    $data[] = $label . $radio;

    $id = 'requests_' . $attendee->id . '_decline';
    $label = html_writer::label(get_string('declineuserevent', 'mod_facetoface', $attendeefullname), $id, '', ['class' => 'sr-only']);
    $radio = html_writer::empty_tag(
        'input',
        array_merge(
            $approvaldisabled,
            array('type' => 'radio', 'name' => 'requests['.$attendee->id.']', 'value' => '1', 'id' => $id)
        )
    );
    $data[] = $label . $radio;

    $id = 'requests_' . $attendee->id . '_approve';
    $label = html_writer::label(get_string('approveuserevent', 'mod_facetoface', $attendeefullname), $id, '',['class' => 'sr-only']);
    $radio = html_writer::empty_tag(
        'input',
        array_merge(
            $approvaldisabled,
            array('type' => 'radio', 'name' => 'requests['.$attendee->id.']', 'value' => '2', 'id' => $id)
        )
    );
    $data[] = $label . $radio;
    $table->data[] = $data;
}

if (!empty($table->data)) {
    echo html_writer::table($table);
    echo html_writer::tag(
        'p',
        html_writer::empty_tag(
            'input',
            array('type' => 'submit', 'value' => get_string('updaterequests', 'facetoface'))
        )
    );
} else {
    echo html_writer::start_span();
    echo html_writer::tag('p', get_string('nopendingapprovals', 'facetoface'));
    echo html_writer::end_span();
}

echo html_writer::end_tag('form');

// If no allowed actions so far, check if this was user/manager who has just approved staff requests (approved == 1).
// If so, do not redirect, just display notify message.
// Hide "Go back" link for case if a user does not have any capabilities to see facetoface/course.
$goback = !($approved == 1);
if ($goback) {
    $url = new moodle_url('/mod/facetoface/view.php', array('f' => $seminar->get_id()));
    $f2f_renderer = $PAGE->get_renderer('mod_facetoface');
    $f2f_renderer->setcontext($context);
    echo $f2f_renderer->render_action_bar_on_tabpage($url);
}

/**
 * Print page footer
 */
echo $OUTPUT->container_end();
echo $OUTPUT->footer();

\mod_facetoface\event\approval_required_viewed::create_from_session($seminarevent->to_record(), $context, $action)->trigger();
