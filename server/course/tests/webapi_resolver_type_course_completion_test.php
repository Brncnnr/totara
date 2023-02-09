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
 * @author David Curry <david.curry@totaralearning.com>
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Tests the totara core course completion type resolver.
 */
class totara_core_webapi_resolver_type_course_completion_testcase extends advanced_testcase {
    private $context;

    protected function tearDown(): void {
        $this->context = null;
    }

    private function resolve($field, $item, array $args = []) {
        $excontext = $this->get_execution_context();
        $excontext->set_relevant_context($this->context);

        return \core\webapi\resolver\type\course_completion::resolve(
            $field,
            $item,
            $args,
            $excontext
        );
    }

    private function get_execution_context(string $type = 'dev', ?string $operation = null) {
        return \core\webapi\execution_context::create($type, $operation);
    }

    /**
     * Create some courses and assign some users for testing.
     * @return []
     */
    private function create_dataset(array $users = []) {
        $users = [];
        $users[] = $this->getDataGenerator()->create_user();
        $users[] = $this->getDataGenerator()->create_user();
        $users[] = $this->getDataGenerator()->create_user();

        $courses = [];
        $courses[] = $this->getDataGenerator()->create_course(['shortname' => 'c1', 'fullname' => 'course1', 'summary' => 'first course']);
        $courses[] = $this->getDataGenerator()->create_course(['shortname' => 'c2', 'fullname' => 'course2', 'summary' => 'second course']);

        // Set-up a default context for the resolver.
        $this->context = \context_course::instance($courses[0]->id);

        $completion_generator = $this->getDataGenerator()->get_plugin_generator('core_completion');
        $completion_generator->enable_completion_tracking($courses[0]);
        $completion_generator->enable_completion_tracking($courses[1]);

        // Criteria
        $completioncriteria = [];

        $enddate = strtotime("+1 week");
        $completioncriteria[COMPLETION_CRITERIA_TYPE_DATE] = $enddate;
        $completioncriteria[COMPLETION_CRITERIA_TYPE_DURATION] = 2 * 86400;
        $completioncriteria[COMPLETION_CRITERIA_TYPE_GRADE] = 75.0;
        $completion_generator->set_completion_criteria($courses[0], $completioncriteria);

        $completion_generator->set_course_criteria_course_completion($courses[1], array($courses[0]->id), COMPLETION_AGGREGATION_ALL);

        $enrol_time = time();
        $this->getDataGenerator()->enrol_user($users[0]->id, $courses[0]->id, 'student', 'manual', $enrol_time);
        $this->getDataGenerator()->enrol_user($users[1]->id, $courses[0]->id, 'student', 'manual', $enrol_time);
        $this->getDataGenerator()->enrol_user($users[1]->id, $courses[1]->id, 'student', 'manual', $enrol_time);

        return [$users, $courses];
    }

    /**
     * Mimic the code fetching completion objects in the course type resolver
     * for the user currently set in phpunit.
     *
     * @param int $courseid
     * @return completion_completion
     */
    private function fetch_course_completion($courseid) {
        global $USER;

        $this->context = \context_course::instance($courseid);

        $params = ['userid' => $USER->id, 'course' => $courseid];
        return new \completion_completion($params);
    }

    /**
     * Check that this only works for course completions.
     */
    public function test_resolve_completions_only() {

        list($users, $courses) = $this->create_dataset();
        $this->setAdminUser();

        try {
            // Attempt to resolve an integer.
            $this->resolve('statuskey', 7);
            $this->fail('Only completion_completion objects should be accepted');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Only completion_completion objects are accepted: integer',
                $ex->getMessage()
            );
        }

        try {
            // Attempt to resolve an array.
            $this->resolve('statuskey', ['statuskey' => 7]);
            $this->fail('Only course instances should be accepted');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Only completion_completion objects are accepted: array',
                $ex->getMessage()
            );
        }

        try {
            // Attempt to resolve a user item.
            $this->resolve('statuskey', $users[0]);
            $this->fail('Only course instances should be accepted');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Only completion_completion objects are accepted: object',
                $ex->getMessage()
            );
        }

        try {
            // Attempt to resolve an invalid object.
            $faux = new \stdClass();
            $faux->statuskey = -1;
            $this->resolve('statuskey', $faux);
            $this->fail('Only course instances should be accepted');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Only completion_completion objects are accepted: object',
                $ex->getMessage()
            );
        }

        // Check that each core instance of course completion gets resolved.
        $completion = $this->fetch_course_completion($courses[0]->id);
        try {
            $value = $this->resolve('statuskey', $completion);
            $this->assertEquals(\completion_completion::get_status($completion), $value);
        } catch (\coding_exception $ex) {
            $this->fail($ex->getMessage());
        }
    }

    /**
     * Test the course completion type resolver for the status field
     */
    public function test_resolve_status() {
        list($users, $courses) = $this->create_dataset();
        $this->setUser($users[0]);
        $course = get_course($courses[0]->id);
        $completion = $this->fetch_course_completion($course->id);

        // Check that each core instance of course completion gets resolved correctly.
        $value = $this->resolve('status', $completion);
        $this->assertEquals(10, $value);
        $this->assertTrue(is_string($value));
    }

    /**
     * Test the course completion type resolver for the statuskey field
     */
    public function test_resolve_statuskey() {
        list($users, $courses) = $this->create_dataset();
        $this->setUser($users[0]);
        $course = get_course($courses[0]->id);
        $completion = $this->fetch_course_completion($course->id);

        // Check that each core instance of course completion gets resolved correctly.
        $value = $this->resolve('statuskey', $completion);
        $this->assertEquals('notyetstarted', $value);
        $this->assertTrue(is_string($value));
    }

    /**
     * Test the course completion type resolver for the progress field
     */
    public function test_resolve_progress() {
        list($users, $courses) = $this->create_dataset();
        $this->setUser($users[0]);
        $course = get_course($courses[0]->id);
        $completion = $this->fetch_course_completion($course->id);

        // Check that each core instance of course completion gets resolved correctly.
        $value = $this->resolve('progress', $completion);
        $this->assertEquals(0, $value);
    }
    /**
     * Test the course completion type resolver for the timecompleted field
     */
    public function test_resolve_timecompleted() {
        list($users, $courses) = $this->create_dataset();
        $this->setUser($users[0]);
        $course = get_course($courses[0]->id);
        $completion = $this->fetch_course_completion($course->id);

        // Check that each core instance of course completion gets resolved correctly.
        $value = $this->resolve('timecompleted', $completion);
        $this->assertEquals(null, $value);
    }
    /**
     * Test the course completion type resolver for the gradefinal field
     */
    public function test_resolve_gradefinal() {
        list($users, $courses) = $this->create_dataset();
        $this->setUser($users[0]);
        $course = get_course($courses[0]->id);
        $completion = $this->fetch_course_completion($course->id);

        // Check that each core instance of course completion gets resolved correctly.
        $value = $this->resolve('gradefinal', $completion);
        $this->assertEquals(0, $value);
    }
    /**
     * Test the course completion type resolver for the grademax field
     */
    public function test_resolve_grademax() {
        list($users, $courses) = $this->create_dataset();
        $this->setUser($users[0]);
        $course = get_course($courses[0]->id);
        $completion = $this->fetch_course_completion($course->id);

        // Check that each core instance of course completion gets resolved correctly.
        $value = $this->resolve('grademax', $completion);
        $this->assertEquals(100, $value);
    }
    /**
     * Test the course completion type resolver for the gradepercentage field
     */
    public function test_resolve_gradepercentage() {
        list($users, $courses) = $this->create_dataset();
        $this->setUser($users[0]);
        $course = get_course($courses[0]->id);
        $completion = $this->fetch_course_completion($course->id);

        // Check that each core instance of course completion gets resolved correctly.
        $value = $this->resolve('gradepercentage', $completion);
        $this->assertEquals(0.0, $value);
    }
}
