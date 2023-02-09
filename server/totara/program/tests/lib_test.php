<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Nathan Lewis <nathan.lewis@totaralms.com>
 * @package totara_program
 */

use totara_core\advanced_feature;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php');
require_once($CFG->dirroot . '/totara/program/lib.php');
require_once($CFG->dirroot . '/totara/certification/lib.php');

/**
 * Program module PHPUnit test class.
 *
 * To test, run this from the command line from the $CFG->dirroot.
 * vendor/bin/phpunit --verbose totara_program_lib_testcase totara/program/tests/lib_test.php
 */
class totara_program_lib_testcase extends reportcache_advanced_testcase {

    public $fixusers = array();
    public $fixprograms = array();
    public $fixcertifications = array();
    public $numfixusers = 10;
    public $numfixcerts = 7;
    public $numfixprogs = 10;

    public $users = array();
    public $programs = array();
    public $certifications = array();
    public $numtestusers = 12;
    public $numtestcerts = 7;
    public $numtestprogs = 10;

    protected function tearDown(): void {
        $this->fixusers = null;
        $this->fixprograms = null;
        $this->fixcertifications = null;
        $this->numfixusers = null;
        $this->numfixcerts = null;
        $this->numfixprogs = null;

        $this->users = null;
        $this->programs = null;
        $this->certifications = null;
        $this->numtestusers = null;
        $this->numtestcerts = null;
        $this->numtestprogs = null;
        parent::tearDown();
    }

    /**
     * Asserts that the number of items in a recordset equals the given number, then close the recordset.
     *
     * @param int $expectedcount the expected number of items in the recordset
     * @param moodle_recordset $rs the recordset to iterate over and then close
     * @throws \PHPUnit\Framework\ExpectationFailedException
     */
    public function assert_count_and_close_recordset($expectedcount, $rs) {
        $i = 0;
        foreach ($rs as $item) {
            $i++;
        }
        $rs->close();

        if ($i != $expectedcount) {
            $this->fail('Recordset does not contain the expected number of items');
        }
    }

    /**
     * Test that prog_update_completion handles programs and certs.
     */
    public function test_prog_update_completion_progs_and_certs() {
        global $DB;


        $program_generator = \totara_program\testing\generator::instance();

        // Set up some stuff.
        $user = $this->getDataGenerator()->create_user();
        $program = $program_generator->create_program();
        $certification = $program_generator->create_certification();
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();
        $course4 = $this->getDataGenerator()->create_course();
        $course5 = $this->getDataGenerator()->create_course();
        $course6 = $this->getDataGenerator()->create_course();

        // Add the courses to the program and certification.
        $program_generator->legacy_add_courseset_program($program->id,
            array($course1->id, $course2->id));
        $program_generator->legacy_add_courseset_program($certification->id,
            array($course3->id, $course4->id), CERTIFPATH_CERT);
        $program_generator->legacy_add_courseset_program($certification->id,
            array($course5->id, $course6->id), CERTIFPATH_RECERT);

        // Assign the user to the program and cert as an individual.
        $program_generator->assign_to_program($program->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);
        $program_generator->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);

        // Mark all the courses complete, with traceable time completed.
        $completion = new completion_completion(array('userid' => $user->id, 'course' => $course1->id));
        $completion->mark_inprogress(800);
        $completion->mark_complete(1000);

        $completion = new completion_completion(array('userid' => $user->id, 'course' => $course2->id));
        $completion->mark_inprogress(1800);
        $completion->mark_complete(2000);

        $completion = new completion_completion(array('userid' => $user->id, 'course' => $course3->id));
        $completion->mark_inprogress(2800);
        $completion->mark_complete(3000);

        $completion = new completion_completion(array('userid' => $user->id, 'course' => $course4->id));
        $completion->mark_inprogress(3800);
        $completion->mark_complete(4000);

        $completion = new completion_completion(array('userid' => $user->id, 'course' => $course5->id));
        $completion->mark_inprogress(5800);
        $completion->mark_complete(6000);

        $completion = new completion_completion(array('userid' => $user->id, 'course' => $course6->id));
        $completion->mark_inprogress(4800);
        $completion->mark_complete(5000);

        // Check the existing data.
        $this->assertEquals(2, $DB->count_records('prog_completion', array('coursesetid' => 0)));
        $this->assertEquals(1, $DB->count_records('certif_completion'));

        // Update the certification so that the user is expired.
        list($certcompletion, $progcompletion) = certif_load_completion($certification->id, $user->id);
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $progcompletion->timecompleted = 0;
        $certcompletion->status = CERTIFSTATUS_EXPIRED;
        $certcompletion->renewalstatus = CERTIFRENEWALSTATUS_EXPIRED;
        $certcompletion->certifpath = CERTIFPATH_CERT;
        $certcompletion->timecompleted = 0;
        $certcompletion->timewindowopens = 0;
        $certcompletion->timeexpires = 0;
        $certcompletion->baselinetimeexpires = 0;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion)); // Contains data validation, so we don't need to check it here.

        // The program should currently be complete. Update the program so that the user is incomplete.
        $progcompletion = $DB->get_record('prog_completion',
            array('programid' => $program->id, 'userid' => $user->id, 'coursesetid' => 0));
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $this->assertEquals(2000, $progcompletion->timecompleted);
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $progcompletion->timecompleted = 0;
        $DB->update_record('prog_completion', $progcompletion);

        // Call prog_update_completion, which should process all programs for the user.
        prog_update_completion($user->id);

        // Verify that the program is marked completed.
        $progcompletion = $DB->get_record('prog_completion',
            array('programid' => $program->id, 'userid' => $user->id, 'coursesetid' => 0));
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $this->assertEquals(2000, $progcompletion->timecompleted);

        // Verify the the user was marked complete using the dates in the primary cert path courses.
        list($certcompletion, $progcompletion) = certif_load_completion($certification->id, $user->id);
        $this->assertEquals(4000, $certcompletion->timecompleted);
        $this->assertEquals(4000, $progcompletion->timecompleted);

        // Update the certification so that the recertification window is open.
        list($certcompletion, $progcompletion) = certif_load_completion($certification->id, $user->id);
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $progcompletion->timecompleted = 0;
        $certcompletion->renewalstatus = CERTIFRENEWALSTATUS_DUE;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion)); // Contains data validation, so we don't need to check it here.

        // Update the course completions all courses. The recertification should have the new date, but the complete program
        // won't be effected.
        $DB->execute("UPDATE {course_completions} SET timecompleted = timecompleted + 10000");

        // Call prog_update_completion, which should process all programs for the user.
        prog_update_completion($user->id);

        // Verify that the program is marked completed (with the original completion date).
        $progcompletion = $DB->get_record('prog_completion',
            array('programid' => $program->id, 'userid' => $user->id, 'coursesetid' => 0));
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $this->assertEquals(2000, $progcompletion->timecompleted);

        // Verify the the user was marked complete using the (increased) dates in the recertification path courses.
        list($certcompletion, $progcompletion) = certif_load_completion($certification->id, $user->id);
        $this->assertEquals(16000, $certcompletion->timecompleted);
        $this->assertEquals(16000, $progcompletion->timecompleted);
    }

    /**
     * Test that prog_update_completion processes only the specified programs.
     */
    public function test_prog_update_completion_specific_prog() {
        global $DB;


        $program_generator = \totara_program\testing\generator::instance();

        // Set up users, programs, courses.
        $user = $this->getDataGenerator()->create_user();
        $program1 = $program_generator->create_program();
        $program2 = $program_generator->create_program();
        $program3 = $program_generator->create_program();
        $program4 = $program_generator->create_program();
        $program5 = $program_generator->create_program();
        $program6 = $program_generator->create_program();
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();
        $course4 = $this->getDataGenerator()->create_course();
        $course5 = $this->getDataGenerator()->create_course();
        $course6 = $this->getDataGenerator()->create_course();

        // Add the courses to the programs.
        $program_generator->legacy_add_courseset_program($program1->id,
            array($course1->id));
        $program_generator->legacy_add_courseset_program($program2->id,
            array($course2->id));
        $program_generator->legacy_add_courseset_program($program3->id,
            array($course2->id)); // Note that course2 is used in program2 and program3.
        $program_generator->legacy_add_courseset_program($program4->id,
            array($course4->id));
        $program_generator->legacy_add_courseset_program($program5->id,
            array($course5->id));
        $program_generator->legacy_add_courseset_program($program6->id,
            array($course6->id));

        // Reload the programs, because their content has changed.
        $program1 = new program($program1->id);
        $program2 = new program($program2->id);
        $program3 = new program($program3->id);
        $program4 = new program($program4->id);
        $program5 = new program($program5->id);
        $program6 = new program($program6->id);

        // Assign the user to the programs.
        $program_generator->assign_to_program($program1->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);
        $program_generator->assign_to_program($program2->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);
        $program_generator->assign_to_program($program3->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);
        $program_generator->assign_to_program($program4->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);
        $program_generator->assign_to_program($program5->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);
        $program_generator->assign_to_program($program6->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);

        // Mark all the courses complete, with traceable time completed.
        $completion = new completion_completion(array('userid' => $user->id, 'course' => $course1->id));
        $completion->mark_complete(1000);
        $completion = new completion_completion(array('userid' => $user->id, 'course' => $course2->id));
        $completion->mark_complete(2000);
        $completion = new completion_completion(array('userid' => $user->id, 'course' => $course3->id));
        $completion->mark_complete(3000);
        $completion = new completion_completion(array('userid' => $user->id, 'course' => $course4->id));
        $completion->mark_complete(4000);
        $completion = new completion_completion(array('userid' => $user->id, 'course' => $course5->id));
        $completion->mark_complete(5000);
        $completion = new completion_completion(array('userid' => $user->id, 'course' => $course6->id));
        $completion->mark_complete(6000);

        // Check that all programs are marked complete, and change them back to incomplete.
        $progcompletion = prog_load_completion($program1->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $this->assertEquals(1000, $progcompletion->timecompleted);
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $progcompletion->timecompleted = 0;
        $this->assertTrue(prog_write_completion($progcompletion));

        $progcompletion = prog_load_completion($program2->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $this->assertEquals(2000, $progcompletion->timecompleted);
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $progcompletion->timecompleted = 0;
        $this->assertTrue(prog_write_completion($progcompletion));

        $progcompletion = prog_load_completion($program3->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $this->assertEquals(2000, $progcompletion->timecompleted); // Completed by course2!
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $progcompletion->timecompleted = 0;
        $this->assertTrue(prog_write_completion($progcompletion));

        $progcompletion = prog_load_completion($program4->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $this->assertEquals(4000, $progcompletion->timecompleted);
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $progcompletion->timecompleted = 0;
        $this->assertTrue(prog_write_completion($progcompletion));

        $progcompletion = prog_load_completion($program5->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $this->assertEquals(5000, $progcompletion->timecompleted);
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $progcompletion->timecompleted = 0;
        $this->assertTrue(prog_write_completion($progcompletion));

        $progcompletion = prog_load_completion($program6->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $this->assertEquals(6000, $progcompletion->timecompleted);
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $progcompletion->timecompleted = 0;
        $this->assertTrue(prog_write_completion($progcompletion));

        // Call prog_update_completion with program1 and check that only program1 was marked complete.
        prog_update_completion($user->id, $program1);
        $progcompletion = prog_load_completion($program1->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $progcompletion = prog_load_completion($program2->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status);
        $progcompletion = prog_load_completion($program3->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status);
        $progcompletion = prog_load_completion($program4->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status);
        $progcompletion = prog_load_completion($program5->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status);
        $progcompletion = prog_load_completion($program6->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status);

        // Call prog_update_completion with course2 and check that program2 and program3 were marked complete (and program1 above).
        prog_update_completion($user->id, null, $course2->id);
        $progcompletion = prog_load_completion($program1->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $progcompletion = prog_load_completion($program2->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $progcompletion = prog_load_completion($program3->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $progcompletion = prog_load_completion($program4->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status);
        $progcompletion = prog_load_completion($program5->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status);
        $progcompletion = prog_load_completion($program6->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status);

        // Call prog_update_completion with no program or course and see that all programs were marked complete.
        prog_update_completion($user->id);
        $progcompletion = prog_load_completion($program1->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $progcompletion = prog_load_completion($program2->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $progcompletion = prog_load_completion($program3->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $progcompletion = prog_load_completion($program4->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $progcompletion = prog_load_completion($program5->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $progcompletion = prog_load_completion($program6->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);

        // Additionally, check that mark_complete is only calling prog_update_completion with the specified course.

        // Change the programs back to incomplete.
        $progcompletion = prog_load_completion($program1->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $this->assertEquals(1000, $progcompletion->timecompleted);
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $progcompletion->timecompleted = 0;
        $this->assertTrue(prog_write_completion($progcompletion));

        $progcompletion = prog_load_completion($program2->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $this->assertEquals(2000, $progcompletion->timecompleted);
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $progcompletion->timecompleted = 0;
        $this->assertTrue(prog_write_completion($progcompletion));

        $progcompletion = prog_load_completion($program3->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $this->assertEquals(2000, $progcompletion->timecompleted); // Completed by course2!
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $progcompletion->timecompleted = 0;
        $this->assertTrue(prog_write_completion($progcompletion));

        $progcompletion = prog_load_completion($program4->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $this->assertEquals(4000, $progcompletion->timecompleted);
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $progcompletion->timecompleted = 0;
        $this->assertTrue(prog_write_completion($progcompletion));

        $progcompletion = prog_load_completion($program5->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $this->assertEquals(5000, $progcompletion->timecompleted);
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $progcompletion->timecompleted = 0;
        $this->assertTrue(prog_write_completion($progcompletion));

        $progcompletion = prog_load_completion($program6->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $this->assertEquals(6000, $progcompletion->timecompleted);
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $progcompletion->timecompleted = 0;
        $this->assertTrue(prog_write_completion($progcompletion));

        // Mark just course2 incomplete. The others must not be marked incomplete, so that if mark_complete causes the
        // other programs to be reaggregated then they will also be marked complete and cause the assertions to fail.
        $DB->set_field('course_completions', 'timecompleted', 0, array('course' => $course2->id));
        cache::make('core', 'coursecompletion')->purge();

        // Run the funciton and check that only program2 and program3 were marked complete.
        $completion = new completion_completion(array('userid' => $user->id, 'course' => $course2->id));
        $completion->mark_complete();
        $progcompletion = prog_load_completion($program1->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status);
        $progcompletion = prog_load_completion($program2->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $progcompletion = prog_load_completion($program3->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $progcompletion = prog_load_completion($program4->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status);
        $progcompletion = prog_load_completion($program5->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status);
        $progcompletion = prog_load_completion($program6->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status);
    }

    public function test_prog_reset_course_set_completions() {
        global $DB;


        $program_generator = \totara_program\testing\generator::instance();

        // Set up some stuff.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $users = array($user1, $user2, $user3, $user4);

        $prog1 = $program_generator->create_program();
        $prog2 = $program_generator->create_program();
        $cert1 = $program_generator->create_certification();
        $cert2 = $program_generator->create_certification();

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();
        $course4 = $this->getDataGenerator()->create_course();
        $course5 = $this->getDataGenerator()->create_course();
        $course6 = $this->getDataGenerator()->create_course();
        $courses = array($course1, $course2, $course3, $course4, $course5, $course6);

        // Add the courses to the programs and certifications.
        $program_generator->legacy_add_courseset_program($prog1->id, array($course1->id));
        $program_generator->legacy_add_courseset_program($prog2->id, array($course2->id));
        $program_generator->legacy_add_courseset_program($cert1->id, array($course3->id), CERTIFPATH_CERT);
        $program_generator->legacy_add_courseset_program($cert1->id, array($course4->id), CERTIFPATH_RECERT);
        $program_generator->legacy_add_courseset_program($cert2->id, array($course5->id), CERTIFPATH_CERT);
        $program_generator->legacy_add_courseset_program($cert2->id, array($course6->id), CERTIFPATH_RECERT);

        // Assign the users to the programs and certs as individuals.
        $startassigntime = time();
        foreach ($users as $user) {
            $program_generator->assign_to_program($prog1->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);
            $program_generator->assign_to_program($prog2->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);
            $program_generator->assign_to_program($cert1->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);
            $program_generator->assign_to_program($cert2->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);
        }
        $endassigntime = time();
        $this->waitForSecond();

        // Mark all the users complete in all the courses, causing completion in all programs/certs.
        foreach ($users as $user) {
            foreach ($courses as $course) {
                $completion = new completion_completion(array('userid' => $user->id, 'course' => $course->id));
                $completion->mark_complete(1000);
            }
        }

        // Hack the timedue field - we can't confirm that 0 changes to 0, so put something else in there.
        $DB->set_field('prog_completion', 'timedue', 12345);

        // Check all the data starts out correct.
        $sql = "SELECT pc.*, pcs.certifpath
                  FROM {prog_completion} pc
                  JOIN {prog_courseset} pcs ON pc.coursesetid = pcs.id
                 WHERE pc.coursesetid != 0";
        $progcompletionnonzeropre = $DB->get_records_sql($sql);
        $this->assertEquals(16, count($progcompletionnonzeropre));

        $sql = "SELECT *
                  FROM {prog_completion}
                 WHERE coursesetid = 0";
        $progcompletionzeropre = $DB->get_records_sql($sql);
        $this->assertEquals(16, count($progcompletionzeropre));

        foreach ($progcompletionnonzeropre as $pcnonzero) {
            $this->assertEquals(STATUS_COURSESET_COMPLETE, $pcnonzero->status);
            $this->assertGreaterThanOrEqual($startassigntime, $pcnonzero->timecreated);
            $this->assertLessThanOrEqual($endassigntime, $pcnonzero->timecreated);
            $this->assertEquals(12345, $pcnonzero->timedue);
            $this->assertEquals(1000, $pcnonzero->timestarted);
            $this->assertEquals(1000, $pcnonzero->timecompleted);

            if ($pcnonzero->programid == $prog1->id && $pcnonzero->userid == $user1->id ||
                $pcnonzero->programid == $cert1->id && $pcnonzero->userid == $user2->id && $pcnonzero->certifpath == CERTIFPATH_CERT ||
                $pcnonzero->programid == $cert2->id && $pcnonzero->userid == $user3->id && $pcnonzero->certifpath == CERTIFPATH_RECERT) {
                // Manually modify the data in memory to what we are expecting to happen automatically in the database.
                $pcnonzero->status = STATUS_COURSESET_INCOMPLETE;
                $pcnonzero->timestarted = 0;
                $pcnonzero->timedue = 0;
                $pcnonzero->timecompleted = 0;
            }
        }

        // Run the function that we're testing.
        prog_reset_course_set_completions($prog1->id, $user1->id);
        prog_reset_course_set_completions($cert1->id, $user2->id, CERTIFPATH_CERT);
        prog_reset_course_set_completions($cert2->id, $user3->id, CERTIFPATH_RECERT);

        // Check that modified data matches the expected.
        $sql = "SELECT pc.*, pcs.certifpath
                  FROM {prog_completion} pc
                  JOIN {prog_courseset} pcs ON pc.coursesetid = pcs.id
                 WHERE coursesetid != 0";
        $progcompletionnonzeropost = $DB->get_records_sql($sql);
        $this->assertEquals($progcompletionnonzeropre, $progcompletionnonzeropost);
        $this->assertEquals(16, count($progcompletionnonzeropost));

        // Course set 0 records are unaffected for all users.
        $sql = "SELECT *
                  FROM {prog_completion}
                 WHERE coursesetid = 0";
        $progcompletionzeropost = $DB->get_records_sql($sql);
        $this->assertEquals($progcompletionzeropre, $progcompletionzeropost);
        $this->assertEquals(16, count($progcompletionzeropost));
    }

    public function test_prog_update_available_enrolments_with_one_program() {
        global $DB;

        $generator = $this->getDataGenerator();
        $program_generator = \totara_program\testing\generator::instance();

        // Create some data.
        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $user3 = $generator->create_user();
        $user4 = $generator->create_user();
        $user5 = $generator->create_user();
        $user6 = $generator->create_user();
        $user7 = $generator->create_user();
        $user8 = $generator->create_user();
        $course1 = $generator->create_course();
        $course2 = $generator->create_course();
        $prog1 = $program_generator->create_program();
        $prog2 = $program_generator->create_program();

        // Assign users to programs.
        $alluserids = array($user1->id, $user2->id, $user3->id, $user4->id, $user5->id, $user6->id, $user7->id, $user8->id);
        $program_generator->assign_program($prog1->id, $alluserids);
        $program_generator->assign_program($prog2->id, $alluserids);

        // Assign course to programs.
        $program_generator->legacy_add_courseset_program($prog1->id, array($course1->id));
        $program_generator->legacy_add_courseset_program($prog2->id, array($course2->id));

        // Enrol the users in the courses using the program enrolment plugin.
        $generator->enrol_user($user1->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user2->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user3->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user4->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user5->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user6->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user7->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user8->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user1->id, $course2->id, null, 'totara_program');
        $generator->enrol_user($user2->id, $course2->id, null, 'totara_program');
        $generator->enrol_user($user3->id, $course2->id, null, 'totara_program');
        $generator->enrol_user($user4->id, $course2->id, null, 'totara_program');
        $generator->enrol_user($user5->id, $course2->id, null, 'totara_program');
        $generator->enrol_user($user6->id, $course2->id, null, 'totara_program');
        $generator->enrol_user($user7->id, $course2->id, null, 'totara_program');
        $generator->enrol_user($user8->id, $course2->id, null, 'totara_program');

        // Check that the current data is as expected.
        $expecteduserenrolments = $DB->get_records('user_enrolments');
        $this->assertCount(16, $expecteduserenrolments);
        foreach ($expecteduserenrolments as $userenrolment) {
            // All users have active enrolments.
            $this->assertEquals(ENROL_USER_ACTIVE, $userenrolment->status);
        }

        // Set up several users in each state, to ensure that there's no crossover between user data.

        // 1) User1 and user2 are assigned and their enrolment is not suspended.
        // Nothing to do here - all users are already assigned.

        // 2) User3 and user4 are assigned but their enrolment is suspended.
        $DB->set_field('user_enrolments', 'status', ENROL_USER_SUSPENDED, array('userid' => $user3->id));
        $DB->set_field('user_enrolments', 'status', ENROL_USER_SUSPENDED, array('userid' => $user4->id));

        // 3) User5 and user6 are not assigned but their enrolment is not suspended.
        $DB->delete_records('prog_user_assignment', array('userid' => $user5->id));
        $DB->delete_records('prog_user_assignment', array('userid' => $user6->id));

        // 4) User7 and user8 are not assigned and their enrolment is suspended.
        $DB->delete_records('prog_user_assignment', array('userid' => $user7->id));
        $DB->delete_records('prog_user_assignment', array('userid' => $user8->id));
        $DB->set_field('user_enrolments', 'status', ENROL_USER_SUSPENDED, array('userid' => $user7->id));
        $DB->set_field('user_enrolments', 'status', ENROL_USER_SUSPENDED, array('userid' => $user8->id));

        // Load the current set of data.
        $expecteduserenrolments = $DB->get_records('user_enrolments');
        $this->assertCount(16, $expecteduserenrolments);

        // Run the function.
        /* @var enrol_totara_program_plugin $programplugin */
        $programplugin = enrol_get_plugin('totara_program');
        prog_update_available_enrolments($programplugin, $prog1->id);

        // Manually make the same change to the expected data.

        // 1) No change - the user's enrolment is still not suspended.
        // 2) The enrolment is unsuspended.
        // 3) The enrolment is suspended.
        // 4) No change - the user's enrolment is still suspended.
        $enrols = $DB->get_records('enrol', array('enrol' => 'totara_program'));
        foreach ($expecteduserenrolments as $key => $userenrolment) {
            if ($enrols[$userenrolment->enrolid]->courseid == $course1->id) {
                if (in_array($userenrolment->userid, array($user3->id, $user4->id))) {
                    // Users 3 and 4 will be unsuspended from course1.
                    $expecteduserenrolments[$key]->status = (string)ENROL_USER_ACTIVE;
                } else if (in_array($userenrolment->userid, array($user5->id, $user6->id))) {
                    // Users 5 and 6 will be suspended from course1.
                    $expecteduserenrolments[$key]->status = (string)ENROL_USER_SUSPENDED;
                }
            }
        }

        // And check that the expected records match the actual records.
        $actualuserenrolments = $DB->get_records('user_enrolments');
        $this->assertCount(16, $actualuserenrolments);
        foreach ($actualuserenrolments as $actualuserenrolment) {
            $expecteduserenrolment = $expecteduserenrolments[$actualuserenrolment->id];
            unset($expecteduserenrolment->timemodified);
            unset($actualuserenrolment->timemodified);
            $this->assertEquals($expecteduserenrolment, $actualuserenrolment);
        }
    }

    public function test_prog_update_available_enrolments_with_all_programs() {
        global $DB;

        $generator = $this->getDataGenerator();
        $program_generator = \totara_program\testing\generator::instance();

        // Create some data.
        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $user3 = $generator->create_user();
        $user4 = $generator->create_user();
        $user5 = $generator->create_user();
        $user6 = $generator->create_user();
        $user7 = $generator->create_user();
        $user8 = $generator->create_user();
        $course1 = $generator->create_course();
        $course2 = $generator->create_course();
        $prog1 = $program_generator->create_program();
        $prog2 = $program_generator->create_program();

        // Assign users to programs.
        $alluserids = array($user1->id, $user2->id, $user3->id, $user4->id, $user5->id, $user6->id, $user7->id, $user8->id);
        $program_generator->assign_program($prog1->id, $alluserids);
        $program_generator->assign_program($prog2->id, $alluserids);

        // Assign course to programs.
        $program_generator->legacy_add_courseset_program($prog1->id, array($course1->id));
        $program_generator->legacy_add_courseset_program($prog2->id, array($course2->id));

        // Enrol the users in the courses using the program enrolment plugin.
        $generator->enrol_user($user1->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user2->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user3->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user4->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user5->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user6->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user7->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user8->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user1->id, $course2->id, null, 'totara_program');
        $generator->enrol_user($user2->id, $course2->id, null, 'totara_program');
        $generator->enrol_user($user3->id, $course2->id, null, 'totara_program');
        $generator->enrol_user($user4->id, $course2->id, null, 'totara_program');
        $generator->enrol_user($user5->id, $course2->id, null, 'totara_program');
        $generator->enrol_user($user6->id, $course2->id, null, 'totara_program');
        $generator->enrol_user($user7->id, $course2->id, null, 'totara_program');
        $generator->enrol_user($user8->id, $course2->id, null, 'totara_program');

        // Check that the current data is as expected.
        $expecteduserenrolments = $DB->get_records('user_enrolments');
        $this->assertCount(16, $expecteduserenrolments);
        foreach ($expecteduserenrolments as $userenrolment) {
            // All users have active enrolments.
            $this->assertEquals(ENROL_USER_ACTIVE, $userenrolment->status);
        }

        // Set up several users in each state, to ensure that there's no crossover between user data.

        // 1) User1 and user2 are assigned and their enrolment is not suspended.
        // Nothing to do here - all users are already assigned.

        // 2) User3 and user4 are assigned but their enrolment is suspended.
        $DB->set_field('user_enrolments', 'status', ENROL_USER_SUSPENDED, array('userid' => $user3->id));
        $DB->set_field('user_enrolments', 'status', ENROL_USER_SUSPENDED, array('userid' => $user4->id));

        // 3) User5 and user6 are not assigned but their enrolment is not suspended.
        $DB->delete_records('prog_user_assignment', array('userid' => $user5->id));
        $DB->delete_records('prog_user_assignment', array('userid' => $user6->id));

        // 4) User7 and user8 are not assigned and their enrolment is suspended.
        $DB->delete_records('prog_user_assignment', array('userid' => $user7->id));
        $DB->delete_records('prog_user_assignment', array('userid' => $user8->id));
        $DB->set_field('user_enrolments', 'status', ENROL_USER_SUSPENDED, array('userid' => $user7->id));
        $DB->set_field('user_enrolments', 'status', ENROL_USER_SUSPENDED, array('userid' => $user8->id));

        // Load the current set of data.
        $expecteduserenrolments = $DB->get_records('user_enrolments');
        $this->assertCount(16, $expecteduserenrolments);

        // Run the function.
        /* @var enrol_totara_program_plugin $programplugin */
        $programplugin = enrol_get_plugin('totara_program');
        prog_update_available_enrolments($programplugin);

        // Manually make the same change to the expected data.

        // 1) No change - the user's enrolment is still not suspended.
        // 2) The enrolment is unsuspended.
        // 3) The enrolment is suspended.
        // 4) No change - the user's enrolment is still suspended.
        $enrols = $DB->get_records('enrol', array('enrol' => 'totara_program'));
        foreach ($expecteduserenrolments as $key => $userenrolment) {
            if (in_array($userenrolment->userid, array($user3->id, $user4->id))) {
                // Users 3 and 4 will be unsuspended from both courses.
                $expecteduserenrolments[$key]->status = (string)ENROL_USER_ACTIVE;
            } else if (in_array($userenrolment->userid, array($user5->id, $user6->id))) {
                // Users 5 and 6 will be suspended from both courses.
                $expecteduserenrolments[$key]->status = (string)ENROL_USER_SUSPENDED;
            }
        }

        // And check that the expected records match the actual records.
        $actualuserenrolments = $DB->get_records('user_enrolments');
        $this->assertCount(16, $actualuserenrolments);
        foreach ($actualuserenrolments as $actualuserenrolment) {
            $expecteduserenrolment = $expecteduserenrolments[$actualuserenrolment->id];
            unset($expecteduserenrolment->timemodified);
            unset($actualuserenrolment->timemodified);
            $this->assertEquals($expecteduserenrolment, $actualuserenrolment);
        }
    }

    public function test_prog_update_available_enrolments_with_learning_plan() {
        global $DB;

        $generator = $this->getDataGenerator();
        $program_generator = \totara_program\testing\generator::instance();
        $plan_generator = \totara_plan\testing\generator::instance();

        // Create some data.
        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $user3 = $generator->create_user();
        $user4 = $generator->create_user();
        $user5 = $generator->create_user();
        $user6 = $generator->create_user();
        $user7 = $generator->create_user();
        $user8 = $generator->create_user();
        $course1 = $generator->create_course();
        $course2 = $generator->create_course();
        $prog1 = $program_generator->create_program();
        $prog2 = $program_generator->create_program();

        // Add programs to learning plans.
        $alluserids = array($user1->id, $user2->id, $user3->id, $user4->id, $user5->id, $user6->id, $user7->id, $user8->id);
        $plan = array();
        $component = array();
        foreach ($alluserids as $userid) {
            $plan[$userid] = $plan_generator->legacy_create_plan($userid, []);
            $component[$userid] = $plan[$userid]->get_component('program');
            $component[$userid]->assign_new_item($prog1->id, false);
            $component[$userid]->assign_new_item($prog2->id, false);
        }

        // Assign course to programs.
        $program_generator->legacy_add_courseset_program($prog1->id, array($course1->id));
        $program_generator->legacy_add_courseset_program($prog2->id, array($course2->id));

        // Enrol the users in the courses using the program enrolment plugin.
        $generator->enrol_user($user1->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user2->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user3->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user4->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user5->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user6->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user7->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user8->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user1->id, $course2->id, null, 'totara_program');
        $generator->enrol_user($user2->id, $course2->id, null, 'totara_program');
        $generator->enrol_user($user3->id, $course2->id, null, 'totara_program');
        $generator->enrol_user($user4->id, $course2->id, null, 'totara_program');
        $generator->enrol_user($user5->id, $course2->id, null, 'totara_program');
        $generator->enrol_user($user6->id, $course2->id, null, 'totara_program');
        $generator->enrol_user($user7->id, $course2->id, null, 'totara_program');
        $generator->enrol_user($user8->id, $course2->id, null, 'totara_program');

        // Check that the current data is as expected.
        $expecteduserenrolments = $DB->get_records('user_enrolments');
        $this->assertCount(16, $expecteduserenrolments);
        foreach ($expecteduserenrolments as $userenrolment) {
            // All users have active enrolments.
            $this->assertEquals(ENROL_USER_ACTIVE, $userenrolment->status);
        }

        // Set up several users in each state, to ensure that there's no crossover between user data.

        // 1) User1 and user2 are assigned and their enrolment is not suspended.
        // Nothing to do here - all users are already assigned.

        // 2) User3 and user4 are assigned but their enrolment is suspended.
        $DB->set_field('user_enrolments', 'status', ENROL_USER_SUSPENDED, array('userid' => $user3->id));
        $DB->set_field('user_enrolments', 'status', ENROL_USER_SUSPENDED, array('userid' => $user4->id));

        // 3) User5 and user6 are not assigned but their enrolment is not suspended.
        $DB->delete_records('dp_plan_program_assign', array('planid' => $plan[$user5->id]->id));
        \totara_program\assignment\plan::update_plan_assignments($user5->id, $plan[$user5->id]->id);
        $DB->delete_records('dp_plan_program_assign', array('planid' => $plan[$user6->id]->id));
        \totara_program\assignment\plan::update_plan_assignments($user6->id, $plan[$user6->id]->id);

        // 4) User7 and user8 are not assigned and their enrolment is suspended.
        $DB->delete_records('dp_plan_program_assign', array('planid' => $plan[$user7->id]->id));
        \totara_program\assignment\plan::update_plan_assignments($user7->id, $plan[$user7->id]->id);
        $DB->delete_records('dp_plan_program_assign', array('planid' => $plan[$user8->id]->id));
        \totara_program\assignment\plan::update_plan_assignments($user8->id, $plan[$user8->id]->id);
        $DB->set_field('user_enrolments', 'status', ENROL_USER_SUSPENDED, array('userid' => $user7->id));
        $DB->set_field('user_enrolments', 'status', ENROL_USER_SUSPENDED, array('userid' => $user8->id));

        // Load the current set of data.
        $expecteduserenrolments = $DB->get_records('user_enrolments');
        $this->assertCount(16, $expecteduserenrolments);

        // Run the function.
        /* @var enrol_totara_program_plugin $programplugin */
        $programplugin = enrol_get_plugin('totara_program');
        prog_update_available_enrolments($programplugin);

        // Manually make the same change to the expected data.

        // 1) No change - the user's enrolment is still not suspended.
        // 2) The enrolment is unsuspended.
        // 3) The enrolment is suspended.
        // 4) No change - the user's enrolment is still suspended.
        $enrols = $DB->get_records('enrol', array('enrol' => 'totara_program'));
        foreach ($expecteduserenrolments as $key => $userenrolment) {
            if (in_array($userenrolment->userid, array($user3->id, $user4->id))) {
                // Users 3 and 4 will be unsuspended from both courses.
                $expecteduserenrolments[$key]->status = (string)ENROL_USER_ACTIVE;
            } else if (in_array($userenrolment->userid, array($user5->id, $user6->id))) {
                // Users 5 and 6 will be suspended from both courses.
                $expecteduserenrolments[$key]->status = (string)ENROL_USER_SUSPENDED;
            }
        }

        // And check that the expected records match the actual records.
        $actualuserenrolments = $DB->get_records('user_enrolments');
        $this->assertCount(16, $actualuserenrolments);
        foreach ($actualuserenrolments as $actualuserenrolment) {
            $expecteduserenrolment = $expecteduserenrolments[$actualuserenrolment->id];
            unset($expecteduserenrolment->timemodified);
            unset($actualuserenrolment->timemodified);
            $this->assertEquals($expecteduserenrolment, $actualuserenrolment);
        }
    }

    /**
     * Set up the users, programs and completions for testing the fixer.
     */
    public function setup_fix_completions() {

        $program_generator = \totara_program\testing\generator::instance();

        // Turn off programs. This is to test that it doesn't interfere with certification completion.
        set_config('enableprograms', advanced_feature::DISABLED);

        // Create users.
        for ($i = 1; $i <= $this->numfixusers; $i++) {
            $this->fixusers[$i] = $this->getDataGenerator()->create_user();
        }

        // Create programs.
        for ($i = 1; $i <= $this->numfixprogs; $i++) {
            $this->fixprograms[$i] = $program_generator->create_program();
        }

        // Create certifications, mostly so that we don't end up with coincidental success due to matching ids.
        for ($i = 1; $i <= $this->numfixcerts; $i++) {
            $this->fixcertifications[$i] = $program_generator->create_certification();
        }

        // Assign users to the program as individuals.
        foreach ($this->fixusers as $user) {
            foreach ($this->fixprograms as $prog) {
                $program_generator->assign_to_program($prog->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);
            }
        }
    }

    /**
     * Test prog_fix_completions - ensure that the correct records are repaired.
     */
    public function test_prog_fix_completions_only_selected() {
        global $DB;

        // Set up some data that is valid.
        $this->setup_fix_completions();

        // Break all the records.
        $sql = "UPDATE {prog_completion} SET timedue = :timedueunknown WHERE coursesetid = 0";
        $DB->execute($sql, array('timedueunknown' => COMPLETION_TIME_UNKNOWN));

        $expectederrors = array('error:timedueunknown' => 'timedue');

        // Check that all records are broken in the specified way.
        $progcompletions = $DB->get_records('prog_completion');
        foreach ($progcompletions as $progcompletion) {
            $errors = prog_get_completion_errors($progcompletion);
            $this->assertEquals($expectederrors, $errors);
        }
        $this->assertCount($this->numfixusers * $this->numfixprogs, $progcompletions);

        $prog = $this->fixprograms[8];
        $user = $this->fixusers[2];

        // Apply the fix to just one user/prog.
        prog_fix_completions('fixassignedtimedueunknown', $prog->id, $user->id);

        // Check that the correct records have been fixed.
        $progcompletions = $DB->get_records('prog_completion');
        foreach ($progcompletions as $progcompletion) {
            $errors = prog_get_completion_errors($progcompletion);
            if ($progcompletion->userid == $user->id && $progcompletion->programid == $prog->id) {
                $this->assertEquals(array(), $errors);
            } else {
                $this->assertEquals($expectederrors, $errors);
            }
        }
        $this->assertEquals($this->numfixusers * $this->numfixprogs, count($progcompletions));

        // Apply the fix to just one user, all progs (don't need to reset, just overlap).
        prog_fix_completions('fixassignedtimedueunknown', 0, $user->id);

        // Check that the correct records have been fixed.
        $progcompletions = $DB->get_records('prog_completion');
        foreach ($progcompletions as $progcompletion) {
            $errors = prog_get_completion_errors($progcompletion);
            if ($progcompletion->userid == $user->id) { // Overlap previous fixes.
                $this->assertEquals(array(), $errors);
            } else {
                $this->assertEquals($expectederrors, $errors);
            }
        }
        $this->assertEquals($this->numfixusers * $this->numfixprogs, count($progcompletions));

        // Apply the fix to just one prog, all users (don't need to reset, just overlap).
        prog_fix_completions('fixassignedtimedueunknown', $prog->id);

        // Check that the correct records have been fixed.
        $progcompletions = $DB->get_records('prog_completion');
        foreach ($progcompletions as $progcompletion) {
            $errors = prog_get_completion_errors($progcompletion);
            if ($progcompletion->userid == $user->id || $progcompletion->programid == $prog->id) { // Overlap previous fixes.
                $this->assertEquals(array(), $errors);
            } else {
                $this->assertEquals($expectederrors, $errors);
            }
        }
        $this->assertEquals($this->numfixusers * $this->numfixprogs, count($progcompletions));

        // Apply the fix to all records (overlaps previous fixes).
        prog_fix_completions('fixassignedtimedueunknown');

        // Check that the correct records have been fixed.
        $progcompletions = $DB->get_records('prog_completion');
        foreach ($progcompletions as $progcompletion) {
            $errors = prog_get_completion_errors($progcompletion);
            $this->assertEquals(array(), $errors);
        }
        $this->assertEquals($this->numfixusers * $this->numfixprogs, count($progcompletions));
    }

    /**
     * Test prog_fix_completions - ensure that records with the specified problem AND other problems are NOT fixed.
     *
     * We will use the fixassignedtimedueunknown fix key. This will set the due date to not set.
     */
    public function test_prog_fix_completions_only_if_isolated_problem() {
        global $DB;

        // Set up some data that is valid.
        $this->setup_fix_completions();
        $timecompleted = time();

        $windowopenprog = $this->fixprograms[8];
        $windowopenuser = $this->fixusers[2];

        // Break all the records - timedue.
        $sql = "UPDATE {prog_completion} SET timedue = 0 WHERE coursesetid = 0";
        $DB->execute($sql);

        // Break some records - timecompleted.
        $sql = "UPDATE {prog_completion}
                   SET timecompleted = 123456789
                 WHERE programid = :programid OR userid = :userid";
        $params = array('programid' => $windowopenprog->id, 'userid' => $windowopenuser->id);
        $DB->execute($sql, $params);

        $expectederrorstimedueonly = array(
            'error:timedueunknown' => 'timedue',
        );

        $expectederrorstimecompleted = array( // Same as above with one extra.
            'error:timedueunknown' => 'timedue',
            'error:stateincomplete-timecompletednotempty' => 'timecompleted',
        );

        // Check that all records are broken in the correct way.
        $progcompletions = $DB->get_records('prog_completion');
        foreach ($progcompletions as $progcompletion) {
            $errors = prog_get_completion_errors($progcompletion);
            if ($progcompletion->userid == $windowopenuser->id || $progcompletion->programid == $windowopenprog->id) {
                $this->assertEquals($expectederrorstimecompleted, $errors);
            } else {
                $this->assertEquals($expectederrorstimedueonly, $errors);
            }
        }
        $this->assertEquals($this->numfixusers * $this->numfixprogs, count($progcompletions));

        // Apply the fixassignedtimedueunknown fix to all users and progs, but won't affect those with timecompleted problem.
        prog_fix_completions('fixassignedtimedueunknown');

        // Check that the correct records have been fixed.
        $progcompletions = $DB->get_records('prog_completion');
        foreach ($progcompletions as $progcompletion) {
            $errors = prog_get_completion_errors($progcompletion);
            if ($progcompletion->userid == $windowopenuser->id || $progcompletion->programid == $windowopenprog->id) {
                $this->assertEquals($expectederrorstimecompleted, $errors);
            } else {
                $this->assertEquals(array(), $errors);
            }
        }
        $this->assertEquals($this->numfixusers * $this->numfixprogs, count($progcompletions));
    }

    /**
     * Test prog_fix_timedue. Set the program due date to COMPLETION_TIME_NOT_SET.
     */
    public function test_prog_fix_timedue() {
        // Expected record is incomplete.
        $expectedprogcompletion = new stdClass();
        $expectedprogcompletion->id = 1007;
        $expectedprogcompletion->programid = 1008;
        $expectedprogcompletion->userid = 1003;
        $expectedprogcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $expectedprogcompletion->timestarted = 0;
        $expectedprogcompletion->timedue = COMPLETION_TIME_NOT_SET;
        $expectedprogcompletion->timecompleted = 0;

        // Check that the expected test data is in a valid state.
        $errors = prog_get_completion_errors($expectedprogcompletion);
        $this->assertEquals(array(), $errors);

        $progcompletion = clone($expectedprogcompletion);

        // Change the record so that the program completion record is wrong.
        $progcompletion->timedue = COMPLETION_TIME_UNKNOWN;

        prog_fix_timedue($progcompletion);

        // Check that the record was changed as expected.
        $this->assertEquals($expectedprogcompletion, $progcompletion);
    }

    public function test_prog_format_log_date() {
        $this->assertEquals('Not set (null)', prog_format_log_date(null));
        $this->assertEquals('Not set ()', prog_format_log_date(''));
        $this->assertEquals('Not set ( )', prog_format_log_date(' '));
        $this->assertEquals('Not set (0)', prog_format_log_date(0));
        $this->assertEquals('Not set (0)', prog_format_log_date('0'));
        $this->assertEquals('Not set (-1)', prog_format_log_date(-1));
        $this->assertEquals('Not set (-1)', prog_format_log_date('-1'));
        $this->assertEquals('23 May 2017, 03:59 (1495511940)', prog_format_log_date(1495511940));
        $this->assertEquals('23 May 2017, 03:59 (1495511940)', prog_format_log_date('1495511940'));
    }

    public function test_prog_load_all_completions() {
        $generator = $this->getDataGenerator();
        $program_generator = \totara_program\testing\generator::instance();

        // Create some users.
        $user1 = $generator->create_user();
        $user2 = $generator->create_user();

        // Create some certs.
        $cert1 = $program_generator->create_certification();
        $cert2 = $program_generator->create_certification();

        // Create some programs.
        $prog1 = $program_generator->create_program();
        $prog2 = $program_generator->create_program();

        // Add the users to the certs.
        $program_generator->assign_to_program($cert1->id, ASSIGNTYPE_INDIVIDUAL, $user1->id, null, true);
        $program_generator->assign_to_program($cert1->id, ASSIGNTYPE_INDIVIDUAL, $user2->id, null, true);
        $program_generator->assign_to_program($cert2->id, ASSIGNTYPE_INDIVIDUAL, $user1->id, null, true);
        $program_generator->assign_to_program($cert2->id, ASSIGNTYPE_INDIVIDUAL, $user2->id, null, true);

        // Add the users to the programs.
        $program_generator->assign_to_program($prog1->id, ASSIGNTYPE_INDIVIDUAL, $user1->id, null, true);
        $program_generator->assign_to_program($prog1->id, ASSIGNTYPE_INDIVIDUAL, $user2->id, null, true);
        $program_generator->assign_to_program($prog2->id, ASSIGNTYPE_INDIVIDUAL, $user1->id, null, true);
        $program_generator->assign_to_program($prog2->id, ASSIGNTYPE_INDIVIDUAL, $user2->id, null, true);

        // Run the function and check the correct records are returned.
        $results = prog_load_all_completions($user1->id);

        // Make sure we've got two records. They're not the same because they're indexed by record id.
        $this->assertCount(2, $results);

        foreach ($results as $progcompletion) {
            // The record belongs to user1.
            $this->assertEquals($user1->id, $progcompletion->userid);

            // The record is not associated with either of the certs.
            $this->assertNotEquals($cert1->id, $progcompletion->programid);
            $this->assertNotEquals($cert2->id, $progcompletion->programid);

            // The prog record is valid - the results should be identical to prog_load_completion, which has
            // already been tested above.
            $expectedprogcompletion = prog_load_completion($progcompletion->programid, $user1->id);
            $this->assertEquals($expectedprogcompletion, $progcompletion);
        }
    }

    public function test_prog_conditionally_delete_completion() {
        global $DB;

        $generator = $this->getDataGenerator();
        /* @var \totara_plan\testing\generator $plangenerator */
        $plangenerator = $generator->get_plugin_generator('totara_plan');
        $program_generator = \totara_program\testing\generator::instance();

        // Create some users.
        $user1 = $generator->create_user();
        $user2 = $generator->create_user();

        // Create some certs.
        $cert1 = $program_generator->create_certification();
        $cert2 = $program_generator->create_certification();

        // Create some programs.
        $prog1 = $program_generator->create_program();
        $prog2 = $program_generator->create_program();
        $prog3 = $program_generator->create_program();
        $prog4 = $program_generator->create_program();

        // Add the users to the certs.
        $program_generator->assign_to_program($cert1->id, ASSIGNTYPE_INDIVIDUAL, $user1->id, null, true);
        $program_generator->assign_to_program($cert1->id, ASSIGNTYPE_INDIVIDUAL, $user2->id, null, true);
        $program_generator->assign_to_program($cert2->id, ASSIGNTYPE_INDIVIDUAL, $user1->id, null, true);
        $program_generator->assign_to_program($cert2->id, ASSIGNTYPE_INDIVIDUAL, $user2->id, null, true);

        // A prog assignment for program1 exists.
        $program_generator->assign_to_program($prog1->id, ASSIGNTYPE_INDIVIDUAL, $user1->id, null, true);
        $program_generator->assign_to_program($prog1->id, ASSIGNTYPE_INDIVIDUAL, $user2->id, null, true);

        // A LP assignment for program2 exists.
        $planrecord = $plangenerator->create_learning_plan(array('userid' => $user1->id));
        $plan = new development_plan($planrecord->id);
        $plan->set_status(DP_PLAN_STATUS_APPROVED);
        // Reload to get change in status.
        $plan = new development_plan($planrecord->id);
        /* @var dp_program_component $component_program */
        $user1lp1componentprogram = $plan->get_component('program');
        $user1lp1componentprogram->assign_new_item($prog2->id, false);

        $planrecord = $plangenerator->create_learning_plan(array('userid' => $user2->id));
        $plan = new development_plan($planrecord->id);
        $plan->set_status(DP_PLAN_STATUS_APPROVED);
        // Reload to get change in status.
        $plan = new development_plan($planrecord->id);
        /* @var dp_program_component $component_program */
        $user1lp1componentprogram = $plan->get_component('program');
        $user1lp1componentprogram->assign_new_item($prog2->id, false);

        // Program3 is complete but the user assignment no longer exists.
        $program_generator->assign_to_program($prog3->id, ASSIGNTYPE_INDIVIDUAL, $user1->id, null, true);
        $program_generator->assign_to_program($prog3->id, ASSIGNTYPE_INDIVIDUAL, $user2->id, null, true);
        $DB->delete_records('prog_user_assignment', array('programid' => $prog4->id));

        $progcompletion = prog_load_completion($prog3->id, $user1->id);
        $progcompletion->status = STATUS_PROGRAM_COMPLETE;
        $progcompletion->timecompleted = 100;
        $this->assertEquals(array(), prog_get_completion_errors($progcompletion));
        $this->assertTrue(prog_write_completion($progcompletion));

        $progcompletion = prog_load_completion($prog3->id, $user2->id);
        $progcompletion->status = STATUS_PROGRAM_COMPLETE;
        $progcompletion->timecompleted = 100;
        $this->assertEquals(array(), prog_get_completion_errors($progcompletion));
        $this->assertTrue(prog_write_completion($progcompletion));

        // Program4 has an incomplete prog_completion and the user is no longer assigned.
        $program_generator->assign_to_program($prog4->id, ASSIGNTYPE_INDIVIDUAL, $user1->id, null, true);
        $program_generator->assign_to_program($prog4->id, ASSIGNTYPE_INDIVIDUAL, $user2->id, null, true);
        $DB->delete_records('prog_user_assignment', array('programid' => $prog4->id));

        // Setup is all done. Now load the current set of data before we start running the function.
        $expectedcertcompletions = $DB->get_records('certif_completion');
        $expectedprogcompletions = $DB->get_records('prog_completion');

        // 1) Try deleting user1's program1 completion - nothing should happen.
        prog_conditionally_delete_completion($prog1->id, $user1->id);

        // The completion record still exists because the user has another assignment.
        $actualprogcompletions = $DB->get_records('prog_completion');
        $this->assertEquals($expectedprogcompletions, $actualprogcompletions);

        // 2) Try deleting user1's program2 completion - nothing should happen.
        prog_conditionally_delete_completion($prog2->id, $user1->id);

        // The completion record still exists because the program is in the user's LP.
        $actualprogcompletions = $DB->get_records('prog_completion');
        $this->assertEquals($expectedprogcompletions, $actualprogcompletions);

        // 3) Try deleting user1's program3 completion - nothing should happen.
        prog_conditionally_delete_completion($prog3->id, $user1->id);

        // The completion record still exists because it is complete.
        $actualprogcompletions = $DB->get_records('prog_completion');
        $this->assertEquals($expectedprogcompletions, $actualprogcompletions);

        // 4) Try deleting user1's program4 completion - the record should be deleted.
        prog_conditionally_delete_completion($prog4->id, $user1->id);

        // Manually make the same change to the expected data.
        foreach ($expectedprogcompletions as $key => $progcompletion) {
            if ($progcompletion->programid == $prog4->id && $progcompletion->userid == $user1->id) {
                unset($expectedprogcompletions[$key]);
            }
        }

        // And check that the expected records match the actual records.
        $actualprogcompletions = $DB->get_records('prog_completion');
        $this->assertEquals($expectedprogcompletions, $actualprogcompletions);

        // 5) Finally, just show that none of the certif_completion records were affected in any way.
        $actualcertcompletions = $DB->get_records('certif_completion');
        $this->assertEquals($expectedcertcompletions, $actualcertcompletions);
    }

    public function test_prog_delete_completion() {
        global $DB;

        $generator = $this->getDataGenerator();
        $program_generator = \totara_program\testing\generator::instance();

        // Create some users.
        $user1 = $generator->create_user();
        $user2 = $generator->create_user();

        // Create some certs.
        $cert1 = $program_generator->create_certification();
        $cert2 = $program_generator->create_certification();

        // Create some programs.
        $prog1 = $program_generator->create_program();
        $prog2 = $program_generator->create_program();

        // Add the users to the certs.
        $program_generator->assign_to_program($cert1->id, ASSIGNTYPE_INDIVIDUAL, $user1->id, null, true);
        $program_generator->assign_to_program($cert1->id, ASSIGNTYPE_INDIVIDUAL, $user2->id, null, true);
        $program_generator->assign_to_program($cert2->id, ASSIGNTYPE_INDIVIDUAL, $user1->id, null, true);
        $program_generator->assign_to_program($cert2->id, ASSIGNTYPE_INDIVIDUAL, $user2->id, null, true);

        // Add the users to the programs.
        $program_generator->assign_to_program($prog1->id, ASSIGNTYPE_INDIVIDUAL, $user1->id, null, true);
        $program_generator->assign_to_program($prog1->id, ASSIGNTYPE_INDIVIDUAL, $user2->id, null, true);
        $program_generator->assign_to_program($prog2->id, ASSIGNTYPE_INDIVIDUAL, $user1->id, null, true);
        $program_generator->assign_to_program($prog2->id, ASSIGNTYPE_INDIVIDUAL, $user2->id, null, true);

        // Load the current set of data.
        $expectedcertcompletions = $DB->get_records('certif_completion');
        $expectedprogcompletions = $DB->get_records('prog_completion');

        // Delete just one prog completion.
        prog_delete_completion($prog1->id, $user1->id);

        // Manually make the same change to the expected data. Only prog_completion is affected!!!
        foreach ($expectedprogcompletions as $key => $progcompletion) {
            if ($progcompletion->programid == $prog1->id && $progcompletion->userid == $user1->id) {
                unset($expectedprogcompletions[$key]);
            }
        }

        // Then just compare the current data with the expected.
        $actualcertcompletions = $DB->get_records('certif_completion');
        $actualprogcompletions = $DB->get_records('prog_completion');
        $this->assertEquals($expectedcertcompletions, $actualcertcompletions);
        $this->assertEquals($expectedprogcompletions, $actualprogcompletions);

        // Make sure that it still deletes the record if the user is complete.
        // We'll do this with a cert, to show that the cert record isn't touched.
        list($certcompletion, $progcompletion) = certif_load_completion($cert2->id, $user2->id);
        $certcompletion->status = CERTIFSTATUS_COMPLETED;
        $certcompletion->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $certcompletion->certifpath = CERTIFPATH_RECERT;
        $certcompletion->timecompleted = 100;
        $certcompletion->timewindowopens = 200;
        $certcompletion->timeexpires = 300;
        $certcompletion->baselinetimeexpires = 300;
        $progcompletion->status = STATUS_PROGRAM_COMPLETE;
        $progcompletion->timecompleted = 100;
        $progcompletion->timedue = 300;
        $this->assertEquals(array(), certif_get_completion_errors($certcompletion, $progcompletion));
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));

        // Load the current set of data.
        $expectedcertcompletions = $DB->get_records('certif_completion');
        $expectedprogcompletions = $DB->get_records('prog_completion');

        // Delete just one prog completion.
        prog_delete_completion($cert2->id, $user2->id);

        // Manually make the same change to the expected data. Only prog_completion is affected!!!
        foreach ($expectedprogcompletions as $key => $progcompletion) {
            if ($progcompletion->programid == $cert2->id && $progcompletion->userid == $user2->id) {
                unset($expectedprogcompletions[$key]);
            }
        }

        // Then just compare the current data with the expected.
        $actualcertcompletions = $DB->get_records('certif_completion');
        $actualprogcompletions = $DB->get_records('prog_completion');
        $this->assertEquals($expectedcertcompletions, $actualcertcompletions);
        $this->assertEquals($expectedprogcompletions, $actualprogcompletions);
    }

    /**
     * This checks that prog_display_progress returns the correct data given the various states of assignment within a program.
     * It does not exhaustively test that the correct progress is returned, e.g. based on course set progress.
     */
    public function test_prog_display_progress_assignment_with_program() {

        $generator = $this->getDataGenerator();
        $program_generator = \totara_program\testing\generator::instance();

        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $user3 = $generator->create_user();
        $user4 = $generator->create_user();

        $prog = $program_generator->create_program();

        // Test a user is assigned, incomplete.
        $program_generator->assign_to_program($prog->id, ASSIGNTYPE_INDIVIDUAL, $user1->id, null, true);
        $progcompletion = prog_load_completion($prog->id, $user1->id, true);
        $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status);
        $result = prog_display_progress($prog->id, $user1->id, CERTIFPATH_STD, true); // Export, so we just get a string.
        $this->assertEquals('0', $result);

        // Test a user is assigned, complete.
        $program_generator->assign_to_program($prog->id, ASSIGNTYPE_INDIVIDUAL, $user2->id, null, true);
        $progcompletion = prog_load_completion($prog->id, $user2->id, true);
        $progcompletion->status = STATUS_PROGRAM_COMPLETE;
        $progcompletion->timecompleted = time();
        $this->assertTrue(prog_write_completion($progcompletion));
        $result = prog_display_progress($prog->id, $user2->id, CERTIFPATH_STD, true); // Export, so we just get a string.
        $this->assertEquals('100', $result);

        // Test a user is not assigned, incomplete - just don't assign, user will have no prog_completion record.
        $progcompletion = prog_load_completion($prog->id, $user3->id, false);
        $this->assertEmpty($progcompletion);
        $result = prog_display_progress($prog->id, $user3->id, CERTIFPATH_STD, true); // Export, so we just get a string.
        $this->assertEquals('Not assigned', $result);

        // Test a user is not assigned, complete.
        $progcompletion = new stdClass();
        $progcompletion->programid = $prog->id;
        $progcompletion->userid = $user4->id;
        $progcompletion->status = STATUS_PROGRAM_COMPLETE;
        $progcompletion->timecompleted = time();
        $progcompletion->timedue = COMPLETION_TIME_NOT_SET;
        $this->assertTrue(prog_write_completion($progcompletion));
        $result = prog_display_progress($prog->id, $user4->id, CERTIFPATH_STD, true); // Export, so we just get a string.
        $this->assertEquals('100', $result); // !!! This is expected behaviour from this function. Maybe we should change it !!!
    }

    /**
     * This checks that prog_display_progress returns the correct data given the various states of assignment within a certification.
     * It does not exhaustively test that the correct progress is returned, e.g. based on course set progress.
     */
    public function test_prog_display_progress_assignment_with_certification() {
        global $DB;


        $now = time();

        $generator = $this->getDataGenerator();
        $program_generator = \totara_program\testing\generator::instance();

        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $user3 = $generator->create_user();
        $user4 = $generator->create_user();
        $user5 = $generator->create_user();
        $user6 = $generator->create_user();
        $user7 = $generator->create_user();
        $user8 = $generator->create_user();

        $cert = $program_generator->create_certification();

        // Test a user is assigned, newly assigned.
        $program_generator->assign_to_program($cert->id, ASSIGNTYPE_INDIVIDUAL, $user1->id, null, true);
        list($certcompletion, $progcompletion) = certif_load_completion($cert->id, $user1->id, true);
        $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status);
        $result = prog_display_progress($cert->id, $user1->id, $certcompletion->certifpath, true); // Export, so we just get a string.
        $this->assertEquals('0', $result);
        unset($user1);

        // Test a user is assigned, certified, before window opens.
        $program_generator->assign_to_program($cert->id, ASSIGNTYPE_INDIVIDUAL, $user2->id, null, true);
        list($certcompletion, $progcompletion) = certif_load_completion($cert->id, $user2->id, true);
        $progcompletion->status = STATUS_PROGRAM_COMPLETE;
        $progcompletion->timecompleted = $now;
        $progcompletion->timedue = $now + DAYSECS * 200;
        $certcompletion->status = CERTIFSTATUS_COMPLETED;
        $certcompletion->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $certcompletion->certifpath = CERTIFPATH_RECERT;
        $certcompletion->timecompleted = $now;
        $certcompletion->timewindowopens = $now + DAYSECS * 100;
        $certcompletion->timeexpires = $now + DAYSECS * 200;
        $certcompletion->baselinetimeexpires = $now + DAYSECS * 200;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        $result = prog_display_progress($cert->id, $user2->id, $certcompletion->certifpath, true); // Export, so we just get a string.
        $this->assertEquals('100', $result);
        unset($user2);

        // Test a user is assigned, certified, window has opened.
        $program_generator->assign_to_program($cert->id, ASSIGNTYPE_INDIVIDUAL, $user3->id, null, true);
        list($certcompletion, $progcompletion) = certif_load_completion($cert->id, $user3->id, true);
        $progcompletion->timedue = $now + DAYSECS * 200;
        $certcompletion->status = CERTIFSTATUS_COMPLETED;
        $certcompletion->renewalstatus = CERTIFRENEWALSTATUS_DUE;
        $certcompletion->certifpath = CERTIFPATH_RECERT;
        $certcompletion->timecompleted = $now;
        $certcompletion->timewindowopens = $now + DAYSECS * 100;
        $certcompletion->timeexpires = $now + DAYSECS * 200;
        $certcompletion->baselinetimeexpires = $now + DAYSECS * 200;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        $result = prog_display_progress($cert->id, $user3->id, $certcompletion->certifpath, true); // Export, so we just get a string.
        $this->assertEquals('0', $result);
        unset($user3);

        // Test a user is assigned, expired.
        $program_generator->assign_to_program($cert->id, ASSIGNTYPE_INDIVIDUAL, $user4->id, null, true);
        list($certcompletion, $progcompletion) = certif_load_completion($cert->id, $user4->id, true);
        $progcompletion->timedue = $now + DAYSECS * 200;
        $certcompletion->status = CERTIFSTATUS_EXPIRED;
        $certcompletion->renewalstatus = CERTIFRENEWALSTATUS_EXPIRED;
        $certcompletion->certifpath = CERTIFPATH_CERT;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        $result = prog_display_progress($cert->id, $user4->id, $certcompletion->certifpath, true); // Export, so we just get a string.
        $this->assertEquals('0', $result);
        unset($user4);

        // Test a user is not assigned, newly assigned - just don't assign, user will have no completion records.
        list($certcompletion, $progcompletion) = certif_load_completion($cert->id, $user5->id, false);
        $this->assertEmpty($certcompletion);
        $this->assertEmpty($progcompletion);
        $result = prog_display_progress($cert->id, $user5->id, CERTIFPATH_CERT, true); // Export, so we just get a string.
        $this->assertEquals('Not assigned', $result);
        unset($user5);

        // Test a user is not assigned, certified, before window opens.
        certif_create_completion($cert->id, $user6->id);
        list($certcompletion, $progcompletion) = certif_load_completion($cert->id, $user6->id, false);
        $progcompletion->status = STATUS_PROGRAM_COMPLETE;
        $progcompletion->timecompleted = $now;
        $progcompletion->timedue = $now + DAYSECS * 200;
        $certcompletion->status = CERTIFSTATUS_COMPLETED;
        $certcompletion->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $certcompletion->certifpath = CERTIFPATH_RECERT;
        $certcompletion->timecompleted = $now;
        $certcompletion->timewindowopens = $now + DAYSECS * 100;
        $certcompletion->timeexpires = $now + DAYSECS * 200;
        $certcompletion->baselinetimeexpires = $now + DAYSECS * 200;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        $DB->delete_records('prog_user_assignment');
        certif_conditionally_delete_completion($cert->id, $user6->id);
        $progcompletion = $DB->get_record('prog_completion', array('programid' => $cert->id, 'userid' => $user6->id, 'coursesetid' => 0));
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status); // Show that prog_completion still exists in correct state.
        list($certcompletion, $progcompletion) = certif_load_completion($cert->id, $user6->id, false);
        $this->assertEmpty($certcompletion); // Certif_load_completion needs both records to exist to work, so we know this record is gone.
        $this->assertEmpty($progcompletion);
        $result = prog_display_progress($cert->id, $user6->id, CERTIFPATH_RECERT, true); // Export, so we just get a string.
        $this->assertEquals('100', $result);
        unset($user6);

        // Test a user is not assigned, certified, before window opens.
        certif_create_completion($cert->id, $user7->id);
        list($certcompletion, $progcompletion) = certif_load_completion($cert->id, $user7->id, false);
        $progcompletion->timedue = $now + DAYSECS * 200;
        $certcompletion->status = CERTIFSTATUS_COMPLETED;
        $certcompletion->renewalstatus = CERTIFRENEWALSTATUS_DUE;
        $certcompletion->certifpath = CERTIFPATH_RECERT;
        $certcompletion->timecompleted = $now;
        $certcompletion->timewindowopens = $now + DAYSECS * 100;
        $certcompletion->timeexpires = $now + DAYSECS * 200;
        $certcompletion->baselinetimeexpires = $now + DAYSECS * 200;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        $DB->delete_records('prog_user_assignment');
        certif_conditionally_delete_completion($cert->id, $user7->id);
        $progcompletion = $DB->get_record('prog_completion', array('programid' => $cert->id, 'userid' => $user7->id, 'coursesetid' => 0));
        $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status); // Show that prog_completion still exists in correct state.
        list($certcompletion, $progcompletion) = certif_load_completion($cert->id, $user7->id, false);
        $this->assertEmpty($certcompletion); // Certif_load_completion needs both records to exist to work, so we know this record is gone.
        $this->assertEmpty($progcompletion);
        $result = prog_display_progress($cert->id, $user7->id, CERTIFPATH_RECERT, true); // Export, so we just get a string.
        $this->assertEquals('Not assigned', $result);
        unset($user7);

        // Test a user is not assigned, certified, before window opens.
        certif_create_completion($cert->id, $user8->id);
        list($certcompletion, $progcompletion) = certif_load_completion($cert->id, $user8->id, false);
        $progcompletion->timedue = $now + DAYSECS * 200;
        $certcompletion->status = CERTIFSTATUS_EXPIRED;
        $certcompletion->renewalstatus = CERTIFRENEWALSTATUS_EXPIRED;
        $certcompletion->certifpath = CERTIFPATH_CERT;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        $DB->delete_records('prog_user_assignment');
        certif_conditionally_delete_completion($cert->id, $user8->id);
        $progcompletion = $DB->get_record('prog_completion', array('programid' => $cert->id, 'userid' => $user8->id, 'coursesetid' => 0));
        $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status); // Show that prog_completion still exists in correct state.
        list($certcompletion, $progcompletion) = certif_load_completion($cert->id, $user8->id, false);
        $this->assertEmpty($certcompletion); // Certif_load_completion needs both records to exist to work, so we know this record is gone.
        $this->assertEmpty($progcompletion);
        $result = prog_display_progress($cert->id, $user8->id, CERTIFPATH_CERT, true); // Export, so we just get a string.
        $this->assertEquals('Not assigned', $result);
        unset($user8);
    }

    /**
     * Set up users, programs, certifications and assignments.
     */
    public function setup_completions() {

        $program_generator = \totara_program\testing\generator::instance();

        // Create users.
        for ($i = 1; $i <= $this->numtestusers; $i++) {
            $this->users[$i] = $this->getDataGenerator()->create_user();
        }

        // Create programs.
        for ($i = 1; $i <= $this->numtestprogs; $i++) {
            $this->programs[$i] = $program_generator->create_program();
        }

        // Create certifications, mostly so that we don't end up with coincidental success due to matching ids.
        for ($i = 1; $i <= $this->numtestcerts; $i++) {
            $this->certifications[$i] = $program_generator->create_certification();
        }

        // Assign users to the programs as individuals.
        foreach ($this->users as $user) {
            foreach ($this->programs as $prog) {
                $program_generator->assign_to_program($prog->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);
            }
        }

        // Assign users to the certifications as individuals.
        foreach ($this->users as $user) {
            foreach ($this->certifications as $prog) {
                $program_generator->assign_to_program($prog->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);
            }
        }
    }

    /**
     * Test prog_fix_missing_completions - ensure that the correct records are repaired.
     */
    public function test_prog_fix_missing_completions() {
        global $DB;

        // Set up some data that is valid.
        $this->setup_completions();
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);

        // Break all the records, progs and certs.
        $DB->delete_records('prog_completion', array('coursesetid' => 0));

        // Check that all of the records are broken, progs and certs.
        $expectedfixedcount = 0;
        $progcompletions = $DB->get_records('prog_completion', array('coursesetid' => 0));
        $this->assertCount($expectedfixedcount, $progcompletions);
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestprogs - $expectedfixedcount, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts, $fulllist);

        // Apply the fix to just one user/prog.
        prog_fix_missing_completions($this->programs[6]->id, $this->users[2]->id);

        // Check that the correct records have been fixed.
        $expectedfixedcount = 1; // One cell in the matrix.
        $progcompletions = $DB->get_records('prog_completion', array('coursesetid' => 0));
        $this->assertCount($expectedfixedcount, $progcompletions);
        foreach ($progcompletions as $progcompletion) {
            $this->assertEquals($this->programs[6]->id, $progcompletion->programid);
            $this->assertEquals($this->users[2]->id, $progcompletion->userid);
        }
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestprogs - $expectedfixedcount, $fulllist);

        // Apply the fix to just one user, all progs (don't need to reset, just overlap).
        prog_fix_missing_completions(0, $this->users[2]->id);

        // Check that the correct records have been fixed.
        $expectedfixedcount = $this->numtestprogs; // One column in the matrix.
        $progcompletions = $DB->get_records('prog_completion', array('coursesetid' => 0));
        $this->assertCount($expectedfixedcount, $progcompletions);
        foreach ($progcompletions as $progcompletion) {
            $this->assertEquals($this->users[2]->id, $progcompletion->userid);
        }
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestprogs - $expectedfixedcount, $fulllist);

        // Apply the fix to just one prog, all users (don't need to reset, just overlap).
        prog_fix_missing_completions($this->programs[6]->id, 0);

        // Check that the correct records have been fixed.
        $expectedfixedcount = $this->numtestprogs + $this->numtestusers - 1; // One column and one row in the matrix.
        $progcompletions = $DB->get_records('prog_completion', array('coursesetid' => 0));
        $this->assertCount($expectedfixedcount, $progcompletions);
        foreach ($progcompletions as $progcompletion) {
            $this->assertTrue($this->programs[6]->id == $progcompletion->programid ||
                $this->users[2]->id == $progcompletion->userid);
        }
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestprogs - $expectedfixedcount, $fulllist);

        // Apply the fix to all records (overlaps previous fixes).
        prog_fix_missing_completions(0, 0);

        // Check that the correct records have been fixed.
        $expectedfixedcount = $this->numtestprogs * $this->numtestusers; // The whole matrix.
        $progcompletions = $DB->get_records('prog_completion', array('coursesetid' => 0));
        $this->assertCount($expectedfixedcount, $progcompletions);
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestprogs - $expectedfixedcount, $fulllist);

        // Make sure that no certs were fixed.
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts, $fulllist);
    }

    /**
     * Test prog_fix_unassigned_incomplete_completions - ensure that the correct records are repaired.
     */
    public function test_prog_fix_unassigned_incomplete_completions() {
        global $DB;

        // Set up some data that is valid.
        $this->setup_completions();
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);

        // Break all the records, progs and certs.
        $DB->delete_records('prog_user_assignment');

        // Check that all of the records are broken, progs and certs.
        $expectedfixedcount = 0;
        $progcompletions = $DB->get_records('prog_completion', array('coursesetid' => 0));
        $this->assertCount($this->numtestusers * ($this->numtestprogs + $this->numtestcerts) - $expectedfixedcount, $progcompletions);
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestprogs - $expectedfixedcount, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts, $fulllist);

        // Apply the fix to just one user/prog.
        prog_fix_unassigned_incomplete_completions($this->programs[6]->id, $this->users[2]->id);

        // Check that the correct records have been fixed.
        $expectedfixedcount = 1; // One cell in the matrix.
        $progcompletions = $DB->get_records('prog_completion', array('coursesetid' => 0));
        $this->assertCount($this->numtestusers * ($this->numtestprogs + $this->numtestcerts) - $expectedfixedcount, $progcompletions);
        foreach ($progcompletions as $progcompletion) {
            $this->assertTrue($this->programs[6]->id != $progcompletion->programid || $this->users[2]->id != $progcompletion->userid);
        }
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestprogs - $expectedfixedcount, $fulllist);

        // Apply the fix to just one user, all progs (don't need to reset, just overlap).
        prog_fix_unassigned_incomplete_completions(0, $this->users[2]->id);

        // Check that the correct records have been fixed.
        $expectedfixedcount = $this->numtestprogs; // One column in the matrix.
        $sql = "SELECT pc.*, p.certifid FROM {prog_completion} pc JOIN {prog} p ON p.id = pc.programid WHERE pc.coursesetid = 0";
        $progcompletions = $DB->get_records_sql($sql);
        $this->assertCount($this->numtestusers * ($this->numtestprogs + $this->numtestcerts) - $expectedfixedcount, $progcompletions);
        foreach ($progcompletions as $progcompletion) {
            if (empty($progcompletion->certifid)) {
                $this->assertTrue($this->users[2]->id != $progcompletion->userid);
            }
        }
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestprogs - $expectedfixedcount, $fulllist);

        // Apply the fix to just one prog, all users (don't need to reset, just overlap).
        prog_fix_unassigned_incomplete_completions($this->programs[6]->id, 0);

        // Check that the correct records have been fixed.
        $expectedfixedcount = $this->numtestprogs + $this->numtestusers - 1; // One column and one row in the matrix.
        $sql = "SELECT pc.*, p.certifid FROM {prog_completion} pc JOIN {prog} p ON p.id = pc.programid WHERE pc.coursesetid = 0";
        $progcompletions = $DB->get_records_sql($sql);
        $this->assertCount($this->numtestusers * ($this->numtestprogs + $this->numtestcerts) - $expectedfixedcount, $progcompletions);
        foreach ($progcompletions as $progcompletion) {
            if (empty($progcompletion->certifid)) {
                $this->assertTrue($this->programs[6]->id != $progcompletion->programid &&
                    $this->users[2]->id != $progcompletion->userid);
            }
        }
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestprogs - $expectedfixedcount, $fulllist);

        // Apply the fix to all records (overlaps previous fixes).
        prog_fix_unassigned_incomplete_completions(0, 0);

        // Check that the correct records have been fixed.
        $expectedfixedcount = $this->numtestprogs * $this->numtestusers; // The whole matrix.
        $progcompletions = $DB->get_records('prog_completion', array('coursesetid' => 0));
        $this->assertCount($this->numtestusers * ($this->numtestprogs + $this->numtestcerts) - $expectedfixedcount, $progcompletions);
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);

        // Make sure that no certs were fixed.
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts, $fulllist);
    }

    /**
     * Test prog_fix_orphaned_exceptions_assign - ensure that the correct records are repaired.
     */
    public function test_prog_fix_orphaned_exceptions_assign_with_programs() {
        global $DB;

        // Set up some data that is valid.
        $this->setup_completions();
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);

        // Break all the records, progs and certs.
        $DB->set_field('prog_user_assignment', 'exceptionstatus', PROGRAM_EXCEPTION_RAISED);

        // Check that all of the records are broken, progs and certs.
        $expectedfixedcount = 0;
        $this->assertEquals($expectedfixedcount,
            $DB->count_records('prog_user_assignment', array('exceptionstatus' => PROGRAM_EXCEPTION_RESOLVED)));
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestprogs - $expectedfixedcount, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts, $fulllist);

        // Apply the fix to just one user/prog.
        prog_fix_orphaned_exceptions_assign($this->programs[6]->id, $this->users[2]->id, 'program');

        // Check that the correct records have been fixed.
        $expectedfixedcount = 1; // One cell in the matrix.
        $this->assertEquals($expectedfixedcount,
            $DB->count_records('prog_user_assignment', array('exceptionstatus' => PROGRAM_EXCEPTION_RESOLVED)));
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestprogs - $expectedfixedcount, $fulllist);

        // Apply the fix to just one user, all progs (don't need to reset, just overlap).
        prog_fix_orphaned_exceptions_assign(0, $this->users[2]->id, 'program');

        // Check that the correct records have been fixed.
        $expectedfixedcount = $this->numtestprogs; // One column in the matrix.
        $this->assertEquals($expectedfixedcount,
            $DB->count_records('prog_user_assignment', array('exceptionstatus' => PROGRAM_EXCEPTION_RESOLVED)));
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestprogs - $expectedfixedcount, $fulllist);

        // Apply the fix to just one prog, all users (don't need to reset, just overlap).
        prog_fix_orphaned_exceptions_assign($this->programs[6]->id, 0, 'program');

        // Check that the correct records have been fixed.
        $expectedfixedcount = $this->numtestprogs + $this->numtestusers - 1; // One column and one row in the matrix.
        $this->assertEquals($expectedfixedcount,
            $DB->count_records('prog_user_assignment', array('exceptionstatus' => PROGRAM_EXCEPTION_RESOLVED)));
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestprogs - $expectedfixedcount, $fulllist);

        // Apply the fix to all records (overlaps previous fixes).
        prog_fix_orphaned_exceptions_assign(0, 0, 'program');

        // Check that the correct records have been fixed.
        $expectedfixedcount = $this->numtestprogs * $this->numtestusers; // The whole matrix.
        $this->assertEquals($expectedfixedcount,
            $DB->count_records('prog_user_assignment', array('exceptionstatus' => PROGRAM_EXCEPTION_RESOLVED)));
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);

        // Make sure that no certs were fixed.
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts, $fulllist);
    }

    /**
     * Test prog_fix_orphaned_exceptions_assign - ensure that the correct records are repaired.
     */
    public function test_prog_fix_orphaned_exceptions_assign_with_certifications() {
        global $DB;

        // Set up some data that is valid.
        $this->setup_completions();
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);

        // Break all the records, progs and certs.
        $DB->set_field('prog_user_assignment', 'exceptionstatus', PROGRAM_EXCEPTION_RAISED);

        // Check that all of the records are broken, progs and certs.
        $expectedfixedcount = 0;
        $this->assertEquals($expectedfixedcount,
            $DB->count_records('prog_user_assignment', array('exceptionstatus' => PROGRAM_EXCEPTION_RESOLVED)));
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestprogs - $expectedfixedcount, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts, $fulllist);

        // Apply the fix to just one user/cert.
        prog_fix_orphaned_exceptions_assign($this->certifications[6]->id, $this->users[2]->id, 'certification');

        // Check that the correct records have been fixed.
        $expectedfixedcount = 1; // One cell in the matrix.
        $this->assertEquals($expectedfixedcount,
            $DB->count_records('prog_user_assignment', array('exceptionstatus' => PROGRAM_EXCEPTION_RESOLVED)));
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $fulllist);

        // Apply the fix to just one user, all certs (don't need to reset, just overlap).
        prog_fix_orphaned_exceptions_assign(0, $this->users[2]->id, 'certification');

        // Check that the correct records have been fixed.
        $expectedfixedcount = $this->numtestcerts; // One column in the matrix.
        $this->assertEquals($expectedfixedcount,
            $DB->count_records('prog_user_assignment', array('exceptionstatus' => PROGRAM_EXCEPTION_RESOLVED)));
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $fulllist);

        // Apply the fix to just one cert, all users (don't need to reset, just overlap).
        prog_fix_orphaned_exceptions_assign($this->certifications[6]->id, 0, 'certification');

        // Check that the correct records have been fixed.
        $expectedfixedcount = $this->numtestcerts + $this->numtestusers - 1; // One column and one row in the matrix.
        $this->assertEquals($expectedfixedcount,
            $DB->count_records('prog_user_assignment', array('exceptionstatus' => PROGRAM_EXCEPTION_RESOLVED)));
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $fulllist);

        // Apply the fix to all records (overlaps previous fixes).
        prog_fix_orphaned_exceptions_assign(0, 0, 'certification');

        // Check that the correct records have been fixed.
        $expectedfixedcount = $this->numtestcerts * $this->numtestusers; // The whole matrix.
        $this->assertEquals($expectedfixedcount,
            $DB->count_records('prog_user_assignment', array('exceptionstatus' => PROGRAM_EXCEPTION_RESOLVED)));
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);

        // Make sure that no progs were fixed.
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestprogs, $fulllist);
    }

    /**
     * Test prog_fix_orphaned_exceptions_recalculate - ensure that the correct records are repaired.
     */
    public function test_prog_fix_orphaned_exceptions_recalculate_with_programs() {
        global $DB;

        // Set up some data that is valid.
        $this->setup_completions();
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);

        // Break all the records, progs and certs.
        $DB->set_field('prog_user_assignment', 'exceptionstatus', PROGRAM_EXCEPTION_RAISED);

        // Check that all of the records are broken, progs and certs.
        $expectedfixedcount = 0;
        $this->assertEquals($expectedfixedcount,
            $DB->count_records('prog_user_assignment', array('exceptionstatus' => PROGRAM_EXCEPTION_NONE)));
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestprogs - $expectedfixedcount, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts, $fulllist);

        // Apply the fix to just one user/prog.
        prog_fix_orphaned_exceptions_recalculate($this->programs[6]->id, $this->users[2]->id, 'program');

        // Check that the correct records have been fixed.
        $expectedfixedcount = 1; // One cell in the matrix.
        $this->assertEquals($expectedfixedcount,
            $DB->count_records('prog_user_assignment', array('exceptionstatus' => PROGRAM_EXCEPTION_NONE)));
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestprogs - $expectedfixedcount, $fulllist);

        // Apply the fix to just one user, all progs (don't need to reset, just overlap).
        prog_fix_orphaned_exceptions_recalculate(0, $this->users[2]->id, 'program');

        // Check that the correct records have been fixed.
        $expectedfixedcount = $this->numtestprogs; // One column in the matrix.
        $this->assertEquals($expectedfixedcount,
            $DB->count_records('prog_user_assignment', array('exceptionstatus' => PROGRAM_EXCEPTION_NONE)));
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestprogs - $expectedfixedcount, $fulllist);

        // Apply the fix to just one prog, all users (don't need to reset, just overlap).
        prog_fix_orphaned_exceptions_recalculate($this->programs[6]->id, 0, 'program');

        // Check that the correct records have been fixed.
        $expectedfixedcount = $this->numtestprogs + $this->numtestusers - 1; // One column and one row in the matrix.
        $this->assertEquals($expectedfixedcount,
            $DB->count_records('prog_user_assignment', array('exceptionstatus' => PROGRAM_EXCEPTION_NONE)));
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestprogs - $expectedfixedcount, $fulllist);

        // Apply the fix to all records (overlaps previous fixes).
        prog_fix_orphaned_exceptions_recalculate(0, 0, 'program');

        // Check that the correct records have been fixed.
        $expectedfixedcount = $this->numtestprogs * $this->numtestusers; // The whole matrix.
        $this->assertEquals($expectedfixedcount,
            $DB->count_records('prog_user_assignment', array('exceptionstatus' => PROGRAM_EXCEPTION_NONE)));
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);

        // Make sure that no certs were fixed.
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts, $fulllist);
    }

    /**
     * Test prog_fix_orphaned_exceptions - ensure that the correct records are repaired.
     */
    public function test_prog_fix_orphaned_exceptions_recalculate_with_certifications() {
        global $DB;

        // Set up some data that is valid.
        $this->setup_completions();
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);

        // Break all the records, progs and certs.
        $DB->set_field('prog_user_assignment', 'exceptionstatus', PROGRAM_EXCEPTION_RAISED);

        // Check that all of the records are broken, progs and certs.
        $expectedfixedcount = 0;
        $this->assertEquals($expectedfixedcount,
            $DB->count_records('prog_user_assignment', array('exceptionstatus' => PROGRAM_EXCEPTION_NONE)));
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestprogs - $expectedfixedcount, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts, $fulllist);

        // Apply the fix to just one user/cert.
        prog_fix_orphaned_exceptions_recalculate($this->certifications[6]->id, $this->users[2]->id, 'certification');

        // Check that the correct records have been fixed.
        $expectedfixedcount = 1; // One cell in the matrix.
        $this->assertEquals($expectedfixedcount,
            $DB->count_records('prog_user_assignment', array('exceptionstatus' => PROGRAM_EXCEPTION_NONE)));
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $fulllist);

        // Apply the fix to just one user, all certs (don't need to reset, just overlap).
        prog_fix_orphaned_exceptions_recalculate(0, $this->users[2]->id, 'certification');

        // Check that the correct records have been fixed.
        $expectedfixedcount = $this->numtestcerts; // One column in the matrix.
        $this->assertEquals($expectedfixedcount,
            $DB->count_records('prog_user_assignment', array('exceptionstatus' => PROGRAM_EXCEPTION_NONE)));
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $fulllist);

        // Apply the fix to just one cert, all users (don't need to reset, just overlap).
        prog_fix_orphaned_exceptions_recalculate($this->certifications[6]->id, 0, 'certification');

        // Check that the correct records have been fixed.
        $expectedfixedcount = $this->numtestcerts + $this->numtestusers - 1; // One column and one row in the matrix.
        $this->assertEquals($expectedfixedcount,
            $DB->count_records('prog_user_assignment', array('exceptionstatus' => PROGRAM_EXCEPTION_NONE)));
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $fulllist);

        // Apply the fix to all records (overlaps previous fixes).
        prog_fix_orphaned_exceptions_recalculate(0, 0, 'certification');

        // Check that the correct records have been fixed.
        $expectedfixedcount = $this->numtestcerts * $this->numtestusers; // The whole matrix.
        $this->assertEquals($expectedfixedcount,
            $DB->count_records('prog_user_assignment', array('exceptionstatus' => PROGRAM_EXCEPTION_NONE)));
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);

        // Make sure that no progs were fixed.
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestprogs, $fulllist);
    }

    /**
     * Test prog_fix_orphaned_exceptions_recalculate - ensure that the correct records have new, valid exceptions.
     */
    public function test_prog_fix_orphaned_exceptions_recalculate_with_exceptions() {
        global $DB;

        // Set up some data that is valid.
        $this->setup_completions();
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);

        // Break all the records, progs and certs.
        $DB->set_field('prog_user_assignment', 'exceptionstatus', PROGRAM_EXCEPTION_RAISED);
        $totalraised = $DB->count_records('prog_user_assignment', array('exceptionstatus' => PROGRAM_EXCEPTION_RAISED));
        $DB->set_field('prog_completion', 'timedue', time() - DAYSECS * 10, array('coursesetid' => 0));

        // Check that all of the records are broken, progs and certs.
        $expectedfixedcount = 0;
        $this->assertEquals($expectedfixedcount,
            $DB->count_records('prog_user_assignment', array('exceptionstatus' => PROGRAM_EXCEPTION_NONE)));
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestprogs - $expectedfixedcount, $fulllist);
        $this->assertEquals($this->numtestusers * $this->numtestprogs, $totalcount);
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts, $fulllist);
        $this->assertEquals($this->numtestusers * $this->numtestcerts, $totalcount);
        $this->assertEquals(0, $DB->count_records('prog_exception')); // This is the important bit.

        // Apply the fix to just one user/prog.
        prog_fix_orphaned_exceptions_recalculate($this->programs[6]->id, $this->users[2]->id, 'program');

        // Check that the correct records have been fixed.
        $expectedfixedcount = 1; // One cell in the matrix.
        $this->assertEquals($totalraised,
            $DB->count_records('prog_user_assignment', array('exceptionstatus' => PROGRAM_EXCEPTION_RAISED)));
        $this->assertEquals($expectedfixedcount, $DB->count_records('prog_exception'));
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestprogs - $expectedfixedcount, $fulllist);

        // Apply the fix to just one user, all progs (don't need to reset, just overlap).
        prog_fix_orphaned_exceptions_recalculate(0, $this->users[2]->id, 'program');

        // Check that the correct records have been fixed.
        $expectedfixedcount = $this->numtestprogs; // One column in the matrix.
        $this->assertEquals($totalraised,
            $DB->count_records('prog_user_assignment', array('exceptionstatus' => PROGRAM_EXCEPTION_RAISED)));
        $this->assertEquals($expectedfixedcount, $DB->count_records('prog_exception'));
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestprogs - $expectedfixedcount, $fulllist);

        // Apply the fix to just one prog, all users (don't need to reset, just overlap).
        prog_fix_orphaned_exceptions_recalculate($this->programs[6]->id, 0, 'program');

        // Check that the correct records have been fixed.
        $expectedfixedcount = $this->numtestprogs + $this->numtestusers - 1; // One column and one row in the matrix.
        $this->assertEquals($totalraised,
            $DB->count_records('prog_user_assignment', array('exceptionstatus' => PROGRAM_EXCEPTION_RAISED)));
        $this->assertEquals($expectedfixedcount, $DB->count_records('prog_exception'));
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestprogs - $expectedfixedcount, $fulllist);

        // Apply the fix to all records (overlaps previous fixes).
        prog_fix_orphaned_exceptions_recalculate(0, 0, 'program');

        // Check that the correct records have been fixed.
        $expectedfixedcount = $this->numtestprogs * $this->numtestusers; // The whole matrix.
        $this->assertEquals($totalraised,
            $DB->count_records('prog_user_assignment', array('exceptionstatus' => PROGRAM_EXCEPTION_RAISED)));
        $this->assertEquals($expectedfixedcount, $DB->count_records('prog_exception'));
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);

        // Make sure that no certs were fixed.
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts, $fulllist);
    }

    public function test_prog_create_completion() {
        global $DB;


        $program_generator = \totara_program\testing\generator::instance();

        $user = $this->getDataGenerator()->create_user();
        $program = $program_generator->create_program();

        // Test defaults.
        $timebefore = time();
        $this->assertTrue(prog_create_completion($program->id, $user->id));
        $timeafter = time();

        $progcompletion = prog_load_completion($program->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status);
        $this->assertGreaterThanOrEqual($timebefore, $progcompletion->timecreated);
        $this->assertLessThanOrEqual($timeafter, $progcompletion->timecreated);
        $this->assertEquals(COMPLETION_TIME_NOT_SET, $progcompletion->timedue);
        $this->assertEquals(0, $progcompletion->timecompleted);
        $this->assertGreaterThanOrEqual($timebefore, $progcompletion->timestarted);
        $this->assertLessThanOrEqual($timeafter, $progcompletion->timestarted);

        // Check the log.
        $latestlog = $DB->get_records('prog_completion_log', array(), 'id DESC', '*', 0, 1);
        $latestlog = reset($latestlog);
        $this->assertEquals($program->id, $latestlog->programid);
        $this->assertEquals($user->id, $latestlog->userid);
        $this->assertStringStartsWith('Completion record created', $latestlog->description);

        // Shift the prog_completion record to a non-zero courseset, then try creating the record again, to see that
        // having a non-zero courseset record does not interfere.
        $DB->set_field('prog_completion', 'coursesetid', 789,
            array('programid' => $program->id, 'userid' => $user->id, 'coursesetid' => 0));

        // Check that nothing happens if the record already exists.
        $progcompletion->status = 123;
        $progcompletion->timestarted = 234;
        $progcompletion->timedue = 345;
        $progcompletion->timecompleted = 456;
        $DB->update_record('prog_completion', $progcompletion); // Make the existing record unique so we will know it is unchanged.
        prog_create_completion($program->id, $user->id);
        $newprogcompletion = prog_load_completion($program->id, $user->id);
        $this->assertEquals($progcompletion, $newprogcompletion);

        // Check that no new log has been created.
        $newlatestlog = $DB->get_records('prog_completion_log', array(), 'id DESC', '*', 0, 1);
        $newlatestlog = reset($newlatestlog);
        $this->assertEquals($latestlog, $newlatestlog);

        // Delete completion
        prog_delete_completion($program->id, $user->id);

        // Test providing data with invalid fields.
        $data = array(
            'dodgyfield' => 789,
        );
        $this->assertFalse(prog_create_completion($program->id, $user->id, $data));

        $progcompletion = prog_load_completion($program->id, $user->id, false);
        $this->assertTrue($progcompletion === false);

        // Test providing data with only valid fields.
        $data = array(
            'status' => STATUS_PROGRAM_COMPLETE,
            'timestarted' => 123,
            'timecreated' => 234,
            'timedue' => 345,
            'timecompleted' => 456,
            'organisationid' => 567,
            'positionid' => 678,
        );
        $this->assertTrue(prog_create_completion($program->id, $user->id, $data));

        $progcompletion = prog_load_completion($program->id, $user->id);
        $this->assertEquals($data['status'], $progcompletion->status);
        $this->assertEquals($data['timestarted'], $progcompletion->timestarted);
        $this->assertEquals($data['timecreated'], $progcompletion->timecreated);
        $this->assertEquals($data['timedue'], $progcompletion->timedue);
        $this->assertEquals($data['timecompleted'], $progcompletion->timecompleted);
        $this->assertEquals($data['organisationid'], $progcompletion->organisationid);
        $this->assertEquals($data['positionid'], $progcompletion->positionid);

        // Check that we get an exception if we try to do it with a certification.
        $certification = $program_generator->create_certification();

        try {
            prog_create_completion($certification->id, $user->id);
            $this->fail('Expected exception!');
        } catch (coding_exception $e) {
            $this->assertEquals('Coding error detected, it must be fixed by a programmer: Tried to create a prog_completion record for a certification using prog_create_completion',
                $e->getMessage());
        }
    }

    public function test_prog_create_courseset_completion() {
        global $DB;


        $generator_program = \totara_program\testing\generator::instance();

        $user = $this->getDataGenerator()->create_user();
        $program = $generator_program->create_program();
        $coursesetdata = array(
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_CERT,
                'timeallowed' => 123123,
                'courses' => array($this->getDataGenerator()->create_course()),
            ),
        );
        $generator_program->legacy_add_coursesets_to_program($program, $coursesetdata);
        $coursesetid = $DB->get_field('prog_courseset', 'id', array('programid' => $program->id));

        // Test defaults.
        $timebefore = time();
        $this->assertTrue(prog_create_courseset_completion($coursesetid, $user->id));
        $timeafter = time();

        $cscompletion = prog_load_courseset_completion($coursesetid, $user->id);
        $this->assertEquals(STATUS_COURSESET_INCOMPLETE, $cscompletion->status);
        $this->assertGreaterThanOrEqual($timebefore, $cscompletion->timecreated);
        $this->assertLessThanOrEqual($timeafter, $cscompletion->timecreated);
        $this->assertEquals(COMPLETION_TIME_NOT_SET, $cscompletion->timedue);
        $this->assertEquals(0, $cscompletion->timecompleted);

        $DB->delete_records('prog_completion', array('programid' => $program->id, 'userid' => $user->id));

        // Test providing data with invalid fields.
        $data = array(
            'dodgyfield' => 789,
        );
        $this->assertFalse(prog_create_courseset_completion($coursesetid, $user->id, $data));

        $cscompletion = prog_load_courseset_completion($coursesetid, $user->id, false);
        $this->assertTrue($cscompletion === false);

        // Test providing data with only valid fields.
        $data = array(
            'status' => STATUS_COURSESET_COMPLETE,
            'timestarted' => 123,
            'timecreated' => 234,
            'timedue' => 345,
            'timecompleted' => 456,
            'organisationid' => 567,
            'positionid' => 678,
        );
        $this->assertTrue(prog_create_courseset_completion($coursesetid, $user->id, $data));

        $cscompletion = prog_load_courseset_completion($coursesetid, $user->id);
        $this->assertEquals($data['status'], $cscompletion->status);
        $this->assertEquals($data['timestarted'], $cscompletion->timestarted);
        $this->assertEquals($data['timecreated'], $cscompletion->timecreated);
        $this->assertEquals($data['timedue'], $cscompletion->timedue);
        $this->assertEquals($data['timecompleted'], $cscompletion->timecompleted);
        $this->assertEquals($data['organisationid'], $cscompletion->organisationid);
        $this->assertEquals($data['positionid'], $cscompletion->positionid);
    }

    public function test_prog_find_missing_completions_with_program_assignments() {
        global $DB;

        // Set up some data that is valid.
        $this->setup_completions();
        $this->assert_count_and_close_recordset(0, prog_find_missing_completions());
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);

        // Break all the records, progs and certs.
        $DB->delete_records('prog_completion', array('coursesetid' => 0));

        // Check that all of the records are broken, progs and certs.
        $this->assert_count_and_close_recordset($this->numtestusers * $this->numtestprogs, prog_find_missing_completions());
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestprogs, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts, $fulllist);

        // Apply the fix to just one user, all progs.
        prog_fix_missing_completions(0, $this->users[2]->id);

        // Apply the fix to just one prog, all users (overlapping).
        prog_fix_missing_completions($this->programs[6]->id, 0);

        // Test the function is returning the correct records when no user or program is specified.
        $expectedfixedcount = $this->numtestusers * $this->numtestprogs - $this->numtestprogs - $this->numtestusers + 1;
        $this->assert_count_and_close_recordset($expectedfixedcount, prog_find_missing_completions());

        // Test the function is returning the correct records when just the program is specified.
        $this->assert_count_and_close_recordset($this->numtestusers - 1, prog_find_missing_completions($this->programs[4]->id, 0));
        $this->assert_count_and_close_recordset(0, prog_find_missing_completions($this->programs[6]->id, 0)); // All were fixed.

        // Test the function is returning the correct records when just the user is specified.
        $this->assert_count_and_close_recordset($this->numtestprogs - 1, prog_find_missing_completions(0, $this->users[7]->id));
        $this->assert_count_and_close_recordset(0, prog_find_missing_completions(0, $this->users[2]->id)); // All were fixed.

        // Test the function is returning the correct records when program and user are specified.
        $this->assert_count_and_close_recordset(1, prog_find_missing_completions($this->programs[4]->id, $this->users[6]->id));
        $this->assert_count_and_close_recordset(0, prog_find_missing_completions($this->programs[6]->id, $this->users[3]->id));
        $this->assert_count_and_close_recordset(0, prog_find_missing_completions($this->programs[5]->id, $this->users[2]->id));
        $this->assert_count_and_close_recordset(0, prog_find_missing_completions($this->programs[6]->id, $this->users[2]->id));
    }

    public function test_prog_find_missing_completions_with_learning_plans() {
        global $DB;


        $generator_program = \totara_program\testing\generator::instance();

        // Set up a program in a user's plan.
        $user = $this->getDataGenerator()->create_user();
        $plangenerator = $this->getDataGenerator()->get_plugin_generator('totara_plan');
        $planrecord = $plangenerator->create_learning_plan(array('userid' => $user->id));
        $plan = new development_plan($planrecord->id);
        $program = $generator_program->create_program();
        $this->setAdminUser(); // Stupid access control.
        $plangenerator->add_learning_plan_program($plan->id, $program->id);

        // Break the completion.
        $DB->delete_records('prog_completion', array('coursesetid' => 0));

        // Run the function to make sure it can find the problem.
        $this->assert_count_and_close_recordset(1, prog_find_missing_completions());

        // Fix the problem.
        prog_fix_missing_completions(0, 0);

        // Run the function to make sure it doesn't find the problem.
        $this->assert_count_and_close_recordset(0, prog_find_missing_completions());
    }

    public function test_prog_find_unassigned_incomplete_completions_with_program_assignments() {
        global $DB;

        // Set up some data that is valid.
        $this->setup_completions();

        // Show that existing prog_user_assignments will prevent the prog_completions from being reported as broken.
        $this->assert_count_and_close_recordset(0, prog_find_unassigned_incomplete_completions());
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);

        // Break all the records, progs and certs.
        $DB->delete_records('prog_user_assignment');

        // Check that all of the records are broken, progs and certs.
        $this->assert_count_and_close_recordset($this->numtestusers * $this->numtestprogs, prog_find_unassigned_incomplete_completions());
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestprogs, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts, $fulllist);

        // Apply the fix to just one user, all progs.
        prog_fix_unassigned_incomplete_completions(0, $this->users[2]->id);

        // Apply the fix to just one prog, all users (overlapping).
        prog_fix_unassigned_incomplete_completions($this->programs[6]->id, 0);

        // Test the function is returning the correct records when no user or program is specified.
        $expectedfixedcount = $this->numtestusers * $this->numtestprogs - $this->numtestprogs - $this->numtestusers + 1;
        $this->assert_count_and_close_recordset($expectedfixedcount, prog_find_unassigned_incomplete_completions());

        // Test the function is returning the correct records when just the program is specified.
        $this->assert_count_and_close_recordset($this->numtestusers - 1, prog_find_unassigned_incomplete_completions($this->programs[4]->id, 0));
        $this->assert_count_and_close_recordset(0, prog_find_unassigned_incomplete_completions($this->programs[6]->id, 0)); // All were fixed.

        // Test the function is returning the correct records when just the user is specified.
        $this->assert_count_and_close_recordset($this->numtestprogs - 1, prog_find_unassigned_incomplete_completions(0, $this->users[7]->id));
        $this->assert_count_and_close_recordset(0, prog_find_unassigned_incomplete_completions(0, $this->users[2]->id)); // All were fixed.

        // Test the function is returning the correct records when program and user are specified.
        $this->assert_count_and_close_recordset(1, prog_find_unassigned_incomplete_completions($this->programs[4]->id, $this->users[6]->id));
        $this->assert_count_and_close_recordset(0, prog_find_unassigned_incomplete_completions($this->programs[6]->id, $this->users[3]->id));
        $this->assert_count_and_close_recordset(0, prog_find_unassigned_incomplete_completions($this->programs[5]->id, $this->users[2]->id));
        $this->assert_count_and_close_recordset(0, prog_find_unassigned_incomplete_completions($this->programs[6]->id, $this->users[2]->id));
    }

    /**
     * This test is designed to show that if learning plans exist then they will not be reported as broken.
     * The previous test already does this for program assignments (near the start).
     */
    public function test_prog_find_unassigned_incomplete_completions_with_learning_plans() {
        global $DB;


        $generator_program = \totara_program\testing\generator::instance();

        // Set up a program in a user's plan.
        $user = $this->getDataGenerator()->create_user();
        $plangenerator = $this->getDataGenerator()->get_plugin_generator('totara_plan');
        $planrecord = $plangenerator->create_learning_plan(array('userid' => $user->id));
        $plan = new development_plan($planrecord->id);
        $program = $generator_program->create_program();
        $this->setAdminUser(); // Stupid access control.
        $plangenerator->add_learning_plan_program($plan->id, $program->id);

        // Run the function to make sure it doesn't find a problem.
        $this->assert_count_and_close_recordset(0, prog_find_unassigned_incomplete_completions());

        // Break the completion.
        $DB->delete_records('dp_plan_program_assign');

        // Run the function to make sure it can find the problem.
        $this->assert_count_and_close_recordset(1, prog_find_unassigned_incomplete_completions());
    }

    public function test_prog_get_all_completions_with_errors_with_consistency_problems() {
        global $DB;

        $this->setup_completions();

        // One consistency problem.
        $progcompletion = prog_load_completion($this->programs[1]->id, $this->users[2]->id);
        $progcompletion->status = STATUS_PROGRAM_COMPLETE;
        $errors = prog_get_completion_errors($progcompletion);
        $this->assertNotEmpty($errors);
        $problemkey = prog_get_completion_error_problemkey($errors);
        $this->assertTrue(prog_write_completion($progcompletion, '', $problemkey));

        // One missing prog_completion.
        $DB->delete_records('prog_completion', array('programid' => $this->programs[3]->id, 'userid' => $this->users[4]->id));

        // One unassigned incomplete completion record.
        $DB->delete_records('prog_user_assignment', array('programid' => $this->programs[5]->id, 'userid' => $this->users[6]->id));

        // Run the function.
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();

        // Check that the results contain the problems.
        $this->assertCount(3, $fulllist);
        $consistencyitem = $fulllist[$this->programs[1]->id . '-' . $this->users[2]->id];
        $missingitem = $fulllist[$this->programs[3]->id . '-' . $this->users[4]->id];
        $unassigneditem = $fulllist[$this->programs[5]->id . '-' . $this->users[6]->id];
        $this->assertEquals('Time completed should not be empty when user has completed the program.', $consistencyitem->problem);
        $this->assertEquals('Program completion record is missing', $missingitem->problem);
        $this->assertEquals('Program completion record exists for a user who is unassigned and incomplete', $unassigneditem->problem);

        $this->assertCount(3, $aggregatelist);
        $consistencyaggregate = $aggregatelist['error:statecomplete-timecompletedempty'];
        $missingaggregate = $aggregatelist['error:missingprogcompletion'];
        $unassignedaggregate = $aggregatelist['error:unassignedincompleteprogcompletion'];
        $this->assertEquals(1, $consistencyaggregate->count);
        $this->assertEquals(1, $missingaggregate->count);
        $this->assertEquals(1, $unassignedaggregate->count);
        $this->assertEquals('Consistency', $consistencyaggregate->category);
        $this->assertEquals('Files', $missingaggregate->category);
        $this->assertEquals('Files', $unassignedaggregate->category);
        $this->assertTrue(isset($consistencyaggregate->problem));
        $this->assertTrue(isset($missingaggregate->problem));
        $this->assertTrue(isset($unassignedaggregate->problem));
        $this->assertTrue(isset($consistencyaggregate->solution));
        $this->assertTrue(isset($missingaggregate->solution));
        $this->assertTrue(isset($unassignedaggregate->solution));

        $this->assertEquals($this->numtestusers * $this->numtestprogs, $totalcount); // Excludes certs.
    }

    public function test_prog_find_orphaned_exceptions_with_programs() {
        global $DB;

        // Set up some data that is valid.
        $this->setup_completions();

        // Show that there are no problems
        $this->assert_count_and_close_recordset(0, prog_find_orphaned_exceptions(0, 0, 'program'));
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);

        // Break all the records, progs and certs.
        $DB->set_field('prog_user_assignment', 'exceptionstatus', PROGRAM_EXCEPTION_RAISED);

        // Check that all of the records are broken, progs and certs.
        $this->assert_count_and_close_recordset($this->numtestusers * $this->numtestprogs, prog_find_orphaned_exceptions(0, 0, 'program'));
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestprogs, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts, $fulllist);

        // Apply the fix to just one user, all progs.
        prog_fix_orphaned_exceptions_assign(0, $this->users[2]->id, 'program');

        // Apply the fix to just one prog, all users (overlapping).
        prog_fix_orphaned_exceptions_assign($this->programs[6]->id, 0, 'program');

        // Test the function is returning the correct records when no user or program is specified.
        $expectedfixedcount = $this->numtestusers * $this->numtestprogs - $this->numtestprogs - $this->numtestusers + 1;
        $this->assert_count_and_close_recordset($expectedfixedcount, prog_find_orphaned_exceptions(0, 0, 'program'));

        // Test the function is returning the correct records when just the program is specified.
        $this->assert_count_and_close_recordset($this->numtestusers - 1, prog_find_orphaned_exceptions($this->programs[4]->id, 0, 'program'));
        $this->assert_count_and_close_recordset(0, prog_find_orphaned_exceptions($this->programs[6]->id, 0, 'program')); // All were fixed.

        // Test the function is returning the correct records when just the user is specified.
        $this->assert_count_and_close_recordset($this->numtestprogs - 1, prog_find_orphaned_exceptions(0, $this->users[7]->id, 'program'));
        $this->assert_count_and_close_recordset(0, prog_find_orphaned_exceptions(0, $this->users[2]->id, 'program')); // All were fixed.

        // Test the function is returning the correct records when program and user are specified.
        $this->assert_count_and_close_recordset(1, prog_find_orphaned_exceptions($this->programs[4]->id, $this->users[6]->id, 'program'));
        $this->assert_count_and_close_recordset(0, prog_find_orphaned_exceptions($this->programs[6]->id, $this->users[3]->id, 'program'));
        $this->assert_count_and_close_recordset(0, prog_find_orphaned_exceptions($this->programs[5]->id, $this->users[2]->id, 'program'));
        $this->assert_count_and_close_recordset(0, prog_find_orphaned_exceptions($this->programs[6]->id, $this->users[2]->id, 'program'));
    }

    public function test_prog_find_orphaned_exceptions_with_certifications() {
        global $DB;

        // Set up some data that is valid.
        $this->setup_completions();

        // Show that there are no problems
        $this->assert_count_and_close_recordset(0, prog_find_orphaned_exceptions(0, 0, 'certification'));
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);

        // Break all the records, progs and certs.
        $DB->set_field('prog_user_assignment', 'exceptionstatus', PROGRAM_EXCEPTION_RAISED);

        // Check that all of the records are broken, progs and certs.
        $this->assert_count_and_close_recordset($this->numtestusers * $this->numtestcerts, prog_find_orphaned_exceptions(0, 0, 'certification'));
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestprogs, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts, $fulllist);

        // Apply the fix to just one user, all progs.
        prog_fix_orphaned_exceptions_assign(0, $this->users[2]->id, 'certification');

        // Apply the fix to just one prog, all users (overlapping).
        prog_fix_orphaned_exceptions_assign($this->certifications[6]->id, 0, 'certification');

        // Test the function is returning the correct records when no user or program is specified.
        $expectedfixedcount = $this->numtestusers * $this->numtestcerts - $this->numtestcerts - $this->numtestusers + 1;
        $this->assert_count_and_close_recordset($expectedfixedcount, prog_find_orphaned_exceptions(0, 0, 'certification'));

        // Test the function is returning the correct records when just the program is specified.
        $this->assert_count_and_close_recordset($this->numtestusers - 1, prog_find_orphaned_exceptions($this->certifications[4]->id, 0, 'certification'));
        $this->assert_count_and_close_recordset(0, prog_find_orphaned_exceptions($this->certifications[6]->id, 0, 'certification')); // All were fixed.

        // Test the function is returning the correct records when just the user is specified.
        $this->assert_count_and_close_recordset($this->numtestcerts - 1, prog_find_orphaned_exceptions(0, $this->users[7]->id, 'certification'));
        $this->assert_count_and_close_recordset(0, prog_find_orphaned_exceptions(0, $this->users[2]->id, 'certification')); // All were fixed.

        // Test the function is returning the correct records when program and user are specified.
        $this->assert_count_and_close_recordset(1, prog_find_orphaned_exceptions($this->certifications[4]->id, $this->users[6]->id, 'certification'));
        $this->assert_count_and_close_recordset(0, prog_find_orphaned_exceptions($this->certifications[6]->id, $this->users[3]->id, 'certification'));
        $this->assert_count_and_close_recordset(0, prog_find_orphaned_exceptions($this->certifications[5]->id, $this->users[2]->id, 'certification'));
        $this->assert_count_and_close_recordset(0, prog_find_orphaned_exceptions($this->certifications[6]->id, $this->users[2]->id, 'certification'));
    }

    /**
     * Tests visibility rules for prog_get_all_programs
     */
    public function test_prog_get_required_programs() {
        global $DB;


        $program_generator = \totara_program\testing\generator::instance();

        $user = $this->getDataGenerator()->create_user();

        $program1 = $program_generator->create_program(); // Enroled, active
        $program2 = $program_generator->create_program(); // Not enroled, active
        $program3 = $program_generator->create_program(); // Enroled, not available, not started
        $program4 = $program_generator->create_program(); // Not enroled, not available, not started

        $program_generator->assign_to_program($program1->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);
        $program_generator->assign_to_program($program3->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);

        //Make programs unavailable
        $program3->availableuntil = 1000;
        $program4->availableuntil = 1000;
        $DB->update_record('prog', $program3);
        $DB->update_record('prog', $program4);

        //Check normal visibility rules
        $programs1 = prog_get_required_programs($user->id);

        $this->assertArrayHasKey($program1->id, $programs1);
        $this->assertArrayNotHasKey($program2->id, $programs1);
        $this->assertArrayNotHasKey($program3->id, $programs1);
        $this->assertArrayNotHasKey($program4->id, $programs1);

        //Check showhidden shows expired programs
        $programs2 = prog_get_required_programs($user->id, '', '', '', false, true);

        $this->assertArrayHasKey($program1->id, $programs2);
        $this->assertArrayNotHasKey($program2->id, $programs2);
        $this->assertArrayHasKey($program3->id, $programs2);
        $this->assertArrayNotHasKey($program4->id, $programs2);
    }

    public function test_prog_set_status_complete() {
        global $DB;

        $generator = $this->getDataGenerator();

        /* @var \totara_program\testing\generator $programgenerator */
        $programgenerator = $generator->get_plugin_generator('totara_program');
        /* @var \core_completion\testing\generator $completiongenerator */
        $completiongenerator = $generator->get_plugin_generator('core_completion');

        // Set up some stuff.
        $user1 = $generator->create_user(); // Control user.
        $user2 = $generator->create_user();

        $prog1 = $programgenerator->create_program();
        $prog2 = $programgenerator->create_program(); // Control program.

        $course1 = $generator->create_course();
        $course2 = $generator->create_course();
        $course3 = $generator->create_course();
        $course4 = $generator->create_course(); // Control course.

        $programgenerator->add_courses_and_courseset_to_program($prog1, [[$course1], [$course2], [$course3]], CERTIFPATH_STD);
        $programgenerator->add_courses_and_courseset_to_program($prog2, [[$course4]], CERTIFPATH_STD);

        $programgenerator->assign_to_program($prog1->id, ASSIGNTYPE_INDIVIDUAL, $user1->id, null, true);
        $programgenerator->assign_to_program($prog1->id, ASSIGNTYPE_INDIVIDUAL, $user2->id, null, true);
        $programgenerator->assign_to_program($prog2->id, ASSIGNTYPE_INDIVIDUAL, $user2->id, null, true);

        // Create some course set group completion records (with timecompleted).
        $now = time();
        $user1course1timecompleted = $now - DAYSECS * 10; // Recent, other user.
        $user2course4timecompleted = $now - DAYSECS * 10; // Recent, other course.
        $user2course1timecompleted = $now - DAYSECS * 30;
        $user2course2timecompleted = $now - DAYSECS * 20; // Expected timecompleted - most recent relevant.
        // Capture all events, so that we're not prematurely triggering program completion.
        $completiongenerator->complete_course($course1, $user1, $user1course1timecompleted);
        $completiongenerator->complete_course($course4, $user2, $user2course4timecompleted);
        $completiongenerator->complete_course($course1, $user2, $user2course1timecompleted);
        $completiongenerator->complete_course($course2, $user2, $user2course2timecompleted);
        // Don't complete the third course, because it would mark the program complete.

        // Before doing the positive test, check that the function will fail correctly.
        $prefailprogcompletion = prog_load_completion($prog1->id, $user2->id);

        // First with some invalid data - timecompleted can only be > 0 if status is complete.
        $DB->set_field('prog_completion', 'timecompleted', 123, ['programid' => $prog1->id, 'userid' => $user2->id, 'coursesetid' => 0]);
        $this->assertFalse(prog_set_status_complete($prog1->id, $user2->id, 'Testing fail 1 prog_set_status_complete'));
        $latestlog = $DB->get_records('prog_completion_log', [], 'id DESC', '*', 0, 1);
        $latestlog = reset($latestlog);
        $this->assertEquals($prog1->id, $latestlog->programid);
        $this->assertEquals($user2->id, $latestlog->userid);
        $this->assertStringStartsWith('Tried to set status to Complete, but failed because current completion data is invlaid', $latestlog->description);

        // Second when the status is already complete.
        $DB->set_field('prog_completion', 'status', STATUS_PROGRAM_COMPLETE, ['programid' => $prog1->id, 'userid' => $user2->id, 'coursesetid' => 0]);
        $this->assertFalse(prog_set_status_complete($prog1->id, $user2->id, 'Testing fail 2 prog_set_status_complete'));
        $latestlog = $DB->get_records('prog_completion_log', [], 'id DESC', '*', 0, 1);
        $latestlog = reset($latestlog);
        $this->assertEquals($prog1->id, $latestlog->programid);
        $this->assertEquals($user2->id, $latestlog->userid);
        $this->assertStringStartsWith('Tried to set status to Complete, but current status is not Incomplete', $latestlog->description);

        // Now do the positive test. Before we start, put the program completion record back to the way it should be.
        prog_write_completion($prefailprogcompletion);
        $progcompletion = prog_load_completion($prog1->id, $user2->id);
        $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status);
        $this->assertEquals(0, $progcompletion->timecompleted);

        // Run the function and capture the event.
        $sink = $this->redirectEvents();
        $this->assertTrue(prog_set_status_complete($prog1->id, $user2->id, 'Testing pass prog_set_status_complete'));
        $events = $sink->get_events();

        // Check the prog_completion record has been updated to complete.
        $progcompletion = prog_load_completion($prog1->id, $user2->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $this->assertEquals($user2course2timecompleted, $progcompletion->timecompleted); // Correct timecompleted was selected.

        // Check the event.
        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertInstanceOf('\totara_program\event\program_completed', $event);
        $this->assertEquals($prog1->id, $event->objectid);
        $this->assertEquals($user2->id, $event->userid);

        // Check the log.
        $latestlog = $DB->get_records('prog_completion_log', [], 'id DESC', '*', 0, 1);
        $latestlog = reset($latestlog);
        $this->assertEquals($prog1->id, $latestlog->programid);
        $this->assertEquals($user2->id, $latestlog->userid);
        $this->assertStringStartsWith('Testing pass prog_set_status_complete', $latestlog->description);
    }

    public function test_prog_get_programs_page() {
        /* @var \totara_program\testing\generator $programgenerator */
        $programgenerator = self::getDataGenerator()->get_plugin_generator('totara_program');

        $category = self::getDataGenerator()->create_category(['parent' => 0]);

        $programdata = ['category' => $category->id];
        $prog1 = $programgenerator->create_program($programdata);
        $prog2 = $programgenerator->create_program($programdata);
        $prog3 = $programgenerator->create_program($programdata);
        $prog4 = $programgenerator->create_program($programdata);
        $prog5 = $programgenerator->create_program($programdata);
        $prog6 = $programgenerator->create_program($programdata);
        $prog7 = $programgenerator->create_program($programdata);

        $page1 = prog_get_programs_page($category->id, 'p.sortorder ASC', 'p.id', $totalcount, 0 * 3, 3);
        self::assertEquals(7, $totalcount);
        self::assertCount(3, $page1);
        self::assertEquals([$prog1->id, $prog2->id, $prog3->id], array_keys($page1));

        $page2 = prog_get_programs_page($category->id, 'p.sortorder ASC', 'p.id', $totalcount, 1 * 3, 3);
        self::assertEquals(7, $totalcount);
        self::assertCount(3, $page2);
        self::assertEquals([$prog4->id, $prog5->id, $prog6->id], array_keys($page2));

        $page3 = prog_get_programs_page($category->id, 'p.sortorder ASC', 'p.id', $totalcount, 2 * 3, 3);
        self::assertEquals(7, $totalcount);
        self::assertCount(1, $page3);
        self::assertEquals([$prog7->id], array_keys($page3));
    }
}
