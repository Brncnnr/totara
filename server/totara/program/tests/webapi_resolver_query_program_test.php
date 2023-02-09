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
 * @package totara_program
 */

defined('MOODLE_INTERNAL') || die();

use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * Tests the totara job assignment query resolver
 */
class totara_program_webapi_resolver_query_program_testcase extends advanced_testcase {

    use webapi_phpunit_helper;

    /**
     *
     * Create some programs and assign some users for testing.
     * @return []
     */
    private function create_faux_programs(array $users = []) {
        $users = [];
        $users[] = $this->getDataGenerator()->create_user();
        $users[] = $this->getDataGenerator()->create_user();
        $users[] = $this->getDataGenerator()->create_user();

        $prog_gen = $this->getDataGenerator()->get_plugin_generator('totara_program');

        $c1 = $this->getDataGenerator()->create_course();
        $c2 = $this->getDataGenerator()->create_course();
        $c3 = $this->getDataGenerator()->create_course();
        $c4 = $this->getDataGenerator()->create_course();

        $programs = [];
        $programs[] = $prog_gen->create_program(['shortname' => 'p1', 'fullname' => 'prog1']);
        $programs[] = $prog_gen->create_program(['shortname' => 'p2', 'fullname' => 'prog2']);
        $programs[] = $prog_gen->create_program(['shortname' => 'p3', 'fullname' => 'prog3', 'visible' => 0]);

        $prog_gen->add_courses_and_courseset_to_program($programs[0], [[$c1, $c2], [$c3]], CERTIFPATH_STD);
        $prog_gen->add_courses_and_courseset_to_program($programs[1], [[$c3], [$c4]], CERTIFPATH_STD);

        $prog_gen->assign_program($programs[0]->id, [$users[0]->id, $users[1]->id]);
        $prog_gen->assign_program($programs[1]->id, [$users[1]->id]);

        return [$users, $programs];
    }
    /**
     * Test the results of the query when the current user is not logged in.
     */
    public function test_resolve_no_login() {
        list($users, $programs) = $this->create_faux_programs();

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Course or activity not accessible. (You are not logged in)');

        $this->resolve_graphql_query('totara_program_program', ['programid' => $programs[0]->id]);
    }

    /**
     * Test the results of the query when the current user is logged in as the guest user.
     */
    public function test_resolve_guest_user() {
        list($users, $programs) = $this->create_faux_programs();
        $this->setGuestUser();

        // Guests can view programs (shouldn't have completion data though so...)
        $result = $this->resolve_graphql_query('totara_program_program', ['programid' => $programs[0]->id]);
        $this->assertEquals($programs[0]->id, $result->id);
        $this->assertEquals($programs[0]->fullname, $result->fullname);
        $this->assertEquals($programs[0]->shortname, $result->shortname);

        // Guests should not be able to see hidden programs however.
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Current user can not access this program.');

        $this->resolve_graphql_query('totara_program_program', ['programid' => $programs[2]->id]);
    }

    /**
     * Test the results of the query when the current user is the site administrator.
     */
    public function test_resolve_admin_user() {
        list($users, $programs) = $this->create_faux_programs();
        $this->setAdminUser();

        // Admins should be able to see programs, again without completion data.
        $result = $this->resolve_graphql_query('totara_program_program', ['programid' => $programs[0]->id]);
        $this->assertEquals($programs[0]->id, $result->id);
        $this->assertEquals($programs[0]->fullname, $result->fullname);
        $this->assertEquals($programs[0]->shortname, $result->shortname);

        // They should also be able to see hidden programs.
        $result = $this->resolve_graphql_query('totara_program_program', ['programid' => $programs[2]->id]);
        $this->assertEquals($programs[2]->id, $result->id);
        $this->assertEquals($programs[2]->fullname, $result->fullname);
        $this->assertEquals($programs[2]->shortname, $result->shortname);
    }

    /**
     * Test the results of the query match expectations for a course learning item.
     */
    public function test_resolve_program() {
        list($users, $programs) = $this->create_faux_programs();

        // Check access for user 0.
        $this->setUser($users[0]);

        // User should be able to see program 1, with full assignment/completion data.
        $result = $this->resolve_graphql_query('totara_program_program', ['programid' => $programs[0]->id]);
        $this->assertEquals($programs[0]->id, $result->id);

        // User should be able to see program 2, with out any assignment/completion data.
        $result = $this->resolve_graphql_query('totara_program_program', ['programid' => $programs[1]->id]);
        $this->assertEquals($programs[1]->id, $result->id);

        // User should not be able to see program 3, its hidden.
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Current user can not access this program.');

        $this->resolve_graphql_query('totara_program_program', ['programid' => $programs[2]->id]);
    }

    /**
     * Test the results of the AJAX query through the GraphQL stack.
     */
    public function test_ajax_query() {
        list($users, $programs) = $this->create_faux_programs();

        $this->setUser($users[0]);
        $result = $this->execute_graphql_operation('totara_program_program', ['programid' => $programs[0]->id]);
        $data = $result->toArray()['data'];

        $completion = prog_load_completion($programs[0]->id, $users[0]->id);

        $coursesets = $programs[0]->get_content()->get_course_sets();
        $expected = [
            "totara_program_program" => [
                "id" => "{$programs[0]->id}",
                "idnumber" => "",
                "fullname" => "prog1",
                "shortname" => "p1",
                "summary" => "",
                "summaryformat" => 'HTML',
                "endnote" => "",
                "duedate" => null,
                "duedate_state" => null,
                "coursesets" => [
                    0 => [
                        "id" => "{$coursesets[0]->id}",
                        "label" => "Course Set 1"
                    ],
                    1 => [
                        "id" => "{$coursesets[1]->id}",
                        "label" => "Course Set 2"
                    ]
                ],
                'completion' => [
                    'id' => $completion->id,
                    'status' => 0,
                    'statuskey' => 'incomplete',
                    'timecompleted' => null,
                    'progress' => 0.0
                ],
                "availablefrom" => null,
                "availableuntil" => null,
                "category" => [
                    "name" => "Miscellaneous"
                ]
            ]
        ];
        $this->assertSame($expected, $data);
    }
}
