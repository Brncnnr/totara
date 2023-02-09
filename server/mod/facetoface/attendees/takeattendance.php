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
 * @author Francois Marier <francois@catalyst.net.nz>
 * @author Aaron Barnes <aaronb@catalyst.net.nz>
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_facetoface
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot.'/mod/facetoface/lib.php');
require_once($CFG->libdir.'/totaratablelib.php');
require_once($CFG->dirroot . '/totara/core/js/lib/setup.php');
require_once($CFG->libdir.'/odslib.class.php');
require_once($CFG->libdir.'/excellib.class.php');

use mod_facetoface\signup_helper;
use \mod_facetoface\attendees_helper;
use mod_facetoface\attendance\{attendance_helper, factory};

/** Load and validate base data */
// Face-to-face session ID
$s = optional_param('s', 0, PARAM_INT);
// Face-to-face sessiondate Id
$sd = optional_param('sd', 0, PARAM_INT);
// Take attendance
$takeattendance = optional_param('takeattendance', false, PARAM_BOOL);
// Cancel request
$cancelform = optional_param('cancelform', false, PARAM_BOOL);
// Action being performed, a proper default will be set shortly.
// Require for attendees.js
$action = optional_param('action', 'takeattendance', PARAM_ALPHA);
// Only return content
$onlycontent = optional_param('onlycontent', false, PARAM_BOOL);
// Export download.
$download = optional_param('download', '', PARAM_ALPHA);

// If there's no sessionid specified.
if (!$s) {
    attendees_helper::process_no_sessionid('takeattendance');
    exit;
}

$seminarevent = new \mod_facetoface\seminar_event($s);
$seminar = $seminarevent->get_seminar();
$cm = $seminar->get_coursemodule();
$context = $seminar->get_contextmodule($cm->id);
$session = (object)['id' => $seminarevent->get_id()];

require_login($seminar->get_course(), false, $cm);

/** Setup urls */
$baseurl = new moodle_url(
    '/mod/facetoface/attendees/takeattendance.php',
    ['s' => $seminarevent->get_id()]
);
if ($sd) {
    $baseurl->param('sd', $sd);
}
/** Print page header */
$PAGE->set_context($context);
$PAGE->set_url($baseurl);

[
    $allowed_actions,
    $available_actions,
    $staff,
    $admin_requests,
    $canapproveanyrequest,
    $cancellations,
    $requests,
    $attendees
] = attendees_helper::get_allowed_available_actions($seminar, $seminarevent, $context);
$includeattendeesnote = (has_any_capability(array('mod/facetoface:viewattendeesnote', 'mod/facetoface:manageattendeesnote'), $context));

$can_view_session = !empty($allowed_actions);
if (!$can_view_session) {
    // If no allowed actions so far.
    redirect(new moodle_url('/mod/facetoface/view.php', ['f' => $seminar->get_id()]));
}
// $allowed_actions is already set, so we can now know if the current action is allowed.
$actionallowed = in_array($action, $allowed_actions);

//Process the submitted data here
if ($formdata = data_submitted()) {
    if (!confirm_sesskey()) {
        redirect($baseurl, get_string('confirmsesskeybad', 'error'), null, core\notification::ERROR);
    }

    if ($cancelform) {
        redirect($baseurl);
    }

    // Take attendance.
    if ($actionallowed && $takeattendance) {
        // Check the attendance data matches the expected seminar event.
        if ($formdata->s != $seminarevent->get_id()) {
            redirect($baseurl, 'Mismatched attendance data handed through form submission.', null, core\notification::ERROR);
        }

        // Pre-process form data.
        $error = [];
        $states = [];
        $grades = [];
        foreach ($formdata as $key => $item) {
            $keyparts = explode('_', $key);
            /**
             * Every user on the form should have an entry in the formdata called
             * "submissionid_X" => $attendance value, but they should only be updated
             * if they also have a "Check_submissionid_X" record.
             */
            if ($keyparts[0] == 'submissionid') {
                if ($item == 110) {
                    continue; // Attendance value not set.
                }

                $states[$keyparts[1]] = (int)$item;
            } else if ($keyparts[0] == 'submissiongradeid') {
                if ($item === '') {
                    $grades[$keyparts[1]] = null;
                } else {
                    $val = unformat_float($item, true);
                    if ($val === false || $val < 0 || $val > 100) {
                        $error[] = get_string('eventgradingoutofrange', 'facetoface', ['min' => 0, 'max' => 100, 'val' => $item]);
                        continue;
                    }
                    $grades[$keyparts[1]] = (float)$item;
                }
            }
        }

        if (count($error)) {
            \core\notification::error(implode('<br>', $error));
            redirect($baseurl);
        } else {
            $result = false;
            if ($sd == 0) {
                $result = signup_helper::process_attendance($seminarevent, $states, $grades);
                if ($result) {
                    // Trigger take attendance update event.
                    \mod_facetoface\event\attendance_updated::create_from_session($session, $context)->trigger();
                }
            } else {
                $result = attendance_helper::process_session_attendance($states, $sd);
            }

            if ($result) {
                \core\notification::success(get_string('updateattendeessuccessful', 'mod_facetoface'));
            } else {
                \core\notification::error(get_string('error:takeattendance', 'facetoface'));
            }
            redirect($baseurl);
        }
    }
}

if (!$onlycontent) {
    $pagetitle = format_string($seminar->get_name());
    $PAGE->set_pagelayout('standard');
    $PAGE->set_title($pagetitle);
    $PAGE->set_cm($cm);
}

/**
 * Print page content
 */
if (!$onlycontent && !$download) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading($pagetitle);
    require_once($CFG->dirroot.'/mod/facetoface/attendees/tabs.php'); // If needed include tabs
    echo $OUTPUT->container_start('f2f-attendees-table');
}

//Print list attendees in taking attendance (if user able to view)
if ($actionallowed) {
    $helper = new attendees_helper($seminarevent);
    $numattendees = $helper->count_attendees();
    $overbooked = ($numattendees > $seminarevent->get_capacity());

    if ($numattendees == 0) {
        if ($seminar->is_approval_required()) {
            if (count($requests) == 1) {
                echo $OUTPUT->notification(get_string('nosignedupusersonerequest', 'facetoface'));
            } else {
                echo $OUTPUT->notification(get_string('nosignedupusersnumrequests', 'facetoface', count($requests)));
            }
        } else {
            echo $OUTPUT->notification(get_string('nosignedupusers', 'facetoface'));
        }
    } else {
        $attendancetracking = factory::get_attendance_tracking($seminarevent, $baseurl, $download, $context, $sd);
        $content = $attendancetracking->generate_content();
        echo $content;
    }
}

/**
 * Print page footer
 */
if (!$onlycontent) {
    echo $OUTPUT->container_end();

    $backurl = new \moodle_url('/mod/facetoface/view.php', ['f' => $seminar->get_id()]);
    /** @var mod_facetoface_renderer $f2f_renderer */
    $f2f_renderer = $PAGE->get_renderer('mod_facetoface');
    $f2f_renderer->setcontext($context);
    echo $f2f_renderer->render_action_bar_on_tabpage($backurl);

    echo $OUTPUT->footer();
    \mod_facetoface\event\attendees_viewed::create_from_session($session, $context, $action)->trigger();
}
