<?php
/**
 * This file is part of Totara Learn
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Angela Kuznetsova <angela.kuznetsova@totaralearning.com>
 * @package core
 */

use core\collection;
use core\entity\user as user_entity;
use core\orm\query\builder;
use core_phpunit\testcase;
use totara_webapi\controllers\api_controller;
use core_user\exception\user_users_exception;
use totara_webapi\phpunit\webapi_phpunit_helper;
use totara_job\job_assignment as job_assignment_object;
use totara_webapi\request;
use totara_webapi\server;
use totara_oauth2\testing\generator as oauth2_generator;
use totara_webapi\controllers\external;
use GraphQL\Error\DebugFlag;

/**
 * @coversDefaultClass \core\webapi\resolver\query\user_users
 *
 * @group core_user
 */
class core_webapi_query_user_users_testcase extends testcase {

    private const QUERY = 'core_user_users';

    use webapi_phpunit_helper;

    /**
     * @covers ::resolve
     */
    public function test_fetch_users_no_required_params(): void {
        self::setAdminUser();

        self::expectExceptionMessage("Required parameter 'column' not being passed.");
        self::expectException(user_users_exception::class);
        $this->resolve_graphql_query(self::QUERY, [
            'query' => [
                'sort' => [
                    [
                        'direction' => 'DESC',
                    ],
                ],
            ]
        ]);
    }

    /**
     * @covers ::resolve
     */
    public function test_find_default_users_params(): void {
        $no_of_users = 18;

        $expected = [(int)get_admin()->id, (int)guest_user()->id];

        // Create 18 users.
        $user_ids = $this->create_users($no_of_users)->pluck('id');
        foreach ($user_ids as $id) {
            $expected[] = $id;
        }

        $result = $this->resolve_graphql_query(self::QUERY, []);
        $this->assertIsArray($result);

        $total = $result['total'];
        $this->assertEquals(20, $total, 'wrong total count');

        $enc_cursor = $result['next_cursor'];
        $this->assertEmpty($enc_cursor, 'empty cursor');

        $items = collection::new($result['items'] ?? []);
        $this->assertEquals(20, $items->count(), 'wrong current page count');
        $this->assertEqualsCanonicalizing($expected, $items->pluck('id'), 'wrong retrievals');

        // Create one more users.
        $this->create_users(1);
        $result = $this->resolve_graphql_query(self::QUERY, []);

        $this->assertEquals(21, $result['total'], 'wrong total count');
        $this->assertNotEmpty( $result['next_cursor'], 'non empty cursor');
    }

    /**
     * @return void
     */
    public function test_fetch_users_with_sort_by_multiple_columns(): void {
        $user = self::getDataGenerator()->create_user();

        self::setUser($user);

        self::expectExceptionMessage("Sorting by more than one column is not currently supported.");
        self::expectException(user_users_exception::class);
        $this->resolve_graphql_query(self::QUERY, [
            'query' => [
                'sort' => [
                    [
                        'column' => 'id',
                    ],
                    [
                        'column' => 'firstname',
                    ]
                ],
            ]
        ]);
    }

    /**
     * @return void
     */
    public function test_fetch_users_by_authenticated_user(): void {
        $user = self::getDataGenerator()->create_user();

        self::setUser($user);

        self::expectException(user_users_exception::class);
        $this->resolve_graphql_query(self::QUERY, [
            'query' => [
                'sort' => [
                    [
                        'column' => 'id',
                    ]
                ],
            ]
        ]);
    }

    /**
     * @covers ::resolve
     */
    public function test_tenant_users_by_admin(): void {
        $order_direction = 'DESC';
        $this->create_tenant_users();

        $page_size = 3;

        $args = [
            'query' => [
                'sort' => [
                    [
                        'column' => 'id, timemodified',
                        'direction' => $order_direction
                    ]
                ],
                'pagination' => [
                    'limit' => $page_size,
                    'cursor' => null
                ]
            ]
        ];

        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $this->resolve_graphql_query(self::QUERY, $args);

        $this->assertEquals(14, $total, 'wrong total count');
        $this->assertCount($page_size, $items, 'wrong current page count');
        $this->assertNotEmpty($enc_cursor, 'empty cursor');
    }

    /**
     * @covers ::resolve
     */
    public function test_tenant_users_by_user_manager(): void {
        $order_direction = 'DESC';

        // Create 6 user belongs to one tenant and 6 users belongs to another
        [$users, $um1, $um2] = $this->create_tenant_users();

        // Set tenant manager of first tenant
        self::setUser($um1);

        $args = [
            'query' => [
                'sort' => [
                    [
                        'column' => 'id',
                        'direction' => $order_direction
                    ]
                ],
                'pagination' => [
                    'cursor' => null
                ]
            ]
        ];

        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $this->resolve_graphql_query(self::QUERY, $args);

        $this->assertEquals(6, $total, 'wrong total count');
        $this->assertCount(6, $items, 'wrong current page count');
        // Only 6 tenant members, so cursor is empty
        $this->assertEmpty($enc_cursor, 'empty cursor');

        $expected_tenant_id = $um1->tenantid;
        foreach ($items as $user) {
            $this->assertEquals($expected_tenant_id, $user->tenantid);
        }

        // Check if manager can see users from own tenancy
        set_config('tenantsisolated', 1);
        $args = [
            'query' => [
                'sort' => [
                    [
                        'column' => 'id',
                        'direction' => $order_direction
                    ]
                ],
                'pagination' => [
                    'cursor' => null
                ]
            ]
        ];

        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $this->resolve_graphql_query(self::QUERY, $args);

        $this->assertEquals(6, $total, 'wrong total count');
        $this->assertCount(6, $items, 'wrong current page count');
        // Only 6 tenant members, so cursor is empty
        $this->assertEmpty($enc_cursor, 'empty cursor');

        $expected_tenant_id = $um1->tenantid;
        foreach ($items as $user) {
            $this->assertEquals($expected_tenant_id, $user->tenantid);
        }
    }

    /**
     * @covers ::resolve
     */
    public function test_sorted_users_pagination(): void {
        $no_of_users = 10;
        $order_direction = 'DESC';
        $user_ids = $this->create_users($no_of_users)
            ->sort('id', $order_direction)
            ->pluck('id');

        $page_size = $no_of_users - 1;

        $args = [
            'query' => [
                'sort' => [
                    [
                        'column' => 'id',
                        'direction' => $order_direction
                    ]
                ],
                'pagination' => [
                    'limit' => $page_size,
                    'cursor' => null
                ]
            ]
        ];

        // 1st round.
        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $this->resolve_graphql_query(self::QUERY, $args);

        $this->assertEquals($no_of_users + 2 , $total, 'wrong total count');
        $this->assertCount($page_size, $items, 'wrong current page count');
        $this->assertNotEmpty($enc_cursor, 'empty cursor');

        $retrieved = array_column($items, 'id');

        // 2nd round.
        $args = [
            'query' => [
                'sort' => [
                    [
                        'column' => 'id',
                        'direction' => $order_direction
                    ]
                ],
                'pagination' => [
                    'limit' => $page_size,
                    'cursor' => $enc_cursor
                ]
            ]
        ];

        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $this->resolve_graphql_query(self::QUERY, $args);

        $this->assertEquals($no_of_users + 2, $total, 'wrong total count');
        $this->assertCount(3, $items, 'wrong current page count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');

        $retrieved = array_merge($retrieved, array_column($items, 'id'));

        $user_ids = array_merge($user_ids, [(int)get_admin()->id, (int)guest_user()->id]);
        // See if items were retrieved in the correct order.
        $this->assertEquals($user_ids, $retrieved, 'retrieved in wrong order');
    }

    /**
     * Generates users.
     *
     * @param int $count no of users to generate.
     *
     * @return collection a list of user_entity objects.
     */
    private function create_users(int $count = 10): collection {
        $this->setAdminUser();

        $generator = $this->getDataGenerator();

        $users = [];
        foreach (range(0, $count - 1) as $i) {
            $user = $generator->create_user([
                'firstname' => 'Test',
                'lastname' => sprintf('User #%02d', $i)
            ]);

            $users[] = new user_entity($user);
        }

        return collection::new($users);
    }

    /**
     * Generates users belongs to tenants.
     *
     * @return array a list of user_entity objects and managers.
     */
    private function create_tenant_users(): array {
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');

        $tenant_generator->enable_tenants();

        $tenant1 = $tenant_generator->create_tenant();
        $tenant2 = $tenant_generator->create_tenant();

        $users = [];
        for ($i = 0; $i < 5; $i++) {
            $user = $generator->create_user([
                'firstname' => 'Test',
                'lastname' => sprintf('User #%02d', $i)
            ]);
            $tenant_generator->migrate_user_to_tenant($user->id, $tenant1->id);
            $users[] = new user_entity($user);
        }
        for ($i = 0; $i < 5; $i++) {
            $user = $generator->create_user([
                'firstname' => 'Test',
                'lastname' => sprintf('User #%02d', $i)
            ]);
            $tenant_generator->migrate_user_to_tenant($user->id, $tenant2->id);
            $users[] = new user_entity($user);
        }

        $user_manager_role = builder::table('role')->where('shortname', 'tenantusermanager')->one(true);

        $um1 = $generator->create_user([
            'firstname' => 'Tenant 1',
            'lastname' => 'User manager'
        ]);
        $tenant_generator->migrate_user_to_tenant($um1->id, $tenant1->id);
        $um1 = new user_entity($um1->id);

        $um2 = $generator->create_user([
            'firstname' => 'Tenant 2',
            'lastname' => 'User manager'
        ]);
        $tenant_generator->migrate_user_to_tenant($um2->id, $tenant2->id);
        $um1 = new user_entity($um2->id);

        $generator->role_assign($user_manager_role->id, $um1->id, context_tenant::instance($tenant1->id));
        $generator->role_assign($user_manager_role->id, $um2->id, context_tenant::instance($tenant2->id));

        return [collection::new($users), $um1, $um2];
    }

    /**
     * @return void
     */
    public function test_fetch_user_job_assignments(): void {
        // Set up.
        $generator = $this->getDataGenerator();

        // Create test users.
        $users = [];
        $test_user_ids = [];
        for ($i = 0; $i < 3; $i++) {
            $user = $generator->create_user([
                'firstname' => 'Test user ' . uniqid(),
            ]);
            $test_user_ids[] = $user->id;
            $users[] = $user;
        }
        unset($generator);

        $generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $framework = $generator->create_org_frame([]);
        $org_typeid = $generator->create_org_type([]);
        $test_organisation = $generator->create_org(['frameworkid' => $framework->id, 'typeid' => $org_typeid]);
        $framework = $generator->create_pos_frame([]);
        $pos_typeid = $generator->create_pos_type([]);

        // Create a Sales Manager job assignment.
        $test_user1 = $users[0];
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
        ];
        $test_job_assignment1 = job_assignment_object::create($ja_params1);

        // Create a Sales Assistant job assignment.
        $test_user2 = $users[1];
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
        $test_job_assignment2 = job_assignment_object::create($ja_params2);

        // Create a Sales Administrator job assignment.
        $test_user3 = $users[2];
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
            'appraiserid' => $test_appraiser_user->id // test_user3 is the only one with an appraiser in the JA.
        ];
        $test_job_assignment3 = job_assignment_object::create($ja_params3);

        $test_job_assignments = [$test_job_assignment1, $test_job_assignment2, $test_job_assignment3];

        $generator = oauth2_generator::instance();
        $user = self::getDataGenerator()->create_user();
        global $DB;
        $role = $DB->get_record('role', ['archetype' => 'apiuser'], 'id');
        role_assign($role->id, $user->id, context_system::instance());
        $api_client = \totara_api\model\client::create(
            '123',
            $user->id,
            null,
            null,
            1,
            ['create_client_provider' => true]
        );
        /** @var \totara_oauth2\model\client_provider $client_provider */
        $client_provider = $api_client->oauth2_client_providers->first();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Linux x86_64; rv:100.0) Gecko/20100101 Firefox/100.0';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $_SERVER['CONTENT_LENGTH'] = '101';
        $access_token = $generator->create_access_token_from_client_provider(
            $client_provider->get_entity_copy(),
            time() + HOURSECS
        );
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer " . $access_token;

        // Operate.
        ob_start();
        $this->get_external_instance()->process('graphql_request');
        $response = ob_get_clean();
        $users_response_data = json_decode($response, true)['data']['core_user_users']['items'];

        // Assert.
        $fields_to_check = ['idnumber',  'organisationid', 'positionid', 'managerjaid', 'tempmanagerjaid', 'appraiserid'];
        $relations_to_check = ['organisation', 'position', 'managerja',  'appraiser'];

        $i = 0;
        foreach ($users_response_data as $user_data) {
            if (!in_array($user_data['id'], $test_user_ids)) { // exclude users like 'guest'.
                continue;
            }
            $current_job_assignment_result = $user_data['job_assignments'][0];
            $job_assignment_check = $test_job_assignments[$i];
            foreach ($fields_to_check as $field_to_check) {
                $this->assertEquals($job_assignment_check->{$field_to_check}, $current_job_assignment_result[$field_to_check]);
            }

            foreach ($relations_to_check as $relation_to_check) {
                if ($current_job_assignment_result[$relation_to_check]) {
                    $id_field_to_check = $relation_to_check . 'id';
                    $this->assertEquals($job_assignment_check->{$id_field_to_check}, $current_job_assignment_result[$relation_to_check]['id']);
                }
            }
            $i++;
        }
    }

    /**
     * @string|null $query
     * @return api_controller
     */
    private function get_external_instance(?string $query = null): api_controller {
        $class = new class() extends external {
            /** @var string */
            protected $query;

            /** @var server */
            public $server;

            public function __construct(?bool $stop_execution = true, ?string $query = null)
            {
                $this->query = $query;
                parent::__construct($stop_execution);
            }

            public function action_graphql_request(): void {
                $execution_context = $this->get_execution_context();
                $request = new request(
                    $execution_context->get_endpoint_type(),
                    [
                        'operationName' => null,
                        'query' => 'query my_users_query {
                            core_user_users(
                                query: {
                                    pagination: {
                                        limit: 10
                                    }
                                }
                            ) {
                                items {
                                    id
                                    username
                                    job_assignments {
                                        id
                                        idnumber
                                        userid
                                        positionid
                                        organisationid
                                        managerjaid
                                        tempmanagerjaid
                                        appraiserid
                                        fullname
                                        shortname
                                        description
                                        startdate
                                        enddate
                                        tempmanagerexpirydate
                                        staffcount
                                        tempstaffcount
                                        user {
                                            id
                                            firstname
                                        }
                                        position {
                                            id
                                            fullname
                                            idnumber
                                        }
                                        organisation {
                                            id
                                            fullname
                                        }
                                        managerja {
                                            id
                                            fullname
                                        }
                                        tempmanagerja {
                                            id
                                            fullname
                                        }
                                        appraiser {
                                            id
                                            fullname
                                        }
                                    }
                                }
                            }
                        }
                        ',
                    ]
                );
                $this->server = new server($execution_context, DebugFlag::INCLUDE_DEBUG_MESSAGE);
                $result = $this->server->handle_request($request);
                $this->server->send_response($result, false);
            }
        };

        return new $class(false, $query);
    }
}