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
 * @author Simon Player <simon.player@totaralearning.com>
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara_certification
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/certification/lib.php');

class totara_certification_certification_duedates_testcase extends advanced_testcase {

    public function test_adding_new_assignment_when_user_is_assigned() {
        global $DB;

        /** @var \core\testing\generator $datagenerator */
        $datagenerator = $this->getDataGenerator();
        /** @var \totara_program\testing\generator $programgenerator */
        $programgenerator = $datagenerator->get_plugin_generator('totara_program');

        // Create a certification.
        $certification = $programgenerator->create_certification();
        $certification = new program($certification);

        // Scenario 1: newly assigned user via multiple methods.
        // Create an audience.
        $cohort = $datagenerator->create_cohort();
        // Create a user.
        $user1 = $datagenerator->create_user();

        // Assign a user to the certification as an individual, with a due date in 2 weeks.
        $assignmentduedate = (new DateTime('2 weeks'))->setTime(0, 0);
        $record = ['completiontime' => $assignmentduedate->format('d/m/Y')];
        $programgenerator->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user1->id, $record, true);

        $user_assignments_task = new \totara_program\task\user_assignments_task();
        $user_assignments_task->execute();

        // Check the duedate has been set correctly.
        $completiontimedue = $DB->get_field('prog_completion', 'timedue', ['programid' => $certification->id, 'userid' => $user1->id]);
        self::assertEquals($assignmentduedate->getTimestamp(), $completiontimedue);

        // Check current status of certification
        $sql = 'SELECT status FROM {certif_completion} WHERE certifid = :certifid AND userid = :userid';
        $params = ['certifid' => $certification->certifid, 'userid' => $user1->id];
        $status = $DB->get_field_sql($sql, $params);
        $this->assertEquals(CERTIFSTATUS_ASSIGNED, $status);

        // Assign the same user to the certification via an audience, with a due date in 10 days.
        $newassignmentduedate = clone($assignmentduedate);
        $newduedate = $newassignmentduedate->add(new DateInterval('P10D'));
        $record = ['completiontime' => $newduedate->format('d/m/Y')];
        cohort_add_member($cohort->id, $user1->id);
        $programgenerator->assign_to_program($certification->id, ASSIGNTYPE_COHORT, $cohort->id, $record, true);

        // The completion duedate should not have been updated with $newassignmentduedate and should still be using $assignmentduedate
        $completiontimedue = $DB->get_field('prog_completion', 'timedue', ['programid' => $certification->id, 'userid' => $user1->id]);
        self::assertEquals($newduedate->getTimestamp(), $completiontimedue);
    }

    public function test_adding_new_assignment_user_inprogress() {
        global $DB;

        /** @var \core\testing\generator $datagenerator */
        $datagenerator = $this->getDataGenerator();
        /** @var \totara_program\testing\generator $programgenerator */
        $programgenerator = $datagenerator->get_plugin_generator('totara_program');

        // Create a certification.
        $certification = $programgenerator->create_certification();
        $certification = new program($certification);

        // Scenario 1: newly assigned user via multiple methods.
        // Create an audience.
        $cohort = $datagenerator->create_cohort();
        // Create a user.
        $user1 = $datagenerator->create_user();

        // Assign a user to the certification as an individual, with a due date in 2 weeks.
        $assignmentduedate = (new DateTime('2 weeks'))->setTime(0, 0);
        $record = ['completiontime' => $assignmentduedate->format('d/m/Y')];
        $programgenerator->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user1->id, $record, true);

        $user_assignments_task = new \totara_program\task\user_assignments_task();
        $user_assignments_task->execute();

        // Check the duedate has been set correctly.
        $completiontimedue = $DB->get_field('prog_completion', 'timedue', ['programid' => $certification->id, 'userid' => $user1->id]);
        self::assertEquals($assignmentduedate->getTimestamp(), $completiontimedue);

        // Set the certification as in progress for the user.
        $DB->set_field('certif_completion', 'status', CERTIFSTATUS_INPROGRESS, ['certifid' => $certification->certifid, 'userid' => $user1->id]);

        // Assign the same user to the certification via an audience, with a due date in 24 days.
        $newassignmentduedate = clone($assignmentduedate);
        $newduedate = $newassignmentduedate->add(new DateInterval('P24D'));
        $record = ['completiontime' => $newduedate->format('d/m/Y')];
        cohort_add_member($cohort->id, $user1->id);
        $programgenerator->assign_to_program($certification->id, ASSIGNTYPE_COHORT, $cohort->id, $record, true);

        // The completion duedate should not have been updated with $newassignmentduedate and should still be using $assignmentduedate
        $completiontimedue = $DB->get_field('prog_completion', 'timedue', ['programid' => $certification->id, 'userid' => $user1->id]);
        self::assertEquals($newduedate->getTimestamp(), $completiontimedue);
    }

    /**
     * Test adding new assignment when user is completed
     */
    public function test_adding_new_assignment_user_certified() {
        global $DB;

        /** @var \core\testing\generator $datagenerator */
        $datagenerator = $this->getDataGenerator();
        /** @var \totara_program\testing\generator $programgenerator */
        $programgenerator = $datagenerator->get_plugin_generator('totara_program');

        // Create a certification.
        $certification = $programgenerator->create_certification();
        $certification = new program($certification);

        // Create an audience.
        $cohort = $datagenerator->create_cohort();
        // Create a user.
        $user1 = $datagenerator->create_user();

        // Assign a user to the certification as an individual, with a due date in 2 weeks.
        $assignmentduedate = (new DateTime('2 weeks'))->setTime(0, 0);
        $record = ['completiontime' => $assignmentduedate->format('d/m/Y')];
        $programgenerator->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user1->id, $record, true);

        $user_assignments_task = new \totara_program\task\user_assignments_task();
        $user_assignments_task->execute();

        // Check the duedate has been set correctly.
        $completiontimedue = $DB->get_field('prog_completion', 'timedue', ['programid' => $certification->id, 'userid' => $user1->id]);
        self::assertEquals($assignmentduedate->getTimestamp(), $completiontimedue);

        // Set the certification as completed for the user.
        $DB->set_field('certif_completion', 'status', CERTIFSTATUS_COMPLETED, ['certifid' => $certification->certifid, 'userid' => $user1->id]);

        // Assign the same user to the certification via an audience, with a due date in 10 days.
        $newassignmentduedate = clone($assignmentduedate);
        $newduedate = $newassignmentduedate->add(new DateInterval('P10D'));
        $record = ['completiontime' => $newduedate->format('d/m/Y')];
        cohort_add_member($cohort->id, $user1->id);
        $programgenerator->assign_to_program($certification->id, ASSIGNTYPE_COHORT, $cohort->id, $record, true);

        // The completion duedate should not have been updated with $newassignmentduedate and should still be using $assignmentduedate
        $completiontimedue = $DB->get_field('prog_completion', 'timedue', ['programid' => $certification->id, 'userid' => $user1->id]);
        self::assertEquals($assignmentduedate->getTimestamp(), $completiontimedue);
    }

    public function test_adding_new_assignment_user_expired() {
        global $DB;

        /** @var \core\testing\generator $datagenerator */
        $datagenerator = $this->getDataGenerator();
        /** @var \totara_program\testing\generator $programgenerator */
        $programgenerator = $datagenerator->get_plugin_generator('totara_program');

        // Create a certification.
        $certification = $programgenerator->create_certification();
        $certification = new program($certification);

        // Scenario 0: newly assigned user via multiple methods.
        // Create an audience.
        $cohort = $datagenerator->create_cohort();
        // Create a user.
        $user0 = $datagenerator->create_user();

        // Assign a user to the certification as an individual, with a due date in 1 weeks.
        $assignmentduedate = (new DateTime('1 weeks'))->setTime(0, 0);
        $record = ['completiontime' => $assignmentduedate->format('d/m/Y')];
        $programgenerator->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user0->id, $record, true);

        $user_assignments_task = new \totara_program\task\user_assignments_task();
        $user_assignments_task->execute();

        // Check the duedate has been set correctly.
        $completiontimedue = $DB->get_field('prog_completion', 'timedue', ['programid' => $certification->id, 'userid' => $user0->id]);
        self::assertEquals($assignmentduedate->getTimestamp(), $completiontimedue);

        // Set the certification as expired for the user.
        $DB->set_field('certif_completion', 'status', CERTIFSTATUS_EXPIRED, ['certifid' => $certification->certifid, 'userid' => $user0->id]);

        // Assign the same user to the certification via an audience, with a due date in 9 days.
        $newassignmentduedate = clone($assignmentduedate);
        $record = ['completiontime' => $newassignmentduedate->add(new DateInterval('P9D'))->format('d/m/Y')];
        cohort_add_member($cohort->id, $user0->id);
        $programgenerator->assign_to_program($certification->id, ASSIGNTYPE_COHORT, $cohort->id, $record, true);

        // The completion duedate should not have been updated with $newassignmentduedate and should still be using $assignmentduedate
        $completiontimedue = $DB->get_field('prog_completion', 'timedue', ['programid' => $certification->id, 'userid' => $user0->id]);
        self::assertEquals($assignmentduedate->getTimestamp(), $completiontimedue);
    }

    /**
     * Test adding new assignment when user has a history record.
     */
    public function test_adding_new_assignment_user_has_history_record() {
        global $DB;

        /** @var \core\testing\generator $datagenerator */
        $datagenerator = $this->getDataGenerator();
        /** @var \totara_program\testing\generator $programgenerator */
        $programgenerator = $datagenerator->get_plugin_generator('totara_program');

        // Create a certification.
        $certification = $programgenerator->create_certification();
        $certification = new program($certification);

        // Create a user.
        $user2 = $datagenerator->create_user();

        // Assign a user to the certification as an individual.
        $assignmentduedate = (new DateTime('1 day'))->setTime(0, 0);
        $record = ['completiontime' => $assignmentduedate->format('d/m/Y')];
        $programgenerator->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user2->id, $record, true);

        // Run user assignment task
        $user_assignments_task = new \totara_program\task\user_assignments_task();
        $user_assignments_task->execute();

        // Update the certification so that the user is expired.
        list($certcompletion, $progcompletion) = certif_load_completion($certification->id, $user2->id);
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $progcompletion->timecompleted = 0;
        $certcompletion->status = CERTIFSTATUS_EXPIRED;
        $certcompletion->renewalstatus = CERTIFRENEWALSTATUS_EXPIRED;
        $certcompletion->certifpath = CERTIFPATH_CERT;
        $certcompletion->timecompleted = 0;
        $certcompletion->timewindowopens = 0;
        $certcompletion->timeexpires = 0;
        $certcompletion->baselinetimeexpires = 0;
        self::assertTrue(certif_write_completion($certcompletion, $progcompletion));

        // Unassign the user.
        $certification->unassign_learners([$user2->id]);

        // Basic check that we have user2 certif_completion_history records.
        $history = $DB->get_records('certif_completion_history', ['userid' => $user2->id, 'certifid' => $certification->certifid]);
        self::assertCount(1, $history);
        self::assertEquals($user2->id, reset($history)->userid);
        self::assertEquals(1, $DB->count_records('prog_completion'));

        $newassignmentduedate = (new DateTime('5 days'))->setTime(0, 0);
        $record = ['completiontime' => $newassignmentduedate->format('d/m/Y')];
        $programgenerator->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user2->id, $record, true);

        // Run user assignment task
        $user_assignments_task->execute();

        // The completion duedate should not have been updated with $newassignmentduedate and should still be using $assignmentduedate
        $completiontimedue = $DB->get_field('prog_completion', 'timedue', ['programid' => $certification->id, 'userid' => $user2->id]);
        self::assertEquals($assignmentduedate->getTimestamp(), $completiontimedue);
    }
}
