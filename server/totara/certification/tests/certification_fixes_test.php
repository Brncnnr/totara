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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara_certification
 */

defined('MOODLE_INTERNAL') || die();

use totara_core\advanced_feature;

global $CFG;
require_once($CFG->dirroot . '/totara/certification/lib.php');

class totara_certification_certification_fixes_testcase extends advanced_testcase {

    private $numtestusers = 5;
    private $numtestcerts = 7;
    private $numtestprogs = 8;

    public function tearDown(): void {
        $this->numtestusers = null;
        $this->numtestcerts = null;
        $this->numtestprogs = null;
    }

    /**
     * Set up users, programs, certifications and assignments.
     */
    private function setup_completions() {
        $data = new \stdClass();

        // Turn off programs. This is to test that it doesn't interfere with certification completion.
        advanced_feature::disable('programs');

        $programgenerator = $this->getDataGenerator()->get_plugin_generator('totara_program');

        // Create users.
        for ($i = 1; $i <= $this->numtestusers; $i++) {
            $data->users[$i] = $this->getDataGenerator()->create_user();
        }

        // Create programs, mostly so that we don't end up with coincidental success due to matching ids.
        for ($i = 1; $i <= $this->numtestprogs; $i++) {
            $data->programs[$i] = $programgenerator->create_program();
        }

        // Create certifications.
        for ($i = 1; $i <= $this->numtestcerts; $i++) {
            $data->certifications[$i] = $programgenerator->create_certification();
        }

        // Assign users to the programs as individuals.
        foreach ($data->users as $user) {
            foreach ($data->programs as $prog) {
                $programgenerator->assign_to_program($prog->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);
            }
        }

        // Assign users to the certifications as individuals.
        foreach ($data->users as $user) {
            foreach ($data->certifications as $prog) {
                $programgenerator->assign_to_program($prog->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);
            }
        }

        return $data;
    }

    /**
     * Sets a certification to be in a certified state
     * Note: the certification must be assigned to a user before this function
     * is run.
     *
     * @param int $certificationid
     * @param int $userid
     * @param int completiontime
     *
     * @return array
     */
    private function set_certified_state(int $certificationid, int $userid, $completetime) {
        global $DB;

        $windowtime = $completetime + (4 * (4 * WEEKSECS));
        $expiretime = $completetime + (6 * (4 * WEEKSECS));

        // Complete the courses in the certification path.
        $certification = new \program($certificationid);
        $coursesets = $certification->content->get_course_sets_path(CERTIFPATH_CERT);
        $courses = [];
        foreach ($coursesets as $set) {
            $set_courses = $set->get_courses();
            $courses = array_merge($courses, $set_courses);
        }

        foreach ($courses as $course) {
            $completion = new completion_completion(array('userid' => $userid, 'course' => $course->id));
            $completion->mark_complete($completetime);
        }

        // Get the completion record for user 1 and update the times.
        $certcomprec = $DB->get_record('certif_completion', ['userid' => $userid, 'certifid' => $certification->certifid]);
        $certcomprec->timecompleted = $completetime;
        $certcomprec->timewindowopens = $windowtime;
        $certcomprec->timeexpires = $expiretime;
        $certcomprec->baselinetimeexpires = $expiretime;
        $certcomprec->status = CERTIFSTATUS_COMPLETED;
        $certcomprec->certifpath = CERTIFPATH_RECERT;
        $DB->update_record('certif_completion', $certcomprec);

        // Update program completion too
        $progcomprec = $DB->get_record('prog_completion', ['userid' => $userid, 'programid' => $certification->id, 'coursesetid' => 0]);
        $progcomprec->timecompleted = $completetime;
        $progcomprec->timedue = $expiretime;
        $progcomprec->status = STATUS_PROGRAM_COMPLETE;
        $DB->update_record('prog_completion', $progcomprec);

        return ['complete' => $completetime,
                'window' => $windowtime,
                'expire' => $expiretime];
    }


    /**
     * Testing function that copies certcompl->timeexpires -> progcompl.timedue
     * Can only be applied to programs in certified state
     *
     */
    public function test_certif_fix_mismatched_expiry_duedate() {
        global $DB;

        $data = $this->setup_completions();
        $userid = reset($data->users)->id;

        // Complete certifications for a users
        $sql = 'SELECT cc.*, p.id AS programid
                  FROM {certif_completion} cc
                  JOIN {prog} p ON p.certifid = cc.certifid AND cc.userid = :userid';
        $params = ['userid' => $userid];
        $records = $DB->get_records_sql($sql, $params);

        foreach ($records as $record) {
            $brokentime = time() - (4 * WEEKSECS);
            $this->set_certified_state($record->programid, $userid, $brokentime);
        }

        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);

        // Modify records to cause them to be broken, as if a new assignment had bumped them forwards.
        $sql = 'UPDATE {prog_completion}
                   SET timedue = timedue + 1000
                 WHERE userid = :userid
                   AND programid IN (SELECT id FROM {prog} WHERE certifid <> 0)';
        $params = ['userid' => $userid];
        $DB->execute($sql, $params);

        $original_prog_completions = $DB->get_records_sql(
            'SELECT pc.*, p.certifid
                   FROM {prog_completion} pc
                   JOIN {prog} p ON p.id = pc.programid
                  WHERE userid = :userid', ['userid' => $userid]);
        $original_certif_completions = $DB->get_records('certif_completion', ['userid' => $userid]);

        // Get list of errors
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount(7, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);

        $certificationid = reset($data->certifications)->id;
        certif_fix_completions('fixcertmismatchedexpiryduedatefromassignment', $certificationid, $userid);

        // Six issues remaining as we fixed one
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount(6, $fulllist);

        // Fix the rest of them
        certif_fix_completions('fixcertmismatchedexpiryduedatefromassignment', 0, $userid);

        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);

        // Get new records and ensure they are fixed correctly.
        $fixed_prog_completions = $DB->get_records_sql(
            'SELECT pc.*, p.certifid
                   FROM {prog_completion} pc
                   JOIN {prog} p ON p.id = pc.programid
                  WHERE userid = :userid', ['userid' => $userid]);
        $fixed_certif_completions = $DB->get_records('certif_completion', ['userid' => $userid]);

        // The program timedue should have been moved backwards by 1000, so that it matches the timeexpires.
        foreach ($original_prog_completions as $original_prog_completion) {
            // The fix only applies to certs.
            if ($original_prog_completion->certifid != 0) {
                $original_prog_completion->timedue -= 1000;
            }
        }

        $this->assertEquals($original_prog_completions, $fixed_prog_completions);
        $this->assertEquals($original_certif_completions, $fixed_certif_completions);
    }

    /**
     * Testing function that copies certcompl.expiry -> progcompl.timedue
     *
     */
    public function test_certif_fix_completion_expiry_to_due_date() {
        global $DB;

        $data = $this->setup_completions();
        $userid = reset($data->users)->id;

        // Complete certifications for a users
        $sql = 'SELECT cc.*, p.id as programid FROM {certif_completion} cc JOIN {prog} p ON p.certifid = cc.certifid AND cc.userid = :userid';
        $params = ['userid' => $userid];
        $records = $DB->get_records_sql($sql, $params);

        foreach ($records as $record) {
            $brokentime = time() - (4 * WEEKSECS);
            $this->set_certified_state($record->programid, $userid, $brokentime);
        }

        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);
        list($fulllist, $aggregatelist, $totalcount) = prog_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);

        // Modify records to cause them to be broken
        //$sql = 'UPDATE {certif_completion} SET timeexpires = timeexpires - 1000 WHERE userid = :userid';
        $sql = 'UPDATE {prog_completion} SET timedue = timedue + 10000 WHERE userid = :userid';
        $params = ['userid' => $userid];
        $DB->execute($sql, $params);

        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount(7, $fulllist);

        $certificationid = reset($data->certifications)->id;
        certif_fix_completions('fixcertcertifiedduedatedifferentmismatchexpiry', $certificationid, $userid);

        // Six issues remaining as we fixed one
        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount(6, $fulllist);

        // Fix remaining completions
        certif_fix_completions('fixcertcertifiedduedatedifferentmismatchexpiry', 0, $userid);

        list($fulllist, $aggregatelist, $totalcount) = certif_get_all_completions_with_errors();
        $this->assertCount(0, $fulllist);
    }

    public function test_certif_fix_completion_copy_from_history() {
        global $DB;

        $target_problem_key = 'error:stateassigned-progstatusincorrect|error:stateassigned-progtimecompletednotempty';

        $this->numtestprogs = 2;
        $this->numtestcerts = 2;
        $this->numtestusers = 2;
        $data = $this->setup_completions();

        // Set up test and control completion data.
        foreach ($data->certifications as $certification) {
            foreach ($data->users as $user) {
                list($certif_completion, $prog_completion) = certif_load_completion($certification->id, $user->id);

                // Mark the program completion complete, while leaving the certif_completion newly assigned.
                $prog_completion->status = STATUS_PROGRAM_COMPLETE;
                $prog_completion->timecompleted = 123;
                $prog_completion->timedue = 345;
                certif_write_completion(
                    $certif_completion,
                    $prog_completion,
                    'Test put data into invalid state',
                    $target_problem_key
                );

                // Create an unassigned history certif completion which is certified.
                unset($certif_completion->id);
                $certif_completion->certifpath = CERTIFPATH_RECERT;
                $certif_completion->status = CERTIFSTATUS_COMPLETED;
                $certif_completion->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
                $certif_completion->timecompleted = 123;
                $certif_completion->timewindowopens = 234;
                $certif_completion->timeexpires = 345;
                $certif_completion->baselinetimeexpires = 456;
                $certif_completion->unassigned = 1;
                certif_write_completion_history(
                    $certif_completion,
                    'Test create history',
                    'error:invalidunassignedhist'
                );
            }
        }

        $target_user = reset($data->users);
        $target_cert = reset($data->certifications);

        $before_certif_completions = $DB->get_records('certif_completion', [], 'id');
        $before_prog_completions = $DB->get_records('prog_completion', [], 'id');

        certif_fix_completions('fixrestorefromhistory', $target_cert->id, $target_user->id);

        // Test that only the expected record was repaired.
        foreach ($data->certifications as $certification) {
            foreach ($data->users as $user) {
                list($certif_completion, $prog_completion) = certif_load_completion($certification->id, $user->id);

                $errors = certif_get_completion_errors($certif_completion, $prog_completion);

                if ($certification->id == $target_cert->id && $user->id == $target_user->id) {
                    $this->assertEmpty($errors);
                    $this->assertEquals(CERTIFPATH_RECERT, $certif_completion->certifpath);
                    $this->assertEquals(CERTIFSTATUS_COMPLETED, $certif_completion->status);
                    $this->assertEquals(CERTIFRENEWALSTATUS_NOTDUE, $certif_completion->renewalstatus);
                    $this->assertEquals(123, $certif_completion->timecompleted);
                    $this->assertEquals(234, $certif_completion->timewindowopens);
                    $this->assertEquals(345, $certif_completion->timeexpires);
                    $this->assertEquals(456, $certif_completion->baselinetimeexpires);
                    $this->assertEquals(STATUS_PROGRAM_COMPLETE, $prog_completion->status);
                    $this->assertEquals(123, $prog_completion->timecompleted);
                    $this->assertEquals(345, $prog_completion->timedue);
                } else {
                    $problemkey = certif_get_completion_error_problemkey($errors);
                    $this->assertEquals($target_problem_key, $problemkey);
                    $this->assertEquals($before_certif_completions[$certif_completion->id], $certif_completion);
                    $this->assertEquals($before_prog_completions[$prog_completion->id], $prog_completion);
                }
            }
        }

        // Check that only the expected history record was deleted.
        $certif_completion_histories = $DB->get_records('certif_completion_history', [], 'certifid, userid');
        $this->assertCount(3, $certif_completion_histories);

        $certif_completion_histories = $DB->get_records(
            'certif_completion_history',
            ['certifid' => $target_cert->id, 'userid' => $target_user->id],
            'certifid, userid'
        );
        $this->assertCount(0, $certif_completion_histories);
    }
}
