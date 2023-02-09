<?php
/**
 * This file is part of Totara Core
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Scott Davies <scott.davies@totaralearning.com>
 * @package totara_job
 */

use core\reference\user_record_reference;
use core_phpunit\testcase;
use totara_job\exception\job_assignment_delete_exception;
use totara_job\job_assignment;
use totara_job\entity\job_assignment as entity_job_assignment;
use totara_webapi\phpunit\webapi_phpunit_helper;

global $CFG;
require_once($CFG->dirroot . '/user/lib.php');

/**
 * Unit tests for the job_delete_job_assignment mutation_resolver for the External API.
 */
class webapi_resolver_mutation_job_delete_job_assignment_test extends testcase {
    use webapi_phpunit_helper;

    /**
     * @var string
     */
    private const MUTATION = 'totara_job_delete_job_assignment';

    /**
     * @param int $test_user_id
     * @return job_assignment
     */
    private function helper_create_job_assignment(int $test_user_id): job_assignment {
        $data = [
            'userid' => $test_user_id,
            'idnumber' => 'job_x' . uniqid()
        ];
        return job_assignment::create($data);
    }

    /**
     * @return int
     */
    private function helper_create_test_api_user_with_capabilities(): int {
        $gen = self::getDataGenerator();
        // Create a test API user.
        $user = $gen->create_user(['username' => 'user' . (string)uniqid()]);
        $test_user_id = $user->id;
        // Give the API user the required capabilities through a role.
        $role_id = $gen->create_role();
        assign_capability('totara/hierarchy:assignuserposition', CAP_ALLOW, $role_id, context_system::instance());
        assign_capability('moodle/user:viewalldetails', CAP_ALLOW, $role_id, context_system::instance());
        role_assign($role_id, $test_user_id, context_system::instance());
        return $test_user_id;
    }

    /**
     * @return int
     */
    private function helper_create_tenant_api_user_with_capabilities(int $tenant_id): int {
        $gen = self::getDataGenerator();
        // Create a test API user.
        $user = $gen->create_user(['username' => 'user' . (string)uniqid(), 'tenantid' => $tenant_id]);
        $test_user_id = $user->id;
        // Give the API user the required capabilities through a role.
        $role_id = $gen->create_role();
        assign_capability('totara/hierarchy:assignuserposition', CAP_ALLOW, $role_id, context_tenant::instance($tenant_id));
        assign_capability('moodle/user:viewalldetails', CAP_ALLOW, $role_id, context_tenant::instance($tenant_id));
        role_assign($role_id, $test_user_id, context_system::instance());
        return $test_user_id;
    }

    /**
     * @dataProvider get_test_job_assignments
     * @return void
     */
    public function test_delete_job_assignment_with_success(string $job_assignment_field): void {
        self::setAdminUser();
        // Set up
        $test_user = $this->getDataGenerator()->create_user();
        $test_user_id = $test_user->id;
        $test_job_assignment = $this->helper_create_job_assignment($test_user_id);
        $value = ($job_assignment_field === 'id' ? $test_job_assignment->id : $test_job_assignment->idnumber);
        $event_sink = self::redirectEvents();

        // Operate - make a delete_job_assignment request with field as 'id' or 'idnumber'
        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_job' => [
                    $job_assignment_field => $value
                ]
            ]
        );

        // Assert
        $events = $event_sink->get_events();
        $event_names = array_map(function ($event){
            return $event->eventname;
        }, $events);
        self::assertTrue(in_array('\totara_job\event\job_assignment_deleted', $event_names));

        self::assertNotEmpty($result);
        self::assertEquals($test_job_assignment->id, $result['job_assignment_id']);
        self::assertEmpty(entity_job_assignment::repository()->find($test_job_assignment->id)); // check it's deleted
    }

    /**
     * @return void
     */
    public function test_delete_job_assignment_fails_if_nonexistent(): void {
        self::setAdminUser();

        $this->expectErrorMessage('There was a problem finding a single job assignment record match or you do not have permission to manage it.');

        // Operate - make a delete_job_assignment request with field as 'id' or 'idnumber'
        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_job' => [
                    'id' => -1
                ]
            ]
        );
    }

    /**
     * @dataProvider get_allow_multiple_jobs_config_values
     * @return void
     */
    public function test_with_allowmultiplejobs_config(string $allow_multiple_jobs): void {
        global $CFG;
        self::setAdminUser();
        // Set up
        $original_config_val = $CFG->totara_job_allowmultiplejobs;
        set_config('totara_job_allowmultiplejobs', $allow_multiple_jobs);

        $test_user = $this->getDataGenerator()->create_user();
        $test_user_id = $test_user->id;
        $test_job_assignment = $this->helper_create_job_assignment($test_user_id);

        // Operate - make a delete_job_assignment request with field as 'id' or 'idnumber'
        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_job' => [
                    'id' => $test_job_assignment->id
                ]
            ]
        );

        // Assert
        self::assertNotEmpty($result);
        self::assertEquals($test_job_assignment->id, $result['job_assignment_id']);

        // Tear down
        set_config('totara_job_allowmultiplejobs', $original_config_val);
    }

    /**
     * @return void
     */
    public function test_with_multiple_job_assignments(): void {
        global $CFG;
        // Set up
        self::setAdminUser();
        $original_config_val = $CFG->totara_job_allowmultiplejobs;
        set_config('totara_job_allowmultiplejobs', '1');

        $test_user1_id = $this->helper_create_test_api_user_with_capabilities();
        $test_user2_id = $this->helper_create_test_api_user_with_capabilities();
        $test_ja_id_number = 'job_x' . (string)uniqid();
        $data1 = [
            'userid' => $test_user1_id,
            'idnumber' => $test_ja_id_number
        ];
        $data2 = [
            'userid' => $test_user2_id,
            'idnumber' => $test_ja_id_number
        ];

        $test_job_assignment1 = job_assignment::create($data1);
        $test_job_assignment2 = job_assignment::create($data2);

        $test_api_user_id = $this->helper_create_test_api_user_with_capabilities();
        self::setUser($test_api_user_id);

        $this->expectErrorMessage('There was a problem finding a single job assignment record match or you do not have permission to manage it.');

        // Operate - make a delete_job_assignment request with field 'idnumber' - more than 1 match
        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_job' => [
                    'idnumber' => $test_ja_id_number
                ]
            ]
        );

        // Tear down
        set_config('totara_job_allowmultiplejobs', $original_config_val);
    }

    /**
     * @return void
     */
    public function test_for_api_user_without_required_capabilities(): void {
        // Set up
        self::setAdminUser();

        // Make a test job_assignment
        $test_user = $this->getDataGenerator()->create_user();
        $test_user_id = $test_user->id;
        $test_job_assignment = $this->helper_create_job_assignment($test_user_id);

        // Set an API user who is missing the required capabilities.
        $data = new stdClass();
        $data->username = 'user' . (string)uniqid();
        $data->email = $data->username . '@example.com';
        $test_user_id = user_create_user($data);
        self::setUser($test_user_id);

        // Expect exception!
        $this->expectErrorMessage('User not fully set-up');

        // Operate - make a delete_job_assignment request with field as 'id'
        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_job' => [
                    'id' =>  $test_job_assignment->id
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_for_api_user_with_required_capabilities(): void {
        // Set up.
        self::setAdminUser();

        // Make a test job_assignment.
        $test_user = $this->getDataGenerator()->create_user();
        $test_user_id = $test_user->id;
        $test_job_assignment = $this->helper_create_job_assignment($test_user_id);

        // Set an API user who has the required capabilities.
        $test_api_user_id = $this->helper_create_test_api_user_with_capabilities();
        self::setUser($test_api_user_id);

        // Operate - make a delete_job_assignment request with field as 'id'
        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_job' => [
                    'id' =>  $test_job_assignment->id
                ]
            ]
        );

        // Assert.
        self::assertNotEmpty($result);
        self::assertEquals($test_job_assignment->id, $result['job_assignment_id']);
    }

    /**
     * @return void
     */
    public function test_for_api_user_with_valid_tenant_id(): void {
        global $CFG;
        // Set up.
        self::setAdminUser();
        $original_config = $CFG->tenantsenabled;

        $tenant_generator = $this->getDataGenerator()->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();
        $test_tenant = $tenant_generator->create_tenant();

        // Make a test job_assignment with a user assigned.
        $test_job_assig_user_id = $this->helper_create_tenant_api_user_with_capabilities((int)$test_tenant->id);
        $test_job_assignment = $this->helper_create_job_assignment($test_job_assig_user_id);

        // Set an API user who has the required capabilities.
        $test_api_user_id = $this->helper_create_tenant_api_user_with_capabilities((int)$test_tenant->id);
        self::setUser($test_api_user_id);

        // Operate - make a delete_job_assignment request with field as 'id'.
        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_job' => [
                    'id' =>  $test_job_assignment->id
                ]
            ]
        );

        // Assert.
        self::assertNotEmpty($result);
        self::assertEquals($test_job_assignment->id, $result['job_assignment_id']);

        // Tear down
        set_config('tenantsenabled', $original_config);
    }

    /**
     * @return void
     */
    public function test_for_api_user_with_invalid_tenant_id(): void {
        global $CFG;
        // Set up.
        self::setAdminUser();
        $original_config = $CFG->tenantsenabled;

        $tenant_generator = $this->getDataGenerator()->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();
        $test_tenant1 = $tenant_generator->create_tenant();
        $test_tenant2 = $tenant_generator->create_tenant();

        // Make a test job_assignment with a user assigned.
        $test_job_assig_user_id = $this->helper_create_tenant_api_user_with_capabilities((int)$test_tenant1->id);
        $test_job_assignment = $this->helper_create_job_assignment($test_job_assig_user_id);

        // Set an API user who has the required capabilities for a different tenant.
        $test_api_user_id = $this->helper_create_tenant_api_user_with_capabilities((int)$test_tenant2->id);
        self::setUser($test_api_user_id);

        $this->expectErrorMessage('There was a problem finding a single job assignment record match or you do not have permission to manage it.');

        // Operate - make a delete_job_assignment request with field as 'id'.
        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_job' => [
                    'id' =>  $test_job_assignment->id
                ]
            ]
        );

        // Tear down
        set_config('tenantsenabled', $original_config);
    }

    /**
     * @return void
     */
    public function test_delete_job_assignment_for_non_unique_idnumber(): void {
        $this->setAdminUser();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $shared_idnumber = '123imnotunique';
        $job_assignment1 = job_assignment::create(['userid' => $user1->id, 'idnumber' => $shared_idnumber]);
        $job_assignment2 = job_assignment::create(['userid' => $user2->id, 'idnumber' => $shared_idnumber]);

        $request_args = [
            'target_job' => [
                'idnumber' => $shared_idnumber
            ]
        ];

        // Expect this to fail - 2 job assignments have the same 'idnumber' value, so multiple records will get returned.
        try {
            $result = $this->resolve_graphql_mutation(
                self::MUTATION,
                $request_args
            );
        } catch (job_assignment_delete_exception $exc) {
            $this->assertEquals('There was a problem finding a single job assignment record match or you do not have permission to manage it.', $exc->getMessage());
        }

        // Expect this to succeed - an additional 'userid' filter is applied so it will find just one record.
        $request_args['target_job']['user'] = [ 'id' => $user1->id];
        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            $request_args
        );

        $this->assertArrayHasKey('job_assignment_id', $result);
        $this->assertEquals($job_assignment1->id, $result['job_assignment_id']);
    }

    /**
     * Data provider.
     * @return string[][]
     */
    public function get_test_job_assignments(): array {
        return [
            [
                'job_assignment_field' => 'id',
            ],
            [
                'job_assignment_field' => 'idnumber',
            ],
        ];
    }

    /**
     * Data provider.
     * @return string[][]
     */
    public function get_allow_multiple_jobs_config_values(): array {
        return [
            [ 'allow_multiple_jobs' => '0'],
            [ 'allow_multiple_jobs' => '1'],
        ];
    }

}
