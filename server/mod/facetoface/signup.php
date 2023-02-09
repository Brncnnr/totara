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
 * @author David Curry <david.curry@totaralearning.com>
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_facetoface
 */

use mod_facetoface\{signup_helper, signup, seminar, trainer_helper, seminar_event_helper};

// Direct access to this page will be transferred to eventinfo.php
if (!defined('FACETOFACE_EVENTINFO_INTERNAL')) {
    require(__DIR__ . '/../../config.php');
    redirect(new \moodle_url('/mod/facetoface/eventinfo.php', [
        's' => required_param('s', PARAM_INT)
    ]));
}

defined('MOODLE_INTERNAL') || die();

/** @var mod_facetoface\seminar $seminar            declared in eventinfo.php */
/** @var mod_facetoface\seminar_event $seminarevent declared in eventinfo.php */
/** @var mod_facetoface\signup $signup              declared in eventinfo.php */
/** @var stdClass $course                           declared in eventinfo.php */
/** @var stdClass $cm                               declared in eventinfo.php */
/** @var context_module $context                    declared in eventinfo.php */
/** @var moodle_url $pageurl                        declared in eventinfo.php */

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

if (!seminar_event_helper::is_available($seminarevent)) {
    redirect(
        $returnurl,
        get_string('notavailablecourse', 'moodle', $seminar->get_name()),
        null,
        \core\notification::ERROR
    );
}

// This is not strictly required for signup (more correctly it is checked in actor_has_role), but leaving it for early
// indication of the issue.
$helper = new trainer_helper($seminarevent);
$trainerroles = trainer_helper::get_trainer_roles(context_course::instance($course->id));
$trainers     = $helper->get_trainers();
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

require_login($course, false, $cm);
require_capability('mod/facetoface:view', $context);

// Choose header depending on resulting state: waitlist or booked.
$currentstate = $signup->get_state();
$heading = get_string('signupfor', 'mod_facetoface', $seminar->get_name());
if ($currentstate instanceof signup\state\booked ||
    $currentstate instanceof signup\state\requested ||
    $currentstate instanceof signup\state\waitlisted) {
    $heading = $seminar->get_name();
}
if (!$currentstate->can_switch(signup\state\booked::class) &&
    $currentstate->can_switch(signup\state\waitlisted::class)) {
    $heading = get_string('waitlistfor', 'mod_facetoface', $seminar->get_name());
}

$PAGE->set_title($heading);
$PAGE->set_pagelayout('noblocks');

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

$params = [
    'signup' => $signup,
];
$mform = new \mod_facetoface\form\signup(null, $params, 'post', '', ['name' => 'signupform']);
if ($mform->is_cancelled()) {
    redirect($pageurl);
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

    $signup->set_notificationtype($fromform->notificationtype);
    $signup->set_discountcode($fromform->discountcode);
    $signup->set_bookedby(null);

    $managerselect = get_config(null, 'facetoface_managerselect');
    if ($managerselect && isset($fromform->managerid) && !empty($fromform->managerid)) {
        $signup->set_managerid($fromform->managerid);
    } else {
        $signup->set_managerid(null);
    }

    $f2fselectedjobassignmentelemid = 'selectedjobassignment_' . $seminar->get_id();
    if (property_exists($fromform, $f2fselectedjobassignmentelemid)) {
        $signup->set_jobassignmentid($fromform->$f2fselectedjobassignmentelemid);
    } else {
        $signup->set_jobassignmentid(null);
    }

    if (signup_helper::can_signup($signup)) {
        signup_helper::signup($signup);

        // Custom fields.
        $fromform->id = $signup->get_id();
        customfield_save_data($fromform, 'facetofacesignup', 'facetoface_signup');

        // Notification.
        $state = $signup->get_state();
        $message = $state->get_message();
        $notificationtype = \core\notification::INFO;

        // There may be lots of factors that will prevent confirmation message to appear at user mailbox
        // but in most cases this will be true:
        if ($state instanceof signup\state\booked) {
            $notificationtype = \core\notification::SUCCESS;
            if (!$signup->get_skipusernotification() && $fromform->notificationtype != MDL_F2F_NONE) {
                $message .= html_writer::empty_tag('br') . html_writer::empty_tag('br') .
                    get_string('confirmationsent', 'mod_facetoface');
            }
        }
    } else {
        // Note - We can't use the renderer_signup_failures() function here, but this is the same.
        $failures = signup_helper::get_failures($signup);
        reset($failures);
        $message = current($failures);
        $notificationtype = \core\notification::ERROR;
    }

    redirect($pageurl, $message, null, $notificationtype);
}
