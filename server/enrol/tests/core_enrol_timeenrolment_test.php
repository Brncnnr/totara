<?php
/*
 * This file is part of Totara Learn
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
 * @author Maria Torres <maria.torres@totaralearning.com>
 * @package core_enrol
 */

defined('MOODLE_INTERNAL') || die();


class core_enrol_timeenrolled_testcase extends advanced_testcase {
    /**
     * Test timeenrolled is being recorded when user enrolments happens.
     */
    public function test_timeenrolled_is_recorded_when_user_is_enrol_in_course() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/enrol/locallib.php');
        $this->setAdminUser();

        // Enable completion site wide.
        set_config('enablecompletion', 1);

        // Set null progress trace.
        $trace = new null_progress_trace();

        // Get student role. Needed when adding audience instace as enrolment for the course.
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->assertNotEmpty($studentrole);

        // Create a course with completion enabled and completionstartonenrol equal to 1.
        // completionstartonenrol is deprecated but old courses could have this on. We are setting this to 1 as it
        // could be the reason why timeenrolled was not recorded as there was a code that mark enrolled with time being 0.
        $setting = array('enablecompletion' => 1, 'completionstartonenrol' => 1);
        $course = $this->getDataGenerator()->create_course($setting);

        // Create a user
        $user = $this->getDataGenerator()->create_user();

        // Create an audience.
        $cohort = $this->getDataGenerator()->create_cohort();

        // Configure audience sync as the enrolment method in the course.
        $cohortplugin = enrol_get_plugin('cohort');
        $cohortplugin->add_instance($course, array('customint1' => $cohort->id, 'roleid' => $studentrole->id));

        // Add user to the audience.
        cohort_add_member($cohort->id, $user->id);

        // Sync.
        enrol_cohort_sync($trace, $course->id);
        $this->assertEquals(1, $DB->count_records('user_enrolments', array()));

        // Now check the completion record, timeenrolled is greater than 0.
        $this->assertGreaterThan(0, (int)$DB->get_field('course_completions', 'timeenrolled', array('userid' => $user->id, 'course' => $course->id)));
    }
}
