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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_facetoface
 */

use mod_facetoface\seminar;
use mod_facetoface\seminar_event;
use mod_facetoface\seminar_session;
use mod_facetoface\dashboard\filters\advanced_filter;
use mod_facetoface\dashboard\filters\booking_filter;
use mod_facetoface\dashboard\filters\event_time_filter;
use mod_facetoface\dashboard\filters\room_filter;
use mod_facetoface\dashboard\filters\facilitator_filter;
use mod_facetoface\query\event\filter\booking_filter as query_booking_filter;
use mod_facetoface\query\event\filter\advanced_filter as query_advanced_filter;
use mod_facetoface\signup_helper;
use mod_facetoface\signup;
use mod_facetoface\signup\state\booked;
use mod_facetoface\seminar_event_helper;
use mod_facetoface\attendance_taking_status;
use mod_facetoface\attendance\attendance_helper;
use mod_facetoface\signup\state\fully_attended;
use mod_facetoface\signup\state\unable_to_attend;
use mod_facetoface\seminar_session_list;
use mod_facetoface\signup\condition\event_taking_attendance;

defined('MOODLE_INTERNAL') || die();

/**
 * mod_facetoface_dashboard_filter_testcase class
 */
class mod_facetoface_dashboard_filter_testcase extends advanced_testcase {

    /** @var \core\testing\generator */
    protected $generator;

    /** @var \mod_facetoface\testing\generator */
    protected $facetoface_generator;

    /** @var stdClass */
    protected $course;

    /** @var seminar */
    protected $seminar;

    /** @var context */
    protected $context;

    public function setUp(): void {
        parent::setUp();
        $this->generator = $this->getDataGenerator();
        $this->facetoface_generator = $this->generator->get_plugin_generator('mod_facetoface');
        $this->course = $this->generator->create_course();
        $f2f = $this->generator->create_module('facetoface', array('course' => $this->course->id));
        $this->seminar = new seminar($f2f->id);
        $this->context = context_module::instance($f2f->cmid);
    }

    protected function tearDown(): void {
        $this->context = null;
        $this->seminar = null;
        $this->course = null;
        $this->facetoface_generator = null;
        $this->generator = null;
        parent::tearDown();
    }

    /**
     * @see \mod_facetoface_lib_testcase::prepare_date
     *
     * @param int|string $timestart
     * @param int|string $timeend
     * @param int|string $roomid
     * @param int|string $facilitatorid
     * @return \stdClass
     */
    protected function prepare_date($timestart, $timeend, $roomid, $facilitatorid): \stdClass {
        $sessiondate = new stdClass();
        $sessiondate->timestart = (string)$timestart;
        $sessiondate->timefinish = (string)$timeend;
        $sessiondate->sessiontimezone = '99';
        if (!empty($roomid)) {
            $sessiondate->roomids[] = $roomid;
        }
        if (!empty($facilitatorid)) {
            $sessiondate->facilitatorids[] = $facilitatorid;
        }
        return $sessiondate;
    }

    /**
     * Create users and sign-ups.
     *
     * @param integer $numusers
     * @param seminar_event $seminarevent
     * @param string|null $stateclass
     * @return array [ user_id => \mod_facetoface\signup ]
     */
    private function create_users_signups(int $numusers, seminar_event $seminarevent, ?string $stateclass = null): array {
        $generator = $this->getDataGenerator();
        $users = [];
        for ($i = 0; $i < $numusers; $i++) {
            $user = $generator->create_user();
            $generator->enrol_user($user->id, $seminarevent->get_seminar()->get_course());

            $signup = signup::create($user->id, $seminarevent);

            signup_helper::signup($signup);

            if ($stateclass) {
                $state = new $stateclass($signup);
                $rc = new ReflectionClass($signup);
                $method = $rc->getMethod('update_status');
                $method->setAccessible(true);
                $method->invoke($signup, $state);
            }

            $users += [ $user->id => $signup ];
        }
        return $users;
    }

    public function test_advanced_filter() {
        global $DB;
        /** @var \moodle_database $DB */

        $filter = new advanced_filter();

        // get_options() always returns [ All, Open, Saved, Overbooked, Underbooked ]
        $this->assertCount(5, $filter->get_options($this->seminar));

        $teacher = $this->generator->create_user();
        $student = $this->generator->create_user();
        $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));
        role_assign($teacherrole->id, $teacher->id, context_system::instance());
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        role_assign($studentrole->id, $student->id, context_system::instance());

        // Some sanity checks
        $this->assertTrue(has_capability('mod/facetoface:takeattendance', $this->context, $teacher));
        $this->assertFalse(has_capability('mod/facetoface:takeattendance', $this->context, $student));

        $seminareventid = $this->facetoface_generator->add_session(['facetoface' => $this->seminar->get_id(), 'sessiondates' => []]);
        $seminarevent = new seminar_event($seminareventid);
        $this->create_users_signups(1, $seminarevent, booked::class);

        $now = time();
        seminar_event_helper::merge_sessions($seminarevent, [$this->prepare_date($now - DAYSECS * 2, $now - DAYSECS, 0, 0)]);

        // Always visible to trainers, always hide from students
        $filter->set_param_value(query_advanced_filter::ALL);
        $this->assertTrue($filter->is_visible($this->seminar, $this->context, $teacher->id));
        $this->assertFalse($filter->is_visible($this->seminar, $this->context, $student->id));
        $filter->set_param_value(query_advanced_filter::ATTENDANCE_OPEN);
        $this->assertTrue($filter->is_visible($this->seminar, $this->context, $teacher->id));
        $this->assertFalse($filter->is_visible($this->seminar, $this->context, $student->id));
        $filter->set_param_value(query_advanced_filter::ATTENDANCE_SAVED);
        $this->assertTrue($filter->is_visible($this->seminar, $this->context, $teacher->id));
        $this->assertFalse($filter->is_visible($this->seminar, $this->context, $student->id));
        $filter->set_param_value(query_advanced_filter::UNDERBOOKED);
        $this->assertTrue($filter->is_visible($this->seminar, $this->context, $teacher->id));
        $this->assertFalse($filter->is_visible($this->seminar, $this->context, $student->id));
        $filter->set_param_value(query_advanced_filter::OVERBOOKED);
        $this->assertTrue($filter->is_visible($this->seminar, $this->context, $teacher->id));
        $this->assertFalse($filter->is_visible($this->seminar, $this->context, $student->id));
    }

    public function test_booking_filter() {
        $filter = new booking_filter();

        // get_options() always returns [ All, Open, Booked, Waitlisted, Requested, Cancelled ]
        $this->assertCount(6, $filter->get_options($this->seminar));

        // So far, booking_filter::is_visible() just returns true
        $filter->set_param_value(query_booking_filter::ALL);
        $this->assertTrue($filter->is_visible($this->seminar, $this->context, null));
        $filter->set_param_value(query_booking_filter::BOOKED);
        $this->assertTrue($filter->is_visible($this->seminar, $this->context, null));
        $filter->set_param_value(query_booking_filter::OPEN);
        $this->assertTrue($filter->is_visible($this->seminar, $this->context, null));
        $filter->set_param_value(query_booking_filter::WAITLISTED);
        $this->assertTrue($filter->is_visible($this->seminar, $this->context, null));
        $filter->set_param_value(query_booking_filter::REQUESTED);
        $this->assertTrue($filter->is_visible($this->seminar, $this->context, null));
        $filter->set_param_value(query_booking_filter::CANCELLED);
        $this->assertTrue($filter->is_visible($this->seminar, $this->context, null));
    }

    /**
     * @return array
     */
    public function data_provider_for_event_time_filter(): array {
        return [
            [ [ [], [] ], false, false, 'Waitlisted events' ],
            [ [ [ -DAYSECS ], [ -DAYSECS * 2 ] ], false, false, 'Past events only' ],
            [ [ [ -HOURSECS ], [ -HOURSECS * 2 ] ], false, false, 'Ongoing events only' ],
            [ [ [ DAYSECS ], [ DAYSECS * 2 ] ], false, false, 'Future events only' ],
            [ [ [], [ DAYSECS ] ], false, true, 'Future event + waitlisted event' ],
            [ [ [ -DAYSECS, DAYSECS ], [ -YEARSECS, YEARSECS ]  ], false, false, 'Ongoing events with future and past sessions' ],
            [ [ [], [] ], true, true, 'Waitlisted event + cancelled waitlisted event' ],
            [ [ [ -DAYSECS ], [ -DAYSECS * 2 ] ], true, true, 'Past event + cancelled past event' ],
            [ [ [ -HOURSECS ], [ -HOURSECS * 2 ] ], true, true, 'Ongoing event + cancelled ongoing event' ],
            [ [ [ -DAYSECS, DAYSECS ], [ -YEARSECS, YEARSECS ]  ], true, true, 'Ongoing future and past sessions + cancelled ongoing event' ],
            [ [ [], [ DAYSECS ] ], true, true, 'Future event + cancelled waitlisted event' ],
        ];
    }

    /**
     * @param array $sessionstimes
     * @param boolean $cancelfirst
     * @param boolean $visibility
     * @param string $tag
     * @dataProvider data_provider_for_event_time_filter
     */
    public function test_event_time_filter(array $sessionstimes, bool $cancelfirst, bool $visibility, string $tag) {
        $filter = new event_time_filter();

        // get_options() always returns [ All, Future, InProgress, Past, Waitlisted, Cancelled ]
        $this->assertCount(6, $filter->get_options($this->seminar));

        // is_visible() returns false if no seminar events
        $this->assertFalse($filter->is_visible($this->seminar, $this->context, null));

        $seminareventids = [];
        foreach ($sessionstimes as $sessiontimes) {
            $dates = array_map(function ($time) {
                $now = time();
                return $this->prepare_date($now + $time, $now + $time + HOURSECS  * 6, 0, 0);
            }, $sessiontimes);
            $seminareventids[] = $this->facetoface_generator->add_session(['facetoface' => $this->seminar->get_id(), 'sessiondates' => $dates ]);
        }

        if ($cancelfirst) {
            // Poor man's cancellation: just flip the cancelledstatus field instead of calling seminar_event::cancel()
            // This is true enough for this test case because we do not have to deal with attendance/notification here!
            (new seminar_event($seminareventids[0]))->set_cancelledstatus(1)->save();
        }

        // get_options() always returns [ All, Upcoming, InProgress, Over ]
        $this->assertCount(6, $filter->get_options($this->seminar));

        $this->assertSame($visibility, $filter->is_visible($this->seminar, $this->context, null));
    }

    public function test_room_filter() {
        $filter = new room_filter();

        // is_visible() just returns true
        $this->assertTrue($filter->is_visible($this->seminar, $this->context, null));
        // No rooms, no seminar events
        $this->assertCount(0, $filter->get_options($this->seminar));

        $room1 = $this->facetoface_generator->add_site_wide_room([ 'name' => 'Room 1' ]);
        $room2 = $this->facetoface_generator->add_site_wide_room([ 'name' => 'Room 2' ]);
        $room3 = $this->facetoface_generator->add_site_wide_room([ 'name' => 'Room 3' ]);
        // Adding rooms does not make any difference
        $this->assertCount(0, $filter->get_options($this->seminar));

        $dates = [ $this->prepare_date(1000, 2000, $room1->id, 0) ];
        $this->facetoface_generator->add_session(['facetoface' => $this->seminar->get_id(), 'sessiondates' => $dates ]);
        // If only one room is used, then get_options() returns an empty array
        $this->assertCount(0, $filter->get_options($this->seminar));

        $dates = [ $this->prepare_date(3000, 4000, $room1->id, 0) ];
        $this->facetoface_generator->add_session(['facetoface' => $this->seminar->get_id(), 'sessiondates' => $dates ]);
        // Adding another seminar event with the same room does not make any difference
        $this->assertCount(0, $filter->get_options($this->seminar));

        $dates = [ $this->prepare_date(5000, 6000, $room2->id, 0) ];
        $this->facetoface_generator->add_session(['facetoface' => $this->seminar->get_id(), 'sessiondates' => $dates ]);
        // 2 rooms + All
        $this->assertCount(3, $filter->get_options($this->seminar));

        $dates = [ $this->prepare_date(7000, 8000, $room3->id, 0) ];
        $this->facetoface_generator->add_session(['facetoface' => $this->seminar->get_id(), 'sessiondates' => $dates ]);
        // 3 rooms + All
        $this->assertCount(4, $filter->get_options($this->seminar));
    }

    public function test_facilitator_filter() {
        $filter = new facilitator_filter();

        // is_visible() just returns true
        $this->assertTrue($filter->is_visible($this->seminar, $this->context, null));
        // No facilitators, no seminar events
        $this->assertCount(0, $filter->get_options($this->seminar));

        $fac1 = $this->facetoface_generator->add_site_wide_facilitator([ 'name' => 'Facilitator 1' ]);
        $fac2 = $this->facetoface_generator->add_site_wide_facilitator([ 'name' => 'Facilitator 2' ]);
        $fac3 = $this->facetoface_generator->add_site_wide_facilitator([ 'name' => 'Facilitator 3' ]);
        // Adding facilitators does not make any difference
        $this->assertCount(0, $filter->get_options($this->seminar));

        $dates = [ $this->prepare_date(1000, 2000, 0, $fac1->id) ];
        $this->facetoface_generator->add_session(['facetoface' => $this->seminar->get_id(), 'sessiondates' => $dates ]);
        // If only one facilitator is used, then get_options() returns an empty array
        $this->assertCount(0, $filter->get_options($this->seminar));

        $dates = [ $this->prepare_date(3000, 4000, 0, $fac1->id) ];
        $this->facetoface_generator->add_session(['facetoface' => $this->seminar->get_id(), 'sessiondates' => $dates ]);
        // Adding another seminar event with the same facilitator does not make any difference
        $this->assertCount(0, $filter->get_options($this->seminar));

        $dates = [ $this->prepare_date(5000, 6000, 0, $fac2->id) ];
        $this->facetoface_generator->add_session(['facetoface' => $this->seminar->get_id(), 'sessiondates' => $dates ]);
        // 2 facilitators + All
        $this->assertCount(3, $filter->get_options($this->seminar));

        $dates = [ $this->prepare_date(7000, 8000, 0, $fac3->id) ];
        $this->facetoface_generator->add_session(['facetoface' => $this->seminar->get_id(), 'sessiondates' => $dates ]);
        // 3 facilitators + All
        $this->assertCount(4, $filter->get_options($this->seminar));
    }
}
