<?php
/**
 *  This file is part of Totara TXP
 *
 *  Copyright (C) 2022 onwards Totara Learning Solutions LTD
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 *
 */

use totara_webapi\controllers\external;
use totara_webapi\helper;

// Find out if we should start session, we do this to allow
// concurrent requests for resources like strings, templates and flex icons.

ini_set('display_errors', '0');
ini_set('log_errors', '1');

if (defined('NO_MOODLE_COOKIES')) {
    // This should not happen, dev must be trying to include this page from elsewhere.
    die;
}

define('NO_MOODLE_COOKIES', true);
define('NO_DEBUG_DISPLAY', true);
define('EXTERNAL_API', true);

require(__DIR__ . '/../totara/webapi/classes/helper.php');
helper::validate_environment();

require_once(__DIR__ . '/../config.php');
(new external())->process('graphql_request');
