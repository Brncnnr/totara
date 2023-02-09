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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package totara_mobile
 */

/*
 * This script provides the Totara Mobile App with the local settings it needs to get started.
 */

use totara_mobile\local\util;

ini_set('display_errors', '0');
ini_set('log_errors', '1');

define('AJAX_SCRIPT', true);
define('NO_DEBUG_DISPLAY', true);
define('NO_MOODLE_COOKIES', true);

try {
    require(__DIR__ . '/../../config.php');
} catch (Throwable $e) {
    util::send_error('Invalid request', 400);
}

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/totara/mobile/site_info.php');

if (!get_config('totara_mobile', 'enable')) {
    util::send_error('Invalid request', 400);
}
if (!isset($_POST)) {
    util::send_error('Invalid site_info request', 400);
}

$request = file_get_contents('php://input');

if (!$request) {
    util::send_error('Invalid site_info request', 400);
}
$request = json_decode($request, true);
if (json_last_error() !== JSON_ERROR_NONE or $request === null) {
    util::send_error('Invalid site_info request', 400);
}
if (empty($request['version']) or !is_string($request['version'])) {
    util::send_error('Invalid site_info request', 400);
}

$app_version = clean_param($request['version'], PARAM_TEXT);
if (empty($app_version)) {
    util::send_error('Invalid site_info request', 400);
}

$siteinfo = util::get_site_info($app_version);
$result = ['data' => $siteinfo];

util::send_response($result, 200);
