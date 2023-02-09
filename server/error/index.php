<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

// Error page to be used as a custom 404 set in server configuration.

require('../config.php');
require_once($CFG->libdir . '/eventslib.php');

$context = context_system::instance();
$title = get_string('pagenotexisttitle', 'error');
$site = get_site();
$PAGE->set_url('/error/index.php');
$PAGE->set_context($context);
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->add($title);

// This allows the webserver to dictate whether the http status should remain
// what it would have been, or force it to be a 404. Under other conditions
// it could most often be a 403, 405 or a 50x error.
$code = optional_param('code', 0, PARAM_INT);
if ($code == 404) {
    header("HTTP/1.0 404 Not Found");
    header("Status: 404 Not Found");
}

$can_message = has_capability('moodle/site:senderrormessage', $context);

$support_user = core_user::get_support_user();

// We can only message support if both the user has the capability
// and the support user is a real user.
if ($can_message) {
    $can_message = core_user::is_real_user($support_user->id);
}

$mform = new \core\form\error_feedback($CFG->wwwroot . '/error/index.php');

if ($data = $mform->get_data()) {
    if (!$can_message) {
        redirect($CFG->wwwroot);
    }

    // Send the message and redirect.
    $message = new \core\message\message();
    $message->courseid = SITEID;
    $message->component = 'moodle';
    $message->name = 'errors';
    $message->userfrom = $USER;
    $message->userto = core_user::get_support_user();
    $message->subject = 'Error: ' . $data->referer . ' -> ' . $data->requested;
    $message->fullmessage = $data->text;
    $message->fullmessageformat = FORMAT_PLAIN;
    $message->fullmessagehtml = '';
    $message->smallmessage = '';
    $message->contexturl = $data->requested;
    message_send($message);

    redirect($CFG->wwwroot, get_string('sendmessagesent', 'error', $data->requested), 5);
    exit;
}

echo $OUTPUT->header();
echo $OUTPUT->box(get_string('pagenotexist', 'error') . '<br />' . s($ME), 'generalbox boxaligncenter');

if ($can_message) {
    echo \html_writer::tag('h4', get_string('sendmessage', 'error'));
    $mform->display();
} else {
    echo $OUTPUT->continue_button($CFG->wwwroot);
}

echo $OUTPUT->footer();