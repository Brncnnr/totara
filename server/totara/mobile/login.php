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
 * This is the second step of logging in via mobile device.
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
    util::send_error('Invalid login registration', 400);
}

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/totara/mobile/login.php');

if (!get_config('totara_mobile', 'enable')) {
    util::send_error('Invalid login registration', 400);
}

if (!isset($_POST)) {
    util::send_error('Invalid login registration', 400);
}

// Check that native authentication is allowed generally.
if (!util::native_auth_allowed()) {
    util::send_error('invalid login request', 400);
}

// Only allow this endpoint if Mobile > Mobile authentication 'Type of login' is 'Native'.
$authtype = get_config('totara_mobile', 'authtype');
if ($authtype != 'native') {
    util::send_error('invalid login request', 400);
}

$request = file_get_contents('php://input');

if (!$request) {
    util::send_error('Invalid registration request', 400);
}
$request = json_decode($request, true);
if (json_last_error() !== JSON_ERROR_NONE or $request === null) {
    util::send_error('Invalid registration request', 400);
}

// Check they have sent back a secret.
if (empty($request['loginsecret']) or !is_string($request['loginsecret'])) {
    util::send_error('Invalid registration request', 400);
}
$loginsecret = clean_param($request['loginsecret'], PARAM_ALPHANUM);

// Check they have sent back a username.
if (empty($request['username']) or !is_string($request['username'])) {
    util::send_error('Invalid registration request', 400);
}
$username = clean_param($request['username'], PARAM_USERNAME);

// Check they have sent back a password.
if (empty($request['password']) or !is_string($request['password'])) {
    util::send_error('Invalid registration request', 400);
}
$password = clean_param($request['password'], PARAM_RAW);

$setupsecret = device::login($loginsecret, $username, $password);
if (!$setupsecret) {
    util::send_error('Invalid registration request', 401);
}

$result = ['data' => ['setupsecret' => $setupsecret]];
util::send_response($result, 200);
