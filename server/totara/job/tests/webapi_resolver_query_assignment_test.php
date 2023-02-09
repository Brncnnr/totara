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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_job
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/job/lib.php');

use totara_job\job_assignment;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * Tests the totara job assignment query resolver
 */
class totara_job_webapi_resolver_query_assignment_testcase extends advanced_testcase {

    use webapi_phpunit_helper;

    private function create_job_assignment($data) {
        $data = (array)$data;
        if (!array_key_exists('userid', $data)) {
            $user = $this->getDataGenerator()->create_user();
            $data['userid'] = $user->id;
        }
        if (!array_key_exists('idnumber', $data)) {
            $data['idnumber'] = 'job_x';
        }
        $instance = job_assignment::create($data);
        return $instance;
    }

    public function test_resolve_nologgedin() {
        $job = $this->create_job_assignment([]);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Course or activity not accessible. (You are not logged in)');

        $this->resolve_graphql_query('totara_job_assignment', ['assignmentid' => $job->id]);
    }

    public function test_resolve_guestuser() {
        $this->setGuestUser();
        $job = $this->create_job_assignment([]);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Sorry, but you do not currently have permissions to do that (view job assignments)');

        $this->resolve_graphql_query('totara_job_assignment', ['assignmentid' => $job->id]);
    }

    public function test_resolve_adminuser() {
        global $DB;

        $this->setAdminUser();
        $job =  $this->create_job_assignment([]);
        $result = $this->resolve_graphql_query('totara_job_assignment', ['assignmentid' => $job->id]);
        self::assertSame((array)$job, (array)$result);

        // Test no assignment id
        try {
            $this->resolve_graphql_query('totara_job_assignment', []);
            self::fail('Expected a moodle_exception: A required parameter (assignmentid) was missing');
        } catch (\moodle_exception $ex) {
            self::assertStringContainsString('A required parameter (assignmentid) was missing', $ex->getMessage());
        }

        // Test invalid job assignment id.
        try {
            $this->resolve_graphql_query('totara_job_assignment', ['assignmentid' => 0]);
            self::fail('Expected a moodle_exception: cannot view job assignments');
        } catch (\dml_exception $ex) {
            self::assertStringContainsString('Can not find data record in database.', $ex->getMessage());
        }
        try {
            $this->resolve_graphql_query('totara_job_assignment', ['assignmentid' => - 73]);
            self::fail('Expected a moodle_exception: cannot view job assignments');
        } catch (\dml_exception $ex) {
            self::assertStringContainsString('Can not find data record in database.', $ex->getMessage());
        }

        // Test job assignment belonging to deleted user.
        delete_user($DB->get_record('user', ['id' => $job->userid]));
        try {
            $this->resolve_graphql_query('totara_job_assignment', ['assignmentid' => $job->id]);
            self::fail('Expected a moodle_exception: cannot view job assignments');
        } catch (\dml_exception $ex) {
            self::assertStringContainsString('Can not find data record in database.', $ex->getMessage());
        }
    }

    /**
     * Integration test of the AJAX query through the GraphQL stack.
     */
    public function test_ajax_query() {
        $this->setAdminUser();

        $user = $this->getDataGenerator()->create_user();
        $appraiser = $this->getDataGenerator()->create_user();
        $manager = $this->getDataGenerator()->create_user();
        $managerja = $this->create_job_assignment(['userid' => $manager->id, 'idnumber' => 'j1']);
        /** @var \totara_hierarchy\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $orgframework = $generator->create_org_frame([]);
        $organisation = $generator->create_org(['frameworkid' => $orgframework->id, 'typeid' => $generator->create_org_type([])]);
        $posframework = $generator->create_pos_frame([]);
        $position = $generator->create_pos(['frameworkid' => $posframework->id, 'typeid' => $generator->create_pos_type([])]);

        $job1 =  $this->create_job_assignment(['userid' => $user->id, 'idnumber' => 'j1']);
        $job2 =  $this->create_job_assignment([
            'userid' => $user->id,
            'idnumber' => 'j2',
            'description' => '<p>This is a test</p>',
            'startdate' => time() - 86400,
            'enddate' => time() + 86400,
            'managerjaid' => $managerja->id,
            'appraiserid' => $appraiser->id,
            'positionid' => $position->id,
            'organisationid' => $organisation->id,

        ]);
        $expected1 = [
            'totara_job_assignment' => [
                'id' => $job1->id,
                'fullname' => 'Unnamed job assignment (ID: j1)',
                'idnumber' => 'j1',
                'description' => '',
                'startdate' => null,
                'enddate' => null,
                'managerja' => null,
                'position' => null,
                'organisation' => null,
                'appraiser' => null,
                'staffcount' => 0,
                'tempstaffcount' => 0,
            ]
        ];
        $expected2 = [
            'totara_job_assignment' => [
                'id' => $job2->id,
                'fullname' => 'Unnamed job assignment (ID: j2)',
                'idnumber' => 'j2',
                'description' => '<p>This is a test</p>',
                'startdate' => $job2->startdate,
                'enddate' => $job2->enddate,
                'managerja' => [
                    'user' => [
                        'id' => $manager->id,
                        'fullname' => fullname($manager)
                    ]
                ],
                'position' => [
                    'id' => $position->id,
                    'fullname' => format_string($position->fullname),
                ],
                'organisation' => [
                    'id' => $organisation->id,
                    'fullname' => format_string($organisation->fullname),
                ],
                'appraiser' => [
                    'id' => $appraiser->id,
                    'fullname' => fullname($appraiser)
                ],
                'staffcount' => 0,
                'tempstaffcount' => 0,
            ]
        ];

        $result = $this->execute_graphql_operation(
            'totara_job_assignment',
            ['userid' => $user->id, 'assignmentid' => $job1->id]
        );
        $result = $result->toArray(true);
        self::assertArrayHasKey('data', $result);
        self::assertSame($expected1, $result['data']);

        $result = $this->execute_graphql_operation(
            'totara_job_assignment',
            ['userid' => $user->id, 'assignmentid' => $job2->id]
        );
        $result = $result->toArray(true);
        self::assertArrayHasKey('data', $result);
        self::assertSame($expected2, $result['data']);

        $this->setUser($user);

        $result = $this->execute_graphql_operation(
            'totara_job_assignment',
            ['userid' => $user->id, 'assignmentid' => $job1->id]
        );
        $result = $result->toArray(true);
        self::assertArrayHasKey('data', $result);
        self::assertSame($expected1, $result['data']);

        $result = $this->execute_graphql_operation(
            'totara_job_assignment',
            ['userid' => $user->id, 'assignmentid' => $job2->id]
        );
        $result = $result->toArray(true);
        self::assertArrayHasKey('data', $result);
        self::assertSame($expected2, $result['data']);
    }
}