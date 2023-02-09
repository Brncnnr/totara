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
 * @package modules
 * @subpackage facetoface
 */

use mod_facetoface\{signup, signup_helper};
use mod_facetoface\signup\state\{waitlisted, user_cancelled};

// Direct access to this page will be transferred to eventinfo.php
if (!defined('FACETOFACE_EVENTINFO_INTERNAL')) {
    require(__DIR__ . '/../../config.php');
    redirect(new \moodle_url('/mod/facetoface/eventinfo.php', [
        's' => required_param('s', PARAM_INT),
        'cancelsignup' => 1
    ]));
}

defined('MOODLE_INTERNAL') || die();

require_login($course, false, $cm);
require_capability('mod/facetoface:view', $context);

$signup = signup::create($USER->id, $seminarevent);
if (!$signup->exists()) {
    throw new coding_exception(
        "No user with ID: {$USER->id} has signed-up for the Seminar event ID: {$seminarevent->get_id()}."
    );
}

// Check user's eligibility to cancel.
$currentstate = $signup->get_state();
if (!$currentstate->can_switch(signup\state\user_cancelled::class)) {
    redirect($pageurl, get_string('error:cancellationsnotallowed', 'facetoface'), null, \core\notification::ERROR);
}

$currentstate = $signup->get_state();
$userisinwaitlist = $currentstate instanceof waitlisted;
$pagetitle = format_string($seminar->get_name());

$seminarrenderer = $PAGE->get_renderer('mod_facetoface');

$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('noblocks');

$cancellation_note = new stdClass();
$cancellation_note->id = $signup->get_id();
customfield_load_data($cancellation_note, 'facetofacecancellation', 'facetoface_cancellation');

$mform = new \mod_facetoface\form\cancelsignup(null, compact('s', 'cancellation_note', 'userisinwaitlist'));
if ($mform->is_cancelled()) {
    redirect($pageurl);
}

if ($fromform = $mform->get_data()) { // Form submitted.

    if (empty($fromform->submitbutton)) {
        print_error('error:unknownbuttonclicked', 'facetoface', $returnurl);
    }

    // Attempt to switch the signup state.
    if (signup_helper::can_user_cancel($signup)) {
        signup_helper::user_cancel($signup);
        // Update cancellation custom fields.
        $fromform->id = $signup->get_id();
        customfield_save_data($fromform, 'facetofacecancellation', 'facetoface_cancellation');

        // Page notification box.
        $message = $userisinwaitlist ? get_string('waitlistcancelled', 'facetoface')
            : get_string('bookingcancelled', 'facetoface');
        if ($userisinwaitlist === false) {
            $error = \mod_facetoface\notice_sender::signup_cancellation($signup);
            if (empty($error)) {
                $minstart = $seminarevent->get_mintimestart();
                $seminar_id = $seminarevent->get_facetoface();

                if ($minstart) {
                    $message .= html_writer::empty_tag('br') . html_writer::empty_tag('br');
                    $managers = signup_helper::find_managers_from_signup($signup);

                    if (!empty($managers) && \mod_facetoface\notice_sender::is_cc_to_manager_when_cancel_signup($seminar_id)) {
                        // If the sign up user has manager, and the notification is set to cc manager then the message
                        // notification banner should mention about CC to manager.
                        $message .= get_string('cancellationsentmgr', 'facetoface');
                    } else {
                        $message .= get_string('cancellationsent', 'facetoface');
                    }
                } else {
                    $msg = ($CFG->facetoface_notificationdisable ? 'cancellationnotsent' : 'cancellationsent');
                    $message .= html_writer::empty_tag('br') . html_writer::empty_tag('br') . get_string($msg, 'facetoface');
                }
            } else {
                print_error($error, 'facetoface');
            }
        }

        \core\notification::success($message);
        redirect($pageurl);
    } else {
        $failures = $signup->get_failures(user_cancelled::class);
        throw new coding_exception("Could not cancel user signup.", implode("\n", $failures));
    }
}

