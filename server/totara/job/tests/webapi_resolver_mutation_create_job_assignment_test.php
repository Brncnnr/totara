<?php
/*
 * This file is part of Totara LMS
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
 * @author Michael Ivanov <michael.ivanov@totaralearning.com>
 * @package totara_job
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/job/lib.php');

use core_phpunit\testcase;
use core_user\access_controller;
use totara_core\advanced_feature;
use totara_job\exception\job_assignment_create_exception;
use totara_job\job_assignment;
use totara_webapi\phpunit\webapi_phpunit_helper;
use core\exception\unresolved_record_reference;

/**
 * Tests the create job assignment mutation
 * @group totara_job
 */
class totara_job_webapi_resolver_mutation_create_job_assignment_testcase extends testcase {

    use webapi_phpunit_helper;

    protected $mutation = 'totara_job_create_job_assignment';

    public function test_resolve_nologgedin() {
        $user = $this->getDataGenerator()->create_user();

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Course or activity not accessible. (You are not logged in)');

        $this->resolve_graphql_mutation(
            $this->mutation,
            ['userid' => $user->id, 'idnumber' => 'j1']
        );
    }

    public function test_resolve_guestuser() {
        $this->setGuestUser();
        $user = $this->getDataGenerator()->create_user();

        $this->expectException(require_login_exception::class);
        $this->resolve_graphql_mutation($this->mutation, ['userid' => $user->id, 'idnumber' => 'j1']);
    }

    public function test_resolve_normaluser() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $this->expectException(job_assignment_create_exception::class);
        $this->expectExceptionMessage(
            'The user does not exist or you do not have permission to create a job assignment.'
        );

        $this->resolve_graphql_mutation($this->mutation, ['userid' => $user->id, 'idnumber' => 'j1']);
    }

    public function test_resolve_normaluser_with_capabilities() {
        $user = $this->getDataGenerator()->create_user();
        $target_user = $this->getDataGenerator()->create_user();

        // Give the API user the required capabilities through a role.
        $gen = self::getDataGenerator();
        $role_id = $gen->create_role();
        assign_capability(
            'totara/hierarchy:assignuserposition',
            CAP_ALLOW,
            $role_id,
            context_user::instance($target_user->id)
        );
        assign_capability('moodle/user:viewalldetails', CAP_ALLOW, $role_id, context_user::instance($target_user->id));
        role_assign($role_id, $user->id, context_system::instance());
        $this->setUser($user);

        $this->resolve_graphql_mutation(
            $this->mutation,
            [
                'input' => [
                    'idnumber' => '189812',
                    'user' => [
                        'id' => $target_user->id,
                    ]
                ]
            ]
        );
        $job_assignment_refreshed = job_assignment::get_with_idnumber($target_user->id, '189812');
        self::assertSame($target_user->id, $job_assignment_refreshed->userid);
    }

    public function test_resolve_tenantuser() {
        $tenant_generator = $this->getDataGenerator()->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();
        set_config('tenantsisolated', 0);
        $tenant1 = $tenant_generator->create_tenant();
        $tenant2 = $tenant_generator->create_tenant();
        $user = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $target_user = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);

        // Give the API user the required capabilities through a role.
        $gen = self::getDataGenerator();
        $role_id = $gen->create_role();
        assign_capability(
            'totara/hierarchy:assignuserposition',
            CAP_ALLOW,
            $role_id,
            context_tenant::instance($tenant1->id)
        );
        assign_capability('moodle/user:viewalldetails', CAP_ALLOW, $role_id, context_tenant::instance($tenant1->id));
        role_assign($role_id, $user->id, context_system::instance());
        $this->setUser($user);

        $this->expectException(job_assignment_create_exception::class);
        $this->resolve_graphql_mutation(
            $this->mutation,
            [
                'input' => [
                    'idnumber' => '123',
                    'user' => [
                        'id' => $target_user->id,
                    ]
                ]
            ]
        );
    }

    public function test_resolve_system_user_tenant_isolation() {
        $tenant_generator = $this->getDataGenerator()->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();
        set_config('tenantsisolated', 0);
        $tenant = $tenant_generator->create_tenant();
        $user = $this->getDataGenerator()->create_user(['tenantid' => $tenant->id]);

        // Give the API user the required capabilities through a role.
        $gen = self::getDataGenerator();
        $role_id = $gen->create_role();
        assign_capability(
            'totara/hierarchy:assignuserposition',
            CAP_ALLOW,
            $role_id,
            context_system::instance()
        );
        assign_capability('moodle/user:viewalldetails', CAP_ALLOW, $role_id, context_system::instance());
        role_assign($role_id, $user->id, context_system::instance());
        $this->setUser($user);

        $target_system_user = $this->getDataGenerator()->create_user();
        $this->resolve_graphql_mutation(
            $this->mutation,
            [
                'input' => [
                    'idnumber' => '123',
                    'user' => [
                        'id' => $target_system_user->id,
                    ]
                ]
            ]
        );

        $job_assignment = job_assignment::get_with_idnumber($target_system_user->id, '123');
        self::assertSame($target_system_user->id, $job_assignment->userid);

        set_config('tenantsisolated', 1);
        access_controller::clear_instance_cache();

        $this->expectException(job_assignment_create_exception::class);
        $this->resolve_graphql_mutation(
            $this->mutation,
            [
                'input' => [
                    'idnumber' => '123',
                    'user' => [
                        'id' => $target_system_user->id,
                    ]
                ]
            ]
        );
    }

    public function test_resolve_adminuser() {
        $this->setAdminUser();
        $user = $this->getDataGenerator()->create_user(['idnumber' => '122']);

        $now = time();

        $this->resolve_graphql_mutation(
            $this->mutation,
            [
                'input' => [
                    'idnumber' => 'j2',
                    'user' => [
                        'idnumber' => $user->idnumber,
                    ],
                    'fullname' => 'Test & test',
                    'start_date' => $now - 86400,
                    'end_date' => $now + 86400,
                ]
            ]
        );

        $job_assignment_refreshed = job_assignment::get_with_idnumber($user->id, 'j2');
        self::assertSame($user->id, $job_assignment_refreshed->userid);
        self::assertSame('j2', $job_assignment_refreshed->idnumber);
        self::assertSame('Test & test', $job_assignment_refreshed->fullname);
        self::assertEquals($now - 86400, $job_assignment_refreshed->startdate);
        self::assertEquals($now + 86400, $job_assignment_refreshed->enddate);

        // Duplicate id number.
        try {
            $this->resolve_graphql_mutation(
                $this->mutation,
                [
                    'input' => [
                        'idnumber' => 'j2',
                        'user' => [
                            'id' => $user->id,
                        ]
                    ]
                ]
            );
            $this->fail('Exception expected.');
        } catch (job_assignment_create_exception $ex) {
            self::assertStringContainsString(
                'Tried to create job assignment idnumber which is not unique for this user',
                $ex->getMessage()
            );
        }
    }

    public function test_resolve_with_multiple_jobs() {
        $this->setAdminUser();
        $user = $this->getDataGenerator()->create_user();
        global $CFG;
        $prev_config_value = $CFG->totara_job_allowmultiplejobs;
        set_config('totara_job_allowmultiplejobs', '0');

        $this->resolve_graphql_mutation(
            $this->mutation,
            [
                'input' => [
                    'idnumber' => 'j2',
                    'user' => [
                        'id' => $user->id,
                    ]
                ]
            ]
        );

        $job_assignment_refreshed = job_assignment::get_with_idnumber($user->id, 'j2');
        self::assertSame($user->id, $job_assignment_refreshed->userid);
        self::assertSame('j2', $job_assignment_refreshed->idnumber);

        //Try to create one more job assignment for this user
        try {
            $this->resolve_graphql_mutation(
                $this->mutation,
                [
                    'input' => [
                        'idnumber' => 'j3',
                        'user' => [
                            'id' => $user->id,
                        ]
                    ]
                ]
            );
            $this->fail('Expected exception');
        } catch (job_assignment_create_exception $exception) {
            self::assertStringContainsString(
                'Attempting to create multiple job assignments for user',
                $exception->getMessage()
            );
        }

        set_config('totara_job_allowmultiplejobs', $prev_config_value);
    }

    public function test_modified_fields() {
        $this->setAdminUser();
        $user = $this->getDataGenerator()->create_user();

        $this->resolve_graphql_mutation(
            $this->mutation,
            [
                'input' => [
                    'idnumber' => 'j2',
                    'user' => [
                        'id' => $user->id,
                    ]
                ]
            ]
        );

        $job_assignment_refreshed = job_assignment::get_with_idnumber($user->id, 'j2');
        self::assertSame($user->id, $job_assignment_refreshed->userid);
        self::assertSame('2', $job_assignment_refreshed->usermodified);
        self::assertLessThan(60, abs($job_assignment_refreshed->timemodified - time()));
    }

    public function test_resolve_managerjaid() {
        global $DB;

        $this->setAdminUser();
        $user = $this->getDataGenerator()->create_user();

        $badmanagerjaid = 100000;
        while ($DB->record_exists('job_assignment', array('id' => $badmanagerjaid))) {
            $badmanagerjaid++;
        }

        // Non existent manager job assignment.
        try {
            $this->resolve_graphql_mutation(
                $this->mutation,
                [
                    'input' => [
                        'idnumber' => 'kjlj2',
                        'user' => [
                            'id' => $user->id,
                        ],
                        'manager' => [
                            'id' => $badmanagerjaid
                        ]
                    ]
                ]
            );
            $this->fail('Exception expected.');
        } catch (\moodle_exception $ex) {
            self::assertStringContainsString('Manager reference not found', $ex->getMessage());
        }

        // Setting yourself as manager.
        job_assignment::create(['userid' => $user->id, 'idnumber' => '22']);
        try {
            $this->resolve_graphql_mutation(
                $this->mutation,
                [
                    'input' => [
                        'idnumber' => '1lkj21',
                        'user' => [
                            'id' => $user->id,
                        ],
                        'manager' => [
                            'idnumber' => '22',
                        ]
                    ]
                ]
            );
            $this->fail('Exception expected.');
        } catch (job_assignment_create_exception $ex) {
            self::assertStringContainsString('The user cannot be assigned as their own manager.', $ex->getMessage());
        }

        // Create a manager and test it worked as expected.
        $manager = $this->getDataGenerator()->create_user();
        $manager_job_assignment = job_assignment::create(['userid' => $manager->id, 'idnumber' => '12312']);

        $this->resolve_graphql_mutation(
            $this->mutation,
            [
                'input' => [
                    'idnumber' => 'lkj2',
                    'user' => [
                        'id' => $user->id,
                    ],
                    'manager' => [
                        'id' => $manager_job_assignment->id
                    ]
                ]
            ]
        );

        $job = job_assignment::get_with_idnumber($user->id, 'lkj2');
        self::assertInstanceOf(job_assignment::class, $job);
        self::assertSame($user->id, $job->userid);
        self::assertSame('lkj2', $job->idnumber);
        self::assertSame($manager_job_assignment->id, $job->managerjaid);
        self::assertSame($manager->id, $job->managerid);
    }

    public function test_resolve_managerjaid_from_other_tenant() {
        $tenant_generator = $this->getDataGenerator()->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();
        $tenant1 = $tenant_generator->create_tenant();
        $tenant2 = $tenant_generator->create_tenant();

        $user = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        // Give the API user the required capabilities through a role.
        $gen = self::getDataGenerator();
        $role_id = $gen->create_role();
        assign_capability(
            'totara/hierarchy:assignuserposition',
            CAP_ALLOW,
            $role_id,
            context_tenant::instance($tenant1->id)
        );
        assign_capability('moodle/user:viewalldetails', CAP_ALLOW, $role_id, context_tenant::instance($tenant1->id));
        assign_capability(
            'totara/core:delegateusersmanager',
            CAP_ALLOW,
            $role_id,
            context_tenant::instance($tenant1->id)
        );
        role_assign($role_id, $user->id, context_tenant::instance($tenant1->id));
        $this->setUser($user);

        // Create a manager with incorrect tenant
        $manager = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $manager_job_assignment = job_assignment::create(['userid' => $manager->id, 'idnumber' => '12312']);

        try {
            $this->resolve_graphql_mutation(
                $this->mutation,
                [
                    'input' => [
                        'idnumber' => 'lkj2',
                        'user' => [
                            'id' => $user->id,
                        ],
                        'manager' => [
                            'id' => $manager_job_assignment->id
                        ]
                    ]
                ]
            );
            $this->fail('Expect exception');
        } catch (moodle_exception $exception) {
            self::assertStringContainsString('Manager user reference not found', $exception->getMessage());
        }

        // Now with the correct tenant
        $manager = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $manager_job_assignment = job_assignment::create(['userid' => $manager->id, 'idnumber' => '12312']);

        $this->resolve_graphql_mutation(
            $this->mutation,
            [
                'input' => [
                    'idnumber' => 'lkj2',
                    'user' => [
                        'id' => $user->id,
                    ],
                    'manager' => [
                        'id' => $manager_job_assignment->id
                    ]
                ]
            ]
        );

        $job = job_assignment::get_with_idnumber($user->id, 'lkj2');
        self::assertInstanceOf(job_assignment::class, $job);
        self::assertSame($user->id, $job->userid);
        self::assertSame('lkj2', $job->idnumber);
        self::assertSame($manager_job_assignment->id, $job->managerjaid);
        self::assertSame($manager->id, $job->managerid);
    }

    public function test_resolve_managerjaid_from_system() {
        $tenant_generator = $this->getDataGenerator()->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();
        set_config('tenantsisolated', 1);
        $tenant = $tenant_generator->create_tenant();

        $user = $this->getDataGenerator()->create_user(['tenantid' => $tenant->id]);
        // Give the API user the required capabilities through a role.
        $gen = self::getDataGenerator();
        $role_id = $gen->create_role();
        assign_capability(
            'totara/hierarchy:assignuserposition',
            CAP_ALLOW,
            $role_id,
            context_system::instance()
        );
        assign_capability('moodle/user:viewalldetails', CAP_ALLOW, $role_id, context_system::instance());
        assign_capability(
            'totara/core:delegateusersmanager',
            CAP_ALLOW,
            $role_id,
            context_system::instance()
        );
        role_assign($role_id, $user->id, context_system::instance());
        $this->setUser($user);

        $manager = $this->getDataGenerator()->create_user();
        $manager_job_assignment = job_assignment::create(['userid' => $manager->id, 'idnumber' => '12312']);

        try {
            $this->resolve_graphql_mutation(
                $this->mutation,
                [
                    'input' => [
                        'idnumber' => 'lkj2',
                        'user' => [
                            'id' => $user->id,
                        ],
                        'manager' => [
                            'id' => $manager_job_assignment->id
                        ]
                    ]
                ]
            );
            $this->fail('Expect exception');
        } catch (moodle_exception $exception) {
            self::assertStringContainsString('Manager user reference not found', $exception->getMessage());
        }

        // Now with the isolation off
        set_config('tenantsisolated', 0);
        access_controller::clear_instance_cache();

        $this->resolve_graphql_mutation(
            $this->mutation,
            [
                'input' => [
                    'idnumber' => 'lkj2',
                    'user' => [
                        'id' => $user->id,
                    ],
                    'manager' => [
                        'id' => $manager_job_assignment->id
                    ]
                ]
            ]
        );

        $job = job_assignment::get_with_idnumber($user->id, 'lkj2');
        self::assertInstanceOf(job_assignment::class, $job);
        self::assertSame($user->id, $job->userid);
        self::assertSame('lkj2', $job->idnumber);
        self::assertSame($manager_job_assignment->id, $job->managerjaid);
        self::assertSame($manager->id, $job->managerid);
    }

    public function test_resolve_tempmanagerjaid() {
        global $DB, $CFG;

        $this->setAdminUser();
        $prev_config_value = $CFG->enabletempmanagers;
        set_config('enabletempmanagers', '1');
        $user = $this->getDataGenerator()->create_user();
        $temp_manager_user  = $this->getDataGenerator()->create_user();
        $badtempmanagerjaid = 100000;
        while ($DB->record_exists('job_assignment', array('id' => $badtempmanagerjaid))) {
            $badtempmanagerjaid++;
        }

        // Non existent temporary manager job assignment.
        try {
            $this->resolve_graphql_mutation(
                $this->mutation,
                [
                    'input' => [
                        'idnumber' => 'lk22',
                        'user' => [
                            'id' => $user->id,
                        ],
                        'temp_manager' => [
                            'id' => $badtempmanagerjaid,
                        ],
                        'temp_manager_expiry_date' => time() + DAYSECS
                    ],
                ]
            );
            $this->fail('Exception expected.');
        } catch (\moodle_exception $ex) {
            self::assertStringContainsString('Temporary manager reference not found', $ex->getMessage());
        }

        // Setting yourself as temporary manager.
        job_assignment::create(['userid' => $user->id, 'idnumber' => 'sdams']);
        try {
            $this->resolve_graphql_mutation(
                $this->mutation,
                [
                    'input' => [
                        'idnumber' => '222l3',
                        'user' => [
                            'id' => $user->id,
                        ],
                        'temp_manager' => [
                            'idnumber' => 'sdams',
                        ],
                        'temp_manager_expiry_date' => time() + DAYSECS
                    ],
                ]
            );
            $this->fail('Exception expected.');
        } catch (job_assignment_create_exception $ex) {
            self::assertStringContainsString(
                'The user cannot be assigned as their own temporary manager.',
                $ex->getMessage()
            );
        }

        // No expiry date.
        try {
            $this->resolve_graphql_mutation(
                $this->mutation,
                [
                    'input' => [
                        'idnumber' => '1jnm4',
                        'user' => [
                            'id' => $user->id,
                        ],
                        'temp_manager' => [
                            'idnumber' => 'sdams'
                        ]
                    ]
                ]
            );
            $this->fail('Exception expected.');
        } catch (job_assignment_create_exception $ex) {
            self::assertStringContainsString('A temporary manager expiry date is required.', $ex->getMessage());
        }

        // Expiry date in the past.
        $temp_manager = job_assignment::create(['userid' => $temp_manager_user->id, 'idnumber' => 'temp']);
        try {
            $this->resolve_graphql_mutation(
                $this->mutation,
                [
                    'input' => [
                        'idnumber' => '3kj22',
                        'user' => [
                            'id' => $user->id,
                        ],
                        'temp_manager' => [
                            'id' => $temp_manager->id,
                        ],
                        'temp_manager_expiry_date' => time() - DAYSECS
                    ]
                ]
            );
            $this->fail('Exception expected.');
        } catch (job_assignment_create_exception $ex) {
            self::assertStringContainsString(
                'The temporary manager expiry date can not be in the past.',
                $ex->getMessage()
            );
        }

        // Now without any issues..
        $this->resolve_graphql_mutation(
            $this->mutation,
            [
                'input' => [
                    'idnumber' => ',2m2m2',
                    'user' => [
                        'id' => $user->id,
                    ],
                    'temp_manager' => [
                        'idnumber' => 'temp',
                    ],
                    'temp_manager_expiry_date' => time() + DAYSECS,
                ]
            ]
        );

        $job = job_assignment::get_with_idnumber($user->id, ',2m2m2');
        self::assertInstanceOf(job_assignment::class, $job);
        self::assertSame($user->id, $job->userid);
        self::assertSame($temp_manager->id, $job->tempmanagerjaid);
        self::assertSame($temp_manager_user->id, $job->tempmanagerid);
        set_config('enabletempmanagers', $prev_config_value);
    }

    public function test_resolve_position() {
        $this->setAdminUser();
        $user = $this->getDataGenerator()->create_user();

        /** @var \totara_hierarchy\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $framework = $generator->create_pos_frame([]);
        $typeid = $generator->create_pos_type([]);
        $position1 = $generator->create_pos(['frameworkid' => $framework->id, 'typeid' => $typeid]);

        // Non existent position.
        try {
            $this->resolve_graphql_mutation(
                $this->mutation,
                [
                    'input' => [
                        'idnumber' => 'lkjlkj',
                        'user' => [
                            'id' => $user->id,
                        ],
                        'position' => [
                            'idnumber' => '1lklkjl1'
                        ]
                    ]
                ]
            );
            $this->fail('Exception expected.');
        } catch (\moodle_exception $ex) {
            self::assertStringContainsString('Position reference not found', $ex->getMessage());
        }

        // Now use correct position.
        $this->resolve_graphql_mutation(
            $this->mutation,
            [
                'input' => [
                    'idnumber' => 'kjlk11',
                    'user' => [
                        'id' => $user->id,
                    ],
                    'position' => [
                        'id' => $position1->id,
                    ]
                ],
            ]
        );

        $job = job_assignment::get_with_idnumber($user->id, 'kjlk11');
        self::assertInstanceOf(job_assignment::class, $job);
        self::assertSame($user->id, $job->userid);
        self::assertSame($position1->id, $job->positionid);
        self::assertLessThan(60, abs($job->positionassignmentdate - time()));
    }

    public function test_resolve_organisation() {
        $this->setAdminUser();
        $user = $this->getDataGenerator()->create_user();

        /** @var \totara_hierarchy\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $framework = $generator->create_org_frame([]);
        $typeid = $generator->create_org_type([]);
        $organisation1 = $generator->create_org(['frameworkid' => $framework->id, 'typeid' => $typeid]);

        // Non existent organisation.
        try {
            $this->resolve_graphql_mutation(
                $this->mutation,
                [
                    'input' => [
                        'idnumber' => '12k1',
                        'user' => [
                            'id' => $user->id,
                        ],
                        'organisation' => [
                            'idnumber' => 'k2lk23k3n',
                        ]
                    ]
                ]
            );
            $this->fail('Exception expected.');
        } catch (\moodle_exception $ex) {
            self::assertStringContainsString('Organisation reference not found', $ex->getMessage());
        }

        // Now use correct organisation.
        $this->resolve_graphql_mutation(
            $this->mutation,
            [
                'input' => [
                    'idnumber' => '1kjl2kl',
                    'user' => [
                        'id' => $user->id,
                    ],
                    'organisation' => [
                        'idnumber' => $organisation1->idnumber,
                    ]
                ],
            ]
        );
        $job = job_assignment::get_with_idnumber($user->id, '1kjl2kl');
        self::assertInstanceOf(job_assignment::class, $job);
        self::assertSame($user->id, $job->userid);
        self::assertSame($organisation1->id, $job->organisationid);
    }

    public function test_resolve_appraiser() {
        $this->setAdminUser();
        $user = $this->getDataGenerator()->create_user();
        $appraiser = $this->getDataGenerator()->create_user();

        // Non existent appraiser.
        try {
            $this->resolve_graphql_mutation(
                $this->mutation,
                [
                    'input' => [
                        'idnumber' => 'kkjlk2',
                        'user' => [
                            'id' => $user->id,
                        ],
                        'appraiser' => [
                            'idnumber' => '1lklkl2',
                        ]
                    ]
                ]
            );
            $this->fail('Exception expected.');
        } catch (unresolved_record_reference $ex) {
            self::assertStringContainsString('Appraiser reference not found', $ex->getMessage());
        }

        // Guest user
        $guest_user = guest_user();
        try {
            $this->resolve_graphql_mutation(
                $this->mutation,
                [
                    'input' => [
                        'idnumber' => 'kljlk1',
                        'user' => [
                            'id' => $user->id,
                        ],
                        'appraiser' => [
                            'id' => $guest_user->id,
                        ]
                    ]
                ]
            );
            $this->fail('Exception expected.');
        } catch (\moodle_exception $ex) {
            self::assertStringContainsString('Guest user can not be specified for Appraiser', $ex->getMessage());
        }

        // Now use correct appraiser.
        $this->resolve_graphql_mutation(
            $this->mutation,
            [
                'input' => [
                    'idnumber' => '1mn2,n2',
                    'user' => [
                        'id' => $user->id,
                    ],
                    'appraiser' => [
                        'id' => $appraiser->id,
                    ]
                ]
            ]
        );
        $job = job_assignment::get_with_idnumber($user->id, '1mn2,n2');
        self::assertInstanceOf(job_assignment::class, $job);
        self::assertSame($user->id, $job->userid);
        self::assertSame($appraiser->id, $job->appraiserid);
    }

    public function test_resolve_start_end_dates() {
        $this->setAdminUser();
        $user = $this->getDataGenerator()->create_user();

        // Use start date greater than end date.
        $start = time() + DAYSECS;
        $end = time() - DAYSECS;
        try {
            $this->resolve_graphql_mutation(
                $this->mutation,
                [
                    'input' => [
                        'idnumber' => 'lkkm,2',
                        'user' => [
                            'id' => $user->id,
                        ],
                        'start_date' => $start,
                        'end_date' => $end
                    ]
                ]
            );
            $this->fail('Exception expected.');
        } catch (job_assignment_create_exception $ex) {
            self::assertStringContainsString('The start date can not be later than the end date.', $ex->getMessage());
        }

        // Correct dates
        $start = time() - DAYSECS;
        $end = time() + DAYSECS;
        $this->resolve_graphql_mutation(
            $this->mutation,
            [
                'input' => [
                    'idnumber' => ',mn,n222',
                    'user' => [
                        'id' => $user->id,
                    ],
                    'start_date' => $start,
                    'end_date' => $end,
                ]
            ]
        );

        $job = job_assignment::get_with_idnumber($user->id, ',mn,n222');
        self::assertInstanceOf(job_assignment::class, $job);
        self::assertSame($user->id, $job->userid);
        self::assertSame($start, (int)$job->startdate);
        self::assertSame($end, (int)$job->enddate);
    }

    public function test_resolve_position_disabled(): void {
        self::setAdminUser();

        $user = $this->getDataGenerator()->create_user();
        /** @var \totara_hierarchy\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $framework = $generator->create_pos_frame([]);
        $typeid = $generator->create_pos_type([]);
        $position = $generator->create_pos(['frameworkid' => $framework->id, 'typeid' => $typeid]);

        advanced_feature::disable('positions');

        $this->expectException(job_assignment_create_exception::class);
        $this->expectExceptionMessage('Position feature is disabled.');
        $this->resolve_graphql_mutation(
            $this->mutation,
            [
                'input' => [
                    'idnumber' => 'idnumber',
                    'user' => [
                        'id' => $user->id,
                    ],
                    'position' => [
                        'id' => $position->id,
                    ]
                ]
            ]
        );
    }

    public function test_resolve_organisation_disabled(): void {
        self::setAdminUser();

        $user = $this->getDataGenerator()->create_user();
        /** @var \totara_hierarchy\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $framework = $generator->create_org_frame([]);
        $typeid = $generator->create_org_type([]);
        $organisation = $generator->create_org(['frameworkid' => $framework->id, 'typeid' => $typeid]);

        advanced_feature::disable('organisations');

        $this->expectException(job_assignment_create_exception::class);
        $this->expectExceptionMessage('Organisations feature is disabled.');
        $this->resolve_graphql_mutation(
            $this->mutation,
            [
                'input' => [
                    'idnumber' => 'idnumber',
                    'user' => [
                        'id' => $user->id,
                    ],
                    'organisation' => [
                        'id' => $organisation->id,
                    ]
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_job_assignment_with_config_setting_for_shortname(): void {
        global $CFG;
        $original_config = $CFG->showhierarchyshortnames;

        // Set up.
        $this->setAdminUser();
        $user1 = $this->getDataGenerator()->create_user();
        $test_ja_idnumber = uniqid();
        $test_ja_idnumber2 = uniqid();
        $test_shortname = 'test shortname';

        $request_args =  [
            'input' => [
                'idnumber' => $test_ja_idnumber,
                'user' => [
                    'id' => $user1->id,
                ],
                'shortname' => $test_shortname
            ]
        ];

        // Operate - test with config enabled. Request should succeed & shortname value should show value in response.
        set_config('showhierarchyshortnames', '1');
        $result = $this->resolve_graphql_mutation(
            $this->mutation,
            $request_args
        );
        $this->assertEquals($test_shortname, $result['job_assignment']->shortname);

        // Operate - test with config disabled. Request should succeed & shortname value should still show value in response
        // to match the UI behaviour.
        set_config('showhierarchyshortnames', '0');
        $request_args['input']['idnumber'] = $test_ja_idnumber2;
        $result = $this->resolve_graphql_mutation(
            $this->mutation,
            $request_args
        );
        $this->assertEquals($test_shortname, $result['job_assignment']->shortname);

        // Tear down.
        set_config('showhierarchyshortnames', $original_config);
    }

    /**
     * @return void
     */
    public function test_resolve_system_user_when_isolation_on(): void {
        $gen = self::getDataGenerator();
        // Turn on isolation
        set_config('tenantsisolated', 1);

        // Create tenant;
        $tenant_generator = $gen->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();
        $tenant1 = $tenant_generator->create_tenant();

        $tenant_user = $gen->create_user(['tenantid' => $tenant1->id]);
        $system_user = $gen->create_user();

        // Give the API user the required capabilities through a role.
        $role_id = $gen->create_role();
        assign_capability(
            'totara/hierarchy:assignuserposition',
            CAP_ALLOW,
            $role_id,
            context_tenant::instance($tenant1->id)
        );
        assign_capability('moodle/user:viewalldetails', CAP_ALLOW, $role_id, context_tenant::instance($tenant1->id));
        role_assign($role_id, $tenant_user->id, context_system::instance());
        self::setUser($tenant_user);

        $this->expectException(job_assignment_create_exception::class);
        $this->expectExceptionMessage(
            'The user does not exist or you do not have permission to create a job assignment.'
        );
        $this->resolve_graphql_mutation(
            $this->mutation,
            [
                'input' => [
                    'idnumber' => '123',
                    'user' => [
                        'id' => $system_user->id,
                    ]
                ]
            ]
        );
    }
}