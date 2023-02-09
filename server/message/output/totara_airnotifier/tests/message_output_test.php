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
 * @author David Curry <david.curry@totaralearning.com>
 * @package message_totara_airnotifier
 */

defined('MOODLE_INTERNAL') || die();

use totara_mobile\local\device;

global $CFG;
require_once($CFG->dirroot . '/message/output/totara_airnotifier/message_output_totara_airnotifier.php');

/**
 * Tests airnotifier event triggers and observers
 */
class air_notifier_message_output_testcase extends advanced_testcase {

    private $device_count = 0;

    public function setUp(): void {
        $this->device_count = 0;
        set_config('enable', true, 'totara_mobile');

        parent::setUp();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->device_count = null;
    }

    /**
     * Create a mock device
     *
     * To mock various responses use different values for the fcmtoken:
     *     mock_valid_*   - This will mock a valid response
     *     mock_invalid_* - This will mock an invalid response
     *     anything else  - This will skip reponse validation
     *
     * @param int    $userid    - The user of the device
     * @param string $fcm_token - The fcmtoken for the device
     * @param int    $time      - Override the time registered and lastaccessed
     * @return stdClass
     */
    private function mock_device(int $userid, string $fcm_token = 'abc123', $time = null): stdClass {
        global $DB;

        if (empty($time)) {
            $time = time();
        }

        $device = new stdClass();
        $device->userid = $userid;
        $device->keyprefix = 'qweqweqwe' . $this->device_count;
        $device->keyhash = 'abcdefghijklmnopqrstuvwxyz' . $this->device_count;
        $device->timeregistered = $time;
        $device->timelastaccess = $time;
        $device->appname = 'applicationname';
        $device->appversion = '0.01';
        $device->fcmtoken = $fcm_token;
        $device->id = $DB->insert_record('totara_mobile_devices', $device);

        $this->device_count++;
        return $device;
    }

    /**
     * Test the message send functionality.
     * Note; This test also covers the hook and event used by message_send
     */
    public function test_air_notifier_message_send() {
        // Create user and set the as the current user.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user->id);

        // And mock up a device record.
        $device = $this->mock_device($user->id, 'abc123');

        // Mock some message data.
        $mockdata = new \stdClass();
        $mockdata->userto = $user;
        $mockdata->userfrom = $user;
        $mockdata->subject = 'Test message';
        $mockdata->smallmessage = 'smallmessage';
        $mockdata->fullmessage = 'Big message with lots of words in it.';
        $mockdata->courseid = 1;
        $mockdata->component = 'test';

        $output = new message_output_totara_airnotifier();
        $result = $output->send_message($mockdata);
        $this->assertTrue($result);

        // Note: This returns true but really fails to push, to test this further (eventsink, messagesink)
        //       we'd need to fake the push success, or set up a test server to hit.
    }

    /**
     * Test the message send with varying tokens.
     */
    public function test_air_notifier_message_token_invalidation() {
        global $DB;

        // Create a control user and devices.
        $user_control = $this->getDataGenerator()->create_user();
        $device_control_valid = $this->mock_device($user_control->id, 'mock_valid_1_' . $user_control->id);
        $device_control_invalid = $this->mock_device($user_control->id, 'mock_invalid_2_' . $user_control->id);

        // Create user and set the as the current user.
        $user_test = $this->getDataGenerator()->create_user();
        $this->setUser($user_test->id);

        // Test devices.
        $devices = [];
        $devices[] = $this->mock_device($user_test->id, 'mock_valid_1_' . $user_test->id);
        $devices[] = $this->mock_device($user_test->id, 'mock_400_2_' . $user_test->id);
        $devices[] = $this->mock_device($user_test->id, 'mock_valid_3_' . $user_test->id);
        $devices[] = $this->mock_device($user_test->id, 'mock_403_4_' . $user_test->id);
        $devices[] = $this->mock_device($user_test->id, 'mock_valid_5_' . $user_test->id);
        $devices[] = $this->mock_device($user_test->id, 'mock_404_6_' . $user_test->id);

        // Mock some message data.
        $mockdata = new \stdClass();
        $mockdata->userto = $user_test;
        $mockdata->userfrom = $user_test;
        $mockdata->subject = 'Test message';
        $mockdata->smallmessage = 'smallmessage';
        $mockdata->fullmessage = 'A full message';
        $mockdata->courseid = 1;
        $mockdata->component = 'test';

        $this->assertCount(2, $DB->get_records('totara_mobile_devices', ['userid' => $user_control->id]));
        $this->assertCount(6, $DB->get_records('totara_mobile_devices', ['userid' => $user_test->id]));

        $output = new message_output_totara_airnotifier();
        $result = $output->send_message($mockdata);
        $this->assertTrue($result); // Note: push() should be false but send_message() will always be true.

        // There should be the same amount of devices.
        $this->assertSame(2, $DB->count_records('totara_mobile_devices', ['userid' => $user_control->id]));
        $this->assertSame(6, $DB->count_records('totara_mobile_devices', ['userid' => $user_test->id]));

        // The two control devices should be untouched.
        $control_valid = $DB->get_record('totara_mobile_devices', ['id' => $device_control_valid->id]);
        $this->assertSame($device_control_valid->keyprefix, $control_valid->keyprefix);
        $this->assertEquals($device_control_valid->userid, $control_valid->userid);
        $this->assertSame($device_control_valid->fcmtoken, $control_valid->fcmtoken);

        $control_invalid = $DB->get_record('totara_mobile_devices', ['id' => $device_control_invalid->id]);
        $this->assertSame($device_control_invalid->keyprefix, $control_invalid->keyprefix);
        $this->assertEquals($device_control_invalid->userid, $control_invalid->userid);
        $this->assertSame($device_control_invalid->fcmtoken, $control_invalid->fcmtoken);

        // But mock_invalid records should have their fcm token nulled out for our test user.
        foreach ($devices as $device) {
            $record = $DB->get_record('totara_mobile_devices', ['id' => $device->id]);
            $this->assertSame($device->keyprefix, $record->keyprefix);
            $this->assertEquals($device->userid, $record->userid);

            if (preg_match('/^mock_valid_.*/', $device->fcmtoken)) {
                // Valid tokens should all still be happily sitting in the database.
                $this->assertSame($device->fcmtoken, $record->fcmtoken);
            } else {
                // Invalid tokens should have been expunged from the database.
                $this->assertSame(null, $record->fcmtoken);
            }
        }
    }

    /**
     * Test the system configuration.
     */
    public function test_air_notifier_system_configuration() {
        $output = new message_output_totara_airnotifier();

        $this->assertFalse($output->is_system_configured());
        set_config('totara_airnotifier_host', 'http://localtest.com');
        $this->assertFalse($output->is_system_configured());
        set_config('totara_airnotifier_appname', 'apptest');
        $this->assertFalse($output->is_system_configured());
        set_config('totara_airnotifier_appcode', 'abc123');
        $this->assertTrue($output->is_system_configured());
    }

    /**
     * Test the air notifier defaults
     */
    public function test_air_notifier_defaults() {
        $output = new message_output_totara_airnotifier();

        $default = $output->get_default_messaging_settings();
        $this->assertEquals(11, $default); // MESSAGE_PERMITTED + MESSAGE_DEFAULT_LOGGEDIN + MESSAGE_DEFAULT_LOGGEDOFF;
    }

    /**
     * Not super important but quickly check the abstract functions
     */
    public function test_abstract_function_implementations() {
        $output = new message_output_totara_airnotifier();

        $mock = null;
        $this->assertNull($output->config_form(null));
        $this->assertTrue($output->process_form(null, $mock));
        $this->assertTrue($output->load_data($mock, null));
    }
 }
