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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();

use mod_facetoface\{
    seminar_event,
    signup,
    seminar_session,
    role,
    asset_helper,
    room_helper,
    seminar,
    signup_status,
    facilitator_helper
};
use mod_facetoface\signup\state\{booked, fully_attended, partially_attended};

class mod_facetoface_delete_event_testcase extends advanced_testcase {
    /**
     * PhpUnit fixture method that runs before the test method executes.
     */
    public function setUp(): void {
        parent::setUp();
        set_config('facetoface_allow_legacy_notifications', 1);
    }

    /**
     * @return seminar_event
     */
    private function create_seminar_event(): seminar_event {
        $gen = $this->getDataGenerator();
        $course = $gen->create_course([], ['createsections' => 1]);

        $f2fgen = $gen->get_plugin_generator('mod_facetoface');
        $f2f = $f2fgen->create_instance(['course' => $course->id]);


        $e = new seminar_event();
        $e->set_facetoface($f2f->id);
        $e->save();
        return $e;
    }

    /**
     * For the event that is in the future, when it gets to delete, the system will try to cancel the event first,
     * because it wants to send out the messages to the learners/trainers/manager that the event is no longer available.
     *
     * Then afterward, it starts deleting the record (hard deleting). This test suite is to ensure that the messages got sent out
     * to related users.
     *
     * @return void
     */
    public function test_delete_event_with_email_sendingout(): void {
        global $DB;

        $this->setAdminUser();

        $event = $this->create_seminar_event();
        $eventid = $event->get_id();

        $gen = $this->getDataGenerator();

        $s = new seminar_session();
        $s->set_sessionid($event->get_id());
        $s->set_timestart(time() + 3600);
        $s->set_timefinish(time() + 7200);
        $s->save();

        $sink = $this->redirectMessages();
        for ($i = 0; $i < 2; $i++) {
            $user = $gen->create_user();
            $gen->enrol_user($user->id, $event->get_seminar()->get_course());

            $signup = signup::create($user->id, $event);
            $signup->save();

            $signup->switch_state(booked::class);
        }

        // Clearing emails that sent out to users for confirmation about booking.
        $this->executeAdhocTasks();
        $sink->clear();

        // Start adding trainer roles to the event
        $teacher = $DB->get_record('role', ['shortname' => 'teacher']);
        for ($i = 0; $i < 2; $i++) {
            $user = $gen->create_user();
            $gen->enrol_user($user->id, $event->get_seminar()->get_course(), 'teacher');

            $role = new role();
            $role->set_roleid($teacher->id);
            $role->set_userid($user->id);
            $role->set_sessionid($event->get_id());
            $role->save();
        }

        $event->delete();
        $this->executeAdhocTasks();

        $messages = $sink->get_messages();
        $this->assertCount(4, $messages);

        $this->assertFalse($DB->record_exists('facetoface_sessions', ['id' => $eventid]));
        $this->assertFalse($DB->record_exists('facetoface_sessions_dates', ['sessionid' => $eventid]));

        $this->assertFalse($DB->record_exists('facetoface_session_roles', ['sessionid' => $eventid]));
        $this->assertFalse($DB->record_exists('facetoface_signups', ['sessionid' => $eventid]));
    }

    /**
     * For past event, the cancellation process will be skipped. Test suite to assure that no messages sent out to related users at
     * all.
     * @return void
     */
    public function test_delete_event_without_email_sendingout(): void {
        global $DB;

        $this->setAdminUser();

        $event = $this->create_seminar_event();
        $eventid = $event->get_id();

        $s = new seminar_session();
        $s->set_timestart(time() - 7200);
        $s->set_timefinish(time() - 3600);
        $s->set_sessionid($event->get_id());
        $s->save();

        $sink = $this->redirectMessages();
        $gen = $this->getDataGenerator();
        for ($i = 0; $i < 2; $i++) {
            $user = $gen->create_user();
            $gen->enrol_user($user->id, $event->get_seminar()->get_course());

            $signup = signup::create($user->id, $event);
            $signup->save();

            $signup->switch_state(booked::class);
        }

        // Start sending out messages to the learners here, so that the last assertion would be more reasonable.
        $this->executeAdhocTasks();
        $sink->clear();

        $teacher = $DB->get_record('role', ['shortname' => 'teacher']);

        for ($i = 0; $i < 2; $i++) {
            $user = $gen->create_user();
            $gen->enrol_user($user->id, $event->get_seminar()->get_course(), 'teacher');

            $role = new role();
            $role->set_sessionid($event->get_id());
            $role->set_userid($user->id);
            $role->set_roleid($teacher->id);
            $role->save();
        }

        $event->delete();
        $this->executeAdhocTasks();

        $messages = $sink->get_messages();
        $this->assertEmpty($messages);

        $this->assertFalse($DB->record_exists('facetoface_sessions', ['id' => $eventid]));
    }

    /**
     * @return void
     */
    public function test_delete_event_with_custom_room(): void {
        global $DB;

        $this->setAdminUser();

        $time = time();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        // Allow one day for unassigned room as it can be just created and not stored in seminar session yet.
        $room = $generator->add_custom_room(['timecreated' => $time - (DAYSECS * 1.1)]);
        $roomid = $room->id;

        $seminarevent = $this->create_seminar_event();
        $s = new seminar_session();
        $s->set_timestart(time() + 3600);
        $s->set_timefinish(time() + 7200);
        $s->set_sessionid($seminarevent->get_id());
        $s->save();
        room_helper::sync($s->get_id(), [$roomid]);

        // Add this custom room to be used at different seminar event, so that we can check whether the room is being
        // deleted after the first event cancelled or not.
        $seminarevent2 = $this->create_seminar_event();
        $s2 = new seminar_session();
        $s2->set_timestart(time() + 7200);
        $s2->set_timefinish(time() + 7200 + 3600);
        $s2->set_sessionid($seminarevent2->get_id());
        $s2->save();
        room_helper::sync($s2->get_id(), [$roomid]);

        $seminareventid = $seminarevent->get_id();
        $seminarevent->delete();
        $this->assertFalse($DB->record_exists('facetoface_sessions', ['id' => $seminareventid]));
        $this->assertTrue($DB->record_exists('facetoface_room', ['id' => $roomid]));

        $seminarevent2id = $seminarevent2->get_id();
        $seminarevent2->delete();

        // Run the cleanup task.
        $task = new \mod_facetoface\task\cleanup_task();
        $task->execute();

        $this->assertFalse($DB->record_exists('facetoface_sessions', ['id' => $seminarevent2id]));
        $this->assertFalse($DB->record_exists('facetoface_room', ['id' => $roomid]));
    }

    /**
     * @return void
     */
    public function test_delete_event_with_custom_assets(): void {
        global $DB;

        $this->setAdminUser();

        $time = time();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        // Allow one day for unassigned room as it can be just created and not stored in seminar session yet.
        $asset1 = $generator->add_custom_asset(['timecreated' => $time - (DAYSECS * 1.1)]);
        $asset1id = $asset1->id;
        $asset2 = $generator->add_custom_asset(['timecreated' => $time - (DAYSECS * 1.1)]);
        $asset2id = $asset2->id;

        $seminarevent1 = $this->create_seminar_event();
        $seminarevent2 = $this->create_seminar_event();

        $time = time();

        /** @var seminar_event $seminarevent */
        foreach ([$seminarevent1, $seminarevent2] as $seminarevent) {
            for ($i = 0; $i < 2; $i++) {
                $time += 7200;

                $s = new seminar_session();
                $s->set_timestart($time);
                $s->set_timefinish($time + 2900);
                $s->set_sessionid($seminarevent->get_id());
                $s->save();

                asset_helper::sync($s->get_id(), [$asset1id, $asset2id]);
            }
        }

        // Deleting the first seminar event does not clear the custom assets at all, because custom asset is also
        // being linked with different seminar_event.
        $seminarevent1->delete();
        $this->assertTrue($DB->record_exists('facetoface_asset', ['id' => $asset1id]));
        $this->assertTrue($DB->record_exists('facetoface_asset', ['id' => $asset2id]));

        $seminarevent2->delete();

        // Run the cleanup task.
        $task = new \mod_facetoface\task\cleanup_task();
        $task->execute();

        $this->assertFalse($DB->record_exists('facetoface_asset', ['id' => $asset2id]));
        $this->assertFalse($DB->record_exists('facetoface_asset', ['id' => $asset1id]));
    }

    /**
     * Test delete seminar event with ad-hoc facilitators.
     */
    public function test_delete_event_with_custom_facilitators(): void {
        global $DB;

        $this->setAdminUser();

        $time = time();
        /** @var \mod_facetoface\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        // Allow one day for unassigned room as it can be just created and not stored in seminar session yet.
        $facilitator1 = $generator->add_custom_facilitator(['timecreated' => $time - (DAYSECS * 1.1)]);
        $facilitator1id = $facilitator1->id;
        $facilitator2 = $generator->add_custom_facilitator(['timecreated' => $time - (DAYSECS * 1.1)]);
        $facilitator2id = $facilitator2->id;

        $seminarevent1 = $this->create_seminar_event();
        $seminarevent2 = $this->create_seminar_event();

        $time = time();

        /** @var seminar_event $seminarevent */
        foreach ([$seminarevent1, $seminarevent2] as $seminarevent) {
            for ($i = 0; $i < 2; $i++) {
                $time += 7200;

                $s = new seminar_session();
                $s->set_timestart($time);
                $s->set_timefinish($time + 2900);
                $s->set_sessionid($seminarevent->get_id());
                $s->save();

                facilitator_helper::sync($s->get_id(), [$facilitator1id, $facilitator2id]);
            }
        }

        // Deleting the first seminar event does not clear the custom assets at all, because custom asset is also
        // being linked with different seminar_event.
        $seminarevent1->delete();
        $this->assertTrue($DB->record_exists('facetoface_facilitator', ['id' => $facilitator1id]));
        $this->assertTrue($DB->record_exists('facetoface_facilitator', ['id' => $facilitator2id]));

        $seminarevent2->delete();

        // Run the cleanup task.
        $task = new \mod_facetoface\task\cleanup_task();
        $task->execute();

        $this->assertFalse($DB->record_exists('facetoface_facilitator', ['id' => $facilitator2id]));
        $this->assertFalse($DB->record_exists('facetoface_facilitator', ['id' => $facilitator1id]));
    }

    /**
     * Create a future seminar event and sign up a user.
     * @param int $f2fid
     * @return signup
     */
    private function make_signup_for_seminar(int $f2fid, int $userid): signup {
        // Just boring boilerplate code as usual.
        $gen = $this->getDataGenerator();
        /** @var \mod_facetoface\testing\generator $f2fgen */
        $f2fgen = $gen->get_plugin_generator('mod_facetoface');
        $f2fevtid = $f2fgen->add_session(['facetoface' => $f2fid]);
        $seminarevent = new seminar_event($f2fevtid);
        $signup = signup::create($userid, $seminarevent)->save();
        signup_status::create($signup, new booked($signup))->save();
        $this->assertInstanceOf(booked::class, $signup->get_state());
        return $signup;
    }

    /**
     * Create a user, a course, a seminar, a future seminar event, sign up a user and set the event past.
     * @return array of [courseID, seminarID, userID, signup]
     */
    private function make_signup(): array {
        // Just boring boilerplate code as usual.
        $gen = $this->getDataGenerator();
        $user = $gen->create_user();
        $course = $gen->create_course();
        /** @var \mod_facetoface\testing\generator $f2fgen */
        $f2fgen = $gen->get_plugin_generator('mod_facetoface');
        $f2fid = $f2fgen->create_instance(['course' => $course->id])->id;
        $signup = $this->make_signup_for_seminar($f2fid, $user->id);
        return [$course->id, $f2fid, $user->id, $signup];
    }

    /**
     * @return array of [ offsetToTimeStart ]
     */
    public function data_timestart_delta(): array {
        return [
            [ -YEARSECS ], // past
            [ -HOURSECS ], // present
            [ +WEEKSECS ], // future
        ];
    }

    /**
     * Delete one of seminar events in various time period and see the event grade updated.
     * @dataProvider data_timestart_delta
     */
    public function test_deleting_an_event_updates_grade(int $timediff) {
        [$courseid, $f2fid, $userid, $signup1] = $this->make_signup();
        $signup2 = $this->make_signup_for_seminar($f2fid, $userid);
        /** @var signup $signup1 */
        /** @var signup $signup2 */

        (new seminar($f2fid))->set_eventgradingmethod(seminar::GRADING_METHOD_GRADEHIGHEST)->save();
        $seminarevent1 = $signup1->get_seminar_event();
        $seminarevent2 = $signup2->get_seminar_event();

        // Temporarily go back in time to take attendance.
        /** @var seminar_session $session */
        $session1 = $seminarevent1->get_sessions()->current();
        $session1->set_timestart(time() - WEEKSECS * 4)->set_timefinish(time() - WEEKSECS * 3)->save();
        $session2 = $seminarevent2->get_sessions()->current();
        $session2->set_timestart(time() - WEEKSECS * 2)->set_timefinish(time() - WEEKSECS)->save();

        $signup1->switch_state_with_grade(77., null, fully_attended::class);
        $signup2->switch_state_with_grade(42., null, partially_attended::class);
        $grade_grades = grade_get_grades($courseid, 'mod', 'facetoface', $f2fid, [$userid]);
        $this->assertSame(77., grade_floatval($grade_grades->items[0]->grades[$userid]->grade));

        // Back to the desired time.
        $session2->set_timestart(time() + $timediff)->set_timefinish(time() + $timediff + DAYSECS)->save();

        // The grade must become 42 because the event where the user is given 77 grade no longer exists.
        $seminarevent1->delete();
        $grade_grades = grade_get_grades($courseid, 'mod', 'facetoface', $f2fid, [$userid]);
        $this->assertSame(42., grade_floatval($grade_grades->items[0]->grades[$userid]->grade));
    }

    /**
     * Delete a seminar event in various time period and see the event grade updated.
     * @dataProvider data_timestart_delta
     */
    public function test_deleting_last_event_updates_grade(int $timediff) {
        [$courseid, $f2fid, $userid, $signup] = $this->make_signup();
        /** @var signup $signup */
        $seminarevent = $signup->get_seminar_event();

        // Temporarily go back in time to take attendance.
        /** @var seminar_session $session */
        $session = $seminarevent->get_sessions()->current();
        $session->set_timestart(time() - WEEKSECS * 2)->set_timefinish(time() - WEEKSECS)->save();

        $signup->switch_state_with_grade(77., null, fully_attended::class);
        $grade_grades = grade_get_grades($courseid, 'mod', 'facetoface', $f2fid, [$userid]);
        $this->assertSame(77., grade_floatval($grade_grades->items[0]->grades[$userid]->grade));

        // Back to the desired time.
        $session->set_timestart(time() + $timediff)->set_timefinish(time() + $timediff + DAYSECS)->save();

        // The grade must become null because the user no longer signs up any event.
        $seminarevent->delete();
        $grade_grades = grade_get_grades($courseid, 'mod', 'facetoface', $f2fid, [$userid]);
        $this->assertSame(null, grade_floatval($grade_grades->items[0]->grades[$userid]->grade));
    }

    /**
     * Delete a seminar event in various time period via seminar_event::delete() and see the event grade updated.
     * @dataProvider data_timestart_delta
     */
    public function test_deleting_event_directly_updates_grade(int $timediff) {
        [$courseid, $f2fid, $userid, $signup] = $this->make_signup();
        /** @var signup $signup */
        $seminarevent = $signup->get_seminar_event();

        // Temporarily go back in time to take attendance.
        /** @var seminar_session $session */
        $session = $seminarevent->get_sessions()->current();
        $session->set_timestart(time() - WEEKSECS * 2)->set_timefinish(time() - WEEKSECS)->save();

        $signup->switch_state_with_grade(77., null, fully_attended::class);
        $grade_grades = grade_get_grades($courseid, 'mod', 'facetoface', $f2fid, [$userid]);
        $this->assertSame(77., grade_floatval($grade_grades->items[0]->grades[$userid]->grade));

        // Back to the desired time.
        $session->set_timestart(time() + $timediff)->set_timefinish(time() + $timediff + DAYSECS)->save();

        // The grade must become null because the user no longer signs up any event.
        $seminarevent->delete();
        $grade_grades = grade_get_grades($courseid, 'mod', 'facetoface', $f2fid, [$userid]);
        $this->assertSame(null, grade_floatval($grade_grades->items[0]->grades[$userid]->grade));
    }

    /**
     * Test delete past seminar event will delete the calendar entries.
     */
    public function test_delete_event_with_calendar_entries(): void {
        global $DB;

        $this->setAdminUser();

        /** @var \mod_facetoface\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $course = $this->getDataGenerator()->create_course();
        $seminar = $this->getDataGenerator()->create_module('facetoface', ['course' => $course->id]);


        $time = time();
        $sessiondate = new stdClass();
        $sessiondate->timestart = $time - (DAYSECS * 1.1);
        $sessiondate->timefinish = $sessiondate->timestart + 3 * HOURSECS;
        $sessiondate->sessiontimezone = '99';
        $sessiondate->assetids = [];
        $sid = $generator->add_session(['facetoface' => $seminar->id, 'sessiondates' => [$sessiondate]]);

        // We still need to add the calendar entries.
        $seminarevent = new seminar_event($sid);
        \mod_facetoface\calendar::update_entries($seminarevent);

        $events = $DB->get_records(
            'event',
            ['modulename' => 'facetoface', 'eventtype' => 'facetofacesession', 'courseid' => $course->id],
            'timestart'
        );
        $this->assertEquals(1, count($events));

        $seminarevent->delete();

        $events = $DB->get_records(
            'event',
            ['modulename' => 'facetoface', 'eventtype' => 'facetofacesession', 'courseid' => $course->id],
            'timestart'
        );
        $this->assertEquals(0, count($events));
    }
}