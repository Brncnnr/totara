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
 * @author Oleg Demeshev <oleg.demeshev@totaralms.com>
 * @package totara
 * @subpackage facetoface
 */

define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');

$userid    = required_param('userid', PARAM_INT); // Facetoface signup user ID.
$sessionid = required_param('s', PARAM_INT); // Facetoface session ID.
$return = optional_param('return', 'view', PARAM_ALPHA);

$seminar = (new \mod_facetoface\seminar_event($sessionid))->get_seminar();
$cm = $seminar->get_coursemodule();
$context = context_module::instance($cm->id);

// Check essential permissions.
require_login($seminar->get_course(), true, $cm);

if (!has_capability('mod/facetoface:manageattendeesnote', $context)) {
    print_error('nopermissions', 'error', '', 'Update attendee note');
}

/* @var mod_facetoface_renderer|core_renderer $renderer */
$renderer = $PAGE->get_renderer('mod_facetoface');
$renderer->setcontext($context);
$seminarevent = new \mod_facetoface\seminar_event($sessionid);

// Get custom field values of the sign-up.
$signup = \mod_facetoface\signup::create($userid, $seminarevent, MDL_F2F_BOTH, true);
if (!$signup->exists()) {
    throw new coding_exception(
        "No user with ID: {$USER->id} has signed-up for the Seminar event ID: {$seminarevent->get_id()}."
    );
}
$archived = $signup->get_archived();

$attendeenote = new \stdClass();
$attendeenote->userid = $userid;
$attendeenote->id = $signup->get_id();
$attendeenote->sessionid = $sessionid;

$customfields = customfield_get_data($attendeenote, 'facetoface_signup', 'facetofacesignup');

// Prepare output.
$usernamefields = get_all_user_name_fields(true);
$user = \core_user::get_user($userid, $usernamefields);
if ($archived) {
    $output = get_string('usernoteheadingarchived', 'mod_facetoface', fullname($user));
} else {
    $output = get_string('usernoteheading', 'mod_facetoface', fullname($user));
}
$output .= html_writer::empty_tag('hr');
if (!empty($customfields)) {
    foreach ($customfields as $cftitle => $cfvalue) {
        $output .= html_writer::tag('strong', str_replace(' ', '&nbsp;', $cftitle) . ': ') . html_writer::span($cfvalue);
        $output .= html_writer::empty_tag('br');
    }
} else {
    $output .= get_string('none');
}
if (!$archived) {
    $output .= html_writer::empty_tag('hr');
    $params = [
        's' => $sessionid,
        'userid'  => $userid,
        'return'  => $return,
        'sesskey' => sesskey()
    ];
    $output .= $renderer->single_button(
        new moodle_url('/mod/facetoface/attendees/edit_signup_notes.php', $params),
        get_string('edit'),
        'post'
    );
}

header('Content-type: text/html; charset=utf-8');
echo $output;
