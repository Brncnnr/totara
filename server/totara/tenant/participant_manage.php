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

use totara_tenant\local\util;

require(__DIR__ . '/../../config.php');

$userid = required_param('id', PARAM_INT);

$syscontext = context_system::instance();

$PAGE->set_url('/totara/tenant/participant_manage.php', ['id' => $userid]);
require_login();
require_capability('totara/tenant:manageparticipants', $syscontext);

$returnurl = new \moodle_url('/user/profile.php', ['id' => $userid]);

if (empty($CFG->tenantsenabled)) {
    redirect($returnurl);
}

$user = $DB->get_record('user', ['id' => $userid, 'deleted' => '0']);
if (!$user or isguestuser($user)) {
    redirect(new moodle_url('/'));
}
$usercontext = context_user::instance($user->id);
$currentuser = ($user->id == $USER->id);

$PAGE->set_context($usercontext);
$PAGE->set_title(get_string('participantmanage', 'totara_tenant'));
$PAGE->set_heading(fullname($user));

if (!$currentuser) {
    $PAGE->navigation->extend_for_user($user);
    $PAGE->navbar->add(get_string('participantmanage', 'totara_tenant'));
} else {
    // We are looking at our own profile.
    $myprofilenode = $PAGE->settingsnav->find('myprofile', null);
    $tenantinfo = $myprofilenode->add(get_string('participantmanage', 'totara_tenant'));
    $tenantinfo->make_active();
}

if ($user->tenantid) {
    $currentdata = new stdClass();
    $currentdata->id = $user->id;
    $currentdata->tenantid = $user->tenantid;
    $currentdata->tenantids = [$user->tenantid];
    $form = new totara_tenant\form\member_manage($currentdata);

    if ($form->is_cancelled()) {
        redirect($returnurl);
    }
    if ($data = $form->get_data()) {
        ignore_user_abort(true);
        if ($data->tenantid) {
            util::migrate_user_to_tenant($user->id, $data->tenantid);
            redirect($returnurl);
        } else {
            util::set_user_participation($user->id, $data->tenantids);
            redirect($returnurl);
        }
    }

} else {
    $currentdata = new stdClass();
    $currentdata->id = $user->id;
    $currentdata->tenantid = 0;
    $currentdata->tenantids = util::get_user_participation($userid);
    $form = new totara_tenant\form\other_manage($currentdata);

    if ($form->is_cancelled()) {
        redirect($returnurl);
    }
    if ($data = $form->get_data()) {
        ignore_user_abort(true);
        if ($data->tenantid) {
            util::migrate_user_to_tenant($user->id, $data->tenantid);
            redirect($returnurl);
        } else {
            util::set_user_participation($user->id, $data->tenantids);
            redirect($returnurl);
        }
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(fullname($user));
echo $form->render();
echo $OUTPUT->footer();
