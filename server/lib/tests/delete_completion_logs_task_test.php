<?php
/**
 * This file is part of Totara Core
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package core
 */

defined('MOODLE_INTERNAL') || die();

use core_phpunit\testcase;
use totara_certification\task\update_certification_task;
use core\task\delete_completion_logs_task;

/** @var \core_config $CFG */
global $CFG;
require_once($CFG->dirroot . '/completion/cron.php');
require_once($CFG->dirroot . '/mod/certificate/locallib.php');
require_once($CFG->dirroot . '/completion/criteria/completion_criteria_activity.php');

/**
 * @group core
 * @cover core\task\delete_completion_logs_task
 */
class core_delete_completion_logs_task_testcase extends testcase {

    private const COMPLETION_TEST_COURSES_CREATED = 3;

    protected function setUp(): void {
        /** @var core_config $CFG */
        global $CFG;
        $CFG->enablecompletion = true;
    }

    public function test_course_completion_logs_task(): void {
        /** @var moodle_database $DB */
        /** @var core_config $CFG */
        global $DB, $CFG;
        // Lets test before running course completion there are no records.
        $this->assertEquals(0, $DB->count_records('course_completion_log'));

        $this->run_course_completion();

        // Lets test after running course completion there are some records.
        $this->assertEquals(30, $DB->count_records('course_completion_log'));

        // Test the "delete completion log" setting is not setup.
        // No deletion, still should be 30 records
        $CFG->deletecompletionlogs = null;
        // Run the task.
        $output = $this->run_task();
        // Test
        $this->assertEmpty($output, "Deleted old course completion log records from 'course_completion_log'");
        $this->assertEquals(30, $DB->count_records('course_completion_log'));

        // The current setting under server/admin/settings/server.php
        /**
         * 7 years => 365 * 7 => new lang_string('numyears', 'moodle', 7),
         * 5 years => 365 * 5 => new lang_string('numyears', 'moodle', 5),
         * 3 years => 365 * 3 => new lang_string('numyears', 'moodle', 3),
         * 1 year  => 365 * 1 => new lang_string('numyear', 'moodle', 1),
         */
        // Set to delete the records which are more then 1 year old
        $CFG->deletecompletionlogs = 365 * 1;
        // Test nothing to delete yet, all records are current
        // No deletion, still should be 30 records
        // Run the task.
        $output = $this->run_task();
        // Test
        $this->assertEquals(30, $DB->count_records('course_completion_log'));

        // Lest make last 5 records 3 years old
        $records = $DB->get_records('course_completion_log', null, '', 'id, timemodified', 25, 5); // get last 5 records
        foreach ($records as $record) {
            $new_timemodified = $record->timemodified - (365 * 3 * 3600 * 24);
            // Update the records to make them 3 years old.
            $DB->update_record('course_completion_log', ['id' => $record->id, 'timemodified' => $new_timemodified]);
        }
        // Test to delete 5 records above, our $CFG->deletecompletionlogs is still 1 year, see above.
        // Run the task.
        $output = $this->run_task();
        // Test
        $this->assertStringContainsString(trim($output), "Deleted old course completion log records from 'course_completion_log'");
        $this->assertEquals(25, $DB->count_records('course_completion_log'));
    }

    /**
     * All below and get_completion_data() function are the setup from the course completion test
     * @see server/completion/tests/course_completion_test.php
     *
     * @return void
     */
    private function run_course_completion(): void {
        global $DB;

        $data = $this->get_completion_data();

        $this->setAdminUser();

        // Add user1 to course2 - this will also be reset when the window opens.
        $this->getDataGenerator()->enrol_user($data->user1->id, $data->course2->id);

        // Make all users complete in their courses by viewing the certificates.
        for ($i = 1; $i <= self::COMPLETION_TEST_COURSES_CREATED; $i++) {
            $courseid = $data->{"course".$i}->id;
            $coursecontext = context_course::instance($courseid);
            foreach ($data->users as $user) {
                if (!is_enrolled($coursecontext, $user->id)) {
                    continue;
                }
                // Create a certificate for the user - this replicates a user going to mod/certificate/view.php.
                certificate_get_issue($data->{"course".$i}, $user, $data->{"certificate".$i}, $data->{"coursemodule".$i});
                // Complete the certificate.
                $data->{"completioninfo".$i}->set_module_viewed($data->{"coursemodule".$i}, $user->id);
            }
        }
        // When marking certificates complete using "set_module_viewed()" (above), it used the current date as
        // completion date. To cause window open, we need to move the window open date backwards. Also move
        // timecompleted backwards to prevent certification validation errors.
        $backsecs = 30 * DAYSECS;
        $DB->execute('UPDATE {certif_completion}
                         SET timewindowopens = timewindowopens - ' . $backsecs .',
                             timecompleted = timecompleted - ' . $backsecs);
        $DB->execute('UPDATE {prog_completion}
                         SET timecompleted = timecompleted - ' . $backsecs);

        // Run the cron.
        $certcron = new update_certification_task();
        $certcron->execute();
    }

    /** This setUp will create: three users (user1, user2, user3), three courses (course1, course2, course3),
     *  one certification program with course1 as certification content path and course2 as re-certification path.
     *  Each course will have a certificate activity which will be used as a criterion for completion.
     *  The enrollments will be as follow:
     *  user1 will be enrolled to course1 and course2 via certification program and course3 via manual,
     *  user2 will be enrolled to course1 and course2 via certification program and
     *  user3 will be enrolled to the course1 and course3 via manual.
     *
     *  @return stdClass
     */
    private function get_completion_data() {
        $generator = $this->getDataGenerator();
        $program_generator = $generator->get_plugin_generator('totara_program');

        // Create three users.
        $data = new \stdClass();
        $data->user1 = $generator->create_user();
        $data->user2 = $generator->create_user();
        $data->user3 = $generator->create_user();
        $data->users = [$data->user1, $data->user2, $data->user3];
        // Set default settings for courses.
        $coursedefaults = [
            'enablecompletion' => COMPLETION_ENABLED,
            'completionstartonenrol' => 1,
            'completionprogressonview' => 1
        ];
        // Create three courses
        for ($i = 1; $i <= self::COMPLETION_TEST_COURSES_CREATED; $i++) {
            $data->{"course".$i} = $generator->create_course($coursedefaults, ['createsections' => true]);
            $data->{"completioninfo".$i} = new completion_info($data->{"course".$i});
        }
        // Assign a certificate activity to each course. Could be any other activity. It's necessary for the criteria completion.
        $completiondefaults = [
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionview' => COMPLETION_VIEW_REQUIRED
        ];
        for ($i = 1; $i <= self::COMPLETION_TEST_COURSES_CREATED; $i++) {
            $courseid = $data->{"course".$i}->id;
            $data->{"certificate".$i} = $generator->create_module('certificate', ['course' => $courseid], $completiondefaults);
            $data->{"coursemodule".$i} = get_coursemodule_from_instance('certificate', $data->{"certificate".$i}->id, $courseid);
        }
        // Create completion based on the certificate activity that each course has.
        /* @var \core_completion\testing\generator $completion_generator */
        $completion_generator = $generator->get_plugin_generator('core_completion');
        for ($i = 1; $i <= self::COMPLETION_TEST_COURSES_CREATED; $i++) {
            $completion_generator->enable_completion_tracking($data->{"course".$i});
            $completion_generator->set_activity_completion($data->{"course".$i}->id, [$data->{"certificate".$i}]);
        }
        // Create a certification program.
        $certdata = array(
            'activeperiod' => '3 day',
            'windowperiod' => '3 day',
            'recertifydatetype' => CERTIFRECERT_COMPLETION,
            'timemodified' => time(),
            'fullname' => 'Certification Program1',
            'shortname' => 'CP1',
        );
        $programid = $program_generator->create_certification($certdata);
        $data->program = new \program($programid);
        // Add course1 and course2 as part of the certification's content.
        $program_generator->add_courses_and_courseset_to_program($data->program, [[$data->course1]], CERTIFPATH_CERT);
        $program_generator->add_courses_and_courseset_to_program($data->program, [[$data->course2]], CERTIFPATH_RECERT);

        $sink = $this->redirectMessages();
        // Enrol user1 and user2 to the certification program.
        $program_generator->assign_program($data->program->id, [$data->user1->id, $data->user2->id]);
        $sink->close();

        // Enrol user1, user2 and user3 to the course1 ... and user1 and user3 to course3 (via manual).
        $generator->enrol_user($data->user1->id, $data->course1->id);
        $generator->enrol_user($data->user2->id, $data->course1->id);
        $generator->enrol_user($data->user3->id, $data->course1->id);
        $generator->enrol_user($data->user1->id, $data->course3->id);
        $generator->enrol_user($data->user3->id, $data->course3->id);

        return $data;
    }

    private function run_task() {
        $sink = $this->redirectEvents();
        $task = new delete_completion_logs_task();
        ob_start();
        $task->execute();
        $output = ob_get_contents();
        ob_end_clean();
        $sink->close();
        return $output;
    }
}
