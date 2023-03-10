<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package mod_facetoface
 */

use \mod_facetoface\signup_helper;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    // It must be included from a Moodle page.
}

global $CFG;
require_once($CFG->dirroot . '/mod/facetoface/lib.php');

class mod_facetoface_cleanup_task_testcase extends advanced_testcase {
    /**
     * PhpUnit fixture method that runs before the test method executes.
     */
    public function setUp(): void {
        parent::setUp();
        set_config('facetoface_allow_legacy_notifications', 1);
    }

    /**
     * Tests the Cleanup Task for Face-to-face.
     *
     * This task does two things, it cancels any user sessions for suspended, deleted users.
     * It also cleans up any unused custom rooms older than a set period.
     *
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function test_cleanup_task() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/lib.php');

        $time = time();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $course1 = $this->getDataGenerator()->create_course();

        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));

        $this->getDataGenerator()->enrol_user($user1->id, $course1->id, $teacherrole->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course1->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($user3->id, $course1->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($user4->id, $course1->id, $studentrole->id);

        $user1ja = \totara_job\job_assignment::create_default($user1->id); // Manager.
        \totara_job\job_assignment::create_default($user2->id, array('managerjaid' => $user1ja->id));
        \totara_job\job_assignment::create_default($user3->id, array('managerjaid' => $user1ja->id));
        \totara_job\job_assignment::create_default($user4->id, array('managerjaid' => $user1ja->id));

        /** @var \mod_facetoface\testing\generator $facetofacegenerator */
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $facetoface = $facetofacegenerator->create_instance([
            'course' => $course1->id,
            'usercalentry' => false,
            'multiplesessions' => 1
        ]);
        $room1 = $facetofacegenerator->add_custom_room(['timecreated' => $time - (DAYSECS * 1.1)]);
        $room2 = $facetofacegenerator->add_custom_room(['timecreated' => $time - (DAYSECS * 1.1)]);
        $room3 = $facetofacegenerator->add_site_wide_room(['timecreated' => $time - (DAYSECS * 1.1)]);
        $room4 = $facetofacegenerator->add_site_wide_room(['timecreated' => $time - (DAYSECS * 1.1)]);
        $session1id = $facetofacegenerator->add_session([
            'facetoface' => $facetoface->id,
            'sessiondates' => [
                (object)[
                    'timestart' => $time + HOURSECS * 36,
                    'timefinish' => $time + HOURSECS * 38,
                    'sessiontimezone' => 'Pacific/Auckland',
                    'roomids' => [$room2->id]
                ]
            ]
        ]);
        $seminarevent1 = new \mod_facetoface\seminar_event($session1id);
        $helper1 = new \mod_facetoface\attendees_helper($seminarevent1);

        $session2id = $facetofacegenerator->add_session([
            'facetoface' => $facetoface->id,
            'sessiondates' => [
                (object)[
                    'timestart' => $time + HOURSECS * 39,
                    'timefinish' => $time + HOURSECS * 41,
                    'sessiontimezone' => 'Pacific/Auckland',
                    'roomids' => [$room3->id]
                ]
            ]
        ]);
        $seminarevent2 = new \mod_facetoface\seminar_event($session2id);
        $helper2 = new \mod_facetoface\attendees_helper($seminarevent2);

        // Sign the users up to the first session.
        $sink = $this->redirectMessages();
        signup_helper::signup(\mod_facetoface\signup::create($user2->id, $seminarevent1)->set_skipusernotification()->set_fromuser($user1));
        signup_helper::signup(\mod_facetoface\signup::create($user3->id, $seminarevent1)->set_skipusernotification()->set_fromuser($user1));
        signup_helper::signup(\mod_facetoface\signup::create($user4->id, $seminarevent1)->set_skipusernotification()->set_fromuser($user1));
        $this->executeAdhocTasks();
        $this->assertSame(3, $sink->count());
        $sink->clear();

        // Now sign them up to the second session.
        $sink = $this->redirectMessages();
        signup_helper::signup(\mod_facetoface\signup::create($user2->id, $seminarevent2)->set_skipusernotification()->set_fromuser($user1));
        signup_helper::signup(\mod_facetoface\signup::create($user3->id, $seminarevent2)->set_skipusernotification()->set_fromuser($user1));
        signup_helper::signup(\mod_facetoface\signup::create($user4->id, $seminarevent2)->set_skipusernotification()->set_fromuser($user1));
        $this->executeAdhocTasks();
        $this->assertSame(3, $sink->count());
        $sink->clear();

        // Confirm the signups for session 1.
        $this->assertCount(3, $helper1->get_attendees_with_codes([\mod_facetoface\signup\state\booked::get_code()]));
        $this->assertCount(0, $helper1->get_attendees_with_codes([\mod_facetoface\signup\state\user_cancelled::get_code()]));

        // Confirm the signups for session 2.
        $this->assertCount(3, $helper2->get_attendees_with_codes([\mod_facetoface\signup\state\booked::get_code()]));
        $this->assertCount(0, $helper2->get_attendees_with_codes([\mod_facetoface\signup\state\user_cancelled::get_code()]));

        // Suspend user 3.
        $user3 = $DB->get_record('user', array('id'=>$user3->id, 'deleted'=>0), '*', MUST_EXIST);
        $user3->suspended = 1;
        user_update_user($user3, false);

        // Delete user 4.
        delete_user($user4);

        // Check that both rooms still exist.
        $rooms = $DB->get_records('facetoface_room');
        $this->assertCount(4, $rooms);
        $this->assertArrayHasKey($room1->id, $rooms);
        $this->assertArrayHasKey($room2->id, $rooms);
        $this->assertArrayHasKey($room3->id, $rooms);
        $this->assertArrayHasKey($room4->id, $rooms);

        // The deleted user will be automatically updated but the suspended user won't.
        $this->assertCount(2, $helper1->get_attendees_with_codes([\mod_facetoface\signup\state\booked::get_code()]));
        $this->assertCount(1, $helper1->get_attendees_with_codes([\mod_facetoface\signup\state\user_cancelled::get_code()]));
        $this->assertCount(2, $helper2->get_attendees_with_codes([\mod_facetoface\signup\state\booked::get_code()]));
        $this->assertCount(1, $helper2->get_attendees_with_codes([\mod_facetoface\signup\state\user_cancelled::get_code()]));

        // Now cancel the second session.
        $seminarevent2->cancel();

        // This should have lead to all users in session 2 being marked as cancelled by session cancellation.
        $this->assertCount(2, $helper1->get_attendees_with_codes([\mod_facetoface\signup\state\booked::get_code()]));
        $this->assertCount(1, $helper1->get_attendees_with_codes([\mod_facetoface\signup\state\user_cancelled::get_code()]));
        $this->assertCount(0, $helper2->get_attendees_with_codes([\mod_facetoface\signup\state\booked::get_code()]));
        $this->assertCount(1, $helper2->get_attendees_with_codes([\mod_facetoface\signup\state\user_cancelled::get_code()]));
        $this->assertCount(2, $helper2->get_attendees_with_codes([\mod_facetoface\signup\state\event_cancelled::get_code()]));

        // Run the cleanup task.
        $task = new \mod_facetoface\task\cleanup_task();
        $task->execute();

        $this->assertDebuggingNotCalled('Cleanup task is zealously cancelling users. Fix it!');

        // We should now have updated statuses for session 1.
        $this->assertCount(1, $helper1->get_attendees_with_codes([\mod_facetoface\signup\state\booked::get_code()]));
        $this->assertCount(2, $helper1->get_attendees_with_codes([\mod_facetoface\signup\state\user_cancelled::get_code()]));
        // And nothing about session 2 should have changed.
        $this->assertCount(0, $helper2->get_attendees_with_codes([\mod_facetoface\signup\state\booked::get_code()]));
        $this->assertCount(1, $helper2->get_attendees_with_codes([\mod_facetoface\signup\state\user_cancelled::get_code()]));
        $this->assertCount(2, $helper2->get_attendees_with_codes([\mod_facetoface\signup\state\event_cancelled::get_code()]));

        // Check that room1 has been deleted.
        $rooms = $DB->get_records('facetoface_room');
        $this->assertCount(3, $rooms);
        $this->assertArrayNotHasKey($room1->id, $rooms);
        $this->assertArrayHasKey($room2->id, $rooms);
        $this->assertArrayHasKey($room3->id, $rooms);
        $this->assertArrayHasKey($room4->id, $rooms);

        $sink->close();

        // Run the cleanup task.
        $task = new \mod_facetoface\task\cleanup_task();
        $task->execute();

        $this->assertDebuggingNotCalled('Cleanup task is zealously cancelling users. Fix it!');
    }

    public function test_suspended_users() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/lib.php');

        $time = time();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $course1 = $this->getDataGenerator()->create_course();

        $studentrole = $DB->get_record('role', array('shortname' => 'student'));

        $this->getDataGenerator()->enrol_user($user1->id, $course1->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course1->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($user3->id, $course1->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($user4->id, $course1->id, $studentrole->id);

        /** @var \mod_facetoface\testing\generator $facetofacegenerator */
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $facetoface = $facetofacegenerator->create_instance([
            'course' => $course1->id,
            'usercalentry' => false,
            'multiplesessions' => 1
        ]);

        // Booking is open, session in the future
        $session1id = $facetofacegenerator->add_session([
            'facetoface' => $facetoface->id,
            'sessiondates' => [
                (object)[
                    'timestart' => $time + HOURSECS * 36,
                    'timefinish' => $time + HOURSECS * 38,
                    'sessiontimezone' => 'Pacific/Auckland',
                ]
            ]
        ]);
        $seminarevent1 = new \mod_facetoface\seminar_event($session1id);
        $helper1 = new \mod_facetoface\attendees_helper($seminarevent1);

        // Session 2 will be in past
        $session2id = $facetofacegenerator->add_session([
            'facetoface' => $facetoface->id,
            'sessiondates' => [
                (object)[
                    'timestart' => $time + HOURSECS * 39,
                    'timefinish' => $time + HOURSECS * 41,
                    'sessiontimezone' => 'Pacific/Auckland',
                ]
            ]
        ]);
        $seminarevent2 = new \mod_facetoface\seminar_event($session2id);
        $helper2 = new \mod_facetoface\attendees_helper($seminarevent2);

        // Session 3 will be in progress
        $session3id = $facetofacegenerator->add_session([
            'facetoface' => $facetoface->id,
            'sessiondates' => [
                (object)[
                    'timestart' => $time + HOURSECS * 42,
                    'timefinish' => $time + HOURSECS * 44,
                    'sessiontimezone' => 'Pacific/Auckland',
                ]
            ]
        ]);
        $seminarevent3 = new \mod_facetoface\seminar_event($session3id);
        $helper3 = new \mod_facetoface\attendees_helper($seminarevent3);

        // Session is waitlisted
        $session4id = $facetofacegenerator->add_session([
            'facetoface' => $facetoface->id,
            'sessiondates' => [
            ]
        ]);
        $seminarevent4 = new \mod_facetoface\seminar_event($session4id);
        $helper4 = new \mod_facetoface\attendees_helper($seminarevent4);

        // Sign the users up to the session 1.
        $sink = $this->redirectMessages();
        signup_helper::signup(\mod_facetoface\signup::create($user1->id, $seminarevent1)->set_skipusernotification());
        signup_helper::signup(\mod_facetoface\signup::create($user2->id, $seminarevent1)->set_skipusernotification());
        signup_helper::signup(\mod_facetoface\signup::create($user3->id, $seminarevent1)->set_skipusernotification());
        signup_helper::signup(\mod_facetoface\signup::create($user4->id, $seminarevent1)->set_skipusernotification());
        $this->executeAdhocTasks();
        $sink->clear();
        // Confirm the signups for session 1.
        $this->assertCount(4, $helper1->get_attendees_with_codes([\mod_facetoface\signup\state\booked::get_code()]));
        $this->assertCount(0, $helper1->get_attendees_with_codes([\mod_facetoface\signup\state\user_cancelled::get_code()]));

        // Sign the users up to the session 2.
        $sink = $this->redirectMessages();
        signup_helper::signup(\mod_facetoface\signup::create($user1->id, $seminarevent2)->set_skipusernotification());
        signup_helper::signup(\mod_facetoface\signup::create($user2->id, $seminarevent2)->set_skipusernotification());
        signup_helper::signup(\mod_facetoface\signup::create($user3->id, $seminarevent2)->set_skipusernotification());
        signup_helper::signup(\mod_facetoface\signup::create($user4->id, $seminarevent2)->set_skipusernotification());
        $this->executeAdhocTasks();
        $sink->clear();
        // Confirm the signups for session 2.
        $this->assertCount(4, $helper2->get_attendees_with_codes([\mod_facetoface\signup\state\booked::get_code()]));
        $this->assertCount(0, $helper2->get_attendees_with_codes([\mod_facetoface\signup\state\user_cancelled::get_code()]));

        // Sign the users up to the session 3.
        $sink = $this->redirectMessages();
        signup_helper::signup(\mod_facetoface\signup::create($user1->id, $seminarevent3)->set_skipusernotification());
        signup_helper::signup(\mod_facetoface\signup::create($user2->id, $seminarevent3)->set_skipusernotification());
        signup_helper::signup(\mod_facetoface\signup::create($user3->id, $seminarevent3)->set_skipusernotification());
        signup_helper::signup(\mod_facetoface\signup::create($user4->id, $seminarevent3)->set_skipusernotification());
        $this->executeAdhocTasks();
        $sink->clear();
        // Confirm the signups for session 3.
        $this->assertCount(4, $helper3->get_attendees_with_codes([\mod_facetoface\signup\state\booked::get_code()]));
        $this->assertCount(0, $helper3->get_attendees_with_codes([\mod_facetoface\signup\state\user_cancelled::get_code()]));

        // Sign the users up to the session 4.
        $sink = $this->redirectMessages();
        signup_helper::signup(\mod_facetoface\signup::create($user1->id, $seminarevent4)->set_skipusernotification());
        signup_helper::signup(\mod_facetoface\signup::create($user2->id, $seminarevent4)->set_skipusernotification());
        signup_helper::signup(\mod_facetoface\signup::create($user3->id, $seminarevent4)->set_skipusernotification());
        signup_helper::signup(\mod_facetoface\signup::create($user4->id, $seminarevent4)->set_skipusernotification());
        $sink->clear();
        // Confirm the signups for session 4.
        $this->assertCount(4, $helper4->get_attendees_with_codes([\mod_facetoface\signup\state\waitlisted::get_code()]));
        $this->assertCount(0, $helper4->get_attendees_with_codes([\mod_facetoface\signup\state\user_cancelled::get_code()]));

        // Move dates back.
        $seminarevent = new \mod_facetoface\seminar_event($session2id);
        \mod_facetoface\seminar_event_helper::merge_sessions($seminarevent, [
            (object)[
                'timestart' => $time - HOURSECS * 38,
                'timefinish' => $time - HOURSECS * 36,
                'sessiontimezone' => 'Pacific/Auckland',
            ]
        ]);
        $seminarevent = new \mod_facetoface\seminar_event($session3id);
        \mod_facetoface\seminar_event_helper::merge_sessions($seminarevent, [
            (object)[
                'timestart' => $time - HOURSECS * 36,
                'timefinish' => $time + HOURSECS * 36,
                'sessiontimezone' => 'Pacific/Auckland',
            ]
        ]);

        // Suspend user 3.
        $user3 = $DB->get_record('user', array('id' => $user3->id), '*', MUST_EXIST);
        $user3->suspended = 1;
        user_update_user($user3, false);

        // Run the cleanup task.
        $task = new \mod_facetoface\task\cleanup_task();
        $task->execute();

        // Session 1 is open for booking, we should now have updated statuses for session 1.
        $this->assertCount(3, $helper1->get_attendees_with_codes([\mod_facetoface\signup\state\booked::get_code()]));
        $this->assertCount(1, $helper1->get_attendees_with_codes([\mod_facetoface\signup\state\user_cancelled::get_code()]));

        // Session 2 is over, we should not have updated statuses for session 2.
        $this->assertCount(4, $helper2->get_attendees_with_codes([\mod_facetoface\signup\state\booked::get_code()]));
        $this->assertCount(0, $helper2->get_attendees_with_codes([\mod_facetoface\signup\state\user_cancelled::get_code()]));

        // Session 3 in progress, we should not have updated statuses for session 3.
        $this->assertCount(4, $helper3->get_attendees_with_codes([\mod_facetoface\signup\state\booked::get_code()]));
        $this->assertCount(0, $helper3->get_attendees_with_codes([\mod_facetoface\signup\state\user_cancelled::get_code()]));

        // Session 4 is waitlisted, we should now have updated statuses for session 4.
        $this->assertCount(3, $helper4->get_attendees_with_codes([\mod_facetoface\signup\state\waitlisted::get_code()]));
        $this->assertCount(1, $helper4->get_attendees_with_codes([\mod_facetoface\signup\state\user_cancelled::get_code()]));

        $sink->close();
    }
}
