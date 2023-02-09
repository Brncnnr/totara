<?php

/*
 * This file is part of Totara LMS
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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_facetoface
 */

use mod_facetoface\seminar;
use mod_facetoface\seminar_event;
use mod_facetoface\seminar_session;
use mod_facetoface\signup_helper;
use mod_facetoface\signup;
use mod_facetoface\signup\state\booked;
use mod_facetoface\signup\state\fully_attended;

defined('MOODLE_INTERNAL') || die();

/**
 * Class mod_facetoface_signup_helper_testcase
 */
class mod_facetoface_activity_completion_task_testcase extends advanced_testcase {

    public function test_trigger_delayed_completions() {
        global $DB;

        // Prepare a course and some learners.
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $learners = [];
        for ($i = 1; $i <= 4; $i++) {
            $learners[$i] = $this->getDataGenerator()->create_user();
            $this->getDataGenerator()->enrol_user($learners[$i]->id, $course->id);
        }

        // Create two identical seminars, with an event that ends now and completion delay of 1 day.
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $f2fdata = new stdClass();
        $f2fdata->course = $course->id;
        $f2fdata->completiondelay = 2;
        $f2foptions = array(
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionstatusrequired' => json_encode(array(\mod_facetoface\signup\state\fully_attended::get_code())),
        );
        $facetofaces = [];
        $seminars = [];
        $seminar_events = [];
        $signups = [];
        for ($i = 1; $i <= 2; $i++) {
            $facetofaces[$i] = $facetofacegenerator->create_instance($f2fdata, $f2foptions);
            $seminars[$i] = new seminar($facetofaces[$i]->id);
            $seminar_events[$i] = new seminar_event();
            $seminar_events[$i]
                ->set_facetoface($seminars[$i]->get_id())
                ->save();
            $seminarsession = new seminar_session();
            $seminarsession->set_sessionid($seminar_events[$i]->get_id())
                ->set_timestart(time() + DAYSECS - HOURSECS)
                ->set_timefinish(time() + DAYSECS)
                ->save();
        }

        // Set up completion checking.
        //$completion = new completion_info($course);
        $modinfo = get_fast_modinfo($course);
        $cminfos = [];
        $cminfos[1] =  $modinfo->instances['facetoface'][$seminars[1]->get_id()];
        $cminfos[2] =  $modinfo->instances['facetoface'][$seminars[2]->get_id()];

        // Sign up the learners, two to seminarevent1, and two to seminarevent2.
        for ($i = 1; $i <= 4; $i++) {
            if ($i < 3) {
                $k = 1;
            } else {
                $k = 2;
            }
            $signups[$i] = signup_helper::signup(signup::create($learners[$i]->id, $seminar_events[$k]));
            $this->assertInstanceOf(booked::class, $signups[$i]->get_state());
            $this->assertEquals(false, $DB->record_exists('course_modules_completion',
                array('coursemoduleid' => $cminfos[$k]->id, 'userid' => $learners[$i]->id)),
                "Signups[{$i}] in seminars[{$k}] has a completion record already.");
        }

        // Adjust session times backward so that we can mark attendance.
        for ($i = 1; $i <= 2; $i++) {
            $date = $DB->get_record('facetoface_sessions_dates', ['sessionid' => $seminar_events[$i]->get_id()]);
            $date->timestart = time() - DAYSECS - HOURSECS;
            $date->timefinish = time() - DAYSECS;
            $DB->update_record('facetoface_sessions_dates', $date);
        }

        // Set attendance on learners, and ensure that completion delay of 1 day prevents completion.
        for ($i = 1; $i <= 4; $i++) {
            if ($i < 3) {
                $k = 1;
            } else {
                $k = 2;
            }
            $data = [$signups[$i]->get_id() => fully_attended::get_code()];
            signup_helper::process_attendance($seminar_events[$k], $data);
            $this->assertEquals(false, $DB->record_exists('course_modules_completion',
                array('coursemoduleid' => $cminfos[$k]->id, 'userid' => $learners[$i]->id, 'completionstate' => COMPLETION_COMPLETE)),
                "Signups[{$i}] in seminars[{$k}] is marked complete, and should not be.");
        }

        // Run the activity_completion_task and check that completion is still prevented.
        $task = new \mod_facetoface\task\activity_completion_task();
        ob_start();
        $task->execute();
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertEquals('Found 0 delayed facetoface activity completion records, processed 0.', trim($output));
        for ($i = 1; $i <= 4; $i++) {
            if ($i < 3) {
                $k = 1;
            } else {
                $k = 2;
            }
            $data = [$signups[$i]->get_id() => fully_attended::get_code()];
            signup_helper::process_attendance($seminar_events[$k], $data);
            $this->assertEquals(false, $DB->record_exists('course_modules_completion',
                array('coursemoduleid' => $cminfos[$k]->id, 'userid' => $learners[$i]->id, 'completionstate' => COMPLETION_COMPLETE)),
                "Signups[{$i}] in seminars[{$k}] is marked complete, and should not be.");
        }

        // Set the first seminar's completiondelay to 0 days and run the task again.
        $seminars[1]->set_completiondelay(0);
        $seminars[1]->save();
        ob_start();
        $task->execute();
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertEquals('Found 2 delayed facetoface activity completion records, processed 2.', trim($output));
        for ($i = 1; $i <= 4; $i++) {
            if ($i < 3) {
                $k = 1;
                $exist = true;
            } else {
                $k = 2;
                $exist = false;
            }
            $data = [$signups[$i]->get_id() => fully_attended::get_code()];
            signup_helper::process_attendance($seminar_events[$k], $data);
            $this->assertEquals($exist, $DB->record_exists('course_modules_completion',
                array('coursemoduleid' => $cminfos[$k]->id, 'userid' => $learners[$i]->id, 'completionstate' => COMPLETION_COMPLETE)),
                "Signups[{$i}] in seminars[{$k}] has the wrong completion state.");
        }
    }

}
