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
 * @author David Curry <david.curry@totaralearning.com>
 * @package totara_mobile
 */

/*
 * This is the first step of logging in via mobile device.
 */

use totara_mobile\local\device;
use totara_mobile\local\util;

ini_set('display_errors', '0');
ini_set('log_errors', '1');

define('AJAX_SCRIPT', true);
define('NO_DEBUG_DISPLAY', true);
define('NO_MOODLE_COOKIES', true);

try {
    require(__DIR__ . '/../../config.php');
} catch (Throwable $e) {
    util::send_error('Invalid registration request', 400);
}

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/totara/mobile/login_setup.php');

if (!get_config('totara_mobile', 'enable')) {
    util::send_error('invalid login request', 400);
}

// Note: This currently only works with manual auth, so if that isn't enabled... throw an error.
if (!is_enabled_auth('manual')) {
    util::send_error('invalid login request', 400);
}

// Only allow this endpoint if Mobile > Mobile authentication 'Type of login' is 'Native'.
$authtype = get_config('totara_mobile', 'authtype');
if ($authtype != 'native') {
    util::send_error('invalid login request', 400);
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
    util::send_error('invalid login request', 400);
}

if (isloggedin() or isguestuser()) {
    util::send_error('invalid login request', 400);
}

unset($SESSION->totara_mobile_device_registration);
$SESSION->totara_mobile_device_registration_done = true;
$loginsecret = device::login_setup();

// Return the secret to them so they can continue the login.
$result = ['data' => ['loginsecret' => $loginsecret]];
util::send_response($result, 200);
