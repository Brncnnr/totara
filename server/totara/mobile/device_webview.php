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
 * This is page that starts webview session from a registered mobile app.
 */

use totara_mobile\local\util;

require('../../config.php');

$syscontext = context_system::instance();
$PAGE->set_context($syscontext);
$PAGE->set_url('/totara/mobile/device_webview.php');
$PAGE->set_pagelayout('redirect'); // No fancy UIs or navigation.

if (!util::is_mobile_webview()) {
    util::webview_error();
}

if (!get_config('totara_mobile', 'enable')) {
    util::webview_error(get_string('errormobileunavailable', 'totara_mobile'));
}

if (isloggedin()) {
    util::webview_error();
}

if (empty($_SERVER['HTTP_X_TOTARA_MOBILE_WEBVIEW_SECRET'])) {
    util::webview_error();
}
$secret = clean_param($_SERVER['HTTP_X_TOTARA_MOBILE_WEBVIEW_SECRET'], PARAM_ALPHANUM);

$webview = $DB->get_record('totara_mobile_webviews', ['secret' => $secret]);
if (!$webview) {
    util::webview_error();
}
$device = $DB->get_record('totara_mobile_devices', ['id' => $webview->deviceid]);
if (!$device) {
    util::webview_error();
}
$user = $DB->get_record('user', ['id' => $device->userid], '*', MUST_EXIST);
if (!$user) {
    util::webview_error();
}

$usercontext = context_user::instance($user->id, IGNORE_MISSING);
if (!$usercontext) {
    util::webview_error();
}
if (!has_capability('totara/mobile:use', $usercontext, $user->id)) {
    util::webview_error(get_string('errormobileunavailable', 'totara_mobile'));
}

// Interruptions and not welcome here, however we must not use transactions for security reasons.
ignore_user_abort(true);

// Prevent reuse of secrets.
if ($webview->timestarted) {
    util::webview_error();
}
$webview->timestarted = time();
$DB->update_record('totara_mobile_webviews', $webview);

// Guess if user would be able to log in.
if ($user->deleted or $user->suspended) {
    util::webview_error(get_string('errormobileunavailable', 'totara_mobile'));
}
$auths = get_enabled_auth_plugins();
if (!in_array($user->auth, $auths)) {
    util::webview_error(get_string('errormobileunavailable', 'totara_mobile'));
}

// Do a fake login, it is probably better to not use complete_user_login($user) here.
core\session\manager::login_user($user);
check_user_preferences_loaded($USER);
// TODO: add some new event for logging

// Store SID and redirect to test that session works.
$webview->sid = session_id();
$webview->sessionid = $DB->get_field('sessions', 'id', ['sid' => session_id()]);
$DB->update_record('totara_mobile_webviews', $webview);

// Add session flags.
$SESSION->forcepagelayout = 'webview';

redirect(new moodle_url($webview->url));
