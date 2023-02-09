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
require_once($CFG->dirroot.'/mod/facetoface/lib.php');
require_once($CFG->dirroot.'/mod/facetoface/notification/lib.php');

use core\notification;
use mod_facetoface\bulk_list;
use mod_facetoface\seminar_event;
use mod_facetoface\attendees_list_helper;
use mod_facetoface\form\attendees_add_confirm;

// The number of users that should be shown per page.
define('USERS_PER_PAGE', 50);

$s      = required_param('s', PARAM_INT); // facetoface session ID
$listid = required_param('listid', PARAM_ALPHANUM); // Session key to list of users to add.
$page   = optional_param('page', 0, PARAM_INT); // Current page number.
$ignoreconflicts = optional_param('ignoreconflicts', false, PARAM_BOOL); // Ignore scheduling conflicts.

$seminarevent = new seminar_event($s);
$seminar = $seminarevent->get_seminar();
$cm = $seminar->get_coursemodule();
$context =  context_module::instance($cm->id);

$returnurl  = new moodle_url('/mod/facetoface/attendees/view.php', array('s' => $s));
$currenturl = new moodle_url('/mod/facetoface/attendees/list/addconfirm.php', array('s' => $s, 'listid' => $listid, 'page' => $page));

// Check essential permissions.
$list = new bulk_list($listid);
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

$pagetitle = get_string('addattendeestep2', 'mod_facetoface');
$PAGE->set_context($context);
$PAGE->set_url($currenturl);
$PAGE->set_cm($cm);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($seminar->get_name() . ': ' . $pagetitle);

$PAGE->requires->js_call_amd('mod_facetoface/attendees_addconfirm', 'init', array(array('s' => $s, 'listid' => $listid)));

// Selected users.
$userlist = $list->get_user_ids();
if (empty($userlist)) {
    redirect(
        $returnurl,
        get_string('updateattendeesunsuccessful', 'mod_facetoface'),
        null,
        notification::ERROR
    );
}

$isnotificationactive = facetoface_is_notification_active(MDL_F2F_CONDITION_BOOKING_CONFIRMATION, $seminar->get_id(), true);
$mform = new attendees_add_confirm(null, [
    's' => $s,
    'listid' => $listid,
    'isapprovalrequired' => $seminar->is_approval_required(),
    'enablecustomfields' => !$list->has_user_data(),
    'ignoreconflicts' => $ignoreconflicts,
    'is_notification_active' => $isnotificationactive
]);

if ($mform->is_cancelled()) {
    $list->clean();
    redirect($returnurl);
}

if ($fromform = $mform->get_data()) {
    attendees_list_helper::add($fromform);
    redirect($returnurl);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($pagetitle);

$jaselector = 0;
if (!empty($seminar->get_forceselectjobassignment())) {
    $jaselector = 2;
} else if (!empty($seminar->get_selectjobassignmentonsignup())) {
    $jaselector = 1;
}

$users = attendees_list_helper::get_user_list($userlist, $page, USERS_PER_PAGE);
$paging = new \paging_bar(count($userlist), $page, USERS_PER_PAGE, $currenturl);
// Table.
$f2frenderer = $PAGE->get_renderer('mod_facetoface');
echo $f2frenderer->render($paging);
echo $f2frenderer->print_userlist_table($users, $list, $seminarevent->get_id(), $jaselector);
echo $f2frenderer->render($paging);

$link = html_writer::link($list->get_returnurl(), get_string('changeselectedusers', 'mod_facetoface'), ['class' => 'btn btn-default']);
echo html_writer::div($link, 'form-group');

$mform->display();

echo $OUTPUT->footer();
