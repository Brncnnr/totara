<?php
/*
 * This file is part of Totara Learn
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Tests of our cahgneas and hacks in user/lib.php file.
 */
class totara_core_userlib_testcase extends advanced_testcase {
    public function test_user_suspend_user() {
        global $CFG, $DB;
        require_once("$CFG->dirroot/user/lib.php");

        $user = $this->getDataGenerator()->create_user(['suspended' => 0, 'deleted' => 0]);
        $user->timecreated = (string)(time() - 200);
        $user->timemodified = (string)(time() - 100);
        $DB->update_record('user', $user);
        $sink = $this->redirectEvents();
        $this->setCurrentTimeStart();
        $result = user_suspend_user($user->id);
        $this->assertTrue($result);
        $events = $sink->get_events();
        $sink->close();
        $this->assertCount(2, $events);
        $this->assertInstanceOf(core\event\user_updated::class, $events[0]);
        $this->assertInstanceOf(totara_core\event\user_suspended::class, $events[1]);
        $newuser = $DB->get_record('user', ['id' => $user->id]);
        $this->assertSame($user->timecreated, $newuser->timecreated);
        $this->assertTimeCurrent($newuser->timemodified);
        $this->assertSame('1', $newuser->suspended);

        $user = $this->getDataGenerator()->create_user(['suspended' => 1, 'deleted' => 0]);
        $sink = $this->redirectEvents();
        $result = user_suspend_user($user->id);
        $this->assertTrue($result);
        $newuser = $DB->get_record('user', ['id' => $user->id]);
        $this->assertEquals($user, $newuser);
        $events = $sink->get_events();
        $sink->close();
        $this->assertCount(0, $events);

        $user = $this->getDataGenerator()->create_user(['suspended' => 0, 'deleted' => 1]);
        $sink = $this->redirectEvents();
        $result = user_suspend_user($user->id);
        $this->assertFalse($result);
        $newuser = $DB->get_record('user', ['id' => $user->id]);
        $this->assertEquals($user, $newuser);
        $events = $sink->get_events();
        $sink->close();
        $this->assertCount(0, $events);

        // Invalid use

        $guest = guest_user();
        $sink = $this->redirectEvents();
        $result = user_suspend_user($guest->id);
        $this->assertFalse($result);
        $newuser = $DB->get_record('user', ['id' => $user->id]);
        $this->assertEquals($user, $newuser);
        $events = $sink->get_events();
        $sink->close();
        $this->assertCount(0, $events);

        $result = user_suspend_user(0);
        $this->assertFalse($result);
        $events = $sink->get_events();
        $sink->close();
        $this->assertCount(0, $events);
    }

    public function test_user_unsuspend_user() {
        global $CFG, $DB;
        require_once("$CFG->dirroot/user/lib.php");

        $user = $this->getDataGenerator()->create_user(['suspended' => 1, 'deleted' => 0]);
        $user->timecreated = (string)(time() - 200);
        $user->timemodified = (string)(time() - 100);
        $DB->update_record('user', $user);
        $sink = $this->redirectEvents();
        $this->setCurrentTimeStart();
        $result = user_unsuspend_user($user->id);
        $this->assertTrue($result);
        $events = $sink->get_events();
        $sink->close();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(core\event\user_updated::class, $events[0]);
        $newuser = $DB->get_record('user', ['id' => $user->id]);
        $this->assertSame($user->timecreated, $newuser->timecreated);
        $this->assertTimeCurrent($newuser->timemodified);
        $this->assertSame('0', $newuser->suspended);

        $user = $this->getDataGenerator()->create_user(['suspended' => 0, 'deleted' => 0]);
        $sink = $this->redirectEvents();
        $result = user_unsuspend_user($user->id);
        $this->assertTrue($result);
        $newuser = $DB->get_record('user', ['id' => $user->id]);
        $this->assertEquals($user, $newuser);
        $events = $sink->get_events();
        $sink->close();
        $this->assertCount(0, $events);

        $user = $this->getDataGenerator()->create_user(['suspended' => 1, 'deleted' => 1]);
        $sink = $this->redirectEvents();
        $result = user_unsuspend_user($user->id);
        $this->assertFalse($result);
        $newuser = $DB->get_record('user', ['id' => $user->id]);
        $this->assertEquals($user, $newuser);
        $events = $sink->get_events();
        $sink->close();
        $this->assertCount(0, $events);

        // Invalid use

        $guest = guest_user();
        $sink = $this->redirectEvents();
        $result = user_unsuspend_user($guest->id);
        $this->assertFalse($result);
        $events = $sink->get_events();
        $sink->close();
        $this->assertCount(0, $events);

        $result = user_unsuspend_user(0);
        $this->assertFalse($result);
        $events = $sink->get_events();
        $sink->close();
        $this->assertCount(0, $events);
    }

    public function test_user_change_password() {
        global $CFG, $DB;
        require_once("$CFG->dirroot/user/lib.php");

        $CFG->passwordreuselimit = 3;

        $oldpassoword = 'SomePassword3_';
        $user = $this->getDataGenerator()->create_user(['suspended' => 0, 'deleted' => 0, 'auth' => 'manual', 'password' => $oldpassoword]);
        $user->timecreated = (string)(time() - 200);
        $user->timemodified = (string)(time() - 100);
        $DB->update_record('user', $user);
        $this->assertTrue(validate_internal_user_password($user, $oldpassoword));
        $prevpasswords = $DB->get_records('user_password_history', ['userid' => $user->id], "id ASC");
        $this->assertCount(0, $prevpasswords);

        $newpassoword = 'SomePassword4_';
        $sink = $this->redirectEvents();
        $result = user_change_password($user->id, $newpassoword);
        $this->assertTrue($result);
        $events = $sink->get_events();
        $sink->close();
        $newuser = $DB->get_record('user', ['id' => $user->id]);
        $this->assertSame($user->timecreated, $newuser->timecreated);
        $this->assertSame($user->timemodified, $newuser->timemodified);
        $this->assertTrue(validate_internal_user_password($newuser, $newpassoword));
        $this->assertCount(1, $events);
        $this->assertInstanceOf(core\event\user_password_updated::class, $events[0]);
        $prevpasswords = $DB->get_records('user_password_history', ['userid' => $user->id], "id ASC");
        $this->assertCount(1, $prevpasswords);
        check_user_preferences_loaded($newuser);
        $this->assertFalse(array_key_exists('auth_forcepasswordchange', $newuser->preference));

        $sink = $this->redirectEvents();
        $result = user_change_password($user->id, $newpassoword, ['forcepasswordchange' => true]);
        $this->assertTrue($result);
        $events = $sink->get_events();
        $sink->close();
        $newuser = $DB->get_record('user', ['id' => $user->id]);
        $this->assertSame($user->timecreated, $newuser->timecreated);
        $this->assertSame($user->timemodified, $newuser->timemodified);
        $this->assertTrue(validate_internal_user_password($newuser, $newpassoword));
        $this->assertCount(0, $events);
        $prevpasswords = $DB->get_records('user_password_history', ['userid' => $user->id], "id ASC");
        $this->assertCount(2, $prevpasswords);
        check_user_preferences_loaded($newuser);
        $this->assertSame('1', $newuser->preference['auth_forcepasswordchange']);

        // Invalid use

        $guest = guest_user();
        $sink = $this->redirectEvents();
        $result = user_change_password($guest->id, $newpassoword);
        $this->assertFalse($result);
        $events = $sink->get_events();
        $sink->close();
        $this->assertCount(0, $events);

        $result = user_change_password(0, $newpassoword);
        $this->assertFalse($result);
        $events = $sink->get_events();
        $sink->close();
        $this->assertCount(0, $events);
    }
}
