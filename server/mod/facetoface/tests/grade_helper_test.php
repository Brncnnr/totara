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

use mod_facetoface\{grade_helper, seminar, seminar_event, signup, signup_helper};
use mod_facetoface\signup\state\{booked, fully_attended, partially_attended, no_show, unable_to_attend};

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/facetoface/lib.php');
require_once($CFG->dirroot . '/lib/gradelib.php');

class mod_facetoface_grade_helper_testcase extends advanced_testcase {
    /** @var \core\testing\generator */
    private $gen;

    /** @var \mod_facetoface\testing\generator */
    private $f2fgen;

    /** @var integer */
    private $course = 0;

    /** @var seminar */
    private $seminar;

    /** @var grade_item */
    private $gradeitem;

    public function setUp(): void {
        parent::setUp();
        $this->gen = $this->getDataGenerator();
        $this->f2fgen = $this->gen->get_plugin_generator('mod_facetoface');
        $this->course = $this->gen->create_course()->id;
        $this->seminar = new seminar($this->f2fgen->create_instance([
            'name' => 'my seminar',
            'course' => $this->course
        ])->id);
        $this->seminar
            ->set_attendancetime(seminar::EVENT_ATTENDANCE_UNRESTRICTED)
            ->set_eventgradingmanual((int)false)
            ->set_multiplesessions((int)true)
            ->set_multisignupmaximum(99)
            ->set_multisignupfully(true)
            ->set_multisignuppartly(true)
            ->set_multisignupunableto(true)
            ->set_multisignupnoshow(true)
            ->save();
        $this->gradeitem = new grade_item(['itemtype' => 'mod', 'itemmodule' => 'facetoface', 'iteminstance' => $this->seminar->get_id(), 'courseid' => $this->course]);
    }

    public function tearDown(): void {
        $this->gradeitem = null;
        $this->seminar = null;
        $this->course = 0;
        $this->f2fgen = null;
        $this->gen = null;

        parent::tearDown();
    }

    /**
     * @return integer[]
     */
    private function create_and_enrol_students(int $num_students): array {
        global $DB;
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $students = [];
        while ($num_students--) {
            $student = $this->gen->create_user()->id;
            $this->gen->enrol_user($student, $this->course, $studentrole->id);
            $students[] = $student;
        }
        return $students;
    }

    /**
     * @return array of [ studentids, [ [ seminarevent, signups ], ... ]
     */
    private function prepare_seminar_with_attendees(): array {
        $students = $this->create_and_enrol_students(5);
        $f2fid = $this->seminar->get_id();
        $time = time();

        // event #0: sign up student0, student1, student2
        $time += WEEKSECS;
        $seminarevent = new seminar_event($this->f2fgen->add_session(['facetoface' => $f2fid, 'sessiondates' => [$time]]));
        $signups = [
            signup_helper::signup(signup::create($students[0], $seminarevent)->set_ignoreconflicts(true)),
            signup_helper::signup(signup::create($students[1], $seminarevent)->set_ignoreconflicts(true)),
            signup_helper::signup(signup::create($students[2], $seminarevent)->set_ignoreconflicts(true)),
            null,
            null,
        ];
        $processed = signup_helper::process_attendance($seminarevent,
            [
                $signups[1]->get_id() => fully_attended::get_code(),
                $signups[2]->get_id() => partially_attended::get_code(),
            ]
        );
        $this->assertTrue($processed);
        $data[] = (object)['event' => $seminarevent, 'signups' => $signups];

        // event #1: sign up student1, student3
        $time += WEEKSECS;
        $seminarevent = new seminar_event($this->f2fgen->add_session(['facetoface' => $f2fid, 'sessiondates' => [$time]]));
        $signups = [
            null,
            signup_helper::signup(signup::create($students[1], $seminarevent)->set_ignoreconflicts(true)),
            null,
            signup_helper::signup(signup::create($students[3], $seminarevent)->set_ignoreconflicts(true)),
            null,
        ];
        $processed = signup_helper::process_attendance($seminarevent,
            [
                $signups[1]->get_id() => unable_to_attend::get_code(),
                $signups[3]->get_id() => partially_attended::get_code(),
            ]
        );
        $this->assertTrue($processed);
        $data[] = (object)['event' => $seminarevent, 'signups' => $signups];

        // Enable event manual grading from now on.
        $this->seminar->set_eventgradingmanual((int)true)->save();

        // event #2: sign up student3
        $time += WEEKSECS;
        $seminarevent = new seminar_event($this->f2fgen->add_session(['facetoface' => $f2fid, 'sessiondates' => [$time]]));
        $signups = [
            null,
            null,
            null,
            signup_helper::signup(signup::create($students[3], $seminarevent)->set_ignoreconflicts(true)),
            null,
        ];
        $processed = signup_helper::process_attendance($seminarevent,
            [
                $signups[3]->get_id() => partially_attended::get_code(),
            ],
            [
                $signups[3]->get_id() => 70.,
            ]
        );
        $this->assertTrue($processed);
        $data[] = (object)['event' => $seminarevent, 'signups' => $signups];

        // event #3: sign up student3
        $time += WEEKSECS;
        $seminarevent = new seminar_event($this->f2fgen->add_session(['facetoface' => $f2fid, 'sessiondates' => [$time]]));
        $signups = [
            null,
            null,
            null,
            signup_helper::signup(signup::create($students[3], $seminarevent)->set_ignoreconflicts(true)),
            null,
        ];
        $processed = signup_helper::process_attendance($seminarevent,
            [
                $signups[3]->get_id() => fully_attended::get_code(),
            ],
            [
                $signups[3]->get_id() => 20.,
            ]
        );
        $this->assertTrue($processed);
        $data[] = (object)['event' => $seminarevent, 'signups' => $signups];

        // event #4: sign up student3
        $time += WEEKSECS;
        $seminarevent = new seminar_event($this->f2fgen->add_session(['facetoface' => $f2fid, 'sessiondates' => [$time]]));
        $signups = [
            null,
            null,
            null,
            signup_helper::signup(signup::create($students[3], $seminarevent)->set_ignoreconflicts(true)),
            null,
        ];
        $processed = signup_helper::process_attendance($seminarevent,
            [
                $signups[3]->get_id() => unable_to_attend::get_code(),
            ],
            [
                $signups[3]->get_id() => 30.,
            ]
        );
        $this->assertTrue($processed);
        $data[] = (object)['event' => $seminarevent, 'signups' => $signups];

        return [$students, $data];
    }

    public function prepare_yet_another_seminar_with_attendees(array $attendees): seminar {
        $seminar = new seminar($this->f2fgen->create_instance([
            'name' => 'test seminar',
            'course' => $this->course,
            'attendancetime' => seminar::EVENT_ATTENDANCE_UNRESTRICTED
        ])->id);

        $seminarevent = new seminar_event($this->f2fgen->add_session(['facetoface' => $seminar->get_id(), 'sessiondates' => [time() + YEARSECS]]));
        $attendance = [];
        foreach ($attendees as $student => $state) {
            $signup = signup_helper::signup(signup::create($student, $seminarevent));
            $attendance[$signup->get_id()] = $state;
        }
        $processed = signup_helper::process_attendance($seminarevent, $attendance);
        $this->assertTrue($processed);

        return $seminar;
    }

    /**
     * Ensure grade_helper::get_final_grades() returns false if no one sign ups a seminar.
     */
    public function test_get_final_grades_with_no_signups() {
        $this->create_and_enrol_students(5);
        $grades = grade_helper::get_final_grades($this->seminar, 0);
        $this->assertFalse($grades);
    }

    /**
     * Ensure grade_helper::get_final_grades() returns false if no event grades are set.
     */
    public function test_get_final_grades_with_no_grades() {
        $students = $this->create_and_enrol_students(5);
        $seminarevent = new seminar_event($this->f2fgen->add_session(['facetoface' => $this->seminar->get_id(), 'sessiondates' => [time() + DAYSECS]]));
        $attendance = [];
        foreach ($students as $student) {
            $signup = signup_helper::signup(signup::create($student, $seminarevent));
            $attendance[$signup->get_id()] = fully_attended::get_code();
        }
        // Call signup_helper::process_attendance() without $grades for the seminar with eventgradingmanual enabled
        // in order to simulate 'save attendance' with empty event grades.
        $this->seminar->set_eventgradingmanual((int)true)->save();
        $processed = signup_helper::process_attendance($seminarevent, $attendance);
        $this->assertTrue($processed);
        $grades = grade_helper::get_final_grades($this->seminar, 0);
        $this->assertFalse($grades);
    }

    /**
     * Ensure grade_helper::get_final_grades() excludes cancellations.
     */
    public function test_get_final_grades_with_grades_but_cancelled() {
        $students = $this->create_and_enrol_students(2);
        $seminarevent = new seminar_event($this->f2fgen->add_session(['facetoface' => $this->seminar->get_id(), 'sessiondates' => [time() + DAYSECS]]));
        $signups = [];
        $attendance = [];
        foreach ($students as $student) {
            $signup = signup_helper::signup(signup::create($student, $seminarevent));
            $signups[] = $signup;
            $attendance[$signup->get_id()] = fully_attended::get_code();
        }
        // Take attendance with auto event grading mode.
        $processed = signup_helper::process_attendance($seminarevent, $attendance);
        $this->assertTrue($processed);
        $grades = grade_helper::get_final_grades($this->seminar, 0);
        $this->assertNotFalse($grades);
        $this->assertSame(100., $grades[$students[0]]->rawgrade);
        $this->assertSame(100., $grades[$students[1]]->rawgrade);

        // To cancel a user, first set his (her?) attendance state back to 'not set'.
        $this->seminar->set_eventgradingmanual((int)true)->save();
        $attendance = [$signups[0]->get_id() => booked::get_code()];
        $grades = [$signups[0]->get_id() => 42.];
        $processed = signup_helper::process_attendance($seminarevent, $attendance, $grades);
        $this->assertTrue($processed);
        $grades = grade_helper::get_final_grades($this->seminar, 0);
        $this->assertNotFalse($grades);
        $this->assertSame(42., $grades[$students[0]]->rawgrade);
        // Now ready to cancel.
        signup_helper::user_cancel($signups[0], 'Duh');

        // Make sure student #0 is now gone, but student #1 is still there.
        $grades = grade_helper::get_final_grades($this->seminar, 0);
        $this->assertNotFalse($grades);
        $this->assertArrayNotHasKey($students[0], $grades);
        $this->assertSame(100., $grades[$students[1]]->rawgrade);

        // Cancel event.
        $this->assertTrue($seminarevent->cancel());
        // Make sure both students are gone.
        $grades = grade_helper::get_final_grades($this->seminar, 0);
        $this->assertFalse($grades);
    }

    public function data_get_final_grades_with_grades(): array {
        return [
            [ seminar::GRADING_METHOD_GRADEHIGHEST, [ null, 100., 50.0, 70.0, null ] ],
            [ seminar::GRADING_METHOD_GRADELOWEST, [ null, 0.00, 50.0, 20.0, null ] ],
            [ seminar::GRADING_METHOD_EVENTFIRST, [ null, 100., 50.0, 50.0, null ] ],
            [ seminar::GRADING_METHOD_EVENTLAST, [ null, 0.00, 50.0, 30.0, null ] ],
        ];
    }

    /**
     * Ensure grade_helper::get_final_grades() returns the correct final grade of each user.
     * @dataProvider data_get_final_grades_with_grades
     */
    public function test_get_final_grades_with_live_grades(int $method, array $expections) {
        [$students, $data] = $this->prepare_seminar_with_attendees();

        // To make sure grade_helper::get_final_grades() only looks at $this->seminar,
        // create another seminar and take attendance.
        $this->prepare_yet_another_seminar_with_attendees([
            $students[0] => fully_attended::get_code(),
            $students[1] => partially_attended::get_code(),
            $students[2] => fully_attended::get_code(),
            $students[3] => fully_attended::get_code(),
            $students[4] => fully_attended::get_code(),
        ]);

        // No need to save() here.
        $this->seminar->set_eventgradingmethod($method);

        $cm = $this->seminar->get_coursemodule();
        $facetoface = $this->seminar->get_properties();
        $facetoface->cmidnumber = $cm->idnumber;
        $facetoface->modname = $cm->modname;

        // Get all users' grades with seminar instance.
        $grades = grade_helper::get_final_grades($this->seminar, 0);
        $this->assertNotFalse($grades);
        $this->assertArrayNotHasKey($students[0], $grades);
        $this->assertSame($expections[1], $grades[$students[1]]->rawgrade);
        $this->assertSame($expections[2], $grades[$students[2]]->rawgrade);
        $this->assertSame($expections[3], $grades[$students[3]]->rawgrade);
        $this->assertArrayNotHasKey($students[4], $grades);

        // Get all users' grades with facetoface instance.
        $grades = grade_helper::get_final_grades($facetoface, 0);
        $this->assertNotFalse($grades);
        $this->assertArrayNotHasKey($students[0], $grades);
        $this->assertSame($expections[1], $grades[$students[1]]->rawgrade);
        $this->assertSame($expections[2], $grades[$students[2]]->rawgrade);
        $this->assertSame($expections[3], $grades[$students[3]]->rawgrade);
        $this->assertArrayNotHasKey($students[4], $grades);

        // Get a user's grade individually with seminar instance.
        $grades = grade_helper::get_final_grades($this->seminar, $students[0]);
        $this->assertFalse($grades);

        $grades = grade_helper::get_final_grades($this->seminar, $students[1]);
        $this->assertNotFalse($grades);
        $this->assertSame($expections[1], $grades[$students[1]]->rawgrade);

        $grades = grade_helper::get_final_grades($this->seminar, $students[2]);
        $this->assertNotFalse($grades);
        $this->assertSame($expections[2], $grades[$students[2]]->rawgrade);

        $grades = grade_helper::get_final_grades($this->seminar, $students[3]);
        $this->assertNotFalse($grades);
        $this->assertSame($expections[3], $grades[$students[3]]->rawgrade);

        $grades = grade_helper::get_final_grades($this->seminar, $students[4]);
        $this->assertFalse($grades);

        // Get a user's grade individually with facetoface instance.
        $grades = grade_helper::get_final_grades($facetoface, $students[0]);
        $this->assertFalse($grades);

        $grades = grade_helper::get_final_grades($facetoface, $students[1]);
        $this->assertNotFalse($grades);
        $this->assertSame($expections[1], $grades[$students[1]]->rawgrade);

        $grades = grade_helper::get_final_grades($facetoface, $students[2]);
        $this->assertNotFalse($grades);
        $this->assertSame($expections[2], $grades[$students[2]]->rawgrade);

        $grades = grade_helper::get_final_grades($facetoface, $students[3]);
        $this->assertNotFalse($grades);
        $this->assertSame($expections[3], $grades[$students[3]]->rawgrade);

        $grades = grade_helper::get_final_grades($facetoface, $students[4]);
        $this->assertFalse($grades);
    }

    /**
     * Test grade_helper::get_final_grades() with grade_helper::FORMAT_xxx.
     */
    public function test_get_final_grades_with_format() {
        $this->seminar->set_eventgradingmanual(seminar::GRADING_METHOD_GRADEHIGHEST);
        $students = $this->create_and_enrol_students(2);

        $sessiondates = [
            (object)[
                'timestart' => time()  + 11112,
                'timefinish' => time() + 22224,
                'sessiontimezone' => 'Pacific/Auckland'
            ],
            (object)[
                'timestart' => time()  + 271828,
                'timefinish' => time() + 314159,
                'sessiontimezone' => 'Pacific/Auckland'
            ]
        ];
        $seminarevents = [
            new seminar_event($this->f2fgen->add_session(['facetoface' => $this->seminar->get_id(), 'sessiondates' => [$sessiondates[0]]])),
            new seminar_event($this->f2fgen->add_session(['facetoface' => $this->seminar->get_id(), 'sessiondates' => [$sessiondates[1]]]))
        ];
        $signups = [
            signup_helper::signup(signup::create($students[0], $seminarevents[0])),
            signup_helper::signup(signup::create($students[1], $seminarevents[0]))
        ];
        $attendance = [
            $signups[0]->get_id() => fully_attended::get_code(),
            $signups[1]->get_id() => no_show::get_code()
        ];
        $processed = signup_helper::process_attendance($seminarevents[0], $attendance);
        $this->assertTrue($processed);
        $signups = [
            signup_helper::signup(signup::create($students[0], $seminarevents[1])),
            signup_helper::signup(signup::create($students[1], $seminarevents[1]))
        ];
        $attendance = [
            $signups[0]->get_id() => unable_to_attend::get_code(),
            $signups[1]->get_id() => partially_attended::get_code()
        ];
        $processed = signup_helper::process_attendance($seminarevents[1], $attendance);
        $this->assertTrue($processed);

        $grades = grade_helper::get_final_grades($this->seminar, 0, grade_helper::FORMAT_GRADELIB);
        $this->assertNotFalse($grades);
        $expected = (object)['id' => $students[0], 'userid' => $students[0], 'rawgrade' => 100.];
        $this->assertEquals($expected, $grades[$students[0]]);
        $expected = (object)['id' => $students[1], 'userid' => $students[1], 'rawgrade' => 50.0];
        $this->assertEquals($expected, $grades[$students[1]]);

        $grades = grade_helper::get_final_grades($this->seminar, 0, grade_helper::FORMAT_FACETOFACE);
        $this->assertNotFalse($grades);
        $expected = (object)['userid' => $students[0], 'rawgrade' => 100., 'timecompleted' => $sessiondates[0]->timefinish];
        $this->assertEquals($expected, $grades[$students[0]]);
        $expected = (object)['userid' => $students[1], 'rawgrade' => 50.0, 'timecompleted' => $sessiondates[1]->timefinish];
        $this->assertEquals($expected, $grades[$students[1]]);
    }
}
