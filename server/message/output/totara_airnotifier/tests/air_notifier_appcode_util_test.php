<?php
/**
 * This file is part of Totara LMS
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

defined('MOODLE_INTERNAL') || die();

use message_totara_airnotifier\appcode_util;

/**
 * Tests message_totara_airnotifier air_notifier_client methods input validation.
 */
class air_notifier_appcode_util_testcase extends advanced_testcase {

    public function test_request_available() {
        $this->setAdminUser();

        // Get defaults.
        $default_host = get_config(null, 'totara_airnotifier_host');
        $default_appname = get_config(null, 'totara_airnotifier_appname');

        // Check defaults.
        $this->assertEquals($default_host, appcode_util::DEFAULT_HOST);
        $this->assertEquals($default_appname, appcode_util::DEFAULT_APPNAME);

        // All conditions met by default.
        $this->assertTrue(appcode_util::request_available());

        // Appcode already set.
        set_config('totara_airnotifier_appcode', '12345');
        $this->assertFalse(appcode_util::request_available());
        // Reset.
        set_config('totara_airnotifier_appcode', '');
        $this->assertTrue(appcode_util::request_available());

        // Not default host.
        set_config('totara_airnotifier_host', 'https://push.example.org/');
        $this->assertFalse(appcode_util::request_available());
        // Reset
        set_config('totara_airnotifier_host', $default_host);
        $this->assertTrue(appcode_util::request_available());

        // Not default appname.
        set_config('totara_airnotifier_appname', 'PrivateApp');
        $this->assertFalse(appcode_util::request_available());
    }

    public function test_request_appcode() {
        // Everything worked.
        $response = ['appcode' => 'THX-1138'];
        \curl::mock_response(json_encode($response));
        $this->assertEquals($response, appcode_util::request_appcode());

        // Something went wrong, predictably.
        $response = ['error' => 'Sorry, that didn\'t work'];
        \curl::mock_response(json_encode($response));
        $this->assertEquals($response, appcode_util::request_appcode());

        // Something went wrong, unpredictably.
        $response = ['bzzzt' => 'whut?'];
        \curl::mock_response(json_encode($response));
        $this->assertEquals(['error' => 'Unknown error'], appcode_util::request_appcode());
    }
}
