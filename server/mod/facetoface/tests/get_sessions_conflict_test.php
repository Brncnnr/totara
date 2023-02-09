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

use mod_facetoface\{seminar_session, seminar_event, signup, seminar_session_list};
use mod_facetoface\signup\state\booked;

class mod_facetoface_get_sessions_conflict_testcase extends advanced_testcase {
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
     * Given two seminar events with two same session dates for both. Signup is only able to signup on one event only, if the user
     * had signuped to the first event, then clearly that user is not able to signup for the second event, and there should be a
     * conflicting in sessions that user is about to signup.
     *
     * @return void
     */
    public function test_conflicting_sessions_for_user(): void {
        $this->setAdminUser();

        $event1 = $this->create_seminar_event();
        $event2 = $this->create_seminar_event();

        // Start creating two new sessions for an event here.
        foreach ([$event1, $event2] as $event) {
            $time = time();

            for ($i = 0; $i < 2; $i++) {
                $s = new seminar_session();
                $s->set_sessionid($event->get_id());
                $s->set_timestart($time + 3600);
                $s->set_timefinish($time + 7200);
                $s->save();

                $time += 7200;
            }
        }

        $gen = $this->getDataGenerator();
        $users = [];
        $resultdata = [];

        for ($i = 0; $i < 3; $i++) {
            $user = $gen->create_user();
            $gen->enrol_user($user->id, $event1->get_seminar()->get_course());
            $gen->enrol_user($user->id, $event2->get_seminar()->get_course());

            $users[] = $user;

            // Start signup up users for one of the event
            $signup = signup::create($user->id, $event1);
            $signup->save();
            $signup->switch_state(booked::class);
            $resultdata[$user->id] = true;
        }

        // Adding one of the users without signup to any of the event here.
        $user = $gen->create_user();
        $users[] = $user;
        $resultdata[$user->id] = false;

        foreach ($users as $user) {
            $conflictsessions = seminar_session_list::from_user_conflicts_with_sessions($user->id, $event2->get_sessions());
            $this->assertEquals($resultdata[$user->id], !$conflictsessions->is_empty());
        }
    }
}