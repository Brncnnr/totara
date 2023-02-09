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
 * @package totara_mobile
 */

/*
 * This is a landing page that is displayed in mobile App webview used for initial device registration request.
 */

use totara_mobile\local\device;
use totara_mobile\local\util;

require('../../config.php');

$syscontext = context_system::instance();

$PAGE->set_context($syscontext);
$PAGE->set_url('/totara/mobile/device_request.php');
$PAGE->set_pagelayout('webview'); // No fancy UIs or navigation.

if (!util::is_mobile_webview() && empty($SESSION->device_emulation)) {
    util::webview_error();
}

if (!get_config('totara_mobile', 'enable')) {
    util::webview_error(get_string('errormobileunavailable', 'totara_mobile'));
}

if (!empty($SESSION->totara_mobile_device_registration_done)) {
    // This page can be used only once.
    if (isloggedin()) {
        require_logout();
    }
    // SSO logout might redirect away, let's print out something just in case.
    echo $OUTPUT->header();
    echo 'OK';
    echo $OUTPUT->footer();
    die;
}

if (!empty($_SERVER['HTTP_X_TOTARA_MOBILE_DEVICE_REGISTRATION'])) {
    $SESSION->totara_mobile_device_registration = true;
}
if (empty($SESSION->totara_mobile_device_registration)) {
    util::webview_error();
}

if (!isloggedin() or isguestuser()) {
    util::webview_error();
}

if (!has_capability('totara/mobile:use', context_user::instance($USER->id))) {
    util::webview_error(get_string('errormobileunavailable', 'totara_mobile'));
}

// Change password if user is forced to change it and the change is actually possible.
$userauth = get_auth_plugin($USER->auth);
if (get_user_preferences('auth_forcepasswordchange', false)) {
    $SESSION->wantsurl = $PAGE->url;
    if ($userauth->can_change_password()) {
        if ($changeurl = $userauth->change_password_url()) {
            redirect($changeurl);
        } else {
            require_once($CFG->dirroot . '/login/lib.php');
            redirect(new moodle_url('/login/change_password.php'));
        }
    }
}

unset($SESSION->totara_mobile_device_registration);
$SESSION->totara_mobile_device_registration_done = true;
$setupsecret = device::request();

echo $OUTPUT->header();
// TODO: make this pretty if users can see this
echo '<span data-totara-mobile-setup-secret="' . s($setupsecret) . '" id="totara_mobile-setup-secret">Registration request created</span>';
// NOTE: logout may do a redirect if SSO is involved, so stay here and let client redirect after getting the secret.
echo $OUTPUT->continue_button($PAGE->url);
echo $OUTPUT->footer();
