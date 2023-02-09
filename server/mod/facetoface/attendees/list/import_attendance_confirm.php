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
use mod_facetoface\form\import_attendance_confirm;

$s = required_param('s', PARAM_INT);
$sd = optional_param('sd', 0, PARAM_INT);
$listid = required_param('listid', PARAM_INT);

$seminarevent = new seminar_event($s);
$seminar = $seminarevent->get_seminar();
$cm = $seminar->get_coursemodule();
$context = $seminar->get_contextmodule($cm->id);

$list = new bulk_list($listid);
$returnurl = $list->get_returnurl();
if ($seminarevent->get_id() != $list->get_seminareventid()) {
    $list->clean();
    redirect($returnurl, get_string('updateattendeesunsuccessful', 'mod_facetoface'), null, \core\notification::ERROR);
}
$params = ['s' => $seminarevent->get_id(), 'sd' => $sd, 'listid' => $list->get_list_id()];
$currenturl = new moodle_url('/mod/facetoface/attendees/list/import_attendance_confirm.php', $params);

// Check capability
require_login($seminar->get_course(), false, $cm);
require_capability('mod/facetoface:takeattendance', $context);

$pagetitle = get_string('uploadattendancestep', 'mod_facetoface', '2');
$PAGE->set_context($context);
$PAGE->set_url($currenturl);
$PAGE->set_cm($cm);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($seminar->get_name() . ': ' . $pagetitle);

// Selected users.
$userlist = $list->get_user_ids();
if (empty($userlist)) {
    $list->clean();
    redirect($returnurl, get_string('updateattendeesunsuccessful', 'mod_facetoface'), null, \core\notification::ERROR);
}

$mform = new import_attendance_confirm(null, $params);
if ($mform->is_cancelled()) {
    $mform->cancel($list);
    redirect($returnurl);
}

if ($mform->is_submitted()) {
    // Save and redirect.
    $mform->process_attendance($seminarevent, $list, $context);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($pagetitle);

$renderer = $PAGE->get_renderer('mod_facetoface');
$renderer->print_attendance_upload_table($list, $seminar->get_course());

$mform->display();

echo $OUTPUT->footer();