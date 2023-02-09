<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Unit tests for (some of) mod/assign/lib.php.
 *
 * @package    mod_assign
 * @category   phpunit
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assign/lib.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once($CFG->dirroot . '/mod/assign/tests/generator.php');

/**
 * Unit tests for (some of) mod/assign/lib.php.
 *
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class mod_assign_lib_testcase extends advanced_testcase {

    // Use the generator helper.
    use mod_assign_test_generator;

    public function test_assign_print_overview() {
        global $DB;


        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setAdminUser();

        // Assignment with default values.
        $firstassign = $this->create_instance($course, ['name' => 'First Assignment']);

        // Assignment with submissions.
        $secondassign = $this->create_instance($course, [
                'name' => 'Assignment with submissions',
                'duedate' => time(),
                'attemptreopenmethod' => ASSIGN_ATTEMPT_REOPEN_METHOD_MANUAL,
                'maxattempts' => 3,
                'submissiondrafts' => 1,
                'assignsubmission_onlinetext_enabled' => 1,
            ]);
        $this->add_submission($student, $secondassign);
        $this->submit_for_grading($student, $secondassign);
        $this->mark_submission($teacher, $secondassign, $student, 50.0);

        // Past assignments should not show up.
        $pastassign = $this->create_instance($course, [
                'name' => 'Past Assignment',
                'duedate' => time() - DAYSECS - 1,
                'cutoffdate' => time() - DAYSECS,
                'nosubmissions' => 0,
                'assignsubmission_onlinetext_enabled' => 1,
            ]);

        // Open assignments should show up only if relevant.
        $openassign = $this->create_instance($course, [
                'name' => 'Open Assignment',
                'duedate' => time(),
                'cutoffdate' => time() + DAYSECS,
                'nosubmissions' => 0,
                'assignsubmission_onlinetext_enabled' => 1,
            ]);
        $pastsubmission = $pastassign->get_user_submission($student->id, true);
        $opensubmission = $openassign->get_user_submission($student->id, true);

        // Check the overview as the different users.
        // For students , open assignments should show only when there are no valid submissions.
        $this->setUser($student);
        $overview = array();
        $courses = $DB->get_records('course', array('id' => $course->id));
        assign_print_overview($courses, $overview);
        $this->assertEquals(1, count($overview));
        $this->assertMatchesRegularExpression('/.*Open Assignment.*/', $overview[$course->id]['assign']); // No valid submission.
        $this->assertDoesNotMatchRegularExpression('/.*First Assignment.*/', $overview[$course->id]['assign']); // Has valid submission.

        // And now submit the submission.
        $opensubmission->status = ASSIGN_SUBMISSION_STATUS_SUBMITTED;
        $openassign->testable_update_submission($opensubmission, $student->id, true, false);

        $overview = array();
        assign_print_overview($courses, $overview);
        $this->assertEquals(0, count($overview));

        $this->setUser($teacher);
        $overview = array();
        assign_print_overview($courses, $overview);
        $this->assertEquals(1, count($overview));
        // Submissions without a grade.
        $this->assertMatchesRegularExpression('/.*Open Assignment.*/', $overview[$course->id]['assign']);
        $this->assertDoesNotMatchRegularExpression('/.*Assignment with submissions.*/', $overview[$course->id]['assign']);

        $this->setUser($teacher);
        $overview = array();
        assign_print_overview($courses, $overview);
        $this->assertEquals(1, count($overview));
        // Submissions without a grade.
        $this->assertMatchesRegularExpression('/.*Open Assignment.*/', $overview[$course->id]['assign']);
        $this->assertDoesNotMatchRegularExpression('/.*Assignment with submissions.*/', $overview[$course->id]['assign']);

        // Let us grade a submission.
        $this->setUser($teacher);
        $data = new stdClass();
        $data->grade = '50.0';
        $openassign->testable_apply_grade_to_user($data, $student->id, 0);

        // The assign_print_overview expects the grade date to be after the submission date.
        $graderecord = $DB->get_record('assign_grades', array('assignment' => $openassign->get_instance()->id,
            'userid' => $student->id, 'attemptnumber' => 0));
        $graderecord->timemodified += 1;
        $DB->update_record('assign_grades', $graderecord);

        $overview = array();
        assign_print_overview($courses, $overview);
        // Now assignment 4 should not show up.
        $this->assertEmpty($overview);

        $this->setUser($teacher);
        $overview = array();
        assign_print_overview($courses, $overview);
        // Now assignment 4 should not show up.
        $this->assertEmpty($overview);
    }

    /**
     * Test that assign_print_overview does not return any assignments which are Open Offline.
     */
    public function test_assign_print_overview_open_offline() {
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setAdminUser();
        $openassign = $this->create_instance($course, [
                'duedate' => time() + DAYSECS,
                'cutoffdate' => time() + (DAYSECS * 2),
            ]);

        $this->setUser($student);
        $overview = [];
        assign_print_overview([$course], $overview);

        $this->assertDebuggingCalledCount(0);
        $this->assertEquals(0, count($overview));
    }

    public function test_assign_get_recent_mod_activity() {
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $assign = $this->create_instance($course);
        $this->add_submission($student, $assign);
        $this->submit_for_grading($student, $assign);

        $index = 1;
        $activities = [
            $index => (object) [
                'type' => 'assign',
                'cmid' => $assign->get_course_module()->id,
            ],
        ];

        $this->setUser($teacher);
        assign_get_recent_mod_activity($activities, $index, time() - HOURSECS, $course->id, $assign->get_course_module()->id);

        $activity = $activities[1];
        $this->assertEquals("assign", $activity->type);
        $this->assertEquals($student->id, $activity->user->id);
    }

    /**
     * Ensure that assign_user_complete displays information about drafts.
     */
    public function test_assign_user_complete() {
        global $PAGE, $DB;

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $assign = $this->create_instance($course, ['submissiondrafts' => 1]);
        $this->add_submission($student, $assign);

        $PAGE->set_url(new moodle_url('/mod/assign/view.php', array('id' => $assign->get_course_module()->id)));

        $submission = $assign->get_user_submission($student->id, true);
        $submission->status = ASSIGN_SUBMISSION_STATUS_DRAFT;
        $DB->update_record('assign_submission', $submission);

        $this->expectOutputRegex('/Draft/');
        assign_user_complete($course, $student, $assign->get_course_module(), $assign->get_instance());
    }

    /**
     * Ensure that assign_user_outline fetches updated grades.
     */
    public function test_assign_user_outline() {
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $assign = $this->create_instance($course);

        $this->add_submission($student, $assign);
        $this->submit_for_grading($student, $assign);
        $this->mark_submission($teacher, $assign, $student, 50.0);

        $this->setUser($teacher);
        $data = $assign->get_user_grade($student->id, true);
        $data->grade = '50.5';
        $assign->update_grade($data);

        $result = assign_user_outline($course, $student, $assign->get_course_module(), $assign->get_instance());

        $this->assertMatchesRegularExpression('/50.5/', $result->info);
    }

    /**
     * Ensure that assign_get_completion_state reflects the correct status at each point.
     */
    public function test_assign_get_completion_state() {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $assign = $this->create_instance($course, [
                'submissiondrafts' => 0,
                'completionsubmit' => 1
            ]);

        $this->setUser($student);
        $result = assign_get_completion_state($course, $assign->get_course_module(), $student->id, false);
        $this->assertFalse($result);

        $this->add_submission($student, $assign);
        $result = assign_get_completion_state($course, $assign->get_course_module(), $student->id, false);
        $this->assertFalse($result);

        $this->submit_for_grading($student, $assign);
        $result = assign_get_completion_state($course, $assign->get_course_module(), $student->id, false);
        $this->assertTrue($result);

        $this->mark_submission($teacher, $assign, $student, 50.0);
        $result = assign_get_completion_state($course, $assign->get_course_module(), $student->id, false);
        $this->assertTrue($result);
    }

    /**
     * Tests for mod_assign_refresh_events.
     */
    public function test_assign_refresh_events() {
        global $DB;


        $duedate = time();
        $newduedate = $duedate + DAYSECS;

        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $assign = $this->create_instance($course, [
                'duedate' => $duedate,
            ]);

        $instance = $assign->get_instance();
        $eventparams = ['modulename' => 'assign', 'instance' => $instance->id];

        // Make sure the calendar event for assignment 1 matches the initial due date.
        $eventtime = $DB->get_field('event', 'timestart', $eventparams, MUST_EXIST);
        $this->assertEquals($eventtime, $duedate);

        // Manually update assignment 1's due date.
        $DB->update_record('assign', (object) ['id' => $instance->id, 'duedate' => $newduedate]);

        // Then refresh the assignment events of assignment 1's course.
        $this->assertTrue(assign_refresh_events($course->id));

        // Confirm that the assignment 1's due date event now has the new due date after refresh.
        $eventtime = $DB->get_field('event', 'timestart', $eventparams, MUST_EXIST);
        $this->assertEquals($eventtime, $newduedate);

        // Create a second course and assignment.
        $othercourse = $this->getDataGenerator()->create_course();;
        $otherassign = $this->create_instance($othercourse, ['duedate' => $duedate, 'course' => $othercourse->id]);
        $otherinstance = $otherassign->get_instance();

        // Manually update assignment 1 and 2's due dates.
        $newduedate += DAYSECS;
        $DB->update_record('assign', (object)['id' => $instance->id, 'duedate' => $newduedate]);
        $DB->update_record('assign', (object)['id' => $otherinstance->id, 'duedate' => $newduedate]);

        // Refresh events of all courses.
        $this->assertTrue(assign_refresh_events());

        // Check the due date calendar event for assignment 1.
        $eventtime = $DB->get_field('event', 'timestart', $eventparams, MUST_EXIST);
        $this->assertEquals($eventtime, $newduedate);

        // Check the due date calendar event for assignment 2.
        $eventparams['instance'] = $otherinstance->id;
        $eventtime = $DB->get_field('event', 'timestart', $eventparams, MUST_EXIST);
        $this->assertEquals($eventtime, $newduedate);

        // In case the course ID is passed as a numeric string.
        $this->assertTrue(assign_refresh_events('' . $course->id));

        // Non-existing course ID.
        $this->assertFalse(assign_refresh_events(-1));

        // Invalid course ID.
        $this->assertFalse(assign_refresh_events('aaa'));
    }
}
