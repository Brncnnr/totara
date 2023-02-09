<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author David Curry <david.curry@totaralearning.com>
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_facetoface
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');

use mod_facetoface\{signup_helper, signup, seminar, trainer_helper, render_event_info_option};

$s = required_param('s', PARAM_INT); // {facetoface_sessions}.id

$seminarevent = new \mod_facetoface\seminar_event($s);
$seminar = $seminarevent->get_seminar();
$course = $DB->get_record('course', ['id' => $seminar->get_course()], '*', MUST_EXIST);
$cm = $seminar->get_coursemodule();
$context = context_module::instance($cm->id);

$returnurl = new moodle_url('/course/view.php', ['id' => $course->id]);
if (isguestuser()) {
    redirect(
        $returnurl,
        get_string('error:cannotsignuptoeventasguest', 'mod_facetoface'),
        null,
        \core\notification::ERROR
    );
}

if (!empty($seminarevent->get_cancelledstatus())) {
    redirect(
        $returnurl,
        get_string('error:cannotsignupforacancelledevent', 'mod_facetoface'),
        null,
        \core\notification::ERROR
    );
}

if ($CFG->enableavailability) {
    if (!get_fast_modinfo($cm->course)->get_cm($cm->id)->available) {
        redirect(
            $returnurl,
            get_string('notavailablecourse', 'moodle', $seminar->get_name()),
            null,
            \core\notification::ERROR
        );
    }
}

$returnurl = new moodle_url('/enrol/index.php', ['id' => $course->id]);
// This is not strictly required for signup (more correctly it is checked in actor_has_role), but leaving it for early
// indication of the issue.
$trainerhelper = new trainer_helper($seminarevent);
$trainerroles = trainer_helper::get_trainer_roles(context_course::instance($course->id));
$trainers     = $trainerhelper->get_trainers();
if ($seminar->get_approvaltype() == seminar::APPROVAL_ROLE) {
    if (empty($trainerroles) || empty($trainers)) {
        redirect(
            $returnurl,
            get_string('error:missingrequiredrole', 'mod_facetoface'),
            null,
            \core\notification::ERROR
        );
    }
}

require_login();

$PAGE->set_context($context);

$signup = signup::create($USER->id, $seminarevent);
// Choose header depending on resulting state: waitlist or booked.
$heading = get_string('signupfor', 'mod_facetoface', $seminar->get_name());
$currentstate = $signup->get_state();
if (!$currentstate->can_switch(signup\state\booked::class) &&
    $currentstate->can_switch(signup\state\waitlisted::class)) {
    $heading = get_string('waitlistfor', 'mod_facetoface', $seminar->get_name());
}

$PAGE->set_pagelayout('noblocks');
$PAGE->set_cm($cm);
$PAGE->set_url(new moodle_url('/enrol/totara_facetoface/signup.php', ['s' => $s]));
$PAGE->set_title($heading);

local_js([TOTARA_JS_DIALOG, TOTARA_JS_TREEVIEW]);
$PAGE->requires->strings_for_js(['selectmanager'], 'mod_facetoface');
$jsmodule = [
    'name' => 'facetoface_managerselect',
    'fullpath' => '/mod/facetoface/js/manager.js',
    'requires' => ['json']
];
$selected_manager = dialog_display_currently_selected(get_string('currentmanager', 'mod_facetoface'), 'manager');
$args = [
    'userid' => $USER->id,
    'fid' => $seminar->get_id(),
    'manager' => $selected_manager,
    'sesskey' => sesskey()
];
$PAGE->requires->js_init_call('M.facetoface_managerselect.init', $args, false, $jsmodule);

$returnurl = new moodle_url('/mod/facetoface/eventinfo.php', array('s' => $seminarevent->get_id()));

$params = [
    'signup' => $signup,
    'backtoallsessions' => false
];
$mform = new \mod_facetoface\form\signup(null, $params, 'post', '', ['name' => 'signupform']);
if ($mform->is_cancelled()) {
    redirect($returnurl);
}
if ($fromform = $mform->get_data()) {
    if (empty($fromform->submitbutton)) {
        redirect(
            $returnurl,
            get_string('error:unknownbuttonclicked', 'mod_facetoface'),
            null,
            \core\notification::ERROR
        );
    }

    if (!is_enrolled($context, $USER)) {
        // Check for and attempt to enrol via the totara_facetoface enrolment plugin.
        $enrolments = enrol_get_plugins(true);
        $instances = enrol_get_instances($course->id, true);
        foreach ($instances as $instance) {
            if ($instance->enrol === 'totara_facetoface') {
                $data = clone($fromform);
                $data->sid = [$seminarevent->get_id()];
                $enrolments[$instance->enrol]->enrol_totara_facetoface($instance, $data, $course, $returnurl);
                // We expect enrol module to take all required sign up action and redirect, so it should never return.
                debugging("Seminar direct enrolment should never return to signup page");
                exit();
            }
        }
    }
}

echo $OUTPUT->header();

/**
 * @var mod_facetoface_renderer $seminarrenderer
 */
$seminarrenderer = $PAGE->get_renderer('mod_facetoface');
$seminarrenderer->setcontext($context);
$signedup = !$signup->get_state()->is_not_happening();
$viewattendees = has_capability('mod/facetoface:viewattendees', $context);

$option = (new render_event_info_option())
    ->set_displaycapacity($viewattendees)
    ->set_calendaroutput(false)
    ->set_displaysignupinfo(!$signedup)
    ->set_heading($heading)
    ->set_backurl($returnurl->out(false))
    ->set_backtoeventinfo(false)
    ->set_backtoallsessions(true);

echo $seminarrenderer->render_seminar_event_information($signup, $option, false);

echo $OUTPUT->footer();
