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

$id      = required_param('id', PARAM_INT);
$update  = required_param('update', PARAM_INT);
$confirm = required_param('confirm', PARAM_INT);

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

$url = new moodle_url('/mod/facetoface/notification/' . $page, array('update' => $update));
if (!$confirm) {
    redirect($url);
}

// Get all notifications.
$notifications = $DB->get_records('facetoface_notification', array('facetofaceid' => $facetoface->id), 'title,type');
// Count the number of system notifications by type.
// We want to allow duplicate system notifications to be deleted.
$autonotifications = array();
foreach ($notifications as $note) {
    if (!isset($autonotifications[$note->conditiontype])) {
        $autonotifications[$note->conditiontype] = 0;
    }
    if ($note->type != MDL_F2F_NOTIFICATION_AUTO) {
        continue;
    }
    $autonotifications[$note->conditiontype]++;
}
// If its not an auto notification OR if it is but there are duplicates allow the notification to be deleted.
if ($notification->type != MDL_F2F_NOTIFICATION_AUTO || $autonotifications[$notification->conditiontype] > 1) {
    // Delete the notification and associated sent and history records.
    $notification->delete();
    \core\notification::success(get_string('notificationdeleted', 'facetoface'));
}

redirect($url);
