<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package totara_engage
 */
use totara_core\advanced_feature;
use totara_engage\engage_core;
use core\notification;

require_once(__DIR__ . '/../../config.php');
global $USER, $OUTPUT, $PAGE;

require_login();
advanced_feature::require('engage_resources');

$user_id = required_param('user_id', PARAM_INT);

// Bounce the active user to their own page
if ($user_id == $USER->id) {
    $your_resource_url = new moodle_url("/totara/engage/your_resources.php");
    redirect($your_resource_url);
    exit;
}

$user = core_user::get_user($user_id, '*', MUST_EXIST);
$user_fullname = fullname($user);

$target_context = context_user::instance($user->id);
$tui = null;

// Set page properties.
$PAGE->set_context($target_context);
$PAGE->set_url(new moodle_url('/totara/engage/user_resources.php'));
$PAGE->set_pagelayout('legacynolayout');

if (engage_core::allow_access_with_tenant_check($target_context, $USER->id)) {
    $PAGE->set_title(get_string('usersresources', 'totara_engage', $user_fullname));
    $PAGE->set_privacy_aware_title(get_string('usersresources', 'totara_engage', get_string('userx', 'moodle', $user_id)));

    $tui = new \totara_tui\output\component(
        'totara_engage/pages/OtherUserLibrary',
        [
            'id' => 'userslibrary',
            'name' => fullname($user),
            'userId' => $user_id,
            'pageId' => 'userslibrary',
        ]
    );
    $tui->register($PAGE);
}

echo $OUTPUT->header();

if (null !== $tui) {
    echo $OUTPUT->render($tui);
} else {
    notification::error(get_string('error:view_user_resources', 'totara_engage'));
}

echo $OUTPUT->footer();