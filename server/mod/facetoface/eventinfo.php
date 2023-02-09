<?php
/*
 * This file is part of Totara LMS
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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_facetoface
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');

use mod_facetoface\{seminar_event, signup, render_event_info_option};

$s = required_param('s', PARAM_INT); // {facetoface_sessions}.id
$cancelsignup = optional_param('cancelsignup', 0, PARAM_BOOL);
$action = optional_param('action', '', PARAM_ALPHA);

$seminarevent = new seminar_event($s);
$seminar = $seminarevent->get_seminar();
if (!$course = $DB->get_record('course', ['id' => $seminar->get_course()])) {
    print_error('error:incorrectcourseid', 'facetoface');
}
$cm = $seminar->get_coursemodule();
$context = context_module::instance($cm->id);

$returnurl = new moodle_url('/mod/facetoface/view.php', array('f' => $seminar->get_id()));

$PAGE->set_context($context);
$PAGE->set_pagelayout('noblocks');
$PAGE->set_cm($cm);

// User might have an ability to render the settings_navigation, and with settings_navigation, it
// requires the url of a page, therefore. PAGE should set url here first.
$pageurl = new moodle_url('/mod/facetoface/eventinfo.php', [
    's' => $seminarevent->get_id(),
]);
$PAGE->set_url($pageurl);

define('FACETOFACE_EVENTINFO_INTERNAL', true);
$signup = signup::create($USER->id, $seminarevent, MDL_F2F_BOTH, true);

if ($action === 'signup') {
    require_once(__DIR__ . '/signup.php');
} else if ($action === 'cancelsignup') {
    require_once(__DIR__ . '/cancelsignup.php');
} else {
    // Default to signup.
    require_once(__DIR__ . '/signup.php');
}

/**
 * @var mod_facetoface_renderer $seminarrenderer
 */
$seminarrenderer = $PAGE->get_renderer('mod_facetoface');
$seminarrenderer->setcontext($context);

echo $OUTPUT->header();

$signedup = !$signup->get_state()->is_not_happening();
$viewattendees = has_capability('mod/facetoface:viewattendees', $context);
$option = (new render_event_info_option())
    ->set_displaycapacity($viewattendees)
    ->set_calendaroutput(false)
    ->set_displaysignupinfo(!$signedup)
    ->set_heading($seminar->get_name())
    ->set_backurl($returnurl->out(false))
    ->set_pageurl($pageurl)
    ->set_backtoeventinfo(true)
    ->set_backtoallsessions(true);

echo $seminarrenderer->render_seminar_event_information($signup, $option, $cancelsignup);

echo $OUTPUT->footer();
