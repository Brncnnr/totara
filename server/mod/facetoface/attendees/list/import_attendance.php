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

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/lib/csvlib.class.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');

use mod_facetoface\bulk_list;
use mod_facetoface\seminar_event;
use mod_facetoface\form\import_attendance;

$s = required_param('s', PARAM_INT);
$sd = optional_param('sd', 0, PARAM_INT);
$listid = optional_param('listid', null, PARAM_INT);

$seminarevent = new seminar_event($s);
$returnurl  = new moodle_url('/mod/facetoface/attendees/takeattendance.php', ['s' => $s, 'sd' => $sd]);

$msg = get_string('error:takeattendance', 'mod_facetoface');
if (!$seminarevent->is_attendance_open()) {
    $msg .= ' - ' . get_string('eventinprogress', 'mod_facetoface');
    redirect($returnurl, $msg, null, \core\notification::ERROR);
}
// Dirty hack?! Not yet ready to process.
if ((bool)$sd) {
    redirect($returnurl, $msg, null, \core\notification::ERROR);
}
unset($msg);

$srctype = 'importattendance';
$listid = $listid ?: \csv_import_reader::get_new_iid($srctype);

$seminar = $seminarevent->get_seminar();
$cm = $seminar->get_coursemodule();
$context = $seminar->get_contextmodule($cm->id);

$params = ['s' => $s, 'sd' => $sd, 'listid' => $listid];
$currenturl = new moodle_url('/mod/facetoface/attendees/list/import_attendance.php', $params);

// Check capability
require_login($seminar->get_course(), false, $cm);
require_capability('mod/facetoface:takeattendance', $context);

$pagetitle = get_string('uploadattendancestep', 'mod_facetoface', '1');
$PAGE->set_context($context);
$PAGE->set_url($currenturl);
$PAGE->set_cm($cm);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($seminar->get_name() . ': ' . $pagetitle);

$list = new bulk_list($listid, $currenturl, $srctype, $seminarevent->get_id());
$mform = new import_attendance(null, $params);
if ($mform->is_cancelled()) {
    $mform->cancel($list);
    redirect($returnurl);
}
// Check if data submitted.
if ($formdata = $mform->get_data()) {
    import_attendance::upload($formdata, $seminarevent, $list);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($pagetitle);

$mform->display();

echo $OUTPUT->footer();