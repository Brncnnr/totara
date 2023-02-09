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
 * @author Vernon Denny <vernon.denny@totaralearning.com>
 * @package completion
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

global $CFG;
require_once($CFG->dirroot . '/completion/criteria/completion_criteria.php');
require_once($CFG->dirroot . '/completion/criteria/completion_criteria_duration.php');

class completion_completion_criteria_duration_test extends advanced_testcase {

    /** @var  \core\testing\generator $data_generator */
    protected $generator;

    /** @var \core_completion\testing\generator $completion_generator */
    protected $completion_generator;

    protected function tearDown(): void {
        $this->generator = null;
        $this->completion_generator = null;
        parent::tearDown();
    }

    protected function setUp(): void {
        parent::setup();

        $this->generator = $this->getDataGenerator();
        $this->completion_generator = $this->getDataGenerator()->get_plugin_generator('core_completion');
    }

    /**
     * Tests the method completion_criteria_duration->get_timeenrolled.
     *
     * In this case we call the completion criterion review method to check that get_timeenrolled() method
     * does not crash during review() when multiple enrolments exist on a course for a specific user.
     */
    public function test_completion_criteria_duration_get_timeenrolled() {
        global $DB;

        // Create multiple courses and one user.
        $user1 = $this->generator->create_user();
        $course1 = $this->generator->create_course();
        $course2 = $this->generator->create_course();
        $course3 = $this->generator->create_course();

        // Enrol user to all courses.
        $this->generator->enrol_user($user1->id, $course1->id);
        $this->generator->enrol_user($user1->id, $course2->id);
        $this->generator->enrol_user($user1->id, $course3->id);

        // Manipulate all enrolments onto same course id (generator does not enable multiple enrolments for a user).
        $DB->set_field('enrol', 'courseid', $course2->id);

        // Run review on the completion of course2 criteria for user1 - should not crash.
        $completion_criteria_duration = new completion_criteria_duration(array(
            'course' => $course2->id,
            'criteriatype' => COMPLETION_CRITERIA_TYPE_DURATION,
            'courseinstance' => $course2->id
        ));
        $criteria_completion = new completion_criteria_completion(array(
            'course' => $course2->id,
            'userid' => $user1->id,
            'criteriaid' => $completion_criteria_duration->id
        ));
        $this->assertEquals(false, $completion_criteria_duration->review($criteria_completion));
    }
}
