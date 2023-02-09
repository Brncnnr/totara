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

use mod_facetoface\{seminar_event, room, seminar_session, signup, room_helper, signup_status};
use mod_facetoface\signup\state\{booked, fully_attended};

class mod_facetoface_cancelled_event_testcase extends advanced_testcase {
    /**
     * This is a test suite of sending an email out of a cancelled event, to check whether the custom room's detail is still
     * included in the email or not.
     *
     * @return void
     */
    public function test_sending_cancellation_with_room_info(): void {
        global $DB;

        $this->setAdminUser();

        $gen = $this->getDataGenerator();
        $course = $gen->create_course([]);

        /** @var \mod_facetoface\testing\generator $f2fgen */
        $f2fgen = $gen->get_plugin_generator('mod_facetoface');
        $f2f = $f2fgen->create_instance(['course' => $course->id]);

        $room = room::create_custom_room();
        $room->set_name('This is custom room');
        $room->set_capacity(2);
        $room->save();

        $e = new seminar_event();
        $e->set_facetoface($f2f->id);
        $e->set_capacity($room->get_capacity());
        $e->save();

        $ss = new seminar_session();
        $ss->set_timestart(time() + 3600);
        $ss->set_timefinish(time() + 7200);
        $ss->set_sessionid($e->get_id());
        $ss->save();
        room_helper::sync($ss->get_id(), [$room->get_id()]);

        // Adding signup here, so that we do have users to receive the email with the room information in it.
        for ($i = 0; $i < 2; $i ++) {
            $user = $gen->create_user();
            $gen->enrol_user($user->id, $course->id);

            $signup = signup::create($user->id, $e);
            $signup->save();

            $signup->switch_state(booked::class);
        }

        $sink = $this->redirectMessages();

        // Execute those booking confirmation to the users first, so that we can start cancelling the event, and perform the
        // assertion way easier.
        $this->executeAdhocTasks();
        $sink->clear();

        // Cancelling event here.
        $e->cancel();
        $this->executeAdhocTasks();
        $messages = $sink->get_messages();

        // Start assertion to locate that whether the room name is included in the cancellation message.
        foreach ($messages as $msg) {
            $this->assertStringContainsString('This is custom room', $msg->fullmessage);
        }
    }

    /**
     * Create a user, a course, a seminar, a future seminar event, sign up a user and set the event past.
     * @return array of [courseID, seminarID, userID, signup]
     */
    private function make_signup(): array {
        // Just boring boilerplate code as usual.
        $gen = $this->getDataGenerator();
        /** @var \mod_facetoface\testing\generator $f2fgen */
        $f2fgen = $gen->get_plugin_generator('mod_facetoface');
        $user = $gen->create_user();
        $course = $gen->create_course();
        $seminarevent = $f2fgen->create_session_for_course($course);
        $signup = signup::create($user->id, $seminarevent)->save();
        signup_status::create($signup, new booked($signup))->save();
        $this->assertInstanceOf(booked::class, $signup->get_state());
        return [$course->id, $seminarevent->get_facetoface(), $user->id, $signup];
    }

    /**
     * @return array of [ offsetToTimeStart, isCancelled, eventGrade ]
     */
    public function data_cancelling_event_updates_grade(): array {
        return [
            [ -YEARSECS, false, 77. ], // past
            [ -HOURSECS, false, 77. ], // present
            [ WEEKSECS, true, null ], // future
        ];
    }

    /**
     * Cancel a seminar event in various time period and see the event grade updated or not.
     * @dataProvider data_cancelling_event_updates_grade
     */
    public function test_cancelling_event_updates_grade(int $timediff, bool $cancelled, ?float $newgrade) {
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

        // Back to the future, or the past.
        $session->set_timestart(time() + $timediff)->set_timefinish(time() + $timediff + DAYSECS)->save();

        // Cancelling a past seminar event is not possible.
        $this->assertSame($cancelled, $seminarevent->cancel());
        $grade_grades = grade_get_grades($courseid, 'mod', 'facetoface', $f2fid, [$userid]);
        $this->assertSame($newgrade, grade_floatval($grade_grades->items[0]->grades[$userid]->grade));
    }
}