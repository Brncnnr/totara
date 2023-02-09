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
 * @package mod_facetface
 */

defined('MOODLE_INTERNAL') || die();

use mod_facetoface\{seminar_event, seminar, seminar_session, signup};
use mod_facetoface\signup\state\{event_cancelled, booked, fully_attended};

class mod_facetoface_user_switch_to_event_cancelled_testcase extends advanced_testcase {
    /**
     * Creating seminar_event with the seminar settings as below:
     * + sessionattendance => 1
     * + attendancetime => ANY
     *
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

        $event = new seminar_event();
        $event->set_facetoface($s->get_id());
        $event->save();

        return $event;
    }

    /**
     * Creating sessions for the seminar event.
     * @param seminar_event $event
     * @return void
     */
    private function create_sessions(seminar_event $event): void {
        $time = time();
        $times = [
            [
                'start' => $time + 3600,
                'finish' => $time + (3600 * 2),
            ],
            [
                'start' => $time + (3600 * 4),
                'finish' => $time + (3600 * 6)
            ]
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
     * Test suite of switching those users that already had attendance state into event_cancelled
     * state, as this is for scenario where admin/editor cancel an event upcoming, but seminar
     * setting allow taking attendance at any time.
     *
     * @return void
     */
    public function test_switch_state(): void {

        $e = $this->create_seminar_event();
        $this->create_sessions($e);

        $gen = $this->getDataGenerator();
        $signups = [];

        for ($i = 0; $i < 5; $i ++) {
            $user = $gen->create_user();
            $gen->enrol_user($user->id, $e->get_seminar()->get_course());

            $signup = new signup();
            $signup->set_userid($user->id);
            $signup->set_sessionid($e->get_id());
            $signup->save();

            // Switch to booked state here, then switch it to fully attended
            $signup->switch_state(booked::class);
            $signup->switch_state(fully_attended::class);

            $signups[] = $signup->get_id();
        }

        $e->cancel();

        foreach ($signups as $signupid) {
            $signup = new signup($signupid);
            $state = $signup->get_state();

            $this->assertInstanceOf(event_cancelled::class, $state);
        }
    }
}