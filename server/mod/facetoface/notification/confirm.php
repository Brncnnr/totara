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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package totara
 * @subpackage mod_facetoface
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot.'/mod/facetoface/lib.php');
require_once($CFG->dirroot.'/mod/facetoface/notification/lib.php');

$id     = required_param('id', PARAM_INT);
$update = required_param('update', PARAM_INT);
$action = required_param('action', PARAM_ALPHANUM);

if (!$cm = get_coursemodule_from_id('facetoface', $update)) {
    print_error('error:incorrectcoursemoduleid', 'facetoface');
}

if (!$course = $DB->get_record("course", array('id' => $cm->course))) {
    print_error('error:coursemisconfigured', 'facetoface');
}

require_login($course, true, $cm); // needed to setup proper $COURSE
$context = context_module::instance($cm->id);
require_capability('moodle/course:manageactivities', $context);

if (!$facetoface = $DB->get_record('facetoface', array('id' => $cm->instance))) {
    print_error('error:incorrectcoursemodule', 'facetoface');
}

if (!confirm_sesskey()) {
    print_error('confirmsesskeybad', 'facetoface');
}

if (!$notification = new facetoface_notification(array('id' => $id), true)) {
    print_error('error:notificationdoesnotexist', 'facetoface');
}

$page = $notification->type == MDL_F2F_NOTIFICATION_MANUAL ? 'adhoc.php' : 'index.php';
$url = new moodle_url('/mod/facetoface/notification/' . $page, array('update' => $cm->id));
switch($action) {
    case('delete'):
        $actionurl = new moodle_url('/mod/facetoface/notification/delete.php', array('update' => $cm->id));
        $actionstr = get_string('deletenotificationconfirm', 'facetoface', format_string($notification->title));
        break;
    case('copy'):
        $actionurl = new moodle_url('/mod/facetoface/notification/copy.php', array('update' => $cm->id));
        $actionstr = get_string('copynotificationconfirm', 'facetoface', format_string($notification->title));
        break;
    default:
        \core\notification::error(get_string('error:notificationdoesnotexist', 'facetoface'));
        redirect($url);
}

$actionurl->param('id', $id);
$actionurl->param('sesskey', sesskey());
$actionurl->param('confirm', '1');

$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('editinga', 'moodle', 'facetoface'));
$PAGE->set_heading($SITE->fullname);
echo $OUTPUT->header();
echo $OUTPUT->confirm($actionstr, $actionurl, $url);
echo $OUTPUT->footer($course);

