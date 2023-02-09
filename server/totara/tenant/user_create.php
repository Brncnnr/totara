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
 * @package totara_tenant
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/lib/filelib.php');
require_once($CFG->dirroot . '/user/lib.php');

$tenantid = required_param('tenantid', PARAM_INT);

$PAGE->set_url('/totara/tenant/user_create.php', ['tenantid' => $tenantid]);
require_login();

$tenant = \core\record\tenant::fetch($tenantid);

$context = context_tenant::instance($tenant->id);
require_capability('totara/tenant:view', $context);
require_capability('totara/tenant:usercreate', $context);
if (empty($CFG->tenantsenabled)) {
    redirect(new moodle_url('/'));
}

$returnurl = new \moodle_url('/totara/tenant/participants.php', ['id' => $tenantid]);
$PAGE->set_context($context);

$user = new stdClass();
$user->id = -1;
$user->timezone = '99';
$user->tenantid = $tenant->id;

$editoroptions = array(
    'maxfiles' => 0,
    'maxbytes' => 0,
);
$filemanageroptions = array(
    'maxbytes'       => $CFG->maxbytes,
    'subdirs'        => 0,
    'maxfiles'       => 1,
    'accepted_types' => 'web_image');
file_prepare_draft_area($draftitemid, null, 'user', 'newicon', 0, $filemanageroptions);
$user->imagefile = $draftitemid;
$userform = new totara_tenant\form\user_create($PAGE->url, ['editoroptions' => $editoroptions, 'filemanageroptions' => $filemanageroptions, 'user' => $user]);

if ($userform->is_cancelled()) {
    redirect($returnurl);
}

if ($usernew = $userform->get_data()) {
    ignore_user_abort(true);

    unset($usernew->returnto);
    $createpassword = !empty($usernew->createpassword);
    unset($usernew->createpassword);

    $trans = $DB->start_delegated_transaction();

    $usernew = file_postupdate_standard_editor($usernew, 'description', $editoroptions, null, 'user', 'profile', null);
    $usernew->tenantid = $tenant->id;
    $usernew->auth = 'manual';
    $usernew->confirmed  = 1;
    if ($createpassword) {
        $usernew->password = '';
    } else {
        $usernew->password = $usernew->newpassword;
    }
    unset($usernew->newpassword);
    $usernew->id = user_create_user($usernew, !$createpassword, false);
    $usercontext = context_user::instance($usernew->id);
    if ($usercontext->tenantid != $tenant->id) {
        throw new coding_exception('Error creating tenant user');
    }

    // Update preferences.
    useredit_update_user_preference($usernew);

    // Update tags.
    if (isset($usernew->interests)) {
        useredit_update_interests($usernew, $usernew->interests);
    }

    // Update user picture.
    core_user::update_picture($usernew, $filemanageroptions);

    // Update forum track preference.
    useredit_update_trackforums($user, $usernew);

    // Save custom profile fields data.
    profile_save_data($usernew);

    // Trigger update/create event, after all fields are stored.
    core\event\user_created::create_from_userid($usernew->id)->trigger();

    $trans->allow_commit();

    if ($createpassword) {
        setnew_password_and_mail($usernew);
    }

    redirect($returnurl);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('createuser'));
$userform->display();
echo $OUTPUT->footer();
