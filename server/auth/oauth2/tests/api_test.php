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

/**
 * Test of api class.
 */
final class auth_oauth2_api_testcase extends advanced_testcase {
    public function test_link_login() {
        $this->setAdminUser();
        $issuer = core\oauth2\api::create_standard_issuer('facebook');

        $user1 = $this->getDataGenerator()->create_user(['auth' => 'manual']);
        $user2 = $this->getDataGenerator()->create_user(['auth' => 'manual']);

        $this->setUser($user1);
        $userinfo1 = [
            'username' => 'pokus1',
            'email' => 'pokus1@gmail.com',
        ];
        $this->setCurrentTimeStart();
        $linkedlogin1 = api::link_login($userinfo1, $issuer, $user1);
        $this->assertSame($user1->id, $linkedlogin1->get('userid'));
        $this->assertSame($issuer->get('id'), $linkedlogin1->get('issuerid'));
        $this->assertSame($userinfo1['username'], $linkedlogin1->get('username'));
        $this->assertSame($userinfo1['email'], $linkedlogin1->get('email'));
        $this->assertSame('1', $linkedlogin1->get('confirmed'));
        $this->assertSame('', $linkedlogin1->get('confirmtoken'));
        $this->assertSame(null, $linkedlogin1->get('confirmtokenexpires'));
        $this->assertTimeCurrent($linkedlogin1->get('timecreated'));
        $this->assertTimeCurrent($linkedlogin1->get('timemodified'));
        $this->assertSame($user1->id, $linkedlogin1->get('usermodified'));

        try {
            api::link_login($userinfo1, $issuer, $user1);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertSame('This OAuth 2 account is already linked to another Totara account', $e->getMessage());
        }

        $this->setUser($user2);
        try {
            api::link_login($userinfo1, $issuer, $user2);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertSame('This OAuth 2 account is already linked to another Totara account', $e->getMessage());
        }

        $this->setUser();
        $userinfo2 = [
            'username' => 'pokus2',
            'email' => 'pokus2@gmail.com',
        ];
        $record = new stdClass();
        $record->userid = $user2->id;
        $record->issuerid = $issuer->get('id');
        $record->username = $userinfo2['username'];
        $record->email = $userinfo2['email'];
        $record->confirmed = 0;
        $record->confirmtoken = random_string(32);
        $record->confirmtokenexpires = time() + api::CONFIRMATION_EXPIRY;
        $linkedlogin2a = new linked_login(0, $record);
        $linkedlogin2a->create();
        $this->setUser($user2);
        $this->setCurrentTimeStart();
        $linkedlogin2 = api::link_login($userinfo2, $issuer, $user2);
        $this->assertSame($linkedlogin2a->get('id'), $linkedlogin2->get('id'));
        $this->assertSame($user2->id, $linkedlogin2->get('userid'));
        $this->assertSame($issuer->get('id'), $linkedlogin2->get('issuerid'));
        $this->assertSame($userinfo2['username'], $linkedlogin2->get('username'));
        $this->assertSame($userinfo2['email'], $linkedlogin2->get('email'));
        $this->assertSame('1', $linkedlogin2->get('confirmed'));
        $this->assertSame('', $linkedlogin2->get('confirmtoken'));
        $this->assertSame(null, $linkedlogin2->get('confirmtokenexpires'));
        $this->assertTimeCurrent($linkedlogin2->get('timemodified'));
        $this->assertSame($user2->id, $linkedlogin2->get('usermodified'));
    }

    public function test_can_link_login() {
        global $CFG;
        $CFG->auth = 'manual,oauth2';

        $this->setAdminUser();
        $issuer1 = core\oauth2\api::create_standard_issuer('facebook');
        $issuer2 = core\oauth2\api::create_standard_issuer('google');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $this->setUser($user1);
        $this->assertTrue(api::can_link_login($issuer1->get('id')));

        // Not enabled issuer.
        $this->assertFalse(api::can_link_login($issuer2->get('id')));

        // Login as
        $GLOBALS['USER']->realuser = $user2;
        $this->assertFalse(api::can_link_login($issuer1->get('id')));
        unset($GLOBALS['USER']->realuser);

        // Unconfirmed link.
        $record = new stdClass();
        $record->userid = $user1->id;
        $record->issuerid = $issuer1->get('id');
        $record->username = 'xx';
        $record->email = $user1->email;
        $record->confirmed = 0;
        $record->confirmtoken = random_string(32);
        $record->confirmtokenexpires = time() + api::CONFIRMATION_EXPIRY;
        $linkedlogin = new linked_login(0, $record);
        $linkedlogin->create();
        $this->assertTrue(api::can_link_login($issuer1->get('id')));

        // Confirmed link.
        $linkedlogin->set('confirmed', 1);
        $linkedlogin->update();
        $this->assertFalse(api::can_link_login($issuer1->get('id')));

        // No capability.
        $linkedlogin->set('confirmed', 0);
        $linkedlogin->update();
        $usercontext = context_user::instance($user1->id);
        assign_capability('auth/oauth2:managelinkedlogins', CAP_PROHIBIT, $CFG->defaultuserroleid, $usercontext->id, true);
        $this->assertFalse(api::can_link_login($issuer1->get('id')));
    }

    public function test_send_confirm_link_login_email() {
        $this->setAdminUser();
        $issuer1 = core\oauth2\api::create_standard_issuer('facebook');
        $issuer2 = core\oauth2\api::create_standard_issuer('google');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $this->setUser(null);

        $userinfo = ['username' => 'xyz', 'email' => $user1->email];
        $sink = $this->redirectEmails();
        $this->assertTrue(api::send_confirm_link_login_email($userinfo, $issuer1, $user1));
        $emails = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $emails);

        $email = reset($emails);
        $this->assertSame($user1->email, $email->to);
        $this->assertStringContainsString('linked login confirmation', $email->subject);
        $linkedlogin1 = linked_login::get_record(['userid' => $user1->id, 'issuerid' => $issuer1->get('id')]);
        $this->assertSame('0', $linkedlogin1->get('confirmed'));
        $this->assertNotEmpty($linkedlogin1->get('confirmtoken'));
        $this->assertEqualsWithDelta(time() + api::CONFIRMATION_EXPIRY, $linkedlogin1->get('confirmtokenexpires'), 4);

        $sink = $this->redirectEmails();
        $this->assertTrue(api::send_confirm_link_login_email($userinfo, $issuer1, $user1));
        $emails = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $emails);

        $linkedlogin2 = linked_login::get_record(['userid' => $user1->id, 'issuerid' => $issuer1->get('id')]);
        $this->assertSame($linkedlogin1->get('id'), $linkedlogin2->get('id'));
        $this->assertSame('0', $linkedlogin2->get('confirmed'));
        $this->assertNotEquals($linkedlogin1->get('confirmtoken'), $linkedlogin2->get('confirmtoken'));

        $linkedlogin2->set('confirmed', 1);
        $linkedlogin2->update();
        $sink = $this->redirectEmails();
        $this->assertFalse(api::send_confirm_link_login_email($userinfo, $issuer1, $user1));
        $emails = $sink->get_messages();
        $sink->close();
        $this->assertCount(0, $emails);
        $linkedlogin2 = linked_login::get_record(['userid' => $user1->id, 'issuerid' => $issuer1->get('id')]);
        $this->assertSame('1', $linkedlogin2->get('confirmed'));
    }

    public function test_confirm_link_login() {
        $this->setAdminUser();
        $issuer1 = core\oauth2\api::create_standard_issuer('facebook');
        $issuer2 = core\oauth2\api::create_standard_issuer('google');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $this->setUser(null);

        $userinfo = ['username' => 'xyz', 'email' => $user1->email];
        $sink = $this->redirectEmails();
        $this->assertTrue(api::send_confirm_link_login_email($userinfo, $issuer1, $user1));
        $sink->close();
        $linkedlogin = linked_login::get_record(['userid' => $user1->id, 'issuerid' => $issuer1->get('id')]);
        $this->assertSame('0', $linkedlogin->get('confirmed'));

        $this->assertNull(api::confirm_link_login($linkedlogin->get('id'), 'xyz'));
        $linkedlogin = linked_login::get_record(['userid' => $user1->id, 'issuerid' => $issuer1->get('id')]);
        $this->assertSame('0', $linkedlogin->get('confirmed'));

        $localuser = api::confirm_link_login($linkedlogin->get('id'), $linkedlogin->get('confirmtoken'));
        $linkedlogin = linked_login::get_record(['userid' => $user1->id, 'issuerid' => $issuer1->get('id')]);
        $this->assertSame('1', $linkedlogin->get('confirmed'));
        $this->assertSame($user1->id, $localuser->id);
        $this->assertSame($localuser->id, $linkedlogin->get('userid'));

        $localuser = api::confirm_link_login($linkedlogin->get('id'), 'xyz');
        $this->assertSame($localuser->id, $linkedlogin->get('userid'));
    }

    public function test_send_confirm_account_email() {
        $this->setAdminUser();
        $issuer1 = core\oauth2\api::create_standard_issuer('facebook');
        $issuer2 = core\oauth2\api::create_standard_issuer('google');

        $user1 = $this->getDataGenerator()->create_user();

        $userinfo = ['username' => 'xyz', 'email' => 'user1@example.com'];
        $sink = $this->redirectEmails();
        $this->assertTrue(api::send_confirm_account_email($userinfo, $issuer1));
        $emails = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $emails);
        $email = reset($emails);
        $this->assertSame($userinfo['email'], $email->to);
        $this->assertStringContainsString('account confirmation', $email->subject);
        $linkedlogin1 = linked_login::get_record(['email' => $userinfo['email'], 'issuerid' => $issuer1->get('id')]);
        $this->assertSame('0', $linkedlogin1->get('confirmed'));
        $this->assertNotEmpty($linkedlogin1->get('confirmtoken'));
        $this->assertEqualsWithDelta(time() + api::CONFIRMATION_EXPIRY, $linkedlogin1->get('confirmtokenexpires'), 4);

        $sink = $this->redirectEmails();
        $this->assertTrue(api::send_confirm_account_email($userinfo, $issuer1));
        $emails = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $emails);
        $email = reset($emails);
        $this->assertSame($userinfo['email'], $email->to);
        $this->assertStringContainsString('account confirmation', $email->subject);
        $linkedlogin2 = linked_login::get_record(['email' => $userinfo['email'], 'issuerid' => $issuer1->get('id')]);
        $this->assertSame('0', $linkedlogin2->get('confirmed'));
        $this->assertNotEmpty($linkedlogin2->get('confirmtoken'));
        $this->assertNotEquals($linkedlogin1->get('confirmtoken'), $linkedlogin2->get('confirmtoken'));
        $this->assertEqualsWithDelta(time() + api::CONFIRMATION_EXPIRY, $linkedlogin2->get('confirmtokenexpires'), 4);

        $linkedlogin2->set('userid', $user1->id);
        $linkedlogin2->update();
        $sink = $this->redirectEmails();
        $this->assertFalse(api::send_confirm_account_email($userinfo, $issuer1));
        $emails = $sink->get_messages();
        $sink->close();
        $this->assertCount(0, $emails);
    }

    public function test_create_new_confirmed_account() {
        $this->setAdminUser();
        $issuer1 = core\oauth2\api::create_standard_issuer('facebook');
        $issuer2 = core\oauth2\api::create_standard_issuer('google');

        $userinfo = ['username' => 'xyz', 'email' => 'user1@example.com'];
        $user1 = api::create_new_confirmed_account($userinfo, $issuer1, null);
        $linkedlogin = linked_login::get_record(['userid' => $user1->id, 'issuerid' => $issuer1->get('id')]);
        $this->assertSame('1', $linkedlogin->get('confirmed'));
        $this->assertSame($user1->id, $linkedlogin->get('userid'));
        $this->assertSame($userinfo['email'], $user1->email);

        $userinfo2 = ['username' => 'abc', 'email' => 'user2@example.com'];
        $sink = $this->redirectEmails();
        $this->assertTrue(api::send_confirm_account_email($userinfo2, $issuer1));
        $sink->close();
        $linkedlogin2 = linked_login::get_record(['email' => $userinfo2['email'], 'issuerid' => $issuer1->get('id')]);
        $user2 = api::create_new_confirmed_account($userinfo2, $issuer1, $linkedlogin2);
        $linkedlogin2 = linked_login::get_record(['userid' => $user2->id, 'issuerid' => $issuer1->get('id')]);
        $this->assertSame('1', $linkedlogin2->get('confirmed'));
        $this->assertSame($user2->id, $linkedlogin2->get('userid'));
        $this->assertSame($userinfo2['email'], $user2->email);
    }

    public function test_confirm_new_account() {
        $this->setAdminUser();
        $issuer1 = core\oauth2\api::create_standard_issuer('facebook');
        $issuer2 = core\oauth2\api::create_standard_issuer('google');

        $userinfo2 = ['username' => 'abc', 'email' => 'user2@example.com'];
        $sink = $this->redirectEmails();
        $this->assertTrue(api::send_confirm_account_email($userinfo2, $issuer1));
        $sink->close();
        $linkedlogin = linked_login::get_record(['email' => $userinfo2['email'], 'issuerid' => $issuer1->get('id')]);

        $this->assertFalse(api::confirm_new_account($linkedlogin->get('id'), 'abc'));

        $this->assertTrue(api::confirm_new_account($linkedlogin->get('id'), $linkedlogin->get('confirmtoken')));
        $linkedlogin = linked_login::get_record(['email' => $userinfo2['email'], 'issuerid' => $issuer1->get('id')]);
        $this->assertSame('1', $linkedlogin->get('confirmed'));
    }

    public function test_can_delete_linked_login() {
        global $CFG, $DB;

        $this->setAdminUser();
        $issuer1 = core\oauth2\api::create_standard_issuer('facebook');
        $issuer2 = core\oauth2\api::create_standard_issuer('google');

        $user1 = $this->getDataGenerator()->create_user(['auth' => 'manual']);
        $user2 = $this->getDataGenerator()->create_user(['auth' => 'oauth2']);

        $usercontext1 = context_user::instance($user1->id);

        // Wrong parameter.
        $this->assertFalse(api::can_delete_linked_login(0));
        $this->assertFalse(api::can_delete_linked_login(1));

        $record = new stdClass();
        $record->userid = $user1->id;
        $record->issuerid = $issuer1->get('id');
        $record->username = 'xx';
        $record->email = $user1->email;
        $record->confirmed = 0;
        $record->confirmtoken = random_string(32);
        $record->confirmtokenexpires = time() + api::CONFIRMATION_EXPIRY;
        $linkedlogin = new linked_login(0, $record);
        $linkedlogin->create();

        $this->setUser($user1);
        $this->assertFalse(api::can_delete_linked_login($linkedlogin->get('id')));
        $this->setAdminUser();
        $this->assertTrue(api::can_delete_linked_login($linkedlogin->get('id')));

        $CFG->auth = 'manual,oauth2';
        $this->setUser($user1);
        $this->assertTrue(api::can_delete_linked_login($linkedlogin->get('id')));

        assign_capability('auth/oauth2:managelinkedlogins', CAP_PROHIBIT, $CFG->defaultuserroleid, $usercontext1->id, true);
        $this->setUser($user1);
        $this->assertFalse(api::can_delete_linked_login($linkedlogin->get('id')));
        assign_capability('auth/oauth2:managelinkedlogins', CAP_ALLOW, $CFG->defaultuserroleid, $usercontext1->id, true);

        $this->setUser($user1);
        $GLOBALS['USER']->realuser = $user2;
        $this->assertFalse(api::can_delete_linked_login($linkedlogin->get('id')));
        unset($GLOBALS['USER']->realuser);

        $this->setUser($user1);
        $this->assertTrue(api::can_delete_linked_login($linkedlogin->get('id')));
        $DB->set_field('user', 'auth', 'oauth2', ['id' => $user1->id]);
        $linkedlogin->set('confirmed', 1);
        $linkedlogin->update();
        $user1->auth = 'oauth2';
        $this->setUser($user1);
        $this->assertFalse(api::can_delete_linked_login($linkedlogin->get('id')));

        $record = new stdClass();
        $record->userid = $user1->id;
        $record->issuerid = $issuer2->get('id');
        $record->username = 'yy';
        $record->email = $user1->email;
        $record->confirmed = 1;
        $linkedlogin2 = new linked_login(0, $record);
        $linkedlogin2->create();
        $this->assertTrue(api::can_delete_linked_login($linkedlogin->get('id')));

        $linkedlogin2->set('confirmed', 0);
        $linkedlogin2->update();
        $this->assertFalse(api::can_delete_linked_login($linkedlogin->get('id')));
        $this->setAdminUser();
        $this->assertTrue(api::can_delete_linked_login($linkedlogin->get('id')));

        $this->setAdminUser();
        delete_user($user1);
        $this->assertTrue(api::can_delete_linked_login($linkedlogin->get('id')));
    }

    public function test_delete_linked_login() {
        $this->setAdminUser();
        $issuer1 = core\oauth2\api::create_standard_issuer('facebook');
        $issuer2 = core\oauth2\api::create_standard_issuer('google');

        $user1 = $this->getDataGenerator()->create_user(['auth' => 'manual']);
        $user2 = $this->getDataGenerator()->create_user(['auth' => 'oauth2']);

        $usercontext1 = context_user::instance($user1->id);

        // Wrong parameter.
        $this->assertFalse(api::can_delete_linked_login(0));
        $this->assertFalse(api::can_delete_linked_login(1));

        $record = new stdClass();
        $record->userid = $user1->id;
        $record->issuerid = $issuer1->get('id');
        $record->username = 'xx';
        $record->email = $user1->email;
        $record->confirmed = 0;
        $record->confirmtoken = random_string(32);
        $record->confirmtokenexpires = time() + api::CONFIRMATION_EXPIRY;
        $linkedlogin = new linked_login(0, $record);
        $linkedlogin->create();

        $this->setUser(null);
        $this->assertTrue(api::delete_linked_login($linkedlogin->get('id')));
        $this->assertEmpty(linked_login::get_records(['userid' => $user1->id]));
        $this->assertTrue(api::delete_linked_login($linkedlogin->get('id')));
    }

    public function test_get_login_issuers_menu() {
        global $CFG;

        $this->setAdminUser();
        $issuer1 = core\oauth2\api::create_standard_issuer('facebook');
        $issuer2 = core\oauth2\api::create_standard_issuer('google');

        $issuers = api::get_login_issuers_menu();
        $this->assertSame([], $issuers);

        $CFG->auth = 'manual,oauth2';

        $issuers = api::get_login_issuers_menu();
        $this->assertContains('Facebook', $issuers);
    }

    public function test_get_visible_linked_logins() {
        global $CFG;

        $this->setAdminUser();
        $issuer1 = core\oauth2\api::create_standard_issuer('facebook');
        $issuer1->set('showonloginpage', 1);
        $issuer1->set('enabled', 1);
        $issuer1->update();
        $issuer2 = core\oauth2\api::create_standard_issuer('google');

        $user1 = $this->getDataGenerator()->create_user(['auth' => 'manual']);
        $user2 = $this->getDataGenerator()->create_user(['auth' => 'oauth2']);

        $this->setUser($user1);

        $record = new stdClass();
        $record->userid = $user1->id;
        $record->issuerid = $issuer1->get('id');
        $record->username = 'xx';
        $record->email = $user1->email;
        $record->confirmed = 1;
        $linkedlogin = new linked_login(0, $record);
        $linkedlogin->create();

        $record = new stdClass();
        $record->userid = $user1->id;
        $record->issuerid = $issuer2->get('id');
        $record->username = 'aa';
        $record->email = $user1->email;
        $record->confirmed = 1;
        $linkedlogin2 = new linked_login(0, $record);
        $linkedlogin2->create();

        $visible = api::get_visible_linked_logins();
        $this->assertSame([], $visible);

        $CFG->auth = 'manual,oauth2';
        $this->assertTrue($issuer1->is_login_enabled());
        $this->assertFalse($issuer2->is_login_enabled());
        $visible = api::get_visible_linked_logins();
        $this->assertCount(1, $visible);
        $first = reset($visible);
        $this->assertInstanceOf(linked_login::class, $first);
        $this->assertSame($user1->id, $first->get('userid'));
        $this->assertSame($issuer1->get('id'), $first->get('issuerid'));
    }
}
