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

use message_totara_airnotifier\airnotifier_client;

/**
 * Tests message_totara_airnotifier air_notifier_client methods input validation.
 */
class air_notifier_client_testcase extends advanced_testcase {

    public function test_register_device() {
        // Test invalid device id
        $device_id = '';
        $result = airnotifier_client::register_device($device_id);
        $this->assertFalse($result);

        // Test valid device id
        $device_id = sha1('Kia ora');
        $result = airnotifier_client::register_device($device_id);
        $this->assertTrue($result);
    }

    public function test_delete_device() {
        // Test invalid device id
        $device_id = '';
        $result = airnotifier_client::delete_device($device_id);
        $this->assertFalse($result);

        // Test valid device id
        $device_id = sha1('Kia ora');
        $result = airnotifier_client::delete_device($device_id);
        $this->assertTrue($result);
    }

    public function test_push() {
        // Test invalid device id
        $device_id = '';
        $message = new \stdClass();
        $message->title = 'Hi!';
        $message->badge_count = 1;
        $result = airnotifier_client::push([$device_id], $message);
        $this->assertFalse($result);

        // Test invalid message
        $device_id = sha1('Kia ora');
        $message = new \stdClass();
        $message->title = '';
        $message->badge_count = 1;
        $result = airnotifier_client::push([$device_id], $message);
        $this->assertFalse($result);

        // Test valid device id and message
        $device_id = sha1('Kia ora');
        $message = new \stdClass();
        $message->title = 'Hi!';
        $message->badge_count = 1;
        $result = airnotifier_client::push([$device_id], $message);
        $this->assertTrue($result);
    }
}
