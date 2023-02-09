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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author David Curry <david.curry@totaralearning.com>
 * @package totara_mobile
 */

defined('MOODLE_INTERNAL') || die();

use totara_webapi\phpunit\webapi_phpunit_helper;
use totara_mobile\local\device;
use \totara_mobile\webapi\execution_context as mobile_context;

/**
 * Test GraphQL resolver of mobile queries
 */
class totara_mobile_webapi_resolver_mutation_set_fcmtoken_testcase extends advanced_testcase {

    use webapi_phpunit_helper;

    /**
     * @param stdClass $device - a record from totara_mobile_devices
     * @param array    $args   - the array of arguments for the execution
     *
     * @return boolean
     */
    private function resolve($device, $args) : bool {
        $ec = new mobile_context('totara_mobile_set_fcmtoken', $device);
        $result = \totara_webapi\graphql::execute_operation($ec, $args);
        return $result->data['set_fcmtoken'];
    }

    /**
     * Create and return 3 devices for separate users.
     *
     * @return array
     */
    private function create_faux_devices() : array {
        $this->setUser(null);

        $users = [];
        for ($index = 1; $index <= 3; $index++) {
            $user = $this->getDataGenerator()->create_user([
                'username' => 'user' . $index,
                'password' => 'M0bil3'
            ]);

            $this->setUser($user->id);
            $secret = device::request();
            $apikey = device::register($secret);

            $users[] = $user;
        }

        return $users;
    }

    /**
     * Test that setting a token will fail without the variable, but will allow reseting for empty strings.
     */
    public function test_resolve_set_fcm_missing_and_empty() {
        global $DB;

        $eventsink = $this->redirectEvents();
        $users = $this->create_faux_devices();

        $u1 = array_pop($users);
        $device = $DB->get_record('totara_mobile_devices', ['userid' => $u1->id]);
        $this->assertNotFalse($device); // The device should exist.
        $this->assertNotEmpty($device->id); // The id for the device should be set.
        $this->assertEmpty($device->fcmtoken); // The fcmtoken however should not be set yet.

        // Set the fcmtoken.
        $this->setUser($u1->id);
        $result = $this->resolve($device, []);
        $this->assertFalse($result);

        // Check the fcm token has been added.
        $device = $DB->get_record('totara_mobile_devices', ['userid' => $u1->id]);
        $this->assertNotEmpty($device);
        $this->assertEmpty($device->fcmtoken);

        // Set the fcmtoken.
        $result = $this->resolve($device, ['token' => 'abc123']);
        $this->assertTrue($result);

        // Check the fcm token has been added.
        $device = $DB->get_record('totara_mobile_devices', ['userid' => $u1->id]);
        $this->assertNotEmpty($device);
        $this->assertEquals('abc123', $device->fcmtoken);

        // Check that setting the token triggered an event.
        $events = $eventsink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertEquals('\totara_mobile\event\fcmtoken_received', $event->eventname);
        $this->assertEquals($u1->id, $event->userid);
        $eventsink->clear();

        // Update the token with an empty string.
        $result = $this->resolve($device, ['token' => '']);
        $this->assertTrue($result);

        // Check the fcm token has been updated.
        $device = $DB->get_record('totara_mobile_devices', ['userid' => $u1->id]);
        $this->assertNotEmpty($device);
        $this->assertEmpty($device->fcmtoken);

        // Check that setting the token triggered an event.
        $events = $eventsink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertEquals('\totara_mobile\event\fcmtoken_received', $event->eventname);
        $this->assertEquals($u1->id, $event->userid);
        $eventsink->clear();

        // Make sure setting another users token to '' won't break unique indexing.
        $u2 = array_pop($users);
        $this->setUser($u2->id);
        $device = $DB->get_record('totara_mobile_devices', ['userid' => $u2->id]);
        $this->assertNotFalse($device); // The device should exist.
        $this->assertNotEmpty($device->id); // The id for the device should be set.
        $this->assertEmpty($device->fcmtoken); // The fcmtoken however should not be set yet.

        // Update the token with an empty string.
        $result = $this->resolve($device, ['token' => '']);
        $this->assertTrue($result);

        // Check the fcm token has been updated.
        $device = $DB->get_record('totara_mobile_devices', ['userid' => $u2->id]);
        $this->assertNotEmpty($device);
        $this->assertEmpty($device->fcmtoken);

        // Check that setting the token triggered an event.
        $events = $eventsink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertEquals('\totara_mobile\event\fcmtoken_received', $event->eventname);
        $this->assertEquals($u2->id, $event->userid);
        $eventsink->clear();
    }

    /**
     * Test that a device can set a token, update it, and the previous token can be reused.
     */
    public function test_resolve_set_fcm_token() {
        global $DB;

        $eventsink = $this->redirectEvents();
        $users = $this->create_faux_devices();

        $u1 = array_pop($users);
        $device = $DB->get_record('totara_mobile_devices', ['userid' => $u1->id]);
        $this->assertNotFalse($device); // The device should exist.
        $this->assertNotEmpty($device->id); // The id for the device should be set.
        $this->assertEmpty($device->fcmtoken); // The fcmtoken however should not be set yet.

        // Set the fcmtoken.
        $this->setUser($u1->id);
        $this->resolve($device, ['token' => 'abc123']);

        // Check the fcm token has been added.
        $device = $DB->get_record('totara_mobile_devices', ['userid' => $u1->id]);
        $this->assertNotEmpty($device);
        $this->assertEquals('abc123', $device->fcmtoken);

        // Override the fcmtoken.
        $this->resolve($device, ['token' => '321zxy']);

        // Check the fcm token has been updated.
        $device = $DB->get_record('totara_mobile_devices', ['userid' => $u1->id]);
        $this->assertNotEmpty($device);
        $this->assertEquals('321zxy', $device->fcmtoken);

        // Check the other users' devices have not been affected
        foreach ($users as $user) {
            $device = $DB->get_record('totara_mobile_devices', ['userid' => $user->id]);
            $this->assertNotFalse($device); // The device should exist.
            $this->assertNotEmpty($device->id); // The id for the device should be set.
            $this->assertEmpty($device->fcmtoken); // The fcmtoken however should not be set yet.
        }

        $u2 = array_pop($users);
        $device = $DB->get_record('totara_mobile_devices', ['userid' => $u2->id]);
        $this->assertNotFalse($device); // The device should exist.
        $this->assertNotEmpty($device->id); // The id for the device should be set.
        $this->assertEmpty($device->fcmtoken); // The fcmtoken however should not be set yet.

        // Set the fcmtoken.
        $this->setUser($u2->id);
        $this->resolve($device, ['token' => 'abc123']); // Shouldn't conflict with the first device anymore.

        // Check the fcm token has been added.
        $device = $DB->get_record('totara_mobile_devices', ['userid' => $u2->id]);
        $this->assertNotEmpty($device);
        $this->assertEquals('abc123', $device->fcmtoken);
    }

    /**
     * Test that a user can't update another users device.
     * This shouldn't be a possible case via any regular interface, but better safe than sorry.
     */
    public function test_resolve_set_fcm_token_user_mismatch() {
        global $DB;

        $eventsink = $this->redirectEvents();
        $users = $this->create_faux_devices();

        $u1 = array_pop($users);
        $device = $DB->get_record('totara_mobile_devices', ['userid' => $u1->id]);
        $this->assertNotFalse($device); // The device should exist.
        $this->assertNotEmpty($device->id); // The id for the device should be set.
        $this->assertEmpty($device->fcmtoken); // The fcmtoken however should not be set yet.

        // Set the fcmtoken.
        $u2 = array_pop($users);
        $this->setUser($u2->id);
        $result = $this->resolve($device, ['token' => 'abc123']);

        $this->assertFalse($result);

        // Refresh the device and double check the field hasn't been updated.
        $device = $DB->get_record('totara_mobile_devices', ['id' => $device->id]);
        $this->assertEmpty($device->fcmtoken);
    }

    /**
     * Test that a device can set a token, update it, and the previous token can be reused.
     */
    public function test_resolve_set_fcm_token_with_duplicate_token() {
        global $DB;

        $eventsink = $this->redirectEvents();
        $users = $this->create_faux_devices();

        // Set the fcmtoken for user one.
        $u1 = array_pop($users);
        $device = $DB->get_record('totara_mobile_devices', ['userid' => $u1->id]);
        $this->setUser($u1->id);
        $this->resolve($device, ['token' => 'abc123']);

        // Check the fcm token has been added.
        $device = $DB->get_record('totara_mobile_devices', ['userid' => $u1->id]);
        $this->assertNotEmpty($device);
        $this->assertEquals('abc123', $device->fcmtoken);

        // Clear the event sink.
        $eventsink->clear();

        // Set the fcmtoken for user 2 to the same token.
        $u2 = array_pop($users);
        $device = $DB->get_record('totara_mobile_devices', ['userid' => $u2->id]);
        $this->setUser($u2->id);
        $this->resolve($device, ['token' => 'abc123']);

        // Check the fcm token has been added.
        $device = $DB->get_record('totara_mobile_devices', ['userid' => $u2->id]);
        $this->assertNotEmpty($device);
        $this->assertEquals('abc123', $device->fcmtoken);

        // Check that user 1's device has been logged out (removed)
        $device = $DB->get_record('totara_mobile_devices', ['userid' => $u1->id]);
        $this->assertEmpty($device);

        // Check that setting the duplicate token triggered just one event.
        // Why not two? Because the device that was deleted (u1's) had the same token, which means there should
        // not have been a token_removed event, which should only fired when a token is completely removed by the system to
        // prevent a race condition in exactly this case.
        $events = $eventsink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertEquals('\totara_mobile\event\fcmtoken_received', $event->eventname);
        $this->assertEquals($u2->id, $event->userid);
        $eventsink->clear();
    }
}
