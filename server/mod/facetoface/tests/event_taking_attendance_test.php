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

use mod_facetoface\{seminar_event, seminar, seminar_session, signup, signup_helper, signup_status};
use mod_facetoface\signup\state\{booked, fully_attended, partially_attended, unable_to_attend, no_show};

class mod_facetoface_event_taking_attendance_testcase extends advanced_testcase {
    /**
     * Create an event with two sessions, where first session is going to be in the pass and the
     * second session is going to the future.
     * @return seminar_event
     */
    private function create_seminar_event(): seminar_event {
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();

        $f2fgen = $gen->get_plugin_generator('mod_facetoface');
        $f2f = $f2fgen->create_instance(['course' => $course->id]);

        $s = new seminar($f2f->id);
        $s->set_sessionattendance(0);
        $s->set_attendancetime(seminar::ATTENDANCE_TIME_ANY);
        $s->save();

        $e = new seminar_event();
        $e->set_facetoface($s->get_id());
        $e->save();

        $time = time();
        $times = [
            ['start' => $time + 3600, 'finish' => $time + (3600 * 2)],
            ['start' => $time +  (3600 * 4), 'finish' => $time + (3600 * 5)],
        ];

        foreach ($times as $t) {
            $ss = new seminar_session();
            $ss->set_timefinish($t['finish']);
            $ss->set_timestart($t['start']);
            $ss->set_sessionid($e->get_id());
            $ss->save();
        }

        return $e;
    }

    /**
     * Test suite to ensure that switching between booked state to attendance_state of a user within
     * event level (which has more than 1 session) has no problem at all.
     * @return void
     */
    public function test_process_attendance_on_event_with_multiplesessions(): void {
        $event = $this->create_seminar_event();

        $gen = $this->getDataGenerator();
        $data = [];

        for ($i = 0; $i < 5; $i++) {
            $user = $gen->create_user();
            $gen->enrol_user($user->id, $event->get_seminar()->get_course());

            $signup = signup::create($user->id, $event);
            $signup->save();
            $signup->switch_state(booked::class);

            // Start preparing the attendance test for taking attendance in event level.
            $data[$signup->get_id()] = fully_attended::get_code();
        }

        // Start marking the first session to be a history session here.
        $time = time();
        $session = $event->get_sessions()->get_first();
        $session->set_timestart($time - (3600 * 2));
        $session->set_timefinish($time - 3600);
        $session->save();

        signup_helper::process_attendance($event, $data);

        foreach ($data as $submissionid => $code) {
            $signup = new signup($submissionid);
            $state = $signup->get_state();
            $this->assertEquals($state::get_code(), $code);
        }
    }

    /**
     * Convert string grade to float grade using grade_floatval().
     * grade_get_grades() returns decimal number as string when GRADE_TYPE_VALUE.
     *
     * @param mixed $grade
     * @return float|null
     */
    private static function fixup_grade($grade) : ?float {
        if (is_null($grade) || is_numeric($grade)) {
            return grade_floatval($grade);
        }
        throw new Exception("'{$grade}' is neither numeric string, float nor null");
    }

    /**
     * Data provider - [ grading_method, states, expected_state, grades, expected_grade ]
     *
     * @return array
     */
    public function data_provider_grading_method() {
        /** @var float[] */
        $grades = [ 4., 2., 8., 5., 7. ];
        /** @var string[] */
        $states = [ partially_attended::class, fully_attended::class, no_show::class, partially_attended::class, unable_to_attend::class ];
        $data = [];
        $data[] = [ seminar::GRADING_METHOD_GRADEHIGHEST, $states, $states[1], $grades, $grades[2] ];
        $data[] = [ seminar::GRADING_METHOD_GRADELOWEST, $states, $states[2], $grades, $grades[1] ];
        $data[] = [ seminar::GRADING_METHOD_EVENTFIRST, $states, $states[0], $grades, $grades[0] ];
        $data[] = [ seminar::GRADING_METHOD_EVENTLAST, $states, $states[4], $grades, $grades[4] ];
        // fully null
        $data[] = [ seminar::GRADING_METHOD_GRADEHIGHEST, null, null, [ null, null, null ], null ];
        $data[] = [ seminar::GRADING_METHOD_GRADELOWEST, null, null, [ null, null, null ], null ];
        $data[] = [ seminar::GRADING_METHOD_EVENTFIRST, null, null, [ null, null, null ], null ];
        $data[] = [ seminar::GRADING_METHOD_EVENTLAST, null, null, [ null, null, null ], null ];
        // partially null
        $data[] = [ seminar::GRADING_METHOD_GRADEHIGHEST, null, null, [ null, 50., 0., 100., null ], 100. ];
        $data[] = [ seminar::GRADING_METHOD_GRADELOWEST, null, null, [ null, 50., 0., 100., null ], 0. ];
        $data[] = [ seminar::GRADING_METHOD_EVENTFIRST, null, null, [ null, 50., 0., 100., null ], 50. ];
        $data[] = [ seminar::GRADING_METHOD_EVENTLAST, null, null, [ null, 50., 0., 100., null ], 100. ];
        return $data;
    }

    /**
     * @param int $grading_method
     * @param string[]|null $states
     * @param string|null $expected_state
     * @param (float|null)[] $grades
     * @param float|null $expected_grade
     * @dataProvider data_provider_grading_method
     */
    public function test_process_attendance_with_grading_method($grading_method, $states, $expected_state, $grades, $expected_grade) {

        if ($states !== null) {
            $this->assertCount(count($states), $grades);
        }

        $gen = $this->getDataGenerator();
        $course = $gen->create_course();

        $f2fgen = $gen->get_plugin_generator('mod_facetoface');
        $f2f = $f2fgen->create_instance(['course' => $course->id]);

        $seminar = new seminar($f2f->id);
        $seminar
            ->set_sessionattendance(0)
            ->set_multisignupfully(true)
            ->set_multisignuppartly(true)
            ->set_multisignupnoshow(true)
            ->set_multisignupunableto(true)
            ->set_multiplesessions(1)
            ->set_multisignupmaximum(count($grades))
            ->set_eventgradingmethod($grading_method)
            ->save();

        $user = $gen->create_user(['firstname' => 'John', 'lastname' => 'Doe']);
        $gen->enrol_user($user->id, $course->id);

        $time = time() + DAYSECS;
        $events = [];
        $signups = [];

        for ($i = 0; $i < count($grades); $i++, $time += DAYSECS) {
            $event = new seminar_event();
            $event->set_facetoface($seminar->get_id());
            $event->save();
            $events[] = $event;

            $session = new seminar_session();
            $session
                ->set_timestart($time)
                ->set_timefinish($time + HOURSECS)
                ->set_sessionid($event->get_id())
                ->save();

            $signup = signup::create($user->id, $event);
            $signup->save();
            $signup->switch_state(booked::class);
            $signups[] = $signup;

            // move an event to the past to take attendance
            $session
                ->set_timestart($time - YEARSECS)
                ->set_timefinish($time - YEARSECS + HOURSECS)
                ->save();

            // TEST 1. When manual grading is off, use default grade based on attendance status
            $result = signup_helper::process_attendance($event, [ $signup->get_id() => partially_attended::get_code() ]);
            $this->assertTrue($result);
            $status = signup_status::find_current($signup->get_id());
            $this->assertSame((float)partially_attended::get_grade(), $status->get_grade());

            // TEST 2. When manual grading is off, signup_helper::process_attendance() ignores the argument $grades
            $result = signup_helper::process_attendance($event, [ $signup->get_id() => unable_to_attend::get_code() ], [ $signup->get_id() => 33 ]);
            $this->assertTrue($result);
            $status = signup_status::find_current($signup->get_id());
            $this->assertSame((float)unable_to_attend::get_grade(), $status->get_grade());

            if ($states !== null) {
                // TEST 3. Set final state
                $result = signup_helper::process_attendance($event, [ $signup->get_id() => $states[$i]::get_code() ]);
                $this->assertTrue($result);
                $status = signup_status::find_current($signup->get_id());
                $this->assertSame((float)$states[$i]::get_grade(), $status->get_grade());
            }

            unset($event, $session, $signup, $result, $status);
        }

        if ($states !== null) {
            // TEST 4. Make sure that the final grade is calculated based on the given grading method
            $grade_grades = grade_get_grades($course->id, 'mod', 'facetoface', $seminar->get_id(), $user->id);
            $this->assertCount(1, $grade_grades->items);
            $this->assertArrayHasKey($user->id, $grade_grades->items[0]->grades);
            $user_grade = $grade_grades->items[0]->grades[$user->id];
            $this->assertSame((float)$expected_state::get_grade(), self::fixup_grade($user_grade->grade));
        }

        // turn on manual grading
        $seminar->set_eventgradingmanual(1)->save();

        for ($i = 0; $i < count($signups); $i++) {
            $event = $events[$i];
            $signup = $signups[$i];

            // TEST 5a. When manual grading is on, signup_helper::process_attendance() takes the argument $grades
            // make sure that the event grade becomes null if $grades is not passed
            $result = signup_helper::process_attendance($event, [ $signup->get_id() => partially_attended::get_code() ]);
            $this->assertTrue($result);
            $status = signup_status::find_current($signup->get_id());
            $this->assertSame(null, $status->get_grade());

            // TEST 5b. When manual grading is on, signup_helper::process_attendance() takes the argument $grades
            // make sure that the event grade becomes null if null is passed as grade
            $result = signup_helper::process_attendance($event, [ $signup->get_id() => partially_attended::get_code() ], [ $signup->get_id() => null ]);
            $this->assertTrue($result);
            $status = signup_status::find_current($signup->get_id());
            $this->assertSame(null, $status->get_grade());

            // TEST 6. When manual grading is on, signup_helper::process_attendance() takes the argument $grades
            // make sure that the event grade becomes the given grade
            $result = signup_helper::process_attendance($event, [ $signup->get_id() => fully_attended::get_code() ], [ $signup->get_id() => $grades[$i] ]);
            $this->assertTrue($result);
            $status = signup_status::find_current($signup->get_id());
            $this->assertSame($grades[$i], $status->get_grade());

            unset($event, $signup, $result, $status);
        }

        // TEST 7. Make sure that the final grade is calculated based on the given grading method
        $grade_grades = grade_get_grades($course->id, 'mod', 'facetoface', $seminar->get_id(), $user->id);
        $this->assertCount(1, $grade_grades->items);
        $this->assertArrayHasKey($user->id, $grade_grades->items[0]->grades);
        $user_grade = $grade_grades->items[0]->grades[$user->id];
        $this->assertSame($expected_grade, self::fixup_grade($user_grade->grade));

        // TEST 8. Set event grade to 0 and make sure 0 is the new event grade
        for ($i = 0; $i < count($signups); $i++) {
            $result = signup_helper::process_attendance($events[$i], [ $signups[$i]->get_id() => fully_attended::get_code() ], [ $signups[$i]->get_id() => 0 ]);
            $this->assertTrue($result);
            $status = signup_status::find_current($signups[$i]->get_id());
            $this->assertSame(0., $status->get_grade());

            unset($result, $status);
        }

        // TEST 9. Make sure that the final grade becomes 0
        $grade_grades = grade_get_grades($course->id, 'mod', 'facetoface', $seminar->get_id(), $user->id);
        $this->assertCount(1, $grade_grades->items);
        $this->assertArrayHasKey($user->id, $grade_grades->items[0]->grades);
        $user_grade = $grade_grades->items[0]->grades[$user->id];
        $this->assertSame(0., self::fixup_grade($user_grade->grade));

        // TEST 10. Nullify event grade and make sure null is the new event grade
        for ($i = 0; $i < count($signups); $i++) {
            $result = signup_helper::process_attendance($events[$i], [ $signups[$i]->get_id() => fully_attended::get_code() ]);
            $this->assertTrue($result);
            $status = signup_status::find_current($signups[$i]->get_id());
            $this->assertSame(null, $status->get_grade());

            unset($result, $status);
        }

        // TEST 11. Make sure that the final grade becomes null
        $grade_grades = grade_get_grades($course->id, 'mod', 'facetoface', $seminar->get_id(), $user->id);
        $this->assertCount(1, $grade_grades->items);
        $this->assertArrayHasKey($user->id, $grade_grades->items[0]->grades);
        $user_grade = $grade_grades->items[0]->grades[$user->id];
        $this->assertSame(null, self::fixup_grade($user_grade->grade));
    }
}
