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

use mod_facetoface\{seminar_event, signup, seminar_session, signup_list, signup_helper};
use totara_job\job_assignment;
use mod_facetoface\signup\state\booked;

class mod_facetoface_retrieve_managers_testcase extends advanced_testcase {
    /**
     * @return seminar_event
     */
    private function create_seminar_event(): seminar_event {
        $gen = $this->getDataGenerator();
        $course = $gen->create_course([], ['createsections' => 1]);

        /** @var \mod_facetoface\testing\generator $f2fgen */
        $f2fgen = $gen->get_plugin_generator('mod_facetoface');
        $f2f = $f2fgen->create_instance(['course' => $course->id]);

        $seminarevent = new seminar_event();
        $seminarevent->set_facetoface($f2f->id);
        $seminarevent->save();

        $s = new seminar_session();
        $s->set_timestart(time() + 3600);
        $s->set_timefinish(time() + (3600 * 2));
        $s->set_sessionid($seminarevent->get_id());
        $s->save();

        return $seminarevent;
    }

    /**
     * @return void
     */
    public function test_retrieving_managers_from_a_signupmanagerid(): void {
        $this->setAdminUser();

        $seminarevent = $this->create_seminar_event();

        $gen = $this->getDataGenerator();
        $manager = $gen->create_user();
        $jaman = job_assignment::create_default($manager->id);

        set_config('facetoface_managerselect', 1);

        for ($i = 0; $i < 2; $i++) {
            $user = $gen->create_user();
            job_assignment::create_default($user->id, ['managerjaid' => $jaman->id]);

            $gen->enrol_user($user->id, $seminarevent->get_seminar()->get_course());
            $signup = signup::create($user->id, $seminarevent);
            $signup->set_managerid($jaman->userid);
            $signup->save();

            $signup->switch_state(booked::class);
        }


        $signups = signup_list::from_conditions(['sessionid' => $seminarevent->get_id()]);
        $this->assertNotEmpty($signups);

        /** @var signup $signup */
        foreach ($signups as $signup) {
            $managers = signup_helper::find_managers_from_signup($signup);
            $this->assertNotEmpty($managers);

            // There is only mangers in this case.
            $this->assertEquals($jaman->userid, $managers[0]->id);
        }
    }

    /**
     * Test suite of retrieving managers of signup base on the jobassignment.
     *
     * @return void
     */
    public function test_retrieving_managers_from_jobassignment(): void {
        $this->setAdminUser();

        $seminarevent = $this->create_seminar_event();
        $gen = $this->getDataGenerator();

        $manager = $gen->create_user();
        $jaman = job_assignment::create_default($manager->id);

        set_config('facetoface_selectjobassignmentonsignupglobal', 1);

        for ($i = 0; $i < 3; $i++) {
            $user = $gen->create_user();
            $ja = job_assignment::create_default($user->id, ['managerjaid' => $jaman->id]);

            $gen->enrol_user($user->id, $seminarevent->get_seminar()->get_course());
            $signup = signup::create($user->id, $seminarevent);
            $signup->set_jobassignmentid($ja->id);

            $signup->save();
            $signup->switch_state(booked::class);
        }

        $signups = signup_list::from_conditions(['sessionid' => $seminarevent->get_id()]);
        $this->assertNotEmpty($signups);

        /** @var signup $signup */
        foreach ($signups as $signup) {
            $managers = signup_helper::find_managers_from_signup($signup);

            // Only one manager here for signup, base on the jobassignment id for this test suite.
            $this->assertCount(1, $managers);
            $this->assertEquals($manager->id, $managers[0]->id);
        }
    }
}