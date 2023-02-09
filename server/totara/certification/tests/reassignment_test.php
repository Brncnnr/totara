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
 * @author David Curry <david.curry@totaralms.com>
 * @package totara
 * @subpackage certification
 */

use totara_core\advanced_feature;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/lib/moodlelib.php');
require_once($CFG->dirroot . '/totara/certification/lib.php');

// Create MONTHSEC value
const MONTHSECS = YEARSECS / 12;

/**
 * Certification module PHPUnit archive test class
 *
 * To test, run this from the command line from the $CFG->dirroot
 * vendor/bin/phpunit --verbose totara_certification_reassignments_testcase totara/certification/tests/reassignment_test.php
 */
class totara_certification_reassignment_testcase extends advanced_testcase {

    /**
     * Setup.
     */
    public function setUp(): void {
        parent::setup();

        // Turn off programs. This is to test that it doesn't interfere with certification completion.
        set_config('enableprograms', advanced_feature::DISABLED);
    }

    private function get_certification_data() {
        $data = new stdClass();

        $data->task = new \totara_certification\task\update_certification_task();

        // Create a certification.
        $certdata = array(
            'cert_activeperiod' => '6 Months',
            'cert_windowperiod' => '2 Months',
        );

        $generator = $this->getDataGenerator();
        $program_generator = $generator->get_plugin_generator('totara_program');

        $programid = $program_generator->create_certification($certdata);
        $data->program = new \program($programid);

        $course1 = $generator->create_course(['fullname' => 'course1']);
        $data->courses[1] = $course1;
        $course2 = $generator->create_course(['fullname' => 'course2']);
        $data->courses[2] = $course2;
        $course3 = $generator->create_course(['fullname' => 'course3']);
        $data->courses[3] = $course3;

        $program_generator->add_courses_and_courseset_to_program($data->program, [[$course1, $course2, $course3]], CERTIFPATH_CERT);
        $program_generator->add_courses_and_courseset_to_program($data->program, [[$course3]], CERTIFPATH_RECERT);

        // Create some test users and store them in an array.
        // User 1 is our guinea pig.
        $user1 = $generator->create_user(['fullname' => 'user1']);
        $data->users[1] = $user1;

        // User 2 is assigned but left alone.
        $user2 = $generator->create_user(['fullname' => 'user2']);
        $data->users[2] = $user2;

        // User 3 is not assigned.
        $user3 = $generator->create_user(['fullname' => 'user3']);
        $data->users[3] = $user3;

        return $data;
    }

    private function setup_certified_state(\stdClass $data, int $completetime) {
        global $DB;

        $windowtime = $completetime + (4 * MONTHSECS);
        $expiretime = $completetime + (6 * MONTHSECS);

        $generator = $this->getDataGenerator();
        $program_generator = $generator->get_plugin_generator('totara_program');

        // Assign the program to users.
        $program_generator->assign_program($data->program->id, [$data->users[1]->id, $data->users[2]->id]);

        // Complete the courses in the certification path.
        $completion = new completion_completion(array('userid' => $data->users[1]->id, 'course' => $data->courses[1]->id));
        $completion->mark_complete($completetime);

        $certcomprec = $DB->get_record('course_completions', array('course' => $data->courses[1]->id, 'userid' => $data->users[1]->id));
        $this->assertEquals($completetime, $certcomprec->timecompleted);

        $completion = new completion_completion(array('userid' => $data->users[1]->id, 'course' => $data->courses[2]->id));
        $completion->mark_complete($completetime);

        $certcomprec = $DB->get_record('course_completions', array('course' => $data->courses[2]->id, 'userid' => $data->users[1]->id));
        $this->assertEquals($completetime, $certcomprec->timecompleted);

        $completion = new completion_completion(array('userid' => $data->users[1]->id, 'course' => $data->courses[3]->id));
        $completion->mark_complete($completetime);

        $certcomprec = $DB->get_record('course_completions', array('course' => $data->courses[3]->id, 'userid' => $data->users[1]->id));
        $this->assertEquals($completetime, $certcomprec->timecompleted);

        // Get the completion record for user 1 and update the times.
        list($certcompletion, $progcompletion) = certif_load_completion($data->program->id, $data->users[1]->id);
        $certcompletion->timecompleted = $completetime;
        $certcompletion->timewindowopens = $windowtime;
        $certcompletion->timeexpires = $expiretime;
        $certcompletion->baselinetimeexpires = $expiretime;
        $progcompletion->timedue = $expiretime;
        $errors = certif_get_completion_errors($certcompletion, $progcompletion);
        $this->assertEquals(array(), $errors);
        certif_write_completion($certcompletion, $progcompletion);

        return array('complete' => $completetime, 'window' => $windowtime, 'expire' => $expiretime);
    }

    /**
     * Test restoration of certification completion
     * when a user was unassigned pre-window and restored straight away.
     */
    public function test_restoration_certified_prewindow() {
        global $DB, $CFG;

        $generator = $this->getDataGenerator();
        $program_generator = $generator->get_plugin_generator('totara_program');
        $data = $this->get_certification_data();

        $progid = $data->program->id;
        $times = $this->setup_certified_state($data, time() - MONTHSECS); // Setup with completion 1 month ago.

        // Unassign.
        $program_generator->assign_program($progid, [$data->users[2]->id]);

        // Run the certification task.
        $data->task->execute();

        // Check that the unassignment has created the expected history record.
        $certhistrec = $DB->get_record('certif_completion_history', array('userid' => $data->users[1]->id));
        $this->assertEquals($times['complete'], $certhistrec->timecompleted);
        $this->assertEquals($times['window'], $certhistrec->timewindowopens);
        $this->assertEquals($times['expire'], $certhistrec->timeexpires);
        $this->assertEquals(1, $certhistrec->unassigned);

        $certcomprec = $DB->record_exists('certif_completion', array('userid' => $data->users[1]->id));
        $this->assertEquals(false, $certcomprec);

         // Reassign.
        $program_generator->assign_program($progid, [$data->users[1]->id, $data->users[2]->id]);

        // Run the certificationtrue task.
        $data->task->execute();

        // Check the restored certification completion record.
        $certcomprec = $DB->get_record('certif_completion', array('userid' => $data->users[1]->id));
        $this->assertEquals($times['complete'], $certcomprec->timecompleted);
        $this->assertEquals($times['window'], $certcomprec->timewindowopens);
        $this->assertEquals($times['expire'], $certcomprec->timeexpires);
        $this->assertEquals(CERTIFSTATUS_COMPLETED, $certcomprec->status); // Check they are still certified.
        $this->assertEquals(CERTIFRENEWALSTATUS_NOTDUE, $certcomprec->renewalstatus); // Check the window has not opened.

        // Check the history record no longer exists, it will be created when the window opens.
        $this->assertFalse($DB->record_exists('certif_completion_history', array('userid' => $data->users[1]->id)));

        // Check the course completions are still there and still marked as complete.
        $comprecs = $DB->get_records('course_completions', array('userid' => $data->users[1]->id));
        $this->assertEquals(3, count($comprecs));
        $comprecs = $DB->get_records('course_completions', array('userid' => $data->users[1]->id, 'timecompleted' => $times['complete']));
        $this->assertEquals(3, count($comprecs));
    }

    /**
     * Test restoration of certification completion
     * when a user was unassigned pre-window and restored post-window
     */
    public function test_restoration_certified_postwindow() {
        global $DB, $CFG;

        $generator = $this->getDataGenerator();
        $program_generator = $generator->get_plugin_generator('totara_program');
        $data = $this->get_certification_data();

        $progid = $data->program->id;
        $times = $this->setup_certified_state($data, time() - (5 * MONTHSECS)); // Setup with completion 5 months ago.

        // Unassign.
        $program_generator->assign_program($progid, [$data->users[2]->id]);

        // Run the certification task.
        $data->task->execute();

        // Check that the unassignment has created the expected history record.
        $certhistrec = $DB->get_record('certif_completion_history', array('userid' => $data->users[1]->id));
        $this->assertEquals($times['complete'], $certhistrec->timecompleted);
        $this->assertEquals($times['window'], $certhistrec->timewindowopens);
        $this->assertEquals($times['expire'], $certhistrec->timeexpires);
        $this->assertEquals(1, $certhistrec->unassigned);

        $certcomprec = $DB->record_exists('certif_completion', array('userid' => $data->users[1]->id));
        $this->assertEquals(false, $certcomprec);

         // Reassign.
        $program_generator->assign_program($progid, [$data->users[1]->id, $data->users[2]->id]);

        // Check the restored certification completion record.
        $certcomprec = $DB->get_record('certif_completion', array('userid' => $data->users[1]->id));
        $this->assertEquals($times['complete'], $certcomprec->timecompleted);
        $this->assertEquals($times['window'], $certcomprec->timewindowopens);
        $this->assertEquals($times['expire'], $certcomprec->timeexpires);

        // Check the history record no longer exists, it will be created when the window opens.
        $this->assertFalse($DB->record_exists('certif_completion_history', array('userid' => $data->users[1]->id)));

        // Check the course completions are still there and still marked as complete.
        $comprecs = $DB->get_records('course_completions', array('userid' => $data->users[1]->id, 'timecompleted' => $times['complete']));
        $this->assertEquals(3, count($comprecs));

        // Run the certification task.
        $data->task->execute();

        // Check the restored certification completion record.
        $certcomprec = $DB->get_record('certif_completion', array('userid' => $data->users[1]->id));
        $this->assertEquals($times['complete'], $certcomprec->timecompleted);
        $this->assertEquals($times['window'], $certcomprec->timewindowopens);
        $this->assertEquals($times['expire'], $certcomprec->timeexpires);
        $this->assertEquals(CERTIFSTATUS_COMPLETED, $certcomprec->status); // Check they are still certified.
        $this->assertEquals(CERTIFRENEWALSTATUS_DUE, $certcomprec->renewalstatus); // Check the window has opened.

        // Check the new history record has been created and is not marked as unassigned.
        $certhistrec = $DB->get_record('certif_completion_history', array('userid' => $data->users[1]->id));
        $this->assertEquals($times['complete'], $certhistrec->timecompleted);
        $this->assertEquals($times['window'], $certhistrec->timewindowopens);
        $this->assertEquals($times['expire'], $certhistrec->timeexpires);
        $this->assertEquals(0, $certhistrec->unassigned);

        // Check the primary-path-only course completions are still there and marked as complete, the other is gone.
        $comprecs = $DB->get_records('course_completions', array('userid' => $data->users[1]->id));
        $this->assertEquals(2, count($comprecs));
        $comprecs = $DB->get_records('course_completions', array('userid' => $data->users[1]->id, 'timecompleted' => $times['complete']));
        $this->assertEquals(2, count($comprecs));

        // Only the recert path course was archived.
        $comphistrecs = $DB->get_records('course_completion_history', array('userid' => $data->users[1]->id));
        $this->assertEquals(1, count($comphistrecs));
        $comphistrecs = $DB->get_records('course_completion_history', array('userid' => $data->users[1]->id, 'timecompleted' => $times['complete']));
        $this->assertEquals(1, count($comphistrecs));
    }

    /**
     * Test restoration of certification completion
     * when a user was unassigned pre-window and restored after expiry
     */
    public function test_restoration_certified_expired() {
        global $DB, $CFG;

        $generator = $this->getDataGenerator();
        $program_generator = $generator->get_plugin_generator('totara_program');
        $data = $this->get_certification_data();

        $progid = $data->program->id;
        $times = $this->setup_certified_state($data, time() - (7 * MONTHSECS)); // Setup with completion 7 months ago.

        // Unassign.
        $program_generator->assign_program($progid, [$data->users[2]->id]);

        // Run the certification task.
        $data->task->execute();

        // Check that the unassignment has created the expected history record.
        $certhistrec = $DB->get_record('certif_completion_history', array('userid' => $data->users[1]->id));
        $this->assertEquals($times['complete'], $certhistrec->timecompleted);
        $this->assertEquals($times['window'], $certhistrec->timewindowopens);
        $this->assertEquals($times['expire'], $certhistrec->timeexpires);
        $this->assertEquals(1, $certhistrec->unassigned);

        $certcomprec = $DB->record_exists('certif_completion', array('userid' => $data->users[1]->id));
        $this->assertEquals(false, $certcomprec);

        // Reassign.
        $program_generator->assign_program($progid, [$data->users[1]->id, $data->users[2]->id]);

        $certcomprec = $DB->record_exists('certif_completion', array('userid' => $data->users[1]->id));
        $this->assertEquals($certcomprec, true);

        // Check the restored certification completion record.
        $certcomprec = $DB->get_record('certif_completion', array('userid' => $data->users[1]->id));
        $this->assertEquals($times['complete'], $certcomprec->timecompleted);
        $this->assertEquals($times['window'], $certcomprec->timewindowopens);
        $this->assertEquals($times['expire'], $certcomprec->timeexpires);

        // Check the history record no longer exists, it will be created when the window opens.
        $this->assertFalse($DB->record_exists('certif_completion_history', array('userid' => $data->users[1]->id)));

        // Check the course completions are still there and still marked as complete.
        $comprecs = $DB->get_records('course_completions', array('userid' => $data->users[1]->id, 'timecompleted' => $times['complete']));
        $this->assertEquals(3, count($comprecs));

        // Run the certification task.
        // Restoring certified status so after the task is run the completion
        // record should be reset for next certification time
        $data->task->execute();

        $certcomprec = $DB->get_record('certif_completion', array('userid' => $data->users[1]->id));
        $this->assertEquals(0, $certcomprec->timecompleted);
        $this->assertEquals(0, $certcomprec->timewindowopens);
        $this->assertEquals(0, $certcomprec->timeexpires);
        $this->assertEquals(CERTIFSTATUS_EXPIRED, $certcomprec->status); // Check they are expired.
        $this->assertEquals(CERTIFRENEWALSTATUS_EXPIRED, $certcomprec->renewalstatus); // Check the window has expired.

        // Check the history record is still there but no longer marked as unassigned.
        $certhistrec = $DB->get_record('certif_completion_history', array('userid' => $data->users[1]->id));
        $this->assertEquals($times['complete'], $certhistrec->timecompleted);
        $this->assertEquals($times['window'], $certhistrec->timewindowopens);
        $this->assertEquals($times['expire'], $certhistrec->timeexpires);
        $this->assertEquals(0, $certhistrec->unassigned);

        // Check all the courses have been archived - course 3 during window open (tested above) and courses 1 and 2 during expiry.
        $comprecs = $DB->get_records('course_completions', array('userid' => $data->users[1]->id));
        $this->assertEquals(0, count($comprecs));

        $comphistrecs = $DB->get_records('course_completion_history', array('userid' => $data->users[1]->id));
        $this->assertEquals(3, count($comphistrecs));
        $comphistrecs = $DB->get_records('course_completion_history', array('userid' => $data->users[1]->id, 'timecompleted' => $times['complete']));
        $this->assertEquals(3, count($comphistrecs));
    }

    private function setup_recertified_state(\stdClass $data, int $recompletetime) {
        global $DB;

        // Set up the initial completions.
        $times = $this->setup_certified_state($data, $recompletetime - (5 * MONTHSECS));

        // Run the window opening to move things around without triggering expiry.
        recertify_window_opens_stage();

        $completetime = $recompletetime;
        $windowtime = $completetime + (4 * MONTHSECS);
        $expiretime = $completetime + (6 * MONTHSECS);

        // Check that the recert path course was reset.
        $comprec = $DB->get_record('course_completions', array('course' => $data->courses[1]->id, 'userid' => $data->users[1]->id));
        $this->assertEquals($times['complete'], $comprec->timecompleted);
        $comprec = $DB->get_record('course_completions', array('course' => $data->courses[2]->id, 'userid' => $data->users[1]->id));
        $this->assertEquals($times['complete'], $comprec->timecompleted);
        $comprec = $DB->record_exists('course_completions', array('course' => $data->courses[3]->id, 'userid' => $data->users[1]->id));
        $this->assertEquals(false, $comprec);

        // Complete the courses in the recertification path.
        $completion = new completion_completion(array('userid' => $data->users[1]->id, 'course' => $data->courses[3]->id));
        $completion->mark_complete($completetime);

        $comprec = $DB->get_record('course_completions', array('course' => $data->courses[3]->id, 'userid' => $data->users[1]->id));
        $this->assertEquals($completetime, $comprec->timecompleted);

        // Get the completion record for user 1 and update the times.
        list($certcompletion, $progcompletion) = certif_load_completion($data->program->id, $data->users[1]->id);
        $certcompletion->timecompleted = $completetime;
        $certcompletion->timewindowopens = $windowtime;
        $certcompletion->timeexpires = $expiretime;
        $certcompletion->baselinetimeexpires = $expiretime;
        $progcompletion->timedue = $expiretime;
        $errors = certif_get_completion_errors($certcompletion, $progcompletion);
        $this->assertEquals(array(), $errors);
        certif_write_completion($certcompletion, $progcompletion);

        // Run the window opening to move things around without triggering expiry.
        recertify_window_opens_stage();

        $data = array(
            'complete' => $completetime,
            'window' => $windowtime,
            'expire' => $expiretime,
            'oldcomplete' => $times['complete'],
            'oldwindow' => $times['window'],
            'oldexpire' => $times['expire'],
        );

        return $data;
    }

    /**
     * Test restoration of recertified completion
     * when a user was unassigned pre-window and restored straight away.
     */
    public function test_restoration_recertified_prewindow() {
        global $DB, $CFG;

        $generator = $this->getDataGenerator();
        $program_generator = $generator->get_plugin_generator('totara_program');
        $data = $this->get_certification_data();

        $progid = $data->program->id;
        $times = $this->setup_recertified_state($data, time() - (3 * MONTHSECS)); // Setup with recertification 3 months ago.

        // Unassign user 1
        $program_generator->assign_program($progid, [$data->users[2]->id]);

        // Run the certification task.
        $data->task->execute();

        // Check that the unassignment has created the expected history record.
        $certhistrecs = $DB->get_records('certif_completion_history', array('userid' => $data->users[1]->id));
        $this->assertEquals(2, count($certhistrecs));

        $certhistrecnew = null;
        $certhistrecold = null;
        // Figure out which is the old history record and which is the new.
        foreach ($certhistrecs as $certhistrec) {
            if (empty($certhistrecnew) || $certhistrec->timecompleted > $certhistrecnew->timecompleted) {
                $certhistrecold = $certhistrecnew;
                $certhistrecnew = $certhistrec;
            } else {
                $certhistrecold = $certhistrec;
            }
        }

        // Then check them both.
        $this->assertEquals($times['complete'], $certhistrecnew->timecompleted);
        $this->assertEquals($times['window'], $certhistrecnew->timewindowopens);
        $this->assertEquals($times['expire'], $certhistrecnew->timeexpires);
        $this->assertEquals(1, $certhistrecnew->unassigned);

        $this->assertEquals($times['oldcomplete'], $certhistrecold->timecompleted);
        $this->assertEquals($times['oldwindow'], $certhistrecold->timewindowopens);
        $this->assertEquals($times['oldexpire'], $certhistrecold->timeexpires);
        $this->assertEquals(0, $certhistrecold->unassigned);

        $certcomprec = $DB->record_exists('certif_completion', array('userid' => $data->users[1]->id));
        $this->assertEquals(false, $certcomprec);

         // Reassign.
        $program_generator->assign_program($progid, [$data->users[1]->id, $data->users[2]->id]);

        // Run the certification task.
        $data->task->execute();

        // Check the restored certification completion record.
        $certcomprec = $DB->get_record('certif_completion', array('userid' => $data->users[1]->id));
        $this->assertEquals($times['complete'], $certcomprec->timecompleted);
        $this->assertEquals($times['window'], $certcomprec->timewindowopens);
        $this->assertEquals($times['expire'], $certcomprec->timeexpires);
        $this->assertEquals(CERTIFPATH_RECERT, $certcomprec->certifpath);
        $this->assertEquals(CERTIFSTATUS_COMPLETED, $certcomprec->status); // Check they are still certified.
        $this->assertEquals(CERTIFRENEWALSTATUS_NOTDUE, $certcomprec->renewalstatus); // Check the window has not opened.

        // Check the history record no longer exists, it will be created when the window opens.
        $this->assertFalse($DB->record_exists('certif_completion_history', array('userid' => $data->users[1]->id, 'timecompleted' => $times['complete'])));

        // Check the course completions are still there and still marked as complete.
        $comprecs = $DB->get_records('course_completions', array('userid' => $data->users[1]->id));
        $this->assertEquals(3, count($comprecs));
        $comprecs = $DB->get_records('course_completions', array('userid' => $data->users[1]->id, 'timecompleted' => $times['complete']));
        $this->assertEquals(1, count($comprecs));
        $comprecs = $DB->get_records('course_completions', array('userid' => $data->users[1]->id, 'timecompleted' => $times['oldcomplete']));
        $this->assertEquals(2, count($comprecs));
    }

    /**
     * Test restoration of recertified completion
     * when a user was unassigned pre-window and restored post window.
     */
    public function test_restoration_recertified_postwindow() {
        global $DB, $CFG;

        $generator = $this->getDataGenerator();
        $program_generator = $generator->get_plugin_generator('totara_program');
        $data = $this->get_certification_data();

        $progid = $data->program->id;
        $times = $this->setup_recertified_state($data, time() - (5 * MONTHSECS)); // Setup with recertification 5 months ago.

        // Unassign.
        $program_generator->assign_program($progid, [$data->users[2]->id]);

        // Run the certification task.
        $data->task->execute();

        // Check that the unassignment has created the expected history record.
        $certhistrecs = $DB->get_records('certif_completion_history', array('userid' => $data->users[1]->id));
        $this->assertEquals(2, count($certhistrecs));

        $certhistrecnew = null;
        $certhistrecold = null;
        // Figure out which is the old history record and which is the new.
        foreach ($certhistrecs as $certhistrec) {
            if (empty($certhistrecnew) || $certhistrec->timecompleted > $certhistrecnew->timecompleted) {
                $certhistrecold = $certhistrecnew;
                $certhistrecnew = $certhistrec;
            } else {
                $certhistrecold = $certhistrec;
            }
        }

        // Then check them both.
        $this->assertEquals($times['complete'], $certhistrecnew->timecompleted);
        $this->assertEquals($times['window'], $certhistrecnew->timewindowopens);
        $this->assertEquals($times['expire'], $certhistrecnew->timeexpires);
        $this->assertEquals(1, $certhistrecnew->unassigned);

        $this->assertEquals($times['oldcomplete'], $certhistrecold->timecompleted);
        $this->assertEquals($times['oldwindow'], $certhistrecold->timewindowopens);
        $this->assertEquals($times['oldexpire'], $certhistrecold->timeexpires);
        $this->assertEquals(0, $certhistrecold->unassigned);

        $certcomprec = $DB->record_exists('certif_completion', array('userid' => $data->users[1]->id));
        $this->assertEquals(false, $certcomprec);

         // Reassign.
        $program_generator->assign_program($progid, [$data->users[1]->id, $data->users[2]->id]);

        // Run the certification task.
        $data->task->execute();

        // Check the restored certification completion record.
        $certcomprec = $DB->get_record('certif_completion', array('userid' => $data->users[1]->id));
        $this->assertEquals($times['complete'], $certcomprec->timecompleted);
        $this->assertEquals($times['window'], $certcomprec->timewindowopens);
        $this->assertEquals($times['expire'], $certcomprec->timeexpires);
        $this->assertEquals(CERTIFPATH_RECERT, $certcomprec->certifpath); // Check they are still on the recert path.
        $this->assertEquals(CERTIFSTATUS_COMPLETED, $certcomprec->status); // Check they are still certified.
        $this->assertEquals(CERTIFRENEWALSTATUS_DUE, $certcomprec->renewalstatus); // Check the window has opened.

        // Check the history record is still there but no longer marked as unassigned.
        $certhistrec = $DB->get_record('certif_completion_history', array('userid' => $data->users[1]->id, 'timecompleted' => $times['complete']));
        $this->assertEquals($times['complete'], $certhistrec->timecompleted);
        $this->assertEquals($times['window'], $certhistrec->timewindowopens);
        $this->assertEquals($times['expire'], $certhistrec->timeexpires);
        $this->assertEquals(0, $certhistrec->unassigned);

        // Check the primary-path-only course completions are still there and marked as complete, the other is gone.
        $comprecs = $DB->get_records('course_completions', array('userid' => $data->users[1]->id));
        $this->assertEquals(2, count($comprecs));
        $comprecs = $DB->get_records('course_completions', array('userid' => $data->users[1]->id, 'timecompleted' => $times['oldcomplete']));
        $this->assertEquals(2, count($comprecs));

        // Course 3 was archived with the new completion time.
        $comphistrecs = $DB->get_records('course_completion_history', array('userid' => $data->users[1]->id, 'timecompleted' => $times['complete']));
        $this->assertEquals(1, count($comphistrecs));

        // Course 3 was archived with the old completion time (from first window open).
        $comphistrecs = $DB->get_records('course_completion_history', array('userid' => $data->users[1]->id, 'timecompleted' => $times['oldcomplete']));
        $this->assertEquals(1, count($comphistrecs));
    }

    /**
     * Test restoration of recertified completion
     * when a user was unassigned pre-window and restored after they assignment has expired.
     */
    public function test_restoration_recertified_expired() {
        global $DB, $CFG;

        $generator = $this->getDataGenerator();
        $program_generator = $generator->get_plugin_generator('totara_program');
        $data = $this->get_certification_data();

        $progid = $data->program->id;
        $times = $this->setup_recertified_state($data, time() - (7 * MONTHSECS)); // Setup with recertification 7 months ago.

        // Unassign.
        $program_generator->assign_program($progid, [$data->users[2]->id]);

        // Run the certification task.
        $data->task->execute();

        // Check that the unassignment has created the expected history record.
        $certhistrecs = $DB->get_records('certif_completion_history', array('userid' => $data->users[1]->id));
        $this->assertEquals(2, count($certhistrecs));

        $certhistrecnew = null;
        $certhistrecold = null;
        // Figure out which is the old history record and which is the new.
        foreach ($certhistrecs as $certhistrec) {
            if (empty($certhistrecnew) || $certhistrec->timecompleted > $certhistrecnew->timecompleted) {
                $certhistrecold = $certhistrecnew;
                $certhistrecnew = $certhistrec;
            } else {
                $certhistrecold = $certhistrec;
            }
        }

        // Then check them both.
        $this->assertEquals($times['complete'], $certhistrecnew->timecompleted);
        $this->assertEquals($times['window'], $certhistrecnew->timewindowopens);
        $this->assertEquals($times['expire'], $certhistrecnew->timeexpires);
        $this->assertEquals(1, $certhistrecnew->unassigned);

        $this->assertEquals($times['oldcomplete'], $certhistrecold->timecompleted);
        $this->assertEquals($times['oldwindow'], $certhistrecold->timewindowopens);
        $this->assertEquals($times['oldexpire'], $certhistrecold->timeexpires);
        $this->assertEquals(0, $certhistrecold->unassigned);

        $certcomprec = $DB->record_exists('certif_completion', array('userid' => $data->users[1]->id));
        $this->assertEquals(false, $certcomprec);

         // Reassign.
        $program_generator->assign_program($progid, [$data->users[1]->id, $data->users[2]->id]);

        // Run the certification task.
        $data->task->execute();

        // Check the restored certification completion record.
        $certcomprec = $DB->get_record('certif_completion', array('userid' => $data->users[1]->id));
        $this->assertEquals(0, $certcomprec->timecompleted); // Zero'd out on expiry.
        $this->assertEquals(0, $certcomprec->timewindowopens); // Zero'd out on expiry.
        $this->assertEquals(0, $certcomprec->timeexpires); // Zero'd out on expiry.
        $this->assertEquals(CERTIFPATH_CERT, $certcomprec->certifpath); // Check they have been put back on the CERT path.
        $this->assertEquals(CERTIFSTATUS_EXPIRED, $certcomprec->status); // Check they are still certified.
        $this->assertEquals(CERTIFRENEWALSTATUS_EXPIRED, $certcomprec->renewalstatus); // Check the window has opened.

        // There should still be 2 history records.
        $certhistcount = $DB->count_records('certif_completion_history', array('userid' => $data->users[1]->id));
        $this->assertEquals(2, $certhistcount);

        // Check the history record is still there but no longer marked as unassigned.
        $certhistrec1 = $DB->get_record('certif_completion_history', array('userid' => $data->users[1]->id, 'timecompleted' => $times['complete']));
        $this->assertEquals($times['complete'], $certhistrec1->timecompleted);
        $this->assertEquals($times['window'], $certhistrec1->timewindowopens);
        $this->assertEquals($times['expire'], $certhistrec1->timeexpires);
        $this->assertEquals(0, $certhistrec1->unassigned);

        // Check the history record is still there but no longer marked as unassigned.
        $certhistrec2 = $DB->get_record('certif_completion_history', array('userid' => $data->users[1]->id, 'timecompleted' => $times['oldcomplete']));
        $this->assertEquals($times['oldcomplete'], $certhistrec2->timecompleted);
        $this->assertEquals($times['oldwindow'], $certhistrec2->timewindowopens);
        $this->assertEquals($times['oldexpire'], $certhistrec2->timeexpires);
        $this->assertEquals(0, $certhistrec2->unassigned);

        // Check the course completions are still there and still marked as complete.
        $comprecs = $DB->get_records('course_completions', array('userid' => $data->users[1]->id));
        $this->assertEquals(0, count($comprecs));

        // Course 3 was archived with latest completion time.
        $comphistrecs = $DB->get_records('course_completion_history', array('userid' => $data->users[1]->id, 'timecompleted' => $times['complete']));
        $this->assertEquals(1, count($comphistrecs));

        // Course 3 was archived with previous completion time on first window open, courses 1 and 2 were archived just now, but had old completion date.
        $comphistrecs = $DB->get_records('course_completion_history', array('userid' => $data->users[1]->id, 'timecompleted' => $times['oldcomplete']));
        $this->assertEquals(3, count($comphistrecs));
    }

    /**
     * Test restoration of an expired recertification completion
     * when a user was unassigned after expiry and then reassigned.
     */
    public function test_restoration_expired_expired() {
        global $DB, $CFG;

        $generator = $this->getDataGenerator();
        $program_generator = $generator->get_plugin_generator('totara_program');
        $data = $this->get_certification_data();

        $progid = $data->program->id;

        $times = $this->setup_recertified_state($data, time() - (7 * MONTHSECS)); // Setup with recertification 7 months ago.

        // Run the certification task.
        $data->task->execute();

        // Unassign.
        $program_generator->assign_program($progid, [$data->users[2]->id]);

        // Run the certification task.
        $data->task->execute();

        // Check that the unassignment has created the expected history record.
        $certhistrecs = $DB->get_records('certif_completion_history', array('userid' => $data->users[1]->id));
        $this->assertEquals(3, count($certhistrecs)); // There are 3, one for the cert, one for the recert, and one expired one created for unassignment.

        // Check all three.
        $certhistrec1 = $DB->get_record('certif_completion_history', array('userid' => $data->users[1]->id, 'timecompleted' => 0));
        $this->assertEquals(0, $certhistrec1->timecompleted);
        $this->assertEquals(0, $certhistrec1->timewindowopens);
        $this->assertEquals(0, $certhistrec1->timeexpires);
        $this->assertEquals(1, $certhistrec1->unassigned);

        $certhistrec2 = $DB->get_record('certif_completion_history', array('userid' => $data->users[1]->id, 'timecompleted' => $times['complete']));
        $this->assertEquals($times['complete'], $certhistrec2->timecompleted);
        $this->assertEquals($times['window'], $certhistrec2->timewindowopens);
        $this->assertEquals($times['expire'], $certhistrec2->timeexpires);
        $this->assertEquals(0, $certhistrec2->unassigned);

        $certhistrec3 = $DB->get_record('certif_completion_history', array('userid' => $data->users[1]->id, 'timecompleted' => $times['oldcomplete']));
        $this->assertEquals($times['oldcomplete'], $certhistrec3->timecompleted);
        $this->assertEquals($times['oldwindow'], $certhistrec3->timewindowopens);
        $this->assertEquals($times['oldexpire'], $certhistrec3->timeexpires);
        $this->assertEquals(0, $certhistrec3->unassigned);

        $certcomprec = $DB->record_exists('certif_completion', array('userid' => $data->users[1]->id));
        $this->assertEquals(false, $certcomprec);

        // Reassign.
        $program_generator->assign_program($progid, [$data->users[1]->id, $data->users[2]->id]);

        // Run the certification task.
        $data->task->execute();

        // Check the restored certification completion record.
        $certcomprec = $DB->get_record('certif_completion', array('userid' => $data->users[1]->id));
        $this->assertEquals(0, $certcomprec->timecompleted); // Zero'd out on expiry.
        $this->assertEquals(0, $certcomprec->timewindowopens); // Zero'd out on expiry.
        $this->assertEquals(0, $certcomprec->timeexpires); // Zero'd out on expiry.
        $this->assertEquals(CERTIFPATH_CERT, $certcomprec->certifpath); // Check they have been put back on the CERT path.
        $this->assertEquals(CERTIFSTATUS_EXPIRED, $certcomprec->status); // Check they have expired.
        $this->assertEquals(CERTIFRENEWALSTATUS_EXPIRED, $certcomprec->renewalstatus); // Check the window has expired.

        // Check the history record was deleted and the other 2 records are not.
        $certhistrec = $DB->get_record('certif_completion_history', array('userid' => $data->users[1]->id, 'timecompleted' => 0));
        $this->assertEquals($certhistrec, false);
        $this->assertCount(2, $DB->get_records('certif_completion_history'));

        // Check the course completions are still there and still marked as complete.
        $comprecs = $DB->get_records('course_completions', array('userid' => $data->users[1]->id, 'timecompleted' => $times['complete']));
        $this->assertEquals(0, count($comprecs));

        // Course 3 was archived with latest completion time.
        $comphistrecs = $DB->get_records('course_completion_history', array('userid' => $data->users[1]->id, 'timecompleted' => $times['complete']));
        $this->assertEquals(1, count($comphistrecs));

        // Course 3 was archived with previous completion time on first window open, courses 1 and 2 were archived just now, but had old completion date.
        $comphistrecs = $DB->get_records('course_completion_history', array('userid' => $data->users[1]->id, 'timecompleted' => $times['oldcomplete']));
        $this->assertEquals(3, count($comphistrecs));

        // Quick test that a new history record is created if we remove the user again.
        $prerecord = $DB->get_record('certif_completion', array('timecompleted' => 0, 'userid' => $data->users[1]->id));
        $program_generator->assign_program($progid, [$data->users[2]->id]);
        $data->task->execute();
        $postrecord = $DB->get_record('certif_completion_history', array('timecompleted' => 0, 'userid' => $data->users[1]->id));
        $this->assertGreaterThanOrEqual($prerecord->timemodified, $postrecord->timemodified);
        $this->assertEquals(1, $postrecord->unassigned);
    }

    /**
     * Test that restoration of certification completion with an old expiry date does not lead to a time allowance exception
     */
    public function test_restoration_exceptions_timeallowance() {
        global $DB, $CFG;

        // Set the restoration setting.
        $CFG->restorecertifenrolments = 1;

        $generator = $this->getDataGenerator();
        $program_generator = $generator->get_plugin_generator('totara_program');
        $data = $this->get_certification_data();

        $now = time();
        $progid = $data->program->id;
        $times = $this->setup_certified_state($data, $now - (7 * MONTHSECS)); // Setup with completion 7 months ago.

        // Unassign.
        $program_generator->assign_program($progid, [$data->users[2]->id]);

        // Run the certification task.
        $data->task->execute();

        // Check that the unassignment has created the expected history record.
        $certhistrec = $DB->get_record('certif_completion_history', array('userid' => $data->users[1]->id));
        $this->assertEquals($certhistrec->timecompleted, $times['complete']);
        $this->assertEquals($certhistrec->timewindowopens, $times['window']);
        $this->assertEquals($certhistrec->timeexpires, $times['expire']);
        $this->assertEquals($certhistrec->unassigned, 1);

        $certcomprec = $DB->record_exists('certif_completion', array('userid' => $data->users[1]->id));
        $this->assertEquals($certcomprec, false);

        // Reassign.
        $program_generator->assign_program($progid, [$data->users[1]->id, $data->users[2]->id]);

        // Check that user 1 does not have a time allowance exception.
        $userassignment = $DB->get_record('prog_user_assignment', array('userid' => $data->users[1]->id));
        $this->assertEquals($userassignment->exceptionstatus, 0);

        $exceptions = $DB->get_records('prog_exception');
        $this->assertEmpty($exceptions);

        // Check the timedue = now-1month.
        $compl = $DB->get_record('prog_completion', array('userid' => $data->users[1]->id, 'programid' => $progid, 'coursesetid' => 0));
        $this->assertEquals($compl->timedue, $now - MONTHSECS);
    }

    /**
     * Test that restoration of certification completion, after assignment to a certification with matching courses,
     * does not lead to a duplicate courses exception
     */
    public function test_restoration_exceptions_dupcourse() {
        global $DB, $CFG;

        // Set the restoration setting.
        $CFG->restorecertifenrolments = 1;

        $generator = $this->getDataGenerator();
        $program_generator = $generator->get_plugin_generator('totara_program');
        $data = $this->get_certification_data();

        $now = time();
        $progid = $data->program->id;
        $times = $this->setup_certified_state($data, $now - (3 * MONTHSECS)); // Setup with completion 3 months ago.

        // Duplicate the certification.
        $certdata = array(
            'cert_activeperiod' => '6 Months',
            'cert_windowperiod' => '2 Months',
        );
        $certid = $program_generator->create_certification($certdata);
        $dupcert = new \program($certid);

        $program_generator->add_courses_and_courseset_to_program($dupcert, [[$data->courses[1], $data->courses[2]]], CERTIFPATH_CERT);
        $program_generator->add_courses_and_courseset_to_program($dupcert, [[$data->courses[3]]], CERTIFPATH_RECERT);


        // Unassign.
        $program_generator->assign_program($progid, [$data->users[2]->id]);

        // Assign user 1 to the dupcert.
        $program_generator->assign_program($dupcert->id, [$data->users[1]->id]);

        // Reassign.
        $program_generator->assign_program($progid, [$data->users[1]->id, $data->users[2]->id]);

        // Check that user 1 has a duplicate course exception.
        $userassignment = $DB->get_record('prog_user_assignment', array('userid' => $data->users[1]->id, 'programid' => $progid));
        $this->assertEquals($userassignment->exceptionstatus, 1);

        $exceptions = $DB->get_records('prog_exception');
        $this->assertEquals(count($exceptions), 1);

        $exception = array_shift($exceptions);
        $this->assertEquals($exception->exceptiontype, \totara_program\exception\manager::EXCEPTIONTYPE_DUPLICATE_COURSE);
        $this->assertEquals($exception->programid, $progid);
        $this->assertEquals($exception->userid, $data->users[1]->id);
    }
}
