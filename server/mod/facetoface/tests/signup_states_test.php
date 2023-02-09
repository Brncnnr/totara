<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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

use mod_facetoface\{seminar, seminar_event, seminar_session, signup, signup_status};
use mod_facetoface\event\{booking_booked, signup_status_updated};
use mod_facetoface\exception\signup_exception;
use mod_facetoface\signup\condition\{ condition, event_taking_attendance };
use mod_facetoface\signup\restriction\restriction;
use mod_facetoface\signup\state\{not_set, state, booked, event_cancelled, fully_attended, partially_attended, unable_to_attend};
use mod_facetoface\signup\transition;

defined('MOODLE_INTERNAL') || die();

/**
 * A unit test for the signup class and state transitions.
 *
 * Class mod_facetoface_signup_states_testcase
 */
class mod_facetoface_signup_states_testcase extends advanced_testcase {
    protected function setUp(): void {
        $this->setAdminUser();
    }

    /**
     * Walk through the transitions of all states and assert their conditions or restrictions.
     *
     * @param string $prop_name the property name of the transaction class
     * @param string $parent_class
     * @return void
     */
    private function assert_state_transitions(string $prop_name, string $parent_class): void {
        $user = $this->getDataGenerator()->create_user();
        $seminar = new seminar();
        $seminar->save();
        $seminarevent = new seminar_event();
        $seminarevent->set_facetoface($seminar->get_id());
        $seminarevent->save();
        $signup = signup::create($user->id, $seminarevent);

        $done = [];

        foreach (state::get_all_states() as $stateclass) {
            $state = new $stateclass($signup);

            foreach ($state->get_map() as $transition) {
                $reflect = new ReflectionClass($transition);
                $prop = $reflect->getProperty($prop_name);
                $prop->setAccessible(true);

                $classes = $prop->getValue($transition);
                foreach ($classes as $class) {
                    // do not test a class that is already tested
                    if (array_key_exists($class, $done)) {
                        continue;
                    }
                    $done[$class] = true;
                    $obj = new $class($signup);
                    $this->assertInstanceOf($parent_class, $obj);
                }
            }
        }
    }

    /**
     * @return void
     */
    public function test_all_given_conditions_are_classes_of_condition(): void {
        // test only conditions
        $this->assert_state_transitions('conditions', condition::class);
    }

    /**
     * @return void
     */
    public function test_all_given_restrictions_are_classes_of_restriction(): void {
        // test only restrictions
        $this->assert_state_transitions('restrictions', restriction::class);
    }

    /**
     * Test the transition of attendance_state classes.
     */
    public function test_all_attendance_states_and_their_transitions(): void {
        $user = $this->getDataGenerator()->create_user();
        $seminar = new seminar();
        $seminar->save();
        $seminarevent = new seminar_event();
        $seminarevent->set_facetoface($seminar->get_id());
        $seminarevent->save();
        $signup = signup::create($user->id, $seminarevent);

        $attendance_state_classes = mod_facetoface\signup\state\attendance_state::get_all_attendance_states();
        // add some states if not there
        $attendance_state_classes = array_unique(array_merge($attendance_state_classes, [ booked::class, event_cancelled::class ]));

        foreach ($attendance_state_classes as $stateclass) {
            $state = new $stateclass($signup);
            if ($state instanceof event_cancelled) {
                // nothing to test
                continue;
            }
            if ($state instanceof booked) {
                // booked state is slightly different
                // its transition must have all attendance_state classes
                $transitions = $state->get_map();
                // remove booked state
                $remains = array_diff($attendance_state_classes, [ booked::class ]);
                foreach ($transitions as $transition) {
                    $to_class = get_class($transition->get_to());
                    // remove found attendance_state
                    $remains = array_diff($remains, [ $to_class ]);
                }
                $this->assertCount(0, $remains);
            } else {
                // anything else must be attendance_state,
                // whose transition must have all attendance_state classes and booked state, except itself
                $this->assertInstanceOf(mod_facetoface\signup\state\attendance_state::class, $state);
                $transitions = $state->get_map();
                $this->assertCount(count($attendance_state_classes) - 1, $transitions);
                foreach ($transitions as $transition) {
                    $to_class = get_class($transition->get_to());
                    $this->assertContains($to_class, $attendance_state_classes);
                    $this->assertNotEquals($to_class, $stateclass);
                }
            }
        }
    }

    /**
     * Create a seminar event and add a session or two to the event.
     *
     * @param \mod_facetoface\seminar $seminar
     * @param integer $timediff
     * @param integer $duration
     * @param integer $timediff2
     * @param integer $duration2
     * @return \mod_facetoface\seminar_event
     */
    private function create_event_and_sessions(seminar $seminar, int $timediff, int $duration, int $timediff2 = 0, int $duration2 = 0) {
        $seminarevent = new seminar_event();
        $seminarevent->set_facetoface($seminar->get_id());
        $seminarevent->save();

        $timestart = time() + $timediff;
        $sessiondate = new seminar_session();
        $sessiondate->set_sessionid($seminarevent->get_id())
            ->set_timestart($timestart)
            ->set_timefinish($timestart + $duration)
            ->save();

        if ($timediff2 && $duration2) {
            $timestart = time() + $timediff2;
            $sessiondate = new seminar_session();
            $sessiondate->set_sessionid($seminarevent->get_id())
                ->set_timestart($timestart)
                ->set_timefinish($timestart + $duration2)
                ->save();
        }

        return $seminarevent;
    }

    /**
     * Data provider - [ human_readable_text, expect, eventattendance, session_duration, time_difference ]
     *
     * @return array
     */
    public function data_provider_name_expect_eventattendance_duration_timediff() {
        $before_attendance_start = mod_facetoface\signup\condition\event_taking_attendance::UNLOCKED_SECS_PRIOR_TO_START + 300;
        $after_attendance_start = mod_facetoface\signup\condition\event_taking_attendance::UNLOCKED_SECS_PRIOR_TO_START - 300;
        $dataset = [
            [ 'case' => 'Far past', /*               */'expects' => [ true, true, true, true ], /*   */'duration' => HOURSECS, /**/'timediff' => -YEARSECS ],
            [ 'case' => 'Just ended', /*             */'expects' => [ true, true, true, true ], /*   */'duration' => DAYSECS - 10, 'timediff' => -DAYSECS ],
            [ 'case' => 'Just started', /*           */'expects' => [ false, true, true, true ], /*  */'duration' => HOURSECS, /**/'timediff' => -1 ],
            [ 'case' => 'Starts within unlock period', 'expects' => [ false, true, true, true ], /*  */'duration' => HOURSECS, /**/'timediff' => $after_attendance_start ],
            [ 'case' => 'Starts before unlock period', 'expects' => [ false, false, true, false ], /**/'duration' => HOURSECS, /**/'timediff' => $before_attendance_start ],
            [ 'case' => 'Far future', /*             */'expects' => [ false, false, true, false ], /**/'duration' => HOURSECS, /**/'timediff' => YEARSECS ],
        ];
        $data = [];
        foreach ($dataset as $e) {
            // add seminar::EVENT_ATTENDANCE_xxx
            $i = 0;
            foreach (seminar::EVENT_ATTENDANCE_VALID_VALUES as $eventattendance) {
                $data[] = [ $e['case'], $e['expects'][$i], $eventattendance, $e['duration'], $e['timediff'] ];
                $i++;
            }
        }
        return $data;
    }

    /**
     * Test event_taking_attendance::pass.
     *
     * @dataProvider data_provider_name_expect_eventattendance_duration_timediff
     */
    public function test_attendance_states_when_event_attendance_tracking_is_enabled(string $name, bool $expect, int $eventattendance, int $duration, int $timediff) {
        $strings = [
            seminar::EVENT_ATTENDANCE_LAST_SESSION_END => 'LAST/END',
            seminar::EVENT_ATTENDANCE_FIRST_SESSION_START => 'FIRST/START',
            seminar::EVENT_ATTENDANCE_LAST_SESSION_START => 'LAST/START',
            seminar::EVENT_ATTENDANCE_UNRESTRICTED => 'ANY'
        ];

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();

        // Test with one session to see first/start and last/start are the same results.
        $seminar = new seminar();
        $seminar->set_attendancetime($eventattendance)->save();
        $event = $this->create_event_and_sessions($seminar, $timediff, $duration);
        $signup = signup::create($user->id, $event)->save();
        $condition = new event_taking_attendance($signup);
        $this->assertSame($expect, $condition->pass(), $name . ' [' . $strings[$eventattendance] . ']');
    }

    public function test_attendance_states_when_event_attendance_tracking_is_enabled2() {
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();

        // Test with two sessions to see first/start and last/start are the same results.
        $seminar = new seminar();
        $seminar->save();
        $event = $this->create_event_and_sessions($seminar, -DAYSECS * 2, HOURSECS, -HOURSECS, HOURSECS * 2);
        $signup = signup::create($user->id, $event)->save();
        $condition = new event_taking_attendance($signup);

        $seminar->set_attendancetime(seminar::EVENT_ATTENDANCE_LAST_SESSION_END)->save();
        $this->assertFalse($condition->pass(), 'LAST/END');

        $seminar->set_attendancetime(seminar::EVENT_ATTENDANCE_FIRST_SESSION_START)->save();
        $this->assertTrue($condition->pass(), 'FIRST/START');

        $seminar->set_attendancetime(seminar::EVENT_ATTENDANCE_LAST_SESSION_START)->save();
        $this->assertTrue($condition->pass(), 'LAST/START');

        $seminar->set_attendancetime(seminar::EVENT_ATTENDANCE_UNRESTRICTED)->save();
        $this->assertTrue($condition->pass(), 'ANY');
    }

    /**
     * Confirm that not_set state cannot be stored in signup_status.
     */
    public function test_signup_status_not_set_cannot_be_saved() {
        $status = new signup_status();
        $status->set_statuscode(signup\state\not_set::get_code());
        $status->set_signupid(42);

        $this->expectException(mod_facetoface\exception\signup_exception::class);
        $this->expectExceptionMessage('Cannot update status without state set');

        $status->save();
    }

    /**
     * Confirm signup_status cannot be stored if status was never set.
     */
    public function test_signup_status_cannot_be_saved_if_set_statuscode_is_never_called() {
        $status = new signup_status();
        $status->set_signupid(42);

        $this->expectException(mod_facetoface\exception\signup_exception::class);
        $this->expectExceptionMessage('Cannot update status without state set');

        $status->save();
    }

    /**
     * Confirm that not_set state cannot be stored as signup state.
     */
    public function test_signup_cannot_be_updated_to_not_set() {
        $signup = new signup();
        $reflection = new ReflectionMethod($signup, 'update_status');
        $reflection->setAccessible(true);

        $this->expectException(mod_facetoface\exception\signup_exception::class);
        $this->expectExceptionMessage('New booking status cannot be \'not set\'');

        $reflection->invoke($signup, new not_set($signup));
    }

    public function test_signup_status_find_current_returns_null_when_signup_is_missing() {
        $this->assertNull(signup_status::find_current(42));
        $signup = new signup();
        $rp = new ReflectionProperty($signup, 'id');
        $rp->setAccessible(true);
        $rp->setValue($signup, 42);
        $this->assertNull(signup_status::find_current($signup));
    }

    /**
     * Create a user, a course, a seminar, a future seminar event, sign up a user and set the event past.
     * @return signup
     */
    private function make_signup(): signup {
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
        /** @var seminar_session $session */
        $session = $seminarevent->get_sessions()->current();
        $session->set_timestart(time() - WEEKSECS - DAYSECS)->set_timefinish(time() - WEEKSECS)->save();
        return $signup;
    }

    /**
     * @return array of cases that end up with signup_exception
     */
    public function data_signup_switch_state_with_grade_exception(): array {
        return [
            [null, null, [not_set::class], "booking status cannot be 'not set'"],
            [null, null, [booked::class], 'Cannot move from '.booked::class.' to any of requested states'],
            [42, null, [booked::class], 'Cannot move from '.booked::class.' to any of requested states'],
            // switch_state_with_grade() will throw exception if it gets multiple desired states or the desired state differs to the current state.
            [42, ['gradeonly' => true], [unable_to_attend::class], 'gradeonly option is not available for the desired state'],
            [42, ['gradeonly' => true], [booked::class, booked::class], 'gradeonly option is not available for the desired state'],
        ];
    }

    /**
     * Ensure signup::switch_state_with_grade() throws signup_exception.
     * @dataProvider data_signup_switch_state_with_grade_exception
     */
    public function test_signup_switch_state_with_grade_exception(?float $grade, ?array $options, array $newstates, string $message) {
        $signup = $this->make_signup();
        try {
            $signup->switch_state_with_grade($grade, $options, ...$newstates);
            $this->fail('signup_exception expected');
        } catch (signup_exception $ex) {
            $this->assertStringContainsString($message, $ex->getMessage());
        }
    }

    /**
     * Ensure signup::switch_state_with_grade() succeeds with expected outcomes.
     */
    public function test_signup_switch_state_with_grade_success() {
        $signup = $this->make_signup();

        $hit_and_run = function ($callback) use ($signup) {
            $sink = $this->redirectEvents();
            $callback($signup);
            $events = $sink->get_events();
            $sink->close();
            return $events;
        };

        // Attendance taken, sort of.
        $events = $hit_and_run(function (signup $signup) {
            $signup->switch_state_with_grade(null, null, fully_attended::class);
        });
        $this->assertCount(1, $events);
        $this->assertInstanceOf(signup_status_updated::class, $events[0]);
        $this->assertInstanceOf(fully_attended::class, $signup->get_state());
        $this->assertSame(null, signup_status::from_current($signup)->get_grade());

        // Attendance updated, sort of.
        $events = $hit_and_run(function (signup $signup) {
            $signup->switch_state_with_grade(42, null, booked::class);
        });
        // Make sure two events are fired - signup_status_updated from switch_state_with_grade, booking_booked from booked state.
        $this->assertCount(2, $events);
        $this->assertInstanceOf(signup_status_updated::class, $events[0]);
        $this->assertInstanceOf(booking_booked::class, $events[1]);
        $this->assertInstanceOf(booked::class, $signup->get_state());
        $this->assertSame(42., signup_status::from_current($signup)->get_grade());

        // Update only grade with the 'gradeonly' option.
        $events = $hit_and_run(function (signup $signup) {
            $signup->switch_state_with_grade(85, ['gradeonly' => true], booked::class);
        });
        // Make sure booking_booked event is not fired because the state is not changed.
        $this->assertCount(1, $events);
        $this->assertInstanceOf(signup_status_updated::class, $events[0]);
        $this->assertInstanceOf(booked::class, $signup->get_state());
        $this->assertSame(85., signup_status::from_current($signup)->get_grade());
    }

    /**
     * @return array of cases that end up with signup_exception
     */
    public function data_signup_update_status_exception(): array {
        return [
            [not_set::class, null, null, "New booking status cannot be 'not set'"],
            [fully_attended::class, null, ['gradeonly' => true], 'gradeonly option is not available for the desired state'],
        ];
    }

    /**
     * @dataProvider data_signup_update_status_exception
     */
    public function test_signup_update_status_exception(string $stateclass, ?float $grade, ?array $options, string $message) {
        $signup = $this->make_signup();
        $state = new $stateclass($signup);
        $rm = new ReflectionMethod($signup, 'update_status');
        $rm->setAccessible(true);
        try {
            $rm->invoke($signup, $state, 0, 0, $grade, $options);
            $this->fail('signup_exception expected');
        } catch (signup_exception $ex) {
            $this->assertStringContainsString($message, $ex->getMessage());
        }
    }

    /**
     * Ensure signup::update_status() succeeds with expected outcomes but not very obviously.
     */
    public function test_signup_update_status_edge_case() {
        $signup = $this->make_signup();
        $method = new ReflectionMethod($signup, 'update_status');
        $method->setAccessible(true);

        $set_and_fire = function ($callback) use ($signup, $method) {
            $sink = $this->redirectEvents();
            $result = $callback($signup, $method);
            $events = $sink->get_events();
            $sink->close();
            return [$events, $result];
        };

        // Update only grade with the 'gradeonly' option.
        $oldstatus = $signup->get_signup_status();
        [$events, $newstatus] = $set_and_fire(function ($signup, $method) {
            return $method->invoke($signup, new booked($signup), 0, 0, 42, ['gradeonly' => true]);
        });
        // Make sure only signup_status_updated event is fired.
        $this->assertCount(1, $events);
        $this->assertInstanceOf(signup_status_updated::class, $events[0]);
        $this->assertInstanceOf(booked::class, $signup->get_state());
        $this->assertSame(42., signup_status::from_current($signup)->get_grade());
        // Make sure a new record is added.
        $this->assertNotEquals($oldstatus->get_id(), $newstatus->get_id());
        $this->assertEquals($newstatus->get_id(), signup_status::from_current($signup)->get_id());

        // Update nothing with the 'gradeonly' option.
        $oldstatus = $signup->get_signup_status();
        [$events, $newstatus] = $set_and_fire(function ($signup, $method) {
            return $method->invoke($signup, new booked($signup), 0, 0, 42, ['gradeonly' => true]);
        });
        // Make sure no events are fired.
        $this->assertCount(0, $events);
        $this->assertInstanceOf(booked::class, $signup->get_state());
        $this->assertSame(42., signup_status::from_current($signup)->get_grade());
        // Make sure a new record is NOT added.
        $this->assertEquals($oldstatus->get_id(), $newstatus->get_id());
        $this->assertEquals($oldstatus->get_id(), signup_status::from_current($signup)->get_id());
    }

    public function test_signup_after_declined() {
        global $CFG, $DB, $PAGE, $OUTPUT, $USER;
        require_once($CFG->dirroot . '/mod/facetoface/lib.php');
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $seminar = new seminar($gen->get_plugin_generator('mod_facetoface')->create_instance(['course' => $course->id])->id);
        $seminarevent = $this->create_event_and_sessions($seminar, DAYSECS, DAYSECS);
        $user = $gen->create_user(['firstname' => 'One', 'lastname' => 'Uno']);
        $this->setUser($user);
        $gen->enrol_user($user->id, $course->id);
        $signup = signup::create($user->id, $seminarevent)->set_managerid(42)->set_jobassignmentid(64)->set_bookedby(77)->save();
        mod_facetoface\signup_status::create($signup, new mod_facetoface\signup\state\declined($signup))->save();

        $signup = $DB->get_record('facetoface_signups', ['userid' => $user->id, 'sessionid' => $seminarevent->get_id()], '*', MUST_EXIST);
        $this->assertEquals(42, $signup->managerid);
        $this->assertEquals(64, $signup->jobassignmentid);
        $this->assertEquals(77, $signup->bookedby);
        $this->assertInstanceOf(mod_facetoface\signup\state\declined::class, signup::create($user->id, $seminarevent)->get_state());

        define('FACETOFACE_EVENTINFO_INTERNAL', 9);
        $s = $seminarevent->get_id();
        $cancelsignup = false;
        $action = 'signup';
        $cm = $seminar->get_coursemodule();
        $context = context_module::instance($cm->id);
        $pageurl = new moodle_url('');
        mod_facetoface\form\signup::mock_submit([
            's' => $seminarevent->get_id(),
            'action' => 'signup',
            'notificationtype' => MDL_F2F_NONE,
            'managerid' => '',
            'submitbutton' => 'Request approval',
        ]);
        $signup = signup::create($user->id, $seminarevent, MDL_F2F_NONE);
        try {
            require_once($CFG->dirroot . '/mod/facetoface/signup.php');
        } catch (moodle_exception $ex) {
            $this->assertStringContainsString('Unsupported redirect detected', $ex->getMessage());
        }

        $signup = $DB->get_record('facetoface_signups', ['userid' => $user->id, 'sessionid' => $seminarevent->get_id()], '*', MUST_EXIST);
        $this->assertNull($signup->managerid);
        $this->assertNull($signup->jobassignmentid);
        $this->assertNull($signup->bookedby);
        $this->assertInstanceOf(booked::class, signup::create($user->id, $seminarevent)->get_state());
    }
}
