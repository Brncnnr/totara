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

use mod_facetoface\{seminar_event, seminar_session, attendees_helper, signup};
use mod_facetoface\signup\state\{
    attendance_state,
    booked,
    fully_attended
};

class mod_facetoface_count_attendees_testcase extends advanced_testcase {
    /**
     * @return seminar_event
     */
    private function create_seminar_event(): seminar_event {
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();

        $f2fgen = $gen->get_plugin_generator('mod_facetoface');
        $f2f = $f2fgen->create_instance(['course' => $course->id]);

        $e = new seminar_event();
        $e->set_facetoface($f2f->id);
        $e->save();
        return $e;
    }

    /**
     * Test counting attendees record in seminar event
     * @return void
     */
    public function test_count_attendees(): void {
        $this->setAdminUser();

        $e = $this->create_seminar_event();
        $s = new seminar_session();
        $s->set_timestart(time() + 3600);
        $s->set_timefinish(time() + 7200);
        $s->set_sessionid($e->get_id());
        $s->save();

        $gen = $this->getDataGenerator();
        $signups = [];

        for ($i = 0; $i < 3; $i++) {
            $user = $gen->create_user();
            $gen->enrol_user($user->id, $e->get_seminar()->get_course());

            $signup = signup::create($user->id, $e);
            $signup->save();

            $signup->switch_state(booked::class);
            $signups[] = $signup;
        }

        $helper = new attendees_helper($e);
        $this->assertEquals(3, $helper->count_attendees_with_codes([booked::get_code()]));

        // Moving the user's state to attendance state.
        $s->set_timestart(time() - 7200);
        $s->set_timefinish(time() - 3600);
        $s->save();

        /** @var signup $signup */
        foreach ($signups as $signup) {
            $signup->get_seminar_event()->clear_sessions();
            $signup->switch_state(fully_attended::class);
        }

        $this->assertEquals(0, $helper->count_attendees_with_codes([booked::get_code()]));
        $this->assertEquals(3, $helper->count_attendees_with_codes(attendance_state::get_all_attendance_code()));
    }
}