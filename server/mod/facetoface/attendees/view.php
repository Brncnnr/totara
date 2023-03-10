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

use \mod_facetoface\attendees_helper;

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot.'/mod/facetoface/lib.php');
require_once($CFG->dirroot . '/totara/core/js/lib/setup.php');

/**
 * Load and validate base data
 */
// Face-to-face session ID
$s = optional_param('s', 0, PARAM_INT);
// Action being performed, a proper default will be set shortly.
// Require for attendees.js
$action            = optional_param('action', 'attendees', PARAM_ALPHA);
// Only return content
$onlycontent       = optional_param('onlycontent', false, PARAM_BOOL);
// Export download.
$download = optional_param('download', '', PARAM_ALPHA);
// Report support.
$sid = optional_param('sid', '0', PARAM_INT);
$debug = optional_param('debug', 0, PARAM_INT);

// If there's no sessionid specified.
if (!$s) {
    attendees_helper::process_no_sessionid('view');
    exit;
}

$seminarevent = new \mod_facetoface\seminar_event($s);
$seminar = $seminarevent->get_seminar();
$cm = $seminar->get_coursemodule();
$context = context_module::instance($cm->id);

// \mod_facetoface\form\signin requires sessiondates to be set
$session = $seminarevent->to_record();
$session->sessiondates = $seminarevent->get_sessions()->sort('timestart')->to_records(false);

require_login($seminar->get_course(), false, $cm);
/**
 * Print page header
 */
// Setup urls
$baseurl = new moodle_url('/mod/facetoface/attendees/view.php', array('s' => $seminarevent->get_id()));
$PAGE->set_context($context);
$PAGE->set_url($baseurl);

list($allowed_actions, $available_actions, $staff, $admin_requests, $canapproveanyrequest, $cancellations, $requests, $attendees)
    = attendees_helper::get_allowed_available_actions($seminar, $seminarevent, $context);

// $allowed_actions is already set, so we can now know if the current action is allowed.
if (!in_array($action, $allowed_actions)) {
    // If no allowed actions so far.
    $return = new moodle_url('/mod/facetoface/view.php', array('f' => $seminar->get_id()));
    redirect($return);
}

$title = format_string($seminar->get_name());
$PAGE->set_cm($cm);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($title . ': ' . get_string('attendees', 'mod_facetoface'));

attendees_helper::process_js($action, $seminar, $seminarevent);

$attendancestatuses = \mod_facetoface\signup\state\attendance_state::get_all_attendance_code_with(
    [
        \mod_facetoface\signup\state\booked::class,
        \mod_facetoface\signup\state\not_set::class
    ]
);
$report = attendees_helper::load_report('facetoface_sessions', $attendancestatuses);

//Print page content
echo $OUTPUT->header();
echo $OUTPUT->heading($title);

require_once($CFG->dirroot.'/mod/facetoface/attendees/tabs.php'); // If needed include tabs

// Get list of attendees
attendees_helper::is_overbooked($seminarevent);

$report->set_baseurl($baseurl);
$report->display_restrictions();

// Actions menu.
if ($seminarevent->get_cancelledstatus() == 0) {
    // Get list of actions
    $actions = [];
    $can_add_attendees = has_capability('mod/facetoface:addattendees', $context);
    if ($seminarevent->is_over() && !has_capability('mod/facetoface:signuppastevents', $context)) {
        $can_add_attendees = false;
    }
    if ($can_add_attendees) {
        $actions['add']          = get_string('addattendees', 'mod_facetoface');
        $actions['bulkaddfile']  = get_string('addattendeesviafileupload', 'mod_facetoface');
        $actions['bulkaddinput'] = get_string('addattendeesviaidlist', 'mod_facetoface');
    }
    if (has_capability('mod/facetoface:removeattendees', $context)) {
        $actions['remove'] = get_string('removeattendees', 'mod_facetoface');
    }
    if (has_capability('mod/facetoface:managearchivedattendees', $context)) {
        $actions['managearchives'] = get_string('managearchivedattendees', 'mod_facetoface');
    }
    if (!empty($actions)) {
        echo $OUTPUT->container_start('actions last');
        // Action selector
        echo html_writer::label(get_string('attendeeactions', 'mod_facetoface'), 'menuf2f-actions', true, ['class' => 'sr-only']);
        echo html_writer::select($actions, 'f2f-actions', '', array('' => get_string('actions')));
        echo $OUTPUT->container_end();
    }
}
/** @var totara_reportbuilder_renderer $output */
$output = $PAGE->get_renderer('totara_reportbuilder');
// This must be done after the header and before any other use of the report.
list($reporthtml, $debughtml) = $output->report_html($report, $debug);
echo $debughtml;

// Print saved search buttons if appropriate.
$report->display_saved_search_options();
$report->display_search();
$report->display_sidebar_search();
echo $reporthtml;

attendees_helper::report_export_form($report, $sid);

// Session downloadable sign in sheet.
if ($seminarevent->is_sessions() && has_capability('mod/facetoface:exportsessionsigninsheet', $context)) {
    if (0 < $report->get_filtered_count()) {
        // We need the dates, and we only want to show this option if there are one or more dates.
        $mform = new \mod_facetoface\form\signin(new moodle_url('/mod/facetoface/reports/signinsheet.php'), $session);
        $mform->display();
    }
}

// Go back.
$url = new moodle_url('/mod/facetoface/view.php', array('f' => $seminar->get_id()));
$f2f_renderer = $PAGE->get_renderer('mod_facetoface');
$f2f_renderer->setcontext($context);
echo $f2f_renderer->render_action_bar_on_tabpage($url);

echo $OUTPUT->footer();

\mod_facetoface\event\attendees_viewed::create_from_session($session, $context, $action)->trigger();
