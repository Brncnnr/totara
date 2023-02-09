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
 * @author Scott Davies <scott.davies@totara.com>
 * @package totara_job
 */

use core\entity\tenant;
use totara_job\job_assignment;
use totara_webapi\phpunit\webapi_phpunit_helper;
use core_phpunit\testcase;
use core\entity\user as user_entity;
use core\webapi\execution_context;
use totara_webapi\graphql;

/**
 * Unit tests for the totara_job\webapi\resolver\query\job_assignments resolver.
 */
class totara_job_webapi_resolver_query_job_assignments_testcase extends testcase {

    use webapi_phpunit_helper;

    /**
     * @var string
     */
    private const QUERY = 'totara_job_job_assignments';

    /**
     * @return void
     */
    public function test_response_values_correct(): void {
        // Set up. Let's make 3 job assignments.
        self::setAdminUser();
        /** @var \totara_hierarchy\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $framework = $generator->create_org_frame([]);
        $org_typeid = $generator->create_org_type([]);
        $test_organisation = $generator->create_org(['frameworkid' => $framework->id, 'typeid' => $org_typeid]);
        $framework = $generator->create_pos_frame([]);
        $pos_typeid = $generator->create_pos_type([]);

        // Create a Sales Manager job assignment.
        $test_user1 = $this->getDataGenerator()->create_user();
        $test_position1 = $generator->create_pos(['fullname' => 'Sales Manager', 'frameworkid' => $framework->id,
            'typeid' => $pos_typeid
        ]);
        $test_ja_idnumber1 = uniqid();
        $ja_params1 = [
            'userid' => $test_user1->id,
            'idnumber' => $test_ja_idnumber1,
            'organisationid' => $test_organisation->id,
            'positionid' => $test_position1->id,
            'fullname' => 'ja_sales_manager',
            'shortname' => 'ja_s_m',
            'description' => 'ja_s_m description',
            'startdate' => time(),
            'enddate' => time(),
        ];
        $test_job_assignment1 = job_assignment::create($ja_params1);

        // Create a Sales Assistant job assignment.
        $test_user2 = $this->getDataGenerator()->create_user();
        $test_position2 = $generator->create_pos(['fullname' => 'Sales Assistant', 'frameworkid' => $framework->id,
            'typeid' => $pos_typeid
        ]);
        $test_ja_idnumber2 = uniqid();
        $ja_params2 = [
            'userid' => $test_user2->id,
            'idnumber' => $test_ja_idnumber2,
            'organisationid' => $test_organisation->id,
            'positionid' => $test_position2->id,
            'managerjaid' => $test_job_assignment1->id // i.e. test_user2 is managed by test_user1.
        ];
        $test_job_assignment2 = job_assignment::create($ja_params2);

        // Create a Sales Administrator job assignment.
        $test_user3 = $this->getDataGenerator()->create_user();
        $test_position3 = $generator->create_pos(['fullname' => 'Sales Administrator', 'frameworkid' => $framework->id,
            'typeid' => $pos_typeid
        ]);
        $test_ja_idnumber3 = uniqid();
        $test_appraiser_user = $this->getDataGenerator()->create_user();
        $ja_params3 = [
            'userid' => $test_user3->id,
            'idnumber' => $test_ja_idnumber3,
            'organisationid' => $test_organisation->id,
            'positionid' => $test_position3->id,
            'managerjaid' => $test_job_assignment1->id,
            'tempmanagerjaid' => $test_job_assignment2->id, // i.e. test_user3 is temp-managed by test_user2.
            'tempmanagerexpirydate' => time(),
            'appraiserid' => $test_appraiser_user->id // test_user3 is the only one with an appraiser in the JA.
        ];
        $test_job_assignment3 = job_assignment::create($ja_params3);

        // Operate.
        $result = $this->resolve_graphql_query(self::QUERY, [
            'query' => []
        ]);

        // Assert count.
        $this->assertCount(3, $result->items);

        // Check the Sales Manager job assignment data in the response.
        $item = $result->items->current();
        $this->assertEquals($test_job_assignment1->id, $item->id);
        foreach ($ja_params1 as $k => $v) {
            $this->assertEquals($v, $item->{$k});
        }

        // Check the Sales Assistant job assignment data in the response.
        $result->items->next();
        $item = $result->items->current();
        $this->assertEquals($test_job_assignment2->id, $item->id);
        foreach ($ja_params2 as $k => $v) {
            $this->assertEquals($v, $item->{$k});
        }

        // Check the Sales Administration job assignment data in the response.
        $result->items->next();
        $item = $result->items->current();
        $this->assertEquals($test_job_assignment3->id, $item->id);
        foreach ($ja_params3 as $k => $v) {
            $this->assertEquals($v, $item->{$k});
        }
    }

    /**
     * @return void
     */
    public function test_api_user_role_has_permissions_to_query(): void {
        global $DB;
        // Set up.
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $framework = $generator->create_pos_frame([]);

        // Make a user with the system-context API User role to carry out the request.
        $test_api_user = self::getDataGenerator()->create_user([
            'username' => 'user' . uniqid(),
        ]);
        $api_user_role = $DB->get_record('role', ['archetype' => 'apiuser'], 'id');
        role_assign($api_user_role->id, $test_api_user->id, context_system::instance());
        self::setUser($test_api_user);

        // Create a Sales Assistant job assignment.
        $test_user = $this->getDataGenerator()->create_user();
        $test_position = $generator->create_pos(['fullname' => 'Sales Assistant', 'frameworkid' => $framework->id]);
        $test_ja_idnumber = uniqid();
        $ja_params = [
            'userid' => $test_user->id,
            'idnumber' => $test_ja_idnumber,
            'positionid' => $test_position->id,
        ];
        $test_job_assignment = job_assignment::create($ja_params);

        // Operate.
        $result = $this->resolve_graphql_query(self::QUERY, [
            'query' => []
        ]);

        // Assert. There should have been no permissions errors & data got returned in the response.
        $item = $result->items->current();
        $this->assertEquals($test_job_assignment->id, $item->id);
        foreach ($ja_params as $k => $v) {
            $this->assertEquals($v, $item->{$k});
        }
    }

    /**
     * @return void
     */
    public function test_api_user_role_does_not_have_permissions_to_query(): void {
        global $DB;
        // Set up.
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $framework = $generator->create_pos_frame([]);

        // Make a user with the system-context API User role to carry out the request.
        $test_api_user = self::getDataGenerator()->create_user([
            'username' => 'api client user' . uniqid(),
        ]);
        $api_user_role = $DB->get_record('role', ['archetype' => 'apiuser'], 'id');
        role_assign($api_user_role->id, $test_api_user->id, context_system::instance());
        self::setUser($test_api_user);

        // Create a Sales Assistant job assignment.
        $test_user = $this->getDataGenerator()->create_user();
        $test_position = $generator->create_pos(['fullname' => 'Sales Assistant', 'frameworkid' => $framework->id]);
        $test_ja_idnumber = uniqid();
        $ja_params = [
            'userid' => $test_user->id,
            'idnumber' => $test_ja_idnumber,
            'positionid' => $test_position->id,
        ];
        $test_job_assignment = job_assignment::create($ja_params);

        // Operate for an invalid user: a soft-deleted user.
        $api_user = user_entity::repository()->find($test_api_user->id);
        $api_user->deleted = 1;
        $api_user->save();
        self::setUser($api_user);

        try {
            $result = $this->resolve_graphql_query(self::QUERY, ['query' => []]);
            $this->fail('Expected exception not thrown');
        } catch (Exception $exc) {
            $this->assertStringContainsString('Sorry, but you do not currently have permissions to do that (view job assignments)',
                $exc->getMessage()
            );
        }

        // Operate for an invalid user: guest.
        self::setUser(guest_user());
        try {
            $result = $this->resolve_graphql_query(self::QUERY, ['query' => []]);
            $this->fail('Expected exception not thrown');
        } catch (Exception $exc) {
            $this->assertStringContainsString('Course or activity not accessible. (Must be an authenticated user)',
                $exc->getMessage()
            );
        }
    }

    /**
     * @return void
     */
    public function test_tenants_cannot_view_other_tenants_data(): void {
        // Set up.
        global $CFG, $DB;
        $original_config = $CFG->tenantsenabled;

        self::setAdminUser();
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $tenant_generator = self::getDataGenerator()->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant1 = $tenant_generator->create_tenant();
        $framework = $generator->create_pos_frame([]);
        $typeid = $generator->create_pos_type([]);
        $test_position_tenant1 = $generator->create_pos(['frameworkid' => $framework->id, 'typeid' => $typeid,
            'fullname' => 'Sales Assistant tenant1'
        ]);
        // Create a Sales Assistant job assignment for tenant1.
        $test_user = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $test_ja_idnumber = uniqid();
        $ja_params_tenant1 = [
            'userid' => $test_user->id,
            'idnumber' => $test_ja_idnumber,
            'positionid' => $test_position_tenant1->id,
        ];
        $test_job_assignment_tenant1 = job_assignment::create($ja_params_tenant1);

        $tenant2 = $tenant_generator->create_tenant();
        $framework = $generator->create_pos_frame([]);
        $typeid = $generator->create_pos_type([]);
        $test_position_tenant2 = $generator->create_pos(['frameworkid' => $framework->id, 'typeid' => $typeid,
            'fullname' => 'Sales Assistant tenant2'
        ]);
        // Create a Sales Assistant job assignment for tenant2.
        $test_user = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $test_ja_idnumber = uniqid();
        $ja_params_tenant2 = [
            'userid' => $test_user->id,
            'idnumber' => $test_ja_idnumber,
            'positionid' => $test_position_tenant2->id,
        ];
        $test_job_assignment_tenant2 = job_assignment::create($ja_params_tenant2);

        // Create an API user for tenant1.
        $test_api_user_tenant1 = self::getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $api_user_role = $DB->get_record('role', ['archetype' => 'apiuser'], 'id');
        role_assign($api_user_role->id, $test_api_user_tenant1->id, context_tenant::instance($tenant1->id));

        // Operate.
        self::setUser($test_api_user_tenant1);
        $result = $this->resolve_graphql_query(self::QUERY, ['query' => []]);

        // Assert. We should only receive result(s) for tenant1, which the API user belongs to.
        $this->assertCount(1, $result->items);
        $item = $result->items->current();
        $this->assertEquals($item->userid, $ja_params_tenant1['userid']);

        // Tear down.
        set_config('tenantsenabled', $original_config);
    }

    /**
     * @return void
     */
    public function test_invalid_tenant(): void {
        // Set up.
        global $CFG;
        $original_config = $CFG->tenantsenabled;

        self::setAdminUser();
        global $DB;
        $tenant_generator = self::getDataGenerator()->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant1 = $tenant_generator->create_tenant();
        // Create an API user for tenant1.
        $test_api_user_tenant1 = self::getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $api_user_role = $DB->get_record('role', ['archetype' => 'apiuser'], 'id');
        role_assign($api_user_role->id, $test_api_user_tenant1->id, context_tenant::instance($tenant1->id));
        self::setUser($test_api_user_tenant1);
        // Suspend the tenant of the current API user to throw an exception.
        $tenant_entity = tenant::repository()->find($tenant1->id);
        $tenant_entity->suspended = 1;
        $tenant_entity->save();

        // Operate.
        try {
            $result = $this->resolve_graphql_query(self::QUERY, ['query' => []]);
            $this->fail('Expected exception not thrown');
        } catch (Exception $exc) {
            $this->assertStringContainsString('Tenant reference must identify exactly one tenant', $exc->getMessage());
        }

        // Tear down.
        set_config('tenantsenabled', $original_config);
    }

    /**
     * @return void
     */
    public function test_response_pagination(): void {
        // Set up.
        self::setAdminUser();

        // Create 6 test job assignments.
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $framework = $generator->create_pos_frame([]);
        for ($i = 0; $i < 6; $i++) {
            // Create a job assignment.
            $test_user = $this->getDataGenerator()->create_user();
            $test_position = $generator->create_pos(['fullname' => 'Sales Assistant' . strval($i),
                'frameworkid' => $framework->id
            ]);
            $test_ja_idnumber = uniqid();
            $ja_params = [
                'userid' => $test_user->id,
                'idnumber' => $test_ja_idnumber,
                'positionid' => $test_position->id,
            ];
            $test_job_assignment = job_assignment::create($ja_params);
        }

        // Operate for a request to get page 1 results.
        $result = $this->resolve_graphql_query(self::QUERY, [
            'query' => [
                'pagination' => [
                    'limit' => 5
                ]
            ]
        ]);

        // Assert.
        $this->assertCount(5, $result->items);
        $this->assertEquals(6, $result->total);
        $this->assertNotEmpty($result->next_cursor);

        // Operate for page 2 results.
        $result = $this->resolve_graphql_query(self::QUERY, [
            'query' => [
                'pagination' => [
                    'limit' => 5,
                    'cursor' => $result->next_cursor
                ]
            ]
        ]);

        // Assert.
        $this->assertCount(1, $result->items);
    }

    /**
     * @return void
     */
    public function test_response_sorting(): void {
        // Set up.
        self::setAdminUser();

        // Create 6 test job assignments.
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $framework = $generator->create_pos_frame([]);
        $job_assignment_ids_in_creation_order = [];
        $num_records_total = 6;
        for ($i = 0; $i < $num_records_total; $i++) {
            // Create a job assignment.
            $test_user = $this->getDataGenerator()->create_user();
            $test_position = $generator->create_pos(['fullname' => 'Sales Assistant' . strval($i),
                'frameworkid' => $framework->id
            ]);
            $test_ja_idnumber = uniqid();
            $ja_params = [
                'userid' => $test_user->id,
                'idnumber' => $test_ja_idnumber,
                'positionid' => $test_position->id,
            ];
            $test_job_assignment = job_assignment::create($ja_params);
            $job_assignment_ids_in_creation_order[] = intval($test_job_assignment->id);
        }

        // Operate.
        $order_direction = 'DESC';
        $result = $this->resolve_graphql_query(self::QUERY, [
            'query' => [
                'sort' => [
                    [
                        'column' => 'id',
                        'direction' => $order_direction
                    ]
                ]
            ]
        ]);

        // Assert. Check the sorting applied is descending order.
        $this->assertEquals(array_reverse($job_assignment_ids_in_creation_order), $result->items->keys());
    }

    /**
     * @return void
     */
    public function test_invalid_sorting(): void {
        self::setAdminUser();

        $this->expectExceptionMessage("Required parameter 'sort.column' not being passed");

        $result = $this->resolve_graphql_query(self::QUERY, [
            'query' => [
                'sort' => [
                    [
                        'direction' => 'ASC'
                    ]
                ]
            ]
        ]);
    }

    /**
     * @return void
     */
    public function test_ajax_query() {
        self::setAdminUser();
        $test_user = $this->getDataGenerator()->create_user();
        $ja_params = ['userid' => $test_user->id, 'idnumber' => uniqid()];
        $test_job_assignment = job_assignment::create($ja_params);

        $this->setUser($test_user);
        $result = graphql::execute_operation(
            execution_context::create('ajax', 'totara_job_assignments'),
            ['userid' => $test_user->id]
        );
        $data = $result->toArray()['data']['totara_job_assignments'];
        $this->assertNotEmpty($data);
        $this->assertEquals($test_job_assignment->id, intval($data[0]['id']));
    }
}
