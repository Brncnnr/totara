<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author David Curry <david.curry@totaralearning.com>
 * @package totara_mobile
 */

defined('MOODLE_INTERNAL') || die();

use message_totara_airnotifier\event\fcmtoken_rejected;

/**
 * Tests the mobile observer class.
 */
class totara_mobile_observer_testcase extends advanced_testcase {

    /**
     * Set up some users with devices.
     */
    public function create_user_devices() : array {
        global $DB;
        set_config('enable', true, 'totara_mobile');

        $now = time();
        $devices = [];
        for ($i = 1; $i <= 3; $i++) {
            $user = $this->getDataGenerator()->create_user(['username' => "user{$i}"]);

            // And mock up a device record.
            $now = time();
            $device = new \stdClass();
            $device->userid = $user->id;
            $device->keyprefix = 'pre' . $user->id;
            $device->keyhash = 'testkeyhash';
            $device->timeregistered = $now;
            $device->timelastaccess = $now;
            $device->appname = 'testappname';
            $device->appversion = '0.00';
            $device->fcmtoken = 'testtoken' . $user->id;
            $device->id = $DB->insert_record('totara_mobile_devices', $device);

            $devices[$user->id] = $device;
        }

        return $devices;
    }

    /**
     * Test the mobile plugins reaction to a token rejection event.
     * It should remove the token from any devices and throw a further
     * token removed event for the devices.
     */
    public function test_fcmtoken_rejected_observer() {
        global $DB;

        $now = time();
        $devices = $this->create_user_devices();

        $this->assertCount(5, $DB->get_records('user')); // Remember the admin and guest accounts.
        $this->assertCount(3, $DB->get_records('totara_mobile_devices'));
        $device = array_pop($devices);

        // Trigger the event.
        fcmtoken_rejected::create_from_token($device->fcmtoken)->trigger();

        // Make sure the right one is  gone, and the other two are still there.
        $this->assertCount(5, $DB->get_records('user'));
        $this->assertCount(3, $DB->get_records('totara_mobile_devices'));
        $this->assertEmpty($DB->get_field('totara_mobile_devices', 'fcmtoken', ['id' => $device->id]));

        foreach ($devices as $uid => $dev) {
            $this->assertTrue($DB->record_exists('totara_mobile_devices', ['fcmtoken' => $dev->fcmtoken]));
        }
    }

}
