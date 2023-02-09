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

use \totara_mobile\webapi\resolver\query;

/**
 * Tests the totara job assignment query resolver
 */
class totara_mobile_webapi_resolver_query_me_testcase extends advanced_testcase {
    private $learner;
    private $course;

    private function get_execution_context(string $type = 'dev', ?string $operation = null) {
        return \core\webapi\execution_context::create($type, $operation);
    }

    public function setUp(): void {

        // Create a spare user to make sure they aren't returned
        $controluser = $this->getDataGenerator()->create_user();

        // Create the target user so we can check the data returned.
        $this->learner = $this->getDataGenerator()->create_user();

        parent::setUp();
    }

    public function tearDown(): void {
        $this->learner = null;
        $this->course = null;

        parent::tearDown();
    }

    /**
     * Test the results of the query when the current user is not logged in.
     */
    public function test_resolve_no_login() {
        try {
            query\me::resolve([], $this->get_execution_context());
            $this->fail('Expected a moodle_exception: you are not logged in');
        } catch (\moodle_exception $ex) {
            // Note: generic login failure error.
            $this->assertSame('Course or activity not accessible. (You are not logged in)', $ex->getMessage());
        }
    }

    /**
     * Test for a user without the mobile:user capability.
     */
    public function test_resolve_user_no_capability() {
        $this->setUser($this->learner);

        // Prohibit the mobile capability for the user.
        $context = context_user::instance($this->learner->id);
        $prohibitrole = create_role('Prohibit Role', 'prohibitrole', 'Stop mobile access capabilities');
        assign_capability('totara/mobile:use', CAP_PROHIBIT, $prohibitrole, $context);
        $this->getDataGenerator()->role_assign($prohibitrole, $this->learner->id, $context->id);

        try {
            query\me::resolve([], $this->get_execution_context());
            $this->fail('Expected a moodle_exception: you do not have the required capability');
        } catch (\moodle_exception $ex) {
            $this->assertSame('Sorry, but you do not currently have permissions to do that (Connect and use mobile app)', $ex->getMessage());
        }
    }

    /**
     * Test the results of the query when the current user is a regular user in the site.
     */
    public function test_resolve_regular_user() {
        global $CFG;

        $this->setUser($this->learner);

        try {
            query\me::resolve([], $this->get_execution_context());

            $data = query\me::resolve([], $this->get_execution_context());
            $user = $data['user'];
            $system = $data['system'];

            // Check user information.
            $admin = get_admin();
            $this->assertEquals($this->learner->id, $user->id);
            $this->assertEquals($this->learner->username, $user->username);
            $this->assertEquals($this->learner->firstname, $user->firstname);
            $this->assertEquals($this->learner->lastname, $user->lastname);
            $this->assertEquals($this->learner->email, $user->email);
            $this->assertEquals($this->learner->timemodified, $user->timemodified);

            // Check system information.
            $this->assertEquals($system['wwwroot'], $CFG->wwwroot . '/'); // Note: I needed the trailing slash.
            $this->assertEquals($system['apiurl'], $CFG->wwwroot . '/totara/mobile/api.php'); // Note: I needed the trailing slash.
            $this->assertEquals($system['release'], $CFG->totara_release);
            $this->assertEquals($system['request_policy_agreement'], false);
            $this->assertEquals($system['request_user_consent'], false);
            $this->assertEquals($system['request_user_fields'], false);
            $this->assertEquals($system['password_change_required'], false);
        } catch (\moodle_exception $ex) {
            $this->fail($ex->getMessage());
        }

        // Make sure it updates properly.
        set_user_preference('auth_forcepasswordchange', 1, $user);

        try {
            query\me::resolve([], $this->get_execution_context());

            $data = query\me::resolve([], $this->get_execution_context());
            $user = $data['user'];
            $system = $data['system'];

            // Check user information.
            $admin = get_admin();
            $this->assertEquals($this->learner->id, $user->id);

            // Check system information.
            $this->assertEquals($system['request_policy_agreement'], false);
            $this->assertEquals($system['request_user_consent'], false);
            $this->assertEquals($system['request_user_fields'], false);
            $this->assertEquals($system['password_change_required'], true);
        } catch (\moodle_exception $ex) {
            $this->fail($ex->getMessage());
        }
    }

    /**
     * Test the results of the query when the current user is the site administrator.
     */
    public function test_resolve_admin_user() {
        global $CFG;

        $this->setAdminUser();

        try {
            $data = query\me::resolve([], $this->get_execution_context());
            $user = $data['user'];
            $system = $data['system'];

            // Check user information.
            $admin = get_admin();
            $this->assertEquals($admin->id, $user->id);
            $this->assertEquals($admin->username, $user->username);
            $this->assertEquals($admin->firstname, $user->firstname);
            $this->assertEquals($admin->lastname, $user->lastname);
            $this->assertEquals($admin->email, $user->email);
            $this->assertEquals($admin->timemodified, $user->timemodified);

            // Check system information.
            $this->assertEquals($system['wwwroot'], $CFG->wwwroot . '/'); // Note: I needed the trailing slash.
            $this->assertEquals($system['apiurl'], $CFG->wwwroot . '/totara/mobile/api.php'); // Note: I needed the trailing slash.
            $this->assertEquals($system['release'], $CFG->totara_release);
            $this->assertEquals($system['request_policy_agreement'], false);
            $this->assertEquals($system['request_user_consent'], false);
            $this->assertEquals($system['request_user_fields'], false);
            $this->assertEquals($system['password_change_required'], false);

            $value = $system['mobile_subplugins'];
            $this->assertNotEmpty($value);

            $manager = \core_plugin_manager::instance();
            $plugins = $manager->get_installed_plugins('mobile');
            foreach ($value as $plugin) {
                $name = $plugin['name'];
                $this->assertSame($plugins[$name], $plugin['version']);
            }
        } catch (\moodle_exception $ex) {
            $this->fail($ex->getMessage());
        }
    }

    /**
     * Test the results of the embedded mobile query through the GraphQL stack.
     */
    public function test_embedded_query() {
        global $CFG;

        $this->setUser($this->learner);
        try {
            $result = \totara_webapi\graphql::execute_operation(
                \core\webapi\execution_context::create('mobile', 'totara_mobile_me'),
                []
            );
            $data = $result->toArray()['data'];

            $user = $data['me']['user'];
            $system = $data['me']['system'];

            // Check user information.
            $this->assertEquals($this->learner->id, $user['id']);
            $this->assertEquals($this->learner->firstname, $user['firstname']);
            $this->assertEquals($this->learner->lastname, $user['lastname']);
            $this->assertEquals(fullname($this->learner), $user['fullname']);
            $this->assertEquals($this->learner->lang, $user['lang']);
            $this->assertEquals($this->learner->email, $user['email']);

            // Check system information.
            $this->assertEquals($system['wwwroot'], $CFG->wwwroot . '/'); // Note: I needed the trailing slash.
            $this->assertEquals($system['apiurl'], $CFG->wwwroot . '/totara/mobile/api.php'); // Note: I needed the trailing slash.
            $this->assertEquals($system['release'], $CFG->totara_release);
            $this->assertEquals($system['request_policy_agreement'], false);
            $this->assertEquals($system['request_user_consent'], false);
            $this->assertEquals($system['request_user_fields'], false);
            $this->assertEquals($system['password_change_required'], false);
            $this->assertEquals($system['view_own_profile'], true);

            $value = $system['mobile_subplugins'];
            $this->assertNotEmpty($value);

            $manager = \core_plugin_manager::instance();
            $plugins = $manager->get_installed_plugins('mobile');
            foreach ($value as $plugin) {
                $name = $plugin['pluginname'];
                $this->assertSame($plugins[$name], $plugin['version']);
            }
        } catch (\moodle_exception $ex) {
            $this->fail($ex->getMessage());
        }
    }
}
