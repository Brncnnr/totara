<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package core
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/mod/facetoface/lib.php');

/**
 * Class totara_core_courselib_testcase
 *
 * This tests functions in the course/lib.php file, particularly those added by Totara. It is here because
 * there is an existing courselib_test file. Also we can avoid merge conflicts this way.
 */
class totara_core_courselib_testcase extends advanced_testcase {

    /** @var \core\testing\generator $generator */
    private $data_generator;

    /** @var \core_grades\testing\generator $grade_generator */
    private $grade_generator;

    /** @var \core_completion\testing\generator $completion_generator */
    private $completion_generator;

    /** @var \mod_facetoface\testing\generator $facetoface_generator */
    private $facetoface_generator;

    /** @var \core_phpunit\message_sink $messagesink */
    private $messagesink;

    private $user1, $user2, $user3, $user4, $user5, $user6;

    public function setUp(): void {
        parent::setUp();

        // Ignore messages and silence debug output in cron.
        $this->messagesink = $this->redirectMessages();

        set_config('enablecompletion', 1);

        $this->data_generator = $this->getDataGenerator();
        $this->grade_generator = $this->data_generator->get_plugin_generator('core_grades');
        $this->completion_generator = $this->data_generator->get_plugin_generator('core_completion');
        $this->facetoface_generator = $this->data_generator->get_plugin_generator('mod_facetoface');

        $this->user1 = $this->data_generator->create_user();
        $this->user2 = $this->data_generator->create_user();
        $this->user3 = $this->data_generator->create_user();
        $this->user4 = $this->data_generator->create_user();
        $this->user5 = $this->data_generator->create_user();
        $this->user6 = $this->data_generator->create_user();
    }

    protected function tearDown(): void {
        $this->messagesink->close();
        $this->messagesink = null;
        $this->data_generator = null;
        $this->grade_generator = null;
        $this->completion_generator = null;
        $this->facetoface_generator = null;
        $this->user1 = $this->user2 = $this->user3 = $this->user4 = $this->user5 = $this->user6 = null;

        parent::tearDown();
    }

    /**
     * Create several activities to add to the course.
     *
     * About the choice of activities:
     * There's several factors that come into play when resetting activities in
     * archive_course_activities that determine how completion is processed.
     * These are if it supports FEATURE_ARCHIVE_COMPLETION,
     * FEATURE_COMPLETION_TRACKS_VIEWS and whether it has a _grade_item_update function.
     * So the activities were chosen for the following reasons:
     *
     * facetoface - supports FEATURE_ARCHIVE_COMPLETION.
     * url - doesn't support FEATURE_ARCHIVE_COMPLETION but does support FEATURE_COMPLETION_TRACKS_VIEWS.
     * label - doesn't support FEATURE_ARCHIVE_COMPLETION nor FEATURE_COMPLETION_TRACKS_VIEWS.
     * All of the activities that have _grade_item_update functions currently also support
     * FEATURE_ARCHIVE_COMPLETION, which means none will arrive at the _grade_item_update function,
     * so it's not necessary to account for here.
     */
    private function set_up_activities_for_course($course) {
        // Face-to-face.
        $facetofacedata = array(
            'name' => 'facetoface1',
            'course' => $course->id
        );
        $f2fmoduleoptions = array(
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionstatusrequired' => json_encode(array(\mod_facetoface\signup\state\fully_attended::get_code()))
        );
        $facetoface = $this->facetoface_generator->create_instance($facetofacedata, $f2fmoduleoptions);

        // Session will be moved to the past after signups
        $sessiondate = new stdClass();
        $sessiondate->timestart = time() + DAYSECS;
        $sessiondate->timefinish = time() + DAYSECS + 60;
        $sessiondate->sessiontimezone = 'Pacific/Auckland';
        $sessiondata = array(
            'facetoface' => $facetoface->id,
            'capacity' => 10,
            'allowoverbook' => 1,
            'sessiondates' => array($sessiondate),
            'mincapacity' => '4',
            'cutoff' => "86400"
        );
        $sessionid = $this->facetoface_generator->add_session($sessiondata);
        $seminarevent = new \mod_facetoface\seminar_event($sessionid);

        // URL.
        $url = $this->data_generator->create_module('url', ['course' => $course->id, 'completion' => COMPLETION_TRACKING_AUTOMATIC, 'completionview' => COMPLETION_VIEW_REQUIRED]);

        // Label.
        $label = $this->data_generator->create_module('label', ['course' => $course->id, 'completion' => COMPLETION_TRACKING_MANUAL]);

        // Assignment.
        $assign = $this->data_generator->create_module('assign', ['course' => $course->id, 'completion' => COMPLETION_TRACKING_AUTOMATIC, 'completionusegrade' => 1, 'gradepass' => 50]);

        $this->completion_generator->set_activity_completion($course->id, [$facetoface, $url, $label, $assign], COMPLETION_AGGREGATION_ANY);

        return ['seminar' => $facetoface, 'event' => $seminarevent, 'url' => $url, 'label' => $label, 'assign' => $assign];
    }

    /**
     * User1 will complete the f2f only.
     * User2 will complete the url only.
     * User3 will complete the label only.
     * User4 will complete all activities.
     * User5 will complete none.
     * User6 will complete all activities (user4 can have activities archived, while user6 doesn't).
     *
     * @param       $course
     * @param array $modules
     */
    private function users_complete_modules($course, array $modules) {
        global $DB;

        $this->data_generator->enrol_user($this->user1->id, $course->id);
        $this->data_generator->enrol_user($this->user2->id, $course->id);
        $this->data_generator->enrol_user($this->user3->id, $course->id);
        $this->data_generator->enrol_user($this->user4->id, $course->id);
        $this->data_generator->enrol_user($this->user5->id, $course->id);
        $this->data_generator->enrol_user($this->user6->id, $course->id);

        // First check no one is yet complete for course and certs.
        $course1info = new completion_info($course);//var_dump($course1info->get_completions($this->user1->id, COMPLETION_CRITERIA_TYPE_ACTIVITY));
        $this->assertFalse($course1info->is_course_complete($this->user1->id));
        $this->assertFalse($course1info->is_course_complete($this->user2->id));
        $this->assertFalse($course1info->is_course_complete($this->user3->id));
        $this->assertFalse($course1info->is_course_complete($this->user4->id));
        $this->assertFalse($course1info->is_course_complete($this->user5->id));
        $this->assertFalse($course1info->is_course_complete($this->user6->id));

        // User1, 4 and 6 attend the facetoface.
        \mod_facetoface\signup_helper::signup(\mod_facetoface\signup::create($this->user1->id, $modules['event']));
        \mod_facetoface\signup_helper::signup(\mod_facetoface\signup::create($this->user4->id, $modules['event']));
        \mod_facetoface\signup_helper::signup(\mod_facetoface\signup::create($this->user6->id, $modules['event']));

        $sessiondate = new stdClass();
        $sessiondate->timestart = time() - DAYSECS;
        $sessiondate->timefinish = time() - DAYSECS + 60;
        $sessiondate->sessiontimezone = 'Pacific/Auckland';
        \mod_facetoface\seminar_event_helper::merge_sessions($modules['event'], [$sessiondate]);

        $f2fsignups =
            $DB->get_records('facetoface_signups', ['sessionid' => $modules['event']->get_id()], '', 'userid, id');
        $attendancedata = [
            $f2fsignups[$this->user1->id]->id => \mod_facetoface\signup\state\fully_attended::get_code(),
            $f2fsignups[$this->user4->id]->id => \mod_facetoface\signup\state\fully_attended::get_code(),
            $f2fsignups[$this->user6->id]->id => \mod_facetoface\signup\state\fully_attended::get_code()
        ];
        \mod_facetoface\signup_helper::process_attendance($modules['event'], $attendancedata);

        ob_start();
        $completion_task = new \core\task\completion_regular_task();
        $completion_task->execute();
        ob_end_clean();

        // Checking that only those who attended are marked complete so far.
        $course1info = new completion_info($course);
        $this->assertTrue($course1info->is_course_complete($this->user1->id));
        $this->assertFalse($course1info->is_course_complete($this->user2->id));
        $this->assertFalse($course1info->is_course_complete($this->user3->id));
        $this->assertTrue($course1info->is_course_complete($this->user4->id));
        $this->assertFalse($course1info->is_course_complete($this->user5->id));
        $this->assertTrue($course1info->is_course_complete($this->user6->id));

        // Users 2, 4 and 6 view the url activity.
        $urlmodulerecord = $DB->get_record('course_modules', array('id' => $modules['url']->cmid));
        $course1info->set_module_viewed($urlmodulerecord, $this->user2->id);
        $course1info->set_module_viewed($urlmodulerecord, $this->user4->id);
        $course1info->set_module_viewed($urlmodulerecord, $this->user6->id);

        // Users 3, 4 and 6 manually complete the label activity.
        $labelmodulerecord = $DB->get_record('course_modules', array('id' => $modules['label']->cmid));
        $course1info->update_state($labelmodulerecord, COMPLETION_COMPLETE, $this->user3->id);
        $course1info->update_state($labelmodulerecord, COMPLETION_COMPLETE, $this->user4->id);
        $course1info->update_state($labelmodulerecord, COMPLETION_COMPLETE, $this->user6->id);

        // Users 4 and 6 will get grade for assignment activity and their activity completion will be recalculated.
        $grade_item = $this->grade_generator->get_item_for_module($course, 'assign', $modules['assign']);
        $this->grade_generator->new_grade_for_item($grade_item->id, 60, $this->user4->id);
        $this->grade_generator->new_grade_for_item($grade_item->id, 95, $this->user6->id);
        $assignmodulerecord = $DB->get_record('course_modules', array('id' => $modules['assign']->cmid));
        $course1info->update_state($assignmodulerecord, null, $this->user4->id);
        $course1info->update_state($assignmodulerecord, null, $this->user6->id);

        // All except User5 should be complete now.
        $course1info = new completion_info($course);
        $this->assertTrue($course1info->is_course_complete($this->user1->id));
        $this->assertTrue($course1info->is_course_complete($this->user2->id));
        $this->assertTrue($course1info->is_course_complete($this->user3->id));
        $this->assertTrue($course1info->is_course_complete($this->user4->id));
        $this->assertFalse($course1info->is_course_complete($this->user5->id));
        $this->assertTrue($course1info->is_course_complete($this->user6->id));

        // Let's check the completion states of each module for each user are where we expect them to be.
        $f2fmodulecompletions =
            $DB->get_records('course_modules_completion', array('coursemoduleid' => $modules['seminar']->cmid), '', 'userid, completionstate');
        $this->assertEquals(COMPLETION_COMPLETE, $f2fmodulecompletions[$this->user1->id]->completionstate);
        $this->assertFalse(isset($f2fmodulecompletions[$this->user2->id]));
        $this->assertFalse(isset($f2fmodulecompletions[$this->user3->id]));
        $this->assertEquals(COMPLETION_COMPLETE, $f2fmodulecompletions[$this->user4->id]->completionstate);
        $this->assertFalse(isset($f2fmodulecompletions[$this->user5->id]));
        $this->assertEquals(COMPLETION_COMPLETE, $f2fmodulecompletions[$this->user6->id]->completionstate);

        $urlmodulecompletions =
            $DB->get_records('course_modules_completion', array('coursemoduleid' => $modules['url']->cmid), '', 'userid, completionstate, viewed');
        $this->assertFalse(isset($urlmodulecompletions[$this->user1->id]));
        $this->assertEquals(COMPLETION_COMPLETE, $urlmodulecompletions[$this->user2->id]->completionstate);
        $this->assertEquals(COMPLETION_VIEWED, $urlmodulecompletions[$this->user2->id]->viewed);
        $this->assertFalse(isset($urlmodulecompletions[$this->user3->id]));
        $this->assertEquals(COMPLETION_COMPLETE, $urlmodulecompletions[$this->user4->id]->completionstate);
        $this->assertEquals(COMPLETION_VIEWED, $urlmodulecompletions[$this->user4->id]->viewed);
        $this->assertFalse(isset($urlmodulecompletions[$this->user5->id]));
        $this->assertEquals(COMPLETION_COMPLETE, $urlmodulecompletions[$this->user6->id]->completionstate);
        $this->assertEquals(COMPLETION_VIEWED, $urlmodulecompletions[$this->user6->id]->viewed);

        $labelmodulecompletions =
            $DB->get_records('course_modules_completion', array('coursemoduleid' => $modules['label']->cmid), '', 'userid, completionstate');
        $this->assertFalse(isset($labelmodulecompletions[$this->user1->id]));
        $this->assertFalse(isset($labelmodulecompletions[$this->user2->id]));
        $this->assertEquals(COMPLETION_COMPLETE, $labelmodulecompletions[$this->user3->id]->completionstate);
        $this->assertEquals(COMPLETION_COMPLETE, $labelmodulecompletions[$this->user4->id]->completionstate);
        $this->assertFalse(isset($labelmodulecompletions[$this->user5->id]));
        $this->assertEquals(COMPLETION_COMPLETE, $labelmodulecompletions[$this->user6->id]->completionstate);

        $assignmodulecompletions = $DB->get_records('course_modules_completion', ['coursemoduleid' => $modules['assign']->cmid], '', 'userid, completionstate');
        $this->assertFalse(isset($assignmodulecompletions[$this->user1->id]));
        $this->assertFalse(isset($assignmodulecompletions[$this->user2->id]));
        $this->assertFalse(isset($assignmodulecompletions[$this->user3->id]));
        $this->assertEquals(COMPLETION_COMPLETE_PASS, $assignmodulecompletions[$this->user4->id]->completionstate);
        $this->assertFalse(isset($assignmodulecompletions[$this->user5->id]));
        $this->assertEquals(COMPLETION_COMPLETE_PASS, $assignmodulecompletions[$this->user6->id]->completionstate);
    }

    /**
     * Tests archive_course_activities.
     *
     * We're not using the window param, meaning that it will default to null, and
     * within the function, will be set to now.
     *
     * We also check there's no interference between courses. e.g. if it's run for course1, it
     * shouldn't affect course2.
     */
    public function test_archive_course_activities_nowindowparam() {
        global $DB;

        $course1 = $this->data_generator->create_course();
        $course2 = $this->data_generator->create_course();
        $this->completion_generator->enable_completion_tracking($course1);
        $this->completion_generator->enable_completion_tracking($course2);

        $modules1 = $this->set_up_activities_for_course($course1);
        $modules2 = $this->set_up_activities_for_course($course2);
        $this->users_complete_modules($course1, $modules1);
        $this->users_complete_modules($course2, $modules2);

        // Run the function for all users in the course.
        archive_course_activities($this->user1->id, $course1->id);
        archive_course_activities($this->user2->id, $course1->id);
        archive_course_activities($this->user3->id, $course1->id);
        archive_course_activities($this->user4->id, $course1->id);
        archive_course_activities($this->user5->id, $course1->id);

        // Now check all that those that should have been reset were, and those that shouldn't are still in the same state.
        // First of all the modules in course1.
        $f2fmodulecompletions =
            $DB->get_records('course_modules_completion', array('coursemoduleid' => $modules1['seminar']->cmid), '', 'userid, completionstate');
        $this->assertFalse(isset($f2fmodulecompletions[$this->user1->id]));
        $this->assertFalse(isset($f2fmodulecompletions[$this->user2->id]));
        $this->assertFalse(isset($f2fmodulecompletions[$this->user3->id]));
        $this->assertFalse(isset($f2fmodulecompletions[$this->user4->id]));
        $this->assertFalse(isset($f2fmodulecompletions[$this->user5->id]));
        $this->assertEquals(COMPLETION_COMPLETE, $f2fmodulecompletions[$this->user6->id]->completionstate);

        $urlmodulecompletions =
            $DB->get_records('course_modules_completion', array('coursemoduleid' => $modules1['url']->cmid), '', 'userid, completionstate, viewed');
        $this->assertFalse(isset($urlmodulecompletions[$this->user1->id]));
        $this->assertFalse(isset($urlmodulecompletions[$this->user2->id]));
        $this->assertFalse(isset($urlmodulecompletions[$this->user3->id]));
        $this->assertFalse(isset($urlmodulecompletions[$this->user4->id]));
        $this->assertFalse(isset($urlmodulecompletions[$this->user5->id]));
        $this->assertEquals(COMPLETION_COMPLETE, $urlmodulecompletions[$this->user6->id]->completionstate);
        $this->assertEquals(COMPLETION_VIEWED, $urlmodulecompletions[$this->user6->id]->viewed);

        $labelmodulecompletions =
            $DB->get_records('course_modules_completion', array('coursemoduleid' => $modules1['label']->cmid), '', 'userid, completionstate');
        $this->assertFalse(isset($labelmodulecompletions[$this->user1->id]));
        $this->assertFalse(isset($labelmodulecompletions[$this->user2->id]));
        $this->assertFalse(isset($labelmodulecompletions[$this->user3->id]));
        $this->assertFalse(isset($labelmodulecompletions[$this->user4->id]));
        $this->assertFalse(isset($labelmodulecompletions[$this->user5->id]));
        $this->assertEquals(COMPLETION_COMPLETE, $labelmodulecompletions[$this->user6->id]->completionstate);

        $assignmodulecompletions = $DB->get_records('course_modules_completion', ['coursemoduleid' => $modules1['assign']->cmid], '', 'userid, completionstate');
        $this->assertFalse(isset($assignmodulecompletions[$this->user1->id]));
        $this->assertFalse(isset($assignmodulecompletions[$this->user2->id]));
        $this->assertFalse(isset($assignmodulecompletions[$this->user3->id]));
        $this->assertFalse(isset($assignmodulecompletions[$this->user4->id]));
        $this->assertFalse(isset($assignmodulecompletions[$this->user5->id]));
        $this->assertEquals(COMPLETION_COMPLETE_PASS, $assignmodulecompletions[$this->user6->id]->completionstate);
        $grade_item = $this->grade_generator->get_item_for_module($course1, 'assign', $modules1['assign']);
        $grade_user4 = $DB->get_record('grade_grades', ['itemid' => $grade_item->id, 'userid' => $this->user4->id]);
        $this->assertFalse($grade_user4);
        $grade_user6 = $DB->get_record('grade_grades', ['itemid' => $grade_item->id, 'userid' => $this->user6->id]);
        $this->assertSame('95.00000', $grade_user6->finalgrade);

        // For course2. The function was not run at all. So these assertions just need
        // to match what is done at the end of users_complete_modules.
        $f2fmodulecompletions =
            $DB->get_records('course_modules_completion', array('coursemoduleid' => $modules2['seminar']->cmid), '', 'userid, completionstate');
        $this->assertEquals(COMPLETION_COMPLETE, $f2fmodulecompletions[$this->user1->id]->completionstate);
        $this->assertFalse(isset($f2fmodulecompletions[$this->user2->id]));
        $this->assertFalse(isset($f2fmodulecompletions[$this->user3->id]));
        $this->assertEquals(COMPLETION_COMPLETE, $f2fmodulecompletions[$this->user4->id]->completionstate);
        $this->assertFalse(isset($f2fmodulecompletions[$this->user5->id]));
        $this->assertEquals(COMPLETION_COMPLETE, $f2fmodulecompletions[$this->user6->id]->completionstate);

        $urlmodulecompletions =
            $DB->get_records('course_modules_completion', array('coursemoduleid' => $modules2['url']->cmid), '', 'userid, completionstate, viewed');
        $this->assertFalse(isset($urlmodulecompletions[$this->user1->id]));
        $this->assertEquals(COMPLETION_COMPLETE, $urlmodulecompletions[$this->user2->id]->completionstate);
        $this->assertEquals(COMPLETION_VIEWED, $urlmodulecompletions[$this->user2->id]->viewed);
        $this->assertFalse(isset($urlmodulecompletions[$this->user3->id]));
        $this->assertEquals(COMPLETION_COMPLETE, $urlmodulecompletions[$this->user4->id]->completionstate);
        $this->assertEquals(COMPLETION_VIEWED, $urlmodulecompletions[$this->user4->id]->viewed);
        $this->assertFalse(isset($urlmodulecompletions[$this->user5->id]));
        $this->assertEquals(COMPLETION_COMPLETE, $urlmodulecompletions[$this->user6->id]->completionstate);
        $this->assertEquals(COMPLETION_VIEWED, $urlmodulecompletions[$this->user6->id]->viewed);

        $labelmodulecompletions =
            $DB->get_records('course_modules_completion', array('coursemoduleid' => $modules2['label']->cmid), '', 'userid, completionstate');
        $this->assertFalse(isset($labelmodulecompletions[$this->user1->id]));
        $this->assertFalse(isset($labelmodulecompletions[$this->user2->id]));
        $this->assertEquals(COMPLETION_COMPLETE, $labelmodulecompletions[$this->user3->id]->completionstate);
        $this->assertEquals(COMPLETION_COMPLETE, $labelmodulecompletions[$this->user4->id]->completionstate);
        $this->assertFalse(isset($labelmodulecompletions[$this->user5->id]));
        $this->assertEquals(COMPLETION_COMPLETE, $labelmodulecompletions[$this->user6->id]->completionstate);

        $assignmodulecompletions = $DB->get_records('course_modules_completion', ['coursemoduleid' => $modules2['assign']->cmid], '', 'userid, completionstate');
        $this->assertFalse(isset($assignmodulecompletions[$this->user1->id]));
        $this->assertFalse(isset($assignmodulecompletions[$this->user2->id]));
        $this->assertFalse(isset($assignmodulecompletions[$this->user3->id]));
        $this->assertEquals(COMPLETION_COMPLETE_PASS, $assignmodulecompletions[$this->user4->id]->completionstate);
        $this->assertFalse(isset($assignmodulecompletions[$this->user5->id]));
        $this->assertEquals(COMPLETION_COMPLETE_PASS, $assignmodulecompletions[$this->user6->id]->completionstate);
        $grade_item = $this->grade_generator->get_item_for_module($course2, 'assign', $modules2['assign']);
        $grade_user4 = $DB->get_record('grade_grades', ['itemid' => $grade_item->id, 'userid' => $this->user4->id]);
        $this->assertSame('60.00000', $grade_user4->finalgrade);
        $grade_user6 = $DB->get_record('grade_grades', ['itemid' => $grade_item->id, 'userid' => $this->user6->id]);
        $this->assertSame('95.00000', $grade_user6->finalgrade);
    }

    /**
     * Tests archive_course_activities.
     *
     * We'll set the window open param to various times here and check we get
     * appropriate results.
     *
     * -- Resetting module despite the completion time --
     * Currently, the windowopen time is only taken into account when running the _archive_completion
     * for modules that support FEATURE_ARCHIVE_COMPLETION.  It is not taken into account for others.
     * For example, a user may complete an activity after their window open time, but before cron has run.
     * That activity would then be reset. This may be intended behaviour or at least not worth
     * complicating the code in order to fix. The windowopen param appears to have been intended to
     * deal with facetoface booking times in the future rather than issues such as these.
     */
    public function test_archive_course_activities_setwindowparam() {
        global $DB;

        $course1 = $this->data_generator->create_course();
        $this->completion_generator->enable_completion_tracking($course1);

        $modules = $this->set_up_activities_for_course($course1);
        $this->users_complete_modules($course1, $modules);

        $twodaysago = time() - DAYSECS * 2;
        $onehourago = time() - HOURSECS;
        $onedayinfuture = time() + DAYSECS;

        // Run course completion archive for all users, so that activities can trigger completion again.
        archive_course_completion($this->user1->id, $course1->id);
        archive_course_completion($this->user2->id, $course1->id);
        archive_course_completion($this->user3->id, $course1->id);
        archive_course_completion($this->user4->id, $course1->id);
        archive_course_completion($this->user5->id, $course1->id);

        // Run the function for all users in the course.
        archive_course_activities($this->user1->id, $course1->id, $twodaysago);
        archive_course_activities($this->user2->id, $course1->id, $onedayinfuture);
        archive_course_activities($this->user3->id, $course1->id, $onehourago);
        archive_course_activities($this->user4->id, $course1->id, $onehourago);
        archive_course_activities($this->user5->id, $course1->id, $onedayinfuture);

        // Now check all that those that should have been reset were, and those that shouldn't are still in the same state.
        $f2fmodulecompletions =
            $DB->get_records('course_modules_completion', array('coursemoduleid' => $modules['seminar']->cmid), '', 'userid, completionstate');
        // User1's window was before the f2f. The module completion record was reset, but the f2f session was not marked archived.
        $this->assertFalse(isset($f2fmodulecompletions[$this->user1->id]));
        $this->assertFalse(isset($f2fmodulecompletions[$this->user2->id]));
        $this->assertFalse(isset($f2fmodulecompletions[$this->user3->id]));
        // User4's window was in the past but will have definitely been after the f2f. It should have been reset.
        $this->assertFalse(isset($f2fmodulecompletions[$this->user4->id]));
        $this->assertFalse(isset($f2fmodulecompletions[$this->user5->id]));
        // User6's module completion record wasn't reset at all.
        $this->assertEquals(COMPLETION_COMPLETE, $f2fmodulecompletions[$this->user6->id]->completionstate);

        ob_start();

        // Create new course_completions records (they will have the reaggregate flag set).
        completion_start_user_bulk();

        // Aggregate completion. This will recreate user1's module completion record, based on their non-archived f2f session.
        completion_cron_completions();

        ob_end_clean();

        // Check again. This time, user1 has been reaggreagted to complete.
        $f2fmodulecompletions =
            $DB->get_records('course_modules_completion', array('coursemoduleid' => $modules['seminar']->cmid), '', 'userid, completionstate');
        // User1's module completion was recreated, because the f2f session was not marked archived.
        $this->assertEquals(COMPLETION_COMPLETE, $f2fmodulecompletions[$this->user1->id]->completionstate);
        // None of the others were changed.
        $this->assertFalse(isset($f2fmodulecompletions[$this->user2->id]));
        $this->assertFalse(isset($f2fmodulecompletions[$this->user3->id]));
        $this->assertFalse(isset($f2fmodulecompletions[$this->user4->id]));
        $this->assertFalse(isset($f2fmodulecompletions[$this->user5->id]));
        $this->assertEquals(COMPLETION_COMPLETE, $f2fmodulecompletions[$this->user6->id]->completionstate);

        $urlmodulecompletions =
            $DB->get_records('course_modules_completion', array('coursemoduleid' => $modules['url']->cmid), '', 'userid, completionstate, viewed');
        $this->assertFalse(isset($urlmodulecompletions[$this->user1->id]));
        $this->assertFalse(isset($urlmodulecompletions[$this->user2->id]));
        $this->assertFalse(isset($urlmodulecompletions[$this->user3->id]));
        // User4's modules are reset even though the window open is before they were completed. See docs for this test.
        $this->assertFalse(isset($urlmodulecompletions[$this->user4->id]));
        $this->assertFalse(isset($urlmodulecompletions[$this->user5->id]));
        $this->assertEquals(COMPLETION_COMPLETE, $urlmodulecompletions[$this->user6->id]->completionstate);
        $this->assertEquals(COMPLETION_VIEWED, $urlmodulecompletions[$this->user6->id]->viewed);

        $labelmodulecompletions =
            $DB->get_records('course_modules_completion', array('coursemoduleid' => $modules['label']->cmid), '', 'userid, completionstate');
        $this->assertFalse(isset($labelmodulecompletions[$this->user1->id]));
        $this->assertFalse(isset($labelmodulecompletions[$this->user2->id]));
        // User3's modules are reset even though the window open is before they were completed. See docs for this test.
        $this->assertFalse(isset($labelmodulecompletions[$this->user3->id]));
        // User4's modules are reset even though the window open is before they were completed. See docs for this test.
        $this->assertFalse(isset($labelmodulecompletions[$this->user4->id]));
        $this->assertFalse(isset($labelmodulecompletions[$this->user5->id]));
        $this->assertEquals(COMPLETION_COMPLETE, $labelmodulecompletions[$this->user6->id]->completionstate);
    }

    /**
     * Tests archive_course_activities.
     *
     * There was an issue where modules were not being reset if they were hidden. This test is to
     * catch that in case it happens again.
     */
    public function test_archive_course_activities_hidden() {
        global $DB;

        $course1 = $this->data_generator->create_course();
        $this->completion_generator->enable_completion_tracking($course1);

        $modules = $this->set_up_activities_for_course($course1);
        $this->users_complete_modules($course1, $modules);

        // Now set the modules to hidden.
        $f2fmodulerecord = $DB->get_record('course_modules', array('id' => $modules['seminar']->cmid));
        $f2fmodulerecord->visible = 0;
        $f2fmodulerecord->visibleold = 0;
        $DB->update_record('course_modules', $f2fmodulerecord);
        $urlmodulerecord = $DB->get_record('course_modules', array('id' => $modules['url']->cmid));
        $urlmodulerecord->visible = 0;
        $urlmodulerecord->visibleold = 0;
        $DB->update_record('course_modules', $urlmodulerecord);
        $labelmodulerecord = $DB->get_record('course_modules', array('id' => $modules['label']->cmid));
        $labelmodulerecord->visible = 0;
        $labelmodulerecord->visibleold = 0;
        $DB->update_record('course_modules', $labelmodulerecord);

        // Run the function for all users in the course.
        archive_course_activities($this->user1->id, $course1->id);
        archive_course_activities($this->user2->id, $course1->id);
        archive_course_activities($this->user3->id, $course1->id);
        archive_course_activities($this->user4->id, $course1->id);
        archive_course_activities($this->user5->id, $course1->id);

        // Now check all that those that should have been reset were, and those that shouldn't are still in the same state.
        $f2fmodulecompletions =
            $DB->get_records('course_modules_completion', array('coursemoduleid' => $modules['seminar']->cmid), '', 'userid, completionstate');
        $this->assertFalse(isset($f2fmodulecompletions[$this->user1->id]));
        $this->assertFalse(isset($f2fmodulecompletions[$this->user2->id]));
        $this->assertFalse(isset($f2fmodulecompletions[$this->user3->id]));
        $this->assertFalse(isset($f2fmodulecompletions[$this->user4->id]));
        $this->assertFalse(isset($f2fmodulecompletions[$this->user5->id]));
        $this->assertEquals(COMPLETION_COMPLETE, $f2fmodulecompletions[$this->user6->id]->completionstate);

        $urlmodulecompletions =
            $DB->get_records('course_modules_completion', array('coursemoduleid' => $modules['url']->cmid), '', 'userid, completionstate, viewed');
        $this->assertFalse(isset($urlmodulecompletions[$this->user1->id]));
        $this->assertFalse(isset($urlmodulecompletions[$this->user2->id]));
        $this->assertFalse(isset($urlmodulecompletions[$this->user3->id]));
        $this->assertFalse(isset($urlmodulecompletions[$this->user4->id]));
        $this->assertFalse(isset($urlmodulecompletions[$this->user5->id]));
        $this->assertEquals(COMPLETION_COMPLETE, $urlmodulecompletions[$this->user6->id]->completionstate);
        $this->assertEquals(COMPLETION_VIEWED, $urlmodulecompletions[$this->user6->id]->viewed);

        $labelmodulecompletions =
            $DB->get_records('course_modules_completion', array('coursemoduleid' => $modules['label']->cmid), '', 'userid, completionstate');
        $this->assertFalse(isset($labelmodulecompletions[$this->user1->id]));
        $this->assertFalse(isset($labelmodulecompletions[$this->user2->id]));
        $this->assertFalse(isset($labelmodulecompletions[$this->user3->id]));
        $this->assertFalse(isset($labelmodulecompletions[$this->user4->id]));
        $this->assertFalse(isset($labelmodulecompletions[$this->user5->id]));
        $this->assertEquals(COMPLETION_COMPLETE, $labelmodulecompletions[$this->user6->id]->completionstate);
    }

    public function test_archive_course_and_activities_with_pass_grades() {
        global $DB;

        $timenow = time();
        $course = $this->data_generator->create_course(['enablecompletion' => 1]);

        // Create quiz activity and add a question.
        $options = [
            'course'           => $course->id,
            'questionsperpage' => 1,
            'grade'            => 100.0,
            'sumgrades'        => 1,
            'completion'       => COMPLETION_TRACKING_AUTOMATIC,
            'completionpass'   => 1,
        ];
        $quiz = $this->data_generator->create_module('quiz', $options);

        /** @var \core_question\testing\generator $questiongenerator */
        $questiongenerator = $this->data_generator->get_plugin_generator('core_question');
        $cat = $questiongenerator->create_question_category();
        $question = $questiongenerator->create_question('truefalse', null, ['category' => $cat->id]);
        quiz_add_quiz_question($question->id, $quiz, 100.0);

        // Set grade to pass for the quiz activity.
        $item = grade_item::fetch(['courseid' => $course->id, 'itemtype' => 'mod', 'itemmodule' => 'quiz', 'iteminstance' => $quiz->id]);
        $item->gradepass = 80;
        $item->update();

        // Set the course to be complete if the quiz is complete.
        /** @var \core_completion\testing\generator $cgen */
        $cgen = $this->data_generator->get_plugin_generator('core_completion');
        $cgen->set_activity_completion($course->id, [$quiz]);

        // Enrol a couple users.
        $this->data_generator->enrol_user($this->user1->id, $course->id);
        $this->data_generator->enrol_user($this->user2->id, $course->id);

        // Start the passing attempt for user1.
        $quizobj = quiz::create($quiz->id, $this->user1->id);
        $quba = question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj->get_context());
        $quba->set_preferred_behaviour($quizobj->get_quiz()->preferredbehaviour);

        $attempt = quiz_create_attempt($quizobj, 1, null, $timenow, false, $this->user1->id);
        $attempt = quiz_start_new_attempt($quizobj, $quba, $attempt, 1, $timenow);
        $attempt = quiz_attempt_save_started($quizobj, $quba, $attempt);

        // Process a response.
        $attemptobj = quiz_attempt::create($attempt->id);
        $tosubmit = [1 => ['answer' => '1']];
        $attemptobj->process_submitted_actions($timenow, false, $tosubmit);
        self::assertTrue($attemptobj->has_response_to_at_least_one_graded_question());
        $attemptobj->process_finish($timenow, false);

        // Check module completions: user1 is complete, user2 has no completion records.
        $quizcompletions = $DB->get_records('course_modules_completion', ['coursemoduleid' => $quiz->cmid], '', 'userid, completionstate');
        self::assertEquals(COMPLETION_COMPLETE_PASS, $quizcompletions[$this->user1->id]->completionstate);
        self::assertFalse(isset($quizcompletions[$this->user2->id]));
        $grade = $DB->get_record('grade_grades', ['itemid' => $item->id, 'userid' => $this->user1->id]);
        self::assertSame('100.00000', $grade->finalgrade);
        self::assertFalse($DB->get_record('grade_grades', ['itemid' => $item->id, 'userid' => $this->user2->id]));

        // Check course criteria completions: user1 is complete, user2 has no completion records.
        $criteria = $DB->get_field('course_completion_criteria', 'id', ['course' => $course->id, 'criteriatype' => 4, 'module' => 'quiz', 'moduleinstance' => $quiz->cmid]);
        $critcompletions = $DB->get_records('course_completion_crit_compl', ['course' => $course->id, 'criteriaid' => $criteria], '', 'userid, timecompleted');
        self::assertCount(1, $critcompletions);
        self::assertNotEmpty($critcompletions[$this->user1->id]->timecompleted);
        self::assertFalse(isset($critcompletions[$this->user2->id]));

        // Archive course and activities for both users.
        archive_course_completion($this->user1->id, $course->id);
        archive_course_activities($this->user1->id, $course->id);

        archive_course_completion($this->user2->id, $course->id);
        archive_course_activities($this->user2->id, $course->id);

        // Check module completions.
        $quizcompletions = $DB->get_records('course_modules_completion', ['coursemoduleid' => $quiz->cmid], '', 'userid, completionstate');
        self::assertFalse(isset($quizcompletions[$this->user1->id]));
        self::assertFalse(isset($quizcompletions[$this->user2->id]));
        self::assertFalse($DB->get_record('grade_grades', ['itemid' => $item->id, 'userid' => $this->user1->id]));

        // Check course criteria completions.
        $critcompletions = $DB->get_records('course_completion_crit_compl', ['course' => $course->id, 'criteriaid' => $criteria], '', 'userid, timecompleted');
        self::assertCount(0, $critcompletions);
        self::assertFalse(isset($critcompletions[$this->user1->id]));
        self::assertFalse(isset($critcompletions[$this->user2->id]));
    }

    public function test_archive_course_purge_gradebook_manual_grades() {
        global $DB;

        // Set up course 1 with a manual grade and 3 enrolled users.
        $course1 = $this->data_generator->create_course();
        $grade_item1 = new grade_item(['courseid' => $course1->id, 'itemtype' => 'manual'], false);
        $grade_item1->insert();
        $this->data_generator->enrol_user($this->user1->id, $course1->id);
        $this->data_generator->enrol_user($this->user2->id, $course1->id);
        $this->data_generator->enrol_user($this->user3->id, $course1->id);

        // Set up course 2 with a couple manual grades and 2 enrolled users.
        $course2 = $this->data_generator->create_course();
        $grade_item21 = new grade_item(['courseid' => $course2->id, 'itemtype' => 'manual'], false);
        $grade_item21->insert();
        $grade_item22 = new grade_item(['courseid' => $course2->id, 'itemtype' => 'manual'], false);
        $grade_item22->insert();
        $this->data_generator->enrol_user($this->user1->id, $course2->id);
        $this->data_generator->enrol_user($this->user5->id, $course2->id);

        // Add grades for users in the courses.
        $this->grade_generator->new_grade_for_item($grade_item1->id, 70, $this->user1->id);
        $this->grade_generator->new_grade_for_item($grade_item1->id, 25, $this->user2->id);
        $this->grade_generator->new_grade_for_item($grade_item1->id, 33, $this->user3->id);
        $this->grade_generator->new_grade_for_item($grade_item21->id, 42, $this->user1->id);
        $this->grade_generator->new_grade_for_item($grade_item21->id, 89, $this->user5->id);
        $this->grade_generator->new_grade_for_item($grade_item22->id, 61, $this->user5->id);

        self::assertEquals(6, $DB->count_records('grade_grades'));

        // Archive user2's manual grades.
        $grade = $DB->get_record('grade_grades', ['itemid' => $grade_item1->id, 'userid' => $this->user2->id]);
        self::assertSame('25.00000', $grade->finalgrade);
        archive_course_purge_gradebook($this->user2->id, $course1->id);
        self::assertEquals(7, $DB->count_records('grade_grades'));
        $newgrade = $DB->get_record('grade_grades', ['itemid' => $grade_item1->id, 'userid' => $this->user2->id]);
        self::assertNull($newgrade->finalgrade);
        self::assertNotSame($grade->id, $newgrade->id);

        // Archive user1's manual grades in course2.
        $grade = $DB->get_record('grade_grades', ['itemid' => $grade_item21->id, 'userid' => $this->user1->id]);
        self::assertSame('42.00000', $grade->finalgrade);
        archive_course_purge_gradebook($this->user1->id, $course2->id);
        self::assertEquals(9, $DB->count_records('grade_grades'));
        $newgrade = $DB->get_record('grade_grades', ['itemid' => $grade_item21->id, 'userid' => $this->user1->id]);
        self::assertNull($newgrade->finalgrade);
        self::assertNotSame($grade->id, $newgrade->id);

        // Archive user5's manual grades in course2.
        $grade1 = $DB->get_record('grade_grades', ['itemid' => $grade_item21->id, 'userid' => $this->user5->id]);
        self::assertSame('89.00000', $grade1->finalgrade);
        $grade2 = $DB->get_record('grade_grades', ['itemid' => $grade_item22->id, 'userid' => $this->user5->id]);
        self::assertSame('61.00000', $grade2->finalgrade);
        archive_course_purge_gradebook($this->user5->id, $course2->id);
        self::assertEquals(10, $DB->count_records('grade_grades'));
        $newgrade1 = $DB->get_record('grade_grades', ['itemid' => $grade_item21->id, 'userid' => $this->user5->id]);
        self::assertNull($newgrade1->finalgrade);
        self::assertNotSame($grade1->id, $newgrade1->id);
        $newgrade2 = $DB->get_record('grade_grades', ['itemid' => $grade_item22->id, 'userid' => $this->user5->id]);
        self::assertNull($newgrade2->finalgrade);
        self::assertNotSame($grade2->id, $newgrade2->id);
    }

    public function test_archive_course_purge_gradebook_course_grades() {
        global $DB;

        // Set up course 1 with a manual grade and 3 enrolled users.
        $course = $this->data_generator->create_course(['enablecompletion' => 1]);
        $this->completion_generator->enable_completion_tracking($course);
        $this->completion_generator->set_completion_criteria($course, [COMPLETION_CRITERIA_TYPE_GRADE => 75]);

        $this->data_generator->enrol_user($this->user1->id, $course->id);
        $this->data_generator->enrol_user($this->user2->id, $course->id);
        $this->data_generator->enrol_user($this->user3->id, $course->id);

        $grade_item = grade_item::fetch_course_item($course->id);

        $this->grade_generator->new_grade_for_item($grade_item->id, 60, $this->user1->id);
        $this->grade_generator->new_grade_for_item($grade_item->id, 76, $this->user2->id);

        self::assertEquals(2, $DB->count_records('grade_grades'));

        $grade_user1 = $DB->get_record('grade_grades', ['itemid' => $grade_item->id, 'userid' => $this->user1->id]);
        self::assertSame('60.00000', $grade_user1->finalgrade);
        $grade_user2before = $DB->get_record('grade_grades', ['itemid' => $grade_item->id, 'userid' => $this->user2->id]);
        archive_course_purge_gradebook($this->user1->id, $course->id);
        self::assertEquals(2, $DB->count_records('grade_grades'));
        $grade_user2after = $DB->get_record('grade_grades', ['itemid' => $grade_item->id, 'userid' => $this->user2->id]);
        $newgrade_user1 = $DB->get_record('grade_grades', ['itemid' => $grade_item->id, 'userid' => $this->user1->id]);
        self::assertNull($newgrade_user1->finalgrade);
        self::assertNotSame($grade_user1->id, $newgrade_user1->id);
        self::assertSame($grade_user2before->finalgrade, $grade_user2after->finalgrade);

        $grade_user2 = $DB->get_record('grade_grades', ['itemid' => $grade_item->id, 'userid' => $this->user2->id]);
        self::assertSame('76.00000', $grade_user2->finalgrade);
        archive_course_purge_gradebook($this->user2->id, $course->id);
        self::assertEquals(2, $DB->count_records('grade_grades'));
        $newgrade_user2 = $DB->get_record('grade_grades', ['itemid' => $grade_item->id, 'userid' => $this->user2->id]);
        self::assertNull($newgrade_user2->finalgrade);
        self::assertNotSame($grade_user2->id, $newgrade_user2->id);
    }
}
