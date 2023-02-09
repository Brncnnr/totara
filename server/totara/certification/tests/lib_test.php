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
 * @package totara_certification
 */

use core\task\completion_regular_task;
use mod_facetoface\seminar_event;
use mod_facetoface\seminar_event_helper;
use mod_facetoface\signup;
use mod_facetoface\signup\state\fully_attended;
use mod_facetoface\signup_helper;
use totara_core\advanced_feature;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php');
require_once($CFG->dirroot . '/totara/certification/lib.php');
require_once($CFG->dirroot . '/totara/program/lib.php');

/**
 * Certification module PHPUnit archive test class.
 *
 * To test, run this from the command line from the $CFG->dirroot.
 * vendor/bin/phpunit --verbose totara_certification_lib_testcase totara/certification/tests/lib_test.php
 */
class totara_certification_lib_testcase extends reportcache_advanced_testcase {

    public $users = array();
    public $programs = array();
    public $certifications = array();
    public $numtestusers = 10;
    public $numtestcerts = 12;
    public $numtestprogs = 7;

    protected function tearDown(): void {
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

    public function test_find_courses_for_certif() {

        $program_generator = \totara_program\testing\generator::instance();

        // Set up some courses and certifications.
        $courses = array();
        $certifications = array();
        for ($i = 1; $i <= 10; $i++) {
            $courses[$i] = $this->getDataGenerator()->create_course();
            $certifications[$i] = $program_generator->create_certification();
        }

        // Set up some courses in the certifications.
        $program_generator->legacy_add_courseset_program($certifications[2]->id,
            array($courses[2]->id, $courses[3]->id, $courses[4]->id), CERTIFPATH_CERT);
        $program_generator->legacy_add_courseset_program($certifications[2]->id,
            array($courses[3]->id, $courses[4]->id, $courses[5]->id), CERTIFPATH_RECERT);

        $program_generator->legacy_add_courseset_program($certifications[3]->id,
            array($courses[2]->id, $courses[3]->id, $courses[4]->id), CERTIFPATH_CERT);
        $program_generator->legacy_add_courseset_program($certifications[3]->id,
            array($courses[3]->id, $courses[4]->id, $courses[5]->id), CERTIFPATH_RECERT);

        $program_generator->legacy_add_courseset_program($certifications[4]->id,
            array($courses[2]->id, $courses[3]->id, $courses[4]->id), CERTIFPATH_CERT);
        $program_generator->legacy_add_courseset_program($certifications[4]->id,
            array($courses[2]->id, $courses[3]->id, $courses[4]->id), CERTIFPATH_RECERT);

        $program_generator->legacy_add_courseset_program($certifications[5]->id,
            array($courses[8]->id, $courses[9]->id), CERTIFPATH_CERT);
        $program_generator->legacy_add_courseset_program($certifications[5]->id,
            array($courses[8]->id, $courses[9]->id), CERTIFPATH_RECERT);

        $program_generator->legacy_add_courseset_program($certifications[6]->id,
            array($courses[6]->id), CERTIFPATH_CERT);
        $program_generator->legacy_add_courseset_program($certifications[6]->id,
            array($courses[7]->id), CERTIFPATH_RECERT);

        $program_generator->legacy_add_courseset_program($certifications[7]->id,
            array($courses[6]->id), CERTIFPATH_CERT);

        $program_generator->legacy_add_courseset_program($certifications[8]->id,
            array($courses[7]->id), CERTIFPATH_RECERT);

        // Call find_courses_for_certif with each of the three params and ensure that the correct courses are returned.
        $found = array_keys(find_courses_for_certif($certifications[2]->certifid, 'c.id'));
        sort($found);
        $this->assertEquals(array($courses[2]->id, $courses[3]->id, $courses[4]->id, $courses[5]->id), $found);
        $found = array_keys(find_courses_for_certif($certifications[2]->certifid, 'c.id', CERTIFPATH_CERT));
        sort($found);
        $this->assertEquals(array($courses[2]->id, $courses[3]->id, $courses[4]->id), $found);
        $found = array_keys(find_courses_for_certif($certifications[2]->certifid, 'c.id', CERTIFPATH_RECERT));
        sort($found);
        $this->assertEquals(array($courses[3]->id, $courses[4]->id, $courses[5]->id), $found);

        $found = array_keys(find_courses_for_certif($certifications[3]->certifid));
        sort($found);
        $this->assertEquals(array($courses[2]->id, $courses[3]->id, $courses[4]->id, $courses[5]->id), $found); // Note default fields.
        $found = array_keys(find_courses_for_certif($certifications[3]->certifid, 'c.id', CERTIFPATH_CERT));
        sort($found);
        $this->assertEquals(array($courses[2]->id, $courses[3]->id, $courses[4]->id), $found);
        $found = array_keys(find_courses_for_certif($certifications[3]->certifid, 'c.id', CERTIFPATH_RECERT));
        sort($found);
        $this->assertEquals(array($courses[3]->id, $courses[4]->id, $courses[5]->id), $found);

        $found = array_keys(find_courses_for_certif($certifications[4]->certifid, 'c.id'));
        sort($found);
        $this->assertEquals(array($courses[2]->id, $courses[3]->id, $courses[4]->id), $found);
        $found = array_keys(find_courses_for_certif($certifications[4]->certifid, 'c.id', CERTIFPATH_CERT));
        sort($found);
        $this->assertEquals(array($courses[2]->id, $courses[3]->id, $courses[4]->id), $found);
        $found = array_keys(find_courses_for_certif($certifications[4]->certifid, 'c.id', CERTIFPATH_RECERT));
        sort($found);
        $this->assertEquals(array($courses[2]->id, $courses[3]->id, $courses[4]->id), $found);

        $found = array_keys(find_courses_for_certif($certifications[5]->certifid, 'c.id'));
        sort($found);
        $this->assertEquals(array($courses[8]->id, $courses[9]->id), $found);
        $found = array_keys(find_courses_for_certif($certifications[5]->certifid, 'c.id', CERTIFPATH_CERT));
        sort($found);
        $this->assertEquals(array($courses[8]->id, $courses[9]->id), $found);
        $found = array_keys(find_courses_for_certif($certifications[5]->certifid, 'c.id', CERTIFPATH_RECERT));
        sort($found);
        $this->assertEquals(array($courses[8]->id, $courses[9]->id), $found);

        $found = array_keys(find_courses_for_certif($certifications[6]->certifid, 'c.id'));
        sort($found);
        $this->assertEquals(array($courses[6]->id, $courses[7]->id), $found);
        $found = array_keys(find_courses_for_certif($certifications[6]->certifid, 'c.id', CERTIFPATH_CERT));
        sort($found);
        $this->assertEquals(array($courses[6]->id), $found);
        $found = array_keys(find_courses_for_certif($certifications[6]->certifid, 'c.id', CERTIFPATH_RECERT));
        sort($found);
        $this->assertEquals(array($courses[7]->id), $found);

        $found = array_keys(find_courses_for_certif($certifications[7]->certifid, 'c.id'));
        sort($found);
        $this->assertEquals(array($courses[6]->id), $found);
        $found = array_keys(find_courses_for_certif($certifications[7]->certifid, 'c.id', CERTIFPATH_CERT));
        sort($found);
        $this->assertEquals(array($courses[6]->id), $found);
        $found = array_keys(find_courses_for_certif($certifications[7]->certifid, 'c.id', CERTIFPATH_RECERT));
        sort($found);
        $this->assertEquals(array(), $found);

        $found = array_keys(find_courses_for_certif($certifications[8]->certifid, 'c.id'));
        sort($found);
        $this->assertEquals(array($courses[7]->id), $found);
        $found = array_keys(find_courses_for_certif($certifications[8]->certifid, 'c.id', CERTIFPATH_CERT));
        sort($found);
        $this->assertEquals(array(), $found);
        $found = array_keys(find_courses_for_certif($certifications[8]->certifid, 'c.id', CERTIFPATH_RECERT));
        sort($found);
        $this->assertEquals(array($courses[7]->id), $found);

        $found = array_keys(find_courses_for_certif($certifications[9]->certifid, 'c.id'));
        sort($found);
        $this->assertEquals(array(), $found);
        $found = array_keys(find_courses_for_certif($certifications[9]->certifid, 'c.id', CERTIFPATH_CERT));
        sort($found);
        $this->assertEquals(array(), $found);
        $found = array_keys(find_courses_for_certif($certifications[9]->certifid, 'c.id', CERTIFPATH_RECERT));
        sort($found);
        $this->assertEquals(array(), $found);
    }

    /**
     * Note that this function is looking at course SET completion dates. A user might have completed the courses required
     * for a couse set, but if they weren't on that path then they won't have a course set completion record for it.
     */
    public function test_certif_get_content_completion_time() {
        $program_generator = \totara_program\testing\generator::instance();


        // Set up some courses and certifications.
        $courses = array();
        $certifications = array();
        for ($i = 1; $i <= 20; $i++) {
            $courses[$i] = $this->getDataGenerator()->create_course();
        }
        for ($i = 1; $i <= 10; $i++) {
            $certifications[$i] = $program_generator->create_certification();
        }

        // Set up some courses in the certifications.
        $program_generator->legacy_add_courseset_program($certifications[2]->id,
            array($courses[2]->id, $courses[3]->id, $courses[4]->id), CERTIFPATH_CERT);
        $program_generator->legacy_add_courseset_program($certifications[2]->id,
            array($courses[5]->id, $courses[6]->id), CERTIFPATH_RECERT);

        $program_generator->legacy_add_courseset_program($certifications[4]->id,
            array($courses[7]->id, $courses[8]->id), CERTIFPATH_CERT);
        $program_generator->legacy_add_courseset_program($certifications[4]->id,
            array($courses[8]->id, $courses[9]->id), CERTIFPATH_RECERT);

        $program_generator->legacy_add_courseset_program($certifications[6]->id,
            array($courses[11]->id), CERTIFPATH_CERT);
        $program_generator->legacy_add_courseset_program($certifications[6]->id,
            array($courses[12]->id), CERTIFPATH_RECERT);

        // Set up some users.
        $users = array();
        for ($i = 1; $i <= 5; $i++) {
            $users[$i] = $this->getDataGenerator()->create_user();
        }

        // Assign all users to all certification as individuals.
        foreach ($certifications as $certification) {
            foreach ($users as $user) {
                $program_generator->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);
            }
        }

        // Mark some users as complete in some courses, with identifiable completion dates.
        $completiondatas = array(
            // Array(userindex, courseindex, time).
            array(1, 1, 1),
            array(1, 2, 1000),
            array(1, 3, 3000),
            array(1, 4, 2000),
            array(1, 5, 1),
            array(1, 6, 99999),
            array(1, 7, 1),
            array(1, 8, 99999),
            array(1, 9, 99999),
            array(1, 11, 1),
            array(1, 12, 99999),
            array(1, 13, 99999),

            array(2, 2, 8000),
            array(2, 3, 6000),
            array(2, 4, 7000),

            array(3, 2, 1000),
            array(3, 3, 4000),
            array(3, 4, 6000),
            array(3, 5, 1),
            array(3, 6, 99999),
            array(3, 7, 5000),
            array(3, 8, 3000),
            array(3, 9, 99999),
            array(3, 11, 1),
            array(3, 12, 99999),

            array(4, 2, 2000),
            array(4, 3, 3000),
            array(4, 4, 4000),
            array(4, 7, 1000),
            array(4, 8, 2000),
            array(4, 9, 99999),
            array(4, 11, 99999),
            array(4, 12, 5000),

            array(5, 2, 1),
            array(5, 4, 99999),
            array(5, 6, 1),
            array(5, 7, 99999),
        );

        // Put user 4 onto recert path for cert 6 (only complete recert path).
        list($certcompletion, $progcompletion) = certif_load_completion($certifications[6]->id, $users[4]->id);
        $certcompletion->status = CERTIFSTATUS_COMPLETED;
        $certcompletion->renewalstatus = CERTIFRENEWALSTATUS_DUE;
        $certcompletion->certifpath = CERTIFPATH_RECERT;
        $certcompletion->timecompleted = 100;
        $certcompletion->timewindowopens = 200;
        $certcompletion->timeexpires = 300;
        $certcompletion->baselinetimeexpires = 300;
        $progcompletion->timedue = 300;
        $this->assertEquals(array(), certif_get_completion_errors($certcompletion, $progcompletion));
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));

        foreach ($completiondatas as $completiondata) {
            list($user, $course, $time) = $completiondata;
            $completion = new completion_completion(array('userid' => $users[$user]->id, 'course' => $courses[$course]->id));
            $completion->mark_complete($time);
        }

        // Put user 4 onto recert path for cert 2 (complete primary and recert paths).
        list($certcompletion, $progcompletion) = certif_load_completion($certifications[2]->id, $users[4]->id);
        $certcompletion->status = CERTIFSTATUS_COMPLETED;
        $certcompletion->renewalstatus = CERTIFRENEWALSTATUS_DUE;
        $certcompletion->certifpath = CERTIFPATH_RECERT;
        $certcompletion->timecompleted = 100;
        $certcompletion->timewindowopens = 200;
        $certcompletion->timeexpires = 300;
        $certcompletion->baselinetimeexpires = 300;
        $progcompletion->timedue = 300;
        $progcompletion->timecompleted = 0;
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $this->assertEquals(array(), certif_get_completion_errors($certcompletion, $progcompletion));
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));

        $completion = new completion_completion(array('userid' => $users[4]->id, 'course' => $courses[5]->id));
        $completion->mark_complete(1000);
        $completion = new completion_completion(array('userid' => $users[4]->id, 'course' => $courses[6]->id));
        $completion->mark_complete(6000);

        // Call the function, checking if the correct times are returned.

        // These two check that the correct user's results are being returned.
        $this->assertEquals(3000, certif_get_content_completion_time($certifications[2]->certifid, $users[1]->id));
        $this->assertEquals(8000, certif_get_content_completion_time($certifications[2]->certifid, $users[2]->id));

        // These two check that the correct certification's results are being returned (on primary path).
        $this->assertEquals(6000, certif_get_content_completion_time($certifications[2]->certifid, $users[3]->id));
        $this->assertEquals(5000, certif_get_content_completion_time($certifications[4]->certifid, $users[3]->id));

        // These check that the correct certification path results are being returned.
        $this->assertEquals(2000, certif_get_content_completion_time($certifications[4]->certifid, $users[4]->id, CERTIFPATH_CERT));
        $this->assertNull(        certif_get_content_completion_time($certifications[4]->certifid, $users[4]->id, CERTIFPATH_RECERT)); // The prog completion record doesn't exist.
        $this->assertEmpty(       certif_get_content_completion_time($certifications[6]->certifid, $users[4]->id, CERTIFPATH_CERT)); // The prog completion record was created but is not completed.
        $this->assertEquals(5000, certif_get_content_completion_time($certifications[6]->certifid, $users[4]->id, CERTIFPATH_RECERT));
        $this->assertEquals(4000, certif_get_content_completion_time($certifications[2]->certifid, $users[4]->id, CERTIFPATH_CERT));
        $this->assertEquals(6000, certif_get_content_completion_time($certifications[2]->certifid, $users[4]->id, CERTIFPATH_RECERT));

        // Check result when course set not complete (user completed recert path courses, but is on primary path).
        $this->assertEmpty(certif_get_content_completion_time($certifications[2]->certifid, $users[5]->id));
    }

    /**
     * This tests that write_certif_completion is getting the timecompleted from the correct path courses.
     */
    public function test_write_certif_completion_timecompleted_course_path() {
        global $DB;

        $program_generator = \totara_program\testing\generator::instance();

        // Set up some stuff.
        $user = $this->getDataGenerator()->create_user();
        $certification = $program_generator->create_certification();
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();
        $course4 = $this->getDataGenerator()->create_course();

        // Add the courses to the certification.
        $program_generator->legacy_add_courseset_program($certification->id,
            array($course1->id, $course2->id), CERTIFPATH_CERT);
        $program_generator->legacy_add_courseset_program($certification->id,
            array($course3->id, $course4->id), CERTIFPATH_RECERT);

        // Assign the user to the cert as an individual.
        $program_generator->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);

        // Mark all the courses complete, with traceable time completed.
        // Recert path courses first to check that they aren't used for completion date.
        $completion = new completion_completion(array('userid' => $user->id, 'course' => $course3->id));
        $completion->mark_complete(4000);
        $completion = new completion_completion(array('userid' => $user->id, 'course' => $course4->id));
        $completion->mark_complete(3000);
        $completion = new completion_completion(array('userid' => $user->id, 'course' => $course1->id));
        $completion->mark_complete(1000);
        $completion = new completion_completion(array('userid' => $user->id, 'course' => $course2->id));
        $completion->mark_complete(2000);

        // Check the existing data.
        $this->assertEquals(1, $DB->count_records('prog_completion', array('coursesetid' => 0)));
        $this->assertEquals(1, $DB->count_records('certif_completion'));
        list($certcompletion, $progcompletion) = certif_load_completion($certification->id, $user->id);
        $errors = certif_get_completion_errors($certcompletion, $progcompletion);
        $this->assertEquals(array(), $errors);
        $this->assertEquals(2000, $progcompletion->timecompleted);
        $this->assertEquals(2000, $certcompletion->timecompleted);

        // The user is now certified. Update the certification so that the window is open.
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $progcompletion->timecompleted = 0;
        $certcompletion->renewalstatus = CERTIFRENEWALSTATUS_DUE;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion)); // Contains data validation, so we don't need to check it here.

        // Indirectly call write_certif_completion, causing the user to be marked certified again.
        prog_update_completion($user->id);

        // Verify the the user was marked complete using the dates in the recert path courses.
        list($certcompletion, $progcompletion) = certif_load_completion($certification->id, $user->id);
        $this->assertEquals(4000, $progcompletion->timecompleted);
        $this->assertEquals(4000, $certcompletion->timecompleted);

        // Update the certification so that the user is expired.
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

        // Indirectly call write_certif_completion, causing the user to be marked certified again.
        prog_update_completion($user->id);

        // Verify the the user was marked complete using the dates in the primary cert path courses.
        list($certcompletion, $progcompletion) = certif_load_completion($certification->id, $user->id);
        $this->assertEquals(2000, $certcompletion->timecompleted);
        $this->assertEquals(2000, $progcompletion->timecompleted);
    }

    /**
     * Test getting the progress of a certification with a single course set.
     */
    public function test_prog_display_progress_single_then_courseset() {
        $now = time();

        $program_generator = \totara_program\testing\generator::instance();

        $certification = $program_generator->create_certification();
        $user = $this->getDataGenerator()->create_user();
        $courses = array();;
        for ($i = 1; $i <= 5; $i++) {
            $courses[$i] = $this->getDataGenerator()->create_course();
        }

        // Set up some courses in the certifications.
        $program_generator->legacy_add_courseset_program($certification->id,
            array($courses[1]->id, $courses[2]->id, $courses[5]->id), CERTIFPATH_CERT);
        $program_generator->legacy_add_courseset_program($certification->id,
            array($courses[4]->id, $courses[1]->id), CERTIFPATH_RECERT);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $program_generator->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);

        $this->assertSame(0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $completion = new completion_completion(array('userid' => $user->id, 'course' => $courses[1]->id));
        $completion->mark_complete($now);

        $this->assertSame(33, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $completion = new completion_completion(array('userid' => $user->id, 'course' => $courses[2]->id));
        $completion->mark_complete($now);

        $this->assertSame(66, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $completion = new completion_completion(array('userid' => $user->id, 'course' => $courses[5]->id));
        $completion->mark_complete($now);

        // This is stupid - now its not a float!
        $this->assertSame(100, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
    }

    /**
     * Test getting the progress of a certification with two course sets.
     */
    public function test_prog_display_progress_two_then_coursesets() {
        $now = time();

        $program_generator = \totara_program\testing\generator::instance();

        $certification = $program_generator->create_certification();
        $user = $this->getDataGenerator()->create_user();
        $courses = array();;
        for ($i = 1; $i <= 7; $i++) {
            $courses[$i] = $this->getDataGenerator()->create_course();
        }

        // Set up some courses in the certifications.
        $program_generator->legacy_add_courseset_program($certification->id,
            array($courses[1]->id, $courses[2]->id, $courses[5]->id), CERTIFPATH_CERT);
        $program_generator->legacy_add_courseset_program($certification->id,
            array($courses[4]->id, $courses[3]->id), CERTIFPATH_CERT);
        $program_generator->legacy_add_courseset_program($certification->id,
            array($courses[6]->id, $courses[7]->id), CERTIFPATH_RECERT);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $program_generator->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);

        $this->assertSame(0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $completion = new completion_completion(array('userid' => $user->id, 'course' => $courses[1]->id));
        $completion->mark_complete($now);

        $this->assertSame(20, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $completion = new completion_completion(array('userid' => $user->id, 'course' => $courses[2]->id));
        $completion->mark_complete($now);

        $this->assertSame(40, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $completion = new completion_completion(array('userid' => $user->id, 'course' => $courses[5]->id));
        $completion->mark_complete($now);

        $this->assertSame(60, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $completion = new completion_completion(array('userid' => $user->id, 'course' => $courses[4]->id));
        $completion->mark_complete($now);

        $this->assertSame(80, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $completion = new completion_completion(array('userid' => $user->id, 'course' => $courses[3]->id));
        $completion->mark_complete($now);

        // This is stupid - now its not a float!
        $this->assertSame(100, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
    }

    /**
     * Completes the given course and then asserts that cert progress is as expected.
     *
     * @param float|int $expectedprogress
     * @param program $program
     * @param stdClass $user
     * @param stdClass $course
     * @param int $path
     */
    protected function assert_program_progress_after_course_completion($expectedprogress, program $program, stdClass $user, stdClass $course, $path = CERTIFPATH_CERT) {
        $completion = new completion_completion(['userid' => $user->id, 'course' => $course->id]);
        $completion->mark_complete(time());

        $this->assertTrue($completion->is_complete());

        $actualprogress = prog_display_progress($program->id, $user->id, $path, true);

        $this->assertSame($expectedprogress, $actualprogress, "Progress found to be $actualprogress but expected $expectedprogress");
    }

    /**
     * Assert that the parsed courseset groups are as you would expect.
     *
     * @param program $certification
     * @param array $expectednames An array of expected course set groups, each of which is an array containing the set names.
     */
    protected function assert_courseset_groups_contain_expected_names(program $certification, array $expectednames) {

        $certification = new program($certification->id);
        $courseset_groups = $certification->get_content()->get_courseset_groups(CERTIFPATH_CERT, true);

        $this->assertCount(count($expectednames), $courseset_groups);

        $courseset_group_names = [];
        foreach ($courseset_groups as $group) {
            $coursesetnames = [];
            foreach ($group as $courseset) {
                $coursesetnames[] = $courseset->label;
            }
            $courseset_group_names[] = $coursesetnames;
        }
        $this->assertSame($courseset_group_names, $expectednames);
    }

    /**
     * Test getting the progress of a certification with three course sets.
     *
     *  - Course set 1 (A, B, C)
     *      THEN
     *  - Course set 2 (D, E)
     *      THEN
     *  - Course set 3 (F)
     */
    public function test_prog_display_progress_three_then_coursesets() {
        $generator = $this->getDataGenerator();
        $program_generator = \totara_program\testing\generator::instance();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 6; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }
        $certification = $program_generator->legacy_create_certification();
        $program_generator->legacy_add_coursesets_to_program($certification, [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1],
                    $courses[2],
                    $courses[3],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[4],
                    $courses[5],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[6]
                ]
            ]
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1',
            ],
            [
                'Course set 2',
            ],
            [
                'Course set 3',
            ]
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $program_generator->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);

        $this->assertSame(0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $this->assert_program_progress_after_course_completion((int)((1/6)*100), $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion((int)((2/6)*100), $certification, $user, $courses[2]);
        $this->assert_program_progress_after_course_completion((int)((3/6)*100), $certification, $user, $courses[3]);
        $this->assert_program_progress_after_course_completion((int)((4/6)*100), $certification, $user, $courses[4]);
        $this->assert_program_progress_after_course_completion((int)((5/6)*100), $certification, $user, $courses[5]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[6]);
    }

    /**
     * Test getting the progress of a certification with two coursesets using the OR operator.
     */
    public function test_prog_display_progress_two_or_coursesets() {
        $generator = $this->getDataGenerator();
        $program_generator = \totara_program\testing\generator::instance();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 4; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $program_generator->legacy_create_certification();
        $program_generator->legacy_add_coursesets_to_program($certification, [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_OR,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1],
                    $courses[2],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_OR,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[3],
                    $courses[4],
                ]
            ]
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1',
                'Course set 2',
            ]
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $program_generator->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);
        $this->assertSame(0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $this->assert_program_progress_after_course_completion(50, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion(50, $certification, $user, $courses[3]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[2]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[4]);
    }

    /**
     * Test getting the progress of a certification with two course sets using the AND operator.
     */
    public function test_prog_display_progress_two_and_coursesets() {
        $generator = $this->getDataGenerator();
        $program_generator = \totara_program\testing\generator::instance();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 4; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $program_generator->legacy_create_certification();
        $program_generator->legacy_add_coursesets_to_program($certification, [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1],
                    $courses[2],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[3],
                    $courses[4],
                ]
            ]
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1',
                'Course set 2',
            ]
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $program_generator->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);
        $this->assertSame(0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $this->assert_program_progress_after_course_completion((int)((1/4)*100), $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion((int)((2/4)*100), $certification, $user, $courses[3]);
        $this->assert_program_progress_after_course_completion((int)((3/4)*100), $certification, $user, $courses[4]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[2]);
    }

    /**
     * Test getting the progress of a certification with AND plus OR operators.
     *
     * This uses:
     *   Course set 1
     *      AND
     *   Course set 2
     *     THEN
     *   Course set 3
     *      OR
     *   Course set 4
     *     THEN
     *   Course set 5
     */
    public function test_prog_display_progress_and_plus_or_coursesets() {
        $generator = $this->getDataGenerator();
        $program_generator = \totara_program\testing\generator::instance();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 10; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $program_generator->legacy_create_certification();
        $program_generator->legacy_add_coursesets_to_program($certification, [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1],
                    $courses[2],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[3],
                    $courses[4],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_OR,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[5],
                    $courses[6],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[7],
                    $courses[8],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[9],
                    $courses[10],
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1',
                'Course set 2',
            ],
            [
                'Course set 3',
                'Course set 4',
            ],
            [
                'Course set 5',
            ]
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $program_generator->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);
        $this->assertSame(0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $this->assert_program_progress_after_course_completion((int)((1/8)*100), $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion((int)((2/8)*100), $certification, $user, $courses[2]);
        $this->assert_program_progress_after_course_completion((int)((3/8)*100), $certification, $user, $courses[3]);
        $this->assert_program_progress_after_course_completion((int)((4/8)*100), $certification, $user, $courses[4]);
        $this->assert_program_progress_after_course_completion((int)((5/8)*100), $certification, $user, $courses[5]);
        $this->assert_program_progress_after_course_completion((int)((6/8)*100), $certification, $user, $courses[6]);
        $this->assert_program_progress_after_course_completion((int)((6/8)*100), $certification, $user, $courses[7]);
        $this->assert_program_progress_after_course_completion((int)((6/8)*100), $certification, $user, $courses[8]);
        $this->assert_program_progress_after_course_completion((int)((7/8)*100), $certification, $user, $courses[9]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[10]);
    }

    /**
     * Test getting the progress of a certification with OR plus AND operators.
     *
     * This uses:
     *   Course set 1
     *      OR
     *   Course set 2
     *     THEN
     *   Course set 3
     *      AND
     *   Course set 4
     *     THEN
     *   Course set 5
     */
    public function test_prog_display_progress_or_plus_and_coursesets() {
        $generator = $this->getDataGenerator();
        $program_generator = \totara_program\testing\generator::instance();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 10; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $program_generator->legacy_create_certification();
        $program_generator->legacy_add_coursesets_to_program($certification, [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_OR,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1],
                    $courses[2],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[3],
                    $courses[4],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[5],
                    $courses[6],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[7],
                    $courses[8],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[9],
                    $courses[10],
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1',
                'Course set 2',
            ],
            [
                'Course set 3',
                'Course set 4',
            ],
            [
                'Course set 5',
            ]
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $program_generator->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);
        $this->assertSame(0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $this->assert_program_progress_after_course_completion((int)((1/8)*100), $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion((int)((2/8)*100), $certification, $user, $courses[2]);
        $this->assert_program_progress_after_course_completion((int)((2/8)*100), $certification, $user, $courses[3]);
        $this->assert_program_progress_after_course_completion((int)((2/8)*100), $certification, $user, $courses[4]);
        $this->assert_program_progress_after_course_completion((int)((3/8)*100), $certification, $user, $courses[5]);
        $this->assert_program_progress_after_course_completion((int)((4/8)*100), $certification, $user, $courses[6]);
        $this->assert_program_progress_after_course_completion((int)((5/8)*100), $certification, $user, $courses[7]);
        $this->assert_program_progress_after_course_completion((int)((6/8)*100), $certification, $user, $courses[8]);
        $this->assert_program_progress_after_course_completion((int)((7/8)*100), $certification, $user, $courses[9]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[10]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1
     *     THEN
     *   Course set 2 (Optional)
     *     THEN
     *   Course set 3
     *
     * Here we are testing three course sets of which one is optional.
     */
    public function test_prog_display_progress_simple_optional_coursesets() {
        $generator = $this->getDataGenerator();
        $program_generator = \totara_program\testing\generator::instance();

        $user = $generator->create_user();
        $courses = array();
        for ($i = 1; $i <= 6; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $program_generator->legacy_create_certification();
        $program_generator->legacy_add_coursesets_to_program($certification, [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1],
                    $courses[2],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[3],
                    $courses[4],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[5],
                    $courses[6],
                ]
            ]
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1'
            ],
            [
                'Course set 3'
            ]
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $program_generator->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);
        $this->assertSame(0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $this->assert_program_progress_after_course_completion((int)((1/4)*100), $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion((int)((2/4)*100), $certification, $user, $courses[2]);
        $this->assert_program_progress_after_course_completion((int)((2/4)*100), $certification, $user, $courses[3]);
        $this->assert_program_progress_after_course_completion((int)((2/4)*100), $certification, $user, $courses[4]);
        $this->assert_program_progress_after_course_completion((int)((3/4)*100), $certification, $user, $courses[5]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[6]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1 (Optional)
     *
     * Here we are testing that a single optional course set is fine.
     */
    public function test_prog_display_progress_single_optional_courseset() {
        $generator = $this->getDataGenerator();
        $program_generator = \totara_program\testing\generator::instance();

        $user = $generator->create_user();
        $course = $generator->create_course(['summary' => 'A short summary']);

        $certification = $program_generator->legacy_create_certification();
        $program_generator->legacy_add_coursesets_to_program($certification, [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $course
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, []);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $program_generator->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);
        // Completion is checked during assignment.
        $this->assertSame(100, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $course);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1 (Optional)
     *      OR
     *   Course set 2 (Optional)
     *
     * Here we are testing optional or optional.
     */
    public function test_prog_display_progress_OoO() {
        $generator = $this->getDataGenerator();
        $program_generator = \totara_program\testing\generator::instance();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 2; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $program_generator->legacy_create_certification();
        $program_generator->legacy_add_coursesets_to_program($certification, [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_OR,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[2]
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, []);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $program_generator->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);
        // Completion is checked during assignment.
        $this->assertSame(100, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[2]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1 (Optional)
     *      AND
     *   Course set 2 (Optional)
     *
     * Here we are testing optional and optional.
     */
    public function test_prog_display_progress_OaO() {
        $generator = $this->getDataGenerator();
        $program_generator = \totara_program\testing\generator::instance();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 2; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $program_generator->legacy_create_certification();
        $program_generator->legacy_add_coursesets_to_program($certification, [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[2]
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, []);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $program_generator->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);
        // Completion is checked during assignment.
        $this->assertSame(100, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[2]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1
     *      AND
     *   Course set 2 (Optional)
     *
     * Here we are testing required and optional.
     */
    public function test_prog_display_progress_RaO() {
        $generator = $this->getDataGenerator();
        $program_generator = \totara_program\testing\generator::instance();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 2; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $program_generator->legacy_create_certification();
        $program_generator->legacy_add_coursesets_to_program($certification, [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[2]
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1',
                'Course set 2'
            ]
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $program_generator->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);
        $this->assertSame(0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[2]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1 (Optional)
     *      AND
     *   Course set 2
     *
     * Here we are testing optional and required.
     */
    public function test_prog_display_progress_OaR() {
        $generator = $this->getDataGenerator();
        $program_generator = \totara_program\testing\generator::instance();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 2; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $program_generator->legacy_create_certification();
        $program_generator->legacy_add_coursesets_to_program($certification, [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[2]
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1',
                'Course set 2',
            ]
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $program_generator->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);
        $this->assertSame(0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
        $this->assert_program_progress_after_course_completion(0, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[2]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1 (Optional)
     *      OR
     *   Course set 2
     *
     * Here we are testing optional or required.
     */
    public function test_prog_display_progress_OoR() {
        $generator = $this->getDataGenerator();
        $program_generator = \totara_program\testing\generator::instance();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 2; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $program_generator->legacy_create_certification();
        $program_generator->legacy_add_coursesets_to_program($certification, [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_OR,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[2]
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, []);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $program_generator->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);
        // Completion is checked during assignment.
        $this->assertSame(100, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[2]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1
     *      AND
     *   Course set 2  (Optional)
     *      AND
     *   Course set 3  (Optional)
     *
     * Here we are testing required and optional and optional.
     */
    public function test_prog_display_progress_RaOaO() {
        $generator = $this->getDataGenerator();
        $program_generator = \totara_program\testing\generator::instance();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 3; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $program_generator->legacy_create_certification();
        $program_generator->legacy_add_coursesets_to_program($certification, [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[2]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[3]
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1',
                'Course set 2',
                'Course set 3',
            ]
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $program_generator->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);
        $this->assertSame(0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[2]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[3]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1  (Optional)
     *      AND
     *   Course set 2  (Optional)
     *      AND
     *   Course set 3
     *
     * Here we are testing optional and optional and required.
     */
    public function test_prog_display_progress_OaOaR() {
        $generator = $this->getDataGenerator();
        $program_generator = \totara_program\testing\generator::instance();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 3; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $program_generator->legacy_create_certification();
        $program_generator->legacy_add_coursesets_to_program($certification, [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[2]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[3]
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1',
                'Course set 2',
                'Course set 3',
            ]
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $program_generator->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);
        $this->assertSame(0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
        $this->assert_program_progress_after_course_completion(0, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion(0, $certification, $user, $courses[2]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[3]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1  (Optional)
     *      AND
     *   Course set 2
     *      AND
     *   Course set 3  (Optional)
     *
     * Here we are testing optional and required and optional.
     */
    public function test_prog_display_progress_OaRaO() {
        $generator = $this->getDataGenerator();
        $program_generator = \totara_program\testing\generator::instance();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 3; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $program_generator->legacy_create_certification();
        $program_generator->legacy_add_coursesets_to_program($certification, [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[2]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[3]
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1',
                'Course set 2',
                'Course set 3',
            ]
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $program_generator->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);
        $this->assertSame(0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
        $this->assert_program_progress_after_course_completion(0, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[2]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[3]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1
     *      AND
     *   Course set 2  (Optional)
     *      AND
     *   Course set 3
     *
     * Here we are testing required and optional and required.
     */
    public function test_prog_display_progress_RaOaR() {
        $generator = $this->getDataGenerator();
        $program_generator = \totara_program\testing\generator::instance();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 3; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $program_generator->legacy_create_certification();
        $program_generator->legacy_add_coursesets_to_program($certification, [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[2]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[3]
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1',
                'Course set 2',
                'Course set 3',
            ]
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $program_generator->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);
        $this->assertSame(0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
        $this->assert_program_progress_after_course_completion((int)((1/2)*100), $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion((int)((1/2)*100), $certification, $user, $courses[2]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[3]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1
     *      AND
     *   Course set 2 (Optional)
     *     THEN
     *   Course set 3 (Optional)
     *      AND
     *   Course set 4
     *     THEN
     *   Course set 5 (Optional)
     *
     * Here we are testing three course sets groups each with an optional courseset.
     */
    public function test_prog_display_progress_RaOtOaRto() {
        $generator = $this->getDataGenerator();
        $program_generator = \totara_program\testing\generator::instance();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 10; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $program_generator->legacy_create_certification();
        $program_generator->legacy_add_coursesets_to_program($certification, [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1],
                    $courses[2],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[3],
                    $courses[4],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[5],
                    $courses[6],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[7],
                    $courses[8],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[9],
                    $courses[10],
                ]
            ],
        ]);

        $certification = new program($certification->id);

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1',
                'Course set 2',
            ],
            [
                'Course set 3',
                'Course set 4',
            ]
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $program_generator->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);
        $this->assertSame(0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $this->assert_program_progress_after_course_completion((int)((1/4)*100), $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion((int)((2/4)*100), $certification, $user, $courses[2]);
        // Complete set 1. Set 2 + 3 skipped as optional.
        $this->assert_program_progress_after_course_completion((int)((2/4)*100), $certification, $user, $courses[3]);
        $this->assert_program_progress_after_course_completion((int)((2/4)*100), $certification, $user, $courses[4]);
        $this->assert_program_progress_after_course_completion((int)((2/4)*100), $certification, $user, $courses[5]);
        $this->assert_program_progress_after_course_completion((int)((2/4)*100), $certification, $user, $courses[6]);
        $this->assert_program_progress_after_course_completion((int)((3/4)*100), $certification, $user, $courses[7]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[8]);
        // Completed set 4. Set 5 skipped as optional.
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[9]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[10]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1
     *      AND
     *   Course set 2 (Some - 0)
     *     THEN
     *   Course set 3 (Some - 0)
     *      AND
     *   Course set 4
     *     THEN
     *   Course set 5 (Some - 0)
     *
     * Here we are testing three course set groups each with a some courses = 0 courseset.
     */
    public function test_prog_display_progress_RaStSaRtS_mincourses_0() {
        $generator = $this->getDataGenerator();
        $program_generator = \totara_program\testing\generator::instance();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 10; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $program_generator->legacy_create_certification();
        $program_generator->legacy_add_coursesets_to_program($certification, [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1],
                    $courses[2],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_SOME,
                'certifpath' => CERTIFPATH_STD,
                'mincourses' => 0,
                'courses' => [
                    $courses[3],
                    $courses[4],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_SOME,
                'mincourses' => 0,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[5],
                    $courses[6],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[7],
                    $courses[8],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_SOME,
                'mincourses' => 0,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[9],
                    $courses[10],
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1',
                'Course set 2',
            ],
            [
                'Course set 3',
                'Course set 4',
            ]
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $program_generator->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);
        // Mincourses == 0 implies that courseset is completed
        $this->assertSame((int)((3/7)*100), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true, true));

        $this->assert_program_progress_after_course_completion((int)((4/7)*100), $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion((int)((5/7)*100), $certification, $user, $courses[2]);
        $this->assert_program_progress_after_course_completion((int)((5/7)*100), $certification, $user, $courses[3]);
        $this->assert_program_progress_after_course_completion((int)((5/7)*100), $certification, $user, $courses[4]);
        $this->assert_program_progress_after_course_completion((int)((5/7)*100), $certification, $user, $courses[5]);
        $this->assert_program_progress_after_course_completion((int)((5/7)*100), $certification, $user, $courses[6]);
        $this->assert_program_progress_after_course_completion((int)((6/7)*100), $certification, $user, $courses[7]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[8]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[9]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[10]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1
     *      AND
     *   Course set 2 (Some - 1)
     *     THEN
     *   Course set 3 (Some - 1)
     *      AND
     *   Course set 4
     *     THEN
     *   Course set 5 (Some - 1)
     *
     * Here we are testing three course sets groups each with a some courses = 1 courseset.
     */
    public function test_prog_display_progress_RaStSaRtS_mincourses_1() {
        $generator = $this->getDataGenerator();
        $program_generator = \totara_program\testing\generator::instance();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 10; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $program_generator->legacy_create_certification();
        $program_generator->legacy_add_coursesets_to_program($certification, [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1],
                    $courses[2],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_SOME,
                'certifpath' => CERTIFPATH_STD,
                'mincourses' => 1,
                'courses' => [
                    $courses[3],
                    $courses[4],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_SOME,
                'mincourses' => 1,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[5],
                    $courses[6],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[7],
                    $courses[8],
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_SOME,
                'mincourses' => 1,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[9],
                    $courses[10],
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1',
                'Course set 2',
            ],
            [
                'Course set 3',
                'Course set 4',
            ],
            [
                'Course set 5',
            ],
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $program_generator->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);
        $this->assertSame(0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $this->assert_program_progress_after_course_completion((int)((1/7)*100), $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion((int)((2/7)*100), $certification, $user, $courses[2]); // Course set 1 complete now.

        $this->assert_program_progress_after_course_completion((int)((3/7)*100), $certification, $user, $courses[3]); // Course set 2 complete now.
        $this->assert_program_progress_after_course_completion((int)((3/7)*100), $certification, $user, $courses[4]);

        $this->assert_program_progress_after_course_completion((int)((4/7)*100), $certification, $user, $courses[5]); // Course set 3 complete now.
        $this->assert_program_progress_after_course_completion((int)((4/7)*100), $certification, $user, $courses[6]);

        $this->assert_program_progress_after_course_completion((int)((5/7)*100), $certification, $user, $courses[7]);
        $this->assert_program_progress_after_course_completion((int)((6/7)*100), $certification, $user, $courses[8]); // Course set 4 complete now.

        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[9]); // Course set 5 complete now.
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[10]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1
     *      AND
     *   Course set 2  (Optional)
     *      OR
     *   Course set 3  (Optional)
     *
     * Here we are testing required and optional or optional.
     */
    public function test_prog_display_progress_RaOoO() {
        $generator = $this->getDataGenerator();
        $program_generator = \totara_program\testing\generator::instance();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 3; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $program_generator->legacy_create_certification();
        $program_generator->legacy_add_coursesets_to_program($certification, [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_OR,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[2]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[3]
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, []);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $program_generator->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);
        // Completion is checked during assignment.
        $this->assertSame(100, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[2]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[3]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1  (Optional)
     *      AND
     *   Course set 2  (Optional)
     *      OR
     *   Course set 3
     *
     * Here we are testing optional and optional or required.
     */
    public function test_prog_display_progress_OaOoR() {
        $generator = $this->getDataGenerator();
        $program_generator = \totara_program\testing\generator::instance();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 3; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $program_generator->legacy_create_certification();
        $program_generator->legacy_add_coursesets_to_program($certification, [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_OR,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[2]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[3]
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, []);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $program_generator->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);
        // Completion is checked during assignment.
        $this->assertSame(100, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[2]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[3]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1  (Optional)
     *      AND
     *   Course set 2
     *      OR
     *   Course set 3  (Optional)
     *
     * Here we are testing optional and required or optional.
     */
    public function test_prog_display_progress_OaRoO() {
        $generator = $this->getDataGenerator();
        $program_generator = \totara_program\testing\generator::instance();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 3; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $program_generator->legacy_create_certification();
        $program_generator->legacy_add_coursesets_to_program($certification, [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_OR,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[2]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[3]
                ]
            ],
        ]);

        $this->assert_courseset_groups_contain_expected_names($certification, []);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        // Assign the user to the cert as an individual.
        $program_generator->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);
        // Completion is checked during assignment.
        $this->assertSame(100, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[2]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[3]);
    }

    /**
     * Test getting the progress of a certification with the following structure.
     *
     * This uses:
     *   Course set 1
     *      AND
     *   Course set 2  (Optional)
     *      OR
     *   Course set 3
     *
     * Here we are testing required and optional or required.
     */
    public function test_prog_display_progress_RaOoR() {
        $generator = $this->getDataGenerator();
        $program_generator = \totara_program\testing\generator::instance();

        $user = $generator->create_user();
        $courses = array();;
        for ($i = 1; $i <= 3; $i++) {
            $courses[$i] = $generator->create_course(['summary' => 'A short summary']);
        }

        $certification = $program_generator->legacy_create_certification();
        $program_generator->legacy_add_coursesets_to_program($certification, [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_AND,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[1]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_OR,
                'completiontype' => COMPLETIONTYPE_OPTIONAL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[2]
                ]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => [
                    $courses[3]
                ]
            ],
        ]);

        // This is stupid, but done. We want to know if anyone changes it.
        $this->assertSame(get_string('notassigned', 'totara_program'), prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));

        $this->assert_courseset_groups_contain_expected_names($certification, [
            [
                'Course set 1',
                'Course set 2',
                'Course set 3'
            ]
        ]);

        // Assign the user to the cert as an individual.
        $program_generator->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);
        $this->assertSame(0, prog_display_progress($certification->id, $user->id, CERTIFPATH_CERT, true));
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[1]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[2]);
        $this->assert_program_progress_after_course_completion(100, $certification, $user, $courses[3]);
    }

    /**
     * This tests that recertify_window_opens_stage and recertify_expires_stage are resetting the correct courses
     * for the correct users and certification paths.
     */
    public function test_recertify_window_opens_stage_and_recertify_expires_stage() {
        global $DB;

        $generator = $this->getDataGenerator();
        $program_generator = \totara_program\testing\generator::instance();

        // Set up some users, courses and certifications.
        $users = array();
        $courses = array();
        $certifications = array();
        for ($i = 1; $i <= 10; $i++) {
            $user = $generator->create_user();
            $users[$i] = $user;
            $course = $generator->create_course();
            $courses[$i] = $course;
            $certification = $program_generator->create_certification(['cert_windowperiod' => '6 month']);
            $certifications[$i] = $certification;
        }

        // Set up some courses in the certifications.
        $program_generator->legacy_add_courseset_program($certifications[5]->id, [$courses[2]->id, $courses[3]->id, $courses[4]->id], CERTIFPATH_CERT);
        $program_generator->legacy_add_courseset_program($certifications[5]->id, [$courses[6]->id, $courses[7]->id], CERTIFPATH_RECERT);

        $program_generator->legacy_add_courseset_program($certifications[7]->id, [$courses[2]->id, $courses[3]->id], CERTIFPATH_CERT);
        $program_generator->legacy_add_courseset_program($certifications[7]->id, [$courses[3]->id, $courses[4]->id], CERTIFPATH_RECERT);

        $program_generator->legacy_add_courseset_program($certifications[9]->id, [$courses[6]->id], CERTIFPATH_CERT);
        $program_generator->legacy_add_courseset_program($certifications[9]->id, [$courses[7]->id], CERTIFPATH_RECERT);

        // Assign some users to certifications as individuals.
        $program_generator->assign_to_program($certifications[5]->id, ASSIGNTYPE_INDIVIDUAL, $users[3]->id, null, true); // User 3 to cert 5 (will complete past).
        $program_generator->assign_to_program($certifications[5]->id, ASSIGNTYPE_INDIVIDUAL, $users[4]->id, null, true); // User 4 to cert 5 (will complete future).
        $program_generator->assign_to_program($certifications[7]->id, ASSIGNTYPE_INDIVIDUAL, $users[5]->id, null, true); // User 5 to cert 7 (will complete future).
        $program_generator->assign_to_program($certifications[9]->id, ASSIGNTYPE_INDIVIDUAL, $users[5]->id, null, true); // User 5 to cert 9 (will complete future).
        $program_generator->assign_to_program($certifications[7]->id, ASSIGNTYPE_INDIVIDUAL, $users[6]->id, null, true); // User 6 to cert 7 (will complete past).
        $program_generator->assign_to_program($certifications[9]->id, ASSIGNTYPE_INDIVIDUAL, $users[6]->id, null, true); // User 6 to cert 9 (will complete past).
        $program_generator->assign_to_program($certifications[7]->id, ASSIGNTYPE_INDIVIDUAL, $users[7]->id, null, true); // User 7 to cert 7 (will not complete).
        $program_generator->assign_to_program($certifications[9]->id, ASSIGNTYPE_INDIVIDUAL, $users[7]->id, null, true); // User 7 to cert 9 (will complete future).
        $this->assertEquals(8, $DB->count_records('prog_completion', array('coursesetid' => 0)));
        $this->assertEquals(8, $DB->count_records('certif_completion'));
        $records = $DB->get_records('certif_completion');
        foreach ($records as $record) {
            $this->assertEquals(CERTIFSTATUS_ASSIGNED, $record->status);
        }

        // Set up some times. The past dates are far in the past, so the window open and expiry events are both due.
        $now = time();
        $timepast = $now - DAYSECS * 15 * 30; // Fifteen months in the past.
        $timepast1 = $timepast + DAYSECS * 2;
        $timepast2 = $timepast + DAYSECS * 4;
        $timepast3 = $timepast + DAYSECS * 6;
        $timefuture = $now + DAYSECS * 30; // One month in the future.
        $timefuture1 = $timefuture + DAYSECS * 2;
        $timefuture2 = $timefuture + DAYSECS * 4;
        $timefuture3 = $timefuture + DAYSECS * 6;

        // User 3 completed cert5 in the distant past, so window should be due to open immediately.
        $completion = new completion_completion(array('userid' => $users[3]->id, 'course' => $courses[2]->id));
        $completion->mark_complete($timepast1);
        $completion = new completion_completion(array('userid' => $users[3]->id, 'course' => $courses[3]->id));
        $completion->mark_complete($timepast2);
        $completion = new completion_completion(array('userid' => $users[3]->id, 'course' => $courses[4]->id));
        $completion->mark_complete($timepast3);
        $completion = new completion_completion(array('userid' => $users[3]->id, 'course' => $courses[6]->id)); // Not needed for primary cert, but will be reset.
        $completion->mark_complete($timepast1);
        $completion = new completion_completion(array('userid' => $users[3]->id, 'course' => $courses[7]->id)); // Not needed for primary cert, but will be reset.
        $completion->mark_complete($timepast2);

        // User 4 completed cert5 with window open in the future, so no window open.
        $completion = new completion_completion(array('userid' => $users[4]->id, 'course' => $courses[2]->id));
        $completion->mark_complete($timefuture2);
        $completion = new completion_completion(array('userid' => $users[4]->id, 'course' => $courses[3]->id));
        $completion->mark_complete($timefuture3);
        $completion = new completion_completion(array('userid' => $users[4]->id, 'course' => $courses[4]->id));
        $completion->mark_complete($timefuture1);

        // User 5 completed cert7 and cert9 in the future, as well as some other courses.
        $completion = new completion_completion(array('userid' => $users[5]->id, 'course' => $courses[2]->id));
        $completion->mark_complete($timefuture1);
        $completion = new completion_completion(array('userid' => $users[5]->id, 'course' => $courses[3]->id));
        $completion->mark_complete($timefuture2);
        $completion = new completion_completion(array('userid' => $users[5]->id, 'course' => $courses[6]->id));
        $completion->mark_complete($timefuture3);
        $completion = new completion_completion(array('userid' => $users[5]->id, 'course' => $courses[5]->id)); // In past, but not part of any cert.
        $completion->mark_complete($timepast1);
        $completion = new completion_completion(array('userid' => $users[5]->id, 'course' => $courses[8]->id)); // In past, but not part of any cert.
        $completion->mark_complete($timepast2);
        $completion = new completion_completion(array('userid' => $users[5]->id, 'course' => $courses[9]->id)); // In past, but not part of any cert.
        $completion->mark_complete($timepast3);

        // User 6 completed cert7 and cert9 in the distant past, so window should be due to open immediately.
        $completion = new completion_completion(array('userid' => $users[6]->id, 'course' => $courses[2]->id));
        $completion->mark_complete($timepast1);
        $completion = new completion_completion(array('userid' => $users[6]->id, 'course' => $courses[3]->id));
        $completion->mark_complete($timepast3);
        $completion = new completion_completion(array('userid' => $users[6]->id, 'course' => $courses[4]->id)); // Not needed for primary cert, but will be reset.
        $completion->mark_complete($timepast3);
        $completion = new completion_completion(array('userid' => $users[6]->id, 'course' => $courses[6]->id));
        $completion->mark_complete($timepast2);
        $completion = new completion_completion(array('userid' => $users[6]->id, 'course' => $courses[7]->id)); // Not needed for primary cert, but will be reset.
        $completion->mark_complete($timepast2);

        // User 7 completed cert9 in the future.
        $completion = new completion_completion(array('userid' => $users[7]->id, 'course' => $courses[6]->id));
        $completion->mark_complete($timefuture1);

        // Check that the correct certs have been marked complete.
        $this->assertEquals(8, $DB->count_records('prog_completion', array('coursesetid' => 0)));
        $records = $DB->get_records('certif_completion');
        $this->assertEquals(8, count($records));
        foreach ($records as $record) {
            if ($record->userid == $users[7]->id && $record->certifid == $certifications[7]->certifid) {
                // Assigned, not complete.
                $this->assertEquals(CERTIFSTATUS_ASSIGNED, $record->status);
                $this->assertEquals(CERTIFRENEWALSTATUS_NOTDUE, $record->renewalstatus);
            } else {
                // Complete (window open and expired not yet processed).
                $this->assertEquals(CERTIFSTATUS_COMPLETED, $record->status);
                $this->assertEquals(CERTIFRENEWALSTATUS_NOTDUE, $record->renewalstatus);
            }
        }

        // Check that the correct courses are marked complete.
        $records = $DB->get_records('course_completions');
        $this->assertEquals(20, count($records));
        foreach ($records as $record) {
            // All 20 are complete.
            if ($record->userid == $users[3]->id && $record->course == $courses[2]->id ||
                $record->userid == $users[3]->id && $record->course == $courses[3]->id ||
                $record->userid == $users[3]->id && $record->course == $courses[4]->id ||
                $record->userid == $users[3]->id && $record->course == $courses[6]->id ||
                $record->userid == $users[3]->id && $record->course == $courses[7]->id ||
                $record->userid == $users[4]->id && $record->course == $courses[2]->id ||
                $record->userid == $users[4]->id && $record->course == $courses[3]->id ||
                $record->userid == $users[4]->id && $record->course == $courses[4]->id ||
                $record->userid == $users[5]->id && $record->course == $courses[2]->id ||
                $record->userid == $users[5]->id && $record->course == $courses[3]->id ||
                $record->userid == $users[5]->id && $record->course == $courses[5]->id ||
                $record->userid == $users[5]->id && $record->course == $courses[6]->id ||
                $record->userid == $users[5]->id && $record->course == $courses[8]->id ||
                $record->userid == $users[5]->id && $record->course == $courses[9]->id ||
                $record->userid == $users[6]->id && $record->course == $courses[2]->id ||
                $record->userid == $users[6]->id && $record->course == $courses[3]->id ||
                $record->userid == $users[6]->id && $record->course == $courses[4]->id ||
                $record->userid == $users[6]->id && $record->course == $courses[6]->id ||
                $record->userid == $users[6]->id && $record->course == $courses[7]->id ||
                $record->userid == $users[7]->id && $record->course == $courses[6]->id) {
                $this->assertEquals(COMPLETION_STATUS_COMPLETE, $record->status);
            } else {
                $this->assertTrue(false);
            }
        }

        // Trigger expiry - this should do nothing, because no cert windows have yet been opened (the code should
        // only be applied to certs in the "window open" state, even if the expiry date has passed).
        recertify_expires_stage();

        // Check that the certs are still marked complete (same checks as before).
        $this->assertEquals(8, $DB->count_records('prog_completion', array('coursesetid' => 0)));
        $records = $DB->get_records('certif_completion');
        $this->assertEquals(8, count($records));
        foreach ($records as $record) {
            if ($record->userid == $users[7]->id && $record->certifid == $certifications[7]->certifid) {
                // Assigned, not complete.
                $this->assertEquals(CERTIFSTATUS_ASSIGNED, $record->status);
                $this->assertEquals(CERTIFRENEWALSTATUS_NOTDUE, $record->renewalstatus);
            } else {
                // Complete (window open and expired not yet processed).
                $this->assertEquals(CERTIFSTATUS_COMPLETED, $record->status);
                $this->assertEquals(CERTIFRENEWALSTATUS_NOTDUE, $record->renewalstatus);
            }
        }

        // Check that the courses are still marked complete (same checks as before).
        $records = $DB->get_records('course_completions');
        $this->assertEquals(20, count($records));
        foreach ($records as $record) {
            // All 20 are complete.
            if ($record->userid == $users[3]->id && $record->course == $courses[2]->id ||
                $record->userid == $users[3]->id && $record->course == $courses[3]->id ||
                $record->userid == $users[3]->id && $record->course == $courses[4]->id ||
                $record->userid == $users[3]->id && $record->course == $courses[6]->id ||
                $record->userid == $users[3]->id && $record->course == $courses[7]->id ||
                $record->userid == $users[4]->id && $record->course == $courses[2]->id ||
                $record->userid == $users[4]->id && $record->course == $courses[3]->id ||
                $record->userid == $users[4]->id && $record->course == $courses[4]->id ||
                $record->userid == $users[5]->id && $record->course == $courses[2]->id ||
                $record->userid == $users[5]->id && $record->course == $courses[3]->id ||
                $record->userid == $users[5]->id && $record->course == $courses[5]->id ||
                $record->userid == $users[5]->id && $record->course == $courses[6]->id ||
                $record->userid == $users[5]->id && $record->course == $courses[8]->id ||
                $record->userid == $users[5]->id && $record->course == $courses[9]->id ||
                $record->userid == $users[6]->id && $record->course == $courses[2]->id ||
                $record->userid == $users[6]->id && $record->course == $courses[3]->id ||
                $record->userid == $users[6]->id && $record->course == $courses[4]->id ||
                $record->userid == $users[6]->id && $record->course == $courses[6]->id ||
                $record->userid == $users[6]->id && $record->course == $courses[7]->id ||
                $record->userid == $users[7]->id && $record->course == $courses[6]->id) {
                $this->assertEquals(COMPLETION_STATUS_COMPLETE, $record->status);
            } else {
                $this->assertTrue(false);
            }
        }

        // Trigger window open only (expiry will occur later).
        recertify_window_opens_stage();

        // Check that the correct certs have been opened for recertification.
        $this->assertEquals(8, $DB->count_records('prog_completion', array('coursesetid' => 0)));
        $records = $DB->get_records('certif_completion');
        $this->assertEquals(8, count($records));
        foreach ($records as $record) {
            if ($record->userid == $users[3]->id && $record->certifid == $certifications[5]->certifid ||
                $record->userid == $users[6]->id && $record->certifid == $certifications[7]->certifid ||
                $record->userid == $users[6]->id && $record->certifid == $certifications[9]->certifid) {
                // Window opened (were completed in past).
                $this->assertEquals(CERTIFSTATUS_COMPLETED, $record->status);
                $this->assertEquals(CERTIFRENEWALSTATUS_DUE, $record->renewalstatus);
            } else if (
                $record->userid == $users[4]->id && $record->certifid == $certifications[5]->certifid ||
                $record->userid == $users[5]->id && $record->certifid == $certifications[7]->certifid ||
                $record->userid == $users[5]->id && $record->certifid == $certifications[9]->certifid ||
                $record->userid == $users[7]->id && $record->certifid == $certifications[9]->certifid) {
                // Certified, window not yet open (were completed in future).
                $this->assertEquals(CERTIFSTATUS_COMPLETED, $record->status);
                $this->assertEquals(CERTIFRENEWALSTATUS_NOTDUE, $record->renewalstatus);
            } else {
                // Assigned, not complete.
                $this->assertEquals(CERTIFSTATUS_ASSIGNED, $record->status);
                $this->assertEquals(CERTIFRENEWALSTATUS_NOTDUE, $record->renewalstatus);
            }
        }

        // Check that the correct courses have been reset.
        $records = $DB->get_records('course_completions');
        $this->assertEquals(15, count($records));
        foreach ($records as $record) {
            // Removed 3-6, 3-7, 6-3, 6-4, 6-7.
            if ($record->userid == $users[3]->id && $record->course == $courses[2]->id ||
                $record->userid == $users[3]->id && $record->course == $courses[3]->id ||
                $record->userid == $users[3]->id && $record->course == $courses[4]->id ||
                $record->userid == $users[4]->id && $record->course == $courses[2]->id ||
                $record->userid == $users[4]->id && $record->course == $courses[3]->id ||
                $record->userid == $users[4]->id && $record->course == $courses[4]->id ||
                $record->userid == $users[5]->id && $record->course == $courses[2]->id ||
                $record->userid == $users[5]->id && $record->course == $courses[3]->id ||
                $record->userid == $users[5]->id && $record->course == $courses[5]->id ||
                $record->userid == $users[5]->id && $record->course == $courses[6]->id ||
                $record->userid == $users[5]->id && $record->course == $courses[8]->id ||
                $record->userid == $users[5]->id && $record->course == $courses[9]->id ||
                $record->userid == $users[6]->id && $record->course == $courses[2]->id ||
                $record->userid == $users[6]->id && $record->course == $courses[6]->id ||
                $record->userid == $users[7]->id && $record->course == $courses[6]->id) {
                $this->assertEquals(COMPLETION_STATUS_COMPLETE, $record->status);
            } else {
                $this->assertTrue(false);
            }
        }

        // Mark these records as complete again, to be sure it isn't being reset during expiry.
        // We're only marking 1 of the 2 courses complete, because we don't want to trigger recertification.
        $completion = new completion_completion(array('userid' => $users[3]->id, 'course' => $courses[6]->id)); // Is only on recert path.
        $completion->mark_complete($timepast2);
        $completion = new completion_completion(array('userid' => $users[6]->id, 'course' => $courses[3]->id)); // Is on recert and primary.
        $completion->mark_complete($timepast3);

        // Check that certification status hasn't changed for any users.
        $this->assertEquals(8, $DB->count_records('prog_completion', array('coursesetid' => 0)));
        $records = $DB->get_records('certif_completion');
        $this->assertEquals(8, count($records));
        foreach ($records as $record) {
            if ($record->userid == $users[3]->id && $record->certifid == $certifications[5]->certifid ||
                $record->userid == $users[6]->id && $record->certifid == $certifications[7]->certifid ||
                $record->userid == $users[6]->id && $record->certifid == $certifications[9]->certifid) {
                // Window opened (were completed in past).
                $this->assertEquals(CERTIFSTATUS_COMPLETED, $record->status);
                $this->assertEquals(CERTIFRENEWALSTATUS_DUE, $record->renewalstatus);
            } else if (
                $record->userid == $users[4]->id && $record->certifid == $certifications[5]->certifid ||
                $record->userid == $users[5]->id && $record->certifid == $certifications[7]->certifid ||
                $record->userid == $users[5]->id && $record->certifid == $certifications[9]->certifid ||
                $record->userid == $users[7]->id && $record->certifid == $certifications[9]->certifid) {
                // Certified, window not yet open (were completed in future).
                $this->assertEquals(CERTIFSTATUS_COMPLETED, $record->status);
                $this->assertEquals(CERTIFRENEWALSTATUS_NOTDUE, $record->renewalstatus);
            } else {
                // Assigned, not complete.
                $this->assertEquals(CERTIFSTATUS_ASSIGNED, $record->status);
                $this->assertEquals(CERTIFRENEWALSTATUS_NOTDUE, $record->renewalstatus);
            }
        }

        // Check that the correct courses have been reset.
        $records = $DB->get_records('course_completions');
        $this->assertEquals(17, count($records));
        foreach ($records as $record) {
            // Added back 3-6 and 6-3.
            if ($record->userid == $users[3]->id && $record->course == $courses[2]->id ||
                $record->userid == $users[3]->id && $record->course == $courses[3]->id ||
                $record->userid == $users[3]->id && $record->course == $courses[4]->id ||
                $record->userid == $users[3]->id && $record->course == $courses[6]->id ||
                $record->userid == $users[4]->id && $record->course == $courses[2]->id ||
                $record->userid == $users[4]->id && $record->course == $courses[3]->id ||
                $record->userid == $users[4]->id && $record->course == $courses[4]->id ||
                $record->userid == $users[5]->id && $record->course == $courses[2]->id ||
                $record->userid == $users[5]->id && $record->course == $courses[3]->id ||
                $record->userid == $users[5]->id && $record->course == $courses[5]->id ||
                $record->userid == $users[5]->id && $record->course == $courses[6]->id ||
                $record->userid == $users[5]->id && $record->course == $courses[8]->id ||
                $record->userid == $users[5]->id && $record->course == $courses[9]->id ||
                $record->userid == $users[6]->id && $record->course == $courses[2]->id ||
                $record->userid == $users[6]->id && $record->course == $courses[3]->id ||
                $record->userid == $users[6]->id && $record->course == $courses[6]->id ||
                $record->userid == $users[7]->id && $record->course == $courses[6]->id) {
                $this->assertEquals(COMPLETION_STATUS_COMPLETE, $record->status);
            } else {
                $this->assertTrue(false);
            }
        }

        // Trigger expiry.
        recertify_expires_stage();

        // Check that the correct certs have expired.
        $this->assertEquals(8, $DB->count_records('prog_completion', array('coursesetid' => 0)));
        $records = $DB->get_records('certif_completion');
        $this->assertEquals(8, count($records));
        foreach ($records as $record) {
            if ($record->userid == $users[3]->id && $record->certifid == $certifications[5]->certifid ||
                $record->userid == $users[6]->id && $record->certifid == $certifications[7]->certifid ||
                $record->userid == $users[6]->id && $record->certifid == $certifications[9]->certifid) {
                // Expired.
                $this->assertEquals(CERTIFSTATUS_EXPIRED, $record->status);
                $this->assertEquals(CERTIFRENEWALSTATUS_EXPIRED, $record->renewalstatus);
            } else if (
                $record->userid == $users[4]->id && $record->certifid == $certifications[5]->certifid ||
                $record->userid == $users[5]->id && $record->certifid == $certifications[7]->certifid ||
                $record->userid == $users[5]->id && $record->certifid == $certifications[9]->certifid ||
                $record->userid == $users[7]->id && $record->certifid == $certifications[9]->certifid) {
                // Certified, window not yet open (were completed in future).
                $this->assertEquals(CERTIFSTATUS_COMPLETED, $record->status);
                $this->assertEquals(CERTIFRENEWALSTATUS_NOTDUE, $record->renewalstatus);
            } else {
                // Assigned, not complete.
                $this->assertEquals(CERTIFSTATUS_ASSIGNED, $record->status);
                $this->assertEquals(CERTIFRENEWALSTATUS_NOTDUE, $record->renewalstatus);
            }
        }

        // Check that the correct courses have been reset (includes previous window open plus primary path reset).
        $records = $DB->get_records('course_completions');
        $this->assertEquals(12, count($records));
        foreach ($records as $record) {
            // Removed 3-2, 3-3, 3-4, 6-2, 6-6. Did NOT remove 6-3!
            if ($record->userid == $users[3]->id && $record->course == $courses[6]->id ||
                $record->userid == $users[4]->id && $record->course == $courses[2]->id ||
                $record->userid == $users[4]->id && $record->course == $courses[3]->id ||
                $record->userid == $users[4]->id && $record->course == $courses[4]->id ||
                $record->userid == $users[5]->id && $record->course == $courses[2]->id ||
                $record->userid == $users[5]->id && $record->course == $courses[3]->id ||
                $record->userid == $users[5]->id && $record->course == $courses[5]->id ||
                $record->userid == $users[5]->id && $record->course == $courses[6]->id ||
                $record->userid == $users[5]->id && $record->course == $courses[8]->id ||
                $record->userid == $users[5]->id && $record->course == $courses[9]->id ||
                $record->userid == $users[6]->id && $record->course == $courses[3]->id ||
                $record->userid == $users[7]->id && $record->course == $courses[6]->id) {
                $this->assertEquals(COMPLETION_STATUS_COMPLETE, $record->status);
            } else {
                $this->assertTrue(false);
            }
        }
    }

    /**
     * Test that reset_certifcomponent_completions is resetting the prog_completion and course records.
     */
    public function test_reset_certifcomponent_completions() {
        global $DB;

        $generator = $this->getDataGenerator();
        $program_generator = \totara_program\testing\generator::instance();

        // Set up some users, courses and certifications.
        $users = [];
        $courses = [];
        $certifications = [];
        for ($i = 1; $i <= 10; $i++) {
            $user = $generator->create_user();
            $users[$i] = $user;
            $course = $generator->create_course();
            $courses[$i] = $course;
            $certification = $program_generator->create_certification(array('cert_windowperiod' => '6 month'));
            $certifications[$i] = $certification;
        }

        // Set up some courses in the certifications.
        $program_generator->legacy_add_courseset_program($certifications[4]->id, [$courses[2]->id, $courses[3]->id], CERTIFPATH_CERT);
        $program_generator->legacy_add_courseset_program($certifications[4]->id, [$courses[2]->id, $courses[3]->id], CERTIFPATH_RECERT);
        $program_generator->legacy_add_courseset_program($certifications[5]->id, [$courses[6]->id], CERTIFPATH_CERT);
        $program_generator->legacy_add_courseset_program($certifications[5]->id, [$courses[7]->id], CERTIFPATH_RECERT);
        $program_generator->legacy_add_courseset_program($certifications[9]->id, [$courses[4]->id], CERTIFPATH_CERT);
        $program_generator->legacy_add_courseset_program($certifications[9]->id, [$courses[8]->id], CERTIFPATH_RECERT);

        // Assign some users to some certs.
        $program_generator->assign_to_program($certifications[4]->id, ASSIGNTYPE_INDIVIDUAL, $users[3]->id, null, true); // User 3 to cert 4.
        $program_generator->assign_to_program($certifications[5]->id, ASSIGNTYPE_INDIVIDUAL, $users[3]->id, null, true); // User 3 to cert 5.
        $program_generator->assign_to_program($certifications[4]->id, ASSIGNTYPE_INDIVIDUAL, $users[4]->id, null, true); // User 4 to cert 4.
        $program_generator->assign_to_program($certifications[5]->id, ASSIGNTYPE_INDIVIDUAL, $users[4]->id, null, true); // User 4 to cert 5.
        $program_generator->assign_to_program($certifications[9]->id, ASSIGNTYPE_INDIVIDUAL, $users[6]->id, null, true); // User 6 to cert 9.

        // Check that program completion records have been set up.
        $records = $DB->get_records('prog_completion', array('coursesetid' => 0));
        $this->assertEquals(5, count($records));
        foreach ($records as $record) {
            if ($record->userid == $users[3]->id && $record->programid == $certifications[4]->id ||
                $record->userid == $users[3]->id && $record->programid == $certifications[5]->id ||
                $record->userid == $users[4]->id && $record->programid == $certifications[4]->id ||
                $record->userid == $users[4]->id && $record->programid == $certifications[5]->id ||
                $record->userid == $users[6]->id && $record->programid == $certifications[9]->id) {
                $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $record->status);
                $this->assertEquals(0, $record->timecompleted);
            } else {
                $this->assertTrue(false);
            }
        }
        $this->assertEquals(10, $DB->count_records('prog_completion')); // Course set completion records have been set up.

        // Complete the certifications.
        $now = time();
        $completion = new completion_completion(['userid' => $users[3]->id, 'course' => $courses[2]->id]);
        $completion->mark_complete($now);
        $completion = new completion_completion(['userid' => $users[3]->id, 'course' => $courses[3]->id]);
        $completion->mark_complete($now);
        $completion = new completion_completion(['userid' => $users[3]->id, 'course' => $courses[5]->id]); // Not part of any cert.
        $completion->mark_complete($now);
        $completion = new completion_completion(['userid' => $users[3]->id, 'course' => $courses[6]->id]);
        $completion->mark_complete($now);
        $completion = new completion_completion(['userid' => $users[4]->id, 'course' => $courses[2]->id]);
        $completion->mark_complete($now);
        $completion = new completion_completion(['userid' => $users[4]->id, 'course' => $courses[3]->id]);
        $completion->mark_complete($now);
        $completion = new completion_completion(['userid' => $users[4]->id, 'course' => $courses[6]->id]);
        $completion->mark_complete($now);
        $completion = new completion_completion(['userid' => $users[6]->id, 'course' => $courses[4]->id]);
        $completion->mark_complete($now);

        // Check that program completion records have been marked complete.
        $records = $DB->get_records('prog_completion', ['coursesetid' => 0]);
        $this->assertEquals(5, count($records));
        foreach ($records as $record) {
            if ($record->userid == $users[3]->id && $record->programid == $certifications[4]->id ||
                $record->userid == $users[3]->id && $record->programid == $certifications[5]->id ||
                $record->userid == $users[4]->id && $record->programid == $certifications[4]->id ||
                $record->userid == $users[4]->id && $record->programid == $certifications[5]->id ||
                $record->userid == $users[6]->id && $record->programid == $certifications[9]->id) {
                $this->assertEquals(STATUS_PROGRAM_COMPLETE, $record->status);
                $this->assertEquals($now, $record->timecompleted);
            } else {
                $this->assertTrue(false);
            }
        }
        $records = $DB->get_records_select('prog_completion', 'coursesetid <> 0');
        $this->assertEquals(5, count($records));
        foreach ($records as $record) {
            if ($record->userid == $users[3]->id && $record->programid == $certifications[4]->id ||
                $record->userid == $users[3]->id && $record->programid == $certifications[5]->id ||
                $record->userid == $users[4]->id && $record->programid == $certifications[4]->id ||
                $record->userid == $users[4]->id && $record->programid == $certifications[5]->id ||
                $record->userid == $users[6]->id && $record->programid == $certifications[9]->id) {
                $this->assertEquals(STATUS_COURSESET_COMPLETE, $record->status);
            } else {
                $this->assertTrue(false);
            }
        }
    }

    /**
     * Test that archive_courses_completion calls archive_course_completion and archive_course_activities for the
     * correct courses and user.
     *
     * Currently, this is only testing the course reset, not activity reset.
     */
    public function test_certif_archive_courses_completion() {
        global $DB;

        $coursedefaults = array(
            'enablecompletion' => COMPLETION_ENABLED,
            'completionstartonenrol' => 1,
            'completionprogressonview' => 1);

        // Set up some users and courses.
        $users = array();
        $courses = array();
        $courses_inprogress = array();
        for ($i = 1; $i <= 10; $i++) {
            $users[] = $this->getDataGenerator()->create_user();
            $courses[] = $this->getDataGenerator()->create_course();
            $courses_inprogress[] = $this->getDataGenerator()->create_course($coursedefaults);
        }

        // Mark all users complete in all courses.
        $now = time();
        foreach ($users as $user) {
            foreach ($courses as $course) {
                $completion = new completion_completion(array('userid' => $user->id, 'course' => $course->id));
                $completion->mark_complete($now);
            }

            foreach ($courses_inprogress as $pcourse) {
                $completion = new completion_completion(array('userid' => $user->id, 'course' => $pcourse->id));
                $completion->mark_inprogress($now);
            }
        }

        // Check that all users are marked complete/in-progress in all courses, and no history exists.
        $records_complete = $DB->get_records('course_completions', array('status' => COMPLETION_STATUS_COMPLETE));
        $this->assertEquals(100, count($records_complete));
        foreach ($records_complete as $record) {
            $this->assertEquals($now, $record->timecompleted);
        }

        $records_inprogress = $DB->get_records('course_completions', array('status' => COMPLETION_STATUS_INPROGRESS));
        $this->assertEquals(100, count($records_inprogress));
        foreach ($records_inprogress as $record) {
            $this->assertEquals($now, $record->timestarted);
        }

        $this->assertEquals(0, $DB->count_records('course_completion_history'));

        // Reset some courses for a user.
        $testuser = $users[3];
        $testcourseids = array($courses[4]->id, $courses[5]->id, $courses[6]->id);
        $testcourseids_inprogress = array($courses_inprogress[2]->id, $courses_inprogress[7]->id);
        certif_archive_courses_completion(array_merge($testcourseids, $testcourseids_inprogress), $testuser->id, $now);

        // Check that all users are marked complete/in-progress in all courses, except for the test user and courses.
        $records = $DB->get_records('course_completions');
        $this->assertEquals(195, count($records));
        foreach ($records as $record) {
            if ($record->userid == $testuser->id
                && (in_array($record->course, $testcourseids) || in_array($record->course, $testcourseids_inprogress))) {
                $this->assertTrue(false);
            } else {
                $this->assertContainsEquals($now, array($record->timecompleted, $record->timestarted));
            }
        }

        // Only 3 completed test courses should be added to the history.
        $records = $DB->get_records('course_completion_history');
        $this->assertEquals(3, count($records));
        foreach ($records as $record) {
            if ($record->userid == $testuser->id && (in_array($record->courseid, $testcourseids))) {
                $this->assertEquals($now, $record->timecompleted);
            } else {
                $this->assertTrue(false);
            }
        }
    }

    public function test_certification_event_handler_course_inprogress() {
        global $DB;

        $generator = $this->getDataGenerator();
        $program_generator = \totara_program\testing\generator::instance();

        // Set up some stuff.
        $user1 = $generator->create_user();
        $user2 = $generator->create_user();

        // Set default settings for courses.
        $coursedefaults = [
            'enablecompletion' => COMPLETION_ENABLED,
            'completionstartonenrol' => 1,
            'completionprogressonview' => 1];

        // Create some courses.
        $course1 = $generator->create_course($coursedefaults, ['createsections' => true]);
        $course2 = $generator->create_course($coursedefaults, ['createsections' => true]);
        $course3 = $generator->create_course($coursedefaults, ['createsections' => true]);
        $course4 = $generator->create_course($coursedefaults, ['createsections' => true]);
        $course5 = $generator->create_course($coursedefaults, ['createsections' => true]);

        $cert1 = $program_generator->create_certification();
        $cert2 = $program_generator->create_certification();

        $program_generator->add_courses_and_courseset_to_program($cert1, [[$course1], [$course2, $course3]], CERTIFPATH_STD);
        $program_generator->add_courses_and_courseset_to_program($cert1, [[$course1], [$course4]], CERTIFPATH_RECERT);
        $program_generator->add_courses_and_courseset_to_program($cert2, [[$course1]], CERTIFPATH_STD);
        $program_generator->add_courses_and_courseset_to_program($cert2, [[$course1]], CERTIFPATH_RECERT);

        $program_generator->assign_to_program($cert1->id, ASSIGNTYPE_INDIVIDUAL, $user1->id, null, true);
        $program_generator->assign_to_program($cert2->id, ASSIGNTYPE_INDIVIDUAL, $user1->id, null, true);

        $logcount = $DB->count_records('prog_completion_log');

        // If the course belongs to no certs then no problem.
        $context = context_course::instance($course5->id);
        $data = array(
            'relateduserid' => $user1->id,
            'objectid' => $course5->id,
            'context' => $context,
        );
        \core\event\course_in_progress::create($data)->trigger();
        $this->assertEquals($logcount, $DB->count_records('prog_completion_log'));

        // If the course belongs to a certification but the user is not enrolled in the certification then no problem.
        $context = context_course::instance($course1->id);
        $data = array(
            'relateduserid' => $user2->id,
            'objectid' => $course1->id,
            'context' => $context,
        );
        \core\event\course_in_progress::create($data)->trigger();
        $this->assertEquals($logcount, $DB->count_records('prog_completion_log'));

        // If the course is in two certs that the user is in then they are both marked in progress.
        // Note that we don't need to check that certif_set_in_progress is working correctly, just that is being called.
        list($oldcert1completion, $oldprog1completion) = certif_load_completion($cert1->id, $user1->id);
        list($oldcert2completion, $oldprog2completion) = certif_load_completion($cert2->id, $user1->id);
        $this->assertEquals(CERTIFSTATUS_ASSIGNED, $oldcert1completion->status);
        $this->assertEquals(CERTIFSTATUS_ASSIGNED, $oldcert2completion->status);
        $context = context_course::instance($course1->id);
        $data = [
            'relateduserid' => $user1->id,
            'objectid' => $course1->id,
            'context' => $context,
        ];
        \core\event\course_in_progress::create($data)->trigger();
        list($newcert1completion, $newprog1completion) = certif_load_completion($cert1->id, $user1->id);
        list($newcert2completion, $newprog2completion) = certif_load_completion($cert2->id, $user1->id);
        $this->assertEquals(CERTIFSTATUS_INPROGRESS, $newcert1completion->status);
        $this->assertEquals(CERTIFSTATUS_INPROGRESS, $newcert2completion->status);
        $this->assertEquals($logcount + 2, $DB->count_records('prog_completion_log'));
    }

    public function test_certification_fix_missing_certif_completions() {
        global $DB;

        $generator = $this->getDataGenerator();
        $program_generator = \totara_program\testing\generator::instance();
        $completion_generator = \core_completion\testing\generator::instance();

        // Set up some stuff.
        $user1 = $generator->create_user();
        $user2 = $generator->create_user();

        // Set default settings for courses.
        $coursedefaults = array(
            'enablecompletion' => COMPLETION_ENABLED,
            'completionstartonenrol' => 1,
            'completionprogressonview' => 1);

        // Create some courses.
        $course1 = $generator->create_course($coursedefaults, array('createsections' => true));

        $cert1 = $program_generator->create_certification();
        $cert2 = $program_generator->create_certification();
        $prog = $program_generator->create_program();

        $program_generator->add_courses_and_courseset_to_program($cert2, array(array($course1)), CERTIFPATH_STD);
        $program_generator->add_courses_and_courseset_to_program($cert2, array(array($course1)), CERTIFPATH_RECERT);

        $program_generator->assign_to_program($cert2->id, ASSIGNTYPE_INDIVIDUAL, $user1->id, null, true);
        $program_generator->assign_to_program($cert1->id, ASSIGNTYPE_INDIVIDUAL, $user2->id, null, true);
        $program_generator->assign_to_program($prog->id, ASSIGNTYPE_INDIVIDUAL, $user1->id, null, true);

        // Prog_completion exists, user is not assigned, certif_completion missing - don't do anything.
        $data = new stdClass();
        $data->programid = $cert1->id;
        $data->userid = $user1->id;
        $data->coursesetid = 0;
        $data->status = STATUS_PROGRAM_COMPLETE;
        $data->timestarted = 987;
        $data->timedue = 876;
        $data->timecompleted = 765;
        $DB->insert_record('prog_completion', $data);

        $this->assertNotEmpty($DB->get_records('prog_completion', array('programid' => $cert1->id, 'userid' => $user1->id, 'coursesetid' => 0)));
        $this->assertEmpty($DB->get_records('prog_user_assignment', array('programid' => $cert1->id, 'userid' => $user1->id)));
        $this->assertEmpty($DB->get_records('certif_completion', array('certifid' => $cert1->certifid, 'userid' => $user1->id)));
        $beforecertcompletioncount = $DB->count_records('certif_completion');
        $fixed = certification_fix_missing_certif_completions();
        $this->assertEquals(0, $fixed);
        $this->assertNotEmpty($DB->get_records('prog_completion', array('programid' => $cert1->id, 'userid' => $user1->id, 'coursesetid' => 0)));
        $this->assertEmpty($DB->get_records('prog_user_assignment', array('programid' => $cert1->id, 'userid' => $user1->id)));
        $this->assertEmpty($DB->get_records('certif_completion', array('certifid' => $cert1->certifid, 'userid' => $user1->id)));
        $this->assertEquals($beforecertcompletioncount, $DB->count_records('certif_completion')); // No records were created for other users.

        // Prog_completion exists, prog_user_assignment exists, certif_completion is missing - do it.
        $data = new stdClass();
        $data->programid = $cert1->id;
        $data->userid = $user1->id;
        $data->assignmentid = 0;
        $data->timeassigned = time();
        $data->exceptionstatus = 0;
        $DB->insert_record('prog_user_assignment', $data);

        $this->assertNotEmpty($DB->get_records('prog_completion', array('programid' => $cert1->id, 'userid' => $user1->id, 'coursesetid' => 0)));
        $this->assertNotEmpty($DB->get_records('prog_user_assignment', array('programid' => $cert1->id, 'userid' => $user1->id)));
        $this->assertEmpty($DB->get_records('certif_completion', array('certifid' => $cert1->certifid, 'userid' => $user1->id)));
        $beforecertcompletioncount = $DB->count_records('certif_completion');
        $fixed = certification_fix_missing_certif_completions();
        $this->assertEquals(1, $fixed);
        $this->assertNotEmpty($DB->get_records('prog_completion', array('programid' => $cert1->id, 'userid' => $user1->id, 'coursesetid' => 0)));
        $this->assertNotEmpty($DB->get_records('prog_user_assignment', array('programid' => $cert1->id, 'userid' => $user1->id)));
        $this->assertNotEmpty($DB->get_records('certif_completion', array('certifid' => $cert1->certifid, 'userid' => $user1->id)));
        $this->assertEquals($beforecertcompletioncount + 1, $DB->count_records('certif_completion')); // No records were created for other users.

        $lastlog = $DB->get_records('prog_completion_log', array(), 'id DESC', '*', 0, 2);
        $lastlog = reset($lastlog);
        $this->assertEquals($cert1->id, $lastlog->programid);
        $this->assertEquals($user1->id, $lastlog->userid);
        $this->assertStringStartsWith('Created certif_completion record for existing prog_completion', $lastlog->description);

        // Make sure that the record created is in the assigned state. Helps prove that prog_update_completion is being
        // run, when the status is calculated as complete in a later step.
        list($certcompletion, $progcompletion) = certif_load_completion($cert1->id, $user1->id);
        $this->assertEquals(CERTIFSTATUS_ASSIGNED, $certcompletion->status);

        // Prog_completion exists, user is assigned, certif_completion exists - don't do anything.
        $this->assertNotEmpty($DB->get_records('prog_completion', array('programid' => $cert1->id, 'userid' => $user1->id, 'coursesetid' => 0)));
        $this->assertNotEmpty($DB->get_records('prog_user_assignment', array('programid' => $cert1->id, 'userid' => $user1->id)));
        $this->assertNotEmpty($DB->get_records('certif_completion', array('certifid' => $cert1->certifid, 'userid' => $user1->id)));
        $beforecertcompletioncount = $DB->count_records('certif_completion');
        $fixed = certification_fix_missing_certif_completions();
        $this->assertEquals(0, $fixed);
        $this->assertNotEmpty($DB->get_records('prog_completion', array('programid' => $cert1->id, 'userid' => $user1->id, 'coursesetid' => 0)));
        $this->assertNotEmpty($DB->get_records('prog_user_assignment', array('programid' => $cert1->id, 'userid' => $user1->id)));
        $this->assertNotEmpty($DB->get_records('certif_completion', array('certifid' => $cert1->certifid, 'userid' => $user1->id)));
        $this->assertEquals($beforecertcompletioncount, $DB->count_records('certif_completion')); // No records were created for other users.

        // Prog_completion exists, prog_user_assignment exists, certif_completion is missing, but this is a program - do nothing.
        $this->assertNotEmpty($DB->get_records('prog_completion', array('programid' => $prog->id, 'userid' => $user1->id, 'coursesetid' => 0)));
        $this->assertNotEmpty($DB->get_records('prog_user_assignment', array('programid' => $prog->id, 'userid' => $user1->id)));
        $this->assertEmpty($DB->get_records('certif_completion', array('certifid' => $prog->certifid, 'userid' => $user1->id)));
        $beforecertcompletioncount = $DB->count_records('certif_completion');
        $fixed = certification_fix_missing_certif_completions();
        $this->assertEquals(0, $fixed);
        $this->assertNotEmpty($DB->get_records('prog_completion', array('programid' => $prog->id, 'userid' => $user1->id, 'coursesetid' => 0)));
        $this->assertNotEmpty($DB->get_records('prog_user_assignment', array('programid' => $prog->id, 'userid' => $user1->id)));
        $this->assertEmpty($DB->get_records('certif_completion', array('certifid' => $prog->certifid, 'userid' => $user1->id)));
        $this->assertEquals($beforecertcompletioncount, $DB->count_records('certif_completion')); // No records were created for other users.

        // Check that prog_update_completion is being run after the missing record is created.
        $completion_generator->complete_course($course1, $user1);
        list($certcompletion, $progcompletion) = certif_load_completion($cert2->id, $user1->id);
        $this->assertEquals(CERTIFSTATUS_COMPLETED, $certcompletion->status);
        $DB->delete_records('certif_completion', array('id' => $certcompletion->id));

        $this->assertNotEmpty($DB->get_records('prog_completion', array('programid' => $cert2->id, 'userid' => $user1->id, 'coursesetid' => 0)));
        $this->assertNotEmpty($DB->get_records('prog_user_assignment', array('programid' => $cert2->id, 'userid' => $user1->id)));
        $this->assertEmpty($DB->get_records('certif_completion', array('certifid' => $cert2->certifid, 'userid' => $user1->id)));
        $beforecertcompletioncount = $DB->count_records('certif_completion');
        $fixed = certification_fix_missing_certif_completions();
        $this->assertEquals(1, $fixed);
        $this->assertNotEmpty($DB->get_records('prog_completion', array('programid' => $cert2->id, 'userid' => $user1->id, 'coursesetid' => 0)));
        $this->assertNotEmpty($DB->get_records('prog_user_assignment', array('programid' => $cert2->id, 'userid' => $user1->id)));
        $this->assertNotEmpty($DB->get_records('certif_completion', array('certifid' => $cert2->certifid, 'userid' => $user1->id)));
        $this->assertEquals($beforecertcompletioncount +1, $DB->count_records('certif_completion')); // No records were created for other users.

        list($certcompletion, $progcompletion) = certif_load_completion($cert2->id, $user1->id);
        $this->assertEquals(CERTIFSTATUS_COMPLETED, $certcompletion->status); // Previous test had assigned when user was not complete.
    }

    /**
     * Test certif_create_completion. This test doesn't test the reassignment code within certif_create_completion
     * because that is already heavily tested in reassignment_test.php.
     */
    public function test_certif_create_completion() {
        global $DB;

        $generator = $this->getDataGenerator();
        $program_generator = \totara_program\testing\generator::instance();

        // Set up some stuff.
        $user = $generator->create_user();

        $prog = $program_generator->create_program();
        $cert = $program_generator->create_certification();

        // Check that we get an exception if we try to do it with a program.
        try {
            certif_create_completion($prog->id, $user->id);
            $this->fail('Expected exception!');
        } catch (moodle_exception $e) {
            $this->assertEquals('Attempting to create certification completion record for non-certification program.',
                $e->getMessage());
        }

        // Create a non-zero course set group completion record to make sure that it doesn't interfere with the later steps.
        $data = new stdClass();
        $data->programid = $cert->id;
        $data->userid = $user->id;
        $data->coursesetid = 1;
        $data->status = STATUS_PROGRAM_COMPLETE;
        $data->timestarted = 987;
        $data->timedue = 876;
        $data->timecompleted = 765;
        $DB->insert_record('prog_completion', $data);

        // Check that two records created successfully if none already exist.
        $timebefore = time();
        certif_create_completion($cert->id, $user->id);
        $timeafter = time();

        list($certcompletion, $progcompletion) = certif_load_completion($cert->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status);
        $this->assertEquals(0, $progcompletion->timecompleted);
        $this->assertEquals(COMPLETION_TIME_NOT_SET, $progcompletion->timedue);
        $this->assertEquals(0, $progcompletion->timestarted);
        $this->assertLessThanOrEqual($timeafter, $progcompletion->timestarted);
        $this->assertEquals(CERTIFPATH_CERT, $certcompletion->certifpath);
        $this->assertEquals(CERTIFSTATUS_ASSIGNED, $certcompletion->status);
        $this->assertEquals(CERTIFRENEWALSTATUS_NOTDUE, $certcompletion->renewalstatus);
        $this->assertEquals(0, $certcompletion->timecompleted);
        $this->assertEquals(0, $certcompletion->timewindowopens);
        $this->assertEquals(0, $certcompletion->timeexpires);
        $this->assertEquals(0, $certcompletion->baselinetimeexpires);
        $this->assertGreaterThanOrEqual($timebefore, $certcompletion->timemodified);
        $this->assertLessThanOrEqual($timeafter, $certcompletion->timemodified);

        // Check that the log was created.
        $lastlog = $DB->get_records('prog_completion_log', array(), 'id DESC', '*', 0, 2);
        $lastlog = reset($lastlog);
        $this->assertEquals($cert->id, $lastlog->programid);
        $this->assertEquals($user->id, $lastlog->userid);
        $this->assertStringStartsWith('Created certif_completion and prog_completion records', $lastlog->description);

        // Check that nothing happens if the records already exist.
        $progcompletion->status = 123;
        $progcompletion->timestarted = 234;
        $progcompletion->timedue = 345;
        $progcompletion->timecompleted = 456;
        $DB->update_record('prog_completion', $progcompletion); // Make the existing records unique so we will know it is unchanged.
        $certcompletion->certifpath = 5;
        $certcompletion->status = 9;
        $certcompletion->renewalstatus = 8;
        $certcompletion->timecompleted = 567;
        $certcompletion->timewindowopens = 678;
        $certcompletion->timemodified = 789;
        $DB->update_record('certif_completion', $certcompletion);
        certif_create_completion($cert->id, $user->id);
        list($newcertcompletion, $newprogcompletion) = certif_load_completion($cert->id, $user->id);
        $this->assertEquals($certcompletion, $newcertcompletion);
        $this->assertEquals($progcompletion, $newprogcompletion);

        // Check that no new log has been created.
        $newlatestlog = $DB->get_records('prog_completion_log', array(), 'id DESC', '*', 0, 1);
        $newlatestlog = reset($newlatestlog);
        $this->assertEquals($lastlog, $newlatestlog);

        // Check that certif_completion is created if only prog_completion exists, and set it to incomplete if it isn't
        // already. But timestarted and timedue should be unaltered.
        $DB->delete_records('certif_completion', array('id' => $certcompletion->id));

        $this->waitForSecond();
        $timebefore = time();
        certif_create_completion($cert->id, $user->id);
        $timeafter = time();

        list($newcertcompletion, $newprogcompletion) = certif_load_completion($cert->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $newprogcompletion->status);
        $this->assertEquals($progcompletion->timecreated, $newprogcompletion->timecreated);
        $this->assertEquals($progcompletion->timestarted, $newprogcompletion->timestarted);
        $this->assertEquals($progcompletion->timedue, $newprogcompletion->timedue);
        $this->assertEquals(0, $newprogcompletion->timecompleted);
        $this->assertEquals(CERTIFPATH_CERT, $newcertcompletion->certifpath);
        $this->assertEquals(CERTIFSTATUS_ASSIGNED, $newcertcompletion->status);
        $this->assertEquals(CERTIFRENEWALSTATUS_NOTDUE, $newcertcompletion->renewalstatus);
        $this->assertEquals(0, $newcertcompletion->timecompleted);
        $this->assertEquals(0, $newcertcompletion->timewindowopens);
        $this->assertEquals(0, $newcertcompletion->timeexpires);
        $this->assertEquals(0, $newcertcompletion->baselinetimeexpires);
        $this->assertGreaterThanOrEqual($timebefore, $newcertcompletion->timemodified);
        $this->assertLessThanOrEqual($timeafter, $newcertcompletion->timemodified);

        // Check that the log was created.
        $lastlog = $DB->get_records('prog_completion_log', array(), 'id DESC', '*', 0, 1);
        $lastlog = reset($lastlog);
        $this->assertEquals($cert->id, $lastlog->programid);
        $this->assertEquals($user->id, $lastlog->userid);
        $this->assertStringStartsWith('Created certif_completion record for existing prog_completion', $lastlog->description);

        // Check that prog_completion is created if only certif_completion exists.
        $progcompletion = $newprogcompletion;
        $certcompletion = $newcertcompletion;
        $DB->delete_records('prog_completion', array('id' => $progcompletion->id));

        $this->waitForSecond();
        $timebefore = time();
        certif_create_completion($cert->id, $user->id);
        $timeafter = time();

        list($newcertcompletion, $newprogcompletion) = certif_load_completion($cert->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $newprogcompletion->status);
        $this->assertGreaterThanOrEqual($timebefore, $newprogcompletion->timecreated);
        $this->assertLessThanOrEqual($timeafter, $newprogcompletion->timecreated);
        $this->assertEquals(0, $newprogcompletion->timestarted);
        $this->assertEquals(COMPLETION_TIME_NOT_SET, $newprogcompletion->timedue);
        $this->assertEquals(0, $newprogcompletion->timecompleted);
        $this->assertEquals($certcompletion->certifpath, $newcertcompletion->certifpath);
        $this->assertEquals($certcompletion->status, $newcertcompletion->status);
        $this->assertEquals($certcompletion->renewalstatus, $newcertcompletion->renewalstatus);
        $this->assertEquals($certcompletion->timecompleted, $newcertcompletion->timecompleted);
        $this->assertEquals($certcompletion->timewindowopens, $newcertcompletion->timewindowopens);
        $this->assertEquals($certcompletion->timeexpires, $newcertcompletion->timeexpires);
        $this->assertEquals($certcompletion->baselinetimeexpires, $newcertcompletion->baselinetimeexpires);
        $this->assertEquals($certcompletion->timemodified, $newcertcompletion->timemodified);

        // Check that the log was created.
        $lastlog = $DB->get_records('prog_completion_log', array(), 'id DESC', '*', 0, 1);
        $lastlog = reset($lastlog);
        $this->assertEquals($cert->id, $lastlog->programid);
        $this->assertEquals($user->id, $lastlog->userid);
        $this->assertStringStartsWith('Created missing prog_completion record for existing certif_completion', $lastlog->description);
    }

    /**
     * Tests that certif_create_completion works when the prog_completion doesn't exist, but history does.
     * In this case, the prog_completion will start out incomplete and will be updated to whatever it needs to be.
     */
    public function test_certif_create_completion_missing_prog_completion() {
        $generator = $this->getDataGenerator();
        $program_generator = \totara_program\testing\generator::instance();

        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $user3 = $generator->create_user();

        $cert1 = $program_generator->create_certification();
        $cert2 = $program_generator->create_certification();
        $cert3 = $program_generator->create_certification();

        //////////////////////////////
        // Assigned certif_completion.
        $certid = $cert1->certifid;
        $progid = $cert1->id;
        $userid = $user1->id;

        // Set up the history record.
        $certcompletionhistory = new stdClass();
        $certcompletionhistory->certifid = $certid;
        $certcompletionhistory->userid = $userid;
        $certcompletionhistory->certifpath = CERTIFPATH_CERT;
        $certcompletionhistory->status = CERTIFSTATUS_ASSIGNED;
        $certcompletionhistory->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $certcompletionhistory->timewindowopens = 0;
        $certcompletionhistory->timeexpires = 0;
        $certcompletionhistory->baselinetimeexpires = 0;
        $certcompletionhistory->timecompleted = 0;
        $certcompletionhistory->timemodified = 123;
        $certcompletionhistory->unassigned = 1;
        certif_write_completion_history($certcompletionhistory);

        // Run the function.
        certif_create_completion($progid, $userid);

        // Load the data.
        list($newcertcompletion, $newprogcompletion) = certif_load_completion($progid, $userid);
        $this->assertEmpty(certif_get_completion_errors($newcertcompletion, $newprogcompletion));

        // Set up the expected certif_completion.
        $expectedcertcompletion = $certcompletionhistory;
        unset($expectedcertcompletion->timemodified);
        unset($expectedcertcompletion->unassigned);
        unset($newcertcompletion->id);
        unset($newcertcompletion->timemodified);

        // Set up the expected prog_completion.
        $expectedprogcompletion = new stdClass();
        $expectedprogcompletion->programid = $progid;
        $expectedprogcompletion->userid = $userid;
        $expectedprogcompletion->coursesetid = 0;
        $expectedprogcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $expectedprogcompletion->timestarted = 0;
        $expectedprogcompletion->timedue = COMPLETION_TIME_NOT_SET;
        $expectedprogcompletion->timecompleted = 0;
        $expectedprogcompletion->organisationid = null;
        $expectedprogcompletion->positionid = null;
        unset($newprogcompletion->id);
        unset($newprogcompletion->timecreated);

        // Check that they match.
        $this->assertEquals($expectedcertcompletion, $newcertcompletion);
        $this->assertEquals($expectedprogcompletion, $newprogcompletion);

        ///////////////////////////////
        // Certified certif_completion.
        $certid = $cert2->certifid;
        $progid = $cert2->id;
        $userid = $user2->id;

        // Set up the history record.
        $certcompletionhistory = new stdClass();
        $certcompletionhistory->certifid = $certid;
        $certcompletionhistory->userid = $userid;
        $certcompletionhistory->certifpath = CERTIFPATH_RECERT;
        $certcompletionhistory->status = CERTIFSTATUS_COMPLETED;
        $certcompletionhistory->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $certcompletionhistory->timewindowopens = 345;
        $certcompletionhistory->timeexpires = 456;
        $certcompletionhistory->baselinetimeexpires = 454;
        $certcompletionhistory->timecompleted = 234;
        $certcompletionhistory->timemodified = 123;
        $certcompletionhistory->unassigned = 1;
        certif_write_completion_history($certcompletionhistory);

        // Run the function.
        certif_create_completion($progid, $userid);

        // Load the data.
        list($newcertcompletion, $newprogcompletion) = certif_load_completion($progid, $userid);
        $this->assertEmpty(certif_get_completion_errors($newcertcompletion, $newprogcompletion));

        // Set up the expected certif_completion.
        $expectedcertcompletion = $certcompletionhistory;
        unset($expectedcertcompletion->timemodified);
        unset($expectedcertcompletion->unassigned);
        unset($newcertcompletion->id);
        unset($newcertcompletion->timemodified);

        // Set up the expected prog_completion.
        $expectedprogcompletion = new stdClass();
        $expectedprogcompletion->programid = $progid;
        $expectedprogcompletion->userid = $userid;
        $expectedprogcompletion->coursesetid = 0;
        $expectedprogcompletion->status = STATUS_PROGRAM_COMPLETE;
        $expectedprogcompletion->timestarted = 0;
        $expectedprogcompletion->timedue = 456;
        $expectedprogcompletion->timecompleted = 234;
        $expectedprogcompletion->organisationid = null;
        $expectedprogcompletion->positionid = null;
        unset($newprogcompletion->id);
        unset($newprogcompletion->timecreated);

        // Check that they match.
        $this->assertEquals($expectedcertcompletion, $newcertcompletion);
        $this->assertEquals($expectedprogcompletion, $newprogcompletion);

        /////////////////////////////////
        // Window open certif_completion.
        $certid = $cert3->certifid;
        $progid = $cert3->id;
        $userid = $user3->id;

        // Set up the history record.
        $certcompletionhistory = new stdClass();
        $certcompletionhistory->certifid = $certid;
        $certcompletionhistory->userid = $userid;
        $certcompletionhistory->certifpath = CERTIFPATH_RECERT;
        $certcompletionhistory->status = CERTIFSTATUS_INPROGRESS; // Just for fun.
        $certcompletionhistory->renewalstatus = CERTIFRENEWALSTATUS_DUE;
        $certcompletionhistory->timewindowopens = 345;
        $certcompletionhistory->timeexpires = 456;
        $certcompletionhistory->baselinetimeexpires = 454;
        $certcompletionhistory->timecompleted = 234;
        $certcompletionhistory->timemodified = 123;
        $certcompletionhistory->unassigned = 1;
        certif_write_completion_history($certcompletionhistory);

        // Run the function.
        certif_create_completion($progid, $userid);

        // Load the data.
        list($newcertcompletion, $newprogcompletion) = certif_load_completion($progid, $userid);
        $this->assertEmpty(certif_get_completion_errors($newcertcompletion, $newprogcompletion));

        // Set up the expected certif_completion.
        $expectedcertcompletion = $certcompletionhistory;
        unset($expectedcertcompletion->timemodified);
        unset($expectedcertcompletion->unassigned);
        unset($newcertcompletion->id);
        unset($newcertcompletion->timemodified);

        // Set up the expected prog_completion.
        $expectedprogcompletion = new stdClass();
        $expectedprogcompletion->programid = $progid;
        $expectedprogcompletion->userid = $userid;
        $expectedprogcompletion->coursesetid = 0;
        $expectedprogcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $expectedprogcompletion->timestarted = 0;
        $expectedprogcompletion->timedue = 456;
        $expectedprogcompletion->timecompleted = 0;
        $expectedprogcompletion->organisationid = null;
        $expectedprogcompletion->positionid = null;
        unset($newprogcompletion->id);
        unset($newprogcompletion->timecreated);

        // Check that they match.
        $this->assertEquals($expectedcertcompletion, $newcertcompletion);
        $this->assertEquals($expectedprogcompletion, $newprogcompletion);

        /////////////////////////////
        // Expired certif_completion.
        $certid = $cert1->certifid;
        $progid = $cert1->id;
        $userid = $user2->id;

        // Set up the history record that will be used for program timedue, created when window opened.
        $certcompletionhistory = new stdClass();
        $certcompletionhistory->certifid = $certid;
        $certcompletionhistory->userid = $userid;
        $certcompletionhistory->certifpath = CERTIFPATH_RECERT;
        $certcompletionhistory->status = CERTIFSTATUS_COMPLETED;
        $certcompletionhistory->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $certcompletionhistory->timewindowopens = 345;
        $certcompletionhistory->timeexpires = 456;
        $certcompletionhistory->baselinetimeexpires = 456;
        $certcompletionhistory->timecompleted = 234;
        $certcompletionhistory->timemodified = 123;
        $certcompletionhistory->unassigned = 0;
        certif_write_completion_history($certcompletionhistory);

        // Set up the history record that will be restored.
        $certcompletionhistory = new stdClass();
        $certcompletionhistory->certifid = $certid;
        $certcompletionhistory->userid = $userid;
        $certcompletionhistory->certifpath = CERTIFPATH_CERT;
        $certcompletionhistory->status = CERTIFSTATUS_EXPIRED;
        $certcompletionhistory->renewalstatus = CERTIFRENEWALSTATUS_EXPIRED;
        $certcompletionhistory->timewindowopens = 0;
        $certcompletionhistory->timeexpires = 0;
        $certcompletionhistory->baselinetimeexpires = 0;
        $certcompletionhistory->timecompleted = 0;
        $certcompletionhistory->timemodified = 123;
        $certcompletionhistory->unassigned = 1;
        certif_write_completion_history($certcompletionhistory);

        // Run the function.
        certif_create_completion($progid, $userid);

        // Load the data.
        list($newcertcompletion, $newprogcompletion) = certif_load_completion($progid, $userid);
        $this->assertEmpty(certif_get_completion_errors($newcertcompletion, $newprogcompletion));

        // Set up the expected certif_completion.
        $expectedcertcompletion = $certcompletionhistory;
        unset($expectedcertcompletion->timemodified);
        unset($expectedcertcompletion->unassigned);
        unset($newcertcompletion->id);
        unset($newcertcompletion->timemodified);

        // Set up the expected prog_completion.
        $expectedprogcompletion = new stdClass();
        $expectedprogcompletion->programid = $progid;
        $expectedprogcompletion->userid = $userid;
        $expectedprogcompletion->coursesetid = 0;
        $expectedprogcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $expectedprogcompletion->timestarted = 0;
        $expectedprogcompletion->timedue = 456; // Restored from other history.
        $expectedprogcompletion->timecompleted = 0;
        $expectedprogcompletion->organisationid = null;
        $expectedprogcompletion->positionid = null;
        unset($newprogcompletion->id);
        unset($newprogcompletion->timecreated);

        // Check that they match.
        $this->assertEquals($expectedcertcompletion, $newcertcompletion);
        $this->assertEquals($expectedprogcompletion, $newprogcompletion);
    }

    /**
     * Tests that certif_create_completion works when the prog_completion exists, but doesn't match the history being restored.
     * In this case, we'll mostly use a prog_completion in a complete state, since the incomplete state is covered by the
     * previous test, but be sure to keep other details from the original prog_completion.
     */
    public function test_certif_create_completion_mismatched_prog_completion() {
        global $DB;

        $generator = $this->getDataGenerator();
        $program_generator = \totara_program\testing\generator::instance();

        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $user3 = $generator->create_user();

        $cert1 = $program_generator->create_certification();
        $cert2 = $program_generator->create_certification();
        $cert3 = $program_generator->create_certification();

        //////////////////////////////
        // Assigned certif_completion.
        $certid = $cert1->certifid;
        $progid = $cert1->id;
        $userid = $user1->id;

        // Set up the history record.
        $certcompletionhistory = new stdClass();
        $certcompletionhistory->certifid = $certid;
        $certcompletionhistory->userid = $userid;
        $certcompletionhistory->certifpath = CERTIFPATH_CERT;
        $certcompletionhistory->status = CERTIFSTATUS_ASSIGNED;
        $certcompletionhistory->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $certcompletionhistory->timewindowopens = 0;
        $certcompletionhistory->timeexpires = 0;
        $certcompletionhistory->baselinetimeexpires = 0;
        $certcompletionhistory->timecompleted = 0;
        $certcompletionhistory->timemodified = 123;
        $certcompletionhistory->unassigned = 1;
        $this->assertTrue(certif_write_completion_history($certcompletionhistory));

        // Set up the prog_completion record, setting fields to values that are inconsistent with the history.
        $progcompletion = new stdClass();
        $progcompletion->programid = $progid;
        $progcompletion->userid = $userid;
        $progcompletion->coursesetid = 0;
        $progcompletion->status = STATUS_PROGRAM_COMPLETE; // Inconsistent with history status.
        $progcompletion->timestarted = 567;
        $progcompletion->timedue = 678;
        $progcompletion->timecompleted = 789; // Inconsistent with history timecompleted.
        $progcompletion->organisationid = 890;
        $progcompletion->positionid = 901;
        $DB->insert_record('prog_completion', $progcompletion);

        // Run the function.
        certif_create_completion($progid, $userid);

        // Load the data.
        list($newcertcompletion, $newprogcompletion) = certif_load_completion($progid, $userid);
        $this->assertEmpty(certif_get_completion_errors($newcertcompletion, $newprogcompletion));

        // Set up the expected certif_completion.
        $expectedcertcompletion = $certcompletionhistory;
        unset($expectedcertcompletion->timemodified);
        unset($expectedcertcompletion->unassigned);
        unset($newcertcompletion->id);
        unset($newcertcompletion->timemodified);

        // Set up the expected prog_completion.
        $expectedprogcompletion = $progcompletion;
        $expectedprogcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $expectedprogcompletion->timecompleted = 0;
        unset($newprogcompletion->id);
        unset($newprogcompletion->timecreated);

        // Check that they match.
        $this->assertEquals($expectedcertcompletion, $newcertcompletion);
        $this->assertEquals($expectedprogcompletion, $newprogcompletion);

        ///////////////////////////////
        // Certified certif_completion.
        $certid = $cert2->certifid;
        $progid = $cert2->id;
        $userid = $user2->id;

        // Set up the history record.
        $certcompletionhistory = new stdClass();
        $certcompletionhistory->certifid = $certid;
        $certcompletionhistory->userid = $userid;
        $certcompletionhistory->certifpath = CERTIFPATH_RECERT;
        $certcompletionhistory->status = CERTIFSTATUS_COMPLETED;
        $certcompletionhistory->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $certcompletionhistory->timewindowopens = 345;
        $certcompletionhistory->timeexpires = 456;
        $certcompletionhistory->baselinetimeexpires = 456;
        $certcompletionhistory->timecompleted = 234;
        $certcompletionhistory->timemodified = 123;
        $certcompletionhistory->unassigned = 1;
        $this->assertTrue(certif_write_completion_history($certcompletionhistory));

        // Set up the prog_completion record, setting fields to values that are inconsistent with the history.
        $progcompletion = new stdClass();
        $progcompletion->programid = $progid;
        $progcompletion->userid = $userid;
        $progcompletion->coursesetid = 0;
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE; // Inconsistent with history status.
        $progcompletion->timestarted = 567;
        $progcompletion->timedue = 678; // Inconsistent with history timeexpires.
        $progcompletion->timecompleted = 789; // Inconsistent with history timecompleted.
        $progcompletion->organisationid = 890;
        $progcompletion->positionid = 901;
        $DB->insert_record('prog_completion', $progcompletion);

        // Run the function.
        certif_create_completion($progid, $userid);

        // Load the data.
        list($newcertcompletion, $newprogcompletion) = certif_load_completion($progid, $userid);
        $this->assertEmpty(certif_get_completion_errors($newcertcompletion, $newprogcompletion));

        // Set up the expected certif_completion.
        $expectedcertcompletion = $certcompletionhistory;
        unset($expectedcertcompletion->timemodified);
        unset($expectedcertcompletion->unassigned);
        unset($newcertcompletion->id);
        unset($newcertcompletion->timemodified);

        // Set up the expected prog_completion.
        $expectedprogcompletion = $progcompletion;
        $expectedprogcompletion->status = STATUS_PROGRAM_COMPLETE;
        $expectedprogcompletion->timedue = $expectedcertcompletion->timeexpires;
        $expectedprogcompletion->timecompleted = $expectedcertcompletion->timecompleted;
        unset($newprogcompletion->id);
        unset($newprogcompletion->timecreated);

        // Check that they match.
        $this->assertEquals($expectedcertcompletion, $newcertcompletion);
        $this->assertEquals($expectedprogcompletion, $newprogcompletion);

        /////////////////////////////////
        // Window open certif_completion.
        $certid = $cert3->certifid;
        $progid = $cert3->id;
        $userid = $user3->id;

        // Set up the history record.
        $certcompletionhistory = new stdClass();
        $certcompletionhistory->certifid = $certid;
        $certcompletionhistory->userid = $userid;
        $certcompletionhistory->certifpath = CERTIFPATH_RECERT;
        $certcompletionhistory->status = CERTIFSTATUS_INPROGRESS; // Just for fun.
        $certcompletionhistory->renewalstatus = CERTIFRENEWALSTATUS_DUE;
        $certcompletionhistory->timewindowopens = 345;
        $certcompletionhistory->timeexpires = 456;
        $certcompletionhistory->baselinetimeexpires = 456;
        $certcompletionhistory->timecompleted = 234;
        $certcompletionhistory->timemodified = 123;
        $certcompletionhistory->unassigned = 1;
        $this->assertTrue(certif_write_completion_history($certcompletionhistory));

        // Set up the prog_completion record, setting fields to values that are inconsistent with the history.
        $progcompletion = new stdClass();
        $progcompletion->programid = $progid;
        $progcompletion->userid = $userid;
        $progcompletion->coursesetid = 0;
        $progcompletion->status = STATUS_PROGRAM_COMPLETE; // Inconsistent with history status.
        $progcompletion->timestarted = 567;
        $progcompletion->timedue = 678; // Inconsistent with history timeexpires.
        $progcompletion->timecompleted = 789; // Inconsistent with history status.
        $progcompletion->organisationid = 890;
        $progcompletion->positionid = 901;
        $DB->insert_record('prog_completion', $progcompletion);

        // Run the function.
        certif_create_completion($progid, $userid);

        // Load the data.
        list($newcertcompletion, $newprogcompletion) = certif_load_completion($progid, $userid);
        $this->assertEmpty(certif_get_completion_errors($newcertcompletion, $newprogcompletion));

        // Set up the expected certif_completion.
        $expectedcertcompletion = $certcompletionhistory;
        unset($expectedcertcompletion->timemodified);
        unset($expectedcertcompletion->unassigned);
        unset($newcertcompletion->id);
        unset($newcertcompletion->timemodified);

        // Set up the expected prog_completion.
        $expectedprogcompletion = $progcompletion;
        $expectedprogcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $expectedprogcompletion->timedue = $expectedcertcompletion->timeexpires;
        $expectedprogcompletion->timecompleted = 0;
        unset($newprogcompletion->id);
        unset($newprogcompletion->timecreated);

        // Check that they match.
        $this->assertEquals($expectedcertcompletion, $newcertcompletion);
        $this->assertEquals($expectedprogcompletion, $newprogcompletion);

        ////////////////////////////////////////////////////////////////
        // Expired certif_completion and prog_completion has no timedue.
        $certid = $cert1->certifid;
        $progid = $cert1->id;
        $userid = $user2->id;

        // Set up the history record that will be used for program timedue, created when window opened.
        $certcompletionhistory = new stdClass();
        $certcompletionhistory->certifid = $certid;
        $certcompletionhistory->userid = $userid;
        $certcompletionhistory->certifpath = CERTIFPATH_RECERT;
        $certcompletionhistory->status = CERTIFSTATUS_COMPLETED;
        $certcompletionhistory->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $certcompletionhistory->timewindowopens = 345;
        $certcompletionhistory->timeexpires = 456;
        $certcompletionhistory->baselinetimeexpires = 456;
        $certcompletionhistory->timecompleted = 234;
        $certcompletionhistory->timemodified = 123;
        $certcompletionhistory->unassigned = 0;
        $this->assertTrue(certif_write_completion_history($certcompletionhistory));

        // Set up the history record that will be restored.
        $certcompletionhistory = new stdClass();
        $certcompletionhistory->certifid = $certid;
        $certcompletionhistory->userid = $userid;
        $certcompletionhistory->certifpath = CERTIFPATH_CERT;
        $certcompletionhistory->status = CERTIFSTATUS_EXPIRED;
        $certcompletionhistory->renewalstatus = CERTIFRENEWALSTATUS_EXPIRED;
        $certcompletionhistory->timewindowopens = 0;
        $certcompletionhistory->timeexpires = 0;
        $certcompletionhistory->baselinetimeexpires = 0;
        $certcompletionhistory->timecompleted = 0;
        $certcompletionhistory->timemodified = 123;
        $certcompletionhistory->unassigned = 1;
        certif_write_completion_history($certcompletionhistory);

        // Set up the prog_completion record, setting fields to values that are inconsistent with the history.
        $progcompletion = new stdClass();
        $progcompletion->programid = $progid;
        $progcompletion->userid = $userid;
        $progcompletion->coursesetid = 0;
        $progcompletion->status = STATUS_PROGRAM_COMPLETE; // Inconsistent with history status.
        $progcompletion->timestarted = 567;
        $progcompletion->timedue = 0; // Inconsistent with history status (must have value when expired).
        $progcompletion->timecompleted = 789; // Inconsistent with history status.
        $progcompletion->organisationid = 890;
        $progcompletion->positionid = 901;
        $DB->insert_record('prog_completion', $progcompletion);

        // Run the function.
        certif_create_completion($progid, $userid);

        // Load the data.
        list($newcertcompletion, $newprogcompletion) = certif_load_completion($progid, $userid);
        $this->assertEmpty(certif_get_completion_errors($newcertcompletion, $newprogcompletion));

        // Set up the expected certif_completion.
        $expectedcertcompletion = $certcompletionhistory;
        unset($expectedcertcompletion->timemodified);
        unset($expectedcertcompletion->unassigned);
        unset($newcertcompletion->id);
        unset($newcertcompletion->timemodified);

        // Set up the expected prog_completion.
        $expectedprogcompletion = $progcompletion;
        $expectedprogcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $expectedprogcompletion->timedue = 456; // Matches other (non-unassigned history) timeexpires.
        $expectedprogcompletion->timecompleted = 0;
        unset($newprogcompletion->id);
        unset($newprogcompletion->timecreated);

        // Check that they match.
        $this->assertEquals($expectedcertcompletion, $newcertcompletion);
        $this->assertEquals($expectedprogcompletion, $newprogcompletion);

        ///////////////////////////////////////////////////////////////
        // Expired certif_completion and prog_completion has a timedue.
        $certid = $cert2->certifid;
        $progid = $cert2->id;
        $userid = $user3->id;

        // Set up the history record that will be used for program timedue, created when window opened.
        $certcompletionhistory = new stdClass();
        $certcompletionhistory->certifid = $certid;
        $certcompletionhistory->userid = $userid;
        $certcompletionhistory->certifpath = CERTIFPATH_RECERT;
        $certcompletionhistory->status = CERTIFSTATUS_COMPLETED;
        $certcompletionhistory->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $certcompletionhistory->timewindowopens = 345;
        $certcompletionhistory->timeexpires = 456;
        $certcompletionhistory->baselinetimeexpires = 456;
        $certcompletionhistory->timecompleted = 234;
        $certcompletionhistory->timemodified = 123;
        $certcompletionhistory->unassigned = 0;
        $this->assertTrue(certif_write_completion_history($certcompletionhistory));

        // Set up the history record that will be restored.
        $certcompletionhistory = new stdClass();
        $certcompletionhistory->certifid = $certid;
        $certcompletionhistory->userid = $userid;
        $certcompletionhistory->certifpath = CERTIFPATH_CERT;
        $certcompletionhistory->status = CERTIFSTATUS_EXPIRED;
        $certcompletionhistory->renewalstatus = CERTIFRENEWALSTATUS_EXPIRED;
        $certcompletionhistory->timewindowopens = 0;
        $certcompletionhistory->timeexpires = 0;
        $certcompletionhistory->baselinetimeexpires = 0;
        $certcompletionhistory->timecompleted = 0;
        $certcompletionhistory->timemodified = 123;
        $certcompletionhistory->unassigned = 1;
        certif_write_completion_history($certcompletionhistory);

        // Set up the prog_completion record, setting fields to values that are inconsistent with the history.
        $progcompletion = new stdClass();
        $progcompletion->programid = $progid;
        $progcompletion->userid = $userid;
        $progcompletion->coursesetid = 0;
        $progcompletion->status = STATUS_PROGRAM_COMPLETE; // Inconsistent with history status.
        $progcompletion->timestarted = 567;
        $progcompletion->timedue = 678; // Should be used since history record has no timeexpires.
        $progcompletion->timecompleted = 789; // Inconsistent with history status.
        $progcompletion->organisationid = 890;
        $progcompletion->positionid = 901;
        $DB->insert_record('prog_completion', $progcompletion);

        // Run the function.
        certif_create_completion($progid, $userid);

        // Load the data.
        list($newcertcompletion, $newprogcompletion) = certif_load_completion($progid, $userid);
        $this->assertEmpty(certif_get_completion_errors($newcertcompletion, $newprogcompletion));

        // Set up the expected certif_completion.
        $expectedcertcompletion = $certcompletionhistory;
        unset($expectedcertcompletion->timemodified);
        unset($expectedcertcompletion->unassigned);
        unset($newcertcompletion->id);
        unset($newcertcompletion->timemodified);

        // Set up the expected prog_completion.
        $expectedprogcompletion = $progcompletion;
        $expectedprogcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $expectedprogcompletion->timecompleted = 0;
        unset($newprogcompletion->id);
        unset($newprogcompletion->timecreated);

        // Check that they match.
        $this->assertEquals($expectedcertcompletion, $newcertcompletion);
        $this->assertEquals($expectedprogcompletion, $newprogcompletion);
    }

    public function test_certif_load_all_completions() {
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
        $results = certif_load_all_completions($user1->id);

        // Make sure we've got two records and they're not the same.
        $this->assertCount(2, $results);
        $this->assertNotEquals($results[0]['progcompletion']->id, $results[1]['progcompletion']->id);

        foreach ($results as $result) {
            $certcompletion = $result['certcompletion'];
            $progcompletion = $result['progcompletion'];

            // The record belongs to user1.
            $this->assertEquals($user1->id, $certcompletion->userid);
            $this->assertEquals($user1->id, $progcompletion->userid);

            // The record is not associated with either of the programs.
            $this->assertNotEquals($prog1->id, $progcompletion->programid);
            $this->assertNotEquals($prog2->id, $progcompletion->programid);

            // The cert and prog records are valid - the results should be identical to certif_load_completion, which has
            // already been tested above.
            list($exectedcertcompletion, $expectedprogcompletion) = certif_load_completion($progcompletion->programid, $user1->id);
            $this->assertEquals($exectedcertcompletion, $certcompletion);
            $this->assertEquals($expectedprogcompletion, $progcompletion);
        }
    }

    /**
     * Data provider for test_certif_conditionally_delete_completion.
     */
    public function data_certif_conditionally_delete_completion() {
        return array(
            array(
                array(),
                array(),
                true, false, false, false), // Assigned, newly assigned.
            array(
                array(),
                array(),
                false, true, true, false), // Not assigned, newly assigned, no history because no progress.
            array(
                array(
                    'status' => CERTIFSTATUS_COMPLETED,
                    'renewalstatus' => CERTIFRENEWALSTATUS_NOTDUE,
                    'certifpath' => CERTIFPATH_RECERT,
                    'timecompleted' => 100,
                    'timewindowopens' => 200,
                    'timeexpires' => 300,
                    'baselinetimeexpires' => 300,
                ),
                array(
                    'status' => STATUS_PROGRAM_COMPLETE,
                    'timecompleted' => 100,
                    'timedue' => 300,
                ),
                true, false, false, false), // Assigned, certified.
            array(
                array(
                    'status' => CERTIFSTATUS_COMPLETED,
                    'renewalstatus' => CERTIFRENEWALSTATUS_NOTDUE,
                    'certifpath' => CERTIFPATH_RECERT,
                    'timecompleted' => 100,
                    'timewindowopens' => 200,
                    'timeexpires' => 300,
                    'baselinetimeexpires' => 300,
                ),
                array(
                    'status' => STATUS_PROGRAM_COMPLETE,
                    'timecompleted' => 100,
                    'timedue' => 300,
                ),
                false, true, false, true), // Not assigned, certified.
            array(
                array(
                    'status' => CERTIFSTATUS_COMPLETED,
                    'renewalstatus' => CERTIFRENEWALSTATUS_DUE,
                    'certifpath' => CERTIFPATH_RECERT,
                    'timecompleted' => 100,
                    'timewindowopens' => 200,
                    'timeexpires' => 300,
                    'baselinetimeexpires' => 300,
                ),
                array(
                    'status' => STATUS_PROGRAM_INCOMPLETE,
                    'timedue' => 300,
                ),
                true, false, false, false), // Assigned, window open.
            array(
                array(
                    'status' => CERTIFSTATUS_COMPLETED,
                    'renewalstatus' => CERTIFRENEWALSTATUS_DUE,
                    'certifpath' => CERTIFPATH_RECERT,
                    'timecompleted' => 100,
                    'timewindowopens' => 200,
                    'timeexpires' => 300,
                    'baselinetimeexpires' => 300,
                ),
                array(
                    'status' => STATUS_PROGRAM_INCOMPLETE,
                    'timedue' => 300,
                ),
                false, true, false, true), // Not assigned, window open.
            array(
                array(
                    'status' => CERTIFSTATUS_EXPIRED,
                    'renewalstatus' => CERTIFRENEWALSTATUS_EXPIRED,
                    'certifpath' => CERTIFPATH_CERT,
                ),
                array(
                    'status' => STATUS_PROGRAM_INCOMPLETE,
                    'timedue' => 300,
                ),
                true, false, false, false), // Assigned, expired.
            array(
                array(
                    'status' => CERTIFSTATUS_EXPIRED,
                    'renewalstatus' => CERTIFRENEWALSTATUS_EXPIRED,
                    'certifpath' => CERTIFPATH_CERT,
                ),
                array(
                    'status' => STATUS_PROGRAM_INCOMPLETE,
                    'timedue' => 300,
                ),
                false, true, false, true), // Not assigned, expired.
        );
    }

    /**
     * Test certif_conditionally_delete_completion.
     *
     * @dataProvider data_certif_conditionally_delete_completion
     */
    public function test_certif_conditionally_delete_completion($certcompletionchanges, $progcompletionchanges, $isassigned,
                                                         $certshouldbedeleted, $progshouldbedeleted, $shouldhavehistory) {
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

        // Hack the removal of the assignment records. We can't unassign the user because that would remove the completion records!
        if (!$isassigned) {
            $DB->delete_records('prog_user_assignment');
        }

        // Update the state of the records.
        $completions = array_merge(certif_load_all_completions($user1->id), certif_load_all_completions($user2->id));
        foreach ($completions as $completion) {
            $certcompletion = $completion['certcompletion'];
            $progcompletion = $completion['progcompletion'];
            foreach ($certcompletionchanges as $key => $value) {
                $certcompletion->$key = $value;
            }
            foreach ($progcompletionchanges as $key => $value) {
                $progcompletion->$key = $value;
            }
            $this->assertEquals(array(), certif_get_completion_errors($certcompletion, $progcompletion));
            $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        }

        // Load the current set of data.
        $expectedcertcompletions = $DB->get_records('certif_completion');
        $expectedprogcompletions = $DB->get_records('prog_completion');
        list($expectedcertcompletionhistory, $progcompletion) = certif_load_completion($cert1->id, $user1->id);

        // Conditionally delete just one cert completion.
        certif_conditionally_delete_completion($cert1->id, $user1->id);

        // Manually make the same change to the expected data.
        if ($certshouldbedeleted) {
            foreach ($expectedcertcompletions as $key => $certcompletion) {
                if ($certcompletion->certifid == $cert1->certifid && $certcompletion->userid == $user1->id) {
                    unset($expectedcertcompletions[$key]);
                }
            }
        }
        if ($progshouldbedeleted) {
            foreach ($expectedprogcompletions as $key => $progcompletion) {
                if ($progcompletion->programid == $cert1->id && $progcompletion->userid == $user1->id) {
                    unset($expectedprogcompletions[$key]);
                }
            }
        }
        if ($shouldhavehistory) {
            unset($expectedcertcompletionhistory->id);
            unset($expectedcertcompletionhistory->timemodified);
            $expectedcertcompletionhistory->unassigned = 1;
        }

        // Then just compare the current data with the expected.
        $actualcertcompletions = $DB->get_records('certif_completion');
        $actualprogcompletions = $DB->get_records('prog_completion');
        $this->assertEquals($expectedcertcompletions, $actualcertcompletions);
        $this->assertEquals($expectedprogcompletions, $actualprogcompletions);

        // Make sure that the history record has been created when appropriate.
        $certcomplhistories = $DB->get_records('certif_completion_history');
        if ($shouldhavehistory) {
            $this->assertCount(1, $certcomplhistories);
            $certcompletionhistory = reset($certcomplhistories);
            unset($certcompletionhistory->id);
            unset($certcompletionhistory->timemodified);
            $this->assertEquals($expectedcertcompletionhistory, $certcompletionhistory);
        } else {
            $this->assertCount(0, $certcomplhistories);
        }
    }

    public function test_certif_conditionally_delete_completion_with_missing_records() {
        global $DB;

        $generator = $this->getDataGenerator();
        $program_generator = \totara_program\testing\generator::instance();

        // Create some users.
        $user1 = $generator->create_user();
        $user2 = $generator->create_user();

        // Create a cert.
        $cert1 = $program_generator->create_certification();

        // We've already tested what happens when both records exist, so just try with missing cert or prog records.

        // Check that the cert record is still deleted if the prog record doesn't exist.
        $program_generator->assign_to_program($cert1->id, ASSIGNTYPE_INDIVIDUAL, $user1->id, null, true);
        $this->assertCount(1, $DB->get_records('certif_completion'));
        $this->assertCount(1, $DB->get_records('prog_completion'));
        $DB->delete_records('prog_completion', array('programid' => $cert1->id, 'userid' => $user1->id));
        $DB->delete_records('prog_user_assignment'); // To make sure the records aren't kept because the user is still assigned.
        $DB->delete_records('prog_assignment'); // To make sure the records aren't kept because the user is still assigned.
        certif_conditionally_delete_completion($cert1->id, $user1->id);
        $this->assertCount(0, $DB->get_records('certif_completion'));

        // Check that the prog record is still deleted if the cert record doesn't exist.
        $program_generator->assign_to_program($cert1->id, ASSIGNTYPE_INDIVIDUAL, $user2->id, null, true);
        $this->assertCount(1, $DB->get_records('certif_completion'));
        $this->assertCount(1, $DB->get_records('prog_completion'));
        $DB->delete_records('certif_completion', array('certifid' => $cert1->certifid, 'userid' => $user2->id));
        $DB->delete_records('prog_user_assignment'); // To make sure the records aren't kept because the user is still assigned.
        $DB->delete_records('prog_assignment'); // To make sure the records aren't kept because the user is still assigned.
        certif_conditionally_delete_completion($cert1->id, $user2->id);
        $this->assertCount(0, $DB->get_records('prog_completion'));
    }

    public function test_certif_delete_completion() {
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

        // Delete just one cert completion.
        certif_delete_completion($cert1->id, $user1->id);

        // Manually make the same change to the expected data. Only certif_completion is affected!!!
        foreach ($expectedcertcompletions as $key => $certcompletion) {
            if ($certcompletion->certifid == $cert1->certifid && $certcompletion->userid == $user1->id) {
                unset($expectedcertcompletions[$key]);
            }
        }

        // Then just compare the current data with the expected.
        $actualcertcompletions = $DB->get_records('certif_completion');
        $actualprogcompletions = $DB->get_records('prog_completion');
        $this->assertEquals($expectedcertcompletions, $actualcertcompletions);
        $this->assertEquals($expectedprogcompletions, $actualprogcompletions);

        // Make sure that it still deletes the record if the user is certified.
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

        // Delete just one cert completion.
        certif_delete_completion($cert2->id, $user2->id);

        // Manually make the same change to the expected data. Only certif_completion is affected!!!
        foreach ($expectedcertcompletions as $key => $certcompletion) {
            if ($certcompletion->certifid == $cert2->certifid && $certcompletion->userid == $user2->id) {
                unset($expectedcertcompletions[$key]);
            }
        }

        // Then just compare the current data with the expected.
        $actualcertcompletions = $DB->get_records('certif_completion');
        $actualprogcompletions = $DB->get_records('prog_completion');
        $this->assertEquals($expectedcertcompletions, $actualcertcompletions);
        $this->assertEquals($expectedprogcompletions, $actualprogcompletions);
    }

    /**
     * Set up users, programs, certifications and assignments.
     */
    public function setup_completions() {

        // Turn off programs. This is to test that it doesn't interfere with certification completion.
        set_config('enableprograms', advanced_feature::DISABLED);

        $program_generator = \totara_program\testing\generator::instance();

        // Create users.
        for ($i = 1; $i <= $this->numtestusers; $i++) {
            $this->users[$i] = $this->getDataGenerator()->create_user();
        }

        // Create programs, mostly so that we don't end up with coincidental success due to matching ids.
        for ($i = 1; $i <= $this->numtestprogs; $i++) {
            $this->programs[$i] = $program_generator->create_program();
        }

        // Create certifications.
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
     * Test certif_fix_missing_completions - ensure that the correct records are repaired when prog_completions are missing.
     */
    public function test_certif_fix_missing_completions_with_prog_completions() {
        global $DB;

        // Set up some data that is valid.
        $this->setup_completions();
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);

        // Break all the records, progs and certs.
        $DB->delete_records('prog_completion', array('coursesetid' => 0));

        // Check that all of the records are broken, progs and certs.
        $expectedfixedcount = 0;
        $progcompletions = $DB->get_records('prog_completion', array('coursesetid' => 0));
        $this->assertCount($expectedfixedcount, $progcompletions);
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestprogs, $fulllist);

        // Apply the fix to just one user/cert.
        certif_fix_missing_completions($this->certifications[6]->id, $this->users[2]->id);

        // Check that the correct records have been fixed.
        $expectedfixedcount = 1; // One cell in the matrix.
        $progcompletions = $DB->get_records('prog_completion', array('coursesetid' => 0));
        $this->assertCount($expectedfixedcount, $progcompletions);
        foreach ($progcompletions as $progcompletion) {
            $this->assertEquals($this->certifications[6]->id, $progcompletion->programid);
            $this->assertEquals($this->users[2]->id, $progcompletion->userid);
        }
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $fulllist);

        // Apply the fix to just one user, all certs (don't need to reset, just overlap).
        certif_fix_missing_completions(0, $this->users[2]->id);

        // Check that the correct records have been fixed.
        $expectedfixedcount = $this->numtestcerts; // One column in the matrix.
        $progcompletions = $DB->get_records('prog_completion', array('coursesetid' => 0));
        $this->assertCount($expectedfixedcount, $progcompletions);
        foreach ($progcompletions as $progcompletion) {
            $this->assertEquals($this->users[2]->id, $progcompletion->userid);
        }
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $fulllist);

        // Apply the fix to just one cert, all users (don't need to reset, just overlap).
        certif_fix_missing_completions($this->certifications[6]->id, 0);

        // Check that the correct records have been fixed.
        $expectedfixedcount = $this->numtestcerts + $this->numtestusers - 1; // One column and one row in the matrix.
        $progcompletions = $DB->get_records('prog_completion', array('coursesetid' => 0));
        $this->assertCount($expectedfixedcount, $progcompletions);
        foreach ($progcompletions as $progcompletion) {
            $this->assertTrue($this->certifications[6]->id == $progcompletion->programid ||
                $this->users[2]->id == $progcompletion->userid);
        }
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $fulllist);

        // Apply the fix to all records (overlaps previous fixes).
        certif_fix_missing_completions(0, 0);

        // Check that the correct records have been fixed.
        $expectedfixedcount = $this->numtestcerts * $this->numtestusers; // The whole matrix.
        $progcompletions = $DB->get_records('prog_completion', array('coursesetid' => 0));
        $this->assertCount($expectedfixedcount, $progcompletions);
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $fulllist);

        // Make sure that no progs were fixed.
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestprogs, $fulllist);
    }

    /**
     * Test certif_fix_missing_completions - ensure that the correct records are repaired when certif_completions are missing.
     */
    public function test_certif_fix_missing_completions_with_certif_completions() {
        global $DB;

        // Set up some data that is valid.
        $this->setup_completions();
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);

        // Break all the records, just certs (progs don't have certif_completion records!).
        $DB->delete_records('certif_completion');

        // Check that all of the records are broken, just certs.
        $expectedfixedcount = 0;
        $certcompletions = $DB->get_records('certif_completion');
        $this->assertCount($expectedfixedcount, $certcompletions);
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);

        // Apply the fix to just one user/cert.
        certif_fix_missing_completions($this->certifications[6]->id, $this->users[2]->id);

        // Check that the correct records have been fixed.
        $expectedfixedcount = 1; // One cell in the matrix.
        $certcompletions = $DB->get_records('certif_completion');
        $this->assertCount($expectedfixedcount, $certcompletions);
        foreach ($certcompletions as $certcompletion) {
            $this->assertEquals($this->certifications[6]->certifid, $certcompletion->certifid);
            $this->assertEquals($this->users[2]->id, $certcompletion->userid);
        }
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $fulllist);

        // Apply the fix to just one user, all certs (don't need to reset, just overlap).
        certif_fix_missing_completions(0, $this->users[2]->id);

        // Check that the correct records have been fixed.
        $expectedfixedcount = $this->numtestcerts; // One column in the matrix.
        $certcompletions = $DB->get_records('certif_completion');
        $this->assertCount($expectedfixedcount, $certcompletions);
        foreach ($certcompletions as $certcompletion) {
            $this->assertEquals($this->users[2]->id, $certcompletion->userid);
        }
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $fulllist);

        // Apply the fix to just one cert, all users (don't need to reset, just overlap).
        certif_fix_missing_completions($this->certifications[6]->id, 0);

        // Check that the correct records have been fixed.
        $expectedfixedcount = $this->numtestcerts + $this->numtestusers - 1; // One column and one row in the matrix.
        $certcompletions = $DB->get_records('certif_completion');
        $this->assertCount($expectedfixedcount, $certcompletions);
        foreach ($certcompletions as $certcompletion) {
            $this->assertTrue($this->certifications[6]->certifid == $certcompletion->certifid ||
                $this->users[2]->id == $certcompletion->userid);
        }
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $fulllist);

        // Apply the fix to all records (overlaps previous fixes).
        certif_fix_missing_completions(0, 0);

        // Check that the correct records have been fixed.
        $expectedfixedcount = $this->numtestcerts * $this->numtestusers; // The whole matrix.
        $certcompletions = $DB->get_records('certif_completion');
        $this->assertCount($expectedfixedcount, $certcompletions);
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $fulllist);

        // Make sure that no progs were unfixed.
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);
    }

    /**
     * Test certif_fix_missing_completions - ensure that the correct records are repaired when both records are missing.
     */
    public function test_certif_fix_missing_completions_with_both_completions() {
        global $DB;

        // Set up some data that is valid.
        $this->setup_completions();
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);

        // Break all the records, progs and certs.
        $DB->delete_records('prog_completion');
        $DB->delete_records('certif_completion');

        // Check that all of the records are broken, progs and certs.
        $expectedfixedcount = 0;
        $certcompletions = $DB->get_records('certif_completion');
        $this->assertCount($expectedfixedcount, $certcompletions);
        $progcompletions = $DB->get_records('prog_completion');
        $this->assertCount($expectedfixedcount, $progcompletions);
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestprogs, $fulllist);

        // Apply the fix to just one user/cert.
        certif_fix_missing_completions($this->certifications[6]->id, $this->users[2]->id);

        // Check that the correct records have been fixed.
        $expectedfixedcount = 1; // One cell in the matrix.
        $certcompletions = $DB->get_records('certif_completion');
        $this->assertCount($expectedfixedcount, $certcompletions);
        $progcompletions = $DB->get_records('prog_completion');
        $this->assertCount($expectedfixedcount, $progcompletions);
        foreach ($certcompletions as $certcompletion) {
            $this->assertEquals($this->certifications[6]->certifid, $certcompletion->certifid);
            $this->assertEquals($this->users[2]->id, $certcompletion->userid);
        }
        foreach ($progcompletions as $progcompletion) {
            $this->assertEquals($this->certifications[6]->id, $progcompletion->programid);
            $this->assertEquals($this->users[2]->id, $progcompletion->userid);
        }
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $fulllist);

        // Apply the fix to just one user, all certs (don't need to reset, just overlap).
        certif_fix_missing_completions(0, $this->users[2]->id);

        // Check that the correct records have been fixed.
        $expectedfixedcount = $this->numtestcerts; // One column in the matrix.
        $certcompletions = $DB->get_records('certif_completion');
        $this->assertCount($expectedfixedcount, $certcompletions);
        $progcompletions = $DB->get_records('prog_completion');
        $this->assertCount($expectedfixedcount, $progcompletions);
        foreach ($certcompletions as $certcompletion) {
            $this->assertEquals($this->users[2]->id, $certcompletion->userid);
        }
        foreach ($progcompletions as $progcompletion) {
            $this->assertEquals($this->users[2]->id, $progcompletion->userid);
        }
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $fulllist);

        // Apply the fix to just one cert, all users (don't need to reset, just overlap).
        certif_fix_missing_completions($this->certifications[6]->id, 0);

        // Check that the correct records have been fixed.
        $expectedfixedcount = $this->numtestcerts + $this->numtestusers - 1; // One column and one row in the matrix.
        $certcompletions = $DB->get_records('certif_completion');
        $this->assertCount($expectedfixedcount, $certcompletions);
        $progcompletions = $DB->get_records('prog_completion');
        $this->assertCount($expectedfixedcount, $progcompletions);
        foreach ($certcompletions as $certcompletion) {
            $this->assertTrue($this->certifications[6]->certifid == $certcompletion->certifid ||
                $this->users[2]->id == $certcompletion->userid);
        }
        foreach ($progcompletions as $progcompletion) {
            $this->assertTrue($this->certifications[6]->id == $progcompletion->programid ||
                $this->users[2]->id == $progcompletion->userid);
        }
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $fulllist);

        // Apply the fix to all records (overlaps previous fixes).
        certif_fix_missing_completions(0, 0);

        // Check that the correct records have been fixed.
        $expectedfixedcount = $this->numtestcerts * $this->numtestusers; // The whole matrix.
        $certcompletions = $DB->get_records('certif_completion');
        $this->assertCount($expectedfixedcount, $certcompletions);
        $progcompletions = $DB->get_records('prog_completion');
        $this->assertCount($expectedfixedcount, $progcompletions);
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $fulllist);

        // Make sure that no progs were fixed.
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestprogs, $fulllist);
    }

    public function test_certif_fix_unassigned_certif_completions() {
        global $DB;

        // Set up some data that is valid.
        $this->setup_completions();
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);

        // Break all the records, progs and certs.
        $DB->delete_records('prog_user_assignment');

        // Check that all of the records are broken, progs and certs.
        $expectedfixedcount = 0;
        $certcompletions = $DB->get_records('certif_completion');
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $certcompletions);
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestprogs, $fulllist);

        // Apply the fix to just one user/cert.
        certif_fix_unassigned_certif_completions($this->certifications[6]->id, $this->users[2]->id);

        // Check that the correct records have been fixed.
        $expectedfixedcount = 1; // One cell in the matrix.
        $certcompletions = $DB->get_records('certif_completion');
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $certcompletions);
        foreach ($certcompletions as $certcompletion) {
            $this->assertTrue($this->certifications[6]->certifid != $certcompletion->certifid ||
                $this->users[2]->id != $certcompletion->userid);
        }
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $fulllist);

        // Apply the fix to just one user, all certs (don't need to reset, just overlap).
        certif_fix_unassigned_certif_completions(0, $this->users[2]->id);

        // Check that the correct records have been fixed.
        $expectedfixedcount = $this->numtestcerts; // One column in the matrix.
        $certcompletions = $DB->get_records('certif_completion');
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $certcompletions);
        foreach ($certcompletions as $certcompletion) {
            $this->assertTrue($this->users[2]->id != $certcompletion->userid);
        }
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $fulllist);

        // Apply the fix to just one cert, all users (don't need to reset, just overlap).
        certif_fix_unassigned_certif_completions($this->certifications[6]->id, 0);

        // Check that the correct records have been fixed.
        $expectedfixedcount = $this->numtestcerts + $this->numtestusers - 1; // One column and one row in the matrix.
        $certcompletions = $DB->get_records('certif_completion');
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $certcompletions);
        foreach ($certcompletions as $certcompletion) {
            $this->assertTrue($this->certifications[6]->certifid != $certcompletion->certifid &&
                $this->users[2]->id != $certcompletion->userid);
        }
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $fulllist);

        // Apply the fix to all records (overlaps previous fixes).
        certif_fix_unassigned_certif_completions(0, 0);

        // Check that the correct records have been fixed.
        $expectedfixedcount = $this->numtestcerts * $this->numtestusers; // The whole matrix.
        $certcompletions = $DB->get_records('certif_completion');
        $this->assertCount($this->numtestusers * $this->numtestcerts - $expectedfixedcount, $certcompletions);
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);

        // Make sure that no progs were fixed.
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestprogs, $fulllist);
    }

    public function test_certif_find_missing_completions() {
        global $DB;

        // Set up some data that is valid.
        $this->setup_completions();
        $this->assert_count_and_close_recordset(0, certif_find_missing_completions());
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);

        // Break all the records, progs and certs.
        $DB->delete_records('prog_completion', array('coursesetid' => 0));

        // Check that all of the records are broken, progs and certs.
        $this->assert_count_and_close_recordset($this->numtestusers * $this->numtestcerts, certif_find_missing_completions());
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestprogs, $fulllist);

        // Apply the fix to just one user, all certs.
        certif_fix_missing_completions(0, $this->users[2]->id);

        // Apply the fix to just one cert, all users (overlapping).
        certif_fix_missing_completions($this->certifications[6]->id, 0);

        // Test the function is returning the correct records when no user or certification is specified.
        $expectedfixedcount = $this->numtestusers * $this->numtestcerts - $this->numtestcerts - $this->numtestusers + 1;
        $this->assert_count_and_close_recordset($expectedfixedcount, certif_find_missing_completions());

        // Test the function is returning the correct records when just the certification is specified.
        $this->assert_count_and_close_recordset($this->numtestusers - 1, certif_find_missing_completions($this->certifications[4]->id, 0));
        $this->assert_count_and_close_recordset(0, certif_find_missing_completions($this->certifications[6]->id, 0)); // All were fixed.

        // Test the function is returning the correct records when just the user is specified.
        $this->assert_count_and_close_recordset($this->numtestcerts - 1, certif_find_missing_completions(0, $this->users[7]->id));
        $this->assert_count_and_close_recordset(0, certif_find_missing_completions(0, $this->users[2]->id)); // All were fixed.

        // Test the function is returning the correct records when certification and user are specified.
        $this->assert_count_and_close_recordset(1, certif_find_missing_completions($this->certifications[4]->id, $this->users[6]->id));
        $this->assert_count_and_close_recordset(0, certif_find_missing_completions($this->certifications[6]->id, $this->users[3]->id));
        $this->assert_count_and_close_recordset(0, certif_find_missing_completions($this->certifications[5]->id, $this->users[2]->id));
        $this->assert_count_and_close_recordset(0, certif_find_missing_completions($this->certifications[6]->id, $this->users[2]->id));
    }

    public function test_certif_find_unassigned_certif_completions() {
        global $DB;

        // Set up some data that is valid.
        $this->setup_completions();

        // Show that existing prog_user_assignments will prevent the certif_completions from being reported as broken.
        $this->assert_count_and_close_recordset(0, certif_find_unassigned_certif_completions());
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);

        // Break all the records, progs and certs.
        $DB->delete_records('prog_user_assignment');

        // Check that all of the records are broken, progs and certs.
        $this->assert_count_and_close_recordset($this->numtestusers * $this->numtestcerts, certif_find_unassigned_certif_completions());
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestcerts, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount($this->numtestusers * $this->numtestprogs, $fulllist);

        // Apply the fix to just one user, all progs.
        certif_fix_unassigned_certif_completions(0, $this->users[2]->id);

        // Apply the fix to just one cert, all users (overlapping).
        certif_fix_unassigned_certif_completions($this->certifications[6]->id, 0);

        // Test the function is returning the correct records when no user or certification is specified.
        $expectedfixedcount = $this->numtestusers * $this->numtestcerts - $this->numtestcerts - $this->numtestusers + 1;
        $this->assert_count_and_close_recordset($expectedfixedcount, certif_find_unassigned_certif_completions());

        // Test the function is returning the correct records when just the certification is specified.
        $this->assert_count_and_close_recordset($this->numtestusers - 1, certif_find_unassigned_certif_completions($this->certifications[4]->id, 0));
        $this->assert_count_and_close_recordset(0, certif_find_unassigned_certif_completions($this->certifications[6]->id, 0)); // All were fixed.

        // Test the function is returning the correct records when just the user is specified.
        $this->assert_count_and_close_recordset($this->numtestcerts - 1, certif_find_unassigned_certif_completions(0, $this->users[7]->id));
        $this->assert_count_and_close_recordset(0, certif_find_unassigned_certif_completions(0, $this->users[2]->id)); // All were fixed.

        // Test the function is returning the correct records when certification and user are specified.
        $this->assert_count_and_close_recordset(1, certif_find_unassigned_certif_completions($this->certifications[4]->id, $this->users[6]->id));
        $this->assert_count_and_close_recordset(0, certif_find_unassigned_certif_completions($this->certifications[6]->id, $this->users[3]->id));
        $this->assert_count_and_close_recordset(0, certif_find_unassigned_certif_completions($this->certifications[5]->id, $this->users[2]->id));
        $this->assert_count_and_close_recordset(0, certif_find_unassigned_certif_completions($this->certifications[6]->id, $this->users[2]->id));
    }

    public function test_certif_get_all_completions_with_errors() {
        global $DB;

        $this->setup_completions();

        // One consistency problem.
        list($certcompletion, $progcompletion) = certif_load_completion($this->certifications[1]->id, $this->users[2]->id);
        $progcompletion->status = STATUS_PROGRAM_COMPLETE;
        $errors = certif_get_completion_errors($certcompletion, $progcompletion);
        $this->assertNotEmpty($errors);
        $problemkey = certif_get_completion_error_problemkey($errors);
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion, '', $problemkey));

        // One missing prog_completion.
        $DB->delete_records('prog_completion', array('programid' => $this->certifications[3]->id, 'userid' => $this->users[4]->id));

        // One unassigned certif completion record.
        $DB->delete_records('prog_user_assignment', array('programid' => $this->certifications[5]->id, 'userid' => $this->users[6]->id));

        // Run the function.
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();

        // Check that the results contain the problems.
        $this->assertCount(3, $fulllist);
        $consistencyitem = $fulllist[$this->certifications[1]->id . '-' . $this->users[2]->id];
        $missingitem = $fulllist[$this->certifications[3]->id . '-' . $this->users[4]->id];
        $unassigneditem = $fulllist[$this->certifications[5]->id . '-' . $this->users[6]->id];
        $this->assertEquals('Program status should be \'Program incomplete\' when user is newly assigned.', $consistencyitem->problem);
        $this->assertEquals('Completion records missing', $missingitem->problem);
        $this->assertEquals('Completion exists for unassigned user', $unassigneditem->problem);

        $this->assertCount(3, $aggregatelist);
        $consistencyaggregate = $aggregatelist['error:stateassigned-progstatusincorrect'];
        $missingaggregate = $aggregatelist['error:missingcompletion'];
        $unassignedaggregate = $aggregatelist['error:unassignedcertifcompletion'];
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

        $this->assertEquals($this->numtestusers * $this->numtestcerts, $totalcount); // Excludes progs.
    }

    /**
     * Creates a certif_completion record for the user and certification we are testing on,
     * as well as for a different user and another again for a different certification.
     */
    public function create_certif_completion() {
        global $DB;

        $program_generator = \totara_program\testing\generator::instance();
        $certification1 = new program($program_generator->create_certification());
        $certification2 = new program($program_generator->create_certification());

        // We'll clone this object and vary it to get a variation of history records.
        $originalcompletion = new stdClass();
        $originalcompletion->certifid = $certification1->certifid;
        $originalcompletion->userid = $this->getDataGenerator()->create_user()->id;
        $originalcompletion->certifpath = CERTIFPATH_RECERT;
        $originalcompletion->status = CERTIFSTATUS_COMPLETED;
        $originalcompletion->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $originalcompletion->timewindowopens = 1000;
        $originalcompletion->timeexpires = 1500;
        $originalcompletion->baselinetimeexpires = 1500;
        $originalcompletion->timecompleted = 500;
        $originalcompletion->timemodified = time();
        $originalcompletion->id = $DB->insert_record('certif_completion', $originalcompletion);

        $cert2_user1 = clone $originalcompletion;
        unset($cert2_user1->id);
        $cert2_user1->certifid = $certification2->certifid;
        $DB->insert_record('certif_completion', $cert2_user1);

        $cert1_user2 = clone $originalcompletion;
        unset($cert1_user2->id);
        $cert1_user2->userid = $this->getDataGenerator()->create_user()->id;
        $DB->insert_record('certif_completion', $cert1_user2);

        copy_certif_completion_to_hist($originalcompletion->certifid, $originalcompletion->userid);

        return $originalcompletion;
    }

    /**
     * Creates a certif_completion record for the user and certification we are testing on,
     * as well as for a different user and another again for a different certification.
     *
     * Checks we only copy the certif_completion record that we specified the user and certification for.
     */
    public function test_copy_certif_completion_to_hist_selects_correct_user_cert() {
        global $DB;

        $originalcompletion = $this->create_certif_completion();

        $this->assertEquals(1, $DB->count_records('certif_completion_history'));
        $this->assertTrue($DB->record_exists('certif_completion_history',
            array('certifid' => $originalcompletion->certifid, 'userid' => $originalcompletion->userid)));

        // The certif_completion record that gets copied is not deleted by copy_certif_completion_to_hist().
        $this->assertTrue($DB->record_exists('certif_completion',
            array('certifid' => $originalcompletion->certifid, 'userid' => $originalcompletion->userid)));
    }

    /**
     * Try copying the same certif_completion record to history as we did in the last test.
     *
     * The history record should NOT be duplicated.
     */
    public function test_copy_certif_completion_to_hist_no_change_with_duplicate() {
        global $DB;

        $originalcompletion = $this->create_certif_completion();

        copy_certif_completion_to_hist($originalcompletion->certifid, $originalcompletion->userid);
        $this->assertEquals(1, $DB->count_records('certif_completion_history'));
    }

    /**
     * Change the timewindowopens value in the certif_completion record.
     *
     * This should not create a new record but simply update the existing history record.
     */
    public function test_copy_certif_completion_to_hist_only_windowopen_is_different() {
        global $DB;

        $originalcompletion = $this->create_certif_completion();

        $differentwindowopen = $originalcompletion->timewindowopens + 100;

        $DB->set_field('certif_completion', 'timewindowopens', $differentwindowopen,
            array('certifid' => $originalcompletion->certifid, 'userid' => $originalcompletion->userid));

        copy_certif_completion_to_hist($originalcompletion->certifid, $originalcompletion->userid);

        // There should still be 1 record which was updated with the new timewindowopens.
        $this->assertEquals(1, $DB->count_records('certif_completion_history'));
        $this->assertTrue($DB->record_exists('certif_completion_history',
            array('timewindowopens' => $differentwindowopen)));
    }

    /**
     * Change the timecompleted value in the certif_completion record.
     *
     * Copying this creates a new history record and leaves the other as is.
     */
    public function test_copy_certif_completion_to_hist_only_timecompleted_is_different() {
        global $DB;

        $originalcompletion = $this->create_certif_completion();

        $differenttimecompleted = $originalcompletion->timecompleted + 100;

        $DB->set_field('certif_completion', 'timecompleted', $differenttimecompleted,
            array('certifid' => $originalcompletion->certifid, 'userid' => $originalcompletion->userid));

        copy_certif_completion_to_hist($originalcompletion->certifid, $originalcompletion->userid);

        // There should be a new record which contains the new timecompleted. The old record should still have it's
        // original timecompleted.
        $this->assertEquals(2, $DB->count_records('certif_completion_history'));
        $this->assertTrue($DB->record_exists('certif_completion_history',
            array('timecompleted' => $originalcompletion->timecompleted)));
        $this->assertTrue($DB->record_exists('certif_completion_history',
            array('timecompleted' => $differenttimecompleted)));

        return $differenttimecompleted;
    }

    /**
     * Now change the timeexpires as well.
     *
     * The 2 existing history records should remain unchanged. A third is created to contain the different expiry date.
     */
    public function test_copy_certif_completion_to_hist_only_timeexpires_is_different() {
        global $DB;

        $originalcompletion = $this->create_certif_completion();

        $differenttimecompleted = $originalcompletion->timecompleted + 100;

        $DB->set_field('certif_completion', 'timecompleted', $differenttimecompleted,
            array('certifid' => $originalcompletion->certifid, 'userid' => $originalcompletion->userid));

        copy_certif_completion_to_hist($originalcompletion->certifid, $originalcompletion->userid);

        $differenttimeexpires = $originalcompletion->timeexpires + 100;

        $DB->set_field('certif_completion', 'timeexpires', $differenttimeexpires,
            array('certifid' => $originalcompletion->certifid, 'userid' => $originalcompletion->userid));

        copy_certif_completion_to_hist($originalcompletion->certifid, $originalcompletion->userid);

        // There should be a new record which contains the new timeexpires.
        // The first 2 records should contain their original values.
        $this->assertEquals(3, $DB->count_records('certif_completion_history'));
        $this->assertTrue($DB->record_exists('certif_completion_history',
            array('timecompleted' => $originalcompletion->timecompleted, 'timeexpires' => $originalcompletion->timeexpires)));
        $this->assertTrue($DB->record_exists('certif_completion_history',
            array('timecompleted' => $differenttimecompleted, 'timeexpires' => $originalcompletion->timeexpires)));
        $this->assertTrue($DB->record_exists('certif_completion_history',
            array('timecompleted' => $differenttimecompleted, 'timeexpires' => $differenttimeexpires)));
    }

    /**
     * With no existing completion data, write a valid history record.
     *
     */
    public function test_certif_write_completion_history_creates_history_record() {
        global $DB;

        $program_generator = \totara_program\testing\generator::instance();
        $certification1 = new program($program_generator->create_certification());

        $originalhistory = new stdClass();
        $originalhistory->certifid = $certification1->certifid;
        $originalhistory->userid = $this->getDataGenerator()->create_user()->id;
        $originalhistory->certifpath = CERTIFPATH_RECERT;
        $originalhistory->status = CERTIFSTATUS_COMPLETED;
        $originalhistory->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $originalhistory->timewindowopens = 1000;
        $originalhistory->timeexpires = 1500;
        $originalhistory->baselinetimeexpires = 1500;
        $originalhistory->timecompleted = 500;
        $originalhistory->timemodified = time();
        $originalhistory->unassigned = 0;

        $this->assertTrue(certif_write_completion_history($originalhistory));
        $this->assertEquals(0, $DB->count_records('certif_completion'));
        $this->assertEquals(1, $DB->count_records('certif_completion_history'));
        $this->assertTrue($DB->record_exists('certif_completion_history', (array) $originalhistory));
    }

    /**
     * Try to repeat writing the same history data from the last test. This is considered a duplicate
     * and is not allowed.
     */
    public function test_certif_write_completion_history_duplicate() {
        global $DB;

        $program_generator = \totara_program\testing\generator::instance();
        $certification1 = new program($program_generator->create_certification());

        $originalhistory = new stdClass();
        $originalhistory->certifid = $certification1->certifid;
        $originalhistory->userid = $this->getDataGenerator()->create_user()->id;
        $originalhistory->certifpath = CERTIFPATH_RECERT;
        $originalhistory->status = CERTIFSTATUS_COMPLETED;
        $originalhistory->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $originalhistory->timewindowopens = 1000;
        $originalhistory->timeexpires = 1500;
        $originalhistory->baselinetimeexpires = 1500;
        $originalhistory->timecompleted = 500;
        $originalhistory->timemodified = time();
        $originalhistory->unassigned = 0;

        $this->assertTrue(certif_write_completion_history($originalhistory));

        try {
            certif_write_completion_history($originalhistory);
            $this->fail('Expected exception was not thrown.');
        } catch (\moodle_exception $e) {
            $this->assertStringContainsString('Call to certif_write_completion_history with completion record that does not match the existing record', $e->getMessage());
        }
        $this->assertEquals(1, $DB->count_records('certif_completion_history'));
    }

    /**
     * The only change made in from the original data is the timewindowopens. This is still considered a duplicate
     * and is not allowed.
     */
    public function test_certif_write_completion_history_different_timewindowopens() {
        global $DB;

        $program_generator = \totara_program\testing\generator::instance();
        $certification1 = new program($program_generator->create_certification());

        $originalhistory = new stdClass();
        $originalhistory->certifid = $certification1->certifid;
        $originalhistory->userid = $this->getDataGenerator()->create_user()->id;
        $originalhistory->certifpath = CERTIFPATH_RECERT;
        $originalhistory->status = CERTIFSTATUS_COMPLETED;
        $originalhistory->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $originalhistory->timewindowopens = 1000;
        $originalhistory->timeexpires = 1500;
        $originalhistory->baselinetimeexpires = 1500;
        $originalhistory->timecompleted = 500;
        $originalhistory->timemodified = time();
        $originalhistory->unassigned = 0;

        $this->assertTrue(certif_write_completion_history($originalhistory));

        $timewindowopenshistory = clone $originalhistory;
        $timewindowopenshistory->timewindowopens = $originalhistory->timewindowopens + 100;
        try {
            certif_write_completion_history($timewindowopenshistory);
            $this->fail('Expected exception was not thrown.');
        } catch (\moodle_exception $e) {
            $this->assertStringContainsString('Call to certif_write_completion_history with completion record that does not match the existing record', $e->getMessage());
        }
        $this->assertEquals(1, $DB->count_records('certif_completion_history'));
        $this->assertTrue($DB->record_exists('certif_completion_history', (array) $originalhistory));
    }

    /**
     * Change only the timecompleted value from the original data. This is now considered a unique record
     * and is inserted alongside the original record.
     */
    public function test_certif_write_completion_history_different_timecompleted() {
        global $DB;

        $program_generator = \totara_program\testing\generator::instance();
        $certification1 = new program($program_generator->create_certification());

        $originalhistory = new stdClass();
        $originalhistory->certifid = $certification1->certifid;
        $originalhistory->userid = $this->getDataGenerator()->create_user()->id;
        $originalhistory->certifpath = CERTIFPATH_RECERT;
        $originalhistory->status = CERTIFSTATUS_COMPLETED;
        $originalhistory->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $originalhistory->timewindowopens = 1000;
        $originalhistory->timeexpires = 1500;
        $originalhistory->baselinetimeexpires = 1500;
        $originalhistory->timecompleted = 500;
        $originalhistory->timemodified = time();
        $originalhistory->unassigned = 0;

        $this->assertTrue(certif_write_completion_history($originalhistory));

        $timecompletedhistory = clone $originalhistory;
        $timecompletedhistory->timecompleted = $originalhistory->timecompleted + 100;
        certif_write_completion_history($timecompletedhistory);
        $this->assertEquals(2, $DB->count_records('certif_completion_history'));
        $this->assertTrue($DB->record_exists('certif_completion_history', (array) $originalhistory));
        $this->assertTrue($DB->record_exists('certif_completion_history', (array) $timecompletedhistory));
    }

    /**
     * Change the timeexpires from the original data. This is considered unique and is inserted alongside
     * the two records that already exist.
     */
    public function test_certif_write_completion_history_different_timeexpires() {
        global $DB;

        $program_generator = \totara_program\testing\generator::instance();
        $certification1 = new program($program_generator->create_certification());

        $originalhistory = new stdClass();
        $originalhistory->certifid = $certification1->certifid;
        $originalhistory->userid = $this->getDataGenerator()->create_user()->id;
        $originalhistory->certifpath = CERTIFPATH_RECERT;
        $originalhistory->status = CERTIFSTATUS_COMPLETED;
        $originalhistory->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $originalhistory->timewindowopens = 1000;
        $originalhistory->timeexpires = 1500;
        $originalhistory->baselinetimeexpires = 1500;
        $originalhistory->timecompleted = 500;
        $originalhistory->timemodified = time();
        $originalhistory->unassigned = 0;

        $this->assertTrue(certif_write_completion_history($originalhistory));

        $timecompletedhistory = clone $originalhistory;
        $timecompletedhistory->timecompleted = $originalhistory->timecompleted + 100;
        certif_write_completion_history($timecompletedhistory);

        $timeexpireshistory = clone $originalhistory;
        $timeexpireshistory->timeexpires = $originalhistory->timeexpires + 100;
        certif_write_completion_history($timeexpireshistory);
        $this->assertEquals(3, $DB->count_records('certif_completion_history'));
        $this->assertTrue($DB->record_exists('certif_completion_history', (array) $originalhistory));
        $this->assertTrue($DB->record_exists('certif_completion_history', (array) $timecompletedhistory));
        $this->assertTrue($DB->record_exists('certif_completion_history', (array) $timeexpireshistory));
    }

    public function test_certif_set_state_certified() {
        global $DB;

        $program_generator = \totara_program\testing\generator::instance();
        $completion_generator = \core_completion\testing\generator::instance();

        // Set up some stuff.
        $user1 = $this->getDataGenerator()->create_user(); // Control user.
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user(); // Control user.

        $certsettings = array(
            'cert_windowperiod' => '5 day',
            'cert_activeperiod' => '10 day',
        );
        $cert1 = $program_generator->legacy_create_certification($certsettings);
        $cert2 = $program_generator->legacy_create_certification($certsettings); // Control certification.

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();
        $course4 = $this->getDataGenerator()->create_course();
        $course5 = $this->getDataGenerator()->create_course(); // Control course.

        $program_generator->add_courses_and_courseset_to_program($cert1, array(array($course1), array($course2), array($course3, $course4)), CERTIFPATH_CERT);
        $program_generator->add_courses_and_courseset_to_program($cert1, array(array($course3)), CERTIFPATH_RECERT);
        $program_generator->add_courses_and_courseset_to_program($cert2, array(array($course5)), CERTIFPATH_CERT);

        $program_generator->assign_to_program($cert1->id, ASSIGNTYPE_INDIVIDUAL, $user1->id, null, true);
        $program_generator->assign_to_program($cert1->id, ASSIGNTYPE_INDIVIDUAL, $user2->id, null, true);
        $program_generator->assign_to_program($cert2->id, ASSIGNTYPE_INDIVIDUAL, $user2->id, null, true);
        $program_generator->assign_to_program($cert1->id, ASSIGNTYPE_INDIVIDUAL, $user3->id, null, true);

        // Create some course set group completion records (with timecompleted).
        $now = time();
        $user1course1timecompleted = $now - DAYSECS * 10; // Recent, other user.
        $user2course5timecompleted = $now - DAYSECS * 10; // Recent, other cert.
        $user2course1timecompleted = $now - DAYSECS * 30;
        $user2course2timecompleted = $now - DAYSECS * 20; // Expected timecompleted - most recent relevant.
        $user2course3timecompleted = $now - DAYSECS * 15; // Not expected, because the course set group is not complete.
        $completion_generator->complete_course($course1, $user1, $user1course1timecompleted);
        $completion_generator->complete_course($course5, $user2, $user2course5timecompleted);
        $completion_generator->complete_course($course1, $user2, $user2course1timecompleted);
        $completion_generator->complete_course($course2, $user2, $user2course2timecompleted);
        $completion_generator->complete_course($course3, $user2, $user2course3timecompleted);
        // Don't complete the fourth course, because it would mark the user certified.

        // Before doing the positive test, check that the function will fail correctly.

        // When the status is already certified, it can't change to certified.
        $this->assertTrue(certif_set_state_certified($cert1->id, $user3->id, 'Testing fail 1 certif_set_state_certified'));
        $this->assertFalse(certif_set_state_certified($cert1->id, $user3->id, 'Testing fail 1 certif_set_state_certified'));
        $latestlog = $DB->get_records('prog_completion_log', array(), 'id DESC', '*', 0, 1);
        $latestlog = reset($latestlog);
        $this->assertEquals($cert1->id, $latestlog->programid);
        $this->assertEquals($user3->id, $latestlog->userid);
        $this->assertStringStartsWith('Tried to set state to Certified, but current state is not Assigned, Window open or Expired.', $latestlog->description);

        // Also, with some invalid data - timecompleted can only be == 0 if status is incomplete.
        $DB->set_field('prog_completion', 'timecompleted', 0, array('programid' => $cert1->id, 'userid' => $user3->id, 'coursesetid' => 0));
        $this->assertFalse(certif_set_state_certified($cert1->id, $user3->id, 'Testing fail 1 certif_set_state_certified'));
        $latestlog = $DB->get_records('prog_completion_log', array(), 'id DESC', '*', 0, 1);
        $latestlog = reset($latestlog);
        $this->assertEquals($cert1->id, $latestlog->programid);
        $this->assertEquals($user3->id, $latestlog->userid);
        $this->assertStringStartsWith('Tried to set state to Certified, but failed because current completion data is invalid.', $latestlog->description);

        // Now do the positive test.

        // Run the function and capture the event.
        $sink = $this->redirectEvents();
        $this->assertTrue(certif_set_state_certified($cert1->id, $user2->id, 'Testing pass certif_set_state_certified'));
        $events = $sink->get_events();

        // Check the completion records have been updated to certified.
        list($certcompletion, $progcompletion) = certif_load_completion($cert1->id, $user2->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $this->assertEquals($user2course2timecompleted, $progcompletion->timecompleted); // Second course set - third hasn't been completed yet.
        $this->assertEquals(CERTIFSTATUS_COMPLETED, $certcompletion->status);
        $this->assertEquals($user2course2timecompleted, $certcompletion->timecompleted);
        $this->assertEquals($user2course2timecompleted + 5 * DAYSECS, $certcompletion->timewindowopens);
        $this->assertEquals($user2course2timecompleted + 10 * DAYSECS, $certcompletion->timeexpires);

        // Check the event.
        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertInstanceOf('\totara_program\event\program_completed', $event);
        $this->assertEquals($cert1->id, $event->objectid);
        $this->assertEquals($user2->id, $event->userid);

        // Check the log.
        $latestlog = $DB->get_records('prog_completion_log', array(), 'id DESC', '*', 0, 1);
        $latestlog = reset($latestlog);
        $this->assertEquals($cert1->id, $latestlog->programid);
        $this->assertEquals($user2->id, $latestlog->userid);
        $this->assertStringStartsWith('Testing pass certif_set_state_certified', $latestlog->description);
    }

    public function test_certif_set_state_windowopen() {
        global $DB;

        $program_generator = \totara_program\testing\generator::instance();
        $completion_generator = \core_completion\testing\generator::instance();

        // Set up some stuff.
        $user1 = $this->getDataGenerator()->create_user(); // Control user.
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user(); // Control user.

        $certsettings = array(
            'cert_windowperiod' => '5 day',
            'cert_activeperiod' => '10 day',
        );
        $cert1 = $program_generator->create_certification($certsettings);
        $cert2 = $program_generator->create_certification($certsettings); // Control certification.

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course(); // Control course.

        $program_generator->add_courses_and_courseset_to_program($cert1, array(array($course1)), CERTIFPATH_CERT);
        $program_generator->add_courses_and_courseset_to_program($cert1, array(array($course2)), CERTIFPATH_RECERT);
        $program_generator->add_courses_and_courseset_to_program($cert2, array(array($course3)), CERTIFPATH_CERT);
        $program_generator->add_courses_and_courseset_to_program($cert2, array(array($course3)), CERTIFPATH_RECERT);

        $program_generator->assign_to_program($cert1->id, ASSIGNTYPE_INDIVIDUAL, $user1->id, null, true);
        $program_generator->assign_to_program($cert1->id, ASSIGNTYPE_INDIVIDUAL, $user2->id, null, true);
        $program_generator->assign_to_program($cert2->id, ASSIGNTYPE_INDIVIDUAL, $user2->id, null, true);
        $program_generator->assign_to_program($cert1->id, ASSIGNTYPE_INDIVIDUAL, $user3->id, null, true);

        $allmessagetypes = array(
            MESSAGETYPE_ENROLMENT,
            MESSAGETYPE_UNENROLMENT,
            MESSAGETYPE_PROGRAM_DUE,
            MESSAGETYPE_PROGRAM_OVERDUE,
            MESSAGETYPE_PROGRAM_COMPLETED,
            MESSAGETYPE_COURSESET_DUE,
            MESSAGETYPE_COURSESET_OVERDUE,
            MESSAGETYPE_COURSESET_COMPLETED,
            MESSAGETYPE_LEARNER_FOLLOWUP,
            MESSAGETYPE_RECERT_WINDOWOPEN,
            MESSAGETYPE_RECERT_WINDOWDUECLOSE,
            MESSAGETYPE_RECERT_FAILRECERT,
        );
        $coursesetmessagetypes = [
            MESSAGETYPE_COURSESET_DUE,
            MESSAGETYPE_COURSESET_OVERDUE,
            MESSAGETYPE_COURSESET_COMPLETED,
        ];
        $messagetypetotal = count($allmessagetypes) + count($coursesetmessagetypes);

        // Set up the program messages.
        $programmessagemanager = $cert1->get_messagesmanager();
        $programmessagemanager->delete();
        foreach ($allmessagetypes as $messagetype) {
            $programmessagemanager->add_message($messagetype);
        }
        $programmessagemanager->save_messages();
        prog_messages_manager::get_program_messages_manager($cert1->id, true); // Causes static cache to be reset.
        $cert1messageprogcompleteid = $DB->get_field('prog_message', 'id',
            array('programid' => $cert1->id, 'messagetype' => MESSAGETYPE_PROGRAM_COMPLETED));
        $cert1messagecsgcompleteid = $DB->get_field('prog_message', 'id',
            array('programid' => $cert1->id, 'messagetype' => MESSAGETYPE_COURSESET_COMPLETED));

        $programmessagemanager = $cert2->get_messagesmanager();
        $programmessagemanager->delete();
        foreach ($allmessagetypes as $messagetype) {
            $programmessagemanager->add_message($messagetype);
        }
        $programmessagemanager->save_messages();
        prog_messages_manager::get_program_messages_manager($cert2->id, true); // Causes static cache to be reset.
        $cert2messageprogcompleteid = $DB->get_field('prog_message', 'id',
            array('programid' => $cert2->id, 'messagetype' => MESSAGETYPE_PROGRAM_COMPLETED));
        $cert2messagecsgcompleteid = $DB->get_field('prog_message', 'id',
            array('programid' => $cert2->id, 'messagetype' => MESSAGETYPE_COURSESET_COMPLETED));

        // Before doing the positive test, check that the function will fail correctly.

        // We need to check that the function will fail if the user is in any state other than CERTIFCOMPLETIONSTATE_CERTIFIED.
        // First try from newly assigned.
        $this->assertFalse(certif_set_state_windowopen($cert1->id, $user3->id, 'Testing fail 1 certif_set_state_windowopen'));
        $latestlog = $DB->get_records('prog_completion_log', array(), 'id DESC', '*', 0, 1);
        $latestlog = reset($latestlog);
        $this->assertEquals($cert1->id, $latestlog->programid);
        $this->assertEquals($user3->id, $latestlog->userid);
        $this->assertStringStartsWith('Tried to set state to Window open, but current state is not Certified.', $latestlog->description);

        // Second try from window open (use the functions we're testing to change state, positive tests are elsewhere here).
        $DB->delete_records('prog_completion_log'); // To ensure we're not looking at the previous log.
        $this->assertTrue(certif_set_state_certified($cert1->id, $user3->id, 'Testing fail 2 certif_set_state_windowopen'));
        $this->assertTrue(certif_set_state_windowopen($cert1->id, $user3->id, 'Testing fail 2 certif_set_state_windowopen'));
        $this->assertFalse(certif_set_state_windowopen($cert1->id, $user3->id, 'Testing fail 2 certif_set_state_windowopen'));
        $latestlog = $DB->get_records('prog_completion_log', array(), 'id DESC', '*', 0, 1);
        $latestlog = reset($latestlog);
        $this->assertEquals($cert1->id, $latestlog->programid);
        $this->assertEquals($user3->id, $latestlog->userid);
        $this->assertStringStartsWith('Tried to set state to Window open, but current state is not Certified.', $latestlog->description);

        // Last from expired.
        $DB->delete_records('prog_completion_log'); // To ensure we're not looking at the previous log.
        $this->assertTrue(certif_set_state_expired($cert1->id, $user3->id, 'Testing fail 3 certif_set_state_windowopen'));
        $this->assertFalse(certif_set_state_windowopen($cert1->id, $user3->id, 'Testing fail 3 certif_set_state_windowopen'));
        $latestlog = $DB->get_records('prog_completion_log', array(), 'id DESC', '*', 0, 1);
        $latestlog = reset($latestlog);
        $this->assertEquals($cert1->id, $latestlog->programid);
        $this->assertEquals($user3->id, $latestlog->userid);
        $this->assertStringStartsWith('Tried to set state to Window open, but current state is not Certified.', $latestlog->description);

        // Also, test failure with some invalid data - timecompleted can only be > 0 if status is complete.
        $DB->set_field('prog_completion', 'timecompleted', 123, array('programid' => $cert1->id, 'userid' => $user3->id, 'coursesetid' => 0));
        $this->assertFalse(certif_set_state_windowopen($cert1->id, $user3->id, 'Testing fail 4 certif_set_state_certified'));
        $latestlog = $DB->get_records('prog_completion_log', array(), 'id DESC', '*', 0, 1);
        $latestlog = reset($latestlog);
        $this->assertEquals($cert1->id, $latestlog->programid);
        $this->assertEquals($user3->id, $latestlog->userid);
        $this->assertStringStartsWith('Tried to set state to Window open, but failed because current completion data is invalid.', $latestlog->description);

        // Start testing what happens when there are no problems.

        // Clear out the prog_messagelogs that might have been create earlier when investigating problems.
        $DB->execute('DELETE FROM {prog_messagelog}');

        // Create some certified records. Includes course completion and course set group completion records.
        $completion_generator->complete_course($course1, $user1);
        $completion_generator->complete_course($course2, $user1);
        $completion_generator->complete_course($course3, $user1);
        $completion_generator->complete_course($course1, $user2);
        $completion_generator->complete_course($course2, $user2);
        $completion_generator->complete_course($course3, $user2);
        list($user1cert1precertcompletion, $user1cert1preprogcompletion) = certif_load_completion($cert1->id, $user1->id);
        $this->assertEquals(CERTIFCOMPLETIONSTATE_CERTIFIED, certif_get_completion_state($user1cert1precertcompletion));
        list($user2cert1precertcompletion, $user2cert1preprogcompletion) = certif_load_completion($cert1->id, $user2->id);
        $this->assertEquals(CERTIFCOMPLETIONSTATE_CERTIFIED, certif_get_completion_state($user2cert1precertcompletion));
        list($user2cert2precertcompletion, $user2cert2preprogcompletion) = certif_load_completion($cert2->id, $user2->id);
        $this->assertEquals(CERTIFCOMPLETIONSTATE_CERTIFIED, certif_get_completion_state($user2cert2precertcompletion));

        // Check the state of the history records before we run the function.
        $this->assertEquals(0, $DB->count_records('certif_completion_history', array('certifid' => $cert1->certifid, 'userid' => $user1->id)));
        $this->assertEquals(0, $DB->count_records('certif_completion_history', array('certifid' => $cert1->certifid, 'userid' => $user2->id)));
        $this->assertEquals(0, $DB->count_records('certif_completion_history', array('certifid' => $cert2->certifid, 'userid' => $user2->id)));

        // Check the state of the prog_completion records before we run the function.
        $where = "programid = :programid AND userid = :userid AND coursesetid = 0 AND status = " . STATUS_PROGRAM_COMPLETE;
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where, array('programid' => $cert1->id, 'userid' => $user1->id)));
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where, array('programid' => $cert1->id, 'userid' => $user2->id)));
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where, array('programid' => $cert2->id, 'userid' => $user2->id)));
        $where = "programid = :programid AND userid = :userid AND coursesetid = 0 AND status = " . STATUS_PROGRAM_INCOMPLETE;
        $this->assertEquals(0, $DB->count_records_select('prog_completion', $where, array('programid' => $cert1->id, 'userid' => $user1->id)));
        $this->assertEquals(0, $DB->count_records_select('prog_completion', $where, array('programid' => $cert1->id, 'userid' => $user2->id)));
        $this->assertEquals(0, $DB->count_records_select('prog_completion', $where, array('programid' => $cert2->id, 'userid' => $user2->id)));

        // Check the state of the non-zero course set group records before we run the function (all primary path).
        $where = "programid = :programid AND userid = :userid AND coursesetid > 0 AND status = " . STATUS_COURSESET_COMPLETE;
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where, array('programid' => $cert1->id, 'userid' => $user1->id)));
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where, array('programid' => $cert1->id, 'userid' => $user2->id)));
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where, array('programid' => $cert2->id, 'userid' => $user2->id)));
        $where = "programid = :programid AND userid = :userid AND coursesetid > 0 AND status = " . STATUS_COURSESET_INCOMPLETE;
        $this->assertEquals(0, $DB->count_records_select('prog_completion', $where, array('programid' => $cert1->id, 'userid' => $user1->id)));
        $this->assertEquals(0, $DB->count_records_select('prog_completion', $where, array('programid' => $cert1->id, 'userid' => $user2->id)));
        $this->assertEquals(0, $DB->count_records_select('prog_completion', $where, array('programid' => $cert2->id, 'userid' => $user2->id)));

        // Check the state of the course completion records before we run the function.
        $this->assertEquals(1, $DB->count_records('course_completions', array('course' => $course1->id, 'userid' => $user1->id)));
        $this->assertEquals(1, $DB->count_records('course_completions', array('course' => $course2->id, 'userid' => $user1->id)));
        $this->assertEquals(1, $DB->count_records('course_completions', array('course' => $course3->id, 'userid' => $user1->id)));
        $this->assertEquals(1, $DB->count_records('course_completions', array('course' => $course1->id, 'userid' => $user2->id)));
        $this->assertEquals(1, $DB->count_records('course_completions', array('course' => $course2->id, 'userid' => $user2->id)));
        $this->assertEquals(1, $DB->count_records('course_completions', array('course' => $course3->id, 'userid' => $user2->id)));

        // Check the state of the course completion history records before we run the function.
        $this->assertEquals(0, $DB->count_records('course_completion_history', array('courseid' => $course1->id, 'userid' => $user1->id)));
        $this->assertEquals(0, $DB->count_records('course_completion_history', array('courseid' => $course2->id, 'userid' => $user1->id)));
        $this->assertEquals(0, $DB->count_records('course_completion_history', array('courseid' => $course3->id, 'userid' => $user1->id)));
        $this->assertEquals(0, $DB->count_records('course_completion_history', array('courseid' => $course1->id, 'userid' => $user2->id)));
        $this->assertEquals(0, $DB->count_records('course_completion_history', array('courseid' => $course2->id, 'userid' => $user2->id)));
        $this->assertEquals(0, $DB->count_records('course_completion_history', array('courseid' => $course3->id, 'userid' => $user2->id)));

        // Check the state of the message log records before we run the function.
        $this->assertEquals(6, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog', array('userid' => $user1->id, 'messageid' => $cert1messageprogcompleteid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog', array('userid' => $user1->id, 'messageid' => $cert1messagecsgcompleteid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog', array('userid' => $user2->id, 'messageid' => $cert1messageprogcompleteid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog', array('userid' => $user2->id, 'messageid' => $cert1messagecsgcompleteid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog', array('userid' => $user2->id, 'messageid' => $cert2messageprogcompleteid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog', array('userid' => $user2->id, 'messageid' => $cert2messagecsgcompleteid)));

        // Rather than setting up all the message types and triggering them, we're just going to create the logs manually.
        $DB->execute('DELETE FROM {prog_messagelog}');
        $allmessages = $DB->get_records('prog_message');
        $messagelog = new stdClass();
        $messagelog->timeissued = time();
        foreach ($allmessages as $message) {
            $messagelog->messageid = $message->id;
            if (in_array($message->messagetype, $coursesetmessagetypes)) {
                // Create one log for each path.
                $params = array('programid' => $message->programid, 'certifpath' => CERTIFPATH_CERT);
                $messagelog->coursesetid = $DB->get_field('prog_courseset', 'id', $params);
                $messagelog->userid = $user1->id;
                $DB->insert_record('prog_messagelog', $messagelog);
                $messagelog->userid = $user2->id;
                $DB->insert_record('prog_messagelog', $messagelog);
                $params = array('programid' => $message->programid, 'certifpath' => CERTIFPATH_RECERT);
                $messagelog->coursesetid = $DB->get_field('prog_courseset', 'id', $params);
            } else {
                $messagelog->coursesetid = 0;
            }
            $messagelog->userid = $user1->id;
            $DB->insert_record('prog_messagelog', $messagelog);
            $messagelog->userid = $user2->id;
            $DB->insert_record('prog_messagelog', $messagelog);
        }
        $this->assertEquals($messagetypetotal * 4, $DB->count_records('prog_messagelog'));
        $this->assertEquals($messagetypetotal * 2, $DB->count_records('prog_messagelog', array('userid' => $user1->id)));
        $this->assertEquals($messagetypetotal * 2, $DB->count_records('prog_messagelog', array('userid' => $user2->id)));

        // Run the function, catching messages.
        $sink = $this->redirectMessages();
        $this->assertTrue(certif_set_state_windowopen($cert1->id, $user2->id, 'Testing pass certif_set_state_windowopen'));
        $messages = $sink->get_messages();

        // Check only the expected history record has been created.
        // No need to check specifics here - copy_certif_completion_to_hist should have its own tests.
        $this->assertEquals(0, $DB->count_records('certif_completion_history', array('certifid' => $cert1->certifid, 'userid' => $user1->id)));
        $this->assertEquals(1, $DB->count_records('certif_completion_history', array('certifid' => $cert1->certifid, 'userid' => $user2->id))); // Created.
        $this->assertEquals(0, $DB->count_records('certif_completion_history', array('certifid' => $cert2->certifid, 'userid' => $user2->id)));

        // Check only the expected prog_completion record was affected.
        $where = "programid = :programid AND userid = :userid AND coursesetid = 0 AND status = " . STATUS_PROGRAM_COMPLETE;
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where, array('programid' => $cert1->id, 'userid' => $user1->id)));
        $this->assertEquals(0, $DB->count_records_select('prog_completion', $where, array('programid' => $cert1->id, 'userid' => $user2->id))); // Reset.
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where, array('programid' => $cert2->id, 'userid' => $user2->id)));
        $where = "programid = :programid AND userid = :userid AND coursesetid = 0 AND status = " . STATUS_PROGRAM_INCOMPLETE;
        $this->assertEquals(0, $DB->count_records_select('prog_completion', $where, array('programid' => $cert1->id, 'userid' => $user1->id)));
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where, array('programid' => $cert1->id, 'userid' => $user2->id))); // Reset.
        $this->assertEquals(0, $DB->count_records_select('prog_completion', $where, array('programid' => $cert2->id, 'userid' => $user2->id)));

        // Check that no non-zero course set group record were affected - only recert path course set groups should be reset by
        // window open. A new recert path record is created.
        $where = "programid = :programid AND userid = :userid AND coursesetid > 0 AND status = " . STATUS_COURSESET_COMPLETE;
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where, array('programid' => $cert1->id, 'userid' => $user1->id)));
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where, array('programid' => $cert1->id, 'userid' => $user2->id))); // Not reset!
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where, array('programid' => $cert2->id, 'userid' => $user2->id)));
        $where = "programid = :programid AND userid = :userid AND coursesetid > 0 AND status = " . STATUS_COURSESET_INCOMPLETE;
        $this->assertEquals(0, $DB->count_records_select('prog_completion', $where, array('programid' => $cert1->id, 'userid' => $user1->id)));
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where, array('programid' => $cert1->id, 'userid' => $user2->id))); // New record!
        $this->assertEquals(0, $DB->count_records_select('prog_completion', $where, array('programid' => $cert2->id, 'userid' => $user2->id)));

        // Check only the expected message logs were deleted. There are ten types that should be deleted. One new log is
        // created (window open). So there should be 9 gone.
        $this->assertEquals($messagetypetotal * 4 - 9, $DB->count_records('prog_messagelog'));
        $this->assertEquals($messagetypetotal * 2,     $DB->count_records('prog_messagelog', array('userid' => $user1->id)));
        $this->assertEquals($messagetypetotal * 2 - 9, $DB->count_records('prog_messagelog', array('userid' => $user2->id)));

        // Check that the six missing records are the ones we were expecting to be deleted.
        $sql = "SELECT pml.*
                  FROM {prog_messagelog} pml
                  JOIN {prog_message} pm
                    ON pml.messageid = pm.id
                 WHERE pm.programid = :programid
                   AND pml.userid = :userid";
        $params = array('programid' => $cert1->id, 'userid' => $user2->id);
        $this->assertCount(6, $DB->get_records_sql($sql, $params));
        $resettypes = array(
            MESSAGETYPE_PROGRAM_COMPLETED,
            MESSAGETYPE_PROGRAM_DUE,
            MESSAGETYPE_PROGRAM_OVERDUE,
            // Exclude course set message types, because they belong to the primary path.
            // Exclude MESSAGETYPE_RECERT_WINDOWOPEN because it should have been sent after reset.
            MESSAGETYPE_RECERT_WINDOWDUECLOSE,
            MESSAGETYPE_RECERT_FAILRECERT,
            MESSAGETYPE_LEARNER_FOLLOWUP,
        );
        list($insql, $inparams) = $DB->get_in_or_equal($resettypes, SQL_PARAMS_NAMED);
        $sql = $sql . ' AND pm.messagetype ' . $insql;
        $this->assertCount(0, $DB->get_records_sql($sql, array_merge($params, $inparams)));

        // Check only the expected course completion record was deleted - primary path course in cert1.
        $this->assertEquals(1, $DB->count_records('course_completions', array('course' => $course1->id, 'userid' => $user1->id)));
        $this->assertEquals(1, $DB->count_records('course_completions', array('course' => $course2->id, 'userid' => $user1->id)));
        $this->assertEquals(1, $DB->count_records('course_completions', array('course' => $course3->id, 'userid' => $user1->id)));
        $this->assertEquals(1, $DB->count_records('course_completions', array('course' => $course1->id, 'userid' => $user2->id)));
        $this->assertEquals(0, $DB->count_records('course_completions', array('course' => $course2->id, 'userid' => $user2->id))); // Gone.
        $this->assertEquals(1, $DB->count_records('course_completions', array('course' => $course3->id, 'userid' => $user2->id)));

        // Check only the expected course completion history records were created - primary path course in cert1.
        $this->assertEquals(0, $DB->count_records('course_completion_history', array('courseid' => $course1->id, 'userid' => $user1->id)));
        $this->assertEquals(0, $DB->count_records('course_completion_history', array('courseid' => $course2->id, 'userid' => $user1->id)));
        $this->assertEquals(0, $DB->count_records('course_completion_history', array('courseid' => $course3->id, 'userid' => $user1->id)));
        $this->assertEquals(0, $DB->count_records('course_completion_history', array('courseid' => $course1->id, 'userid' => $user2->id)));
        $this->assertEquals(1, $DB->count_records('course_completion_history', array('courseid' => $course2->id, 'userid' => $user2->id))); // Appeared.
        $this->assertEquals(0, $DB->count_records('course_completion_history', array('courseid' => $course3->id, 'userid' => $user2->id)));

        // Check only the expected prog_completion and certif_completion records were affected.
        list($user1cert1postcertcompletion, $user1cert1postprogcompletion) = certif_load_completion($cert1->id, $user1->id);
        $this->assertEquals($user1cert1precertcompletion, $user1cert1postcertcompletion);
        $this->assertEquals($user1cert1preprogcompletion, $user1cert1postprogcompletion);
        list($user2cert2postcertcompletion, $user2cert2postprogcompletion) = certif_load_completion($cert2->id, $user2->id);
        $this->assertEquals($user2cert2precertcompletion, $user2cert2postcertcompletion);
        $this->assertEquals($user2cert2preprogcompletion, $user2cert2postprogcompletion);

        // Check the completion records have been updated to window open.
        list($certcompletion, $progcompletion) = certif_load_completion($cert1->id, $user2->id);
        $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status);
        $this->assertEquals(0, $progcompletion->timecompleted);
        $this->assertEquals($user2cert1preprogcompletion->timedue, $progcompletion->timedue);
        $this->assertEquals(CERTIFSTATUS_COMPLETED, $certcompletion->status);
        $this->assertEquals(CERTIFPATH_RECERT, $certcompletion->certifpath);
        $this->assertEquals(CERTIFRENEWALSTATUS_DUE, $certcompletion->renewalstatus);
        $this->assertEquals($user2cert1precertcompletion->timecompleted, $certcompletion->timecompleted);
        $this->assertEquals($user2cert1precertcompletion->timewindowopens, $certcompletion->timewindowopens);
        $this->assertEquals($user2cert1precertcompletion->timeexpires, $certcompletion->timeexpires);

        // Check the log.
        $latestlog = $DB->get_records('prog_completion_log', array(), 'id DESC', '*', 0, 1);
        $latestlog = reset($latestlog);
        $this->assertEquals($cert1->id, $latestlog->programid);
        $this->assertEquals($user2->id, $latestlog->userid);
        $this->assertStringStartsWith('Testing pass certif_set_state_windowopen', $latestlog->description);

        // Check a program completion message has been sent. Note that this test isn't specific about the message content, it
        // just makes sure a message was sent.
        $this->assertCount(1, $messages);
        $message = reset($messages);
        $this->assertEquals($user2->id, $message->useridto);
        $this->assertStringEndsWith('totara/program/view.php?id=' . $cert1->id, $message->contexturl);

        // We'll do one additional check, that non-zero course set group completion records relating to the recertification
        // path are reset on window open.

        // Catch up the two control records.
        $this->assertTrue(certif_set_state_windowopen($cert1->id, $user1->id, 'Testing pass certif_set_state_windowopen'));
        $this->assertTrue(certif_set_state_windowopen($cert2->id, $user2->id, 'Testing pass certif_set_state_windowopen'));

        // Mark the users certified again. This time, recertification path non-zero course set records will be created.
        $completion_generator->complete_course($course2, $user1);
        $completion_generator->complete_course($course2, $user2);
        $completion_generator->complete_course($course3, $user2);

        // Check the record status before running the function.
        $where = "programid = :programid AND userid = :userid AND coursesetid > 0 AND status = " . STATUS_COURSESET_COMPLETE;
        $this->assertEquals(2, $DB->count_records_select('prog_completion', $where, array('programid' => $cert1->id, 'userid' => $user1->id)));
        $this->assertEquals(2, $DB->count_records_select('prog_completion', $where, array('programid' => $cert1->id, 'userid' => $user2->id)));
        $this->assertEquals(2, $DB->count_records_select('prog_completion', $where, array('programid' => $cert2->id, 'userid' => $user2->id)));
        $where = "programid = :programid AND userid = :userid AND coursesetid > 0 AND status = " . STATUS_COURSESET_INCOMPLETE;
        $this->assertEquals(0, $DB->count_records_select('prog_completion', $where, array('programid' => $cert1->id, 'userid' => $user1->id)));
        $this->assertEquals(0, $DB->count_records_select('prog_completion', $where, array('programid' => $cert1->id, 'userid' => $user2->id)));
        $this->assertEquals(0, $DB->count_records_select('prog_completion', $where, array('programid' => $cert2->id, 'userid' => $user2->id)));

        // Run the function.
        $this->assertTrue(certif_set_state_windowopen($cert1->id, $user2->id, 'Testing pass certif_set_state_windowopen'));

        // Check that only the correct record was reset.
        $where = "programid = :programid AND userid = :userid AND coursesetid > 0 AND status = " . STATUS_COURSESET_COMPLETE;
        $this->assertEquals(2, $DB->count_records_select('prog_completion', $where, array('programid' => $cert1->id, 'userid' => $user1->id)));
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where, array('programid' => $cert1->id, 'userid' => $user2->id))); // One reset!
        $this->assertEquals(2, $DB->count_records_select('prog_completion', $where, array('programid' => $cert2->id, 'userid' => $user2->id)));
        $where = "programid = :programid AND userid = :userid AND coursesetid > 0 AND status = " . STATUS_COURSESET_INCOMPLETE;
        $this->assertEquals(0, $DB->count_records_select('prog_completion', $where, array('programid' => $cert1->id, 'userid' => $user1->id)));
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where, array('programid' => $cert1->id, 'userid' => $user2->id))); // One reset!
        $this->assertEquals(0, $DB->count_records_select('prog_completion', $where, array('programid' => $cert2->id, 'userid' => $user2->id)));
    }

    /**
     * There used to be a bug where certif_set_state_expired() failed to reset completion of face-to-face activities.
     * This is a test for that particular fix, making sure the bug is not re-introduced.
     */
    public function test_certif_set_state_expired_resets_activities() {
        global $DB;

        $data_generator = self::getDataGenerator();
        $completion_generator = \core_completion\testing\generator::instance();
        $program_generator = \totara_program\testing\generator::instance();

        $user = $data_generator->create_user();
        $course = $data_generator->create_course();
        $course_recert = $data_generator->create_course();
        $completion_generator->enable_completion_tracking($course);

        $cert = $program_generator->create_certification();
        $program_generator->add_courses_and_courseset_to_program($cert, [[$course]], CERTIFPATH_CERT);
        $program_generator->add_courses_and_courseset_to_program($cert, [[$course_recert]], CERTIFPATH_RECERT);

        $program_generator->assign_to_program($cert->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);

        // Create face-to-face.
        $facetofacedata = [
            'name' => 'facetoface1',
            'course' => $course->id
        ];
        $f2fmoduleoptions = [
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionstatusrequired' => json_encode([fully_attended::get_code()])
        ];
        $facetoface_generator = $data_generator->get_plugin_generator('mod_facetoface');
        $facetoface = $facetoface_generator->create_instance($facetofacedata, $f2fmoduleoptions);

        // Create a f2f session. It will be moved to the past after signups.
        $session_date = new stdClass();
        $session_date->timestart = time() + DAYSECS;
        $session_date->timefinish = time() + DAYSECS + 60;
        $session_date->sessiontimezone = 'Pacific/Auckland';
        $session_data = [
            'facetoface' => $facetoface->id,
            'capacity' => 10,
            'allowoverbook' => 1,
            'sessiondates' => [$session_date],
            'mincapacity' => '4',
            'cutoff' => "86400"
        ];
        $session_id = $facetoface_generator->add_session($session_data);
        $seminarevent = new seminar_event($session_id);

        $completion_generator->set_activity_completion($course->id, [$facetoface], COMPLETION_AGGREGATION_ANY);

        $data_generator->enrol_user($user->id, $course->id);

        // Check user is not yet complete for course and certs.
        $course_info = new completion_info($course);
        $this->assertFalse($course_info->is_course_complete($user->id));

        // Attend the face-to-face.
        signup_helper::signup(signup::create($user->id, $seminarevent));

        // Move session time to the past.
        $sessiondate = new stdClass();
        $sessiondate->timestart = time() - DAYSECS;
        $sessiondate->timefinish = time() - DAYSECS + 60;
        $sessiondate->sessiontimezone = 'Pacific/Auckland';
        seminar_event_helper::merge_sessions($seminarevent, [$sessiondate]);

        $f2fsignups = $DB->get_records('facetoface_signups', ['sessionid' => $seminarevent->get_id()], '', 'userid, id');
        $attendancedata = [
            $f2fsignups[$user->id]->id => fully_attended::get_code(),
        ];
        signup_helper::process_attendance($seminarevent, $attendancedata);

        ob_start();
        $completion_task = new completion_regular_task();
        $completion_task->execute();
        ob_end_clean();

        // Check that the course is marked complete.
        $course_info = new completion_info($course);
        $this->assertTrue($course_info->is_course_complete($user->id));

        // Check that the module is marked complete.
        $f2fmodulecompletions =
            $DB->get_records('course_modules_completion', ['coursemoduleid' => $facetoface->cmid], '', 'userid, completionstate');
        $this->assertEquals(COMPLETION_COMPLETE, $f2fmodulecompletions[$user->id]->completionstate);

        // Make sure the signup record exists and is not archived.
        $signup_records = $DB->get_records('facetoface_signups', ['userid' => $user->id, 'sessionid' => $seminarevent->get_id()]);
        self::assertCount(1, $signup_records);
        $signup_record = reset($signup_records);
        self::assertEquals(0, $signup_record->archived);

        // Set the certification into the window open state for the user.
        $this->assertTrue(certif_set_state_windowopen($cert->id, $user->id));

        // Run the function, catching messages.
        $sink = self::redirectMessages();
        $this->assertTrue(certif_set_state_expired($cert->id, $user->id));
        $sink->close();

        $f2fmodulecompletions =
            $DB->get_records('course_modules_completion', ['coursemoduleid' => $facetoface->cmid], '', 'userid, completionstate');
        $this->assertFalse(isset($f2fmodulecompletions[$user->id]));

        // Verify the signup record has been archived.
        $signup_records = $DB->get_records('facetoface_signups', ['userid' => $user->id, 'sessionid' => $seminarevent->get_id()]);
        self::assertCount(1, $signup_records);
        $signup_record = reset($signup_records);
        self::assertEquals(1, $signup_record->archived);
    }

    public function test_certif_set_state_expired() {
        global $DB;

        $program_generator = \totara_program\testing\generator::instance();
        $completion_generator = \core_completion\testing\generator::instance();

        // Set up some stuff.
        $user1 = $this->getDataGenerator()->create_user(); // Control user.
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user(); // Control user.

        $cert1 = $program_generator->create_certification();
        $cert2 = $program_generator->create_certification(); // Control certification.

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course(); // Control course.

        $program_generator->add_courses_and_courseset_to_program($cert1, array(array($course1)), CERTIFPATH_CERT);
        $program_generator->add_courses_and_courseset_to_program($cert1, array(array($course2)), CERTIFPATH_RECERT);
        $program_generator->add_courses_and_courseset_to_program($cert2, array(array($course3)), CERTIFPATH_CERT);
        $program_generator->add_courses_and_courseset_to_program($cert2, array(array($course3)), CERTIFPATH_RECERT);

        $program_generator->assign_to_program($cert1->id, ASSIGNTYPE_INDIVIDUAL, $user1->id, null, true);
        $program_generator->assign_to_program($cert1->id, ASSIGNTYPE_INDIVIDUAL, $user2->id, null, true);
        $program_generator->assign_to_program($cert2->id, ASSIGNTYPE_INDIVIDUAL, $user2->id, null, true);
        $program_generator->assign_to_program($cert1->id, ASSIGNTYPE_INDIVIDUAL, $user3->id, null, true);

        $allmessagetypes = [
            MESSAGETYPE_ENROLMENT,
            MESSAGETYPE_UNENROLMENT,
            MESSAGETYPE_PROGRAM_DUE,
            MESSAGETYPE_PROGRAM_OVERDUE,
            MESSAGETYPE_PROGRAM_COMPLETED,
            MESSAGETYPE_COURSESET_DUE,
            MESSAGETYPE_COURSESET_OVERDUE,
            MESSAGETYPE_COURSESET_COMPLETED,
            MESSAGETYPE_LEARNER_FOLLOWUP,
            MESSAGETYPE_RECERT_WINDOWOPEN,
            MESSAGETYPE_RECERT_WINDOWDUECLOSE,
            MESSAGETYPE_RECERT_FAILRECERT,
        ];
        $coursesetmessagetypes = [
            MESSAGETYPE_COURSESET_DUE,
            MESSAGETYPE_COURSESET_OVERDUE,
            MESSAGETYPE_COURSESET_COMPLETED,
        ];
        $messagetypetotal = count($allmessagetypes) + count($coursesetmessagetypes);

        // Set up the program messages.
        $programmessagemanager = $cert1->get_messagesmanager();
        $programmessagemanager->delete();
        foreach ($allmessagetypes as $messagetype) {
            $programmessagemanager->add_message($messagetype);
        }
        $programmessagemanager->save_messages();
        prog_messages_manager::get_program_messages_manager($cert1->id, true); // Causes static cache to be reset.

        $programmessagemanager = $cert2->get_messagesmanager();
        $programmessagemanager->delete();
        foreach ($allmessagetypes as $messagetype) {
            $programmessagemanager->add_message($messagetype);
        }
        $programmessagemanager->save_messages();
        prog_messages_manager::get_program_messages_manager($cert2->id, true); // Causes static cache to be reset.

        // Before doing the positive test, check that the function will fail correctly.

        // We need to check that the function will fail if the user is in any state other than CERTIFCOMPLETIONSTATE_WINDOWOPEN.
        // First try from newly assigned.
        $this->assertFalse(certif_set_state_expired($cert1->id, $user3->id, 'Testing fail 1 certif_set_state_expired'));
        $latestlog = $DB->get_records('prog_completion_log', array(), 'id DESC', '*', 0, 1);
        $latestlog = reset($latestlog);
        $this->assertEquals($cert1->id, $latestlog->programid);
        $this->assertEquals($user3->id, $latestlog->userid);
        $this->assertStringStartsWith('Tried to set state to Expired, but current state is not Window open.', $latestlog->description);

        // Second try from certified (use the functions we're testing to change state, positive tests are elsewhere here).
        $DB->delete_records('prog_completion_log'); // To ensure we're not looking at the previous log.
        $this->assertTrue(certif_set_state_certified($cert1->id, $user3->id, 'Testing fail 2 certif_set_state_expired'));
        $this->assertFalse(certif_set_state_expired($cert1->id, $user3->id, 'Testing fail 2 certif_set_state_expired'));
        $latestlog = $DB->get_records('prog_completion_log', array(), 'id DESC', '*', 0, 1);
        $latestlog = reset($latestlog);
        $this->assertEquals($cert1->id, $latestlog->programid);
        $this->assertEquals($user3->id, $latestlog->userid);
        $this->assertStringStartsWith('Tried to set state to Expired, but current state is not Window open.', $latestlog->description);

        // Last from expired.
        $DB->delete_records('prog_completion_log'); // To ensure we're not looking at the previous log.
        $this->assertTrue(certif_set_state_windowopen($cert1->id, $user3->id, 'Testing fail 3 certif_set_state_expired'));
        $this->assertTrue(certif_set_state_expired($cert1->id, $user3->id, 'Testing fail 3 certif_set_state_expired'));
        $this->assertFalse(certif_set_state_expired($cert1->id, $user3->id, 'Testing fail 3 certif_set_state_expired'));
        $latestlog = $DB->get_records('prog_completion_log', array(), 'id DESC', '*', 0, 1);
        $latestlog = reset($latestlog);
        $this->assertEquals($cert1->id, $latestlog->programid);
        $this->assertEquals($user3->id, $latestlog->userid);
        $this->assertStringStartsWith('Tried to set state to Expired, but current state is not Window open.', $latestlog->description);

        // Also, test failure with some invalid data - timecompleted can only be > 0 if status is complete.
        $DB->set_field('prog_completion', 'timecompleted', 123, array('programid' => $cert1->id, 'userid' => $user3->id, 'coursesetid' => 0));
        $this->assertFalse(certif_set_state_expired($cert1->id, $user3->id, 'Testing fail 4 certif_set_state_certified'));
        $latestlog = $DB->get_records('prog_completion_log', array(), 'id DESC', '*', 0, 1);
        $latestlog = reset($latestlog);
        $this->assertEquals($cert1->id, $latestlog->programid);
        $this->assertEquals($user3->id, $latestlog->userid);
        $this->assertStringStartsWith('Tried to set state to Expired, but failed because current completion data is invalid.', $latestlog->description);

        // Start testing what happens when there are no problems.

        // Set the test data into the Window open state.
        $completion_generator->complete_course($course1, $user1);
        $completion_generator->complete_course($course2, $user1);
        $completion_generator->complete_course($course3, $user1);
        $completion_generator->complete_course($course1, $user2);
        $completion_generator->complete_course($course2, $user2);
        $completion_generator->complete_course($course3, $user2);
        $this->assertTrue(certif_set_state_windowopen($cert1->id, $user1->id));
        $this->assertTrue(certif_set_state_windowopen($cert1->id, $user2->id));
        $this->assertTrue(certif_set_state_windowopen($cert2->id, $user2->id));
        list($user1cert1precertcompletion, $user1cert1preprogcompletion) = certif_load_completion($cert1->id, $user1->id);
        list($user2cert1precertcompletion, $user2cert1preprogcompletion) = certif_load_completion($cert1->id, $user2->id);
        list($user2cert2precertcompletion, $user2cert2preprogcompletion) = certif_load_completion($cert2->id, $user2->id);

        // Rather than setting up all the message types and triggering them, we're just going to create the logs manually.
        $DB->execute('DELETE FROM {prog_messagelog}');
        $allmessages = $DB->get_records('prog_message');
        $messagelog = new stdClass();
        $messagelog->timeissued = time();
        foreach ($allmessages as $message) {
            if ($message->messagetype == MESSAGETYPE_RECERT_FAILRECERT) {
                // Don't create a log for the expiry message, or else it won't send an expiry message!
                continue;
            }
            $messagelog->messageid = $message->id;
            if (in_array($message->messagetype, $coursesetmessagetypes)) {
                // Create one log for each path.
                $params = array('programid' => $message->programid, 'certifpath' => CERTIFPATH_CERT);
                $messagelog->coursesetid = $DB->get_field('prog_courseset', 'id', $params);
                $messagelog->userid = $user1->id;
                $DB->insert_record('prog_messagelog', $messagelog);
                $messagelog->userid = $user2->id;
                $DB->insert_record('prog_messagelog', $messagelog);
                $params = array('programid' => $message->programid, 'certifpath' => CERTIFPATH_RECERT);
                $messagelog->coursesetid = $DB->get_field('prog_courseset', 'id', $params);
            } else {
                $messagelog->coursesetid = 0;
            }
            $messagelog->userid = $user1->id;
            $DB->insert_record('prog_messagelog', $messagelog);
            $messagelog->userid = $user2->id;
            $DB->insert_record('prog_messagelog', $messagelog);
        }
        $messagetypetotal -= 1; // We skipped one type of message - expiry.
        $this->assertEquals($messagetypetotal * 4, $DB->count_records('prog_messagelog'));
        $this->assertEquals($messagetypetotal * 2, $DB->count_records('prog_messagelog', array('userid' => $user1->id)));
        $this->assertEquals($messagetypetotal * 2, $DB->count_records('prog_messagelog', array('userid' => $user2->id)));

        // Check the state of the prog_completion records before we run the function.
        $where = "programid = :programid AND userid = :userid AND coursesetid = 0 AND status = " . STATUS_PROGRAM_COMPLETE;
        $this->assertEquals(0, $DB->count_records_select('prog_completion', $where, array('programid' => $cert1->id, 'userid' => $user1->id)));
        $this->assertEquals(0, $DB->count_records_select('prog_completion', $where, array('programid' => $cert1->id, 'userid' => $user2->id)));
        $this->assertEquals(0, $DB->count_records_select('prog_completion', $where, array('programid' => $cert2->id, 'userid' => $user2->id)));
        $where = "programid = :programid AND userid = :userid AND coursesetid = 0 AND status = " . STATUS_PROGRAM_INCOMPLETE;
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where, array('programid' => $cert1->id, 'userid' => $user1->id)));
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where, array('programid' => $cert1->id, 'userid' => $user2->id)));
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where, array('programid' => $cert2->id, 'userid' => $user2->id)));

        // Check the state of the non-zero course set group records before we run the function.
        $where = "programid = :programid AND userid = :userid AND coursesetid > 0 AND status = " . STATUS_COURSESET_COMPLETE;
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where, array('programid' => $cert1->id, 'userid' => $user1->id)));
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where, array('programid' => $cert1->id, 'userid' => $user2->id)));
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where, array('programid' => $cert2->id, 'userid' => $user2->id)));
        $where = "programid = :programid AND userid = :userid AND coursesetid > 0 AND status = " . STATUS_COURSESET_INCOMPLETE;
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where, array('programid' => $cert1->id, 'userid' => $user1->id)));
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where, array('programid' => $cert1->id, 'userid' => $user2->id)));
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where, array('programid' => $cert2->id, 'userid' => $user2->id)));

        // Check the state of the course completion records before we run the function.
        $this->assertEquals(1, $DB->count_records('course_completions', array('course' => $course1->id, 'userid' => $user1->id)));
        $this->assertEquals(0, $DB->count_records('course_completions', array('course' => $course2->id, 'userid' => $user1->id)));
        $this->assertEquals(1, $DB->count_records('course_completions', array('course' => $course3->id, 'userid' => $user1->id)));
        $this->assertEquals(1, $DB->count_records('course_completions', array('course' => $course1->id, 'userid' => $user2->id)));
        $this->assertEquals(0, $DB->count_records('course_completions', array('course' => $course2->id, 'userid' => $user2->id)));
        $this->assertEquals(0, $DB->count_records('course_completions', array('course' => $course3->id, 'userid' => $user2->id)));

        // Check the state of the course completion history records before we run the function.
        $this->assertEquals(0, $DB->count_records('course_completion_history', array('courseid' => $course1->id, 'userid' => $user1->id)));
        $this->assertEquals(1, $DB->count_records('course_completion_history', array('courseid' => $course2->id, 'userid' => $user1->id)));
        $this->assertEquals(0, $DB->count_records('course_completion_history', array('courseid' => $course3->id, 'userid' => $user1->id)));
        $this->assertEquals(0, $DB->count_records('course_completion_history', array('courseid' => $course1->id, 'userid' => $user2->id)));
        $this->assertEquals(1, $DB->count_records('course_completion_history', array('courseid' => $course2->id, 'userid' => $user2->id)));
        $this->assertEquals(1, $DB->count_records('course_completion_history', array('courseid' => $course3->id, 'userid' => $user2->id)));

        // We want to be sure that recert path courses are not reset during expiration, so move the history records back into current.
        $DB->execute("INSERT INTO {course_completions} (userid, course, timeenrolled, timestarted, reaggregate, status)
                           SELECT userid, courseid, 0, 0, 0, " . COMPLETION_STATUS_COMPLETE . "
                             FROM {course_completion_history}");
        $DB->delete_records('course_completion_history');

        // Re-check the state of the course completion records before we run the function.
        $this->assertEquals(1, $DB->count_records('course_completions', array('course' => $course1->id, 'userid' => $user1->id)));
        $this->assertEquals(1, $DB->count_records('course_completions', array('course' => $course2->id, 'userid' => $user1->id)));
        $this->assertEquals(1, $DB->count_records('course_completions', array('course' => $course3->id, 'userid' => $user1->id)));
        $this->assertEquals(1, $DB->count_records('course_completions', array('course' => $course1->id, 'userid' => $user2->id)));
        $this->assertEquals(1, $DB->count_records('course_completions', array('course' => $course2->id, 'userid' => $user2->id)));
        $this->assertEquals(1, $DB->count_records('course_completions', array('course' => $course3->id, 'userid' => $user2->id)));

        // Re-check the state of the course completion history records before we run the function.
        $this->assertEquals(0, $DB->count_records('course_completion_history', array('courseid' => $course1->id, 'userid' => $user1->id)));
        $this->assertEquals(0, $DB->count_records('course_completion_history', array('courseid' => $course2->id, 'userid' => $user1->id)));
        $this->assertEquals(0, $DB->count_records('course_completion_history', array('courseid' => $course3->id, 'userid' => $user1->id)));
        $this->assertEquals(0, $DB->count_records('course_completion_history', array('courseid' => $course1->id, 'userid' => $user2->id)));
        $this->assertEquals(0, $DB->count_records('course_completion_history', array('courseid' => $course2->id, 'userid' => $user2->id)));
        $this->assertEquals(0, $DB->count_records('course_completion_history', array('courseid' => $course3->id, 'userid' => $user2->id)));

        // Run the function, catching messages.
        $sink = $this->redirectMessages();
        $this->assertTrue(certif_set_state_expired($cert1->id, $user2->id, 'Testing pass certif_set_state_expired'));
        $messages = $sink->get_messages();

        // Check only the expected prog_completion and certif_completion records were affected.
        list($user1cert1postcertcompletion, $user1cert1postprogcompletion) = certif_load_completion($cert1->id, $user1->id);
        $this->assertEquals($user1cert1precertcompletion, $user1cert1postcertcompletion);
        $this->assertEquals($user1cert1preprogcompletion, $user1cert1postprogcompletion);
        list($user2cert2postcertcompletion, $user2cert2postprogcompletion) = certif_load_completion($cert2->id, $user2->id);
        $this->assertEquals($user2cert2precertcompletion, $user2cert2postcertcompletion);
        $this->assertEquals($user2cert2preprogcompletion, $user2cert2postprogcompletion);

        // Check the completion records have been updated to expired.
        list($certcompletion, $progcompletion) = certif_load_completion($cert1->id, $user2->id);
        $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status);
        $this->assertEquals(0, $progcompletion->timecompleted);
        $this->assertEquals($user2cert1preprogcompletion->timedue, $progcompletion->timedue);
        $this->assertEquals(CERTIFSTATUS_EXPIRED, $certcompletion->status);
        $this->assertEquals(CERTIFPATH_CERT, $certcompletion->certifpath);
        $this->assertEquals(CERTIFRENEWALSTATUS_EXPIRED, $certcompletion->renewalstatus);
        $this->assertEquals(0, $certcompletion->timecompleted);
        $this->assertEquals(0, $certcompletion->timewindowopens);
        $this->assertEquals(0, $certcompletion->timeexpires);

        // Check the log.
        $latestlog = $DB->get_records('prog_completion_log', array(), 'id DESC', '*', 0, 1);
        $latestlog = reset($latestlog);
        $this->assertEquals($cert1->id, $latestlog->programid);
        $this->assertEquals($user2->id, $latestlog->userid);
        $this->assertStringStartsWith('Testing pass certif_set_state_expired', $latestlog->description);

        // Check only the expected message logs were deleted. There are three types that should be deleted. One message was
        // created, failed recert. So 2 total.
        $this->assertEquals($messagetypetotal * 4 - 2, $DB->count_records('prog_messagelog'));
        $this->assertEquals($messagetypetotal * 2,     $DB->count_records('prog_messagelog', array('userid' => $user1->id)));
        $this->assertEquals($messagetypetotal * 2 - 2, $DB->count_records('prog_messagelog', array('userid' => $user2->id)));

        // Check a program completion message has been sent. Note that this test isn't specific about the message content, it
        // just makes sure a message was sent.
        $this->assertCount(1, $messages);
        $message = reset($messages);
        $this->assertEquals($user2->id, $message->useridto);
        $this->assertStringEndsWith('totara/program/view.php?id=' . $cert1->id, $message->contexturl);

        // Check the state of the prog_completion records after we run the function.
        $where = "programid = :programid AND userid = :userid AND coursesetid = 0 AND status = " . STATUS_PROGRAM_COMPLETE;
        $this->assertEquals(0, $DB->count_records_select('prog_completion', $where, array('programid' => $cert1->id, 'userid' => $user1->id)));
        $this->assertEquals(0, $DB->count_records_select('prog_completion', $where, array('programid' => $cert1->id, 'userid' => $user2->id)));
        $this->assertEquals(0, $DB->count_records_select('prog_completion', $where, array('programid' => $cert2->id, 'userid' => $user2->id)));
        $where = "programid = :programid AND userid = :userid AND coursesetid = 0 AND status = " . STATUS_PROGRAM_INCOMPLETE;
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where, array('programid' => $cert1->id, 'userid' => $user1->id)));
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where, array('programid' => $cert1->id, 'userid' => $user2->id)));
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where, array('programid' => $cert2->id, 'userid' => $user2->id)));

        // Check the state of the non-zero course set group records after we run the function.
        $where = "programid = :programid AND userid = :userid AND coursesetid > 0 AND status = " . STATUS_COURSESET_COMPLETE;
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where, array('programid' => $cert1->id, 'userid' => $user1->id)));
        $this->assertEquals(0, $DB->count_records_select('prog_completion', $where, array('programid' => $cert1->id, 'userid' => $user2->id)));
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where, array('programid' => $cert2->id, 'userid' => $user2->id)));
        $where = "programid = :programid AND userid = :userid AND coursesetid > 0 AND status = " . STATUS_COURSESET_INCOMPLETE;
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where, array('programid' => $cert1->id, 'userid' => $user1->id)));
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where, array('programid' => $cert1->id, 'userid' => $user2->id)));
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where, array('programid' => $cert2->id, 'userid' => $user2->id)));

        // Check the state of the course completion records after we run the function.
        $this->assertEquals(1, $DB->count_records('course_completions', array('course' => $course1->id, 'userid' => $user1->id)));
        $this->assertEquals(1, $DB->count_records('course_completions', array('course' => $course2->id, 'userid' => $user1->id)));
        $this->assertEquals(1, $DB->count_records('course_completions', array('course' => $course3->id, 'userid' => $user1->id)));
        $this->assertEquals(0, $DB->count_records('course_completions', array('course' => $course1->id, 'userid' => $user2->id)));
        $this->assertEquals(1, $DB->count_records('course_completions', array('course' => $course2->id, 'userid' => $user2->id)));
        $this->assertEquals(1, $DB->count_records('course_completions', array('course' => $course3->id, 'userid' => $user2->id)));

        // Check the state of the course completion history records after we run the function.
        $this->assertEquals(0, $DB->count_records('course_completion_history', array('courseid' => $course1->id, 'userid' => $user1->id)));
        $this->assertEquals(0, $DB->count_records('course_completion_history', array('courseid' => $course2->id, 'userid' => $user1->id)));
        $this->assertEquals(0, $DB->count_records('course_completion_history', array('courseid' => $course3->id, 'userid' => $user1->id)));
        $this->assertEquals(1, $DB->count_records('course_completion_history', array('courseid' => $course1->id, 'userid' => $user2->id)));
        $this->assertEquals(0, $DB->count_records('course_completion_history', array('courseid' => $course2->id, 'userid' => $user2->id)));
        $this->assertEquals(0, $DB->count_records('course_completion_history', array('courseid' => $course3->id, 'userid' => $user2->id)));
    }

    public function test_certif_set_in_progress() {
        global $DB;

        $program_generator = \totara_program\testing\generator::instance();
        $completion_generator = \core_completion\testing\generator::instance();

        // Set up some stuff.
        $user1 = $this->getDataGenerator()->create_user(); // Control user.
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user(); // Control user.

        $cert1 = $program_generator->create_certification();
        $cert2 = $program_generator->create_certification(); // Control certification.

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course(); // Control course.

        $program_generator->add_courses_and_courseset_to_program($cert1, array(array($course1)), CERTIFPATH_CERT);
        $program_generator->add_courses_and_courseset_to_program($cert1, array(array($course2)), CERTIFPATH_RECERT);
        $program_generator->add_courses_and_courseset_to_program($cert2, array(array($course3)), CERTIFPATH_CERT);

        $program_generator->assign_to_program($cert1->id, ASSIGNTYPE_INDIVIDUAL, $user1->id, null, true);
        $program_generator->assign_to_program($cert1->id, ASSIGNTYPE_INDIVIDUAL, $user2->id, null, true);
        $program_generator->assign_to_program($cert2->id, ASSIGNTYPE_INDIVIDUAL, $user2->id, null, true);
        $program_generator->assign_to_program($cert1->id, ASSIGNTYPE_INDIVIDUAL, $user3->id, null, true);

        // Collect the control data.
        list($user1cert1precertcompletion, $user1cert1preprogcompletion) = certif_load_completion($cert1->id, $user1->id);
        list($user2cert2precertcompletion, $user2cert2preprogcompletion) = certif_load_completion($cert2->id, $user2->id);

        // Try setting In progress while Assigned.
        list($user2cert1precertcompletion, $user2cert1preprogcompletion) = certif_load_completion($cert1->id, $user2->id);
        $this->assertEquals(CERTIFCOMPLETIONSTATE_ASSIGNED, certif_get_completion_state($user2cert1precertcompletion));
        $DB->delete_records('prog_completion_log');
        $this->assertTrue(certif_set_in_progress($cert1->id, $user2->id, 'Testing pass 1 certif_set_in_progress'));
        $latestlog = $DB->get_records('prog_completion_log', array(), 'id DESC', '*', 0, 1);
        $latestlog = reset($latestlog);
        $this->assertEquals($cert1->id, $latestlog->programid);
        $this->assertEquals($user2->id, $latestlog->userid);
        $this->assertStringStartsWith('Testing pass 1 certif_set_in_progress', $latestlog->description);
        list($user2cert1postcertcompletion, $user2cert1postprogcompletion) = certif_load_completion($cert1->id, $user2->id);
        $this->assertEquals(CERTIFCOMPLETIONSTATE_ASSIGNED, certif_get_completion_state($user2cert1postcertcompletion));
        $user2cert1precertcompletion->status = CERTIFSTATUS_INPROGRESS; // The only change.
        $this->assertEquals($user2cert1precertcompletion, $user2cert1postcertcompletion);
        $this->assertEquals($user2cert1preprogcompletion, $user2cert1postprogcompletion);

        // Try setting In progress while Certified - will return false and logs an problem, but it otherwise doesn't hurt.
        certif_set_state_certified($cert1->id, $user2->id);
        list($user2cert1precertcompletion, $user2cert1preprogcompletion) = certif_load_completion($cert1->id, $user2->id);
        $this->assertEquals(CERTIFCOMPLETIONSTATE_CERTIFIED, certif_get_completion_state($user2cert1precertcompletion));
        $DB->delete_records('prog_completion_log');
        $this->assertFalse(certif_set_in_progress($cert1->id, $user2->id, 'Testing fail 1 certif_set_in_progress')); // Note false!
        $latestlog = $DB->get_records('prog_completion_log', array(), 'id DESC', '*', 0, 1);
        $latestlog = reset($latestlog);
        $this->assertEquals($cert1->id, $latestlog->programid);
        $this->assertEquals($user2->id, $latestlog->userid);
        $this->assertStringStartsWith('Tried to set In progress, but current state is not Assigned, Window open or Expired.', $latestlog->description);
        list($user2cert1postcertcompletion, $user2cert1postprogcompletion) = certif_load_completion($cert1->id, $user2->id);
        $this->assertEquals(CERTIFCOMPLETIONSTATE_CERTIFIED, certif_get_completion_state($user2cert1postcertcompletion));
        // No data changed between pre and post.
        $this->assertEquals($user2cert1precertcompletion, $user2cert1postcertcompletion);
        $this->assertEquals($user2cert1preprogcompletion, $user2cert1postprogcompletion);

        // Try setting In progress while Window open.
        certif_set_state_windowopen($cert1->id, $user2->id);
        list($user2cert1precertcompletion, $user2cert1preprogcompletion) = certif_load_completion($cert1->id, $user2->id);
        $this->assertEquals(CERTIFCOMPLETIONSTATE_WINDOWOPEN, certif_get_completion_state($user2cert1precertcompletion));
        $DB->delete_records('prog_completion_log');
        $this->assertTrue(certif_set_in_progress($cert1->id, $user2->id, 'Testing pass 2 certif_set_in_progress'));
        $latestlog = $DB->get_records('prog_completion_log', array(), 'id DESC', '*', 0, 1);
        $latestlog = reset($latestlog);
        $this->assertEquals($cert1->id, $latestlog->programid);
        $this->assertEquals($user2->id, $latestlog->userid);
        $this->assertStringStartsWith('Testing pass 2 certif_set_in_progress', $latestlog->description);
        list($user2cert1postcertcompletion, $user2cert1postprogcompletion) = certif_load_completion($cert1->id, $user2->id);
        $this->assertEquals(CERTIFCOMPLETIONSTATE_WINDOWOPEN, certif_get_completion_state($user2cert1postcertcompletion));
        $user2cert1precertcompletion->status = CERTIFSTATUS_INPROGRESS; // The only change.
        $this->assertEquals($user2cert1precertcompletion, $user2cert1postcertcompletion);
        $this->assertEquals($user2cert1preprogcompletion, $user2cert1postprogcompletion);

        // Try setting In progress while Expired.
        certif_set_state_expired($cert1->id, $user2->id);
        list($user2cert1precertcompletion, $user2cert1preprogcompletion) = certif_load_completion($cert1->id, $user2->id);
        $this->assertEquals(CERTIFCOMPLETIONSTATE_EXPIRED, certif_get_completion_state($user2cert1precertcompletion));
        $DB->delete_records('prog_completion_log');
        $this->assertTrue(certif_set_in_progress($cert1->id, $user2->id, 'Testing pass 3 certif_set_in_progress'));
        $latestlog = $DB->get_records('prog_completion_log', array(), 'id DESC', '*', 0, 1);
        $latestlog = reset($latestlog);
        $this->assertEquals($cert1->id, $latestlog->programid);
        $this->assertEquals($user2->id, $latestlog->userid);
        $this->assertStringStartsWith('Testing pass 3 certif_set_in_progress', $latestlog->description);
        list($user2cert1postcertcompletion, $user2cert1postprogcompletion) = certif_load_completion($cert1->id, $user2->id);
        $this->assertEquals(CERTIFCOMPLETIONSTATE_EXPIRED, certif_get_completion_state($user2cert1postcertcompletion));
        $user2cert1precertcompletion->status = CERTIFSTATUS_INPROGRESS; // The only change.
        $this->assertEquals($user2cert1precertcompletion, $user2cert1postcertcompletion);
        $this->assertEquals($user2cert1preprogcompletion, $user2cert1postprogcompletion);

        // Try setting In progress when it is already In progress - returns true but does nothing.
        list($user2cert1precertcompletion, $user2cert1preprogcompletion) = certif_load_completion($cert1->id, $user2->id);
        $user2cert1precertcompletion->status = CERTIFSTATUS_INPROGRESS;
        $this->assertEquals(CERTIFCOMPLETIONSTATE_EXPIRED, certif_get_completion_state($user2cert1precertcompletion));
        $DB->delete_records('prog_completion_log');
        $this->assertTrue(certif_set_in_progress($cert1->id, $user2->id, 'Testing skip certif_set_in_progress')); // Returns true!
        $this->assertEquals(0, $DB->count_records('prog_completion_log')); // No new log.
        list($user2cert1postcertcompletion, $user2cert1postprogcompletion) = certif_load_completion($cert1->id, $user2->id);
        $this->assertEquals(CERTIFCOMPLETIONSTATE_EXPIRED, certif_get_completion_state($user2cert1postcertcompletion));
        // No data changed between pre and post.
        $this->assertEquals($user2cert1precertcompletion, $user2cert1postcertcompletion);
        $this->assertEquals($user2cert1preprogcompletion, $user2cert1postprogcompletion);

        // Check that the control records haven't had any changes.
        list($user1cert1postcertcompletion, $user1cert1postprogcompletion) = certif_load_completion($cert1->id, $user1->id);
        $this->assertEquals($user1cert1precertcompletion, $user1cert1postcertcompletion);
        $this->assertEquals($user1cert1preprogcompletion, $user1cert1postprogcompletion);
        list($user2cert2postcertcompletion, $user2cert2postprogcompletion) = certif_load_completion($cert2->id, $user2->id);
        $this->assertEquals($user2cert2precertcompletion, $user2cert2postcertcompletion);
        $this->assertEquals($user2cert2preprogcompletion, $user2cert2postprogcompletion);

        // Lastly, test failure with some invalid data - timecompleted can only be > 0 if status is complete.
        $DB->set_field('prog_completion', 'timecompleted', 123, array('programid' => $cert1->id, 'userid' => $user3->id, 'coursesetid' => 0));
        $this->assertFalse(certif_set_in_progress($cert1->id, $user3->id, 'Testing fail 2 certif_set_state_certified'));
        $latestlog = $DB->get_records('prog_completion_log', array(), 'id DESC', '*', 0, 1);
        $latestlog = reset($latestlog);
        $this->assertEquals($cert1->id, $latestlog->programid);
        $this->assertEquals($user3->id, $latestlog->userid);
        $this->assertStringStartsWith('Tried to set In progress, but failed because current completion data is invalid.', $latestlog->description);
    }
}
