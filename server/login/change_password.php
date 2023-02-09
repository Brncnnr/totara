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
 * Change password page.
 *
 * @package    core
 * @subpackage auth
 * @copyright  1999 onwards Martin Dougiamas  http://dougiamas.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../config.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once('change_password_form.php');
require_once($CFG->libdir.'/authlib.php');
require_once($CFG->dirroot.'/webservice/lib.php');
require_once($CFG->dirroot.'/user/editlib.php');

$id     = optional_param('id', SITEID, PARAM_INT); // current course
$return = optional_param('return', 0, PARAM_BOOL); // redirect after password change
$returnto = optional_param('returnto', '', PARAM_ALPHANUMEXT);  // Code determining where to return to after save/cancel.

$systemcontext = context_system::instance();

$PAGE->set_url('/login/change_password.php', array('id'=>$id, 'returnto'=>$returnto));

$PAGE->set_context($systemcontext);

if (!$course = $DB->get_record('course', array('id'=>$id))) {
    print_error('invalidcourseid');
}

if ($return) {
    // this redirect prevents security warning because https can not POST to http pages
    if ($returnto) {
        $returntourl = useredit_get_return_url($USER, $returnto, $course);
    } else if (empty($SESSION->wantsurl)
            or stripos(str_replace('https://', 'http://', $SESSION->wantsurl), str_replace('https://', 'http://', $CFG->wwwroot.'/login/change_password.php')) === 0) {
        $returntourl = "$CFG->wwwroot/user/preferences.php?userid=$USER->id&course=$id";
    } else {
        $returntourl = $SESSION->wantsurl;
    }
    unset($SESSION->wantsurl);

    redirect($returntourl);
}

$strparticipants = get_string('participants');

// require proper login; guest user can not change password
if (!isloggedin() or isguestuser()) {
    if (empty($SESSION->wantsurl)) {
        $SESSION->wantsurl = $CFG->wwwroot.'/login/change_password.php';
    }
    redirect(get_login_url());
}

$PAGE->set_context(context_user::instance($USER->id));
$PAGE->set_pagelayout('standard');
$PAGE->set_course($course);

// do not require change own password cap if change forced
if (!get_user_preferences('auth_forcepasswordchange', false)) {
    require_capability('moodle/user:changeownpassword', context_user::instance($USER->id));
} else {
    // Totara: remove any distractions that might lead user aways from changing of password.
    $PAGE->set_pagelayout('login');
}

// do not allow "Logged in as" users to change any passwords
if (\core\session\manager::is_loggedinas()) {
    print_error('cannotcallscript');
}

// load the appropriate auth plugin
$userauth = get_auth_plugin($USER->auth);

if (!$userauth->can_change_password()) {
    print_error('nopasswordchange', 'auth');
}

if ($changeurl = $userauth->change_password_url()) {
    // this internal scrip not used
    redirect($changeurl);
}

$mform = new login_change_password_form();
$mform->set_data(array('id'=>$course->id, 'returnto'=>$returnto));

$navlinks = array();
$navlinks[] = array('name' => $strparticipants, 'link' => "$CFG->wwwroot/user/index.php?id=$course->id", 'type' => 'misc');

if ($mform->is_cancelled()) {
    if ($returnto) {
        redirect(useredit_get_return_url($USER, $returnto, $course));
    }
    redirect($CFG->wwwroot.'/user/preferences.php?userid='.$USER->id.'&amp;course='.$course->id);
} else if ($data = $mform->get_data()) {

    if (!$userauth->user_update_password($USER, $data->newpassword1)) {
        print_error('errorpasswordupdate', 'auth');
    }

    user_add_password_history($USER->id, $data->newpassword1);

    if (!empty($CFG->passwordchangelogout)) {
        \core\session\manager::kill_user_sessions($USER->id, session_id());
    }

    if (!empty($data->signoutofotherservices)) {
        webservice::delete_user_ws_tokens($USER->id);
    }

    // Totara: always force users to login again after closing browser or normal session timeout.
    \totara_core\persistent_login::kill_user($USER->id);

    // Reset login lockout - we want to prevent any accidental confusion here.
    login_unlock_account($USER);

    // register success changing password
    unset_user_preference('auth_forcepasswordchange', $USER);
    unset_user_preference('create_password', $USER);

    $strpasswordchanged = get_string('passwordchanged');
    redirect(new moodle_url($PAGE->url, array('return'=>1)), $strpasswordchanged, null, \core\output\notification::NOTIFY_SUCCESS);

    exit;
}

$strchangepassword = get_string('changepassword');

$fullname = fullname($USER, true);

$PAGE->set_title($strchangepassword);
$PAGE->set_heading($fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading($strchangepassword);

if (get_user_preferences('auth_forcepasswordchange')) {
    echo $OUTPUT->notification(get_string('forcepasswordchangenotice'));
}
$mform->display();
echo $OUTPUT->footer();
