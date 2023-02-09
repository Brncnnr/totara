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

use mod_facetoface\bulk_list;
use mod_facetoface\seminar_event;
use mod_facetoface\attendees_list_helper;
use mod_facetoface\form\attendees_remove_confirm;

// The number of users that should be shown per page.
define('USERS_PER_PAGE', 50);

$s      = required_param('s', PARAM_INT); // facetoface session ID
$listid = required_param('listid', PARAM_ALPHANUM); // Session key to list of users to add.
$page   = optional_param('page', 0, PARAM_INT); // Current page number.

$seminarevent = new seminar_event($s);
$seminar = $seminarevent->get_seminar();
$cm = $seminar->get_coursemodule();
$context =  context_module::instance($cm->id);

$returnurl  = new moodle_url('/mod/facetoface/attendees/view.php', array('s' => $s));
$currenturl = new moodle_url('/mod/facetoface/attendees/list/removeconfirm.php', array('s' => $s, 'listid' => $listid, 'page' => $page));

// Check essential permissions.
require_login($seminar->get_course(), false, $cm);
require_capability('mod/facetoface:removeattendees', $context);

$pagetitle = get_string('removeattendeestep2', 'mod_facetoface');
$PAGE->set_context($context);
$PAGE->set_url($currenturl);
$PAGE->set_cm($cm);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($seminar->get_name() . ': ' . $pagetitle);

try {
    $list = new bulk_list($listid);
} catch (invalid_parameter_exception $e) {
    // This can happen when re-sending the form and the bulk list is already removed from the session.
    // In that case the processing has already been done, and we just redirect without error message.
    redirect($returnurl);
}

// Selected users.
$userlist = $list->get_user_ids();
if (empty($userlist)) {
    \core\notification::error(get_string('updateattendeesunsuccessful', 'mod_facetoface'));
    redirect($returnurl);
}

$isnotificationactive = facetoface_is_notification_active(MDL_F2F_CONDITION_CANCELLATION_CONFIRMATION, $seminar->get_id(), true);
$mform = new attendees_remove_confirm(null, [
    's' => $s,
    'listid' => $listid,
    'enablecustomfields' => !$list->has_user_data(),
    'is_notification_active' => $isnotificationactive
]);
$mform->enable_double_submit_detection(md5($listid));

if ($mform->is_cancelled()) {
    $list->clean();
    redirect($returnurl);
}

if ($fromform = $mform->get_data()) {
    if (!$mform->is_double_submit_detected()) {
        $fromform->users = $mform->get_user_list($userlist);
        attendees_list_helper::remove($fromform);
        $mform->mark_submit_as_processed();
    }
    redirect($returnurl);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($pagetitle);

$users = $mform->get_user_list($userlist, $page, USERS_PER_PAGE);
$paging = new paging_bar(count($userlist), $page, USERS_PER_PAGE, $currenturl);
// Table.
$f2frenderer = $PAGE->get_renderer('mod_facetoface');
echo $f2frenderer->render($paging);
echo $f2frenderer->print_userlist_table($users);
echo $f2frenderer->render($paging);

$link = html_writer::link($list->get_returnurl(), get_string('changeselectedusers', 'mod_facetoface'), ['class' => 'btn btn-default']);
echo html_writer::div($link,'form-group');

$mform->display();
echo $OUTPUT->footer();
