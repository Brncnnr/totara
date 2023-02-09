<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2023 onwards Totara Learning Solutions LTD
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
 * @author Chris Snyder <chris.snyder@totara.com>
 * @package tool_smtp_test
 */

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->libdir.'/adminlib.php');

admin_externalpage_setup('tool_smtp_test');

$mform = new \tool_smtp_test\smtp_test_form();

$site = get_site();

$a = new \stdClass();
$a->email = $USER->email;
$a->sitename = format_string($site->fullname);
$a->admin = generate_email_signoff();

// Set default values
$default = new \stdClass();
$default->to = $a->email;
$default->subject = get_string('test_subject', 'tool_smtp_test', $a);
$default->message = get_string('test_message', 'tool_smtp_test', $a);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'tool_smtp_test'));

if ($data = $mform->get_data()) {
    // Temporarily show ALL debugging messages.
    $was_cfg = ['debug' => $CFG->debug, 'debugdisplay' => $CFG->debugdisplay, 'debugsmtp' => $CFG->debugsmtp];
    $CFG->debug = DEBUG_ALL;
    $CFG->debugdisplay = true;
    $CFG->debugsmtp = true;

    $user = $DB->get_record('user', ['email' => $data->to]);

    // Send a test email.
    if (\tool_smtp_test\smtp_test::send_test_email($data->to, $data->subject, $data->message, $user)) {
        echo $OUTPUT->notification(get_string('test_success', 'tool_smtp_test'), 'notifysuccess');
    }
    else {
        echo $OUTPUT->notification(get_string('test_failure', 'tool_smtp_test'), 'notifyproblem');
    }

    // Reset debugging.
    foreach ($was_cfg as $key => $value) {
        $CFG->{$key} = $value;
    }
}
else {
    $mform->set_data($default);
}

echo $OUTPUT->notification(get_string('infomessage', 'tool_smtp_test'), 'notifymessage');

$mform->display();

echo $OUTPUT->footer();
