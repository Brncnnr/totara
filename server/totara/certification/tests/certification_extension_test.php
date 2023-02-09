<?php
/*
 * This file is part of Totara Learn
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
 * @author Carl Anderson <carl.anderson@totaralearning.com>
 * @package totara_certification
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/program/lib.php');

/**
 * Lib test for prog_process_extension to ensure correct behaviour of extensions
 */
class totara_certification_extension_testcase extends advanced_testcase {

    public function test_can_request_extension() {
        global $CFG, $DB;

        $CFG->enablecompletion = true;

        $initdate = time();

        $generator = self::getDataGenerator();
        $program_generator = $generator->get_plugin_generator('totara_program');
        $job_generator = $generator->get_plugin_generator('totara_job');

        $manager = $generator->create_user();

        // Create a user with manager.
        [$user, $job] = $job_generator->create_user_and_job([], $manager->id);

        $certificationid = $program_generator->create_certification();
        $certification = new program($certificationid);

        $course1 = $generator->create_course();
        $course2 = $generator->create_course();

        // Add the courses to the certification.
        $program_generator->add_courses_and_courseset_to_program($certification, [[$course1]], CERTIFPATH_CERT);
        $program_generator->add_courses_and_courseset_to_program($certification, [[$course2]], CERTIFPATH_RECERT);

        // Assign the user to the cert as an individual.
        $program_generator->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);

        list($certcompletion, $progcompletion) = certif_load_completion($certification->id, $user->id);
        // Check the existing data.
        self::assertEquals(1, $DB->count_records('prog_completion', ['coursesetid' => 0]));
        self::assertEquals(1, $DB->count_records('certif_completion'));

        // Update the certification so that it is in progress and has a due date.
        $certcompletion->status = CERTIFSTATUS_INPROGRESS;
        $progcompletion->timedue = strtotime('+1 day', $initdate);
        self::assertTrue(certif_write_completion($certcompletion, $progcompletion));

        // Users can request extensions for themselves only.
        self::setUser($user->id);
        self::assertTrue($certification->can_request_extension($user->id));

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
        self::assertTrue(certif_write_completion($certcompletion, $progcompletion)); // Contains data validation, so we don't need to check it here.

        // Check that expired user can no longer request extension.
        self::setUser($user->id);
        self::assertFalse($certification->can_request_extension($user->id));
    }

    public function test_granting_extension_extends_expiry() {
        global $DB, $CFG;

        self::setAdminUser();
        $CFG->enablecompletion = true;

        $generator = self::getDataGenerator();
        $program_generator = $generator->get_plugin_generator('totara_program');
        $job_generator = $generator->get_plugin_generator('totara_job');

        //Create a certification
        $progid = $program_generator->create_certification(['cert_recertifydatetype' => CERTIFRECERT_EXPIRY]);
        $prog = new program($progid);

        $course = $generator->create_course(array('enablecompletion' => true));
        $manager = $generator->create_user();
        [$user, $job] = $job_generator->create_user_and_job([], $manager->id);
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        self::assertNotEmpty($studentrole);

        // Get manual enrolment plugin and enrol user.
        require_once($CFG->dirroot.'/enrol/manual/locallib.php');
        $manplugin = enrol_get_plugin('manual');
        $maninstance = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'manual'], '*', MUST_EXIST);
        $manplugin->enrol_user($maninstance, $user->id, $studentrole->id);
        self::assertEquals(1, $DB->count_records('user_enrolments'));

        $completionsettings = array('completion' => COMPLETION_TRACKING_AUTOMATIC, 'completionview' => 1);
        $module = $generator->create_module('forum', ['course' => $course->id], $completionsettings);

        //Assign User to program
        $program_generator->assign_to_program($prog->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);

        $initdate = time();
        $expirydate = strtotime('+1 day', $initdate);

        $submitted = new stdClass();
        $submitted->id = $prog->id;
        $submitted->userid = $user->id;
        $submitted->status = CERTIFSTATUS_COMPLETED;
        $submitted->renewalstatus = CERTIFRENEWALSTATUS_DUE;
        $submitted->certifpath = CERTIFPATH_RECERT;
        $submitted->timecompleted = 1004;
        $submitted->timewindowopens = 1005;
        $submitted->timeexpires = $expirydate;
        $submitted->baselinetimeexpires = $expirydate;
        $submitted->progstatus = STATUS_PROGRAM_INCOMPLETE;
        $submitted->timeduenotset = 'no';
        $submitted->timedue = $expirydate;
        $submitted->progtimecompleted = 0;

        // Get completion data
        list($certcompletion, $progcompletion) = certif_process_submitted_edit_completion($submitted);

        //Commit it to the database
        certif_write_completion($certcompletion, $progcompletion);

        //Request extension for user
        $extensiondate = strtotime('+1 day', $expirydate);

        $extension = new stdClass();
        $extension->programid = $prog->id;
        $extension->userid = $user->id;
        $extension->extensiondate = $extensiondate;
        $extension->extensionreason = ""; //Ehhh we don't need a reason
        $extension->status = 0;

        $extensionid = $DB->insert_record('prog_extension', $extension);

        // Set manager as the current user to process extension.
        self::setUser($manager->id);
        prog_process_extensions([$extensionid => PROG_EXTENSION_GRANT], [$extensionid => '']);

        // Switch back to admin.
        self::setAdminUser();

        //Check that extension moves timeexpires, but not baselinetimeexpires
        list($updatedcert, $updatedprog) = certif_load_completion($extension->programid, $extension->userid);
        self::assertEquals($extensiondate, $updatedcert->timeexpires);
        self::assertEquals($expirydate, $updatedcert->baselinetimeexpires);

        // Write cert completion and verify that new completion has used baselinetimeexpires
        // as the base instead of timeexpires.
        certif_set_state_certified($prog->id, $user->id);

        //Grab the certificate active period
        $activeperiod = $DB->get_field('certif', 'activeperiod', ['id' => $prog->certifid]);

        $expectedexpiry = strtotime($activeperiod, $extensiondate);
        $updatedcert = $DB->get_record('certif_completion', ['certifid' => $prog->certifid, 'userid' => $user->id]);
        $updatedprog = $DB->get_record('prog_completion', ['programid' => $prog->id, 'userid' => $user->id]);

        self::assertEquals($expectedexpiry, $updatedprog->timedue);
        self::assertEquals($expectedexpiry, $updatedcert->timeexpires);
        self::assertEquals($expectedexpiry, $updatedcert->baselinetimeexpires); //verify default expiry is reset
    }
}
