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

use mod_facetoface\{seminar_event, seminar, seminar_session, signup, session_status};
use mod_facetoface\signup\state\{fully_attended, booked};
use mod_facetoface\attendance\attendance_helper;

class mod_facetoface_retrieve_attendance_testcase extends advanced_testcase {
    /**
     * Create seminar_event with seminar setting as below:
     * + attendancetime => END
     * + sessionattendance => END
     * @return seminar_event
     */
    private function create_seminar_event(): seminar_event {
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();

        /** @var \mod_facetoface\testing\generator $f2fgen */
        $f2fgen = $gen->get_plugin_generator('mod_facetoface');
        $f2f = $f2fgen->create_instance(['course' => $course->id]);

        $s = new seminar($f2f->id);
        $s->set_attendancetime(seminar::EVENT_ATTENDANCE_LAST_SESSION_END);
        $s->set_sessionattendance(seminar::SESSION_ATTENDANCE_END);
        $s->save();

        $e = new seminar_event();
        $e->set_facetoface($s->get_id());
        $e->save();

        return $e;
    }

    /**
     * Test suite is about checking whether the attendee has been added into the list of attendees
     * per session or not. As the table {facetoface_signups_dates_status} is not populated when
     * any new users has been added into the list of attendees of an event. However, the
     * attendance_helper has the ability to include these newly added users into the list of
     * session attendance, therefore, this test suite purpose is to make sure that is happening
     * correctly.
     *
     * @throws \Exception
     * @return void
     */
    public function test_get_attendees(): void {
        $this->setAdminUser();

        $event = $this->create_seminar_event();
        $time = time();
        $times = [
            ['start' => $time + 3600, 'finish' => $time + (3600 * 2)],
            ['start' => $time + (3600 * 3), 'finish' => $time + (3600 * 4)]
        ];

        foreach ($times as $t) {
            $ss = new seminar_session();
            $ss->set_timestart($t['start']);
            $ss->set_timefinish($t['finish']);
            $ss->set_sessionid($event->get_id());
            $ss->save();
        }

        $gen = $this->getDataGenerator();
        $signups = [];
        for ($i = 0; $i < 5; $i++) {
            $user = $gen->create_user();
            $gen->enrol_user($user->id, $event->get_seminar()->get_course());
            $signup = signup::create($user->id, $event);
            $signup->save();
            $signup->switch_state(booked::class);

            $signups[] = $signup;
        }

        $helper = new attendance_helper();
        $sessions = $event->get_sessions();

        foreach ($sessions as $session) {
            $attendees = $helper->get_attendees($event->get_id(), $session->get_id());

            // 5 attendees has been added into the seminar event.
            $this->assertCount(5, $attendees);

            // Now we start tracking the attendance status of these signup, if they didnt have any.
            foreach ($signups as $signup) {
                $sessionstatus = session_status::from_signup($signup, $session->get_id());
                $sessionstatus->set_attendance_status(fully_attended::class);
                $sessionstatus->save();
            }
        }

        // Add 2 more attendees to the event, and we check whether these two users has been included
        // inside the record retrieved by attendance_helper or not.
        $newlycreated = [];
        for ($i = 0; $i < 2; $i++) {
            $user = $gen->create_user();
            $gen->enrol_user($user->id, $event->get_seminar()->get_course());
            $signup = signup::create($user->id, $event);
            $signup->save();
            $signup->switch_state(booked::class);

            $newlycreated[] = $user->id;
        }

        foreach ($sessions as $session) {
            $attendees = $helper->get_attendees($event->get_id(), $session->get_id());
            $this->assertCount(7, $attendees);

            foreach ($newlycreated as $userid) {
                $this->assertArrayHasKey($userid, $attendees);
            }
        }
    }
}
