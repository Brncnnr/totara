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
use mod_facetoface\event\booking_booked;
use mod_facetoface\event\signup_status_updated;
use mod_facetoface\seminar;
use mod_facetoface\seminar_event;
use mod_facetoface\seminar_session;
use mod_facetoface\signup;
use mod_facetoface\signup_status;
use mod_facetoface\signup_helper;
use mod_facetoface\signup\state\booked;
use mod_facetoface\signup\state\declined;
use mod_facetoface\signup\state\event_cancelled;
use mod_facetoface\signup\state\fully_attended;
use mod_facetoface\signup\state\no_show;
use mod_facetoface\signup\state\not_set;
use mod_facetoface\signup\state\partially_attended;
use mod_facetoface\signup\state\requested;
use mod_facetoface\signup\state\requestedadmin;
use mod_facetoface\signup\state\requestedrole;
use mod_facetoface\signup\state\unable_to_attend;
use mod_facetoface\signup\state\user_cancelled;
use mod_facetoface\signup\state\waitlisted;
use totara_job\job_assignment;
use mod_facetoface\testing\generator;

defined('MOODLE_INTERNAL') || die();

/**
 * Class mod_facetoface_signup_helper_testcase
 * @coversDefaultClass mod_facetoface\signup_helper
 */
class mod_facetoface_signup_helper_testcase extends advanced_testcase {
    /**
     * PhpUnit fixture method that runs before the test method executes.
     */
    public function setUp(): void {
        parent::setUp();
        set_config('facetoface_allow_legacy_notifications', 1);
    }

    /**
     * set up for test_compute_final_grade
     *
     * @return \stdClass containing seminar, user1, user2, event1, event2, signup1, signup2
     */
    protected function setup_compute_final_grade() {
        $this->setAdminUser();

        $that = new \stdClass();
        $gen = $this->getDataGenerator();
        $that->course = $gen->create_course();

        $f2fgen = $gen->get_plugin_generator('mod_facetoface');
        $f2f = $f2fgen->create_instance(['course' => $that->course->id]);

        $that->seminar = new seminar($f2f->id);
        $that->seminar
            ->set_sessionattendance(0)
            ->set_multisignupfully(true)
            ->set_multisignuppartly(true)
            ->set_multiplesessions(1)
            ->set_multisignupmaximum(2)
            ->set_eventgradingmethod(0)
            ->save();

        $that->user1 = $gen->create_user();
        $that->user2 = $gen->create_user();
        $gen->enrol_user($that->user1->id, $that->course->id);

        $creator = function ($timediff, $user, $status, &$evt, &$sup) use ($f2f) {
            $evt = new seminar_event();
            $evt->set_facetoface($f2f->id)->save();
            $time = time() + $timediff;
            $sess = new seminar_session();
            $sess->set_timestart($time)->set_timefinish($time + HOURSECS)->set_sessionid($evt->get_id())->save();
            $sup = signup::create($user->id, $evt);
            $sup->save();
            $sup->switch_state(booked::class);
            $sess->set_timestart($time - YEARSECS)->set_timefinish($time - YEARSECS + HOURSECS)->save();
            return signup_helper::process_attendance($evt, [ $sup->get_id() => $status ]);
        };

        $result = $creator(DAYSECS, $that->user1, partially_attended::get_code(), $that->event1, $that->signup1);
        $this->assertTrue($result);
        $result = $creator(DAYSECS * 2, $that->user1, partially_attended::get_code(), $that->event2, $that->signup2);
        $this->assertTrue($result);
        $result = signup_helper::process_attendance($that->event1, [ $that->signup1->get_id() => fully_attended::get_code() ]);
        $this->assertTrue($result);

        return $that;
    }

    /**
     * For more extensive tests,
     * @see \mod_facetoface_event_taking_attendance_testcase
     */
    public function test_compute_final_grade() {
        global $DB;
        $that = $this->setup_compute_final_grade();

        $grade = signup_helper::compute_final_grade($that->seminar, $that->user1->id);
        $this->assertDebuggingCalled();
        $this->assertSame(100., $grade);

        $grade = signup_helper::compute_final_grade($that->seminar, $that->user2->id);
        $this->assertDebuggingCalled();
        $this->assertSame(null, $grade);

        $that->seminar->set_eventgradingmethod(seminar::GRADING_METHOD_GRADELOWEST);
        $grade = signup_helper::compute_final_grade($that->seminar, $that->user1->id);
        $this->assertDebuggingCalled();
        $this->assertSame(50., $grade);

        $grade = signup_helper::compute_final_grade($that->seminar, $that->user2->id);
        $this->assertDebuggingCalled();
        $this->assertSame(null, $grade);

        $f2f = (object)[ 'id' => $that->seminar->get_id() ];
        $grade = signup_helper::compute_final_grade($f2f, $that->user1->id);
        $this->assertDebuggingCalled();
        $this->assertSame(100., $grade);

        $grade = signup_helper::compute_final_grade($f2f, $that->user2->id);
        $this->assertDebuggingCalled();
        $this->assertSame(null, $grade);

        $f2f->eventgradingmethod = seminar::GRADING_METHOD_GRADELOWEST;
        $grade = signup_helper::compute_final_grade($f2f, $that->user1->id);
        $this->assertDebuggingCalled();
        $this->assertSame(50., $grade);

        $grade = signup_helper::compute_final_grade($f2f, $that->user2->id);
        $this->assertDebuggingCalled();
        $this->assertSame(null, $grade);

        $bogusf2fid = $f2f->id + 42;
        $this->assertEquals(0, $DB->count_records('facetoface', [ 'id' => $bogusf2fid ]));

        $seminar = new seminar();
        $rc = new ReflectionClass($seminar);
        $pr = $rc->getProperty('id');
        $pr->setAccessible(true);
        $pr->setValue($seminar, $bogusf2fid); // invalidate seminar->id
        $grade = signup_helper::compute_final_grade($seminar, $that->user1->id);
        $this->assertDebuggingCalled();
        $this->assertSame(null, $grade);

        $that->seminar->set_eventgradingmethod(42); // invalidate eventgradingmethod
        try {
            signup_helper::compute_final_grade($that->seminar, $that->user1->id);
            $this->fail('Must fail when invalid eventgradingmethod is passed');
        } catch (\coding_exception $e) {
            $this->resetDebugging();
        }

        $f2f->id += 42; // invalidate f2f->id
        $this->assertEquals(0, $DB->count_records('facetoface', [ 'id' => $f2f->id ]));
        $grade = signup_helper::compute_final_grade($f2f, $that->user1->id);
        $this->assertDebuggingCalled();
        $this->assertSame(null, $grade);

        $f2f->eventgradingmethod = 42; // invalidate eventgradingmethod
        try {
            signup_helper::compute_final_grade($f2f, $that->user1->id);
            $this->fail('Must fail when invalid eventgradingmethod is passed');
        } catch (\coding_exception $e) {
            $this->resetDebugging();
        }

        try {
            signup_helper::compute_final_grade($f2f->id, $that->user1->id);
            $this->fail('Must fail when first argument is neither seminar nor stdClass');
        } catch (\coding_exception $e) {
            $this->resetDebugging();
        }
    }

    /**
     * @return array of [time_difference]
     */
    public function data_process_attendance_and_email_notification(): array {
        return [
            [-WEEKSECS],
            [-HOURSECS],
            [+DAYSECS]
        ];
    }

    /**
     * @param int $timediff
     * @dataProvider data_process_attendance_and_email_notification
     */
    public function test_process_attendance_and_email_notification(int $timediff) {
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();

        /** @var \mod_facetoface\testing\generator $f2fgen */
        $f2fgen = $gen->get_plugin_generator('mod_facetoface');
        $f2f = $f2fgen->create_instance(['course' => $course->id]);

        (new seminar($f2f->id))
            ->set_attendancetime(seminar::EVENT_ATTENDANCE_UNRESTRICTED)
            ->set_eventgradingmanual(1)
            ->save();

        $user = $gen->create_user();
        $gen->enrol_user($user->id, $course->id);

        $now = time();
        $event = new seminar_event();
        $event->set_facetoface($f2f->id)->save();
        $session = new seminar_session();
        $session->set_timestart($now + YEARSECS)->set_timefinish($now + YEARSECS + HOURSECS)->set_sessionid($event->get_id())->save();
        $sinkevent = $this->redirectEvents();
        $sinkemail = $this->redirectEmails();

        $sinkevent->clear();
        $sinkemail->clear();
        $signup = (signup::create($user->id, $event))->save()->switch_state(booked::class);
        $events = $sinkevent->get_events();
        $this->assertCount(2, $events);
        $this->assertInstanceOf(signup_status_updated::class, $events[0]);
        $this->assertInstanceOf(booking_booked::class, $events[1]);
        self::dispatch_events($events);
        $this->executeAdhocTasks();
        $messages = $sinkemail->get_messages();
        $this->assertCount(1, $messages);

        $session->set_timestart($now + $timediff)->set_timefinish($now + $timediff + HOURSECS * 3)->set_sessionid($event->get_id())->save();

        $sinkevent->clear();
        $sinkemail->clear();
        $result = signup_helper::process_attendance($event, [$signup->get_id() => fully_attended::get_code()], [$signup->get_id() => null]);
        $events = $sinkevent->get_events();
        $this->assertTrue($result);
        $this->assertCount(1, $events);
        $this->assertInstanceOf(signup_status_updated::class, $events[0]);
        self::dispatch_events($events);
        $this->executeAdhocTasks();
        $messages = $sinkemail->get_messages();
        $this->assertCount(0, $messages);

        $sinkevent->clear();
        $sinkemail->clear();
        $result = signup_helper::process_attendance($event, [$signup->get_id() => booked::get_code()], [$signup->get_id() => null]);
        $events = $sinkevent->get_events();
        $this->assertTrue($result);
        $this->assertCount(2, $events);
        $this->assertInstanceOf(signup_status_updated::class, $events[0]);
        $this->assertInstanceOf(booking_booked::class, $events[1]);
        self::dispatch_events($events);
        $this->executeAdhocTasks();
        $messages = $sinkemail->get_messages();
        $this->assertCount(0, $messages);
    }

    /**
     * Simulate dispatching events.
     * @param \core\event\base[] $events
     */
    private static function dispatch_events(array $events) {
        foreach ($events as $event) {
            $disp = new ReflectionProperty(\core\event\base::class, 'dispatched');
            $disp->setAccessible(true);
            $disp->setValue($event, false);
            \core\event\manager::dispatch($event);
            $disp->setValue($event, true);
        }
    }

    public function test_process_attendance() {
        global $CFG;
        require_once($CFG->dirroot . '/lib/gradelib.php');

        $gen = $this->getDataGenerator();
        $course = $gen->create_course();

        /** @var \mod_facetoface\testing\generator $f2fgen */
        $f2fgen = $gen->get_plugin_generator('mod_facetoface');
        $f2f = $f2fgen->create_instance(['course' => $course->id]);

        $seminar = (new seminar($f2f->id))
            ->set_sessionattendance(0)
            ->set_multisignupfully(true)
            ->set_multisignuppartly(true)
            ->set_multiplesessions(1)
            ->set_multisignupmaximum(2)
            ->set_eventgradingmethod(0)
            ->set_eventgradingmanual(1)
            ->save();

        $user1 = $gen->create_user();
        $user2 = $gen->create_user();
        $gen->enrol_user($user1->id, $course->id);
        $gen->enrol_user($user2->id, $course->id);

        $now = time();
        $event1 = new seminar_event();
        $event1->set_facetoface($f2f->id)->save();
        $session1 = new seminar_session();
        $session1->set_timestart($now + DAYSECS)->set_timefinish($now + DAYSECS + HOURSECS)->set_sessionid($event1->get_id())->save();

        $event2 = new seminar_event();
        $event2->set_facetoface($f2f->id)->save();
        $session2 = new seminar_session();
        $session2->set_timestart($now + DAYSECS * 2)->set_timefinish($now + DAYSECS * 2 + HOURSECS)->set_sessionid($event2->get_id())->save();

        $signup11 = (signup::create($user1->id, $event1))->save()->switch_state(booked::class);
        $signup21 = (signup::create($user2->id, $event1))->save()->switch_state(booked::class);
        $signup21->delete();

        $session1->set_timestart($now - DAYSECS * 2)->set_timefinish($now - DAYSECS * 2 + HOURSECS)->save();

        // Attendance
        $result = signup_helper::process_attendance($event1, [ $signup11->get_id() => fully_attended::get_code() ]);
        $this->assertTrue($result);
        $this->assertInstanceOf(fully_attended::class, $signup11->get_state());

        $signup12 = (signup::create($user1->id, $event2))->save()->switch_state(booked::class);
        $session2->set_timestart($now - DAYSECS)->set_timefinish($now - DAYSECS + HOURSECS)->save();

        // Wrong event: silently fails so far
        $result = signup_helper::process_attendance($event2, [ $signup11->get_id() => partially_attended::get_code() ]);
        $this->assertFalse($result);
        $this->assertInstanceOf(fully_attended::class, $signup11->get_state());

        // Attendance
        $result = signup_helper::process_attendance($event2, [ $signup12->get_id() => partially_attended::get_code() ]);
        $this->assertTrue($result);
        $this->assertInstanceOf(fully_attended::class, $signup11->get_state());
        $this->assertInstanceOf(partially_attended::class, $signup12->get_state());

        // Wrong event: silently fails so far
        $result = signup_helper::process_attendance($event1, [ $signup12->get_id() => unable_to_attend::get_code() ]);
        $this->assertFalse($result);
        $this->assertInstanceOf(fully_attended::class, $signup11->get_state());
        $this->assertInstanceOf(partially_attended::class, $signup12->get_state());

        // Ghost signup: silently fails so far
        $result = signup_helper::process_attendance($event1, [ $signup21->get_id() => fully_attended::get_code() ]);
        $this->assertFalse($result);

        // Ghost signup & wrong event: silently fails so far
        $result = signup_helper::process_attendance($event2, [ $signup21->get_id() => fully_attended::get_code() ]);
        $this->assertFalse($result);

        // Attendance & event grade
        $result = signup_helper::process_attendance($event1, [ $signup11->get_id() => no_show::get_code() ], [ $signup11->get_id() => 42]);
        $this->assertTrue($result);
        $grade = signup_status::from_current($signup11)->get_grade();
        $this->assertSame(42., grade_floatval($grade));

        // Same attendance state but event grade
        $result = signup_helper::process_attendance($event1, [ $signup11->get_id() => no_show::get_code() ], [ $signup11->get_id() => 64]);
        $this->assertTrue($result);
        $grade = signup_status::from_current($signup11)->get_grade();
        $this->assertSame(64., grade_floatval($grade));

        // Attendance & wrong event grade: throws coding_exception
        try {
            signup_helper::process_attendance($event2, [ $signup12->get_id() => no_show::get_code() ], [ $signup11->get_id() => 85]);
            $this->fail('coding_exception expected');
        } catch (\coding_exception $ex) {
        }
        $grade = signup_status::from_current($signup11)->get_grade();
        $this->assertSame(64., grade_floatval($grade));
    }

    /**
     * @return array of { state_class, expected, expected_attendance }
     */
    public function data_provider_is_booked_valid(): array {
        return [
            // These states are always displayed as-is.
            [ booked::class, booked::get_string(), booked::get_string() ],
            [ not_set::class, not_set::get_string(), not_set::get_string() ],
            [ requested::class, requested::get_string(), requested::get_string() ],
            [ requestedadmin::class, requestedadmin::get_string(), requestedadmin::get_string() ],
            [ requestedrole::class, requestedrole::get_string(), requestedrole::get_string() ],
            [ waitlisted::class, waitlisted::get_string(), waitlisted::get_string() ],

            // Attendance states will be displayed as "Booked" if $attendancestatus is false.
            [ fully_attended::class, booked::get_string(), fully_attended::get_string() ],
            [ partially_attended::class, booked::get_string(), partially_attended::get_string() ],
            [ no_show::class, booked::get_string(), no_show::get_string() ],
            [ unable_to_attend::class, booked::get_string(), unable_to_attend::get_string() ],

            // These states are not supposed to display in the dashboard.
            [ declined::class, declined::get_string(), declined::get_string() ],
            [ event_cancelled::class, event_cancelled::get_string(), event_cancelled::get_string() ],
            [ user_cancelled::class, user_cancelled::get_string(), user_cancelled::get_string() ],
        ];
    }

    /**
     * @param string $state
     * @param string $expected
     * @param string $expected_at
     * @dataProvider data_provider_is_booked_valid
     */
    public function test_get_user_booking_status_valid(string $state, string $expected, string $expected_at) {
        $this->assertSame($expected, signup_helper::get_user_booking_status($state, false));
        $this->assertSame($expected_at, signup_helper::get_user_booking_status($state, true));

        $this->assertSame($expected, signup_helper::get_user_booking_status($state::get_code(), false));
        $this->assertSame($expected_at, signup_helper::get_user_booking_status($state::get_code(), true));

        $stateinstance = new $state(new signup()); // Instantiate a state class using an empty signup
        $this->assertSame($expected, signup_helper::get_user_booking_status($stateinstance, false));
        $this->assertSame($expected_at, signup_helper::get_user_booking_status($stateinstance, true));
    }

    /**
     * @return array of { state, expected_exception }
     */
    public function data_provider_is_booked_invalid(): array {
        return [
            [ 'foo', '$state must be a state class string, a state class instance or status code' ],
            [ 'mod_facetoface\\signup\\state\\he_who_must_not_be_named', '$state must be a state class string, a state class instance or status code' ],
            [ 3.14159, '$state must be a state class string, a state class instance or status code' ],
            [ -42, 'Cannot find booking state with code: -42' ],
            [ new DateTime(), '$state must be a state class string, a state class instance or status code' ],
        ];
    }

    /**
     * @param mixed $state
     * @param string $expected_ex
     * @dataProvider data_provider_is_booked_invalid
     */
    public function test_get_user_booking_status_invalid($state, string $expected_ex) {
        try {
            signup_helper::get_user_booking_status($state, false);
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertSame('Coding error detected, it must be fixed by a programmer: ' . $expected_ex, $ex->getMessage());
        }

        try {
            signup_helper::get_user_booking_status($state, true);
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertSame('Coding error detected, it must be fixed by a programmer: ' . $expected_ex, $ex->getMessage());
        }
    }

    /**
     * @covers ::get_archived_signups
     */
    public function test_get_archived_signups() {
        // user   signup  archived  status     deleted  return
        // ----   ------  --------  ------     -------  ------
        // user1  yes     yes       fully      yes      no
        // user2  yes     yes       partially  no       yes
        // user3  yes     yes       not_set    no       yes
        // user4  yes     no        unable_to  no       no
        // user5  no      -         -          no       no
        $gen = $this->getDataGenerator();
        $user1 = $gen->create_user();
        $user2 = $gen->create_user();
        $user3 = $gen->create_user();
        $user4 = $gen->create_user();
        $user5 = $gen->create_user();
        $course = $gen->create_course();
        $seminar = new seminar();
        $seminar->set_course($course->id)->save();
        $event = new seminar_event();
        $event->set_facetoface($seminar->get_id())->save();
        $signup1 = signup::create($user1->id, $event)->save();
        $signup2 = signup::create($user2->id, $event)->save();
        $signup3 = signup::create($user3->id, $event)->save();
        $signup4 = signup::create($user4->id, $event)->save();
        signup_status::create($signup1, new fully_attended($signup1), 1111)->save();
        signup_status::create($signup2, new partially_attended($signup2), 2222)->save();
        signup_status::create($signup4, new unable_to_attend($signup4), 4444)->save();
        builder::table('user')->where('id', $user1->id)->update(['deleted' => 1]);
        builder::table('facetoface_signups')->where_in('id', [$signup1->get_id(), $signup2->get_id(), $signup3->get_id()])->update(['archived' => '1']);

        $records = signup_helper::get_archived_signups($event->get_id());
        $this->assertCount(2, $records);
        $this->assertEquals($signup2->get_id(), $records[$signup2->get_id()]->id);
        $this->assertEquals($user2->id, $records[$signup2->get_id()]->userid);
        $this->assertEquals(2222, $records[$signup2->get_id()]->timecreated);
        $this->assertEquals(partially_attended::get_code(), $records[$signup2->get_id()]->statuscode);
        $this->assertEquals($signup3->get_id(), $records[$signup3->get_id()]->id);
        $this->assertEquals($user3->id, $records[$signup3->get_id()]->userid);
        $this->assertSame(null, $records[$signup3->get_id()]->timecreated);
        $this->assertEquals(booked::get_code(), $records[$signup3->get_id()]->statuscode);
    }

    /**
     * @covers ::unarchive_signups
     */
    public function test_unarchive_signups() {
        // user   signup  archived  status     unarchive
        // ----   ------  --------  ------     ---------
        // user1  yes     yes       fully      yes
        // user2  yes     yes       not_set    yes
        // user3  yes     yes       partially  no
        // user4  yes     no        not_set    yes
        // user5  no      -         -          no
        $gen = $this->getDataGenerator();
        $user1 = $gen->create_user();
        $user2 = $gen->create_user();
        $user3 = $gen->create_user();
        $user4 = $gen->create_user();
        $user5 = $gen->create_user();
        $course = $gen->create_course();
        $seminar = new seminar();
        $seminar->set_course($course->id)->save();
        $event = new seminar_event();
        $event->set_facetoface($seminar->get_id())->save();
        $signup1 = signup::create($user1->id, $event)->save();
        $signup2 = signup::create($user2->id, $event)->save();
        $signup3 = signup::create($user3->id, $event)->save();
        $signup4 = signup::create($user4->id, $event)->save();
        signup_status::create($signup1, new fully_attended($signup1), 1111, 11)->save();
        signup_status::create($signup3, new partially_attended($signup2), 3333, 33)->save();
        builder::table('facetoface_signups')->where_in('id', [$signup1->get_id(), $signup2->get_id(), $signup3->get_id()])->update(['archived' => '1']);

        $count = signup_helper::unarchive_signups($event->get_id(), [$signup1->get_id(), $signup2->get_id(), $signup4->get_id()]);
        $this->assertEquals(2, $count);

        $signup1 = new signup($signup1->get_id());
        $this->assertInstanceOf(booked::class, $signup1->get_state());
        $this->assertNull(signup_status::from_current($signup1)->get_grade());
        $signup2 = new signup($signup2->get_id());
        $this->assertInstanceOf(booked::class, $signup2->get_state());
        $this->assertNull(signup_status::from_current($signup2)->get_grade());
        $signup3 = new signup($signup3->get_id());
        $this->assertInstanceOf(partially_attended::class, $signup3->get_state());
        $this->assertEquals(33, signup_status::from_current($signup3)->get_grade());
        $signup4 = new signup($signup4->get_id());
        $this->assertInstanceOf(not_set::class, $signup4->get_state());
        $this->assertNull(signup_status::find_current($signup4));
    }

    /**
     * @return void
     */
    public function test_find_managers_from_signup(): void {
        // Setup the global settings.
        set_config("facetoface_selectjobassignmentonsignupglobal", 1);
        $generator = self::getDataGenerator();

        $user_one = $generator->create_user(["firstname" => "User", "lastname" => "One"]);
        $user_two = $generator->create_user(["firstname" => "User", "lastname" => "Two"]);
        $user_three =  $generator->create_user(["firstname" => "User", "lastname" => "Three"]);

        self::setAdminUser();
        $manager_job_assignment = job_assignment::create_default($user_three->id);
        $temporary_job_assignment = job_assignment::create_default($user_two->id);
        $job_assignment = job_assignment::create_default(
            $user_one->id,
            [
                "tempmanagerjaid" => $temporary_job_assignment->id,
                "tempmanagerexpirydate" => time() + WEEKSECS,
                "managerjaid" => $manager_job_assignment->id,
            ]
        );

        // Reset the user in session.
        self::setUser(null);
        // Create a course and enrol user one to the course.
        $course = $generator->create_course();
        $generator->enrol_user($user_one->id, $course->id);

        $seminar_record = $generator->create_module(
            "facetoface",
            [
                "course" => $course->id,
                "approvaltype" => seminar::APPROVAL_MANAGER,
                "selectjobassignmentonsignup" => true
            ]
        );
        $seminar_generator = generator::instance();
        $event_id = $seminar_generator->add_session(["facetoface" => $seminar_record->id]);

        $signup = signup::create($user_one->id, $event_id);
        $signup->set_jobassignmentid($job_assignment->id);

        self::assertTrue(signup_helper::can_signup($signup));
        $new_signup = signup_helper::signup($signup);
        self::assertTrue($new_signup->exists());

        $managers = signup_helper::find_managers_from_signup($new_signup);
        self::assertCount(2, $managers);

        // The first manager will be the permanent manager.
        $first_manager = reset($managers);
        self::assertIsObject($first_manager);
        self::assertObjectHasAttribute("id", $first_manager);
        self::assertEquals($user_three->id, $first_manager->id);

        $second_manager = end($managers);
        self::assertIsObject($second_manager);
        self::assertObjectHasAttribute("id", $second_manager);
        self::assertEquals($user_two->id, $second_manager->id);
    }
}
