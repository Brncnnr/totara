<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once("{$CFG->dirroot}/mod/facetoface/lib.php");

use mod_facetoface\seminar_event;
use mod_facetoface\signup;
use mod_facetoface\signup_helper;
use mod_facetoface\signup\state\{booked, waitlisted};

/**
 * Class mod_facetoface_waitlist_event_testcase
 */
class mod_facetoface_waitlist_event_testcase extends advanced_testcase {

    /**
     * @return stdClass
     */
    private function create_facetoface(): stdClass {
        $generator = $this->getDataGenerator();

        /** @var \mod_facetoface\testing\generator $f2fgenerator */
        $f2fgenerator = $generator->get_plugin_generator("mod_facetoface");

        $course = $generator->create_course(null, ['createsections' => true]);
        $parameters = ['course' => $course->id];
        $f2f = $f2fgenerator->create_instance((object) $parameters);
        return $f2f;
    }

    /**
     * @param int $numberofusers    How many users to be created
     * @param stdClass $course Course to enrol users to
     * @return stdClass[]
     */
    private function create_users(int $numberofusers, stdClass $course): array {
        $generator = $this->getDataGenerator();
        $users = array();

        for ($i = 0; $i < $numberofusers; $i++) {
            $user = $generator->create_user();
            $generator->enrol_user($user->id, $course->id);
            $users[] = $user;
        }

        return $users;
    }

    /**
     * Properly test the waitlist everyone setting.
     *
     * @return void
     */
    public function test_waitlist_everyone(): void {
        global $DB, $USER;

        $f2f = $this->create_facetoface();
        $course = $DB->get_record("course", ['id' => $f2f->course]);
        $users = $this->create_users(4, $course);

        /** @var \mod_facetoface\testing\generator $f2fgenerator */
        $f2fgenerator = $this->getDataGenerator()->get_plugin_generator("mod_facetoface");
        $sessiondate = new stdClass();
        $sessiondate->timestart = time() + (DAYSECS * 1);
        $sessiondate->timefinish = $sessiondate->timestart + (DAYSECS * 1);
        $sessiondate->sessiontimezone = 'Pacific/Auckland';
        $sessionid = $f2fgenerator->add_session((object)[
            'facetoface' => $f2f->id,
            'capacity' => 2,
            'timecreated' => time(),
            'timemodified' => time(),
            'usermodified' => $USER->id,
            'sessiondates' => array($sessiondate),
        ]);

        // Waitlist everyone needs to be set globally for seminar_event::set_waitlist(1) to have any effect.
        set_config('facetoface_allowwaitlisteveryone', 1);

        $seminar_event = new seminar_event($sessionid);
        $seminar_event->set_waitlisteveryone(1);

        // Create 4 signups.
        $signups = [];
        foreach ($users as $index => $user) {
            $signup = signup::create($user->id, $seminar_event);
            signup_helper::signup($signup);
            $signups[] = $signup;
        }

        // All signups should be waitlisted.
        $records = $DB->get_records('facetoface_signups_status', ['statuscode' => waitlisted::get_code(), 'superceded' => 0]);
        $this->assertEquals('4', count($records));

        // Do a user cancellation.
        signup_helper::user_cancel($signups[0]);

        // All remaining signups should still be waitlisted
        $records = $DB->get_records('facetoface_signups_status', ['statuscode' => waitlisted::get_code(), 'superceded' => 0]);
        $this->assertEquals('3', count($records));
    }
}
