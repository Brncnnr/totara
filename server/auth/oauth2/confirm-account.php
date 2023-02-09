<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Confirm self oauth2 user.
 *
 * @package    auth_oauth2
 * @copyright  2017 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->libdir . '/authlib.php');

$token = required_param('token', PARAM_RAW);
$linkid = required_param('linkid', PARAM_INT);

$PAGE->set_url('/auth/oauth2/confirm-account.php');
$PAGE->set_context(context_system::instance());

if (!is_enabled_auth('oauth2')) {
    print_error('notenabled', 'auth_oauth2', get_login_url());
}
if (!get_config('auth_oauth2', 'allowaccountcreation')) {
    print_error('loginerror_cannotcreateaccounts', 'auth_oauth2', get_login_url());
}

$confirmed = \auth_oauth2\api::confirm_new_account($linkid, $token);
if (!$confirmed) {
    print_error('confirmationinvalid', 'auth_oauth2', get_login_url());
}

// We MUST NOT login user here automatically, because the token is ignored if they click the link again.

$PAGE->navbar->add(get_string("confirmed"));
$PAGE->set_title(get_string("confirmed"));
$PAGE->set_heading($COURSE->fullname);
echo $OUTPUT->header();
echo $OUTPUT->box_start('generalbox centerpara boxwidthnormal boxaligncenter');
echo "<h3>".get_string("thanks")."</h3>\n";
echo "<p>".get_string("confirmed")."</p>\n";

if (!isloggedin() || isguestuser()) {
    // Prevent confusion on next login page access.
    unset($SESSION->loginerrormsg);
    $SESSION->wantsurl = $CFG->wwwroot . '/';
    echo $OUTPUT->single_button(get_login_url(), get_string('login'), 'get');
} else {
    echo $OUTPUT->single_button(new moodle_url('/login/logout.php', ['sesskey' => sesskey()]), get_string('login'));
}

echo $OUTPUT->box_end();
echo $OUTPUT->footer();
