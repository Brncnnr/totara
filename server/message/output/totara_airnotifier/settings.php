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

defined('MOODLE_INTERNAL') || die();
/** @var admin_root $ADMIN */

require_once("$CFG->dirroot/$CFG->admin/registerlib.php");

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext('totara_airnotifier_host', get_string('airnotifier_host', 'message_totara_airnotifier'), get_string('config_airnotifier_host', 'message_totara_airnotifier'), appcode_util::DEFAULT_HOST, PARAM_URL));
    $settings->add(new admin_setting_configtext('totara_airnotifier_appname', get_string('airnotifier_appname', 'message_totara_airnotifier'), get_string('config_airnotifier_appname', 'message_totara_airnotifier'), appcode_util::DEFAULT_APPNAME, PARAM_TEXT));
    $host = get_config(null, 'totara_airnotifier_host');
    $appname = get_config(null, 'totara_airnotifier_appname');
    $appcode = get_config(null, 'totara_airnotifier_appcode');
    if (appcode_util::request_available() && !is_registration_required()) {
        $a = new \stdClass();
        $url = new moodle_url('/message/output/totara_airnotifier/request_appcode.php');
        $a->url = $url->out();
        $appcode_description = get_string('config_airnotifier_appcode_registered', 'message_totara_airnotifier', $a);
    } else {
        $appcode_description = get_string('config_airnotifier_appcode', 'message_totara_airnotifier');
    }
    $settings->add(new admin_setting_configpasswordunmask('totara_airnotifier_appcode', get_string('airnotifier_appcode', 'message_totara_airnotifier'), $appcode_description, ''));
}
