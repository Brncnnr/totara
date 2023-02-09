<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package core_user
 */

require_once('../config.php');
require_once("$CFG->dirroot/lib/authlib.php");
require_once("$CFG->dirroot/user/editlib.php");
require_once("$CFG->dirroot/user/lib.php");

$id = required_param('id', PARAM_INT);
$returnto = optional_param('returnto', '', PARAM_ALPHANUMEXT);
$customreturn = optional_param('returnurl', '', PARAM_LOCALURL);

require_login();
$context = context_user::instance($id);
require_capability('moodle/user:managelogin', $context);

$user = $DB->get_record('user', array('id' => $id, 'deleted' => 0, 'confirmed' => 1), '*', MUST_EXIST);
$returnurl = useredit_get_return_url($user, $returnto, null, $customreturn);
$currentuser = ($USER->id == $user->id);

if (isguestuser($user)) {
    redirect($returnurl);
}

if (!is_siteadmin() && is_siteadmin($user)) {
    // No editing of admins unless user is an admin.
    redirect($returnurl);
}

$title = get_string('manageuserloginaction', 'totara_core', fullname($user));

$PAGE->set_context($context);
$PAGE->set_url('/user/managelogin.php', array('id' => $id, 'returnto' => $returnto, 'returnurl' => $customreturn));
$PAGE->set_pagelayout('admin');
$PAGE->set_title($title);
$PAGE->set_heading(fullname($user));

if (!$currentuser) {
    $PAGE->navigation->extend_for_user($user);
    $PAGE->navbar->add(get_string('manageuserlogin', 'totara_core'));
} else {
    // We are looking at our own profile.
    $myprofilenode = $PAGE->settingsnav->find('myprofile', null);
    $userinfo = $myprofilenode->add(get_string('manageuserlogin', 'totara_core'));
    $userinfo->make_active();
}

$auth = get_auth_plugin($user->auth);

$currentdata = [
    'id' => $user->id,
    'suspended' => $user->suspended,
    'returnto' => $returnto,
    'returnurl' => $customreturn,
];
$form = new core_user\form\manage_login($currentdata, ['user' => $user, 'auth' => $auth]);

if ($form->is_cancelled()) {
    redirect($returnurl);
}

if ($data = $form->get_data()) {
    if (empty($data->action)) {
        redirect($returnurl);
    }

    if ($data->action === 'unlock') {
        login_unlock_account($user);
        redirect($returnurl);

    } else if ($data->action === 'suspend') {
        if ($currentuser || is_siteadmin($user->id)) {
            redirect($returnurl);
        }
        user_suspend_user($user->id);
        redirect($returnurl);

    } else if ($data->action === 'unsuspend') {
        user_unsuspend_user($user->id);
        redirect($returnurl);

    } else if ($data->action === 'changepassword') {
        if (!user_change_password($user->id, $data->newpassword, ['forcepasswordchange' => !empty($data->forcepasswordchange)])) {
            print_error('cannotupdatepasswordonextauth', '', $returnurl, $user->auth);
        }
        redirect($returnurl);

    } else if ($data->action === 'createpassword') {
        if ($currentuser) {
            redirect($returnurl);
        }
        if (!$auth->is_internal()) {
            redirect($returnurl);
        }
        setnew_password_and_mail($user, false, false);

        redirect($returnurl);
    }

    redirect($returnurl);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($title);

echo $form->render();

echo $OUTPUT->footer();