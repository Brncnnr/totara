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
 * Tests program progress information functions
 */
class totara_mobile_authentication_testcase extends advanced_testcase {
    private $m_user;
    private $d_user;

    /**
     * Sets up the common data for all the test functions below.
     */
    public function setUp(): void {
        global $CFG;
        $syscontext = \context_system::instance();

        // Create moby the mobile tester.
        $this->m_user = $this->getDataGenerator()->create_user([
            'username' => 'moby_isle',
            'firstname' => 'Moby',
            'lastname' => 'Mobile',
            'password' => 'M0b1l3',
        ]);

        // Make sure they have the right capability, should be at user context but system is easier here.
        $m_roleid = create_role('Mobile role', 'mobrole', 'Mobile role description');
        assign_capability('totara/mobile:use', CAP_ALLOW, $m_roleid, $syscontext);
        role_assign($m_roleid, $this->m_user->id, $syscontext->id);

        // And create Derrick the desktop tester.
        $this->d_user = $this->getDataGenerator()->create_user([
            'username' => 'derrick_top',
            'firstname' => 'Derrick',
            'lastname' => 'Desktop',
            'password' => 'D3skt0p',
        ]);

        // Make sure they don't have the right capability, should be at user context but system is easier here.
        $d_roleid = create_role('Desktop role', 'deskrole', 'Desktop role description');
        assign_capability('totara/mobile:use', CAP_PROHIBIT, $d_roleid, $syscontext);
        role_assign($d_roleid, $this->d_user->id, $syscontext->id);

        parent::setUp();
    }

    /**
     * Clears the common data used by the test functions.
     */
    public function tearDown(): void {
        $this->m_user = null;
        $this->d_user = null;

        parent::tearDown();
    }

    /**
     * Test mobile authentication with a valid username/password combination.
     */
    public function test_authentication_login_success() {
        global $DB;

        $requests = $DB->get_records('totara_mobile_tokens');
        $this->assertCount(0, $requests);

        // Get secret from login_setup, this returns a secret for totara_mobile_tokens.
        $secret = device::login_setup();
        $this->assertEquals(device::SETUP_SECRET_LENGTH, strlen($secret));

        $requests = $DB->get_records('totara_mobile_tokens');
        $this->assertCount(1, $requests);

        $request = array_pop($requests);
        $this->assertSame($request->loginsecret, $secret);

        // Get result from login(), this returns a secret for totara_mobile_request.
        $apikey = device::login($secret, $this->m_user->username, 'M0b1l3');
        $this->assertEquals(device::SETUP_SECRET_LENGTH, strlen($apikey));

        $logins = $DB->get_records('totara_mobile_requests');
        $this->assertCount(1, $logins);

        $login = array_pop($logins);
        $this->assertEquals($this->m_user->id, $login->userid);
    }

    /**
     * Test mobile authentication with invalid username/password combinations.
     */
    public function test_authentication_login_failure() {
        global $DB;

        $requests = $DB->get_records('totara_mobile_tokens');
        $this->assertCount(0, $requests);

        // Get secret from login_setup, this returns a secret for totara_mobile_tokens.
        $secret = device::login_setup();
        $this->assertEquals(device::SETUP_SECRET_LENGTH, strlen($secret));

        $requests = $DB->get_records('totara_mobile_tokens');
        $this->assertCount(1, $requests);

        $request = array_pop($requests);
        $this->assertSame($request->loginsecret, $secret);

        // Get result from login(), this returns a secret for totara_mobile_request.
        $apikey = device::login($secret, $this->m_user->username, 'nopass');
        $this->assertEmpty($apikey);

        $logins = $DB->get_records('totara_mobile_requests');
        $this->assertEmpty($logins);
    }

    /**
     * Test mobile authentication for user with and without totara/mobile:use capability.
     */
    public function test_authentication_capability_failure() {
        global $DB;

        $requests = $DB->get_records('totara_mobile_tokens');
        $this->assertCount(0, $requests);

        // Get secret from login_setup, this returns a secret for totara_mobile_tokens.
        $secret = device::login_setup();
        $this->assertEquals(device::SETUP_SECRET_LENGTH, strlen($secret));

        $requests = $DB->get_records('totara_mobile_tokens');
        $this->assertCount(1, $requests);

        $request = array_pop($requests);
        $this->assertSame($request->loginsecret, $secret);

        // Get result from login(), this returns a secret for totara_mobile_request.
        $apikey = device::login($secret, $this->d_user->username, 'D3skt0p');
        $this->assertEmpty($apikey);

        $logins = $DB->get_records('totara_mobile_requests');
        $this->assertEmpty($logins);
    }

    /**
     * Test mobile authentication for user with an incorrect secret.
     */
    public function test_authentication_secret_failure() {
        global $DB;

        $requests = $DB->get_records('totara_mobile_tokens');
        $this->assertCount(0, $requests);

        // Get secret from login_setup, this returns a secret for totara_mobile_tokens.
        $secret = device::login_setup();
        $this->assertEquals(device::SETUP_SECRET_LENGTH, strlen($secret));

        $requests = $DB->get_records('totara_mobile_tokens');
        $this->assertCount(1, $requests);

        $request = array_pop($requests);
        $this->assertSame($request->loginsecret, $secret);

        // Try to log in with an empty secret.
        $apikey = device::login('', $this->m_user->username, 'M0b1l3');
        $this->assertEmpty($apikey);

        $logins = $DB->get_records('totara_mobile_requests');
        $this->assertEmpty($logins);

        // Try to log in with the incorrect sized secret.
        $apikey = device::login('123', $this->m_user->username, 'M0b1l3');
        $this->assertEmpty($apikey);

        $logins = $DB->get_records('totara_mobile_requests');
        $this->assertEmpty($logins);

        // Try to log in with an incorrect secret of the right size.
        $rando = random_string(device::SETUP_SECRET_LENGTH);
        while ($rando == $secret) {
            // This shouldn't really conflict with just 2 of them, but just in case.
            $rando = random_string(device::SETUP_SECRET_LENGTH);
        }

        $apikey = device::login($rando, $this->m_user->username, 'M0b1l3');
        $this->assertEmpty($apikey);

        $logins = $DB->get_records('totara_mobile_requests');
        $this->assertEmpty($logins);
    }

    /**
     * Test mobile authentication for user with a secret that has timed out.
     */
    public function test_authentication_secret_timeout() {
        global $DB;

        $requests = $DB->get_records('totara_mobile_tokens');
        $this->assertCount(0, $requests);

        // Get secret from login_setup, this returns a secret for totara_mobile_tokens.
        $secret = device::login_setup();
        $this->assertEquals(device::SETUP_SECRET_LENGTH, strlen($secret));

        $requests = $DB->get_records('totara_mobile_tokens');
        $this->assertCount(1, $requests);

        $request = array_pop($requests);
        $this->assertSame($request->loginsecret, $secret);

        $request->timecreated = $request->timecreated - (device::LOGIN_SECRET_VALIDITY * 2);
        $DB->update_record('totara_mobile_tokens', $request);

        // Try to log in with an expired secret.
        $apikey = device::login($secret, $this->m_user->username, 'M0b1l3');
        $this->assertEmpty($apikey);

        $logins = $DB->get_records('totara_mobile_requests');
        $this->assertEmpty($logins);
    }
}
