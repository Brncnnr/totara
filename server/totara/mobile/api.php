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
use totara_webapi\graphql;

ini_set('display_errors', '0');
ini_set('log_errors', '1');

if (defined('NO_MOODLE_COOKIES')) {
    die;
}

// Make sure we can access methods of this utility class before setup runs or if it fails.
require(__DIR__ . '/classes/local/util.php');

if (!isset($_POST)) {
    util::send_error('Invalid Mobile API request', 401);
}

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
    util::send_error('Invalid Mobile API request', 401);
}

define('AJAX_SCRIPT', true);
define('NO_DEBUG_DISPLAY', true);
define('NO_MOODLE_COOKIES', true);
define('TOTARA_MOBILE_ACCESS', true);

try {
    require(__DIR__ . '/../../config.php');
    set_exception_handler([util::class, 'exception_handler']);
    set_error_handler([util::class, 'error_handler'], E_ALL | E_STRICT);
} catch (Throwable $e) {
    error_log('MOBILE API error: exception during set up stage - ' . $e->getMessage());
    util::send_error('Unknown internal error', 500);
}

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/totara/mobile/api.php');

if (!get_config('totara_mobile', 'enable')) {
    util::send_error('Invalid Mobile API request', 401);
}

$apikey = clean_param($raw_api_key, PARAM_RAW);

$device = device::find($apikey);
if (!$device) {
    util::send_error('Invalid Mobile API request', 401);
}

// Set up $USER global.
$user = $DB->get_record('user', array('id' => $device->userid), '*', MUST_EXIST);
core\session\manager::write_close(); // Make 100% sure we cannot affect normal sessions.
core\session\manager::set_user($user);
if (!empty($user->lang)) {
    // Make sure the session is using the userlanguage.
    $SESSION->lang = clean_param($user->lang, PARAM_LANG);
}

if (user_not_fully_set_up($user, false)) {
    util::send_error('User not fully set up', 401);
}

// Parse request.
$mobilerequestraw = file_get_contents('php://input');
if (!$mobilerequestraw) {
    util::send_error('Invalid Mobile API request, request must be a json encoded body with operationName and variables', 400);
}
$mobilerequest = json_decode($mobilerequestraw, true);
unset($mobilerequestraw);
if (json_last_error() !== JSON_ERROR_NONE or $mobilerequest === null) {
    util::send_error('Invalid Mobile API request, request must be a json encoded body with operationName and variables', 400);
}
if (empty($mobilerequest['operationName']) or !isset($mobilerequest['variables']) or !is_array($mobilerequest['variables'])) {
    util::send_error('Invalid Mobile API request, request must be a json encoded body with operationName and variables', 400);
}
$mobilerequest = fix_utf8($mobilerequest);
$operationname = $mobilerequest['operationName'];
$variables = $mobilerequest['variables'];

if (!preg_match('/^[a-z][a-z0-9_]+$/D', $operationname)) {
    util::send_error('Invalid Mobile API request, operation name is invalid', 400);
}

// Update app names and last access.
$update = new stdClass();
$update->id = $device->id;
$update->timelastaccess = time();
if (isset($mobilerequest['appName'])) {
    $update->appname = $mobilerequest['appName'];
    $update->appversion = $mobilerequest['appVersion'];
}
$DB->update_record('totara_mobile_devices', $update);

$ec = new \totara_mobile\webapi\execution_context($operationname, $device);
$result = graphql::execute_operation($ec, $variables);

$result->setErrorsHandler([util::class, 'graphql_error_handler']);
if (!empty($result->errors)) {
    util::send_response($result->toArray((bool) $CFG->debugdeveloper), 500);
} else {
    util::send_response($result->toArray((bool) $CFG->debugdeveloper), 200);
}
