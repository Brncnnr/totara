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

use core\webapi\execution_context;
use GraphQL\Error\DebugFlag;
use totara_job\job_assignment;
use totara_webapi\graphql;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * Tests the totara job assignments query resolver
 */
class totara_job_webapi_resolver_query_assignments_testcase extends advanced_testcase {

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

        $this->resolve_graphql_query('totara_job_assignments', ['userid' => $job->userid]);
    }

    public function test_resolve_guestuser() {
        $this->setGuestUser();
        $job = $this->create_job_assignment([]);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Sorry, but you do not currently have permissions to do that (view job assignments)');

        $this->resolve_graphql_query('totara_job_assignments', ['userid' => $job->userid]);
    }

    public function test_resolve_adminuser() {
        global $DB;

        $this->setAdminUser();

        $job1 =  $this->create_job_assignment([]);
        $result = $this->resolve_graphql_query('totara_job_assignments', ['userid' => $job1->userid, 'idnumber' => 'job_1']);
        self::assertEquals([$job1->sortorder => $job1], $result);

        $job2 =  $this->create_job_assignment(['userid' => $job1->userid, 'idnumber' => 'job_2']);
        $result = $this->resolve_graphql_query('totara_job_assignments', ['userid' => $job1->userid]);
        self::assertEquals([$job1->sortorder => $job1, $job2->sortorder => $job2], $result);

        // Test no user provided.
        try {
            $this->resolve_graphql_query('totara_job_assignments', []);
            self::fail('Expected a moodle_exception: no user argument provided');
        } catch (\moodle_exception $ex) {
            self::assertStringContainsString('A required parameter (userid) was missing', $ex->getMessage());
        }

        // Test invalid user id.
        try {
            $this->resolve_graphql_query('totara_job_assignments', ['userid' => 0]);
            self::fail('Expected a moodle_exception: cannot view job assignments');
        } catch (\dml_exception $ex) {
            self::assertStringContainsString('Can not find data record in database.', $ex->getMessage());
        }
        try {
            $this->resolve_graphql_query('totara_job_assignments', ['userid' => '-2']);
            self::fail('Expected a moodle_exception: cannot view job assignments');
        } catch (\dml_exception $ex) {
            self::assertStringContainsString('Can not find data record in database.', $ex->getMessage());
        }

        // Test job assignment belonging to deleted user.
        delete_user($DB->get_record('user', ['id' => $job1->userid]));
        try {
            $this->resolve_graphql_query('totara_job_assignments', ['userid' => $job1->userid]);
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
        $job1 =  $this->create_job_assignment(['userid' => $user->id, 'idnumber' => 'j1']);
        $job2 =  $this->create_job_assignment(['userid' => $user->id, 'idnumber' => 'j2', 'managerjaid' => $managerja->id, 'appraiserid' => $appraiser->id]);

        $result = graphql::execute_operation(
            execution_context::create('ajax', 'totara_job_assignments'),
            ['userid' => $user->id]
        );
        $expected = [
            'totara_job_assignments' => [
                [
                    'id' => $job1->id,
                    'fullname' => 'Unnamed job assignment (ID: j1)',
                    'idnumber' => 'j1',
                    'managerja' => null,
                    'appraiser' => null,
                ],
                [
                    'id' => $job2->id,
                    'fullname' => 'Unnamed job assignment (ID: j2)',
                    'idnumber' => 'j2',
                    'managerja' => [
                        'user' => [
                            'fullname' => fullname($manager)
                        ]
                    ],
                    'appraiser' => [
                        'fullname' => fullname($appraiser)
                    ],
                ],
            ]
        ];
        $result = $result->toArray(true);
        $this->assertArrayHasKey('data', $result);
        $this->assertSame($expected, $result['data']);

        $this->setUser($user);
        $result = graphql::execute_operation(
            execution_context::create('ajax', 'totara_job_assignments'),
            ['userid' => $user->id]
        );
        $result = $result->toArray(true);
        $this->assertArrayHasKey('data', $result);
        $this->assertSame($expected, $result['data']);

        // Invalid userid
        $result = $this->execute_graphql_operation('totara_job_assignments', ['userid' => 'apples']);

        $this->assertEquals(
            'Variable "$userid" got invalid value "apples"; Expected type core_id; Invalid parameter value detected',
            $result->toArray(DebugFlag::INCLUDE_DEBUG_MESSAGE)['errors'][0]['debugMessage']
        );

        // Missing userid
        $result = $this->execute_graphql_operation('totara_job_assignments', []);

        $this->assertEquals(
            'Variable "$userid" of required type "core_id!" was not provided.',
            $result->toArray(DebugFlag::INCLUDE_DEBUG_MESSAGE)['errors'][0]['message']
        );
    }
}