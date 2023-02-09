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
 * @package mod_facetoface
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');
require_once($CFG->dirroot . '/totara/customfield/fieldlib.php');

use \core\notification;
use mod_facetoface\room;
use mod_facetoface\room_helper;
use mod_facetoface\form\editroom as room_edit;

$id = optional_param('id', 0, PARAM_INT);
$backurl = optional_param('b', '', PARAM_LOCALURL);

$params = ['id' => $id];
$baseurl = new moodle_url('/mod/facetoface/room/edit.php', $params);
// Check permissions.
if (is_siteadmin()) {
    admin_externalpage_setup('modfacetofacerooms', '', null, $baseurl);
} else {
    $context = context_system::instance();
    $PAGE->set_pagelayout('standard');
    $PAGE->set_context($context);
    $PAGE->set_url($baseurl);
    require_login(0, false);
    require_capability('mod/facetoface:managesitewiderooms', $context);
}

$room = new room($id);
if (!empty($backurl)) {
    $returnurl = new moodle_url($backurl);
} else {
    $returnurl = new moodle_url('/mod/facetoface/room/manage.php');
}

if ($room->get_custom()) {
    redirect($returnurl, get_string('error:incorrectroomid', 'mod_facetoface'), null, notification::ERROR);
}

$customdata = [
    'room' => $room,
    'virtual_meeting' => new \mod_facetoface\room_virtualmeeting(),
    'backurl' => $returnurl,
    'adhoc' => false
];
$mform = new room_edit(null, $customdata, 'post', '', ['class' => 'dialog-nobind'], true, null, 'mform_modal');
$mform->enable_double_submit_detection($id);

if ($mform->is_cancelled()) {
    redirect($returnurl);
}

if ($data = $mform->get_data()) {
    $message = '';
    if (!$mform->is_double_submit_detected()) {
        $room = room_helper::save($data);
        $mform->mark_submit_as_processed();
        $message = $id ? get_string('roomupdatesuccess', 'mod_facetoface') : get_string('roomcreatesuccess', 'mod_facetoface');
    }
    redirect($returnurl, $message, null, notification::SUCCESS);
}

$pageheading = $id ? get_string('editroom', 'mod_facetoface') : get_string('addroom', 'mod_facetoface');
$PAGE->set_title($pageheading);
echo $OUTPUT->header();
echo $OUTPUT->heading($pageheading);

$mform->display();
echo $OUTPUT->footer();
