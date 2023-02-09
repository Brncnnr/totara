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
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @author Aaron Barnes <aaron.barnes@totaralms.com>
 * @author Francois Marier <francois@catalyst.net.nz>
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package mod_facetoface
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');
require_once($CFG->dirroot . '/totara/core/js/lib/setup.php');

$f  = required_param('f', PARAM_INT);  // facetoface Module ID
$id = optional_param('id', 0, PARAM_INT); // Course Module ID
$cntdates = optional_param('cntdates', 0, PARAM_INT); // Number of events to set.
$backtoevent = optional_param('backtoevent', 0, PARAM_BOOL);
$backtoallsessions = optional_param('backtoallsessions', 1, PARAM_BOOL);
$savewithconflicts = optional_param('savewithconflicts', 0, PARAM_BOOL); // Save with conflicts.

$session = null;
$s = 0;
$c = 0;

$seminar = new mod_facetoface\seminar($f);
if (!$seminar->exists()) {
    print_error('error:incorrectfacetofaceid', 'facetoface');
}

$cm = $seminar->get_coursemodule();
$f  = $seminar->get_id();
$id = $cm->id;
$context = $seminar->get_contextmodule($cm->id);
$course = $DB->get_record('course', array('id' => $seminar->get_course()));

require_login($seminar->get_course(), false, $cm);
require_capability('mod/facetoface:editevents', $context);

local_js(array(
    TOTARA_JS_DIALOG,
    TOTARA_JS_TREEVIEW
));
$PAGE->set_url('/mod/facetoface/events/add.php', array('f' => $f, 'backtoallsessions' => $backtoallsessions));
$PAGE->set_title(get_string('addingseminarsession', 'facetoface', format_string($seminar->get_name())));
$PAGE->set_heading($course->fullname);

$PAGE->requires->strings_for_js(array('save', 'delete'), 'totara_core');
$PAGE->requires->strings_for_js(array('cancel', 'ok', 'edit', 'loadinghelp'), 'moodle');
$PAGE->requires->strings_for_js(
    array(
        'chooseassets', 'choosefacilitators', 'chooserooms', 'dateselect', 'useroomcapacity', 'nodatesyet', 'createnewasset', 'editasset',
        'createnewroom', 'editroom', 'createnewfacilitator', 'editfacilitator', 'editcustomassetx', 'editcustomroomx', 'editcustomfacilitatorx',
        'chooserooms', 'chooseassets', 'choosefacilitators', 'removeroomx', 'removeassetx', 'removefacilitatorx', 'bookingconflict'
    ),
    'mod_facetoface'
);
$jsconfig = array(
    'sessionid' => $s, 'can_edit' => 'true', 'facetofaceid' => $seminar->get_id(), 'clone' => 0,
    'manageadhocassets' => has_capability('mod/facetoface:manageadhocassets', $context),
    'manageadhocfacilitators' => has_capability('mod/facetoface:manageadhocfacilitators', $context),
    'manageadhocrooms' => has_capability('mod/facetoface:manageadhocrooms', $context),
);
$PAGE->requires->js_call_amd('mod_facetoface/event', 'init', array($jsconfig));

if ($backtoallsessions) {
    $returnurl = new moodle_url('/mod/facetoface/view.php', array('f' => $seminar->get_id()));
} else {
    $returnurl = new moodle_url('/course/view.php', array('id' => $course->id));
}
// Offer one date for new sessions by default.
if ($cntdates < 1) {
    $cntdates = 1;
}
$facetoface = new stdClass();
$facetoface->id = $seminar->get_id();
$facetoface->allowcancellationsdefault = $seminar->get_allowcancellationsdefault();
$facetoface->cancellationscutoffdefault = $seminar->get_cancellationscutoffdefault();
list($sessiondata, $editoroptions, $defaulttimezone, $nbdays) = \mod_facetoface\form\event::prepare_data($session, $facetoface, $course, $context, $cntdates);

$mform = new \mod_facetoface\form\event(
    null,
    compact('id', 'f', 's', 'c', 'session', 'nbdays', 'course', 'editoroptions', 'defaulttimezone', 'facetoface', 'cm',
        'sessiondata', 'backtoallsessions', 'savewithconflicts', 'backtoevent'),
    'post',
    '',
    array('id' => 'mform_seminar_event')
);

if ($mform->is_cancelled()) {
    redirect($returnurl);
}
if ($todb = $mform->process_data()) { // Form submitted
    // Lets see if user conflicts are present.
    $users_in_conflict = $mform->get_users_in_conflict();
    if (empty($users_in_conflict)) {
        $mform->save($todb);
        redirect($returnurl);
    } else {
        $text = $mform->get_conflict_message();
        $PAGE->requires->js_call_amd('mod_facetoface/user_conflicts_confirm', 'init', array('note' => $text));
    }
}

echo $OUTPUT->header();
echo $OUTPUT->box_start();
echo $OUTPUT->heading(get_string('addingsession', 'facetoface', format_string($seminar->get_name())));

$mform->display();

echo $OUTPUT->box_end();
echo $OUTPUT->footer($course);
