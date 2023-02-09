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
 * This scripts allows Mobile Apps to fetch new API key using the secret received from login webview.
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
$PAGE->set_url('/totara/mobile/device_register.php');

if (!get_config('totara_mobile', 'enable')) {
    util::send_error('Invalid registration request', 400);
}
if (!isset($_POST)) {
    util::send_error('Invalid registration request', 400);
}

$request = file_get_contents('php://input');

if (!$request) {
    util::send_error('Invalid registration request', 400);
}
$request = json_decode($request, true);
if (json_last_error() !== JSON_ERROR_NONE or $request === null) {
    util::send_error('Invalid registration request', 400);
}
if (empty($request['setupsecret']) or !is_string($request['setupsecret'])) {
    util::send_error('Invalid registration request', 400);
}

$setupsecret = clean_param($request['setupsecret'], PARAM_ALPHANUM);
$apikey = device::register($setupsecret);
if (!$apikey) {
    util::send_error('Invalid registration request', 400);
}

$result = ['data' => ['apikey' => $apikey, 'apiurl' => $CFG->wwwroot . '/totara/mobile/api.php', 'version' => util::get_api_version()]];
util::send_response($result, 200);
