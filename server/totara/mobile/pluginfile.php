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

use totara_mobile\local\device;
use totara_mobile\local\util;

ini_set('display_errors', '0');
ini_set('log_errors', '1');

if (defined('NO_MOODLE_COOKIES')) {
    die;
}

// Make sure we can access methods of this utility class before setup runs or if it fails.
require(__DIR__ . '/classes/local/util.php');

// Get API key from HTTP header, prefer Authorization: Bearer mechanism.
$raw_api_key = false;
if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
    // Looks like 'Bearer <apikey>'
    $raw_api_key = substr($_SERVER['HTTP_AUTHORIZATION'], 7);
} else if (!empty($_SERVER['HTTP_X_API_KEY'])) {
    $raw_api_key = $_SERVER['HTTP_X_API_KEY'];
} else if (function_exists('apache_request_headers')) {
    // Apache doesn't always pass the Authorization header to PHP.
    $apache_headers = apache_request_headers();
    if (!empty($apache_headers['authorization'])) {
        $raw_api_key = substr($apache_headers['authorization'], 7);
    } else if (!empty($apache_headers['Authorization'])) {
        $raw_api_key = substr($apache_headers['Authorization'], 7);
    }
}

if (empty($raw_api_key)) {
    util::send_file_not_found();
}

define('NO_DEBUG_DISPLAY', true);
define('NO_MOODLE_COOKIES', true);
define('TOTARA_MOBILE_ACCESS', true);

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/totara/mobile/pluginfile.php');

if (!get_config('totara_mobile', 'enable')) {
    util::send_error('Invalid mobile file request', 401);
}

$apikey = clean_param($raw_api_key, PARAM_RAW);

$device = device::find($apikey);
if (!$device) {
    util::send_error('Invalid mobile file request', 401);
}

// Set up $USER global.
$user = $DB->get_record('user', array('id' => $device->userid), '*', MUST_EXIST);
core\session\manager::write_close(); // Make 100% sure we cannot affect normal sessions.
core\session\manager::set_user($user);

if (user_not_fully_set_up($user, false)) {
    util::send_error('User not fully set up', 401);
}

// Serve the file finally.
$relativepath = get_file_argument();
$forcedownload = optional_param('forcedownload', 0, PARAM_BOOL);
$preview = optional_param('preview', null, PARAM_ALPHANUM);
try {
    file_pluginfile($relativepath, $forcedownload, $preview);
} catch (Throwable $ex) {
    util::send_file_not_found($ex->getMessage());
}
