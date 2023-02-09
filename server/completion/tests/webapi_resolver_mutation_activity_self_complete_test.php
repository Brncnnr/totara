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
class core_completion_webapi_resolver_mutation_activity_self_complete_testcase extends advanced_testcase {
    private const MUTATION = 'core_completion_activity_self_complete';

    use webapi_phpunit_helper;

    protected function setUp(): void {
        global $CFG;
        parent::setup();

        $CFG->enablecompletion = true;
    }

    private function get_execution_context(string $type = 'dev', ?string $operation = null) {
        return \core\webapi\execution_context::create($type, $operation);
    }

    private function create_self_completion_activity($data) {
        $data = (array)$data;
        $coursedefaults = array(
            'enablecompletion' => COMPLETION_ENABLED,
            'completionstartonenrol' => 1,
            'completionprogressonview' => 1,
        );

        $course = $this->getDataGenerator()->create_course($coursedefaults, array('createsections' => true));

        // Enrol user if present.
        if (!empty($data['user'])) {
            $this->getDataGenerator()->enrol_user($data['user']->id, $course->id);
        }

        $activity_completion = COMPLETION_TRACKING_MANUAL;
        if (!empty($data['noself'])) {
            $activity_completion = COMPLETION_TRACKING_NONE;
        }

        $page = $this->getDataGenerator()->get_plugin_generator('mod_page')->create_instance(
            array('course' => $course->id, 'completion' => $activity_completion)
        );

        return $page;
    }

    public function test_resolve_nologgedin() {
        $page1 = $this->create_self_completion_activity([]);
        try {
            $args = ['cmid' => $page1->cmid, 'complete' => true];
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            self::fail('Expected a require_login_exception: Course or activity not accessible. (You are not logged in)');
        } catch (\require_login_exception $ex) {
            self::assertSame('Course or activity not accessible. (You are not logged in)', $ex->getMessage());
        }
    }

    public function test_resolve_guestuser() {
        $this->setGuestUser();

        $page1 = $this->create_self_completion_activity([]);
        try {
            $args = ['cmid' => $page1->cmid, 'complete' => true];
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
        $page1 = $this->create_self_completion_activity([]);
        try {
            $args = ['cmid' => $page1->cmid, 'complete' => true];
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            self::fail('Expected a require_login_exception: Course or activity not accessible. (Not enrolled)');
        } catch (\require_login_exception $ex) {
            self::assertStringContainsString('Course or activity not accessible. (Not enrolled)', $ex->getMessage());
        }
    }

    public function test_resolve_enrolleduser() {
        global $PAGE;

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Actually mark an activity as complete.
        $page1 = $this->create_self_completion_activity(['user' => $user]);
        try {
            $args = ['cmid' => $page1->cmid, 'complete' => true];
            $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
            self::assertTrue($result);
        } catch (\moodle_exception $ex) {
            self::fail('Unexpected exception where activity should have been marked completed.');
        }

        // Try marking it complete again.
        try {
            $args = ['cmid' => $page1->cmid, 'complete' => true];
            $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
            self::assertTrue($result);
        } catch (\moodle_exception $ex) {
            self::fail('Unexpected exception where activity should have been marked completed.');
        }

        // Try marking it as incomplete
        try {
            $args = ['cmid' => $page1->cmid, 'complete' => false];
            $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
            self::assertTrue($result);
        } catch (\moodle_exception $ex) {
            self::fail('Unexpected exception where activity should have been marked incomplete.');
        }

        // We need to reset the page first or it thinks we're double setting the context.
        $PAGE->reset_theme_and_output();

        // Create another activity without completion.
        $page2 = $this->getDataGenerator()->get_plugin_generator('mod_page')->create_instance(
            array('course' => $page1->course, 'completion' => COMPLETION_TRACKING_NONE)
        );
        try {
            $args = ['cmid' => $page2->cmid, 'complete' => true];
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            self::fail('Expected a moodle_exception: Completion is not enabled');
        } catch (\moodle_exception $ex) {
            self::assertStringContainsString('Completion is not enabled', $ex->getMessage());
        }

        // We need to reset the page first or it thinks we're double setting the context.
        $PAGE->reset_theme_and_output();

        // Create another course with completion enabled but not self-completion
        $page3 = $this->getDataGenerator()->get_plugin_generator('mod_page')->create_instance(
            array('course' => $page1->course, 'completion' => COMPLETION_TRACKING_AUTOMATIC)
        );
        try {
            $args = ['cmid' => $page3->cmid, 'complete' => true];
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

        $page1 = $this->create_self_completion_activity(['user' => $user]);

        $records = $DB->get_records('course_modules_completion', ['userid' => $user->id]);
        $this->assertEquals(0, count($records));

        $map = function($obj) {
            return (array)$obj;
        };

        // Complete the activity.
        $result = \totara_webapi\graphql::execute_operation(
            \core\webapi\execution_context::create('ajax', 'core_completion_activity_self_complete'),
            ['cmid' => $page1->cmid, 'complete' => true]
        );
        self::assertSame(
            ['data' => ['core_completion_activity_self_complete' => true]],
            array_map($map, $result->toArray(true))
        );

        $records = $DB->get_records('course_modules_completion', ['userid' => $user->id]);
        $this->assertEquals(1, count($records));
        $record = array_pop($records);
        $this->assertEquals(1, $record->completionstate);

        // Remove activity completion.
        $result = \totara_webapi\graphql::execute_operation(
            \core\webapi\execution_context::create('ajax', 'core_completion_activity_self_complete'),
            ['cmid' => $page1->cmid, 'complete' => false]
        );
        self::assertSame(
            ['data' => ['core_completion_activity_self_complete' => true]],
            array_map($map, $result->toArray(true))
        );

        $records = $DB->get_records('course_modules_completion', ['userid' => $user->id]);
        $this->assertEquals(1, count($records));
        $record = array_pop($records);
        $this->assertEquals(0, $record->completionstate);
    }

}
