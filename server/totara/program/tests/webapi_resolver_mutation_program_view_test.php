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
* @package totara_program
*/

defined('MOODLE_INTERNAL') || die();

global $CFG;

use totara_webapi\phpunit\webapi_phpunit_helper;

class webapi_resolver_mutation_program_view_testcase extends advanced_testcase {

    private const MUTATION = 'totara_program_program_view';

    use webapi_phpunit_helper;

    private function get_execution_context(string $type = 'dev', ?string $operation = null) {
        return \core\webapi\execution_context::create($type, $operation);
    }

    /**
     * Create a program to test the mutation resolver.
     *
     * @param stdClass $user - an optional user to enrol in the program.
     */
    private function create_test_program($user = null) {
        $pgen = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $program = $pgen->create_program();


        // Enrol user if present.
        if (!empty($user)) {
            $pgen->assign_to_program($program->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        }

        return $program;
    }

    /**
     * Test the expected exception when calling the program view mutation without any args.
     */
    public function test_resolve_program_view_no_args() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        try {
            $result = $this->resolve_graphql_mutation(self::MUTATION, []);
            self::fail('Expected an exception');
        } catch (\exception $ex) {
            self::assertSame('Invalid parameter value detected (programid)', $ex->getMessage());
        }
    }

    /**
     * Test the expected exception when calling the program view mutation with an invalid program_id.
     */
    public function test_resolve_program_view_invalid_program_arg() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $program = $this->create_test_program($user);
        try {
            $args = ['program_id' => $program->id + 123];
            $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
            self::fail('Expected an exception');
        } catch (\exception $ex) {
            self::assertSame('Invalid parameter value detected (programid)', $ex->getMessage());
        }
    }

    /**
     * Test the expected exception when calling the program_view mutation without access to the program.
     */
    public function test_resolve_program_view_no_access() {
        $user = self::getDataGenerator()->create_user();
        self::setUser($user);

        $pgen = self::getDataGenerator()->get_plugin_generator('totara_program');
        $program = $pgen->create_program(['visible' => 0]);

        try {
            $args = ['program_id' => $program->id];
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            self::fail('Expected an exception');
        } catch (exception $ex) {
            self::assertSame('Invalid parameter value detected (programid)', $ex->getMessage());
        }
    }

    /**
     * Test resolve program view with valid programid arg
     *
     * @throws coding_exception
     */
    public function test_resolve_program_view_program_arg() {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $program = $this->create_test_program($user);
        $eventsink = $this->redirectEvents();
        try {
            $args = ['program_id' => $program->id];
            $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
            self::assertTrue($result);
        } catch (\moodle_exception $ex) {
            self::fail($ex->getMessage());
        }

        $events = $eventsink->get_events();
        $eventsink->clear();
        $this->assertCount(1, $events);

        $event = array_pop($events);
        $this->assertInstanceOf(\totara_program\event\program_viewed::class, $event);
        $this->assertSame('program', $event->target);
        $this->assertSame('viewed', $event->action);
        $this->assertSame($user->id, $event->userid);
        $this->assertSame(0, $event->courseid);
        $this->assertSame($program->id, $event->objectid);

        $expected = ['section' => 'general'];
        $this->assertSame($expected, $event->other);
    }

    public function test_resolve_program_view_middleware() {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        // Don't log in since that's the middleware.

        $program = $this->create_test_program();
        try {
            $args = ['program_id' => $program->id];
            $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
            self::fail('Expected an exception');
        } catch (\exception $ex) {
            self::assertSame('Course or activity not accessible. (You are not logged in)', $ex->getMessage());
        }
    }
}
