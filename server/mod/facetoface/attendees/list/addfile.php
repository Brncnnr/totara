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
require_once($CFG->dirroot . '/lib/csvlib.class.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');

use core\notification;
use mod_facetoface\bulk_list;
use mod_facetoface\seminar_event;
use mod_facetoface\attendees_list_helper;
use mod_facetoface\form\attendees_add_file;

$s = required_param('s', PARAM_INT);
$listid = optional_param('listid', uniqid('f2f'), PARAM_ALPHANUM);
$importid = optional_param('importid', '', PARAM_INT);

$seminarevent = new seminar_event($s);
$seminar = $seminarevent->get_seminar();
$cm = $seminar->get_coursemodule();
$context =  context_module::instance($cm->id);

$returnurl  = new moodle_url('/mod/facetoface/attendees/view.php', array('s' => $s));
$currenturl = new moodle_url('/mod/facetoface/attendees/list/addfile.php', array('s' => $s, 'listid' => $listid));

// Check capability
$list = new bulk_list($listid, $currenturl, 'addfile');
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

$mform = new attendees_add_file(null, ['s' => $s, 'listid' => $listid, 'seminar' => $seminar]);
if ($mform->is_cancelled()) {
    $list->clean();
    redirect($returnurl);
}
// Check if data submitted.
if ($formdata = $mform->get_data()) {
    attendees_list_helper::add_file($formdata);
}

local_js(array(TOTARA_JS_DIALOG));
$PAGE->requires->js_call_amd('mod_facetoface/attendees_addremove', 'init', array(array('s' => $s, 'listid' => $listid)));

echo $OUTPUT->header();
echo $OUTPUT->heading($pagetitle);

$mform->display();

echo $OUTPUT->footer();