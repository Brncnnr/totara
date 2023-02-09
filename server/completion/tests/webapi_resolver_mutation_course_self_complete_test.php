<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @package core_completion
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/completion/criteria/completion_criteria.php');
require_once($CFG->dirroot . '/completion/criteria/completion_criteria_self.php');

use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * Tests the totara job delete assignment mutation
 */
class core_completion_webapi_resolver_mutation_course_self_complete_testcase extends advanced_testcase {
    private const MUTATION = 'core_completion_course_self_complete';

    use webapi_phpunit_helper;

    protected function setUp(): void {
        global $CFG;
        parent::setup();

        $CFG->enablecompletion = true;
    }

    private function get_execution_context(string $type = 'dev', ?string $operation = null) {
        return \core\webapi\execution_context::create($type, $operation);
    }

    private function create_self_completion_course($data) {
        $data = (array)$data;
        $coursedefaults = array(
            'enablecompletion' => COMPLETION_ENABLED,
            'completionstartonenrol' => 1,
            'completionprogressonview' => 1);

        $course = $this->getDataGenerator()->create_course($coursedefaults, array('createsections' => true));

        // Set up self completion.
        if (empty($data['noself'])) {
            $criteriadata = new stdClass();
            $criteriadata->id = $course->id;
            $criteriadata->criteria_activity = array();
            $criteriadata->criteria_self_value = COMPLETION_CRITERIA_TYPE_SELF; // Totara: we have different field names and update_config() method.
            $criterion = new completion_criteria_self();
            $criterion->update_config($criteriadata);
        }

        // Enrol user if present.
        if (!empty($data['user'])) {
            $this->getDataGenerator()->enrol_user($data['user']->id, $course->id);
        }
        return $course;
    }

    public function test_resolve_nologgedin() {
        $course1 = $this->create_self_completion_course([]);
        try {
            $args = ['courseid' => $course1->id];
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            self::fail('Expected a require_login_exception: Course or activity not accessible. (You are not logged in)');
        } catch (\require_login_exception $ex) {
            self::assertSame('Course or activity not accessible. (You are not logged in)', $ex->getMessage());
        }
    }

    public function test_resolve_guestuser() {
        $this->setGuestUser();

        $course1 = $this->create_self_completion_course([]);
        try {
            $args = ['courseid' => $course1->id];
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            self::fail('Expected a require_login_exception: Course or activity not accessible. (Not enrolled)');
        } catch (\require_login_exception $ex) {
            self::assertStringContainsString('Course or activity not accessible. (Not enrolled)', $ex->getMessage());
        }
    }

    public function test_resolve_normaluser() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Note: not enrolled.
        $course1 = $this->create_self_completion_course([]);
        try {
            $args = ['courseid' => $course1->id];
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            self::fail('Expected a require_login_exception: Course or activity not accessible. (Not enrolled)');
        } catch (\require_login_exception $ex) {
            self::assertStringContainsString('Course or activity not accessible. (Not enrolled)', $ex->getMessage());
        }
    }

    public function test_resolve_enrolleduser() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Actually mark a course as complete.
        $course1 = $this->create_self_completion_course(['user' => $user]);
        try {
            $args = ['courseid' => $course1->id];
            $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
            self::assertTrue($result);
        } catch (\moodle_exception $ex) {
            self::fail('Unexpected exception where course should have been marked completed.');
        }

        // Try marking it complete again.
        try {
            $args = ['courseid' => $course1->id];
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            self::fail('Expected a moodle_exception: useralreadymarkedcomplete');
        } catch (\moodle_exception $ex) {
            self::assertStringContainsString('useralreadymarkedcomplete', $ex->getMessage());
        }

        // Create another course without completion.
        $course2 = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user->id, $course2->id);
        try {
            $args = ['courseid' => $course2->id];
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            self::fail('Expected a moodle_exception: Completion is not enabled');
        } catch (\moodle_exception $ex) {
            self::assertStringContainsString('Completion is not enabled', $ex->getMessage());
        }

        // Create another course with completion enabled but not self-completion
        $course3 = $this->create_self_completion_course(['user' => $user, 'noself' => true]);
        try {
            $args = ['courseid' => $course3->id];
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            self::fail('Expected a moodle_exception: noselfcompletioncriteria');
        } catch (\moodle_exception $ex) {
            self::assertStringContainsString('noselfcompletioncriteria', $ex->getMessage());
        }
    }

    /**
     * Integration test of the AJAX mutation through the GraphQL stack.
     */
    public function test_ajax_query() {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $course1 = $this->create_self_completion_course(['user' => $user]);

        $records = $DB->get_records('course_completions', ['userid' => $user->id]);
        $this->assertEquals(1, count($records));
        $record = array_pop($records);
        $this->assertNull($record->timecompleted);

        $map = function($obj) {
            return (array)$obj;
        };

        $result = \totara_webapi\graphql::execute_operation(
            \core\webapi\execution_context::create('ajax', 'core_completion_course_self_complete'),
            ['courseid' => $course1->id]
        );
        self::assertSame(
            ['data' => ['core_completion_course_self_complete' => true]],
            array_map($map, $result->toArray(true))
        );

        $records = $DB->get_records('course_completions', ['userid' => $user->id]);
        $this->assertEquals(1, count($records));
        $record = array_pop($records);
        $this->assertNotNull($record->timecompleted);
    }

}