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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_certification
 */

use totara_core\advanced_feature;
use totara_program\task\send_messages_task;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php');

/**
 * Test messages in certifications.
 *
 * Resending enrolment and unenrolment messages is tested in the programs message test.
 *
 * Includes:
 * program completed
 * program due
 * program overdue
 * course set completed
 * course set due
 * course set overdue
 * recertification window open
 * recertification window due to close
 * failure to recertify
 * learner follow-up
 *
 * To test, run this from the command line from the $CFG->dirroot
 * vendor/bin/phpunit totara_certification_messages_testcase
 *
 */
class totara_certification_messages_testcase extends reportcache_advanced_testcase {

    /* @var program $cert1 */
    private $cert1;

    /* @var program $cert2 */
    private $cert2;

    private $user1, $user2;

    /* @var \core_phpunit\message_sink $sink */
    private $sink;

    /* @var \totara_program\testing\generator $programgenerator */
    private $programgenerator;

    public function setUp(): void {
        global $DB;

        parent::setUp();

        $this->setAdminUser();

        // Turn off programs. This is to test that it doesn't interfere with certification completion.
        set_config('enableprograms', advanced_feature::DISABLED);

        $this->programgenerator = \totara_program\testing\generator::instance();

        // Create users.
        $this->assertEquals(2, $DB->count_records('user'));
        $this->user1 = $this->getDataGenerator()->create_user();
        $this->user2 = $this->getDataGenerator()->create_user();
        $this->assertEquals(2 + 2, $DB->count_records('user'));

        // Create two certs.
        $this->assertEquals(0, $DB->count_records('certif'));
        $data = array(
            'cert_activeperiod' => '1 year',
            'cert_windowperiod' => '6 month',
        );
        $this->cert1 = $this->programgenerator->legacy_create_certification($data);
        $this->cert2 = $this->programgenerator->legacy_create_certification($data);
        $this->assertEquals(2, $DB->count_records('certif'));

        unset_config('noemailever');
        $this->sink = $this->redirectMessages();

        // Make sure the mail is redirecting and the sink is clear.
        $this->sink->clear();
    }

    protected function tearDown(): void {
        $this->sink->close();
        $this->cert1 = null;
        $this->cert2 = null;
        $this->user1 = null;
        $this->user2 = null;
        $this->sink = null;
        $this->programgenerator = null;
        parent::tearDown();
    }

    /**
     * Make sure that certification completed messages are resent when a user recertifies.
     */
    public function test_certification_completed_messages() {
        global $DB;

        // Set up the messages.
        $programmessagemanager = $this->cert1->get_messagesmanager();
        $programmessagemanager->delete();
        $programmessagemanager->add_message(MESSAGETYPE_PROGRAM_COMPLETED);
        $programmessagemanager->save_messages();
        prog_messages_manager::get_program_messages_manager($this->cert1->id, true); // Causes static cache to be reset.
        $messageid = $DB->get_field('prog_message', 'id',
            array('programid' => $this->cert1->id, 'messagetype' => MESSAGETYPE_PROGRAM_COMPLETED));

        // Create course.
        $course1 = $this->getDataGenerator()->create_course();

        // Assign courses to cert.
        $coursesetdata = array(
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_CERT,
                'courses' => array($course1)
            ),
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_RECERT,
                'courses' => array($course1)
            ),
        );
        $this->programgenerator->legacy_add_coursesets_to_program($this->cert1, $coursesetdata);

        // Assign users to program.
        $usersprogram = array($this->user1->id, $this->user2->id);
        $this->programgenerator->assign_program($this->cert1->id, $usersprogram);

        // Complete courses.
        $this->getDataGenerator()->enrol_user($this->user1->id, $course1->id);
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course1->id));
        $completion->mark_complete();
        $this->getDataGenerator()->enrol_user($this->user2->id, $course1->id);
        $completion = new completion_completion(array('userid' => $this->user2->id, 'course' => $course1->id));
        $completion->mark_complete();

        // Check that both users were sent messages.
        $emails = $this->sink->get_messages();
        $this->assertCount(2, $emails);
        $this->sink->clear();

        // Check that both message logs exist.
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Reopen the window for user1 - move all dates back nine months.
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user1->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 9;
        $certcompletion->timeexpires -= DAYSECS * 30 * 9;
        $certcompletion->timecompleted -= DAYSECS * 30 * 9;
        $progcompletion->timedue -= DAYSECS * 30 * 9;
        $progcompletion->timecompleted -= DAYSECS * 30 * 9;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        $certcron = new \totara_certification\task\update_certification_task();
        $certcron->execute();

        // Check that no new messages were sent.
        $emails = $this->sink->get_messages();
        $this->assertCount(0, $emails);
        $this->sink->clear();

        // Check that only the log for user2 is still there - the log for user1 was deleted when their window opened.
        $this->assertEquals(1, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Recertify user1.
        $this->getDataGenerator()->enrol_user($this->user1->id, $course1->id);
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course1->id));
        $completion->mark_complete();

        // Check that just user1 received a new message.
        $emails = $this->sink->get_messages();
        $this->assertCount(1, $emails);
        $this->sink->clear();

        // Check both users now have message logs (user2's is old, user1's is new).
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));
    }

    /**
     * Make sure that certification due messages are resent when a user's due date is nearly reached.
     */
    public function test_certification_due_messages() {
        global $DB;

        // Set up the messages.
        $programmessagemanager = $this->cert1->get_messagesmanager();
        $programmessagemanager->delete();
        $programmessagemanager->add_message(MESSAGETYPE_PROGRAM_DUE);
        $programmessagemanager->save_messages();
        prog_messages_manager::get_program_messages_manager($this->cert1->id, true); // Causes static cache to be reset.
        $messageid = $DB->get_field('prog_message', 'id',
            array('programid' => $this->cert1->id, 'messagetype' => MESSAGETYPE_PROGRAM_DUE));
        // Hack the message record to be triggered 100 days before due.
        $DB->set_field('prog_message', 'triggertime', DAYSECS * 100, array('id' => $messageid));

        // Create course.
        $course1 = $this->getDataGenerator()->create_course();

        // Assign courses to cert.
        $coursesetdata = array(
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_CERT,
                'courses' => array($course1)
            ),
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_RECERT,
                'courses' => array($course1)
            ),
        );
        $this->programgenerator->legacy_add_coursesets_to_program($this->cert1, $coursesetdata);

        // Assign users to program.
        $usersprogram = array($this->user1->id, $this->user2->id);
        $this->programgenerator->assign_program($this->cert1->id, $usersprogram);

        // Hack the due dates, 10 days from now.
        $duedate = time() + DAYSECS * 10;
        list($certcompl1, $progcompl1) = certif_load_completion($this->cert1->id, $this->user1->id);
        $progcompl1->timedue = $duedate;
        $this->assertTrue(certif_write_completion($certcompl1, $progcompl1));
        list($certcompl2, $progcompl2) = certif_load_completion($this->cert1->id, $this->user2->id);
        $progcompl2->timedue = $duedate;
        $this->assertTrue(certif_write_completion($certcompl2, $progcompl2));

        // Attempt to send any program messages.
        $this->waitForSecond(); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new send_messages_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.

        // Check that both users were sent messages.
        $emails = $this->sink->get_messages();
        $this->assertCount(2, $emails);
        $this->sink->clear();

        // Check that both message logs exist.
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Mark user1 certified.
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course1->id));
        $completion->mark_complete();

        // Attempt to send any program messages.
        $this->waitForSecond(); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new send_messages_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.

        // Check that no new messages were sent.
        $emails = $this->sink->get_messages();
        $this->assertCount(0, $emails);
        $this->sink->clear();

        // Check that both message logs still exist.
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Reopen the window for user1 - move all dates back nine months.
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user1->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 9;
        $certcompletion->timeexpires -= DAYSECS * 30 * 9;
        $certcompletion->timecompleted -= DAYSECS * 30 * 9;
        $progcompletion->timedue -= DAYSECS * 30 * 9;
        $progcompletion->timecompleted -= DAYSECS * 30 * 9;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        $certcron = new \totara_certification\task\update_certification_task();
        $certcron->execute();

        // Check that no new messages were sent.
        $emails = $this->sink->get_messages();
        $this->assertCount(0, $emails);
        $this->sink->clear();

        // Check that only the log for user2 is still there - the log for user1 was deleted when their window opened.
        $this->assertEquals(1, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Attempt to send any program messages.
        $this->waitForSecond(); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new send_messages_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.

        // Check that just user1 received a new message.
        $emails = $this->sink->get_messages();
        $this->assertCount(1, $emails);
        $this->sink->clear();

        // Check both users now have message logs (user2's is old, user1's is new).
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));
    }

    /**
     * Make sure that certification overdue messages are resent when a user's due date has been passed.
     */
    public function test_certification_overdue_messages() {
        global $DB;

        // Set up the messages.
        $programmessagemanager = $this->cert1->get_messagesmanager();
        $programmessagemanager->delete();
        $programmessagemanager->add_message(MESSAGETYPE_PROGRAM_OVERDUE);
        $programmessagemanager->save_messages();
        prog_messages_manager::get_program_messages_manager($this->cert1->id, true); // Causes static cache to be reset.
        $messageid = $DB->get_field('prog_message', 'id',
            array('programid' => $this->cert1->id, 'messagetype' => MESSAGETYPE_PROGRAM_OVERDUE));
        // Hack the message record to be triggered 10 days after overdue.
        $DB->set_field('prog_message', 'triggertime', DAYSECS * 10, array('id' => $messageid));

        // Create course.
        $course1 = $this->getDataGenerator()->create_course();

        // Assign courses to cert.
        $coursesetdata = array(
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_CERT,
                'courses' => array($course1)
            ),
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_RECERT,
                'courses' => array($course1)
            ),
        );
        $this->programgenerator->legacy_add_coursesets_to_program($this->cert1, $coursesetdata);

        // Assign users to program.
        $usersprogram = array($this->user1->id, $this->user2->id);
        $this->programgenerator->assign_program($this->cert1->id, $usersprogram);

        // Hack the due dates, 20 days ago.
        $duedate = time() - DAYSECS * 20;
        list($certcompl1, $progcompl1) = certif_load_completion($this->cert1->id, $this->user1->id);
        $progcompl1->timedue = $duedate;
        $this->assertTrue(certif_write_completion($certcompl1, $progcompl1));
        list($certcompl2, $progcompl2) = certif_load_completion($this->cert1->id, $this->user2->id);
        $progcompl2->timedue = $duedate;
        $this->assertTrue(certif_write_completion($certcompl2, $progcompl2));

        // Attempt to send any program messages.
        $this->waitForSecond(); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new send_messages_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.

        // Check that both users were sent messages.
        $emails = $this->sink->get_messages();
        $this->assertCount(2, $emails);
        $this->sink->clear();

        // Check that both message logs exist.
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Mark user1 certified.
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course1->id));
        $completion->mark_complete();

        // Attempt to send any program messages.
        $this->waitForSecond(); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new send_messages_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.

        // Check that no new messages were sent.
        $emails = $this->sink->get_messages();
        $this->assertCount(0, $emails);
        $this->sink->clear();

        // Check that both message logs still exist.
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Reopen the window for user1 - move all dates back fifteen months.
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user1->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 15;
        $certcompletion->timeexpires -= DAYSECS * 30 * 15;
        $certcompletion->timecompleted -= DAYSECS * 30 * 15;
        $progcompletion->timedue -= DAYSECS * 30 * 15;
        $progcompletion->timecompleted -= DAYSECS * 30 * 15;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        $certcron = new \totara_certification\task\update_certification_task();
        $certcron->execute();

        // Check that no new messages were sent.
        $emails = $this->sink->get_messages();
        $this->assertCount(0, $emails);
        $this->sink->clear();

        // Check that only the log for user2 is still there - the log for user1 was deleted when their window opened.
        $this->assertEquals(1, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Attempt to send any program messages.
        $this->waitForSecond(); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new send_messages_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.

        // Check that just user1 received a new message.
        $emails = $this->sink->get_messages();
        $this->assertCount(1, $emails);
        $this->sink->clear();

        // Check both users now have message logs (user2's is old, user1's is new).
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));
    }

    /**
     * Make sure that courseset completed messages are resent when a user completes the content.
     *
     * Overall patern is: Primary, Recert, Recert, Primary (due to expiry).
     *
     * This test works with just one course set, so it's pretty much the same as the program complete test.
     * The program tests make sure that multiple courseset messages are sent within a single program.
     */
    public function test_certification_courseset_completed_messages() {
        global $DB;

        // Set up the messages.
        $programmessagemanager = $this->cert1->get_messagesmanager();
        $programmessagemanager->delete();
        $programmessagemanager->add_message(MESSAGETYPE_COURSESET_COMPLETED);
        $programmessagemanager->save_messages();
        prog_messages_manager::get_program_messages_manager($this->cert1->id, true); // Causes static cache to be reset.
        $messageid = $DB->get_field('prog_message', 'id',
            array('programid' => $this->cert1->id, 'messagetype' => MESSAGETYPE_COURSESET_COMPLETED));

        // Create course.
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        // Assign courses to cert.
        $coursesetdata = array(
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_CERT,
                'courses' => array($course1)
            ),
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_RECERT,
                'courses' => array($course2)
            ),
        );
        $this->programgenerator->legacy_add_coursesets_to_program($this->cert1, $coursesetdata);
        $params = array('programid' => $this->cert1->id, 'certifpath' => CERTIFPATH_CERT);
        $primarycoursesetid = $DB->get_field('prog_courseset', 'id', $params);
        $params = array('programid' => $this->cert1->id, 'certifpath' => CERTIFPATH_RECERT);
        $recertcoursesetid = $DB->get_field('prog_courseset', 'id', $params);

        // Assign users to program.
        $usersprogram = array($this->user1->id, $this->user2->id);
        $this->programgenerator->assign_program($this->cert1->id, $usersprogram);

        // Enrol both users into both courses.
        $this->getDataGenerator()->enrol_user($this->user1->id, $course1->id);
        $this->getDataGenerator()->enrol_user($this->user1->id, $course2->id);
        $this->getDataGenerator()->enrol_user($this->user2->id, $course1->id);
        $this->getDataGenerator()->enrol_user($this->user2->id, $course2->id);

        // Complete primary path courses.
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course1->id));
        $completion->mark_complete();
        $completion = new completion_completion(array('userid' => $this->user2->id, 'course' => $course1->id));
        $completion->mark_complete();

        // First message, on primary path. Check that both users were sent messages.
        $emails = $this->sink->get_messages();
        $this->assertCount(2, $emails);
        $this->sink->clear();

        // Check that both message logs exist.
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Reopen the window for user1 - move all dates back nine months.
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user1->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 9;
        $certcompletion->timeexpires -= DAYSECS * 30 * 9;
        $certcompletion->timecompleted -= DAYSECS * 30 * 9;
        $progcompletion->timedue -= DAYSECS * 30 * 9;
        $progcompletion->timecompleted -= DAYSECS * 30 * 9;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        $certcron = new \totara_certification\task\update_certification_task();
        $certcron->execute();

        // Check that no new messages were sent.
        $emails = $this->sink->get_messages();
        $this->assertCount(0, $emails);
        $this->sink->clear();

        // Check that logs for both users are still there - they belong to the primary cert path, so shouldn't be touched.
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid, 'coursesetid' => $primarycoursesetid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid, 'coursesetid' => $primarycoursesetid)));

        // Recertify user1.
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course2->id));
        $completion->mark_complete();

        // Second message, on recert path. Check that just user1 received a new message.
        $emails = $this->sink->get_messages();
        $this->assertCount(1, $emails);
        $this->sink->clear();

        // Check that we've added another record for the test user, for the recert path.
        $this->assertEquals(3, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid, 'coursesetid' => $primarycoursesetid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid, 'coursesetid' => $recertcoursesetid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid, 'coursesetid' => $primarycoursesetid)));

        // Reopen the window for user1 - move all dates back nine months.
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user1->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 9;
        $certcompletion->timeexpires -= DAYSECS * 30 * 9;
        $certcompletion->timecompleted -= DAYSECS * 30 * 9;
        $progcompletion->timedue -= DAYSECS * 30 * 9;
        $progcompletion->timecompleted -= DAYSECS * 30 * 9;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        $certcron = new \totara_certification\task\update_certification_task();
        $certcron->execute();

        // The test user's recert path log was removed.
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid, 'coursesetid' => $primarycoursesetid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid, 'coursesetid' => $primarycoursesetid)));

        // Check that no new messages were sent.
        $emails = $this->sink->get_messages();
        $this->assertCount(0, $emails);
        $this->sink->clear();

        // Recertify user1 again.
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course2->id));
        $completion->mark_complete();

        // Third message, second time on recert path. Check that just user1 received a new message.
        $emails = $this->sink->get_messages();
        $this->assertCount(1, $emails);
        $this->sink->clear();

        // The test user's recert path log was created again.
        $this->assertEquals(3, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid, 'coursesetid' => $primarycoursesetid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid, 'coursesetid' => $recertcoursesetid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid, 'coursesetid' => $primarycoursesetid)));

        // To ensure that the control's recert message log record is not deleted, we'll create it artificially now.
        $messagelog = $DB->get_record('prog_messagelog', array('userid' => $this->user2->id));
        unset($messagelog->id);
        $messagelog->coursesetid = $recertcoursesetid;
        $DB->insert_record('prog_messagelog', $messagelog);

        // Expire user1 - move all dates back FIFTEEN months.
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user1->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 15;
        $certcompletion->timeexpires -= DAYSECS * 30 * 15;
        $certcompletion->timecompleted -= DAYSECS * 30 * 15;
        $progcompletion->timedue -= DAYSECS * 30 * 15;
        $progcompletion->timecompleted -= DAYSECS * 30 * 15;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        $certcron = new \totara_certification\task\update_certification_task();
        $certcron->execute();

        // Check that no new messages were sent.
        $emails = $this->sink->get_messages();
        $this->assertCount(0, $emails);
        $this->sink->clear();

        // The test user's primary and recert path logs were removed (because window opened then expired). User2's are both there.
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid, 'coursesetid' => $primarycoursesetid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid, 'coursesetid' => $recertcoursesetid)));

        // Recertify user1 on primary path again.
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course1->id));
        $completion->mark_complete();

        // Fourth message, second time on primary path. Check that just user1 received a new message.
        $emails = $this->sink->get_messages();
        $this->assertCount(1, $emails);
        $this->sink->clear();

        // The test user's primary path log was created again.
        $this->assertEquals(3, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid, 'coursesetid' => $primarycoursesetid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid, 'coursesetid' => $primarycoursesetid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid, 'coursesetid' => $recertcoursesetid)));
    }

    /**
     * Make sure that courseset due messages are resent when a user's due date is nearly reached.
     *
     * Overall patern is: Primary, Recert, Recert, Primary (due to expiry).
     *
     * This test works with just one course set, so it's pretty much the same as the program due test.
     * The program tests make sure that multiple courseset messages are sent within a single program.
     */
    public function test_certification_courseset_due_messages() {
        global $DB;

        // Set up the messages.
        $programmessagemanager = $this->cert1->get_messagesmanager();
        $programmessagemanager->delete();
        $programmessagemanager->add_message(MESSAGETYPE_COURSESET_DUE);
        $programmessagemanager->save_messages();
        prog_messages_manager::get_program_messages_manager($this->cert1->id, true); // Causes static cache to be reset.
        $messageid = $DB->get_field('prog_message', 'id',
            array('programid' => $this->cert1->id, 'messagetype' => MESSAGETYPE_COURSESET_DUE));
        // Hack the message record to be triggered 20 days before due.
        $DB->set_field('prog_message', 'triggertime', DAYSECS * 20, array('id' => $messageid));

        // Create course.
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        // Assign courses to cert.
        $coursesetdata = array(
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_CERT,
                'timeallowed' => DAYSECS * 100,
                'courses' => array($course1)
            ),
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_RECERT,
                'timeallowed' => DAYSECS * 100,
                'courses' => array($course2)
            ),
        );
        $this->programgenerator->legacy_add_coursesets_to_program($this->cert1, $coursesetdata);
        $params = array('programid' => $this->cert1->id, 'certifpath' => CERTIFPATH_CERT);
        $primarycoursesetid = $DB->get_field('prog_courseset', 'id', $params);
        $params = array('programid' => $this->cert1->id, 'certifpath' => CERTIFPATH_RECERT);
        $recertcoursesetid = $DB->get_field('prog_courseset', 'id', $params);

        // Assign users to cert.
        $usersprogram = array($this->user1->id, $this->user2->id);
        $timebefore = time();
        $this->programgenerator->assign_program($this->cert1->id, $usersprogram);
        $timeafter = time();

        // Before we hack the course set due dates, make sure that they are currently valid.
        $this->assertEquals(2, $DB->count_records('prog_completion', array('userid' => $this->user1->id)));
        $this->assertEquals(1, $DB->count_records('prog_completion', array('userid' => $this->user1->id, 'coursesetid' => 0)));
        $coursesetcompletion = $DB->get_record('prog_completion', array('userid' => $this->user1->id, 'coursesetid' => $primarycoursesetid));
        $this->assertGreaterThanOrEqual($timebefore, $coursesetcompletion->timecreated);
        $this->assertLessThanOrEqual($timeafter, $coursesetcompletion->timecreated);
        $this->assertEquals($coursesetcompletion->timecreated + 100 * DAYSECS, $coursesetcompletion->timedue);

        // Hack the course set due dates, 10 days from now.
        $duedate = time() + DAYSECS * 10;
        $DB->set_field('prog_completion', 'timedue', $duedate,
            array('programid' => $this->cert1->id, 'userid' => $this->user1->id));
        $DB->set_field('prog_completion', 'timedue', $duedate,
            array('programid' => $this->cert1->id, 'userid' => $this->user2->id));

        // Before the test, make sure the correct program message logs exist.
        $this->assertEquals(0, $DB->count_records('prog_messagelog'));

        // First course set due message, primary path. Course set due messages go out 20 days before due date.
        $this->waitForSecond(); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start();
        $task = new send_messages_task();
        $this->sink->clear();
        $task->execute();
        $emails = $this->sink->get_messages();
        $this->sink->clear();
        ob_end_clean();

        // Check that both users were sent messages.
        $this->assertCount(2, $emails);

        // Check both users now have message logs.
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Mark user1 certified.
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course1->id));
        $completion->mark_complete();

        // Open the window for user1 - move all dates back nine months.
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user1->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 9;
        $certcompletion->timeexpires -= DAYSECS * 30 * 9;
        $certcompletion->timecompleted -= DAYSECS * 30 * 9;
        $progcompletion->timedue -= DAYSECS * 30 * 9;
        $progcompletion->timecompleted -= DAYSECS * 30 * 9;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        $certcron = new \totara_certification\task\update_certification_task();
        $timebefore = time();
        $certcron->execute();
        $timeafter = time();

        // Check that logs for both users are still there - they belong to the primary cert path, so shouldn't be touched.
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid, 'coursesetid' => $primarycoursesetid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid, 'coursesetid' => $primarycoursesetid)));

        // Before we hack the course set due dates, make sure that they are currently valid.
        $this->assertEquals(3, $DB->count_records('prog_completion', array('userid' => $this->user1->id)));
        $this->assertEquals(1, $DB->count_records('prog_completion', array('userid' => $this->user1->id, 'coursesetid' => 0)));
        $this->assertEquals(1, $DB->count_records('prog_completion', array('userid' => $this->user1->id, 'coursesetid' => $primarycoursesetid)));
        $coursesetcompletion = $DB->get_record('prog_completion', array('userid' => $this->user1->id, 'coursesetid' => $recertcoursesetid));
        $this->assertGreaterThanOrEqual($timebefore, $coursesetcompletion->timecreated);
        $this->assertLessThanOrEqual($timeafter, $coursesetcompletion->timecreated);
        $this->assertEquals($coursesetcompletion->timecreated + 100 * DAYSECS, $coursesetcompletion->timedue);

        // Hack the course set due dates again, 10 days from now.
        $duedate = time() + DAYSECS * 10;
        $DB->set_field('prog_completion', 'timedue', $duedate, array('userid' => $this->user1->id, 'coursesetid' => $recertcoursesetid));

        // Second course set due message, recert path. Course set due messages go out 20 days before due date.
        $this->waitForSecond(); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start();
        $task = new send_messages_task();
        $this->sink->clear();
        $task->execute();
        $emails = $this->sink->get_messages();
        $this->sink->clear();
        ob_end_clean();

        // Check that just user1 received a new message.
        $this->assertCount(1, $emails);

        // Check that we've added another record for the test user, for the recert path.
        $this->assertEquals(3, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid, 'coursesetid' => $primarycoursesetid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid, 'coursesetid' => $recertcoursesetid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid, 'coursesetid' => $primarycoursesetid)));

        // Mark user1 recertified.
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course2->id));
        $completion->mark_complete();

        // Reopen the window for user1 - move all dates back nine months.
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user1->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 9;
        $certcompletion->timeexpires -= DAYSECS * 30 * 9;
        $certcompletion->timecompleted -= DAYSECS * 30 * 9;
        $progcompletion->timedue -= DAYSECS * 30 * 9;
        $progcompletion->timecompleted -= DAYSECS * 30 * 9;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        $certcron = new \totara_certification\task\update_certification_task();
        $timebefore = time();
        $certcron->execute();
        $timeafter = time();

        // The test user's recert path log was removed.
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid, 'coursesetid' => $primarycoursesetid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid, 'coursesetid' => $primarycoursesetid)));

        // Before we hack the course set due dates, make sure that they are currently valid.
        $this->assertEquals(3, $DB->count_records('prog_completion', array('userid' => $this->user1->id)));
        $this->assertEquals(1, $DB->count_records('prog_completion', array('userid' => $this->user1->id, 'coursesetid' => 0)));
        $this->assertEquals(1, $DB->count_records('prog_completion', array('userid' => $this->user1->id, 'coursesetid' => $primarycoursesetid)));
        $coursesetcompletion = $DB->get_record('prog_completion', array('userid' => $this->user1->id, 'coursesetid' => $recertcoursesetid));
        $this->assertGreaterThanOrEqual($timebefore, $coursesetcompletion->timecreated);
        $this->assertLessThanOrEqual($timeafter, $coursesetcompletion->timecreated);
        $this->assertEquals($coursesetcompletion->timecreated + 100 * DAYSECS, $coursesetcompletion->timedue);

        // Hack the course set due dates again, 10 days from now.
        $duedate = time() + DAYSECS * 10;
        $DB->set_field('prog_completion', 'timedue', $duedate, array('userid' => $this->user1->id, 'coursesetid' => $recertcoursesetid));

        // Third course set due message, repeat on recert path. Course set due messages go out 20 days before due date.
        $this->waitForSecond(); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start();
        $task = new send_messages_task();
        $this->sink->clear();
        $task->execute();
        $emails = $this->sink->get_messages();
        $this->sink->clear();
        ob_end_clean();

        // Check that just user1 received a new message.
        $this->assertCount(1, $emails);

        // The test user's recert path log was created again.
        $this->assertEquals(3, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid, 'coursesetid' => $primarycoursesetid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid, 'coursesetid' => $recertcoursesetid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid, 'coursesetid' => $primarycoursesetid)));

        // To ensure that the control's recert message log record is not deleted, we'll create it artificially now.
        $messagelog = $DB->get_record('prog_messagelog', array('userid' => $this->user2->id));
        unset($messagelog->id);
        $messagelog->coursesetid = $recertcoursesetid;
        $DB->insert_record('prog_messagelog', $messagelog);

        // Expire the user - move all dates back an additional six month (on top of 9 months earlier) months.
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user1->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 6;
        $certcompletion->timeexpires -= DAYSECS * 30 * 6;
        $certcompletion->timecompleted -= DAYSECS * 30 * 6;
        $progcompletion->timedue -= DAYSECS * 30 * 6;
        // No progcompletion->timecompleted because the window is already open.
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        $certcron = new \totara_certification\task\update_certification_task();
        $timebefore = time();
        $certcron->execute();
        $timeafter = time();

        // Only user1's recert path log was removed (because we've only expired). User2's are both there.
        $this->assertEquals(3, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid, 'coursesetid' => $recertcoursesetid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid, 'coursesetid' => $primarycoursesetid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid, 'coursesetid' => $recertcoursesetid)));

        // Before we hack the course set due dates, make sure that they are currently valid. This time, primary is reset!
        $this->assertEquals(3, $DB->count_records('prog_completion', array('userid' => $this->user1->id)));
        $this->assertEquals(1, $DB->count_records('prog_completion', array('userid' => $this->user1->id, 'coursesetid' => 0)));
        $this->assertEquals(1, $DB->count_records('prog_completion', array('userid' => $this->user1->id, 'coursesetid' => $recertcoursesetid)));
        $coursesetcompletion = $DB->get_record('prog_completion', array('userid' => $this->user1->id, 'coursesetid' => $primarycoursesetid));
        $this->assertGreaterThanOrEqual($timebefore, $coursesetcompletion->timecreated);
        $this->assertLessThanOrEqual($timeafter, $coursesetcompletion->timecreated);
        $this->assertEquals($coursesetcompletion->timecreated + 100 * DAYSECS, $coursesetcompletion->timedue);

        // Hack the course set due dates again, 10 days from now.
        $duedate = time() + DAYSECS * 10;
        $DB->set_field('prog_completion', 'timedue', $duedate, array('userid' => $this->user1->id, 'coursesetid' => $primarycoursesetid));

        // Fourth course set due message, repeat on primary path. Course set due messages go out 20 days before due date.
        $this->waitForSecond(); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start();
        $task = new send_messages_task();
        $this->sink->clear();
        $task->execute();
        $emails = $this->sink->get_messages();
        $this->sink->clear();
        ob_end_clean();

        // Check that just user1 received a new message.
        $this->assertCount(1, $emails);

        // The test user's primary path log was created again.
        $this->assertEquals(4, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid, 'coursesetid' => $primarycoursesetid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid, 'coursesetid' => $recertcoursesetid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid, 'coursesetid' => $primarycoursesetid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid, 'coursesetid' => $recertcoursesetid)));
    }

    /**
     * Make sure that courseset overdue messages are resent when a user's due date is nearly reached.
     *
     * Overall patern is: Primary, Recert, Recert, Primary (due to expiry).
     *
     * This test works with just one course set, so it's pretty much the same as the program overdue test.
     * The program tests make sure that multiple courseset messages are sent within a single program.
     */
    public function test_certification_courseset_overdue_messages() {
        global $DB;

        // Set up the messages.
        $programmessagemanager = $this->cert1->get_messagesmanager();
        $programmessagemanager->delete();
        $programmessagemanager->add_message(MESSAGETYPE_COURSESET_OVERDUE);
        $programmessagemanager->save_messages();
        prog_messages_manager::get_program_messages_manager($this->cert1->id, true); // Causes static cache to be reset.
        $messageid = $DB->get_field('prog_message', 'id',
            array('programid' => $this->cert1->id, 'messagetype' => MESSAGETYPE_COURSESET_OVERDUE));
        // Hack the message record to be triggered 10 days after overdue.
        $DB->set_field('prog_message', 'triggertime', DAYSECS * 10, array('id' => $messageid));

        // Create course.
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        // Assign courses to cert.
        $coursesetdata = array(
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_CERT,
                'timeallowed' => DAYSECS * 100,
                'courses' => array($course1)
            ),
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_RECERT,
                'timeallowed' => DAYSECS * 100,
                'courses' => array($course2)
            ),
        );
        $this->programgenerator->legacy_add_coursesets_to_program($this->cert1, $coursesetdata);
        $params = array('programid' => $this->cert1->id, 'certifpath' => CERTIFPATH_CERT);
        $primarycoursesetid = $DB->get_field('prog_courseset', 'id', $params);
        $params = array('programid' => $this->cert1->id, 'certifpath' => CERTIFPATH_RECERT);
        $recertcoursesetid = $DB->get_field('prog_courseset', 'id', $params);

        // Assign users to program.
        $usersprogram = array($this->user1->id, $this->user2->id);
        $timebefore = time();
        $this->programgenerator->assign_program($this->cert1->id, $usersprogram);
        $timeafter = time();

        // Before we hack the course set due dates, make sure that they are currently valid.
        $this->assertEquals(2, $DB->count_records('prog_completion', array('userid' => $this->user1->id)));
        $this->assertEquals(1, $DB->count_records('prog_completion', array('userid' => $this->user1->id, 'coursesetid' => 0)));
        $coursesetcompletion = $DB->get_record('prog_completion', array('userid' => $this->user1->id, 'coursesetid' => $primarycoursesetid));
        $this->assertGreaterThanOrEqual($timebefore, $coursesetcompletion->timecreated);
        $this->assertLessThanOrEqual($timeafter, $coursesetcompletion->timecreated);
        $this->assertEquals($coursesetcompletion->timecreated + 100 * DAYSECS, $coursesetcompletion->timedue);

        // Hack the course set due dates, 20 days ago.
        $duedate = time() - DAYSECS * 20;
        $DB->set_field('prog_completion', 'timedue', $duedate,
            array('programid' => $this->cert1->id, 'userid' => $this->user1->id));
        $DB->set_field('prog_completion', 'timedue', $duedate,
            array('programid' => $this->cert1->id, 'userid' => $this->user2->id));

        // Before the test, make sure the correct program message logs exist.
        $this->assertEquals(0, $DB->count_records('prog_messagelog'));

        // First course set overdue message, primary path. Course set overdue messages go out 10 days after due date.
        $this->waitForSecond(); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start();
        $task = new send_messages_task();
        $this->sink->clear();
        $task->execute();
        $emails = $this->sink->get_messages();
        $this->sink->clear();
        ob_end_clean();

        // Check that both users were sent messages.
        $this->assertCount(2, $emails);

        // Check that both message logs exist.
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Mark user1 certified.
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course1->id));
        $completion->mark_complete();

        // Open the window for user1 - move all dates back nine months.
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user1->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 9;
        $certcompletion->timeexpires -= DAYSECS * 30 * 9;
        $certcompletion->timecompleted -= DAYSECS * 30 * 9;
        $progcompletion->timedue -= DAYSECS * 30 * 9;
        $progcompletion->timecompleted -= DAYSECS * 30 * 9;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        $certcron = new \totara_certification\task\update_certification_task();
        $timebefore = time();
        $certcron->execute();
        $timeafter = time();

        // Check that logs for both users are still there - they belong to the primary cert path, so shouldn't be touched.
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid, 'coursesetid' => $primarycoursesetid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid, 'coursesetid' => $primarycoursesetid)));

        // Before we hack the course set due dates, make sure that they are currently valid.
        $this->assertEquals(3, $DB->count_records('prog_completion', array('userid' => $this->user1->id)));
        $this->assertEquals(1, $DB->count_records('prog_completion', array('userid' => $this->user1->id, 'coursesetid' => 0)));
        $this->assertEquals(1, $DB->count_records('prog_completion', array('userid' => $this->user1->id, 'coursesetid' => $primarycoursesetid)));
        $coursesetcompletion = $DB->get_record('prog_completion', array('userid' => $this->user1->id, 'coursesetid' => $recertcoursesetid));
        $this->assertGreaterThanOrEqual($timebefore, $coursesetcompletion->timecreated);
        $this->assertLessThanOrEqual($timeafter, $coursesetcompletion->timecreated);
        $this->assertEquals($coursesetcompletion->timecreated + 100 * DAYSECS, $coursesetcompletion->timedue);

        // Hack the course set due dates again, 20 days ago.
        $duedate = time() - DAYSECS * 20;
        $DB->set_field('prog_completion', 'timedue', $duedate, array('userid' => $this->user1->id, 'coursesetid' => $recertcoursesetid));

        // Second course set overdue message, recert path. Course set overdue messages go out 10 days after due date.
        $this->waitForSecond(); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start();
        $task = new send_messages_task();
        $this->sink->clear();
        $task->execute();
        $emails = $this->sink->get_messages();
        $this->sink->clear();
        ob_end_clean();

        // Check that just user1 received a new message.
        $this->assertCount(1, $emails);

        // Check that we've added another record for the test user, for the recert path.
        $this->assertEquals(3, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid, 'coursesetid' => $primarycoursesetid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid, 'coursesetid' => $recertcoursesetid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid, 'coursesetid' => $primarycoursesetid)));

        // Mark user1 recertified.
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course2->id));
        $completion->mark_complete();

        // Reopen the window for user1 - move all dates back nine months.
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user1->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 9;
        $certcompletion->timeexpires -= DAYSECS * 30 * 9;
        $certcompletion->timecompleted -= DAYSECS * 30 * 9;
        $progcompletion->timedue -= DAYSECS * 30 * 9;
        $progcompletion->timecompleted -= DAYSECS * 30 * 9;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        $certcron = new \totara_certification\task\update_certification_task();
        $timebefore = time();
        $certcron->execute();
        $timeafter = time();

        // The test user's recert path log was removed.
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid, 'coursesetid' => $primarycoursesetid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid, 'coursesetid' => $primarycoursesetid)));

        // Before we hack the course set due dates, make sure that they are currently valid.
        $this->assertEquals(3, $DB->count_records('prog_completion', array('userid' => $this->user1->id)));
        $this->assertEquals(1, $DB->count_records('prog_completion', array('userid' => $this->user1->id, 'coursesetid' => 0)));
        $this->assertEquals(1, $DB->count_records('prog_completion', array('userid' => $this->user1->id, 'coursesetid' => $primarycoursesetid)));
        $coursesetcompletion = $DB->get_record('prog_completion', array('userid' => $this->user1->id, 'coursesetid' => $recertcoursesetid));
        $this->assertGreaterThanOrEqual($timebefore, $coursesetcompletion->timecreated);
        $this->assertLessThanOrEqual($timeafter, $coursesetcompletion->timecreated);
        $this->assertEquals($coursesetcompletion->timecreated + 100 * DAYSECS, $coursesetcompletion->timedue);

        // Hack the course set due dates again, 20 days ago.
        $duedate = time() - DAYSECS * 20;
        $DB->set_field('prog_completion', 'timedue', $duedate, array('userid' => $this->user1->id, 'coursesetid' => $recertcoursesetid));

        // Third course set overdue message, repeat on recert path. Course set overdue messages go out 10 days after due date.
        $this->waitForSecond(); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start();
        $task = new send_messages_task();
        $this->sink->clear();
        $task->execute();
        $emails = $this->sink->get_messages();
        $this->sink->clear();
        ob_end_clean();

        // Check that just user1 received a new message.
        $this->assertCount(1, $emails);

        // The test user's recert path log was created again.
        $this->assertEquals(3, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid, 'coursesetid' => $primarycoursesetid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid, 'coursesetid' => $recertcoursesetid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid, 'coursesetid' => $primarycoursesetid)));

        // To ensure that the control's recert message log record is not deleted, we'll create it artificially now.
        $messagelog = $DB->get_record('prog_messagelog', array('userid' => $this->user2->id));
        unset($messagelog->id);
        $messagelog->coursesetid = $recertcoursesetid;
        $DB->insert_record('prog_messagelog', $messagelog);

        // Expire the user - move all dates back an additional six month (on top of 9 months earlier) months.
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user1->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 6;
        $certcompletion->timeexpires -= DAYSECS * 30 * 6;
        $certcompletion->timecompleted -= DAYSECS * 30 * 6;
        $progcompletion->timedue -= DAYSECS * 30 * 6;
        // No progcompletion->timecompleted because the window is already open.
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        $certcron = new \totara_certification\task\update_certification_task();
        $timebefore = time();
        $certcron->execute();
        $timeafter = time();

        // Only user1's recert path log was removed (because we've only expired). User2's are both there.
        $this->assertEquals(3, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid, 'coursesetid' => $recertcoursesetid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid, 'coursesetid' => $primarycoursesetid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid, 'coursesetid' => $recertcoursesetid)));

        // Before we hack the course set due dates, make sure that they are currently valid.
        $this->assertEquals(3, $DB->count_records('prog_completion', array('userid' => $this->user1->id)));
        $this->assertEquals(1, $DB->count_records('prog_completion', array('userid' => $this->user1->id, 'coursesetid' => 0)));
        $this->assertEquals(1, $DB->count_records('prog_completion', array('userid' => $this->user1->id, 'coursesetid' => $recertcoursesetid)));
        $coursesetcompletion = $DB->get_record('prog_completion', array('userid' => $this->user1->id, 'coursesetid' => $primarycoursesetid));
        $this->assertGreaterThanOrEqual($timebefore, $coursesetcompletion->timecreated);
        $this->assertLessThanOrEqual($timeafter, $coursesetcompletion->timecreated);
        $this->assertEquals($coursesetcompletion->timecreated + 100 * DAYSECS, $coursesetcompletion->timedue);

        // Hack the course set due dates again, 20 days ago.
        $duedate = time() - DAYSECS * 20;
        $DB->set_field('prog_completion', 'timedue', $duedate, array('userid' => $this->user1->id, 'coursesetid' => $primarycoursesetid));

        // Fourth course set overdue message, repeat on primary path. Course set overdue messages go out 10 days after due date.
        $this->waitForSecond(); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start();
        $task = new send_messages_task();
        $this->sink->clear();
        $task->execute();
        $emails = $this->sink->get_messages();
        $this->sink->clear();
        ob_end_clean();

        // Check that just user1 received a new message.
        $this->assertCount(1, $emails);

        // The test user's primary path log was created again.
        $this->assertEquals(4, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid, 'coursesetid' => $primarycoursesetid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid, 'coursesetid' => $recertcoursesetid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid, 'coursesetid' => $primarycoursesetid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid, 'coursesetid' => $recertcoursesetid)));
    }

    /**
     * Make sure that recertification window open messages are resent when a user recertifies.
     */
    public function test_recertification_window_open_messages() {
        global $DB;

        // Set up the messages.
        $programmessagemanager = $this->cert1->get_messagesmanager();
        $programmessagemanager->delete();
        $programmessagemanager->add_message(MESSAGETYPE_RECERT_WINDOWOPEN);
        $programmessagemanager->save_messages();
        prog_messages_manager::get_program_messages_manager($this->cert1->id, true); // Causes static cache to be reset.
        $messageid = $DB->get_field('prog_message', 'id',
            array('programid' => $this->cert1->id, 'messagetype' => MESSAGETYPE_RECERT_WINDOWOPEN));

        // Create course.
        $course1 = $this->getDataGenerator()->create_course();

        // Assign courses to cert.
        $coursesetdata = array(
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_CERT,
                'courses' => array($course1)
            ),
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_RECERT,
                'courses' => array($course1)
            ),
        );
        $this->programgenerator->legacy_add_coursesets_to_program($this->cert1, $coursesetdata);

        // Assign users to program.
        $usersprogram = array($this->user1->id, $this->user2->id);
        $this->programgenerator->assign_program($this->cert1->id, $usersprogram);

        // Complete courses.
        $this->getDataGenerator()->enrol_user($this->user1->id, $course1->id);
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course1->id));
        $completion->mark_complete();
        $this->getDataGenerator()->enrol_user($this->user2->id, $course1->id);
        $completion = new completion_completion(array('userid' => $this->user2->id, 'course' => $course1->id));
        $completion->mark_complete();

        // Check that neither user was sent messages.
        $emails = $this->sink->get_messages();
        $this->assertCount(0, $emails);
        $this->sink->clear();

        // Check that no message logs exist.
        $this->assertEquals(0, $DB->count_records('prog_messagelog'));

        // Reopen the window for both users - move all dates back nine months.
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user1->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 9;
        $certcompletion->timeexpires -= DAYSECS * 30 * 9;
        $certcompletion->timecompleted -= DAYSECS * 30 * 9;
        $progcompletion->timedue -= DAYSECS * 30 * 9;
        $progcompletion->timecompleted -= DAYSECS * 30 * 9;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user2->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 9;
        $certcompletion->timeexpires -= DAYSECS * 30 * 9;
        $certcompletion->timecompleted -= DAYSECS * 30 * 9;
        $progcompletion->timedue -= DAYSECS * 30 * 9;
        $progcompletion->timecompleted -= DAYSECS * 30 * 9;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        $certcron = new \totara_certification\task\update_certification_task();
        $certcron->execute();

        // Check that two new messages were sent.
        $emails = $this->sink->get_messages();
        $this->assertCount(2, $emails);
        $this->sink->clear();

        // Check that there is one each.
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Recertify user1.
        $this->getDataGenerator()->enrol_user($this->user1->id, $course1->id);
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course1->id));
        $completion->mark_complete();

        // Check that neither user was sent messages.
        $emails = $this->sink->get_messages();
        $this->assertCount(0, $emails);
        $this->sink->clear();

        // Check that there is still one each.
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Reopen the window for user1 - move all dates back nine months.
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user1->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 9;
        $certcompletion->timeexpires -= DAYSECS * 30 * 9;
        $certcompletion->timecompleted -= DAYSECS * 30 * 9;
        $progcompletion->timedue -= DAYSECS * 30 * 9;
        $progcompletion->timecompleted -= DAYSECS * 30 * 9;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        $certcron = new \totara_certification\task\update_certification_task();
        $certcron->execute();

        // Check that one message was received.
        $emails = $this->sink->get_messages();
        $this->assertCount(1, $emails);
        $this->sink->clear();

        // Check that there is still one each (user2's is old, user1's is new).
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));
    }

    /**
     * Make sure that recertification window due to close messages are resent when a user recertifies.
     */
    public function test_recertification_window_due_to_close_messages() {
        global $DB;

        // Set up the messages.
        $programmessagemanager = $this->cert1->get_messagesmanager();
        $programmessagemanager->delete();
        $programmessagemanager->add_message(MESSAGETYPE_RECERT_WINDOWDUECLOSE);
        $programmessagemanager->save_messages();
        prog_messages_manager::get_program_messages_manager($this->cert1->id, true); // Causes static cache to be reset.
        $messageid = $DB->get_field('prog_message', 'id',
            array('programid' => $this->cert1->id, 'messagetype' => MESSAGETYPE_RECERT_WINDOWDUECLOSE));
        // Hack the message record to be triggered 100 days before due.
        $DB->set_field('prog_message', 'triggertime', DAYSECS * 100, array('id' => $messageid));

        // Create course.
        $course1 = $this->getDataGenerator()->create_course();

        // Assign courses to cert.
        $coursesetdata = array(
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_CERT,
                'courses' => array($course1)
            ),
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_RECERT,
                'courses' => array($course1)
            ),
        );
        $this->programgenerator->legacy_add_coursesets_to_program($this->cert1, $coursesetdata);

        // Assign users to program.
        $usersprogram = array($this->user1->id, $this->user2->id);
        $this->programgenerator->assign_program($this->cert1->id, $usersprogram);

        // Complete courses.
        $this->getDataGenerator()->enrol_user($this->user1->id, $course1->id);
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course1->id));
        $completion->mark_complete();
        $this->getDataGenerator()->enrol_user($this->user2->id, $course1->id);
        $completion = new completion_completion(array('userid' => $this->user2->id, 'course' => $course1->id));
        $completion->mark_complete();

        // Check that neither user was sent messages.
        $emails = $this->sink->get_messages();
        $this->assertCount(0, $emails);
        $this->sink->clear();

        // Check that no message logs exist.
        $this->assertEquals(0, $DB->count_records('prog_messagelog'));

        // Reopen the window for both users - move all dates back nine months.
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user1->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 9;
        $certcompletion->timeexpires -= DAYSECS * 30 * 9;
        $certcompletion->timecompleted -= DAYSECS * 30 * 9;
        $progcompletion->timedue -= DAYSECS * 30 * 9;
        $progcompletion->timecompleted -= DAYSECS * 30 * 9;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user2->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 9;
        $certcompletion->timeexpires -= DAYSECS * 30 * 9;
        $certcompletion->timecompleted -= DAYSECS * 30 * 9;
        $progcompletion->timedue -= DAYSECS * 30 * 9;
        $progcompletion->timecompleted -= DAYSECS * 30 * 9;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        $certcron = new \totara_certification\task\update_certification_task();
        $certcron->execute();

        // Check that both users were sent messages.
        $emails = $this->sink->get_messages();
        $this->assertCount(2, $emails);
        $this->sink->clear();

        // Check that both message logs exist.
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Mark user1 certified.
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course1->id));
        $completion->mark_complete();

        // Attempt to send any program messages.
        $this->waitForSecond(); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new send_messages_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.

        // Check that no new messages were sent.
        $emails = $this->sink->get_messages();
        $this->assertCount(0, $emails);
        $this->sink->clear();

        // Check that both message logs still exist.
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Reopen the window for user1 - move all dates back nine months.
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user1->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 9;
        $certcompletion->timeexpires -= DAYSECS * 30 * 9;
        $certcompletion->timecompleted -= DAYSECS * 30 * 9;
        $progcompletion->timedue -= DAYSECS * 30 * 9;
        $progcompletion->timecompleted -= DAYSECS * 30 * 9;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        $certcron = new \totara_certification\task\update_certification_task();
        $certcron->execute();

        // Check that one message was received.
        $emails = $this->sink->get_messages();
        $this->assertCount(1, $emails);
        $this->sink->clear();

        // Check that there is still one each (user2's is old, user1's is new).
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));
    }

    /**
     * Make sure that recertification failure to recertify messages are resent on subsequent recertifications.
     *
     * Sequence is: assign, certify, fail to recertify on time (message #1), recertify, fail to recertify on time (message #2)
     */
    public function test_recertification_failure_to_recertify_messages() {
        global $DB;

        // Set up the messages.
        $programmessagemanager = $this->cert1->get_messagesmanager();
        $programmessagemanager->delete();
        $programmessagemanager->add_message(MESSAGETYPE_RECERT_FAILRECERT);
        $programmessagemanager->save_messages();
        prog_messages_manager::get_program_messages_manager($this->cert1->id, true); // Causes static cache to be reset.
        $messageid = $DB->get_field('prog_message', 'id',
            array('programid' => $this->cert1->id, 'messagetype' => MESSAGETYPE_RECERT_FAILRECERT));

        // Create course.
        $course1 = $this->getDataGenerator()->create_course();

        // Assign courses to cert.
        $coursesetdata = array(
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_CERT,
                'courses' => array($course1)
            ),
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_RECERT,
                'courses' => array($course1)
            ),
        );
        $this->programgenerator->legacy_add_coursesets_to_program($this->cert1, $coursesetdata);

        // Assign users to program.
        $usersprogram = array($this->user1->id, $this->user2->id);
        $this->programgenerator->assign_program($this->cert1->id, $usersprogram);

        // Complete courses.
        $this->getDataGenerator()->enrol_user($this->user1->id, $course1->id);
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course1->id));
        $completion->mark_complete();
        $this->getDataGenerator()->enrol_user($this->user2->id, $course1->id);
        $completion = new completion_completion(array('userid' => $this->user2->id, 'course' => $course1->id));
        $completion->mark_complete();

        // Check that neither user was sent messages.
        $emails = $this->sink->get_messages();
        $this->assertCount(0, $emails);
        $this->sink->clear();

        // Check that no message logs exist.
        $this->assertEquals(0, $DB->count_records('prog_messagelog'));

        // Reopen the window for both users - move all dates back nine months.
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user1->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 9;
        $certcompletion->timeexpires -= DAYSECS * 30 * 9;
        $certcompletion->timecompleted -= DAYSECS * 30 * 9;
        $progcompletion->timedue -= DAYSECS * 30 * 9;
        $progcompletion->timecompleted -= DAYSECS * 30 * 9;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user2->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 9;
        $certcompletion->timeexpires -= DAYSECS * 30 * 9;
        $certcompletion->timecompleted -= DAYSECS * 30 * 9;
        $progcompletion->timedue -= DAYSECS * 30 * 9;
        $progcompletion->timecompleted -= DAYSECS * 30 * 9;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        $certcron = new \totara_certification\task\update_certification_task();
        $certcron->execute();

        // Check that neither user was sent messages.
        $emails = $this->sink->get_messages();
        $this->assertCount(0, $emails);
        $this->sink->clear();

        // Check that no message logs exist.
        $this->assertEquals(0, $DB->count_records('prog_messagelog'));

        // Fail to recertify on time for both users - move all dates back another six months (total 15).
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user1->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 6;
        $certcompletion->timeexpires -= DAYSECS * 30 * 6;
        $certcompletion->timecompleted -= DAYSECS * 30 * 6;
        $progcompletion->timedue -= DAYSECS * 30 * 6;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user2->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 6;
        $certcompletion->timeexpires -= DAYSECS * 30 * 6;
        $certcompletion->timecompleted -= DAYSECS * 30 * 6;
        $progcompletion->timedue -= DAYSECS * 30 * 6;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        $certcron = new \totara_certification\task\update_certification_task();
        $certcron->execute();

        // Check that two new messages were sent.
        $emails = $this->sink->get_messages();
        $this->assertCount(2, $emails);
        $this->sink->clear();

        // Check that there is one each.
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Recertify user1.
        $this->getDataGenerator()->enrol_user($this->user1->id, $course1->id);
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course1->id));
        $completion->mark_complete();

        // Check that neither user was sent messages.
        $emails = $this->sink->get_messages();
        $this->assertCount(0, $emails);
        $this->sink->clear();

        // Check that there is still one each.
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Reopen the window for user1 - move all dates back nine months.
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user1->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 9;
        $certcompletion->timeexpires -= DAYSECS * 30 * 9;
        $certcompletion->timecompleted -= DAYSECS * 30 * 9;
        $progcompletion->timedue -= DAYSECS * 30 * 9;
        $progcompletion->timecompleted -= DAYSECS * 30 * 9;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        $certcron = new \totara_certification\task\update_certification_task();
        $certcron->execute();

        // Check that neither user was sent messages.
        $emails = $this->sink->get_messages();
        $this->assertCount(0, $emails);
        $this->sink->clear();

        // Check that user2 still has their message log, but user1's was deleted.
        $this->assertEquals(1, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Fail to recertify for user1 - move all dates back another six months (total 15).
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user1->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 6;
        $certcompletion->timeexpires -= DAYSECS * 30 * 6;
        $certcompletion->timecompleted -= DAYSECS * 30 * 6;
        $progcompletion->timedue -= DAYSECS * 30 * 6;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        $certcron = new \totara_certification\task\update_certification_task();
        $certcron->execute();

        // Check that one message was received.
        $emails = $this->sink->get_messages();
        $this->assertCount(1, $emails);
        $this->sink->clear();

        // Check that there is still one each (user2's is old, user1's is new).
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));
    }

    /**
     * Make sure that certification learner followup messages are resent after learner has certified and time has passed.
     */
    public function test_certification_learner_followup_messages() {
        global $DB;

        // Set up the messages.
        $programmessagemanager = $this->cert1->get_messagesmanager();
        $programmessagemanager->delete();
        $programmessagemanager->add_message(MESSAGETYPE_LEARNER_FOLLOWUP);
        $programmessagemanager->save_messages();
        prog_messages_manager::get_program_messages_manager($this->cert1->id, true); // Causes static cache to be reset.
        $messageid = $DB->get_field('prog_message', 'id',
            array('programid' => $this->cert1->id, 'messagetype' => MESSAGETYPE_LEARNER_FOLLOWUP));
        // Hack the message record to be triggered 10 days after completion.
        $DB->set_field('prog_message', 'triggertime', DAYSECS * 10, array('id' => $messageid));

        // Create course.
        $course1 = $this->getDataGenerator()->create_course();

        // Assign courses to cert.
        $coursesetdata = array(
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_CERT,
                'courses' => array($course1)
            ),
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_RECERT,
                'courses' => array($course1)
            ),
        );
        $this->programgenerator->legacy_add_coursesets_to_program($this->cert1, $coursesetdata);

        // Assign users to program.
        $usersprogram = array($this->user1->id, $this->user2->id);
        $this->programgenerator->assign_program($this->cert1->id, $usersprogram);

        // Assign users and complete some courses.
        $this->getDataGenerator()->enrol_user($this->user1->id, $course1->id);
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course1->id));
        $completion->mark_complete();
        $this->getDataGenerator()->enrol_user($this->user2->id, $course1->id);
        $completion = new completion_completion(array('userid' => $this->user2->id, 'course' => $course1->id));
        $completion->mark_complete();

        // Attempt to send any program messages.
        $this->waitForSecond(); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new send_messages_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.

        // Check that no users were sent messages.
        $emails = $this->sink->get_messages();
        $this->assertCount(0, $emails);
        $this->sink->clear();

        // Check that no message logs exist.
        $this->assertEquals(0, $DB->count_records('prog_messagelog'));

        // Move all dates one month back to trigger the follow-up message.
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user1->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 1;
        $certcompletion->timeexpires -= DAYSECS * 30 * 1;
        $certcompletion->timecompleted -= DAYSECS * 30 * 1;
        $progcompletion->timedue -= DAYSECS * 30 * 1;
        $progcompletion->timecompleted -= DAYSECS * 30 * 1;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user2->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 1;
        $certcompletion->timeexpires -= DAYSECS * 30 * 1;
        $certcompletion->timecompleted -= DAYSECS * 30 * 1;
        $progcompletion->timedue -= DAYSECS * 30 * 1;
        $progcompletion->timecompleted -= DAYSECS * 30 * 1;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        $certcron = new \totara_certification\task\update_certification_task();
        $certcron->execute();

        // Attempt to send any program messages.
        $this->waitForSecond(); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new send_messages_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.

        // Check that both messages were sent.
        $emails = $this->sink->get_messages();
        $this->assertCount(2, $emails);
        $this->sink->clear();

        // Check that both message logs exist.
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Reopen the window for user1 - move all dates back eight months.
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user1->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 8;
        $certcompletion->timeexpires -= DAYSECS * 30 * 8;
        $certcompletion->timecompleted -= DAYSECS * 30 * 8;
        $progcompletion->timedue -= DAYSECS * 30 * 8;
        $progcompletion->timecompleted -= DAYSECS * 30 * 8;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        $certcron = new \totara_certification\task\update_certification_task();
        $certcron->execute();

        // Attempt to send any program messages.
        $this->waitForSecond(); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new send_messages_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.

        // Check that no new messages were sent.
        $emails = $this->sink->get_messages();
        $this->assertCount(0, $emails);
        $this->sink->clear();

        // Check that only the log for user2 is still there - the log for user1 was deleted when their window opened.
        $this->assertEquals(1, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Complete the cert again for user1.
        $this->getDataGenerator()->enrol_user($this->user1->id, $course1->id);
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course1->id));
        $completion->mark_complete();

        // Attempt to send any program messages.
        $this->waitForSecond(); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new send_messages_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.

        // Check that no new messages were sent.
        $emails = $this->sink->get_messages();
        $this->assertCount(0, $emails);
        $this->sink->clear();

        // Check that just user2 still has the message log.
        $this->assertEquals(1, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Move all dates one month back to trigger the follow-up message.
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user1->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 1;
        $certcompletion->timeexpires -= DAYSECS * 30 * 1;
        $certcompletion->timecompleted -= DAYSECS * 30 * 1;
        $progcompletion->timedue -= DAYSECS * 30 * 1;
        $progcompletion->timecompleted -= DAYSECS * 30 * 1;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        $certcron = new \totara_certification\task\update_certification_task();
        $certcron->execute();

        // Attempt to send any program messages.
        $this->waitForSecond(); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new send_messages_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.

        // Check that one new message was sent.
        $emails = $this->sink->get_messages();
        $this->assertCount(1, $emails);
        $this->sink->clear();

        // Check that there is still one each (user2's is old, user1's is new).
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));
    }

    /**
     * Check config setting "program_message_prevent_resend_on_schedule_change".
     */
    public function test_prevent_resend_on_schedule_change(): void {
        global $DB, $CFG;

        // Set up the due message for cert1.
        $message_manager = $this->cert1->get_messagesmanager();
        $message_manager->delete();
        $message_manager->add_message(MESSAGETYPE_PROGRAM_DUE);
        $message_manager->save_messages();

        // Adjust the message content.
        $due_message = $DB->get_record('prog_message', ['programid' => $this->cert1->id, 'messagetype' => MESSAGETYPE_PROGRAM_DUE]);
        $due_message->messagesubject = 'Learner ProgDue Message';
        $due_message->mainmessage = 'Hey dude, do your program';
        $due_message->notifymanager = 0;
        $DB->update_record('prog_message', $due_message);

        // Update the message record to be triggered 100 days before due.
        $message_id = $due_message->id;
        $triggertime = DAYSECS * 100;
        $DB->set_field('prog_message', 'triggertime', $triggertime, ['id' => $message_id]);
        prog_messages_manager::get_program_messages_manager($this->cert1->id, true); // Causes static cache to be reset.

        // Assign users to certification.
        $this->programgenerator->assign_program($this->cert1->id, [$this->user1->id]);

        // Adjust the due date, 10 days from now.
        $duedate = time() + DAYSECS * 10;
        $progcompl1 = prog_load_completion($this->cert1->id, $this->user1->id);
        $progcompl1->timedue = $duedate;
        $this->assertTrue(prog_write_completion($progcompl1));

        // Attempt to send any program messages.
        $this->waitForSecond(); // Messages are only sent if they were created before "now", so we need to wait for the next second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new send_messages_task();
        $task->execute();
        ob_end_clean();

        $emails = $this->sink->get_messages();
        $this->assertCount(1, $emails);
        $this->sink->clear();
        $email = reset($emails);
        $this->assertEquals($email->subject, 'Learner ProgDue Message');
        $this->assertEquals($email->fullmessage, 'Hey dude, do your program');

        // Check message log.
        $this->assertEquals(1, $DB->count_records('prog_messagelog', ['userid' => $this->user1->id, 'messageid' => $message_id]));

        // Changing the schedule should remove the message log entry and resend the message.
        $due_message_obj = new prog_program_due_message($due_message->id, $due_message);
        $due_message_obj->triggertime = $triggertime - 1;
        $due_message_obj->save_message();

        $this->assertEquals(0, $DB->count_records('prog_messagelog', ['userid' => $this->user1->id, 'messageid' => $message_id]));
        ob_start();
        $task = new send_messages_task();
        $task->execute();
        ob_end_clean();

        $emails = $this->sink->get_messages();
        $this->assertCount(1, $emails);
        $this->sink->clear();
        $email = reset($emails);
        $this->assertEquals($email->subject, 'Learner ProgDue Message');
        $this->assertEquals($email->fullmessage, 'Hey dude, do your program');
        $this->assertEquals(1, $DB->count_records('prog_messagelog', ['userid' => $this->user1->id, 'messageid' => $message_id]));

        // Change the schedule again, but this time configure it to prevent resending.
        $CFG->program_message_prevent_resend_on_schedule_change = true;
        $due_message_obj = new prog_program_due_message($due_message->id, $due_message);
        $due_message_obj->triggertime = $triggertime - 2;
        $due_message_obj->save_message();

        $this->assertEquals(1, $DB->count_records('prog_messagelog', ['userid' => $this->user1->id, 'messageid' => $message_id]));
        ob_start();
        $task = new send_messages_task();
        $task->execute();
        ob_end_clean();

        $emails = $this->sink->get_messages();
        $this->assertCount(0, $emails);

        unset($CFG->program_message_prevent_resend_on_schedule_change);
    }
}
