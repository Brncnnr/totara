<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 *
 * @package auth_oauth2
 */

use auth_oauth2\api;
use auth_oauth2\linked_login;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for OAuth 2 plugin class.
 */
final class auth_oauth2_plugin_testcase extends advanced_testcase {
    public function test_user_login() {
        global $CFG;

        $CFG->auth = 'manual,oauth2';
        $auth = get_auth_plugin('oauth2');

        $user = $this->getDataGenerator()->create_user(['password' => 'secret', 'auth' => 'oauth2']);
        $this->assertFalse($auth->user_login($user->username, 'secret'));
    }

    public function test_prevent_local_passwords() {
        $auth = get_auth_plugin('oauth2');
        $this->assertTrue($auth->prevent_local_passwords());
    }

    public function test_is_internal() {
        $auth = get_auth_plugin('oauth2');
        $this->assertFalse($auth->is_internal());
    }

    public function test_is_synchronised_with_external() {
        $auth = get_auth_plugin('oauth2');
        $this->assertTrue($auth->is_synchronised_with_external());
    }

    public function test_can_change_password() {
        $auth = get_auth_plugin('oauth2');
        $this->assertFalse($auth->can_change_password());
    }

    public function test_change_password_url() {
        $auth = get_auth_plugin('oauth2');
        $this->assertNull($auth->change_password_url());
    }

    public function test_can_reset_password() {
        $auth = get_auth_plugin('oauth2');
        $this->assertFalse($auth->can_reset_password());
    }

    public function test_can_be_manually_set() {
        $auth = get_auth_plugin('oauth2');
        $this->assertTrue($auth->can_be_manually_set());
    }

    public function test_loginpage_idp_list() {
        /** @var auth_plugin_oauth2 $auth */
        $auth = get_auth_plugin('oauth2');

        $this->setAdminUser();

        $issuer1 = core\oauth2\api::create_standard_issuer('facebook');
        $issuer1->set('showonloginpage', 1);
        $issuer1->set('clientid', 'abc');
        $issuer1->set('clientsecret', 'def');
        $issuer1->set('enabled', 1);
        $issuer1->update();

        $issuer2 = core\oauth2\api::create_standard_issuer('microsoft');
        $issuer2->set('showonloginpage', 1);
        $issuer2->set('clientid', 'opq');
        $issuer2->set('clientsecret', 'rst');
        $issuer2->set('enabled', 1);
        $issuer2->update();

        $issuer3 = core\oauth2\api::create_standard_issuer('google');
        $issuer3->set('showonloginpage', 0);
        $issuer3->set('enabled', 0);
        $issuer3->update();

        $list = $auth->loginpage_idp_list('/pokus.html');
        $this->assertCount(2, $list);
    }

    public function test_complete_login_all_disabled() {
        global $CFG, $USER, $SESSION, $DB;
        require_once(__DIR__ . '/fixtures/oauth2_test_client.php');

        set_config('allowaccountcreation', 0, 'auth_oauth2');
        set_config('allowautolinkingexisting', 0, 'auth_oauth2');

        $CFG->auth = 'manual,oauth2';
        /** @var auth_plugin_oauth2 $auth */
        $auth = get_auth_plugin('oauth2');

        $this->setAdminUser();
        $issuer1 = core\oauth2\api::create_standard_issuer('facebook');
        $issuer1->set('showonloginpage', 1);
        $issuer1->set('clientid', 'abc');
        $issuer1->set('clientsecret', 'def');
        $issuer1->set('enabled', 1);
        $issuer1->set('requireconfirmation', 0);
        $issuer1->update();

        // No new accounts.
        $this->setUser(null);
        $client = new oauth2_test_client($issuer1);
        $userinfo1 = ['username' => 'user1', 'email' => 'user1@example.com'];
        $client->set_fake_userinfo($userinfo1);
        $sink = $this->redirectEmails();
        try {
            $auth->complete_login($client, new moodle_url('/abc.php'));
        } catch (moodle_exception $ex) {
            $this->assertSame('Unsupported redirect detected, script execution terminated', $ex->getMessage());
            $this->assertSame('https://www.example.com/moodle/login/index.php', $ex->link);
        }
        $emails = $sink->get_messages();
        $sink->close();
        $this->assertCount(0, $emails);
        $this->assertObjectHasAttribute('loginerrormsg', $SESSION);
        $this->assertSame('The login attempt failed. Reason: An account with your email address could not be created.', $SESSION->loginerrormsg);
        $linkedlogins = linked_login::get_records(['email' => $userinfo1['email']]);
        $this->assertCount(0, $linkedlogins);

        // No linking.
        $user2 = $this->getDataGenerator()->create_user(['email' => 'user2@example.com']);
        $this->setUser(null);
        $client = new oauth2_test_client($issuer1);
        $userinfo2 = ['username' => 'user2', 'email' => 'user2@example.com'];
        $client->set_fake_userinfo($userinfo2);
        $sink = $this->redirectEmails();
        try {
            $auth->complete_login($client, new moodle_url('/def.php'));
        } catch (moodle_exception $ex) {
            $this->assertSame('Unsupported redirect detected, script execution terminated', $ex->getMessage());
            $this->assertSame('https://www.example.com/moodle/login/index.php', $ex->link);
        }
        $emails = $sink->get_messages();
        $sink->close();
        $this->assertCount(0, $emails);
        $this->assertObjectHasAttribute('loginerrormsg', $SESSION);
        $this->assertSame("A user already exists on this site with this email. If this is your account, log in by entering your username and password and add it as a linked login via your preferences page.", $SESSION->loginerrormsg);
        $this->assertSame(0, $USER->id);

        // Login existing.
        $user1 = $this->getDataGenerator()->create_user();
        $userinfo1 = ['username' => 'efg', 'email' => $user1->email];
        api::link_login($userinfo1, $issuer1, $user1);
        $this->setUser(null);
        $client = new oauth2_test_client($issuer1);
        $client->set_fake_userinfo($userinfo1);
        $sink = $this->redirectEmails();
        try {
            $auth->complete_login($client, new moodle_url('/abc.php'));
        } catch (moodle_exception $ex) {
            $this->assertSame('Unsupported redirect detected, script execution terminated', $ex->getMessage());
            $this->assertSame('https://www.example.com/moodle/abc.php', $ex->link);
        }
        $emails = $sink->get_messages();
        $sink->close();
        $this->assertCount(0, $emails);
        $this->assertObjectNotHasAttribute('loginerrormsg', $SESSION);
        $this->assertTrue($SESSION->justloggedin);
        $this->assertSame($user1->id, $USER->id);
    }

    public function test_complete_login_creation_no_confirmation() {
        global $CFG, $USER, $SESSION, $DB;
        require_once(__DIR__ . '/fixtures/oauth2_test_client.php');

        set_config('allowaccountcreation', 1, 'auth_oauth2');
        set_config('allowautolinkingexisting', 0, 'auth_oauth2');

        $CFG->auth = 'manual,oauth2';
        /** @var auth_plugin_oauth2 $auth */
        $auth = get_auth_plugin('oauth2');

        $this->setAdminUser();
        $issuer1 = core\oauth2\api::create_standard_issuer('facebook');
        $issuer1->set('showonloginpage', 1);
        $issuer1->set('clientid', 'abc');
        $issuer1->set('clientsecret', 'def');
        $issuer1->set('enabled', 1);
        $issuer1->set('requireconfirmation', 0);
        $issuer1->update();

        $this->setUser(null);
        $client = new oauth2_test_client($issuer1);
        $userinfo1 = ['username' => 'user1', 'email' => 'user1@example.com', 'firstname' => 'Some', 'lastname' => 'User'];
        $client->set_fake_userinfo($userinfo1);
        $sink = $this->redirectEmails();
        try {
            $auth->complete_login($client, new moodle_url('/abc.php'));
        } catch (moodle_exception $ex) {
            $this->assertSame('Unsupported redirect detected, script execution terminated', $ex->getMessage());
            $this->assertSame('https://www.example.com/moodle/abc.php', $ex->link);
        }
        $emails = $sink->get_messages();
        $sink->close();
        $this->assertCount(0, $emails);
        $this->assertObjectNotHasAttribute('loginerrormsg', $SESSION);
        $this->assertTrue($SESSION->justloggedin);
        $linkedlogin = linked_login::get_record(['email' => $userinfo1['email'], 'issuerid' => $issuer1->get('id')]);
        $this->assertSame('1', $linkedlogin->get('confirmed'));
        $this->assertSame($USER->id, $linkedlogin->get('userid'));
        $this->assertSame($USER->email, $linkedlogin->get('email'));
        $this->assertStringStartsWith('oauth2_', $USER->username);
        $user1 = $DB->get_record('user', ['id' => $USER->id]);

        // Login existing.
        $this->setUser(null);
        $client = new oauth2_test_client($issuer1);
        $client->set_fake_userinfo($userinfo1);
        $sink = $this->redirectEmails();
        try {
            $auth->complete_login($client, new moodle_url('/abc.php'));
        } catch (moodle_exception $ex) {
            $this->assertSame('Unsupported redirect detected, script execution terminated', $ex->getMessage());
            $this->assertSame('https://www.example.com/moodle/abc.php', $ex->link);
        }
        $emails = $sink->get_messages();
        $sink->close();
        $this->assertCount(0, $emails);
        $this->assertObjectNotHasAttribute('loginerrormsg', $SESSION);
        $this->assertTrue($SESSION->justloggedin);
        $this->assertSame($user1->id, $USER->id);

        // Prevent conflicts.
        $user2 = $this->getDataGenerator()->create_user(['email' => 'user2@example.com']);
        $this->setUser(null);
        $client = new oauth2_test_client($issuer1);
        $userinfo2 = ['username' => 'user2', 'email' => 'user2@example.com'];
        $client->set_fake_userinfo($userinfo2);
        $sink = $this->redirectEmails();
        try {
            $auth->complete_login($client, new moodle_url('/def.php'));
        } catch (moodle_exception $ex) {
            $this->assertSame('Unsupported redirect detected, script execution terminated', $ex->getMessage());
            $this->assertSame('https://www.example.com/moodle/login/index.php', $ex->link);
        }
        $emails = $sink->get_messages();
        $sink->close();
        $this->assertCount(0, $emails);
        $this->assertObjectHasAttribute('loginerrormsg', $SESSION);
        $this->assertSame("A user already exists on this site with this email. If this is your account, log in by entering your username and password and add it as a linked login via your preferences page.", $SESSION->loginerrormsg);
        $this->assertSame(0, $USER->id);
    }

    public function test_complete_login_creation_with_confirmation() {
        global $CFG, $USER, $SESSION, $DB;
        require_once(__DIR__ . '/fixtures/oauth2_test_client.php');

        set_config('allowaccountcreation', 1, 'auth_oauth2');
        set_config('allowautolinkingexisting', 0, 'auth_oauth2');

        $CFG->auth = 'manual,oauth2';
        /** @var auth_plugin_oauth2 $auth */
        $auth = get_auth_plugin('oauth2');

        $this->setAdminUser();
        $issuer1 = core\oauth2\api::create_standard_issuer('facebook');
        $issuer1->set('showonloginpage', 1);
        $issuer1->set('clientid', 'abc');
        $issuer1->set('clientsecret', 'def');
        $issuer1->set('enabled', 1);
        $issuer1->set('requireconfirmation', 1);
        $issuer1->update();

        $this->setUser(null);
        $client = new oauth2_test_client($issuer1);
        $userinfo1 = ['username' => 'user1', 'email' => 'user1@example.com', 'firstname' => 'Some', 'lastname' => 'User'];
        $client->set_fake_userinfo($userinfo1);
        $sink = $this->redirectEmails();
        try {
            $auth->complete_login($client, new moodle_url('/abc.php'));
        } catch (moodle_exception $ex) {
            $this->assertSame('Unsupported redirect detected, script execution terminated', $ex->getMessage());
            $this->assertSame('https://www.example.com/moodle/login/index.php', $ex->link);
        }
        $emails = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $emails);
        $this->assertObjectHasAttribute('loginerrormsg', $SESSION);
        $this->assertSame('This account is pending email confirmation.', $SESSION->loginerrormsg);
        $this->assertSame(0, $USER->id);
        $linkedlogin = linked_login::get_record(['email' => $userinfo1['email'], 'issuerid' => $issuer1->get('id')]);
        $this->assertSame('0', $linkedlogin->get('confirmed'));
        $this->assertNull($linkedlogin->get('userid'));
        $this->assertNotEmpty($linkedlogin->get('confirmtoken'));
        $this->assertEqualsWithDelta(time() + api::CONFIRMATION_EXPIRY, $linkedlogin->get('confirmtokenexpires'), 4);
        $email = reset($emails);
        $this->assertSame($userinfo1['email'], $email->to);
        $this->assertSame('PHPUnit test site: account confirmation', $email->subject);

        // Repeated attempt nothing gets emailed.
        $this->setUser(null);
        $client = new oauth2_test_client($issuer1);
        $client->set_fake_userinfo($userinfo1);
        $sink = $this->redirectEmails();
        try {
            $auth->complete_login($client, new moodle_url('/abc.php'));
        } catch (moodle_exception $ex) {
            $this->assertSame('Unsupported redirect detected, script execution terminated', $ex->getMessage());
            $this->assertSame('https://www.example.com/moodle/login/index.php', $ex->link);
        }
        $emails = $sink->get_messages();
        $sink->close();
        $this->assertCount(0, $emails);
        $this->assertObjectHasAttribute('loginerrormsg', $SESSION);
        $this->assertSame('This account is pending email confirmation.', $SESSION->loginerrormsg);
        $this->assertSame(0, $USER->id);
        $linkedlogin2 = linked_login::get_record(['email' => $userinfo1['email'], 'issuerid' => $issuer1->get('id')]);
        $this->assertSame('0', $linkedlogin2->get('confirmed'));
        $this->assertNull($linkedlogin2->get('userid'));
        $this->assertSame($linkedlogin->get('confirmtoken'), $linkedlogin2->get('confirmtoken'));
        $this->assertSame($linkedlogin->get('confirmtokenexpires'), $linkedlogin2->get('confirmtokenexpires'));

        // Login attempt after expiration send new token.
        $linkedlogin->set('confirmtokenexpires', time() - 5);
        $linkedlogin->update();
        $this->setUser(null);
        $client = new oauth2_test_client($issuer1);
        $client->set_fake_userinfo($userinfo1);
        $sink = $this->redirectEmails();
        try {
            $auth->complete_login($client, new moodle_url('/abc.php'));
        } catch (moodle_exception $ex) {
            $this->assertSame('Unsupported redirect detected, script execution terminated', $ex->getMessage());
            $this->assertSame('https://www.example.com/moodle/login/index.php', $ex->link);
        }
        $emails = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $emails);
        $this->assertObjectHasAttribute('loginerrormsg', $SESSION);
        $this->assertSame('This account is pending email confirmation.', $SESSION->loginerrormsg);
        $this->assertSame(0, $USER->id);
        $linkedlogin2 = linked_login::get_record(['email' => $userinfo1['email'], 'issuerid' => $issuer1->get('id')]);
        $this->assertSame('0', $linkedlogin2->get('confirmed'));
        $this->assertNull($linkedlogin2->get('userid'));
        $this->assertNotSame($linkedlogin->get('confirmtoken'), $linkedlogin2->get('confirmtoken'));
        $this->assertEqualsWithDelta(time() + api::CONFIRMATION_EXPIRY, $linkedlogin2->get('confirmtokenexpires'), 4);
        $email = reset($emails);
        $this->assertSame($userinfo1['email'], $email->to);
        $this->assertSame('PHPUnit test site: account confirmation', $email->subject);

        // Confirm account creation.
        api::confirm_new_account($linkedlogin2->get('id'), $linkedlogin2->get('confirmtoken'));
        $linkedlogin2 = linked_login::get_record(['email' => $userinfo1['email'], 'issuerid' => $issuer1->get('id')]);
        $this->assertSame('1', $linkedlogin2->get('confirmed'));
        $this->assertNotEmpty($linkedlogin2->get('userid'));
        $this->setUser(null);
        $client = new oauth2_test_client($issuer1);
        $client->set_fake_userinfo($userinfo1);
        $sink = $this->redirectEmails();
        try {
            $auth->complete_login($client, new moodle_url('/abc.php'));
        } catch (moodle_exception $ex) {
            $this->assertSame('Unsupported redirect detected, script execution terminated', $ex->getMessage());
            $this->assertSame('https://www.example.com/moodle/abc.php', $ex->link);
        }
        $emails = $sink->get_messages();
        $sink->close();
        $this->assertCount(0, $emails);
        $this->assertObjectNotHasAttribute('loginerrormsg', $SESSION);
        $this->assertTrue($SESSION->justloggedin);
        $linkedlogin2 = linked_login::get_record(['email' => $userinfo1['email'], 'issuerid' => $issuer1->get('id')]);
        $this->assertSame('1', $linkedlogin2->get('confirmed'));
        $this->assertSame($USER->id, $linkedlogin2->get('userid'));
        $this->assertSame($USER->email, $linkedlogin2->get('email'));
        $this->assertStringStartsWith('oauth2_', $USER->username);
        $this->assertSame('oauth2', $USER->auth);
        $user1 = $DB->get_record('user', ['id' => $USER->id]);

        // Prevent conflicts.
        $user2 = $this->getDataGenerator()->create_user(['email' => 'user2@example.com']);
        $this->setUser(null);
        $client = new oauth2_test_client($issuer1);
        $userinfo2 = ['username' => 'user2', 'email' => 'user2@example.com'];
        $client->set_fake_userinfo($userinfo2);
        $sink = $this->redirectEmails();
        try {
            $auth->complete_login($client, new moodle_url('/def.php'));
        } catch (moodle_exception $ex) {
            $this->assertSame('Unsupported redirect detected, script execution terminated', $ex->getMessage());
            $this->assertSame('https://www.example.com/moodle/login/index.php', $ex->link);
        }
        $emails = $sink->get_messages();
        $sink->close();
        $this->assertCount(0, $emails);
        $this->assertObjectHasAttribute('loginerrormsg', $SESSION);
        $this->assertSame("A user already exists on this site with this email. If this is your account, log in by entering your username and password and add it as a linked login via your preferences page.", $SESSION->loginerrormsg);
        $this->assertSame(0, $USER->id);
    }

    public function test_complete_login_linking_no_confirmation() {
        global $CFG, $USER, $SESSION;
        require_once(__DIR__ . '/fixtures/oauth2_test_client.php');

        set_config('allowaccountcreation', 0, 'auth_oauth2');
        set_config('allowautolinkingexisting', 1, 'auth_oauth2');

        $CFG->auth = 'manual,oauth2';
        /** @var auth_plugin_oauth2 $auth */
        $auth = get_auth_plugin('oauth2');

        $this->setAdminUser();
        $issuer1 = core\oauth2\api::create_standard_issuer('facebook');
        $issuer1->set('showonloginpage', 1);
        $issuer1->set('clientid', 'abc');
        $issuer1->set('clientsecret', 'def');
        $issuer1->set('enabled', 1);
        $issuer1->set('requireconfirmation', 0);
        $issuer1->update();

        $user1 = $this->getDataGenerator()->create_user();
        $userinfo1 = ['username' => 'user1', 'email' => $user1->email];

        $this->setUser(null);
        $client = new oauth2_test_client($issuer1);
        $client->set_fake_userinfo($userinfo1);
        $sink = $this->redirectEmails();
        try {
            $auth->complete_login($client, new moodle_url('/abc.php'));
        } catch (moodle_exception $ex) {
            $this->assertSame('Unsupported redirect detected, script execution terminated', $ex->getMessage());
            $this->assertSame('https://www.example.com/moodle/abc.php', $ex->link);
        }
        $emails = $sink->get_messages();
        $sink->close();
        $this->assertCount(0, $emails);
        $this->assertObjectNotHasAttribute('loginerrormsg', $SESSION);
        $this->assertTrue($SESSION->justloggedin);
        $linkedlogin = linked_login::get_record(['email' => $userinfo1['email'], 'issuerid' => $issuer1->get('id')]);
        $this->assertSame('1', $linkedlogin->get('confirmed'));
        $this->assertSame($USER->id, $linkedlogin->get('userid'));
        $this->assertSame($USER->email, $linkedlogin->get('email'));
        $this->assertSame($user1->username, $USER->username);
        $this->assertSame($user1->auth, $USER->auth);

        // Login existing.
        $this->setUser(null);
        $client = new oauth2_test_client($issuer1);
        $client->set_fake_userinfo($userinfo1);
        $sink = $this->redirectEmails();
        try {
            $auth->complete_login($client, new moodle_url('/abc.php'));
        } catch (moodle_exception $ex) {
            $this->assertSame('Unsupported redirect detected, script execution terminated', $ex->getMessage());
            $this->assertSame('https://www.example.com/moodle/abc.php', $ex->link);
        }
        $emails = $sink->get_messages();
        $sink->close();
        $this->assertCount(0, $emails);
        $this->assertObjectNotHasAttribute('loginerrormsg', $SESSION);
        $this->assertTrue($SESSION->justloggedin);
        $this->assertSame($user1->id, $USER->id);
    }

    public function test_complete_login_linking_with_confirmation() {
        global $CFG, $USER, $SESSION;
        require_once(__DIR__ . '/fixtures/oauth2_test_client.php');

        set_config('allowaccountcreation', 0, 'auth_oauth2');
        set_config('allowautolinkingexisting', 1, 'auth_oauth2');

        $CFG->auth = 'manual,oauth2';
        /** @var auth_plugin_oauth2 $auth */
        $auth = get_auth_plugin('oauth2');

        $this->setAdminUser();
        $issuer1 = core\oauth2\api::create_standard_issuer('facebook');
        $issuer1->set('showonloginpage', 1);
        $issuer1->set('clientid', 'abc');
        $issuer1->set('clientsecret', 'def');
        $issuer1->set('enabled', 1);
        $issuer1->set('requireconfirmation', 1);
        $issuer1->update();

        $user1 = $this->getDataGenerator()->create_user();
        $userinfo1 = ['username' => 'user1', 'email' => $user1->email];

        $this->setUser(null);
        $client = new oauth2_test_client($issuer1);
        $client->set_fake_userinfo($userinfo1);
        $sink = $this->redirectEmails();
        try {
            $auth->complete_login($client, new moodle_url('/abc.php'));
        } catch (moodle_exception $ex) {
            $this->assertSame('Unsupported redirect detected, script execution terminated', $ex->getMessage());
            $this->assertSame('https://www.example.com/moodle/login/index.php', $ex->link);
        }
        $emails = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $emails);
        $this->assertObjectHasAttribute('loginerrormsg', $SESSION);
        $this->assertSame('This account is pending email confirmation.', $SESSION->loginerrormsg);
        $linkedlogin = linked_login::get_record(['email' => $userinfo1['email'], 'issuerid' => $issuer1->get('id')]);
        $this->assertSame('0', $linkedlogin->get('confirmed'));
        $this->assertNotEmpty($linkedlogin->get('confirmtoken'));
        $this->assertSame($user1->id, $linkedlogin->get('userid'));
        $this->assertEqualsWithDelta(time() + api::CONFIRMATION_EXPIRY, $linkedlogin->get('confirmtokenexpires'), 4);
        $email = reset($emails);
        $this->assertSame($userinfo1['email'], $email->to);
        $this->assertSame('PHPUnit test site: linked login confirmation', $email->subject);

        // Login attempt after expiration send new token.
        $linkedlogin->set('confirmtokenexpires', time() - 5);
        $linkedlogin->update();
        $this->setUser(null);
        $client = new oauth2_test_client($issuer1);
        $client->set_fake_userinfo($userinfo1);
        $sink = $this->redirectEmails();
        try {
            $auth->complete_login($client, new moodle_url('/abc.php'));
        } catch (moodle_exception $ex) {
            $this->assertSame('Unsupported redirect detected, script execution terminated', $ex->getMessage());
            $this->assertSame('https://www.example.com/moodle/login/index.php', $ex->link);
        }
        $emails = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $emails);
        $this->assertObjectHasAttribute('loginerrormsg', $SESSION);
        $this->assertSame('This account is pending email confirmation.', $SESSION->loginerrormsg);
        $linkedlogin2 = linked_login::get_record(['email' => $userinfo1['email'], 'issuerid' => $issuer1->get('id')]);
        $this->assertSame($linkedlogin->get('id'), $linkedlogin2->get('id'));
        $this->assertSame('0', $linkedlogin2->get('confirmed'));
        $this->assertNotEmpty($linkedlogin2->get('confirmtoken'));
        $this->assertSame($user1->id, $linkedlogin2->get('userid'));
        $this->assertEqualsWithDelta(time() + api::CONFIRMATION_EXPIRY, $linkedlogin2->get('confirmtokenexpires'), 4);
        $email = reset($emails);
        $this->assertSame($userinfo1['email'], $email->to);
        $this->assertSame('PHPUnit test site: linked login confirmation', $email->subject);

        // Confirm the linking.
        api::confirm_link_login($linkedlogin2->get('id'), $linkedlogin2->get('confirmtoken'));
        $this->setUser(null);
        $client = new oauth2_test_client($issuer1);
        $client->set_fake_userinfo($userinfo1);
        $sink = $this->redirectEmails();
        try {
            $auth->complete_login($client, new moodle_url('/abc.php'));
        } catch (moodle_exception $ex) {
            $this->assertSame('Unsupported redirect detected, script execution terminated', $ex->getMessage());
            $this->assertSame('https://www.example.com/moodle/abc.php', $ex->link);
        }
        $emails = $sink->get_messages();
        $sink->close();
        $this->assertCount(0, $emails);
        $this->assertObjectNotHasAttribute('loginerrormsg', $SESSION);
        $this->assertTrue($SESSION->justloggedin);
        $linkedlogin2 = linked_login::get_record(['email' => $userinfo1['email'], 'issuerid' => $issuer1->get('id')]);
        $this->assertSame($USER->id, $linkedlogin2->get('userid'));
        $this->assertSame($USER->email, $linkedlogin2->get('email'));
        $this->assertSame($user1->username, $USER->username);
        $this->assertSame($user1->auth, $USER->auth);

        // Login existing.
        $this->setUser(null);
        $client = new oauth2_test_client($issuer1);
        $client->set_fake_userinfo($userinfo1);
        $sink = $this->redirectEmails();
        try {
            $auth->complete_login($client, new moodle_url('/abc.php'));
        } catch (moodle_exception $ex) {
            $this->assertSame('Unsupported redirect detected, script execution terminated', $ex->getMessage());
            $this->assertSame('https://www.example.com/moodle/abc.php', $ex->link);
        }
        $emails = $sink->get_messages();
        $sink->close();
        $this->assertCount(0, $emails);
        $this->assertObjectNotHasAttribute('loginerrormsg', $SESSION);
        $this->assertTrue($SESSION->justloggedin);
        $this->assertSame($user1->id, $USER->id);
    }

    public function test_get_password_change_info() {
        $user = $this->getDataGenerator()->create_user(['auth' => 'oauth2']);
        $auth = get_auth_plugin($user->auth);
        $info = $auth->get_password_change_info($user);

        $this->assertArrayHasKey('subject', $info);
        $this->assertArrayHasKey('message', $info);
        $this->assertStringContainsString(
                'your password cannot be reset because you are using your account on another site to log in',
                $info['message']);
    }
}