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
 * @author Keelin Devenney <keelin@learningpool.com>
 * @package mod_facetoface
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');

use \mod_facetoface\seminar_event;
use \core\output\notification;

$s = required_param('s', PARAM_INT); // facetoface session ID
$backtoallsessions = optional_param('backtoallsessions', 1, PARAM_BOOL);

$seminarevent = new seminar_event($s);
$seminar = $seminarevent->get_seminar();
$cm = $seminar->get_coursemodule();
$context =  $seminar->get_contextmodule($cm->id);

require_login($seminar->get_course(), false, $cm);
require_capability('mod/facetoface:editevents', $context);

$pagetitle = get_string('cancelingsession', 'mod_facetoface', $seminar->get_name());
$baseurl = new moodle_url('/mod/facetoface/events/cancel.php', ['s' => $s, 'backtoallsessions' => $backtoallsessions]);

$PAGE->set_cm($cm);
$PAGE->set_url($baseurl);
$PAGE->set_context($context);
$PAGE->set_title($pagetitle);
$PAGE->set_pagelayout('standard');

if ($backtoallsessions) {
    $returnurl = new moodle_url('/mod/facetoface/view.php', array('f' => $seminarevent->get_facetoface()));
} else {
    $returnurl = new moodle_url('/course/view.php', ['id' => $seminar->get_course()]);
}

if (!$seminarevent->is_cancellable()) {
    // How did they get here? There should not be any link in UI to this page.
    redirect($returnurl, get_string('error:cannoteditcancelledevent', 'mod_facetoface'), null, notification::NOTIFY_ERROR);
}

$mform = new \mod_facetoface\form\cancelsession(
    null,
    ['backtoallsessions' => $backtoallsessions, 'seminarevent' => $seminarevent],
    'post',
    '',
    ['class' => 'mform_seminarevent_cancellation']
);
if ($mform->is_cancelled()) {
    redirect($returnurl);
}

if ($fromform = $mform->get_data()) {
    // This may take a long time...
    ignore_user_abort(true);

    if ($seminarevent->cancel()) {
        // Save the custom fields.
        if ($fromform) {
            $fromform->id = $seminarevent->get_id();
            customfield_save_data($fromform, 'facetofacesessioncancel', 'facetoface_sessioncancel');
        }
        redirect($returnurl, get_string('bookingsessioncancelled', 'mod_facetoface'), null, notification::NOTIFY_SUCCESS);
    }
    redirect($returnurl, get_string('error:couldnotcancelsession', 'mod_facetoface'), null, notification::NOTIFY_ERROR);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($pagetitle);

/** @var mod_facetoface_renderer $seminarrenderer */
$seminarrenderer = $PAGE->get_renderer('mod_facetoface');
echo $seminarrenderer->render_seminar_event($seminarevent, true);

$mform->display();

echo $OUTPUT->footer();
