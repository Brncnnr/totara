<?php
/*
* This file is part of Totara Learn
*
* Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
* @package core_completion
*/

defined('MOODLE_INTERNAL') || die();

global $CFG;

use totara_webapi\phpunit\webapi_phpunit_helper;

class webapi_resolver_mutation_course_view_testcase extends advanced_testcase {

    private const MUTATION = 'core_course_view';

    use webapi_phpunit_helper;

    protected function setUp(): void {
        global $CFG;
        parent::setup();

        $CFG->enablecompletion = true;
    }

    private function get_execution_context(string $type = 'dev', ?string $operation = null) {
        return \core\webapi\execution_context::create($type, $operation);
    }

    /**
     * Create a course with completion enabled to test the mutation resolver.
     *
     * @param stdClass $user - an optional user to enrol in the course.
     */
    private function create_completion_course($user = null) {
        $coursedefaults = array(
            'enablecompletion' => COMPLETION_ENABLED,
            'completionstartonenrol' => 1,
            'completionprogressonview' => 0,
        );

        $course = $this->getDataGenerator()->create_course($coursedefaults, array('createsections' => true));

        // Enrol user if present.
        if (!empty($user)) {
            $this->getDataGenerator()->enrol_user($user->id, $course->id);
        }

        return $course;
    }

    /**
     * Test the expected exception when calling the course view mutation without any args.
     */
    public function test_resolve_course_view_no_args() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        try {
            $result = $this->resolve_graphql_mutation(self::MUTATION, []);
            self::fail('Expected an exception');
        } catch (\exception $ex) {
            self::assertSame('Invalid course', $ex->getMessage());
        }
    }

    /**
     * Test the expected exception when calling the course view mutation with an invalid course_id.
     */
    public function test_resolve_course_view_invalid_course_arg() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $course = $this->create_completion_course($user);
        try {
            $args = ['course_id' => $course->id + 123];
            $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
            self::fail('Expected an exception');
        } catch (\exception $ex) {
            self::assertSame('Invalid course', $ex->getMessage());
        }
    }

    /**
     * Test the expected exception when calling the course view mutation with valid but mismatched
     * course_id and sectionid arguments.
     */
    public function test_resolve_course_view_mismatched_args() {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $c1 = $this->create_completion_course($user);
        $c2 = $this->create_completion_course($user);
        $sectionid = $DB->get_field_sql(
            "SELECT MAX(id) FROM {course_sections} WHERE course = :cid",
            ["cid" => $c2->id]
        );
        try {
            $args = ['course_id' => $c1->id, 'section_id' => $sectionid];
            $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
            self::fail('Expected an exception');
        } catch (\exception $ex) {
            $this->assertStringStartsWith('Can not find data record in database', $ex->getMessage());
        }
    }

    /**
     * Test resolve course view with valid course_id arg
     *
     * @throws coding_exception
     */
    public function test_resolve_course_view_course_arg() {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $course = $this->create_completion_course($user);
        $eventsink = $this->redirectEvents();
        try {
            $args = ['course_id' => $course->id];
            $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
            self::assertTrue($result);
        } catch (\moodle_exception $ex) {
            self::fail($ex->getMessage());
        }

        $events = $eventsink->get_events();
        $eventsink->clear();
        $this->assertCount(1, $events);

        $event = array_pop($events);
        $this->assertInstanceOf(\core\event\course_viewed::class, $event);
        $this->assertSame('course', $event->target);
        $this->assertSame('viewed', $event->action);
        $this->assertSame($user->id, $event->userid);
        $this->assertSame($course->id, $event->courseid);
        $this->assertEmpty($event->other); // Only filled in with sectionid!
    }

    /**
     * Test resolve course view with valid courseid and sectionid args
     *
     * @throws coding_exception
     */
    public function test_resolve_course_view_section_arg() {
        global $DB;

        $eventsink = $this->redirectEvents();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $course = $this->create_completion_course($user);
        $sectionid = $DB->get_field_sql(
            "SELECT MAX(id) FROM {course_sections} WHERE course = :cid",
            ["cid" => $course->id]
        );

        $eventsink = $this->redirectEvents();
        try {
            $args = ['course_id' => $course->id, 'section_id' => $sectionid];
            $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
            self::assertTrue($result);
        } catch (\moodle_exception $ex) {
            self::fail($ex->getMessage());
        }

        $events = $eventsink->get_events();
        $eventsink->clear();
        $this->assertCount(1, $events);

        $event = array_pop($events);
        $this->assertInstanceOf(\core\event\course_viewed::class, $event);
        $this->assertSame('course', $event->target);
        $this->assertSame('viewed', $event->action);
        $this->assertSame($user->id, $event->userid);
        $this->assertSame($course->id, $event->courseid);

        $expected = ['coursesectionnumber' => '5'];
        $this->assertSame($expected, $event->other);
    }

    public function test_resolve_course_view_middleware() {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $course = $this->create_completion_course();
        try {
            $args = ['course_id' => $course->id];
            $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
            self::fail('Expected an exception');
        } catch (\exception $ex) {
            self::assertSame('Course or activity not accessible. (Not enrolled)', $ex->getMessage());
        }
    }
}
