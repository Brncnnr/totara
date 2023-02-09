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
 * @package core_user
 */

defined('MOODLE_INTERNAL') || die();

use \core\webapi\resolver\query\user_own_profile;

/**
 * Tests the totara current learning query resolver
 */
class core_user_webapi_resolver_query_user_own_profile_testcase extends advanced_testcase {

    private function get_execution_context(string $type = 'dev', ?string $operation = null) {
        return \core\webapi\execution_context::create($type, $operation);
    }

    /**
     * Create some users for testing.
     * @return []
     */
    private function create_faux_users(array $users = []) {
        $users = [];
        $u1 = $this->getDataGenerator()->create_user([
            'idnumber' => 'u1',
            'firstname' => 'Gregory',
            'lastname' => 'Smith',
            'middlename' => "Jeremiah",
            'alternatename' => "Grog",
            'email' => "greggy291@hotmail.com",
            'city' => "Wellington",
            'country' => "nz",
            'timezone' => "99",
            'description' => "Great Glorious Greg",
            'webpage' => "www.gregsnothere.com",
            'skypeid' => "s456",
            'institution' => "Psyc",
            'department' => "Ward",
            'phone' => "123456789",
            'mobile' => "987654321",
            'address' => "123 numbers lane",
        ]);

        $u2 = $this->getDataGenerator()->create_user([
            'idnumber' => 'u2',
            'firstname' => 'Samantha',
            'lastname' => 'Crumpets',
        ]);

        // Allow U2 to see user details, this shouldn't affect seeing their own profile.
        $context = \context_system::instance();
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('moodle/user:viewdetails', CAP_ALLOW, $roleid, $context);
        $this->getDataGenerator()->role_assign($roleid, $u2->id, $context->id);

        $u3 = $this->getDataGenerator()->create_user([
            'idnumber' => 'u3',
            'firstname' => 'Valentina',
            'lastname' => 'westson',
        ]);

        // Prohibit U3 from seeing user details, this shouldn't actually stop them seeing their own profile.
        $context = \context_system::instance();
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('moodle/user:viewdetails', CAP_PROHIBIT, $roleid, $context);
        $this->getDataGenerator()->role_assign($roleid, $u3->id, $context->id);


        $users = [$u1, $u2, $u3];
        return [$users, $users];
    }

    /**
     * Test the results of the query when a deleted user attempts to run it.
     */
    public function test_resolve_deleted_user() {
        global $CFG, $DB;
        list($users, $users) = $this->create_faux_users();

        // Get one of the users and delete them.
        $user = array_pop($users);
        $user->deleted = 1;
        $DB->update_record('user', $user);
        $this->setUser($user->id);

        try {
            user_own_profile::resolve([], $this->get_execution_context());
            $this->fail('expected failure');
        } catch (\moodle_exception $ex) {
            $this->assertSame('Coding error detected, it must be fixed by a programmer: Current user can not access their profile.', $ex->getMessage());
        }

        // Double check the rest of the user's aren't affected.
        $user = array_pop($users);
        $this->setUser($user->id);
        try {
            $profile = user_own_profile::resolve([], $this->get_execution_context());
            $profile->id = $CFG->siteguest;
            $profile->username = 'guest';
        } catch (\moodle_exception $ex) {
            $this->fail('unexpected failure on guest profile');
        }
    }

    /**
     * Test the results of the query when the current user is not logged in.
     */
    public function test_resolve_no_login() {
        global $CFG;
        list($users, $users) = $this->create_faux_users();

        // This shouldn't be possible, but test it anyway just in case.
        $CFG->forceloginforprofiles = false;
        try {
            user_own_profile::resolve([], $this->get_execution_context());
            $this->fail('expected failure');
        } catch (\moodle_exception $ex) {
            $this->assertSame('Coding error detected, it must be fixed by a programmer: User access controllers can only be used for real users.', $ex->getMessage());
        }

        // This should be the same with forcelogin enabled.
        $CFG->forceloginforprofiles = true;
        try {
            user_own_profile::resolve([], $this->get_execution_context());
            $this->fail('expected failure');
        } catch (\moodle_exception $ex) {
            $this->assertSame('Coding error detected, it must be fixed by a programmer: User access controllers can only be used for real users.', $ex->getMessage());
        }
    }

    /**
     * Test the results of the query when the current user is logged in as the guest user.
     */
    public function test_resolve_guest_user() {
        global $CFG;
        list($users, $users) = $this->create_faux_users();
        $this->setGuestUser();
        $guest = guest_user();

        // This shouldn't be possible, but test it anyway just in case.
        $CFG->forceloginforprofiles = false;
        try {
            $profile = user_own_profile::resolve([], $this->get_execution_context());
            $profile->id = $CFG->siteguest;
            $profile->username = 'guest';
        } catch (\moodle_exception $ex) {
            $this->fail('unexpected failure on guest profile');
        }

        // Interestingly this works for guests as long as they only try access their own guest profile.
        $CFG->forceloginforprofiles = true;
        try {
            $profile = user_own_profile::resolve([], $this->get_execution_context());
            $profile->id = $CFG->siteguest;
            $profile->username = 'guest';
        } catch (\moodle_exception $ex) {
            $this->fail('unexpected failure on guest profile');
        }
    }

    /**
     * Test the results of the query match expectations for a learner.
     */
    public function test_resolve_user() {
        list($users, $users) = $this->create_faux_users();

        $u1 = array_shift($users);
        $this->assertSame('u1', $u1->idnumber);
        $this->setUser($u1->id);
        try {
            $profile = user_own_profile::resolve([], $this->get_execution_context());

            // Do some checks on the item to make sure it's what we are expecting.
            $this->assertEquals($u1->id, $profile->id);
            $this->assertEquals($u1->username, $profile->username);
            $this->assertEquals($u1->firstname, $profile->firstname);
            $this->assertEquals($u1->lastname, $profile->lastname);
        } catch (\moodle_exception $ex) {
            $this->fail($ex->getMessage());
        }

        $u2 = array_shift($users);
        $this->assertSame('u2', $u2->idnumber);
        $this->setUser($u2->id);
        $this->assertTrue(has_capability('moodle/user:viewdetails', \context_user::instance($u2->id)));
        try {
            $profile = user_own_profile::resolve([], $this->get_execution_context());

            // Do some checks on the item to make sure it's what we are expecting.
            $this->assertEquals($u2->id, $profile->id);
            $this->assertEquals($u2->username, $profile->username);
            $this->assertEquals($u2->firstname, $profile->firstname);
            $this->assertEquals($u2->lastname, $profile->lastname);
        } catch (\moodle_exception $ex) {
            $this->fail($ex->getMessage());
        }

        $u3 = array_shift($users);
        $this->assertSame('u3', $u3->idnumber);
        $this->setUser($u3->id);
        $this->assertFalse(has_capability('moodle/user:viewdetails', \context_user::instance($u3->id)));
        try {
            // The view details capability would stop them seeing other peoples details, but never their own.
            $profile = user_own_profile::resolve([], $this->get_execution_context());

            // Do some checks on the item to make sure it's what we are expecting.
            $this->assertEquals($u3->id, $profile->id);
            $this->assertEquals($u3->username, $profile->username);
            $this->assertEquals($u3->firstname, $profile->firstname);
            $this->assertEquals($u3->lastname, $profile->lastname);
        } catch (\moodle_exception $ex) {
            $this->fail($ex->getMessage());
        }
    }
}
