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
 * @package totara_mobile
 */

defined('MOODLE_INTERNAL') || die();

use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * Tests the totara job assignment query resolver
 */
class totara_mobile_webapi_resolver_query_certification_testcase extends advanced_testcase {

    use webapi_phpunit_helper;

    /**
     * Create some certifications and assign some users for testing.
     * @return []
     */
    private function create_faux_certifications(array $users = []) {

        $users = [];
        $users[] = $this->getDataGenerator()->create_user();
        $users[] = $this->getDataGenerator()->create_user();
        $users[] = $this->getDataGenerator()->create_user();

        $prog_gen = $this->getDataGenerator()->get_plugin_generator('totara_program');

        $c1 = $this->getDataGenerator()->create_course([
            'fullname' => 'course 1',
            'shortname' => 'c1',
            'summary' => 'The first course'
        ]);
        $c2 = $this->getDataGenerator()->create_course([
            'fullname' => 'course 2',
            'shortname' => 'c2',
            'summary' => 'The second course'
        ]);
        $c3 = $this->getDataGenerator()->create_course([
            'fullname' => 'course 3',
            'shortname' => 'c3',
            'summary' => 'The third course'
        ]);
        $c4 = $this->getDataGenerator()->create_course([
            'fullname' => 'course 4',
            'shortname' => 'c4',
            'summary' => 'The fourth course'
        ]);
        $c5 = $this->getDataGenerator()->create_course([
            'fullname' => 'course 5',
            'shortname' => 'c5',
            'summary' => 'The fifth course'
        ]);
        $c6 = $this->getDataGenerator()->create_course([
            'fullname' => 'course 6',
            'shortname' => 'c6',
            'summary' => 'The final course'
        ]);

        $certifications = [];
        $certifications[] = $prog_gen->create_certification([
            'fullname' => 'cert1',
            'shortname' => 'c1',
            'summary' => 'The first certification',
            'endnote' => 'Congratulations on completing the first certification'
        ]);
        $certifications[] = $prog_gen->create_certification(['shortname' => 'c2', 'fullname' => 'cert2']);
        $certifications[] = $prog_gen->create_certification(['shortname' => 'c3', 'fullname' => 'cert3', 'visible' => 0]);

        $prog_gen->add_courses_and_courseset_to_program($certifications[0], [[$c1, $c2], [$c3]], CERTIFPATH_CERT);
        $prog_gen->add_courses_and_courseset_to_program($certifications[0], [[$c1], [$c3]], CERTIFPATH_RECERT);

        $prog_gen->add_courses_and_courseset_to_program($certifications[1], [[$c4, $c5], [$c6]], CERTIFPATH_CERT);
        $prog_gen->add_courses_and_courseset_to_program($certifications[1], [[$c4], [$c6]], CERTIFPATH_RECERT);

        $prog_gen->assign_program($certifications[0]->id, [$users[0]->id, $users[1]->id]);
        $prog_gen->assign_program($certifications[1]->id, [$users[1]->id]);

        // Set the first user to in progress on the first certification.
        list($ccomp, $pcomp) = certif_load_completion($certifications[0]->id, $users[0]->id);
        $pcomp->timestarted = time() - 1;
        $pcomp->timedue = -1; // Make sure this isn't set for now.
        certif_write_completion($ccomp, $pcomp);

        return [$users, $certifications];
    }
    /**
     * Test the results of the query when the current user is not logged in.
     */
    public function test_resolve_no_login() {
        list($users, $certifications) = $this->create_faux_certifications();

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Course or activity not accessible. (You are not logged in)');

        $this->resolve_graphql_query('totara_mobile_certification', ['certificationid' => $certifications[0]->id]);
    }

    /**
     * Test the results of the query when the current user is logged in as the guest user.
     */
    public function test_resolve_guest_user() {
        list($users, $certifications) = $this->create_faux_certifications();
        $this->setGuestUser();

        // Guests can view certifications (shouldn't have completion data though so...)
        $result = $this->resolve_graphql_query('totara_mobile_certification', ['certificationid' => $certifications[0]->id]);
        $this->assertEquals($certifications[0]->id, $result->id);
        $this->assertEquals($certifications[0]->fullname, $result->fullname);
        $this->assertEquals($certifications[0]->shortname, $result->shortname);

        // Guests should not be able to see hidden certifications however.
        try {
            $result = $this->resolve_graphql_query('totara_mobile_certification', ['certificationid' => $certifications[2]->id]);
            $this->fail('Expected a moodle_exception: cannot view certification');
        } catch (\moodle_exception $ex) {
            // Note: I know this is a certification, but this is the require_login generic error.
            $this->assertSame('Coding error detected, it must be fixed by a programmer: Current user can not access this certification.', $ex->getMessage());
        }
    }

    /**
     * Test the results of the query when the current user is the site administrator.
     */
    public function test_resolve_admin_user() {
        list($users, $certifications) = $this->create_faux_certifications();
        $this->setAdminUser();

        // Admins should be able to see certifications, again without completion data.
        $result = $this->resolve_graphql_query('totara_mobile_certification', ['certificationid' => $certifications[0]->id]);
        $this->assertEquals($certifications[0]->id, $result->id);
        $this->assertEquals($certifications[0]->fullname, $result->fullname);
        $this->assertEquals($certifications[0]->shortname, $result->shortname);
    }

    /**
     * Test the results of the query when the current user is the site administrator.
     */
    public function test_resolve_admin_user_hidden_certificate() {
        list($users, $certifications) = $this->create_faux_certifications();
        $this->setAdminUser();

        // They should also be able to see hidden certifications.
        $result = $this->resolve_graphql_query('totara_mobile_certification', ['certificationid' => $certifications[2]->id]);
        $this->assertEquals($certifications[2]->id, $result->id);
        $this->assertEquals($certifications[2]->fullname, $result->fullname);
        $this->assertEquals($certifications[2]->shortname, $result->shortname);
    }

    /**
     * Test the results of the query match expectations for a course learning item.
     */
    public function test_resolve_certification() {
        list($users, $certifications) = $this->create_faux_certifications();

        // Check access for user 0.
        $this->setUser($users[0]);

        // User should be able to see certification 1, with full assignment/completion data.
        $result = $this->resolve_graphql_query('totara_mobile_certification', ['certificationid' => $certifications[0]->id]);
        $this->assertEquals($certifications[0]->id, $result->id);

        // User should be able to see certification 2, with out any assignment/completion data.
        $result = $this->resolve_graphql_query('totara_mobile_certification', ['certificationid' => $certifications[1]->id]);
        $this->assertEquals($certifications[1]->id, $result->id);

        // User should not be able to see certification 3, its hidden.
        try {
            $result = $this->resolve_graphql_query('totara_mobile_certification', ['certificationid' => $certifications[2]->id]);
            $this->fail('Expected a moodle_exception: cannot view certification');
        } catch (\moodle_exception $ex) {
            // Note: I know this is a certification, but this is the require_login generic error.
            $this->assertSame('Coding error detected, it must be fixed by a programmer: Current user can not access this certification.', $ex->getMessage());
        }
    }

    /**
     * Test the results of the embedded mobile query through the GraphQL stack.
     */
    public function test_embedded_query() {
        global $DB;

        list($users, $certifications) = $this->create_faux_certifications();

        $this->setUser($users[0]);
        list($certcompletion, $progcompletion) = certif_load_completion($certifications[0]->id, $users[0]->id);

        try {
            $prog = new \program($certifications[0]->id); // Have to reload the object because of generators...
            $result = \totara_webapi\graphql::execute_operation(
                \core\webapi\execution_context::create('mobile', 'totara_mobile_certification'),
                ["certificationid" => $prog->id]
            );
            $data = $result->toArray()['data'];

            // Retrieve some data to compare against.
            $coursesets = $prog->content->get_course_sets_path(CERTIFPATH_CERT);
            $cs1 = array_shift($coursesets);
            $cs1courses = $cs1->get_courses();
            $cs2 = array_shift($coursesets);
            $cs2courses = $cs2->get_courses();

            $expectedsetdata = [];

            // Set up the expected data for the coursesets.
            $csdata = [];
            $csdata['id'] = "{$cs1->id}";
            $csdata['label'] = $cs1->label;
            $csdata['nextsetoperator'] = empty($cs1->islastset) ? 'THEN' : null;
            $csdata['completionCriteria'] = ['All courses in this set must be completed (unless this is an optional set).'];
            $csdata['statuskey'] = 'incomplete';

            $cscourses = [];
            foreach ($cs1->get_courses() as $course) {
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

            $expected = [
                "totara_mobile_certification" => [
                    "id" => "{$certifications[0]->id}",
                    "certifid" => "{$certifications[0]->certifid}",
                    "idnumber" => "",
                    "fullname" => "cert1",
                    "shortname" => "c1",
                    "duedate" => null,
                    "duedateState" => null,
                    "summary" => "The first certification",
                    "summaryformat" => "HTML",
                    "endnote" => "Congratulations on completing the first certification",
                    "endnoteformat" => "HTML",
                    "availablefrom" => null,
                    "availableuntil" => null,
                    "imageSrc" => '',
                    "completion" => [
                        "id" => $certcompletion->id,
                        "statuskey" => 'assigned',
                        "renewalstatuskey" => 'notdue',
                        "progress" => 0.0,
                        "__typename" => 'totara_certification_completion'
                    ],
                    "currentCourseSets" => [$expectedsetdata],
                    "countUnavailableSets" => 1,
                    "courseSetHeader" => "",
                    "__typename" => 'totara_mobile_certification'
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
        $certif = $prog_gen->create_certification([
            'shortname' => 'p1',
            'fullname' => 'prog1',
        ]);
        $prog_gen->add_courses_and_courseset_to_program($certif, [[$c1, $c2]], CERTIFPATH_STD);
        $prog_gen->assign_program($certif->id, [$user->id]);

        $this->setUser($user);

        $result = \totara_webapi\graphql::execute_operation(
            \core\webapi\execution_context::create('mobile', 'totara_mobile_certification'),
            ["certificationid" => $certif->id]
        );

        $data = $result->toArray()['data'];

        // Set up the expected data
        $csets = $certif->get_content()->get_course_sets();
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
        list($certcompletion, $progcompletion) = certif_load_completion($certif->id, $user->id);

        $expected = [
            "totara_mobile_certification" => [
                "id" => "{$certif->id}",
                "certifid" => "{$certif->certifid}",
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
                "imageSrc" => "",
                "completion" => [
                    "id" => $certcompletion->id,
                    "statuskey" => "assigned",
                    "renewalstatuskey" => "notdue",
                    "progress" => 0.0,
                    "__typename" => 'totara_certification_completion'
                ],
                "currentCourseSets" => [$expectedsetdata],
                "countUnavailableSets" => 0,
                "courseSetHeader" => "",
                "__typename" => 'totara_mobile_certification'
            ]
        ];
        $this->assertSame($expected, $data);
    }
}
