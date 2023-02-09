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

use \mod_facetoface\attendees_helper;
use \mod_facetoface\signup\state\declined;
use \mod_facetoface\signup\state\event_cancelled;
use \mod_facetoface\signup\state\user_cancelled;

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot.'/mod/facetoface/lib.php');

// Face-to-face session ID
$s = optional_param('s', 0, PARAM_INT);
// Action being performed, a proper default will be set shortly.
// Require for attendees.js
$action = optional_param('action', 'cancellations', PARAM_ALPHA);
$sid = optional_param('sid', '0', PARAM_INT);
$debug = optional_param('debug', 0, PARAM_INT);

// If there's no sessionid specified.
if (!$s) {
    \mod_facetoface\attendees_helper::process_no_sessionid('cancellations');
    exit;
}

$seminarevent = new \mod_facetoface\seminar_event($s);
$seminar = $seminarevent->get_seminar();
$cm = $seminar->get_coursemodule();
$context = context_module::instance($cm->id);

require_login($seminar->get_course(), false, $cm);
/**
 * Print page header
 */
// Setup urls
$baseurl = new moodle_url('/mod/facetoface/attendees/cancellations.php', ['s' => $seminarevent->get_id()]);
$PAGE->set_context($context);
$PAGE->set_url($baseurl);

list($allowed_actions, $available_actions, $staff, $admin_requests, $canapproveanyrequest, $cancellations, $requests, $attendees)
    = attendees_helper::get_allowed_available_actions($seminar, $seminarevent, $context);

// $allowed_actions is already set, so we can now know if the current action is allowed.
if (!in_array($action, $allowed_actions)) {
    // If no allowed actions so far.
    $return = new moodle_url('/mod/facetoface/view.php', ['f' => $seminar->get_id()]);
    redirect($return);
}

$pagetitle = format_string($seminar->get_name());
$PAGE->set_pagelayout('standard');
$PAGE->set_title($pagetitle);
$PAGE->set_cm($cm);

attendees_helper::process_js($action, $seminar, $seminarevent);

$attendancestatuses = [
    user_cancelled::get_code(),
    event_cancelled::get_code(),
    declined::get_code()
];
$report = attendees_helper::load_report('facetoface_cancellations', $attendancestatuses);

/**
 * Print page content
 */
echo $OUTPUT->header();
echo $OUTPUT->heading($pagetitle);

require_once($CFG->dirroot.'/mod/facetoface/attendees/tabs.php'); // If needed include tabs

$report->set_baseurl($baseurl);
$report->display_restrictions();

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

// Go back.
$url = new moodle_url('/mod/facetoface/view.php', array('f' => $seminar->get_id()));
$f2f_renderer = $PAGE->get_renderer('mod_facetoface');
$f2f_renderer->setcontext($context);
echo $f2f_renderer->render_action_bar_on_tabpage($url);

echo $OUTPUT->footer();

\mod_facetoface\event\attendees_viewed::create_from_session($seminarevent->to_record(), $context, $action)->trigger();
