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
 * Display profile for a particular user
 *
 * @package core_user
 * @copyright 1999 Martin Dougiamas  http://dougiamas.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../config.php");
require_once($CFG->dirroot.'/user/profile/lib.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/badgeslib.php');

$id             = optional_param('id', 0, PARAM_INT); // User id.
$courseid       = optional_param('course', SITEID, PARAM_INT); // course id (defaults to Site).
$showallcourses = optional_param('showallcourses', 0, PARAM_INT);

// See your own profile by default.
if (empty($id)) {
    $id = $USER->id;
}

$user = $DB->get_record('user', array('id' => $id), '*');
if ($user === false || $user->deleted) {
    print_error('usernotavailable', 'error');
}

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

// Totara: Not logged-in users are not allowed into courses!
require_login();

$coursecontext = context_course::instance($course->id);

$PAGE->set_context($coursecontext);
$PAGE->set_url('/user/profile.php', array('id' => $id, 'course' => $courseid));

if ($courseid == SITEID) {   // Since Moodle 2.0 all site-level profiles are shown by profile.php.
    // Totara: this is needed because in some places suchas in user_picture class we are not checking if courseid is SITEID.
    redirect($CFG->wwwroot.'/user/profile.php?id='.$id);  // Immediate redirect.
}

$currentuser = ($user->id == $USER->id);
$usercontext = context_user::instance($user->id);

if (!empty($CFG->forceloginforprofiles)) {
    // Guests do not have permissions to view anyone's profile if forceloginforprofiles is set.
    if (isguestuser()) {
        echo $OUTPUT->header();
        echo $OUTPUT->confirm(get_string('guestcantaccessprofiles', 'error'),
                              get_login_url(),
                              $CFG->wwwroot);
        echo $OUTPUT->footer();
        die;
    }
}

// Check we are not trying to view guest's profile.
if (isguestuser($user)) {
    // Can not view profile of guest - thre is nothing to see there.
    print_error('usernotavailable', 'error');
}

$PAGE->set_course($course);
$PAGE->set_pagetype('course-view-' . $course->format);  // To get the blocks exactly like the course.
$PAGE->add_body_class('path-user');                     // So we can style it independently.
$PAGE->set_other_editing_capability('moodle/course:manageactivities');

// Set the Moodle docs path explicitly because the default behaviour
// of inhereting the pagetype will lead to an incorrect docs location.
$PAGE->set_docs_path('user/profile');

// Totara: Make sure user may view course profile.
if (!user_can_view_profile($user, $course)) {
    if (user_can_view_profile($user)) {
        redirect(new moodle_url('/user/profile.php', ['id' => $id]));
    }
    if (isguestuser()) {
        // Let them log in.
        redirect(get_login_url());
    }
    print_error('cannotviewprofile');
}

if (!$currentuser and has_capability('moodle/user:viewalldetails', $usercontext)) {
    // Totara: do not require staff managers assigned in user context to be enrolled in courses.
    $PAGE->navigation->set_userid_for_parent_checks($id);
} else {
    // Normal user, check user can access course.
    require_login($course);
}

$strpersonalprofile = get_string('personalprofile');
$strparticipants = get_string("participants");
$struser = get_string("user");

$fullname = fullname($user, has_capability('moodle/site:viewfullnames', $coursecontext));

// Totara: there is no need to check enrolment any more, user_can_view_profile() does it automatically!

if (!$currentuser) {
    // Somebody else.
    $PAGE->set_title("$strpersonalprofile: ");
    $PAGE->set_heading("$strpersonalprofile: ");
}

$PAGE->set_title("$course->fullname: $strpersonalprofile: $fullname");
$PAGE->set_privacy_aware_title("$course->fullname: $strpersonalprofile: " . get_string('userx', 'moodle', $user->id));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('standard');

// Locate the users settings in the settings navigation and force it open.
// This MUST be done after we've set up the page as it is going to cause theme and output to initialise.
if (!$currentuser) {
    $PAGE->navigation->extend_for_user($user);
    if ($node = $PAGE->settingsnav->get('userviewingsettings'.$user->id)) {
        $node->forceopen = true;
    }
} else if ($node = $PAGE->settingsnav->get('usercurrentsettings', navigation_node::TYPE_CONTAINER)) {
    $node->forceopen = true;
}
if ($node = $PAGE->settingsnav->get('courseadmin')) {
    $node->forceopen = false;
}

echo $OUTPUT->header();

echo '<div class="userprofile">';
$headerinfo = array('heading' => fullname($user), 'user' => $user, 'usercontext' => $usercontext);
echo $OUTPUT->context_header($headerinfo, 2);

// OK, security out the way, now we are showing the user.
// Trigger a user profile viewed event.
profile_view($user, $coursecontext, $course);

$hiddenfields = [];
if (!has_capability('moodle/user:viewhiddendetails', $coursecontext)) {
    $hiddenfields = array_flip(explode(',', $CFG->hiddenuserfields));
}

if ($user->description && !isset($hiddenfields['description'])) {
    echo '<div class="description">';
    if (!empty($CFG->profilesforenrolledusersonly) && !$DB->record_exists('role_assignments', array('userid' => $id))) {
        echo get_string('profilenotshown', 'moodle');
    } else {
        if ($courseid == SITEID) {
            $user->description = file_rewrite_pluginfile_urls($user->description, 'pluginfile.php', $usercontext->id, 'user', 'profile', null);
        } else {
            // We have to make a little detour thought the course context to verify the access control for course profile.
            $user->description = file_rewrite_pluginfile_urls($user->description, 'pluginfile.php', $coursecontext->id, 'user', 'profile', $user->id);
        }
        $options = array('overflowdiv' => true);
        echo format_text($user->description, $user->descriptionformat, $options);
    }
    echo '</div>'; // Description class.
}

// Render custom blocks.
$renderer = $PAGE->get_renderer('core_user', 'myprofile');
$tree = core_user\output\myprofile\manager::build_tree($user, $currentuser, $course);
echo $renderer->render($tree);

echo '</div>';  // Userprofile class.

echo $OUTPUT->footer();
