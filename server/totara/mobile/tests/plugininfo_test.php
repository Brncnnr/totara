<?php
/*
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package totara_mobile
 */

use totara_mobile\plugininfo;

/**
 * @group totara_mobile
 */
class totara_mobile_plugininfo_testcase extends advanced_testcase {

    public function test_plugininfo_data() {
        $this->setAdminUser();

        $plugininfo = new plugininfo();

        $result = $plugininfo->get_usage_for_registration_data();
        $this->assertEquals(0, $result['mobileenabled']);
        $this->assertEquals(0, $result['numdevices']);
        $this->assertEquals(0, $result['numcompatiblecourses']);
        $this->assertEquals(0, $result['numofflinescorms']);
        $this->assertEquals(0, $result['airnotifierenabled']);
        $this->assertEquals(0, $result['customairnotifier']);

        $this->generate_data();

        $result = $plugininfo->get_usage_for_registration_data();
        $this->assertEquals(1, $result['mobileenabled']);
        $this->assertEquals(1, $result['numdevices']);
        $this->assertEquals(1, $result['numcompatiblecourses']);
        $this->assertEquals(1, $result['numofflinescorms']);
        $this->assertEquals(1, $result['airnotifierenabled']);
        $this->assertEquals(1, $result['customairnotifier']);
    }

    protected function generate_data() {
        global $DB;

        set_config('enable', true, 'totara_mobile');
        // Any appcode is treated as enabled.
        set_config('totara_airnotifier_appcode', 'abc123');
        // Custom airnotifier host (not default).
        set_config('totara_airnotifier_host', 'https://push.example.com');

        $u1 = $this->getDataGenerator()->create_user(['username' => 'user1']);

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

        // Set up some courses and enrolments for the last part of the data.
        $course = $this->getDataGenerator()->create_course(['shortname' => 'c1', 'fullname' => 'course1', 'summary' => 'first course']);
        $this->getDataGenerator()->enrol_user($u1->id, $course->id, 'student', 'manual');

        // Mark course as compatible with mobile.
        $todb = new \stdClass();
        $todb->courseid = $course->id;
        $DB->insert_record('totara_mobile_compatible_courses', $todb);

        // One example without offline allowed to test filtering.
        $this->getDataGenerator()->create_module('scorm', ['course' => $course, 'name' => 'c1sc1', 'allowmobileoffline' => 0]);
        $this->getDataGenerator()->create_module('scorm', ['course' => $course, 'name' => 'c1sc2', 'allowmobileoffline' => 1]);
    }
}