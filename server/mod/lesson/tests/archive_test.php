<?php
/*
 * This file is part of Totara LMS
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
 * @author David Curry <david.curry@totaralearning.com>
 * @package mod_lesson
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/mod/lesson/locallib.php');
require_once($CFG->libdir . '/completionlib.php');

class mod_lesson_archive_testcase extends advanced_testcase {
    /**
     * Is archive completion supported?
     */
    public function test_module_supports_archive_completion() {
        $this->assertTrue(lesson_supports(FEATURE_ARCHIVE_COMPLETION));
    }

    public function test_archive() {
        global $DB;

        $this->setAdminUser();
        set_config('enablecompletion', 1);

        // Create a course
        $this->assertEquals(1, $DB->count_records('course')); // Site course
        $coursedefaults = array('enablecompletion' => COMPLETION_ENABLED);
        $course = $this->getDataGenerator()->create_course($coursedefaults);
        $this->assertEquals(2, $DB->count_records('course')); // Site course + this course

        // Check it has course competion
        $completioninfo = new completion_info($course);
        $this->assertEquals(COMPLETION_ENABLED, $completioninfo->is_enabled());

        // Create a lesson and add it to the course
        $this->assertEquals(0, $DB->count_records('lesson'));
        $completiondefaults = array(
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionview' => COMPLETION_VIEW_REQUIRED
        );
        $lesson = $this->getDataGenerator()->create_module(
            'lesson',
            array('course' => $course->id),
            $completiondefaults
        );
        $this->assertEquals(1, $DB->count_records('lesson'));

        // Create a user.
        $this->assertEquals(2, $DB->count_records('user')); // Guest + Admin
        $user = $this->getDataGenerator()->create_user();
        $this->assertEquals(3, $DB->count_records('user')); // Guest + Admin + this user

        // Enrol user on course
        $this->assertTrue($this->getDataGenerator()->enrol_user($user->id, $course->id));

        // Complete lesson
        $course_module = get_coursemodule_from_instance('lesson', $lesson->id, $course->id);
        $this->assertEquals(COMPLETION_TRACKING_AUTOMATIC, $completioninfo->is_enabled($course_module));

        // Check it isn't complete
        $params = array('userid' => $user->id, 'coursemoduleid' => $course_module->id);
        $completionstate = $DB->get_field('course_modules_completion', 'completionstate', $params);
        $this->assertEmpty($completionstate);

        // Complete the lesson
        $completioninfo->set_module_viewed($course_module, $user->id); // Depends on whether a view is required to complete

        // Manually create some attempt data to make sure it gets deleted.
        $attempt = [
            'lessonid' => $lesson->id,
            'pageid' => 1,
            'userid' => $user->id,
            'answerid' => 1,
            'retry' => 0,
            'correct' => 1,
            'useranswer' => 'yes',
            'timeseen' => time()
        ];
        $DB->insert_record('lesson_attempts', $attempt);

        // Manually create some timer data to make sure gets deleted.
        $timer = [
            'lessonid' => $lesson->id,
            'userid' => $user->id,
            'starttime' => time() - 100,
            'lessontime' => time() - 50,
            'completed' => 1,
            'timemodifiedoffline' => 0
        ];
        $DB->insert_record('lesson_timer', $timer);

        // Check its completed
        $completionstate = $DB->get_field('course_modules_completion', 'completionstate', $params, MUST_EXIST);
        $this->assertEquals(COMPLETION_COMPLETE, $completionstate);
        $this->assertEquals(1, $DB->count_records('lesson_timer'));
        $this->assertEquals(1, $DB->count_records('lesson_attempts'));

        // Archive course activities.
        archive_course_activities($user->id, $course->id);

        // Check the lesson data has been reset.
        $this->assertEquals(0, $DB->count_records('lesson_timer'));
        $this->assertEquals(0, $DB->count_records('lesson_attempts'));
        $this->assertEquals(0, $DB->count_records('lesson_overrides'));
        $this->assertEquals(0, $DB->count_records('lesson_grades'));
        // Verify module completion record is gone.
        $this->assertEquals(0, $DB->count_records('course_modules_completion', $params));
    }
}
