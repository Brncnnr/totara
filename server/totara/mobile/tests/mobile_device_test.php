<?php
/*
 * This file is part of Totara LMS
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
 * @author David Curry <david.curry@totaralearning.com>
 * @package totara_mobile
 */

defined('MOODLE_INTERNAL') || die();

use totara_mobile\local\device;

/**
 * Tests the mobile device class.
 */
class totara_mobile_device_testcase extends advanced_testcase {

    /**
     * Test that the device deletion removes the mobile_device record
     * and triggers an fcm token removed event
     */
    public function test_mobile_device_deletion() {
        global $DB;

        $eventsink = $this->redirectEvents();

        // First create some users.
        $u1 = $this->getDataGenerator()->create_user(['username' => 'user1']);
        $u2 = $this->getDataGenerator()->create_user(['username' => 'user2']);

        // And mock up a device record.
        $now = time();
        $device = new \stdClass();
        $device->userid = $u1->id;
        $device->keyprefix = 'qweqweqwe';
        $device->keyhash = 'abcdefghijklmnopqrstuvwxyz';
        $device->timeregistered = $now;
        $device->timelastaccess = $now;
        $device->appname = 'applicationname';
        $device->appversion = '0.01';
        $device->fcmtoken = 'abc123';
        $device->id = $DB->insert_record('totara_mobile_devices', $device);

        $this->assertCount(1, $DB->get_records('totara_mobile_devices'));

        // Try delete the correct device for the wrong user.
        device::delete($u2->id, $device->id);
        $this->assertCount(1, $DB->get_records('totara_mobile_devices'));

        // Try delete the wrong device for the correct user.
        device::delete($u1->id, $device->id * 2);
        $this->assertCount(1, $DB->get_records('totara_mobile_devices'));

        // Finally delete the correct device for the correct user.
        device::delete($u1->id, $device->id);
        $this->assertCount(0, $DB->get_records('totara_mobile_devices'));

        $events = $eventsink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertEquals('\totara_mobile\event\fcmtoken_removed', $event->eventname);
        $eventsink->clear();
    }

}
