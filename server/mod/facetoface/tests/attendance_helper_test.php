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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();

use mod_facetoface\{seminar_event, seminar, seminar_session, signup, session_status, signup\condition\event_taking_attendance};
use mod_facetoface\signup\state\{booked, fully_attended, not_set, partially_attended};
use mod_facetoface\attendance\attendance_helper;

class mod_facetoface_attendance_helper_testcase extends advanced_testcase {
    /**
     * Create the seminar event with seminar setting as follow:
     * + sessionattendance => ANY
     * @return seminar_event
     */
    private function get_seminar_event(): seminar_event {
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();

        $f2fgen = $gen->get_plugin_generator('mod_facetoface');
        $f2f = $f2fgen->create_instance(['course' => $course->id]);

        $s = new seminar($f2f->id);
        $s->set_sessionattendance(seminar::SESSION_ATTENDANCE_UNRESTRICTED);
        $s->save();

        $event = new seminar_event();
        $event->set_facetoface($s->get_id());
        $event->save();

        return $event;
    }

    /**
     * Create number of users.
     * @param int $numberofusers
     * @return array
     */
    private function create_users(int $numberofusers = 5): array {
        $gen = $this->getDataGenerator();
        $a = [];
        for ($i = 0; $i < $numberofusers; $i++) {
            $a[] = $gen->create_user();
        }

        return $a;
    }

    /**
     * Create 2 sessions in future for an event.
     * @param seminar_event $event
     * @return void
     */
    private function create_sessions(seminar_event $event): void {
        $time = time();
        $s1 = new seminar_session();
        $s1->set_timestart($time + (3600 * 3));
        $s1->set_timefinish($time + (3600 * 4));
        $s1->set_sessionid($event->get_id());
        $s1->save();

        $s2 = new seminar_session();
        $s2->set_timestart($time + (3600 * 5));
        $s2->set_timefinish($time + (3600 * 6));
        $s2->set_sessionid($event->get_id());
        $s2->save();
    }

    /**
     * A unit test to check on UNION ALL sql, as normal UNION would taking distinct record(s) rather
     * than all the record(s) that might be the same.
     *
     * @return void
     */
    public function test_get_calculated_status(): void {

        $event = $this->get_seminar_event();
        $this->create_sessions($event);
        $first = $event->get_sessions()->get_first();

        $users = $this->create_users();

        // This is the expected data for assertion. For each signup, it should have a total of
        // 2 statuses, because there are 2 session dates within an event here. And the status is
        // not_set by default, and only one signup has set one attendance status of session.
        $data = [];
        $gen = $this->getDataGenerator();

        foreach ($users as $user) {
            $gen->enrol_user($user->id, $event->get_seminar()->get_course());

            $signup = signup::create($user->id, $event);
            $signup->save();
            $signup->switch_state(booked::class);

            // Total of 2 sessions, and these are at not_set state, for each single signup.
            $data[$signup->get_userid()] = 2;

            $sessionstatus = session_status::from_signup($signup, $first->get_id());
            $sessionstatus->save();
        }

        // Edit the last user here to have the fully_attended attendance state. so that we can
        // start testing whether the calculation is correct or not.
        $lastuser = end($users);
        $sessionstatus = session_status::from_signup(
            signup::create($lastuser->id, $event),
            $first->get_id()
        );

        $sessionstatus->set_attendance_status(fully_attended::class);
        $sessionstatus->save();
        $data[$lastuser->id] = 1;


        $helper = new attendance_helper();
        $rs = $helper->get_calculated_session_attendance_status($event->get_id());

        foreach ($data as $userid => $total) {
            if (!isset($rs[$userid])) {
                $this->fail("No user id '{$userid}' was found in \$rs");
            }

            $stats = $rs[$userid];
            $this->assertEquals($total, $stats[not_set::get_code()]);
        }
    }

    /**
     * @return void
     */
    public function test_get_calculated_status_without_deleted_users(): void {
        global $PAGE;
        $PAGE->set_url('/');

        $this->setAdminUser();

        $e = $this->get_seminar_event();
        $times = [
            [
                'start' => time() - (3600 * 4),
                'finish' => time() - (3600 * 3)
            ],
            [
                'start' => time() - (3600 * 2),
                'finish' => time() - (3600 * 1)
            ]
        ];

        foreach ($times as $time) {
            $s = new seminar_session();
            $s->set_sessionid($e->get_id());
            $s->set_timestart($time['start']);
            $s->set_timefinish($time['finish']);
            $s->save();
        }

        $gen = $this->getDataGenerator();
        // A user to be deleted.
        $current = null;

        for ($i = 0; $i < 5; $i++) {
            $user = $gen->create_user();
            $gen->enrol_user($user->id, $e->get_seminar()->get_course());

            $signup = signup::create($user->id, $e);
            $signup->save();
            $signup->switch_state(booked::class);

            if (null == $current) {
                $current = $user;
            }

            $sessionstatus = session_status::from_signup($signup, $e->get_sessions()->get_first()->get_id());
            $sessionstatus->set_attendance_status(fully_attended::class);
            $sessionstatus->save();
        }

        $cm = get_coursemodule_from_instance('facetoface', $e->get_facetoface(), $e->get_seminar()->get_course());
        $context = context_module::instance($cm->id);
        $PAGE->set_context($context);

        $trainer = $gen->create_user();
        $gen->enrol_user($trainer->id, $e->get_seminar()->get_course(), 'editingteacher');
        $this->setUser($trainer);

        delete_user($current);
        $helper = new attendance_helper();
        $records = $helper->get_calculated_session_attendance_status($e->get_id());

        // We had 5 users in signup and 5 session statuses had been set for all of 5 users, however, one of the users had been
        // deleted before this retrieving process. Therefore, we should only expecting 4 records here, because the current actor
        // does not have the ability to view the deleted records.
        $this->assertCount(4, $records);
    }

    /**
     * @return array of [ sessionattendance, [ past, present, near future, far future ] ]
     */
    public function data_provider_process_session_attendance(): array {
        return [
            [ seminar::SESSION_ATTENDANCE_DISABLED, [ false, false, false, false ] ],
            [ seminar::SESSION_ATTENDANCE_END, [ true, false, false, false ] ],
            [ seminar::SESSION_ATTENDANCE_START, [ true, true, true, false ] ],
            [ seminar::SESSION_ATTENDANCE_UNRESTRICTED, [ true, true, true, true ] ],
        ];
    }

    /**
     * Test attendance_helper::process_session_attendance() with seminar::sessionattendance combinations.
     * @dataProvider data_provider_process_session_attendance
     */
    public function test_process_session_attendance(int $sessionattendance, array $expects): void {
        $this->setAdminUser();
        $gen = $this->getDataGenerator();
        $event = $this->get_seminar_event();

        $time = time();

        $seminar = $event->get_seminar();
        $user = $gen->create_user();
        $gen->enrol_user($user->id, $seminar->get_course());

        $seminar->set_sessionattendance($sessionattendance)->save();

        $session = new seminar_session();
        $session->set_timestart($time - DAYSECS)
                ->set_timefinish($time - DAYSECS + HOURSECS)
                ->set_sessionid($event->get_id())
                ->save();

        $signup = signup::create($user->id, $event);
        $signup->save();
        $signup->switch_state(booked::class);
        $attendance = [ $signup->get_id() => fully_attended::get_code() ];

        $result = attendance_helper::process_session_attendance($attendance, $session->get_id());
        $this->assertSame($expects[0], $result, 'past');

        $session->set_timestart($time - HOURSECS)
                ->set_timefinish($time + HOURSECS)
                ->save();
        $result = attendance_helper::process_session_attendance($attendance, $session->get_id());
        $this->assertSame($expects[1], $result, 'present');

        $session->set_timestart($time + event_taking_attendance::UNLOCKED_SECS_PRIOR_TO_START / 2)
                ->set_timefinish($time + event_taking_attendance::UNLOCKED_SECS_PRIOR_TO_START + HOURSECS)
                ->save();
        $result = attendance_helper::process_session_attendance($attendance, $session->get_id());
        $this->assertSame($expects[2], $result, 'near future');

        $session->set_timestart($time + DAYSECS)
                ->set_timefinish($time + DAYSECS + HOURSECS)
                ->save();
        $result = attendance_helper::process_session_attendance($attendance, $session->get_id());
        $this->assertSame($expects[3], $result, 'far future');
    }

    /**
     * Test suite of checking whether the field `createdby` and `timecreated` are populated corectly for table
     * `facetoface_signups_dates_status`. And also it does check the functionality of processing attendance state should
     * skip those records that does not change at all, when saving to storage.
     * @return void
     */
    public function test_taking_session_attendance(): void {
        global $USER, $DB;

        $this->setAdminUser();

        $gen = $this->getDataGenerator();
        $event = $this->get_seminar_event();
        $this->create_sessions($event);

        $users = $this->create_users(5);
        $attendance = [];

        foreach ($users as $user) {
            $gen->enrol_user($user->id, $event->get_seminar()->get_course());
            $signup = signup::create($user->id, $event);
            $signup->save();
            $signup->switch_state(booked::class);

            $attendance[$signup->get_id()] = fully_attended::get_code();
        }

        $sessions = $event->get_sessions();
        foreach ($sessions as $session) {
            attendance_helper::process_session_attendance($attendance, $session->get_id());
        }

        // Test asserting the data are populated correctly here for field createdby and timecreated.
        $records = $DB->get_records('facetoface_signups_dates_status');
        $original = count($records);
        foreach ($records as $record) {
            $this->assertEquals($USER->id, $record->createdby);
            $this->assertNotEmpty($record->timecreated);
            $this->assertEquals(fully_attended::get_code(), $record->attendancecode);
        }

        // Now start updating a few attendance status here to see if the process_session_attendance does skip those
        // duplicated records here.
        $x = 0;
        foreach ($attendance as $submissionid => $code) {
            $attendance[$submissionid] = partially_attended::get_code();
            $x++;
            if ($x == 2) {
                break;
            }
        }

        attendance_helper::process_session_attendance($attendance, $sessions->get_first()->get_id());

        // As if the attendance state is the same for the signup (without any update), then it should not insert a new
        // row and supersede the old one. However, the different statuses will be able to allow the function to do so.
        // Therefore, we are expecting $x number of rows to be inserted into the database
        $records = $DB->get_records('facetoface_signups_dates_status');
        $this->assertCount(($original + $x), $records);

        // Expecting $x number of records that has been superseded.
        $records = $DB->get_records('facetoface_signups_dates_status', ['superceded' => 1]);
        $this->assertCount($x, $records);
    }

    /**
     * This is the test suite assure that we are retrieving the attendance record(s) of users, but not the deleted users.
     * @return void
     */
    public function test_retrieve_attendance_records_without_deleted_users(): void {
        global $PAGE;
        $PAGE->set_url('/');

        $this->setAdminUser();

        $e = $this->get_seminar_event();

        $s = new seminar_session();
        $s->set_sessionid($e->get_id());
        $s->set_timestart(time() - 7200);
        $s->set_timefinish(time() - 3600);
        $s->save();

        $users = $this->create_users();
        $gen = $this->getDataGenerator();

        foreach ($users as $user) {
            $gen->enrol_user($user->id, $e->get_seminar()->get_course());
            $signup = signup::create($user->id, $e);
            $signup->save();

            $signup->switch_state(booked::class);
        }

        delete_user($users[0]);

        $cm = get_coursemodule_from_instance('facetoface', $e->get_seminar()->get_id(), $e->get_seminar()->get_course());
        $ctxt = context_module::instance($cm->id);
        $PAGE->set_context($ctxt);

        $trainer = $gen->create_user();
        $this->setUser($trainer);

        $helper = new attendance_helper();
        $records = $helper->get_attendees($e->get_id());

        // There is 1 deleted user, therefore the attendance helper should not retrieve the deleted users, in its return records.
        // This is for the event level.
        $this->assertCount(4, $records);

        // This is for session level
        $records = $helper->get_attendees($e->get_id(), $s->get_id());
        $this->assertCount(4, $records);
    }

    /**
     * A test suite assures that we are retrieving the attendance record(s) of users, but the role user with the permission
     * should be able to see it.
     *
     * @return void
     */
    public function test_retrieve_attendance_records_with_deleted_users(): void {
        global $PAGE, $DB, $USER;
        $PAGE->set_url('/');

        $this->setAdminUser();

        $e = $this->get_seminar_event();
        $s = new seminar_session();
        $s->set_sessionid($e->get_id());
        $s->set_timestart(time() - 7200);
        $s->set_timefinish(time() - 3600);
        $s->save();

        $gen = $this->getDataGenerator();
        $current = null;

        for ($i = 0; $i < 5; $i++) {
            $user = $gen->create_user();
            $gen->enrol_user($user->id, $e->get_seminar()->get_course());

            $signup = signup::create($user->id, $e);
            $signup->save();
            $signup->switch_state(booked::class);

            if (null == $current) {
                // Just set which users we should deleted here
                $current = $user;
            }
        }

        $cm = get_coursemodule_from_instance('facetoface', $e->get_facetoface(), $e->get_seminar()->get_course());
        $role = $DB->get_record('role', ['shortname' => 'editingteacher']);

        $context = context_module::instance($cm->id);
        $PAGE->set_context($context);

        assign_capability('totara/core:seedeletedusers', CAP_ALLOW, $role->id, $context->id);

        $trainer = $gen->create_user();
        $gen->enrol_user($trainer->id, $e->get_seminar()->get_course(), 'editingteacher');
        $this->setUser($trainer);

        delete_user($current);
        $helper = new attendance_helper();

        // Because the current user is able to view the deleted user, therefore attendance_helper should be able to provide these
        // deleted user in the result set for this actor.
        $records = $helper->get_attendees($e->get_id());
        $this->assertCount(5, $records);
    }
}