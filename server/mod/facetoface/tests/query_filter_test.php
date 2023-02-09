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

use core\orm\query\builder;
use core\orm\query\sql\query;
use mod_facetoface\attendance_taking_status;
use mod_facetoface\dashboard\filter_list;
use mod_facetoface\dashboard\filters\{advanced_filter, booking_filter, event_time_filter, facilitator_filter, room_filter};
use mod_facetoface\event_time;
use mod_facetoface\query\event\filter\advanced_filter as event_advanced_filter;
use mod_facetoface\query\event\filter\booking_filter as event_booking_filter;
use mod_facetoface\query\event\sortorder\past_sortorder;
use mod_facetoface\seminar;
use mod_facetoface\seminar_event;
use mod_facetoface\seminar_event_helper;
use mod_facetoface\seminar_event_list;
use mod_facetoface\seminar_session;
use mod_facetoface\session_status;
use mod_facetoface\signup;
use mod_facetoface\signup\state\{attendance_state, booked, declined, event_cancelled, fully_attended, no_show, not_set, partially_attended, requested, requestedadmin, requestedrole, unable_to_attend, user_cancelled, waitlisted};
use mod_facetoface\signup_status;

defined('MOODLE_INTERNAL') || die();

class mod_facetoface_query_filter_testcase extends advanced_testcase {

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
     * Create a session date object that can be passed to seminar_event_helper::merge_sessions.
     * @param int $timestart
     * @param int $timeend
     * @param array $roomids
     * @param array $facilitatorids
     * @return stdClass
     */
    private function prepare_date(int $timestart, int $timeend, array $roomids = [], array $facilitatorids = []): stdClass {
        $sessiondate = new stdClass();
        $sessiondate->timestart = (string)$timestart;
        $sessiondate->timefinish = (string)$timeend;
        $sessiondate->sessiontimezone = '99';
        $sessiondate->roomids = $roomids;
        $sessiondate->facilitatorids = $facilitatorids;
        return $sessiondate;
    }

    /**
     * Sign up and directly switch sign-up status.
     *
     * @param seminar_event $seminarevent
     * @param integer $userid
     * @param string $stateclass
     * @return signup
     */
    private function signup_state(seminar_event $seminarevent, int $userid, string $stateclass): signup {
        $signup = signup::create($userid, $seminarevent);
        $signup->save();

        $state = new $stateclass($signup);
        if (!($state instanceof not_set)) {
            signup_status::create($signup, $state)->save();
            $this->assertEquals($stateclass, signup_status::find_current($signup)->get_state_class());
        }
        return $signup;
    }

    /**
     * Get the visibility of filtered seminar events as string.
     * @param string $filterclass
     * @param mixed $filtervalue
     * @param array $seminareventids array of seminar event ids
     * @return string the list of 'x' or '.' comprising the visibility or invisibility of the seminar events.
     */
    private function get_filtered_event_list(string $filterclass, $filtervalue, array $seminareventids): string {
        $this->assertSame(0, strpos($filterclass, 'mod_facetoface\\dashboard\\filters\\'));
        $filterlist = (new filter_list())->add_filter(new $filterclass())->set_filter_value($filterclass, $filtervalue);
        $query = $filterlist->to_query($this->seminar, $this->context, null)->with_sortorder(new past_sortorder());
        $events = iterator_to_array(seminar_event_list::from_query($query), true);
        return array_reduce($seminareventids, function ($accum, $eventid) use (&$events) {
            return $accum . (isset($events[$eventid]) ? 'x' : '.');
        }, '');
    }

    /**
     * Helper function to count the number of saved attendees.
     *
     * @return integer
     */
    private function count_saved_attendees(int $seminareventid): int {
        return builder::table('facetoface_signups', 'su')
            ->join(['facetoface_signups_status', 'sus'], 'id', 'signupid')
            ->where('sus.superceded', 0)
            ->where('su.sessionid', $seminareventid)
            ->where_in('sus.statuscode', attendance_state::get_all_attendance_code())
            ->count();
    }

    /**
     * @return array of [ filterID, expected ]
     */
    public function data_advanced_filter_basic(): array {
        return [
            [event_advanced_filter::ALL,                'xxxxxxxxxxxx'],
            [event_advanced_filter::ATTENDANCE_OPEN,    '.x.x.xx.xxx.'],
            [event_advanced_filter::ATTENDANCE_SAVED,   '..x.x..x...x'],
            [event_advanced_filter::OVERBOOKED,         '........xxxx'],
            [event_advanced_filter::UNDERBOOKED,        'xxx.........'],
        ];
    }

    /**
     * Test advanced_filter.
     * @dataProvider data_advanced_filter_basic
     */
    public function test_advanced_filter_basic(int $value, string $expected) {
        $eventsdata = [
            [0, 0, '0 booked, 0 attended'],
            [1, 0, '1 booked, 0 attended'],
            [1, 1, '1 booked, 1 attended'],
            [2, 1, '2 booked, 1 attended'],
            [2, 2, '2 booked, 2 attended'],
            [3, 0, '3 booked, 0 attended'],
            [3, 2, '3 booked, 2 attended'],
            [3, 3, '3 booked, 3 attended'],
            [4, 0, '4 booked, 0 attended'],
            [4, 1, '4 booked, 1 attended'],
            [4, 3, '4 booked, 3 attended'],
            [4, 4, '4 booked, 4 attended'],
        ];

        $this->seminar->set_attendancetime(seminar::EVENT_ATTENDANCE_UNRESTRICTED)->save();

        $seminareventids = [];
        $time = time();
        foreach ($eventsdata as $data) {
            $users = [];
            for ($i = 0; $i < 4; $i++) {
                $userid = $this->generator->create_user()->id;
                $this->generator->enrol_user($userid, $this->course->id);
                $users[] = $userid;
            }
            $seminarevent = (new seminar_event())->set_facetoface($this->seminar->get_id())
                ->set_allowoverbook(0)->set_mincapacity(2)->set_capacity(3);
            $seminarevent->save();
            $time += HOURSECS * 2;
            seminar_event_helper::merge_sessions($seminarevent, [$this->prepare_date($time, $time + HOURSECS)]);
            /** @var signup[] $signups */
            $signups = [];
            for ($i = 0; $i < $data[0]; $i++) {
                $signup = $this->signup_state($seminarevent, $users[$i], booked::class);
                $signups[] = $signup;
            }
            for ($i = 0; $i < $data[1]; $i++) {
                $signups[$i]->switch_state(fully_attended::class);
            }
            $seminareventids[] = $seminarevent->get_id();
        }

        $events = $this->get_filtered_event_list(advanced_filter::class, $value, $seminareventids);
        $this->assertEquals($expected, $events);
    }

    /**
     * @return array of [ attendancetime, expected ]
     */
    public function data_advanced_filter_attendance_event_session(): array {
        return [
            [event_advanced_filter::ATTENDANCE_OPEN,    'xxxxxxxxxxxxx.'],
            [event_advanced_filter::ATTENDANCE_SAVED,   '..x.x.xx..x.xx'],
        ];
    }

    /**
     * Test advanced_filter related to attendance.
     * @dataProvider data_advanced_filter_attendance_event_session
     */
    public function test_advanced_filter_attendance_event_session(int $value, string $expected) {
        $eventsdata = [
            [0, [0, 0, 0], '0 event taken, 0/0/0 session taken'],
            [0, [0, 0, 1], '0 event taken, 0/0/1 session taken'],
            [0, [0, 0, 2], '0 event taken, 0/0/2 session taken'],
            [0, [0, 1, 1], '0 event taken, 0/1/1 session taken'],
            [0, [0, 2, 1], '0 event taken, 0/2/1 session taken'],
            [0, [1, 1, 1], '0 event taken, 1/1/1 session taken'],
            [0, [2, 1, 1], '0 event taken, 2/1/1 session taken'],
            [0, [2, 2, 2], '0 event taken, 2/2/2 session taken'],
            [1, [0, 0, 0], '1 event taken, 0/0/0 session taken'],
            [1, [0, 0, 1], '1 event taken, 0/0/1 session taken'],
            [1, [0, 0, 2], '1 event taken, 0/0/2 session taken'],
            [1, [1, 1, 1], '1 event taken, 1/1/1 session taken'],
            [2, [1, 1, 1], '2 event taken, 1/1/1 session taken'],
            [2, [2, 2, 2], '2 event taken, 2/2/2 session taken'],
        ];

        $this->seminar
            ->set_attendancetime(seminar::EVENT_ATTENDANCE_UNRESTRICTED)
            ->set_sessionattendance(seminar::SESSION_ATTENDANCE_UNRESTRICTED)->save();

        // Hold on until attendance_helper is fixed.
        // $to_at = function (int $count) {
        //     if ($count == 2) {
        //         return attendance_taking_status::ALLSAVED;
        //     } else {
        //         return attendance_taking_status::OPEN;
        //     }
        // };

        $seminareventids = [];
        foreach ($eventsdata as $data) {
            $users = [];
            for ($i = 0; $i < 2; $i++) {
                $userid = $this->generator->create_user()->id;
                $this->generator->enrol_user($userid, $this->course->id);
                $users[] = $userid;
            }
            $seminarevent = (new seminar_event())->set_facetoface($this->seminar->get_id());
            $seminarevent->save();
            $time = time();
            seminar_event_helper::merge_sessions($seminarevent, [
                $this->prepare_date($time - HOURSECS * 3, $time - HOURSECS * 2),
                $this->prepare_date($time - HOURSECS * 1, $time + HOURSECS * 1),
                $this->prepare_date($time + HOURSECS * 2, $time + HOURSECS * 3),
            ]);
            /** @var signup[] $signups */
            $signups = [];
            for ($i = 0; $i < 2; $i++) {
                $signup = $this->signup_state($seminarevent, $users[$i], booked::class);
                $signups[] = $signup;
            }
            for ($i = 0; $i < $data[0]; $i++) {
                $signups[$i]->switch_state(fully_attended::class);
            }
            $this->assertEquals($data[0], $this->count_saved_attendees($seminarevent->get_id()));
            /** @var seminar_session[] $sessions */
            $sessions = iterator_to_array($seminarevent->get_sessions(true), false);
            $this->assertCount(3, $sessions);
            for ($j = 0; $j < 3; $j++) {
                $sessiondateid = $sessions[$j]->get_id();
                for ($i = 0; $i < $data[1][$j]; $i++) {
                    $sesss = session_status::from_signup($signups[$i], $sessiondateid);
                    $sesss->set_attendance_status(partially_attended::class)->save();
                }
                // Hold on until attendance_helper is fixed.
                // $status = $sessions[$j]->get_attendance_taking_status(null, 0, true, true);
                // $this->assertEquals($to_at($data[1][$j]), $status);
            }
            // Hold on until attendance_helper is fixed.
            // $status = $seminarevent->get_attendance_taking_status(null, 0, true, true);
            // $this->assertEquals($to_at($data[0]), $status);
            $seminareventids[] = $seminarevent->get_id();
        }

        $events = $this->get_filtered_event_list(advanced_filter::class, $value, $seminareventids);
        $this->assertEquals($expected, $events);
    }

    /**
     * @return array of [ attendancetime, expected ]
     */
    public function data_advanced_filter_attendance_open(): array {
        return [
            [seminar::EVENT_ATTENDANCE_LAST_SESSION_END,    '..x.......x.....'],
            [seminar::EVENT_ATTENDANCE_FIRST_SESSION_START, '..x.x...x.x.x...'],
            [seminar::EVENT_ATTENDANCE_UNRESTRICTED,        '..x.x.x.x.x.x.x.'],
            [seminar::EVENT_ATTENDANCE_LAST_SESSION_START,  '..x.x.....x.x...'],
        ];
    }

    /**
     * Test attendance open of advanced_filter fourth dimensionally.
     * @dataProvider data_advanced_filter_attendance_open
     */
    public function test_advanced_filter_attendance_open(int $attendancetime, string $expected) {
        $eventsdata = [
            [[], false, 'Wait-listed event'],
            [[], true, 'Cancelled wait-listed event'],
            [[-DAYSECS * 9, -DAYSECS * 8], false, 'Past sessions only'],
            [[-DAYSECS * 7, -DAYSECS * 6], true, 'Cancelled past sessions only'],
            [[-HOURSECS, -HOURSECS * 2], false, 'Ongoing sessions only'],
            [[-HOURSECS * 3, -HOURSECS * 4], true, 'Cancelled ongoing sessions only'],
            [[+DAYSECS * 9, +DAYSECS * 8], false, 'Future events only'],
            [[+DAYSECS * 7, +DAYSECS * 6], true, 'Cancelled future events only'],
            [[-DAYSECS * 5, +DAYSECS * 5], false, 'Future and past sessions'],
            [[-DAYSECS * 4, +DAYSECS * 4], true, 'Cancelled future and past sessions'],
            [[-DAYSECS * 3], false, 'Past session'],
            [[-DAYSECS * 2], true, 'Cancelled past session'],
            [[-HOURSECS * 5], false, 'Ongoing session'],
            [[-HOURSECS * 6], true, 'Cancelled ongoing session'],
            [[+DAYSECS * 3], false, 'Future session'],
            [[+DAYSECS * 2], true, 'Cancelled future session'],
        ];

        $userid = $this->generator->create_user()->id;
        $this->generator->enrol_user($userid, $this->course->id);

        $seminareventids = [];
        foreach ($eventsdata as $data) {
            $dates = array_map(function ($time) {
                $now = time();
                return $this->prepare_date($now + $time, $now + $time + HOURSECS * 12);
            }, $data[0]);
            $seminarevent = (new seminar_event())->set_facetoface($this->seminar->get_id());
            $seminarevent->save();
            seminar_event_helper::merge_sessions($seminarevent, $dates);
            if ($data[1]) {
                $stateclass = event_cancelled::class;
            } else if (empty($data[0])) {
                $stateclass = waitlisted::class;
            } else {
                $stateclass = booked::class;
            }
            $this->signup_state($seminarevent, $userid, $stateclass);
            if ($data[1]) {
                // Poor man's cancellation: flip cancelledstatus instead of seminar_event::cancel()
                $seminarevent->set_cancelledstatus(1)->save();
            }
            $seminareventids[] = $seminarevent->get_id();
        }

        $this->seminar->set_attendancetime($attendancetime)->save();
        $events = $this->get_filtered_event_list(advanced_filter::class, event_advanced_filter::ATTENDANCE_OPEN, $seminareventids);
        $this->assertEquals($expected, $events);
    }

    /**
     * @return array of [ attendancetime, expected ]
     */
    public function data_advanced_filter_booking(): array {
        return [
            [event_advanced_filter::OVERBOOKED,     '............xx.'],
            [event_advanced_filter::UNDERBOOKED,    'xx.xx..........'],
        ];
    }

    /**
     * Test advanced_filter related to booking fourth dimensionally.
     * @dataProvider data_advanced_filter_booking
     */
    public function test_advanced_filter_booking(int $value, string $expected) {
        $eventsdata = [
            [0, +3, '0 booked, future'],
            [0, -1, '0 booked, ongoing'],
            [0, -4, '0 booked, past'],
            [1, +3, '1 booked, future'],
            [1, -1, '1 booked, ongoing'],
            [1, -4, '1 booked, past'],
            [2, +3, '2 booked, future'],
            [2, -1, '2 booked, ongoing'],
            [2, -4, '2 booked, past'],
            [3, +3, '3 booked, future'],
            [3, -1, '3 booked, ongoing'],
            [3, -4, '3 booked, past'],
            [4, +3, '4 booked, future'],
            [4, -1, '4 booked, ongoing'],
            [4, -4, '4 booked, past'],
        ];

        $seminareventids = [];
        foreach ($eventsdata as $data) {
            $users = [];
            for ($i = 0; $i < 4; $i++) {
                $userid = $this->generator->create_user()->id;
                $this->generator->enrol_user($userid, $this->course->id);
                $users[] = $userid;
            }
            $seminarevent = (new seminar_event())->set_facetoface($this->seminar->get_id())
                ->set_allowoverbook(0)->set_mincapacity(2)->set_capacity(3);
            $seminarevent->save();
            $time = time() + $data[1] * HOURSECS;
            seminar_event_helper::merge_sessions($seminarevent, [$this->prepare_date($time, $time + HOURSECS * 2)]);
            /** @var signup[] $signups */
            $signups = [];
            for ($i = 0; $i < $data[0]; $i++) {
                $signup = $this->signup_state($seminarevent, $users[$i], booked::class);
                $signups[] = $signup;
            }
            $seminareventids[] = $seminarevent->get_id();
        }

        $events = $this->get_filtered_event_list(advanced_filter::class, $value, $seminareventids);
        $this->assertEquals($expected, $events);
    }

    /**
     * @return array of [ filterID, expected ]
     */
    public function data_booking_filter(): array {
        return [
            [event_booking_filter::ALL,         'xxxxxxxxxxxxxxxxxxxxx'],
            [event_booking_filter::OPEN,        '...xx.xx.x.........x.'],
            [event_booking_filter::BOOKED,      '...........xxxxx.....'],
            [event_booking_filter::WAITLISTED,  '..........x..........'],
            [event_booking_filter::REQUESTED,   '................xxx..'],
            [event_booking_filter::CANCELLED,   '...................x.'],
        ];
    }

    /**
     * Test booking_filter.
     * @dataProvider data_booking_filter
     */
    public function test_booking_filter(int $value, string $expected) {
        $eventsdata = [
            [not_set::class, +20, 0, 0, true, 'not booked, cancelled'],
            [not_set::class, -10, 0, 0, false, 'not booked, past'],
            [not_set::class, -1, 0, 0, false, 'not booked, ongoing'],
            [not_set::class, +10, 0, 0, false, 'not booked, future'],
            [not_set::class, +5, -1, 0, false, 'not booked, future, sign-up open'],
            [not_set::class, +6, +1, 0, false, 'not booked, future, sign-up not open'],
            [not_set::class, +7, -2, +2, false, 'not booked, future, sign-up open, not closed'],
            [not_set::class, +8, 0, +1, false, 'not booked, future, sign-up not closed'],
            [not_set::class, +9, 0, -1, false, 'not booked, future, sign-up closed'],
            [declined::class, +10, 0, 0, false, 'declined'],
            [waitlisted::class, +10, 0, 0, false, 'waitlisted'],
            [booked::class, +10, 0, 0, false, 'booked'],
            [fully_attended::class, +10, 0, 0, false, 'fully attended'],
            [partially_attended::class, +10, 0, 0, false, 'partially attended'],
            [unable_to_attend::class, +10, 0, 0, false, 'unable to attend'],
            [no_show::class, +10, 0, 0, false, 'no show'],
            [requested::class, +10, 0, 0, false, 'requested'],
            [requestedadmin::class, +10, 0, 0, false, 'requested (admin)'],
            [requestedrole::class, +10, 0, 0, false, 'requested (role)'],
            [user_cancelled::class, +10, 0, 0, false, 'user cancelled'],
            [event_cancelled::class, +10, 0, 0, true, 'event cancelled'],
        ];

        $userid = $this->generator->create_user()->id;
        $this->generator->enrol_user($userid, $this->course->id);

        $seminareventids = [];
        foreach ($eventsdata as $data) {
            $seminarevent = (new seminar_event())->set_facetoface($this->seminar->get_id());
            $seminarevent->save();
            $time = time() + HOURSECS * $data[1];
            seminar_event_helper::merge_sessions($seminarevent, [$this->prepare_date($time, $time + HOURSECS + HOURSECS / 2)]);
            if ($data[2]) {
                $seminarevent->set_registrationtimestart(time() + HOURSECS * $data[2])->save();
            }
            if ($data[3]) {
                $seminarevent->set_registrationtimefinish(time() + HOURSECS * $data[3])->save();
            }
            $this->signup_state($seminarevent, $userid, $data[0]);
            if ($data[4]) {
                // Poor man's cancellation: flip cancelledstatus instead of seminar_event::cancel()
                $seminarevent->set_cancelledstatus(1)->save();
            }
            $seminareventids[] = $seminarevent->get_id();
        }

        $this->setUser($userid);
        $events = $this->get_filtered_event_list(booking_filter::class, $value, $seminareventids);
        $this->assertEquals($expected, $events);
    }

    /**
     * @return array of [ event_time, expected ]
     */
    public function data_event_time_filter(): array {
        return [
            [event_time::ALL,           'xxxxxxxxxxxxxxxx'],
            [event_time::UPCOMING,      'x.....x.......x.'],
            [event_time::INPROGRESS,    '....x...x...x...'],
            [event_time::OVER,          '.xxx.x.x.xxx.x.x'],
            [event_time::FUTURE,        '......x.......x.'],
            [event_time::PAST,          '..x.......x.....'],
            [event_time::WAITLISTED,    'x...............'],
            [event_time::CANCELLED,     '.x.x.x.x.x.x.x.x'],
        ];
    }

    /**
     * Test event_time_filter.
     * @dataProvider data_event_time_filter
     */
    public function test_event_time_filter(int $value, string $expected) {
        $eventsdata = [
            [[], false, 'Wait-listed event'],
            [[], true, 'Cancelled wait-listed event'],
            [[-DAYSECS * 9, -DAYSECS * 8], false, 'Past sessions only'],
            [[-DAYSECS * 7, -DAYSECS * 6], true, 'Cancelled past sessions only'],
            [[-HOURSECS, -HOURSECS * 2], false, 'Ongoing sessions only'],
            [[-HOURSECS * 3, -HOURSECS * 4], true, 'Cancelled ongoing sessions only'],
            [[+DAYSECS * 9, +DAYSECS * 8], false, 'Future events only'],
            [[+DAYSECS * 7, +DAYSECS * 6], true, 'Cancelled future events only'],
            [[-DAYSECS * 5, +DAYSECS * 5], false, 'Future and past sessions'],
            [[-DAYSECS * 4, +DAYSECS * 4], true, 'Cancelled future and past sessions'],
            [[-DAYSECS * 3], false, 'Past session'],
            [[-DAYSECS * 2], true, 'Cancelled past session'],
            [[-HOURSECS * 5], false, 'Ongoing session'],
            [[-HOURSECS * 6], true, 'Cancelled ongoing session'],
            [[+DAYSECS * 3], false, 'Future session'],
            [[+DAYSECS * 2], true, 'Cancelled future session'],
        ];

        $seminareventids = [];
        foreach ($eventsdata as $data) {
            $dates = array_map(function ($time) {
                $now = time();
                return $this->prepare_date($now + $time, $now + $time + HOURSECS * 12);
            }, $data[0]);
            $seminarevent = (new seminar_event())->set_facetoface($this->seminar->get_id());
            $seminarevent->save();
            seminar_event_helper::merge_sessions($seminarevent, $dates);
            if ($data[1]) {
                // Poor man's cancellation: just set the cancelledstatus instead of seminar_event::cancel()
                $seminarevent->set_cancelledstatus(1)->save();
            }
            $seminareventids[] = $seminarevent->get_id();
        }

        $events = $this->get_filtered_event_list(event_time_filter::class, $value, $seminareventids);
        $this->assertEquals($expected, $events);
    }

    /**
     * @return array of [ selectedIndex, expected ]
     */
    public function data_room_filter(): array {
        return [
            [0, 'xxxxxxx'],
            [1, '.xxxxxx'],
            [2, '..xxxxx'],
            [3, '...x.xx'],
        ];
    }

    /**
     * Test room_filter.
     * @dataProvider data_room_filter
     */
    public function test_room_filter(int $value, string $expected) {
        $rooms = [
            room_filter::DEFAULT_VALUE,
            $this->facetoface_generator->add_site_wide_room(['name' => 'Room 1'])->id,
            $this->facetoface_generator->add_site_wide_room(['name' => 'Room 2'])->id,
            $this->facetoface_generator->add_site_wide_room(['name' => 'Room 3'])->id,
        ];

        $eventsdata = [
            [[[], []], 'no rooms + no rooms'],
            [[[1], []], '1 + no rooms'],
            [[[1, 2], []], '1/2 + no rooms'],
            [[[1, 2, 3], []], '1/2/3 + no rooms'],
            [[[1], [2]], '1 + 2'],
            [[[1], [2, 3]], '1 + 2/3'],
            [[[1, 2], [3]], '1/2 + 3'],
        ];

        $seminareventids = [];
        $time = time();
        foreach ($eventsdata as $data) {
            $dates = array_map(function ($indexes) use (&$time, &$rooms) {
                $roomids = array_map(function ($index) use (&$rooms) {
                    return $rooms[$index];
                }, $indexes);
                $time += HOURSECS * 2;
                return $this->prepare_date($time, $time + HOURSECS, $roomids, []);
            }, $data[0]);
            $seminarevent = (new seminar_event())->set_facetoface($this->seminar->get_id());
            $seminarevent->save();
            seminar_event_helper::merge_sessions($seminarevent, $dates);
            $seminareventids[] = $seminarevent->get_id();
        }

        $events = $this->get_filtered_event_list(room_filter::class, $rooms[$value], $seminareventids);
        $this->assertEquals($expected, $events);
    }

    /**
     * @return array of [ selectedIndex, expected ]
     */
    public function data_facilitator_filter(): array {
        return [
            [0, 'xxxxxxx'],
            [1, '.xxxxxx'],
            [2, '..xxxxx'],
            [3, '...x.xx'],
        ];
    }

    /**
     * Test facilitator_filter.
     * @dataProvider data_facilitator_filter
     */
    public function test_facilitator_filter(int $value, string $expected) {
        $facilitators = [
            facilitator_filter::DEFAULT_VALUE,
            $this->facetoface_generator->add_site_wide_facilitator(['name' => 'Facilitator 1'])->id,
            $this->facetoface_generator->add_site_wide_facilitator(['name' => 'Facilitator 2'])->id,
            $this->facetoface_generator->add_site_wide_facilitator(['name' => 'Facilitator 3'])->id,
        ];

        $eventsdata = [
            [[[], []], 'no facilitators + no facilitators'],
            [[[1], []], '1 + no facilitators'],
            [[[1, 2], []], '1/2 + no facilitators'],
            [[[1, 2, 3], []], '1/2/3 + no facilitators'],
            [[[1], [2]], '1 + 2'],
            [[[1], [2, 3]], '1 + 2/3'],
            [[[1, 2], [3]], '1/2 + 3'],
        ];

        $seminareventids = [];
        $time = time();
        foreach ($eventsdata as $data) {
            $dates = array_map(function ($indexes) use (&$time, &$facilitators) {
                $facilitatorids = array_map(function ($index) use (&$facilitators) {
                    return $facilitators[$index];
                }, $indexes);
                $time += HOURSECS * 2;
                return $this->prepare_date($time, $time + HOURSECS, [], $facilitatorids);
            }, $data[0]);
            $seminarevent = (new seminar_event())->set_facetoface($this->seminar->get_id());
            $seminarevent->save();
            seminar_event_helper::merge_sessions($seminarevent, $dates);
            $seminareventids[] = $seminarevent->get_id();
        }

        $events = $this->get_filtered_event_list(facilitator_filter::class, $facilitators[$value], $seminareventids);
        $this->assertEquals($expected, $events);
    }
}
