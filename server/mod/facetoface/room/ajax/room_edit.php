<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');
require_once($CFG->dirroot . '/totara/customfield/fieldlib.php');

use mod_facetoface\room;
use mod_facetoface\seminar;
use mod_facetoface\room_helper;
use mod_facetoface\room_virtualmeeting;
use mod_facetoface\seminar_event;
use mod_facetoface\form\editroom as room_edit;

$id = required_param('id', PARAM_INT);   // Room id.
$facetofaceid = required_param('f', PARAM_INT);   // Face-to-face id.
$sessionid = optional_param('s', 0, PARAM_INT);

$seminar = new seminar($facetofaceid);
$seminarevent = new seminar_event($sessionid);
$cm = $seminar->get_coursemodule();
$context = $seminar->get_contextmodule($cm->id);

if (!$seminarevent->exists()) {
    $seminarevent->set_facetoface($seminar->get_id());
}

ajax_require_login($seminar->get_course(), false, $cm, false, true);
if (!has_capability('mod/facetoface:manageadhocrooms', $context)) {
    throw new required_capability_exception($context, 'mod/facetoface:manageadhocrooms', 'nopermissions', '');
}
require_sesskey();

if ($id) {
    $room = new room($id);
    // Only custom room can be changed here!
    if (!$room->get_custom()) {
        throw new coding_exception('Site wide room must be edited from the Site administration > Seminar > Room menu');
    }
    if (!$room->is_available(0, 0, $seminarevent)) {
        // They should never get here, any error will do.
        print_error('Error: Room is unavailable in this seminar event');
    }
} else {
    $room = room::create_custom_room();
}

// Legacy Totara HTML ajax, this should be converted to json + AJAX_SCRIPT.
send_headers('text/html; charset=utf-8', false);

$PAGE->set_context($context);
$PAGE->set_url('/mod/facetoface/room/ajax/room_edit.php', ['id' => $id, 'f' => $facetofaceid]);

$virtual_meeting = room_virtualmeeting::get_virtual_meeting($room);
$customdata = [
    'room' => $room,
    'virtual_meeting' => $virtual_meeting,
    'seminar' => $seminar,
    'seminarevent' => $seminarevent,
    'adhoc' => true
];
$form = new room_edit(null, $customdata, 'post', '', array('class' => 'dialog-nobind'), true, null, 'mform_modal');

if ($data = $form->get_data()) {
    $room = room_helper::save($data);
    echo json_encode(
        ['id' => $room->get_id(), 'name' => $room->get_name(), 'custom' => (int)$room->get_custom(), 'capacity' => $room->get_capacity()]
    );
} else {
    // This is required because custom fields may use AMD module for JS and we can't re-initialise AMD
    // which will happen if we call get_end_code() without setting the first arg to false.
    // It must be called before form->display and importantly before get_end_code.
    $amdsnippets = $PAGE->requires->get_raw_amd_js_code();

    $form->display();
    echo $PAGE->requires->get_end_code(false);
    // Finally add our AMD code into the page.
    echo html_writer::script(implode(";\n", $amdsnippets));
}
