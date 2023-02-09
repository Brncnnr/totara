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

use message_totara_airnotifier\appcode_util;

require_once('../../../config.php');
require_once("$CFG->libdir/adminlib.php");
require_once("$CFG->dirroot/$CFG->admin/registerlib.php");

$url = new moodle_url('/' . $CFG->admin . '/settings.php?section=messagesettingtotara_airnotifier');

admin_externalpage_setup('managemessageoutputs');
require_capability('moodle/site:config', context_system::instance()); // Double check nobody changed the capability in settings.

if (!appcode_util::request_available()) {
    \core\notification::error(get_string('request_appcode_error:notdefault', 'message_totara_airnotifier'));
} else if (is_registration_required()) {
    \core\notification::error(get_string('request_appcode_error:notregistered', 'message_totara_airnotifier'));
} else {
    // Try to always finish this request without interruption.
    ignore_user_abort(true);

    $response = appcode_util::request_appcode();
    if (!empty($response['appcode'])) {
        set_config('totara_airnotifier_appcode', $response['appcode']);
        \core\notification::success(get_string('request_appcode_success', 'message_totara_airnotifier'));
    } else {
        $a = new \stdClass();
        if (!empty($response['error'])) {
            $a->error = $response['error'];
        } else {
            $a->error = 'Undefined error';
        }
        \core\notification::error(get_string('request_appcode_error:requestfail', 'message_totara_airnotifier', $a));
    }
}

// Always redirect
redirect($url);