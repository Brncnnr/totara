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
 * @author David Curry <david.curry@totaralearning.com>
 * @package totara_mobile
 */

defined('MOODLE_INTERNAL') || die();

use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * Tests the totara job assignment query resolver
 */
class totara_mobile_webapi_resolver_query_program_testcase extends advanced_testcase {

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

        $c1 = $this->getDataGenerator()->create_course([
            'fullname' => 'course1',
            'summary' => 'course1 summary'
        ]);
        $c2 = $this->getDataGenerator()->create_course([
            'fullname' => 'course2',
            'summary' => 'course2 summary'
        ]);
        $c3 = $this->getDataGenerator()->create_course([
            'fullname' => 'course3',
            'summary' => '<div class=\'summary\'>course3 summary</div>'
        ]);
        $c4 = $this->getDataGenerator()->create_course([
            'fullname' => 'course4',
            'summary' => ''
        ]);

        $programs = [];
        $programs[] = $prog_gen->create_program([
            'shortname' => 'p1',
            'fullname' => 'prog1',
            'summary' => 'The first program',
            'endnote' => 'You\'ve finished the first program'
        ]);

        $programs[] = $prog_gen->create_program([
            'shortname' => 'p2',
            'fullname' => 'prog2'
        ]);

        $programs[] = $prog_gen->create_program([
            'shortname' => 'p3',
            'fullname' => 'prog3',
            'visible' => 0
        ]);

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

        $this->resolve_graphql_query('totara_mobile_program', ['programid' => $programs[0]->id]);
    }

    /**
     * Test the results of the query when the current user is logged in as the guest user.
     */
    public function test_resolve_guest_user() {
        list($users, $programs) = $this->create_faux_programs();
        $this->setGuestUser();

        // Guests can view programs (shouldn't have completion data though so...)
        $result = $this->resolve_graphql_query('totara_mobile_program', ['programid' => $programs[0]->id]);
        $this->assertEquals($programs[0]->id, $result->id);
        $this->assertEquals($programs[0]->fullname, $result->fullname);
        $this->assertEquals($programs[0]->shortname, $result->shortname);

        // Guests should not be able to see hidden programs however.
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Current user can not access this program.');

        $this->resolve_graphql_query('totara_mobile_program', ['programid' => $programs[2]->id]);
    }

    /**
     * Test the results of the query when the current user is the site administrator.
     */
    public function test_resolve_admin_user() {
        list($users, $programs) = $this->create_faux_programs();
        $this->setAdminUser();

        // Admins should be able to see programs, again without completion data.
        $result = $this->resolve_graphql_query('totara_mobile_program', ['programid' => $programs[0]->id]);
        $this->assertEquals($programs[0]->id, $result->id);
        $this->assertEquals($programs[0]->fullname, $result->fullname);
        $this->assertEquals($programs[0]->shortname, $result->shortname);

        // They should also be able to see hidden programs.
        $result = $this->resolve_graphql_query('totara_mobile_program', ['programid' => $programs[2]->id]);
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
        $result = $this->resolve_graphql_query('totara_mobile_program', ['programid' => $programs[0]->id]);
        $this->assertEquals($programs[0]->id, $result->id);

        // User should be able to see program 2, with out any assignment/completion data.
        $result = $this->resolve_graphql_query('totara_mobile_program', ['programid' => $programs[1]->id]);
        $this->assertEquals($programs[1]->id, $result->id);

        // User should not be able to see program 3, its hidden.
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Current user can not access this program.');

        $this->resolve_graphql_query('totara_mobile_program', ['programid' => $programs[2]->id]);
    }

    /**
     * Test the results of the embedded mobile query through the GraphQL stack.
     */
    public function test_embedded_query() {
       list($users, $programs) = $this->create_faux_programs();

       $this->setUser($users[0]);

        global $CFG;

        try {
            $result = \totara_webapi\graphql::execute_operation(
                \core\webapi\execution_context::create('mobile', 'totara_mobile_program'),
                ["programid" => $programs[0]->id]
            );

            $data = $result->toArray()['data'];

            // Retrieve some data to compare against.
            $coursesets = $programs[0]->get_content()->get_course_sets();
            $cs1 = array_shift($coursesets);
            $cs1courses = $cs1->get_courses();
            $cs2 = array_shift($coursesets);
            $cs2courses = $cs2->get_courses();

            $expectedsetdata = [];

            // Set up the expected data for the first courseset.
            $csets = $programs[0]->get_content()->get_course_sets();
            $courseset = array_shift($csets);

            $csdata = [];
            $csdata['id'] = "{$courseset->id}";
            $csdata['label'] = $courseset->label;
            $csdata['nextsetoperator'] = empty($courseset->islastset) ? 'THEN' : null;
            $csdata['completionCriteria'] = ['All courses in this set must be completed (unless this is an optional set).'];
            $csdata['statuskey'] = 'incomplete';

            $cscourses = [];
            foreach ($courseset->get_courses() as $course) {
                $cinfo = [];
                $cinfo['id'] = 'course_' . $course->id;
                $cinfo['itemtype'] = 'course';
                $cinfo['itemcomponent'] = 'core_course';
                $cinfo['shortname'] = $course->shortname;
                $cinfo['fullname'] = $course->fullname;
                $cinfo['summary'] = format_text($course->summary, FORMAT_HTML, ['context' => \context_course::instance($course->id)]);
                $cinfo['summaryFormat'] = 'HTML';
                $cinfo['progress'] = null;
                $cinfo['urlView'] = 'https://www.example.com/moodle/course/view.php?id=' . $course->id;
                $cinfo['duedate'] = null;
                $cinfo['duedateState'] = null;
                $cinfo['native'] = false;
                $cinfo['imageSrc'] = 'https://www.example.com/moodle/theme/image.php/_s/ventura/core/1/course_defaultimage';
                $cinfo['viewable'] = true;
                $cinfo['__typename'] = 'totara_mobile_program_content';

                $cscourses[] = $cinfo;
            }
            $csdata['courses'] = $cscourses;
            $csdata['__typename'] = 'totara_mobile_program_courseset';

            $expectedsetdata[] = $csdata;

            $completion = prog_load_completion($programs[0]->id, $users[0]->id);

            $expected = [
                "totara_mobile_program" => [
                    "id" => "{$programs[0]->id}",
                    "idnumber" => "",
                    "fullname" => "prog1",
                    "shortname" => "p1",
                    "duedate" => null,
                    "duedateState" => null,
                    "summary" => "The first program",
                    "summaryformat" => "HTML",
                    "endnote" => "You've finished the first program",
                    "endnoteformat" => "HTML",
                    "availablefrom" => null,
                    "availableuntil" => null,
                    "imageSrc" => '',
                    "completion" => [
                        "id" => $completion->id,
                        "statuskey" => 'incomplete',
                        "progress" => 0.0,
                        "__typename" => 'totara_program_completion'
                    ],
                    "currentCourseSets" => [$expectedsetdata],
                    "countUnavailableSets" => 1,
                    "courseSetHeader" => "",
                    "__typename" => 'totara_mobile_program'
                ]
            ];
            $this->assertSame($expected, $data);
        } catch (\moodle_exception $ex) {
            $this->fail($ex->getMessage());
        }
    }

    /**
     * Test the results of progam with containing hidden courses
     */
    public function test_query_with_hidden_courses() {
        $user = $this->getDataGenerator()->create_user();
        $c1 = $this->getDataGenerator()->create_course([
            'fullname' => 'course1',
            'summary' => 'course1 summary'
        ]);
        $c2 = $this->getDataGenerator()->create_course([
            'fullname' => 'course2 hidden',
            'summary' => 'course2 summary',
            'visible' => 0,
        ]);

        $prog_gen = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $program = $prog_gen->create_program([
            'shortname' => 'p1',
            'fullname' => 'prog1',
        ]);
        $prog_gen->add_courses_and_courseset_to_program($program, [[$c1, $c2]], CERTIFPATH_STD);
        $prog_gen->assign_program($program->id, [$user->id]);

        $this->setUser($user);

        $result = \totara_webapi\graphql::execute_operation(
            \core\webapi\execution_context::create('mobile', 'totara_mobile_program'),
            ["programid" => $program->id]
        );

        $data = $result->toArray()['data'];

        // Set up the expected data
        $csets = $program->get_content()->get_course_sets();
        $courseset = array_shift($csets);

        $csdata = [];
        $csdata['id'] = "{$courseset->id}";
        $csdata['label'] = $courseset->label;
        $csdata['nextsetoperator'] = empty($courseset->islastset) ? 'THEN' : null;
        $csdata['completionCriteria'] = ['All courses in this set must be completed (unless this is an optional set).'];
        $csdata['statuskey'] = 'incomplete';

        $cscourses = [];
        foreach ($courseset->get_courses() as $course) {
            $cinfo = [];
            $cinfo['id'] = 'course_' . $course->id;
            $cinfo['itemtype'] = 'course';
            $cinfo['itemcomponent'] = 'core_course';
            $cinfo['shortname'] = $course->shortname;
            $cinfo['fullname'] = $course->fullname;
            $cinfo['summary'] = format_text($course->summary, FORMAT_HTML, ['context' => \context_course::instance($course->id)]);
            $cinfo['summaryFormat'] = 'HTML';
            $cinfo['progress'] = null;
            $cinfo['urlView'] = 'https://www.example.com/moodle/course/view.php?id=' . $course->id;
            $cinfo['duedate'] = null;
            $cinfo['duedateState'] = null;
            $cinfo['native'] = false;
            $cinfo['imageSrc'] = 'https://www.example.com/moodle/theme/image.php/_s/ventura/core/1/course_defaultimage';
            $cinfo['viewable'] = $course->id != $c2->id;
            $cinfo['__typename'] = 'totara_mobile_program_content';

            $cscourses[] = $cinfo;
        }
        $csdata['courses'] = $cscourses;
        $csdata['__typename'] = 'totara_mobile_program_courseset';

        $expectedsetdata[] = $csdata;

        $completion = prog_load_completion($program->id, $user->id);

        $expected = [
            "totara_mobile_program" => [
                "id" => "{$program->id}",
                "idnumber" => "",
                "fullname" => "prog1",
                "shortname" => "p1",
                "duedate" => null,
                "duedateState" => null,
                "summary" => "",
                "summaryformat" => "HTML",
                "endnote" => "",
                "endnoteformat" => "HTML",
                "availablefrom" => null,
                "availableuntil" => null,
                "imageSrc" => '',
                "completion" => [
                    "id" => $completion->id,
                    "statuskey" => 'incomplete',
                    "progress" => 0.0,
                    "__typename" => 'totara_program_completion'
                ],
                "currentCourseSets" => [$expectedsetdata],
                "countUnavailableSets" => 0,
                "courseSetHeader" => "",
                "__typename" => 'totara_mobile_program'
            ]
        ];
        $this->assertSame($expected, $data);
    }
}
