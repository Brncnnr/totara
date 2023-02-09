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
 * @package totara_certification
 */

defined('MOODLE_INTERNAL') || die();

use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * Tests the totara job assignment query resolver
 */
class totara_certification_webapi_resolver_query_certification_testcase extends advanced_testcase {

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

        $c1 = $this->getDataGenerator()->create_course();
        $c2 = $this->getDataGenerator()->create_course();
        $c3 = $this->getDataGenerator()->create_course();
        $c4 = $this->getDataGenerator()->create_course();
        $c5 = $this->getDataGenerator()->create_course();
        $c6 = $this->getDataGenerator()->create_course();

        $certifications = [];
        $certifications[] = $prog_gen->create_certification(['shortname' => 'c1', 'fullname' => 'cert1']);
        $certifications[] = $prog_gen->create_certification(['shortname' => 'c2', 'fullname' => 'cert2']);
        $certifications[] = $prog_gen->create_certification(['shortname' => 'c3', 'fullname' => 'cert3', 'visible' => 0]);

        $prog_gen->add_courses_and_courseset_to_program($certifications[0], [[$c1, $c2], [$c3]], CERTIFPATH_CERT);
        $prog_gen->add_courses_and_courseset_to_program($certifications[0], [[$c1], [$c3]], CERTIFPATH_RECERT);

        $prog_gen->add_courses_and_courseset_to_program($certifications[1], [[$c4, $c5], [$c6]], CERTIFPATH_CERT);
        $prog_gen->add_courses_and_courseset_to_program($certifications[1], [[$c4], [$c6]], CERTIFPATH_RECERT);

        $prog_gen->assign_program($certifications[0]->id, [$users[0]->id, $users[1]->id]);
        $prog_gen->assign_program($certifications[1]->id, [$users[1]->id]);

        return [$users, $certifications];
    }
    /**
     * Test the results of the query when the current user is not logged in.
     */
    public function test_resolve_no_login() {
        list($users, $certifications) = $this->create_faux_certifications();

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Course or activity not accessible. (You are not logged in)');

        $this->resolve_graphql_query('totara_certification_certification', ['certificationid' => $certifications[0]->id]);
    }

    /**
     * Test the results of the query when the current user is logged in as the guest user.
     */
    public function test_resolve_guest_user() {
        list($users, $certifications) = $this->create_faux_certifications();
        $this->setGuestUser();

        // Guests can view certifications (shouldn't have completion data though so...)
        $result = $this->resolve_graphql_query('totara_certification_certification', ['certificationid' => $certifications[0]->id]);
        $this->assertEquals($certifications[0]->id, $result->id);
        $this->assertEquals($certifications[0]->fullname, $result->fullname);
        $this->assertEquals($certifications[0]->shortname, $result->shortname);

        // Guests should not be able to see hidden certifications however.
        try {
            $result = $this->resolve_graphql_query('totara_certification_certification', ['certificationid' => $certifications[2]->id]);
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
        $result = $this->resolve_graphql_query('totara_certification_certification', ['certificationid' => $certifications[0]->id]);
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
        $result = $this->resolve_graphql_query('totara_certification_certification', ['certificationid' => $certifications[2]->id]);
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
        $result = $this->resolve_graphql_query('totara_certification_certification', ['certificationid' => $certifications[0]->id]);
        $this->assertEquals($certifications[0]->id, $result->id);

        // User should be able to see certification 2, with out any assignment/completion data.
        $result = $this->resolve_graphql_query('totara_certification_certification', ['certificationid' => $certifications[1]->id]);
        $this->assertEquals($certifications[1]->id, $result->id);

        // User should not be able to see certification 3, its hidden.
        try {
            $result = $this->resolve_graphql_query('totara_certification_certification', ['certificationid' => $certifications[2]->id]);
            $this->fail('Expected a moodle_exception: cannot view certification');
        } catch (\moodle_exception $ex) {
            // Note: I know this is a certification, but this is the require_login generic error.
            $this->assertSame('Coding error detected, it must be fixed by a programmer: Current user can not access this certification.', $ex->getMessage());
        }
    }

    /**
     * Test the results of the AJAX query through the GraphQL stack.
     */
    public function test_ajax_query() {
        global $DB;

        list($users, $certifications) = $this->create_faux_certifications();

        $this->setUser($users[0]);
        list($certcompletion, $progcompletion) = certif_load_completion($certifications[0]->id, $users[0]->id);

        $result = $this->execute_graphql_operation('totara_certification_certification', ['certificationid' => $certifications[0]->id]);
        $data = $result->toArray()['data'];

        $coursesets = $certifications[0]->get_content()->get_course_sets();
        $cssql = 'SELECT id, label
                    FROM {prog_courseset}
                   WHERE programid = :cid
                ORDER BY certifpath, sortorder';
        $coursesets = array_values($DB->get_records_sql($cssql, ['cid' => $certifications[0]->id]));
        $expected = [
            "totara_certification_certification" => [
                "id" => "{$certifications[0]->id}",
                "idnumber" => "",
                "fullname" => $certifications[0]->fullname,
                "shortname" => $certifications[0]->shortname,
                "summary" => "",
                "summaryformat" => "HTML",
                "endnote" => '',
                "duedate" => null,
                "duedate_state" => '',
                "coursesets" => [
                    0 => [
                        "id" => "{$coursesets[0]->id}",
                        "label" => "Course Set 1"
                    ],
                    1 => [
                        "id" => "{$coursesets[1]->id}",
                        "label" => "Course Set 2"
                    ],
                    2 => [
                        "id" => "{$coursesets[2]->id}",
                        "label" => "Course Set 1"
                    ],
                    3 => [
                        "id" => "{$coursesets[3]->id}",
                        "label" => "Course Set 2"
                    ]
                ],
                'completion' => [
                    'id' => $certcompletion->id,
                    'status' => 1,
                    'statuskey' => 'assigned',
                    'renewalstatus' => 0,
                    'renewalstatuskey' => 'notdue',
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
