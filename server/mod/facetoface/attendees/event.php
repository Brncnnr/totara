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

use \mod_facetoface\attendees_helper;
use \mod_facetoface\facilitator_list;

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot.'/mod/facetoface/lib.php');
require_once($CFG->dirroot . '/totara/core/js/lib/setup.php');

// Face-to-face session ID
$s = optional_param('s', 0, PARAM_INT);
// If there's no sessionid specified.
if (!$s) {
    attendees_helper::process_no_sessionid($action);
    exit;
}
$action = 'event';
$seminarevent = new \mod_facetoface\seminar_event($s);
$seminar = $seminarevent->get_seminar();
$cm = $seminar->get_coursemodule();
$context = $seminar->get_contextmodule($cm->id);

// Allow facilitators to be able to view event details, without being enrolled in the course.
// Will check everyone else below.
require_login();

$baseurl = new moodle_url('/mod/facetoface/attendees/event.php', ['s' => $seminarevent->get_id()]);
$PAGE->set_context($context);
$PAGE->set_url($baseurl);

$user_is_facilitator = false;
$facilitators = facilitator_list::from_seminarevent($seminarevent->get_id());
foreach ($facilitators as $f => $facilitator) {
    if ($facilitator->get_userid() == $USER->id) {
        $user_is_facilitator = true;
        break;
    }
}

// Anyone who isn't a facilitator must be enrolled or able to view the course.
if (!$user_is_facilitator) {
    require_login($seminar->get_course(), false, $cm);
}

// Generate page header.
list($allowed_actions, $available_actions, $staff, $admin_requests, $canapproveanyrequest, $cancellations, $requests, $attendees)
    = attendees_helper::get_allowed_available_actions($seminar, $seminarevent, $context);
// $allowed_actions is already set, so we can now know if the current action is allowed.
$seminarurl = new moodle_url('/mod/facetoface/view.php', ['f' => $seminar->get_id()]);
if (!$user_is_facilitator && !in_array($action, $allowed_actions)) {
    redirect($seminarurl);
}

$PAGE->set_cm($cm);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($seminar->get_name() . ': ' . get_string('eventdetails', 'mod_facetoface'));

// Print page content.
echo $OUTPUT->header();
echo $OUTPUT->heading($seminar->get_name());

require_once($CFG->dirroot.'/mod/facetoface/attendees/tabs.php'); // If needed include tabs

/** @var mod_facetoface_renderer $renderer */
$renderer = $PAGE->get_renderer('mod_facetoface');
$renderer->setcontext($context);
if (!(bool)$seminarevent->get_cancelledstatus()) {
    echo $renderer->render_editevent_button($seminarevent);
}
echo $renderer->render_seminar_event($seminarevent, true, false, true);
echo $renderer->render_action_bar_on_tabpage($seminarurl);
echo $OUTPUT->footer();

\mod_facetoface\event\attendees_viewed::create_from_session((object)['id' => $seminarevent->get_id()], $context, $action)->trigger();
