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

use mod_facetoface\{seminar_event, seminar_session, seminar, signup, session_status, signup_helper};
use mod_facetoface\signup\state\{booked, fully_attended};
use mod_facetoface\attendance\attendance_helper;

class mod_facetoface_user_cancel_signup_testcase extends advanced_testcase {
    /**
     * Creating a seminar event the seminar settings as follow:
     * + attendancetime => ANY
     * + sessionattendance => 1
     * @return seminar_event
     */
    private function create_seminar_event(): seminar_event {
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();

        $f2fgen = $gen->get_plugin_generator('mod_facetoface');
        $f2f = $f2fgen->create_instance(['course' => $course->id]);

        $s = new seminar($f2f->id);
        $s->set_attendancetime(seminar::EVENT_ATTENDANCE_UNRESTRICTED);
        $s->save();

        $e = new seminar_event();
        $e->set_facetoface($s->get_id());
        $e->save();

        return $e;
    }

    /**
     * Creating sessions for the event, most are the future sessions
     * @param seminar_event $event
     * @return void
     */
    private function create_sessions(seminar_event $event): void {
        $time = time();
        $times = [
            ['start' => $time + 3600, 'finish' => $time + (3600 * 2)],
            ['start' => $time + (3600 * 4), 'finish' => $time + (3600 * 5)]
        ];

        foreach ($times as $t) {
            $ss = new seminar_session();
            $ss->set_sessionid($event->get_id());
            $ss->set_timestart($t['start']);
            $ss->set_timefinish($t['finish']);
            $ss->save();
        }
    }

    /**
     * Markt he attendance state of the attendee, within the session level, then cancel one of the user out and expecting
     * the number of attendance statuses to be reduced, because user cancelled should supersede the attendance status.
     * @return void
     */
    public function test_user_cancel_should_supersede_the_attendance_status(): void {
        $this->setAdminUser();

        $gen = $this->getDataGenerator();

        $event = $this->create_seminar_event();
        $this->create_sessions($event);

        $sessions = $event->get_sessions();
        $users = [];

        // Create users/signups/session-statuses here
        for ($i = 0; $i < 5; $i++) {
            $user = $gen->create_user();
            $users[] = $user;
            $gen->enrol_user($user->id, $event->get_seminar()->get_course());

            $signup = signup::create($user->id, $event);
            $signup->save();
            $signup->switch_state(booked::class);

            $sessionstatus = session_status::from_signup($signup, $sessions->get_first()->get_id());
            $sessionstatus->set_attendance_status(fully_attended::class);
            $sessionstatus->save();
        }

        // Now start cancelling a few users here for the event. After cancelling one user, there should be 4 users
        // left within the session attendance.
        $user1 = current($users);
        $signup = signup::create($user1->id, $event);
        signup_helper::user_cancel($signup);

        $helper = new attendance_helper();
        foreach ($sessions as $session) {
            $attendees = $helper->get_attendees($event->get_id(), $session->get_id());
            $this->assertCount(4, $attendees);
        }
    }

    /**
     * Test suite where a cancel signup without any attendance status record, should not create a new superceded
     * record here.
     *
     * @return void
     */
    public function test_user_cancel_should_not_created_new_record(): void {
        global $DB;

        $this->setAdminUser();

        $gen = $this->getDataGenerator();

        $event = $this->create_seminar_event();
        $this->create_sessions($event);

        $signups = [];
        for ($i = 0; $i < 5; $i++) {
            $user = $gen->create_user();

            $gen->enrol_user($user->id, $event->get_seminar()->get_course());
            $signup = signup::create($user->id, $event);
            $signup->save();

            $signup->switch_state(booked::class);
            $signups[] = $signup;
        }

        foreach ($signups as $signup) {
            signup_helper::user_cancel($signup);
        }

        $records = $DB->get_records('facetoface_signups_dates_status');
        $this->assertCount(0, $records);
    }
}