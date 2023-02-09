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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package message_totara_airnotifier
 */

/*
 * This page uses JavaScript to simulate the requests made by a Totata Mobile App,
 * for development and testing purposes.
 *
 * Developers can set $CFG->mobile_device_emulator = 1 to enable manual use.
 */

use message_totara_airnotifier\airnotifier_mock_server;

try {
    require('../../../config.php');
} catch (Throwable $e) {
    airnotifier_mock_server::response('400 Not Found');
}

// Only allow behat
if (!defined('BEHAT_SITE_RUNNING') || !BEHAT_SITE_RUNNING) {
    die();
}

if (empty($_GET) || empty($_GET['api'])) {
    airnotifier_mock_server::response('500 Bad Request');
}

$request = json_decode(file_get_contents('php://input'));

switch ($_GET['api']) {
    case 'push':
        airnotifier_mock_server::push($request);
        break;

    case 'register_device':
        airnotifier_mock_server::register_device($request);
        break;

    case 'delete_device':
        airnotifier_mock_server::delete_device($request);
        break;
}

// Should never get here, invalid request.
airnotifier_mock_server::response('500 Bad Request');
