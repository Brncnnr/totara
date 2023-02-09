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

use mod_facetoface\{seminar_event, seminar, seminar_session, signup, signup_helper, seminar_event_helper, seminar_session_list, attendance_taking_status, attendance\attendance_helper, signup\state\booked, signup\state\fully_attended, signup\state\unable_to_attend, signup\condition\event_taking_attendance};

class mod_facetoface_seminar_session_testcase extends advanced_testcase {
    /**
     * Create seminar_event with default setting of seminar and event itself
     * @return seminar_event
     */
    private function create_event(): seminar_event {
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();

        $f2f = new seminar();
        $f2f->set_course($course->id)->save();

        $event = new seminar_event();
        $event->set_facetoface($f2f->get_id());
        $event->save();

        return $event;
    }

    /**
     * Data for session_status test suite.
     * @return array
     */
    public function provide_session_status_data(): array {
        $time = time();
        return [
            [
                ['start' => $time + 3600, 'finish' => $time + (3600 * 2)],
                $time,
                ['upcoming' => true, 'over' => false]
            ],
            [
                ['start' => $time + 3600, 'finish' => $time + (3600 * 3)],
                $time + (3600 * 5),
                ['upcoming' => false, 'over' => true]
            ],
            [
                ['start' => $time + 3600, 'finish' => $time + (3600 * 3)],
                $time + (3600 * 2),
                ['upcoming' => false , 'over' => false]
            ]
        ];
    }

    /**
     * Test suite of checking the session status base on: created time check against the current-time
     *
     * @dataProvider provide_session_status_data
     * @param array $time
     * @param int $currenttime
     * @param array $result
     */
    public function test_check_session_status(array $time, int $currenttime, array $result): void {
        $event = $this->create_event();

        $session = new seminar_session();
        $session->set_sessionid($event->get_id());
        $session->set_timestart($time['start']);
        $session->set_timefinish($time['finish']);
        $session->save();

        $this->assertEquals($result['upcoming'], $session->is_upcoming($currenttime));
        $this->assertEquals($result['over'], $session->is_over($currenttime));
    }

    /**
     * Data for checking attendance_open test suite.
     * @return array
     */
    public function provide_attendance_status_data(): array {
        $time = time();
        return [
            [
                ['start' => $time + 3600, 'finish' => $time + (3600 * 3)],
                $time,
                ['sessionattendance' => seminar::SESSION_ATTENDANCE_UNRESTRICTED],
                true,
            ],
            [
                ['start' => $time + 3600, 'finish' => $time + (3600 * 3)],
                $time,
                ['sessionattendance' => seminar::SESSION_ATTENDANCE_END],
                false
            ],
            [
                ['start' => $time + 295, 'finish' => $time + (3600 * 3)],
                $time,
                ['sessionattendance' => seminar::SESSION_ATTENDANCE_START],
                true
            ],
            [
                ['start' => $time + 3600, 'finish' => $time + (3600 * 3)],
                $time + (3600 * 4),
                ['sessionattendance' => seminar::SESSION_ATTENDANCE_END],
                true
            ],
            [
                ['start' => $time + 3600, 'finish' => $time + (3600 * 3)],
                $time,
                ['sessionattendance' => seminar::SESSION_ATTENDANCE_END],
                false
            ],
            [
                [],
                $time,
                ['sessionattendance' => seminar::SESSION_ATTENDANCE_START],
                false
            ],
            [
                [],
                $time,
                ['sessionattendance' => seminar::SESSION_ATTENDANCE_UNRESTRICTED],
                false
            ],
            [
                [],
                $time,
                ['sessionattendance' => seminar::SESSION_ATTENDANCE_END],
                false
            ],
            [
                ['start' => $time + 3600, 'finish' => $time + (3600 * 2)],
                $time,
                ['sessionattendance' => seminar::SESSION_ATTENDANCE_DISABLED],
                false
            ],
            [
                ['start' => $time + 3600, 'finish' => $time + (3600 * 2)],
                $time,
                ['sessionattendance' => seminar::SESSION_ATTENDANCE_DISABLED],
                false
            ],
            [
                ['start' => $time + 3600, 'finish' => $time + (3600 * 2)],
                $time,
                ['sessionattendance' => seminar::SESSION_ATTENDANCE_DISABLED],
                false
            ],
            [
                ['start' => $time + 3600, 'finish' => $time + (3600 * 2)],
                $time,
                ['sessionattendance' => seminar::SESSION_ATTENDANCE_DISABLED],
                false
            ],
        ];
    }

    /**
     * Test suite to check the logic of open attendance: sessions time against current time,
     * and also checking against seminar->settings.
     *
     * @dataProvider provide_attendance_status_data
     * @param array $time
     * @param int $currenttime
     * @param bool $result
     * @param array $seminarsetting
     */
    public function test_is_attendance_open(array $time, int $currenttime, array $seminarsetting,
                                            bool $result): void {
        $event = $this->create_event();

        $seminar = $event->get_seminar();
        $seminar->set_sessionattendance($seminarsetting['sessionattendance']);
        $seminar->save();

        $session = new seminar_session();
        $session->set_sessionid($event->get_id());
        if (isset($time['start'])) {
            $session->set_timestart($time['start']);
        }

        if (isset($time['finish'])) {
            $session->set_timefinish($time['finish']);
        }

        $session->save();
        $this->assertEquals($result, $session->is_attendance_open($currenttime));
    }

    /**
     * If an event is cancelled, then it should not be open for taking attendance.
     * @return void
     */
    public function test_open_attendance_when_event_is_cancelled(): void {
        $event = $this->create_event();

        $seminar = $event->get_seminar();
        $seminar->set_sessionattendance(seminar::EVENT_ATTENDANCE_UNRESTRICTED);
        $seminar->set_attendancetime(seminar::SESSION_ATTENDANCE_UNRESTRICTED);
        $seminar->save();

        $session = new seminar_session();
        $session->set_timefinish(time() + 3000);
        $session->set_timestart(time() + 300);
        $session->set_sessionid($event->get_id());
        $session->save();

        $event->set_cancelledstatus(1);
        $event->save();

        $this->assertFalse($session->is_attendance_open(time()));
    }

    /**
     * @see \mod_facetoface_lib_testcase::prepare_date
     *
     * @param int|string $timestart
     * @param int|string $timeend
     * @param int|string $roomid
     * @return \stdClass
     */
    protected function prepare_date($timestart, $timeend, $roomid): \stdClass {
        $sessiondate = new stdClass();
        $sessiondate->timestart = (string)$timestart;
        $sessiondate->timefinish = (string)$timeend;
        $sessiondate->sessiontimezone = '99';
        $sessiondate->roomids = [$roomid];
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
    private function create_users_signups(int $numusers, seminar_event $seminarevent, $stateclass = null): array {
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

    /**
     * @return array
     */
    public function data_provider_for_get_attendance_taking_status_sane_cases(): array {
        $not_avail = attendance_taking_status::NOTAVAILABLE;
        $not_end__ = attendance_taking_status::CLOSED_UNTILEND;
        $not_start = attendance_taking_status::CLOSED_UNTILSTART;
        $yes_open_ = attendance_taking_status::OPEN;
        $allsaved_ = attendance_taking_status::ALLSAVED;
        $near_future = event_taking_attendance::UNLOCKED_SECS_PRIOR_TO_START / 2;

        // [ timestart, attendance_time, [ no_signups, one_taken, two_taken ], tag ]
        return [
            [
                -YEARSECS,
                seminar::SESSION_ATTENDANCE_END,
                [ [ $yes_open_, $yes_open_, $yes_open_, $not_avail ], [ $yes_open_, $yes_open_ ], [ $yes_open_, $allsaved_ ] ],
                'Over (end)'
            ],
            [
                -YEARSECS,
                seminar::SESSION_ATTENDANCE_START,
                [ [ $yes_open_, $yes_open_, $yes_open_, $not_avail ], [ $yes_open_, $yes_open_ ], [ $yes_open_, $allsaved_ ] ],
                'Over (start)'
            ],
            [
                -YEARSECS,
                seminar::SESSION_ATTENDANCE_UNRESTRICTED,
                [ [ $yes_open_, $yes_open_, $yes_open_, $not_avail ], [ $yes_open_, $yes_open_ ], [ $yes_open_, $allsaved_ ] ],
                'Over (any)'
            ],
            [
                -MINSECS,
                seminar::SESSION_ATTENDANCE_END,
                [ [ $not_end__, $not_end__, $not_end__, $not_end__ ], [ $not_end__, $not_end__ ], [ $not_end__, $not_end__ ] ],
                'Ongoing (end)'
            ],
            [
                -MINSECS,
                seminar::SESSION_ATTENDANCE_START,
                [ [ $yes_open_, $yes_open_, $yes_open_, $not_avail ], [ $yes_open_, $yes_open_ ], [ $yes_open_, $allsaved_ ] ],
                'Ongoing (start)'
            ],
            [
                -MINSECS,
                seminar::SESSION_ATTENDANCE_UNRESTRICTED,
                [ [ $yes_open_, $yes_open_, $yes_open_, $not_avail ], [ $yes_open_, $yes_open_ ], [ $yes_open_, $allsaved_ ] ],
                'Ongoing (any)'
            ],
            [
                $near_future,
                seminar::SESSION_ATTENDANCE_END,
                [ [ $not_end__, $not_end__, $not_end__, $not_end__ ], [ $not_end__, $not_end__ ], [ $not_end__, $not_end__ ] ],
                'Almost open (end)'
            ],
            [
                $near_future,
                seminar::SESSION_ATTENDANCE_START,
                [ [ $yes_open_, $yes_open_, $yes_open_, $not_avail ], [ $yes_open_, $yes_open_ ], [ $yes_open_, $allsaved_ ] ],
                'Almost open (start)'
            ],
            [
                $near_future,
                seminar::SESSION_ATTENDANCE_UNRESTRICTED,
                [ [ $yes_open_, $yes_open_, $yes_open_, $not_avail ], [ $yes_open_, $yes_open_ ], [ $yes_open_, $allsaved_ ] ],
                'Almost open (any)'
            ],
            [
                YEARSECS,
                seminar::SESSION_ATTENDANCE_END,
                [ [ $not_end__, $not_end__, $not_end__, $not_end__ ], [ $not_end__, $not_end__ ], [ $not_end__, $not_end__ ] ],
                'Upcoming (end)'
            ],
            [
                YEARSECS,
                seminar::SESSION_ATTENDANCE_START,
                [ [ $not_start, $not_start, $not_start, $not_start ], [ $not_start, $not_start ], [ $not_start, $not_start ] ],
                'Upcoming (start)'
            ],
            [
                YEARSECS,
                seminar::SESSION_ATTENDANCE_UNRESTRICTED,
                [ [ $yes_open_, $yes_open_, $yes_open_, $not_avail ], [ $yes_open_, $yes_open_ ], [ $yes_open_, $allsaved_ ] ],
                'Upcoming (any)'
            ],
        ];
    }

    /**
     * @param integer   $timestart
     * @param integer   $sessionattendance
     * @param array     $expections
     * @param string    $tag
     * @dataProvider data_provider_for_get_attendance_taking_status_sane_cases
     */
    public function test_get_attendance_taking_status_sane_cases(int $timestart, int $sessionattendance, array $expections, string $tag) {
        $now = time();

        $facetoface_generator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $course = $this->getDataGenerator()->create_course();
        $f2f = $this->getDataGenerator()->create_module('facetoface', array('course' => $course->id));
        $seminar = new seminar($f2f->id);

        // Turn on session attendance tracking
        $seminar->set_sessionattendance($sessionattendance)->save();

        // Set up three seminar events as follows:
        // Event  Sign-up  Attendance
        // -----  -------  ----------
        // 1      none     -
        // 2      2 users  1 user
        // 3      2 users  2 users

        $room1 = $facetoface_generator->add_site_wide_room([]);
        $room2 = $facetoface_generator->add_site_wide_room([]);
        $room3 = $facetoface_generator->add_site_wide_room([]);
        $seminarevent1id = $facetoface_generator->add_session(['facetoface' => $seminar->get_id(), 'sessiondates' => [] ]);
        $seminarevent2id = $facetoface_generator->add_session(['facetoface' => $seminar->get_id(), 'sessiondates' => [] ]);
        $seminarevent3id = $facetoface_generator->add_session(['facetoface' => $seminar->get_id(), 'sessiondates' => [] ]);
        $seminarevent1 = new seminar_event($seminarevent1id);
        $seminarevent2 = new seminar_event($seminarevent2id);
        $seminarevent3 = new seminar_event($seminarevent3id);

        $signups1 = []; // no signups
        $signups2 = $this->create_users_signups(2, $seminarevent2, booked::class);
        $signups3 = $this->create_users_signups(2, $seminarevent3, booked::class);

        seminar_event_helper::merge_sessions($seminarevent1, [ $this->prepare_date($now + $timestart, $now + $timestart + DAYSECS, $room1->id) ]);
        seminar_event_helper::merge_sessions($seminarevent2, [ $this->prepare_date($now + $timestart, $now + $timestart + DAYSECS, $room2->id) ]);
        seminar_event_helper::merge_sessions($seminarevent3, [ $this->prepare_date($now + $timestart, $now + $timestart + DAYSECS, $room3->id) ]);

        $seminarsession1 = seminar_session_list::from_seminar_event($seminarevent1)->current();
        /** @var seminar_session $seminarsession1 */
        $seminarsession2 = seminar_session_list::from_seminar_event($seminarevent2)->current();
        /** @var seminar_session $seminarsession2 */
        $seminarsession3 = seminar_session_list::from_seminar_event($seminarevent3)->current();
        /** @var seminar_session $seminarsession3 */

        // Take only the first attendance for event2
        $signup = current($signups2);
        /** @var signup $signup */
        attendance_helper::process_session_attendance([ $signup->get_id() => fully_attended::get_code() ], $seminarsession2->get_id());

        // Take all attendance for event3
        foreach ($signups3 as $signup) {
            /** @var signup $signup */
            attendance_helper::process_session_attendance([ $signup->get_id() => unable_to_attend::get_code() ], $seminarsession3->get_id());
        }

        // Walk though the $expections matrix
        $this->assertSame($expections[0][0], $seminarsession1->get_attendance_taking_status(null, $now, false, false), 'event1, false, false');
        $this->assertSame($expections[0][1], $seminarsession1->get_attendance_taking_status(null, $now, true, false), 'event1, true, false');
        $this->assertSame($expections[0][2], $seminarsession1->get_attendance_taking_status(null, $now, false, true), 'event1, false, true');
        $this->assertSame($expections[0][3], $seminarsession1->get_attendance_taking_status(null, $now, true, true), 'event1, true, true');
        $this->assertSame($expections[1][0], $seminarsession2->get_attendance_taking_status(null, $now, false), 'event2, false');
        $this->assertSame($expections[1][1], $seminarsession2->get_attendance_taking_status(null, $now, true), 'event2, true');
        $this->assertSame($expections[2][0], $seminarsession3->get_attendance_taking_status(null, $now, false), 'event3, false');
        $this->assertSame($expections[2][1], $seminarsession3->get_attendance_taking_status(null, $now, true), 'event3, true');

        $test_cancelled_event = function (seminar_event $event, seminar_session $session, string $name) use ($now) {
            if (!$event->cancel()) {
                return;
            }
            // Because the seminar_session instance holds its parent seminar_event instance internally,
            // the seminar_event.cancelledstatus has now become out of sync (sigh)
            $this->assertNotEquals($event->get_cancelledstatus(), $session->get_seminar_event()->get_cancelledstatus());
            // Reloading the seminar_session instance is the way to go
            $session = new seminar_session($session->get_id());
            $this->assertSame(attendance_taking_status::CANCELLED, $session->get_attendance_taking_status(null, $now, false), $name . ' cancelled');
        };

        // If a seminar event is cancellable, then cancel it and see the function returns CANCELLED afterwards
        $test_cancelled_event($seminarevent1, $seminarsession1, 'event1');
        $test_cancelled_event($seminarevent2, $seminarsession2, 'event2');
        $test_cancelled_event($seminarevent3, $seminarsession3, 'event3');
    }

    public function test_get_attendance_taking_status_insane_cases() {
        $now = time();

        $facetoface_generator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $course = $this->getDataGenerator()->create_course();
        $f2f = $this->getDataGenerator()->create_module('facetoface', array('course' => $course->id));
        $seminar = new seminar($f2f->id);

        $room = $facetoface_generator->add_site_wide_room([]);
        $seminareventid = $facetoface_generator->add_session(['facetoface' => $seminar->get_id(), 'sessiondates' => [] ]);
        $seminarevent = new seminar_event($seminareventid);

        $signups = $this->create_users_signups(1, $seminarevent, booked::class);

        seminar_event_helper::merge_sessions($seminarevent, [ $this->prepare_date(0, 0, $room->id) ]);

        $seminarsession = seminar_session_list::from_seminar_event($seminarevent)->current();
        /** @var seminar_session $seminarsession */

        try {
            $emptyseminarsession = new seminar_session();
            $emptyseminarsession->get_attendance_taking_status(null);
            $this->fail('coding_exception expected');
        } catch (\coding_exception $e) {
        }

        // Bogus first argument results in debugging()
        $this->resetDebugging();
        $this->assertSame(attendance_taking_status::UNKNOWN, $seminarsession->get_attendance_taking_status(42));
        $this->assertDebuggingCalled();

        // This is the only sane case
        $seminarsession->set_timestart(1)->set_timefinish(2)->save();
        $this->assertSame(attendance_taking_status::OPEN, $seminarsession->get_attendance_taking_status(seminar::ATTENDANCE_TIME_ANY, $now, false));

        // Mess with invalid timestamps
        $seminarsession->set_timestart(0)->set_timefinish(1)->save();
        $this->assertSame(attendance_taking_status::UNKNOWN, $seminarsession->get_attendance_taking_status(seminar::ATTENDANCE_TIME_ANY, $now, false));
        $seminarsession->set_timestart(1)->set_timefinish(0)->save();
        $this->assertSame(attendance_taking_status::UNKNOWN, $seminarsession->get_attendance_taking_status(seminar::ATTENDANCE_TIME_ANY, $now, true));
        $seminarsession->set_timestart(0)->set_timefinish(0)->save();
        $this->assertSame(attendance_taking_status::UNKNOWN, $seminarsession->get_attendance_taking_status(seminar::ATTENDANCE_TIME_ANY, $now, true));
        $seminarsession->set_timestart(-1)->set_timefinish(1)->save();
        $this->assertSame(attendance_taking_status::UNKNOWN, $seminarsession->get_attendance_taking_status(seminar::ATTENDANCE_TIME_ANY, $now, true));
        $seminarsession->set_timestart(1)->set_timefinish(-1)->save();
        $this->assertSame(attendance_taking_status::UNKNOWN, $seminarsession->get_attendance_taking_status(seminar::ATTENDANCE_TIME_ANY, $now, true));
        $seminarsession->set_timestart(-1)->set_timefinish(-1)->save();
        $this->assertSame(attendance_taking_status::UNKNOWN, $seminarsession->get_attendance_taking_status(seminar::ATTENDANCE_TIME_ANY, $now, true));

        // Delete it
        $seminarsession->set_timestart(1)->set_timefinish(2)->save();
        $seminarsession->delete();
        $status = $seminarsession->get_attendance_taking_status(seminar::ATTENDANCE_TIME_ANY, $now, true);
        $this->assertSame(attendance_taking_status::UNKNOWN, $status);
    }
}
