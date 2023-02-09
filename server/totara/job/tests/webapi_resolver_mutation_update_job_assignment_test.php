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
use totara_job\exception\job_assignment_update_exception;
use totara_job\job_assignment;
use totara_webapi\phpunit\webapi_phpunit_helper;
use totara_tenant\local\util as tenant_util;

/**
 * Tests the update job assignment mutation
 * @group totara_job
 */
class totara_job_webapi_resolver_mutation_update_job_assignment_testcase extends testcase {

    use webapi_phpunit_helper;

    protected $mutation = 'totara_job_update_job_assignment';

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

        $this->expectException(job_assignment_update_exception::class);
        $this->expectExceptionMessage('Job assignment does not exist or you do not have permission to manage it.');

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
        $job_assignment = job_assignment::create(['userid' => $target_user->id, 'idnumber' => '12312']);

        $this->resolve_graphql_mutation(
            $this->mutation,
            [
                'target_job' => [
                    'idnumber' => $job_assignment->idnumber,
                ]
            ]
        );
        $job_assignment_refreshed = job_assignment::get_with_id($job_assignment->id);
        self::assertSame($target_user->id, $job_assignment_refreshed->userid);
    }

    public function test_resolve_tenantuser() {
        $tenant_generator = $this->getDataGenerator()->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();
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
        $job_assignment = job_assignment::create(['userid' => $target_user->id, 'idnumber' => '12312']);

        $this->expectException(job_assignment_update_exception::class);
        $this->resolve_graphql_mutation(
            $this->mutation,
            [
                'target_job' => [
                    'idnumber' => $job_assignment->idnumber,
                ]
            ]
        );
    }

    public function test_resolve_adminuser() {
        $this->setAdminUser();
        $user = $this->getDataGenerator()->create_user();
        $job_assignment = job_assignment::create(['userid' => $user->id, 'idnumber' => '12312']);
        $job_assignment2 = job_assignment::create(['userid' => $user->id, 'idnumber' => '123312']);

        $now = time();

        $this->resolve_graphql_mutation(
            $this->mutation,
            [
                'target_job' => [
                    'id' => $job_assignment->id,
                ],
                'input' => [
                    'idnumber' => 'j2',
                    'fullname' => 'Test & test',
                    'start_date' => $now - 86400,
                    'end_date' => $now + 86400,
                ]
            ]
        );

        $job_assignment_refreshed = job_assignment::get_with_id($job_assignment->id);
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
                    'target_job' => [
                        'id' => $job_assignment->id,
                    ],
                    'input' => [
                        'idnumber' => $job_assignment2->idnumber,
                    ]
                ]
            );
            $this->fail('Exception expected.');
        } catch (job_assignment_update_exception $ex) {
            self::assertStringContainsString(
                'Tried to update job assignment to an idnumber which is not unique for this user',
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
        $job_assignment = job_assignment::create(['userid' => $user->id, 'idnumber' => '12312']);

        $this->resolve_graphql_mutation(
            $this->mutation,
            [
                'target_job' => [
                    'id' => $job_assignment->id,
                ],
                'input' => [
                    'idnumber' => 'j2',
                ]
            ]
        );

        $job_assignment_refreshed = job_assignment::get_with_id($job_assignment->id);
        self::assertSame($user->id, $job_assignment_refreshed->userid);
        self::assertSame('j2', $job_assignment_refreshed->idnumber);
        set_config('totara_job_allowmultiplejobs', $prev_config_value);
    }

    public function test_modified_fields() {
        $this->setAdminUser();
        $user = $this->getDataGenerator()->create_user();
        $job_assignment = job_assignment::create(['userid' => $user->id, 'idnumber' => '12312']);
        global $DB;
        $DB->update_record('job_assignment', ['timemodified' => 0, 'id' => $job_assignment->id]);
        $job_assignment = job_assignment::get_with_id($job_assignment->id);

        $this->resolve_graphql_mutation(
            $this->mutation,
            [
                'target_job' => [
                    'id' => $job_assignment->id,
                ],
                'input' => [
                    'idnumber' => 'j2',
                ]
            ]
        );

        $job_assignment_refreshed = job_assignment::get_with_id($job_assignment->id);
        self::assertSame($user->id, $job_assignment_refreshed->userid);
        self::assertSame('2', $job_assignment_refreshed->usermodified);
        self::assertLessThan(60, abs($job_assignment_refreshed->timemodified - time()));
    }

    public function test_resolve_managerjaid() {
        global $DB;

        $this->setAdminUser();
        $user = $this->getDataGenerator()->create_user();

        $job_assignment = job_assignment::create(['userid' => $user->id, 'idnumber' => '12312']);
        $badmanagerjaid = 100000;
        while ($DB->record_exists('job_assignment', array('id' => $badmanagerjaid))) {
            $badmanagerjaid++;
        }

        // Non existent manager job assignment.
        try {
            $this->resolve_graphql_mutation(
                $this->mutation,
                [
                    'target_job' => [
                        'id' => $job_assignment->id,
                    ],
                    'input' => [
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
                    'target_job' => [
                        'id' => $job_assignment->id,
                    ],
                    'input' => [
                        'manager' => [
                            'idnumber' => '22',
                        ]
                    ]
                ]
            );
            $this->fail('Exception expected.');
        } catch (job_assignment_update_exception $ex) {
            self::assertStringContainsString('The user cannot be assigned as their own manager.', $ex->getMessage());
        }

        // Create a manager and test it worked as expected.
        $manager = $this->getDataGenerator()->create_user();
        $manager_job_assignment = job_assignment::create(['userid' => $manager->id, 'idnumber' => '12312']);

        $this->resolve_graphql_mutation(
            $this->mutation,
            [
                'target_job' => [
                    'id' => $job_assignment->id,
                ],
                'input' => [
                    'manager' => [
                        'id' => $manager_job_assignment->id
                    ]
                ]
            ]
        );

        $job = job_assignment::get_with_id($job_assignment->id);
        self::assertInstanceOf(job_assignment::class, $job);
        self::assertSame($user->id, $job->userid);
        self::assertSame('12312', $job->idnumber);
        self::assertSame($manager_job_assignment->id, $job->managerjaid);
        self::assertSame($manager->id, $job->managerid);
    }

    public function test_resolve_tempmanagerjaid() {
        global $DB, $CFG;

        $this->setAdminUser();
        $prev_config_value = $CFG->enabletempmanagers;
        set_config('enabletempmanagers', '1');
        $user = $this->getDataGenerator()->create_user();
        $job_assignment = job_assignment::create(['userid' => $user->id, 'idnumber' => 'jj3j3']);
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
                    'target_job' => [
                        'id' => $job_assignment->id
                    ],
                    'input' => [
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
                    'target_job' => [
                        'id' => $job_assignment->id,
                    ],
                    'input' => [
                        'temp_manager' => [
                            'idnumber' => 'sdams',
                        ],
                        'temp_manager_expiry_date' => time() + DAYSECS
                    ],
                ]
            );
            $this->fail('Exception expected.');
        } catch (job_assignment_update_exception $ex) {
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
                    'target_job' => [
                        'id' => $job_assignment->id,
                    ],
                    'input' => [
                        'temp_manager' => [
                            'idnumber' => 'sdams'
                        ]
                    ]
                ]
            );
            $this->fail('Exception expected.');
        } catch (job_assignment_update_exception $ex) {
            self::assertStringContainsString('A temporary manager expiry date is required.', $ex->getMessage());
        }

        // Expiry date in the past.
        $temp_manager = job_assignment::create(['userid' => $temp_manager_user->id, 'idnumber' => 'temp']);
        try {
            $this->resolve_graphql_mutation(
                $this->mutation,
                [
                    'target_job' => [
                        'id' => $job_assignment->id,
                    ],
                    'input' => [
                        'temp_manager' => [
                            'id' => $temp_manager->id,
                        ],
                        'temp_manager_expiry_date' => time() - DAYSECS
                    ]
                ]
            );
            $this->fail('Exception expected.');
        } catch (job_assignment_update_exception $ex) {
            self::assertStringContainsString(
                'The temporary manager expiry date can not be in the past.',
                $ex->getMessage()
            );
        }

        // Now without any issues..
        $this->resolve_graphql_mutation(
            $this->mutation,
            [
                'target_job' => [
                    'id' => $job_assignment->id,
                ],
                'input' => [
                    'temp_manager' => [
                        'idnumber' => 'temp',
                    ],
                    'temp_manager_expiry_date' => time() + DAYSECS,
                ]
            ]
        );

        $job = job_assignment::get_with_id($job_assignment->id);
        self::assertInstanceOf(job_assignment::class, $job);
        self::assertSame($user->id, $job->userid);
        self::assertSame('jj3j3', $job->idnumber);
        self::assertSame($temp_manager->id, $job->tempmanagerjaid);
        self::assertSame($temp_manager_user->id, $job->tempmanagerid);
        set_config('enabletempmanagers', $prev_config_value);
    }

    public function test_resolve_position() {
        $this->setAdminUser();
        $user = $this->getDataGenerator()->create_user();
        $job_assignment = job_assignment::create(['userid' => $user->id, 'idnumber' => 'ja112']);

        //Reset position assignment date
        global $DB;
        $DB->update_record('job_assignment', ['positionassignmentdate' => 0, 'id' => $job_assignment->id]);
        $job_assignment = job_assignment::get_with_id($job_assignment->id);

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
                    'target_job' => [
                        'id' => $job_assignment->id,
                    ],
                    'input' => [
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
                'target_job' => [
                    'id' => $job_assignment->id,
                ],
                'input' => [
                    'position' => [
                        'id' => $position1->id,
                    ]
                ],
            ]
        );

        $job = job_assignment::get_with_id($job_assignment->id);
        self::assertInstanceOf(job_assignment::class, $job);
        self::assertSame($user->id, $job->userid);
        self::assertSame($position1->id, $job->positionid);
        self::assertLessThan(60, abs($job->positionassignmentdate - time()));

        // Same position
        $previous_positionassignmentdate = $job->positionassignmentdate;
        $this->resolve_graphql_mutation(
            $this->mutation,
            [
                'target_job' => [
                    'id' => $job_assignment->id,
                ],
                'input' => [
                    'position' => [
                        'id' => $job->positionid,
                    ]
                ],
            ]
        );
        $job = job_assignment::get_with_id($job_assignment->id);
        self::assertSame($previous_positionassignmentdate, $job->positionassignmentdate);
    }

    public function test_resolve_organisation() {
        $this->setAdminUser();
        $user = $this->getDataGenerator()->create_user();
        $job_assignment = job_assignment::create(['userid' => $user->id, 'idnumber' => 'ja873']);

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
                    'target_job' => [
                        'id' => $job_assignment->id,
                    ],
                    'input' => [
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
                'target_job' => [
                    'id' => $job_assignment->id,
                ],
                'input' => [
                    'organisation' => [
                        'idnumber' => $organisation1->idnumber,
                    ]
                ],
            ]
        );
        $job = job_assignment::get_with_id($job_assignment->id);
        self::assertInstanceOf(job_assignment::class, $job);
        self::assertSame($user->id, $job->userid);
        self::assertSame($organisation1->id, $job->organisationid);
    }

    public function test_resolve_appraiser() {
        $this->setAdminUser();
        $user = $this->getDataGenerator()->create_user();
        $job_assignment = job_assignment::create(['userid' => $user->id, 'idnumber' => 'ja8331']);
        $appraiser = $this->getDataGenerator()->create_user();

        // Non existent appraiser.
        try {
            $this->resolve_graphql_mutation(
                $this->mutation,
                [
                    'target_job' => [
                        'id' => $job_assignment->id,
                    ],
                    'input' => [
                        'appraiser' => [
                            'idnumber' => '1lklkl2',
                        ]
                    ]
                ]
            );
            $this->fail('Exception expected.');
        } catch (\moodle_exception $ex) {
            self::assertStringContainsString('Appraiser reference not found', $ex->getMessage());
        }

        // Guest user
        $guest_user = guest_user();
        try {
            $this->resolve_graphql_mutation(
                $this->mutation,
                [
                    'target_job' => [
                        'id' => $job_assignment->id,
                    ],
                    'input' => [
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
                'target_job' => [
                    'idnumber' => 'ja8331',
                ],
                'input' => [
                    'appraiser' => [
                        'id' => $appraiser->id,
                    ]
                ]
            ]
        );
        $job = job_assignment::get_with_id($job_assignment->id);
        self::assertInstanceOf(job_assignment::class, $job);
        self::assertSame($user->id, $job->userid);
        self::assertSame($appraiser->id, $job->appraiserid);
    }

    public function test_resolve_start_end_dates() {
        $this->setAdminUser();
        $user = $this->getDataGenerator()->create_user();
        $job_assignment = job_assignment::create(['userid' => $user->id, 'idnumber' => 'jaiii2']);

        // Use start date greater than end date.
        $start = time() + DAYSECS;
        $end = time() - DAYSECS;
        try {
            $this->resolve_graphql_mutation(
                $this->mutation,
                [
                    'target_job' => [
                        'id' => $job_assignment->id,
                    ],
                    'input' => [
                        'start_date' => $start,
                        'end_date' => $end
                    ]
                ]
            );
            $this->fail('Exception expected.');
        } catch (job_assignment_update_exception $ex) {
            self::assertStringContainsString('The start date can not be later than the end date.', $ex->getMessage());
        }

        // Correct dates
        $start = time() - DAYSECS;
        $end = time() + DAYSECS;
        $this->resolve_graphql_mutation(
            $this->mutation,
            [
                'target_job' => [
                    'id' => $job_assignment->id,
                ],
                'input' => [
                    'start_date' => $start,
                    'end_date' => $end,
                ]
            ]
        );

        $job = job_assignment::get_with_id($job_assignment->id);
        self::assertInstanceOf(job_assignment::class, $job);
        self::assertSame($user->id, $job->userid);
        self::assertSame($start, (int)$job->startdate);
        self::assertSame($end, (int)$job->enddate);
    }

    /**
     * @return void
     */
    public function test_update_job_assignment_for_non_unique_idnumber(): void {
        $this->setAdminUser();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $shared_idnumber = '123imnotunique';
        $modified_fullname = 'I am being modified';

        $job_assignment1 = job_assignment::create(['userid' => $user1->id, 'idnumber' => $shared_idnumber]);
        $job_assignment2 = job_assignment::create(['userid' => $user2->id, 'idnumber' => $shared_idnumber]);

        $request_args = [
            'target_job' => [
                'idnumber' => $shared_idnumber
            ],
            'input' => [
                'fullname' => $modified_fullname
            ]
        ];

        // Expect this to fail - 2 job assignments have the same 'idnumber' value, so multiple records will get returned.
        try {
            $this->resolve_graphql_mutation(
                $this->mutation,
                $request_args
            );
        } catch (job_assignment_update_exception $exc) {
            $this->assertEquals('There was a problem finding a single job assignment record match or you do not have permission to manage it.', $exc->getMessage());
        }

        // Expect this to succeed - an additional 'userid' filter is applied so it will find just one record.
        $request_args['target_job']['user'] = [ 'id' => $user1->id];
        $result = $this->resolve_graphql_mutation(
            $this->mutation,
            $request_args
        );
        $this->assertEquals($user1->id, $result['job_assignment']->userid);
        $this->assertEquals($modified_fullname, $result['job_assignment']->fullname);
    }

    /**
     * @return void
     */
    public function test_update_job_assignment_with_config_setting_for_shortname(): void {
        global $CFG;
        $original_config = $CFG->showhierarchyshortnames;

        // Set up.
        $this->setAdminUser();
        $user1 = $this->getDataGenerator()->create_user();
        $test_ja_idnumber = uniqid();
        $job_assignment1 = job_assignment::create(['userid' => $user1->id, 'idnumber' => $test_ja_idnumber,
            'shortname' => 'original shortname'
        ]);

        $new_shortname1 = 'new shortname1';
        $new_shortname2 = 'new shortname2';
        $request_args = [
            'target_job' => [
                'idnumber' => $test_ja_idnumber
            ],
            'input' => [
                'shortname' => $new_shortname1
            ]
        ];

        // Operate - test with config enabled. Shortname value should update & show value in response.
        set_config('showhierarchyshortnames', '1');
        $result = $this->resolve_graphql_mutation(
            $this->mutation,
            $request_args
        );
        $this->assertEquals($new_shortname1, $result['job_assignment']->shortname);

        // Operate - test with config disabled.  Request should succeed & shortname value should still show value in response
        // to match the UI behaviour.
        set_config('showhierarchyshortnames', '0');
        $request_args['input']['shortname'] = $new_shortname2;
        $result = $this->resolve_graphql_mutation(
            $this->mutation,
            $request_args
        );
        $this->assertEquals($new_shortname2, $result['job_assignment']->shortname);

        // Tear down.
        set_config('showhierarchyshortnames', $original_config);
    }

    /**
     * @return void
     */
    public function test_update_job_assignment_for_a_tenant_participant_manager(): void {
        // Set up.
        global $DB, $CFG;
        // Create a test tenant.
        $tenant_generator = $this->getDataGenerator()->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();
        $tenant1 = $tenant_generator->create_tenant();
        $tenant_context =  context_tenant::instance($tenant1->id);

        // Create a tenant api client user & give capabilities.
        $api_user = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $test_api_user = self::getDataGenerator()->create_user([
            'username' => 'user' . uniqid(),
            'tenantid' => $tenant1->id
        ]);
        $tbl_prefix = $CFG->prefix;
        $api_user_roles = $DB->get_records_sql(
            "select * from {$tbl_prefix}role where archetype in ('apiuser', 'tenantusermanager', 'tenantdomainmanager')",
            []
        );
        foreach ($api_user_roles as $api_user_role) {
            role_assign($api_user_role->id, $test_api_user->id, $tenant_context);
        }

        $role_id = $this->getDataGenerator()->create_role();
        assign_capability(
            'totara/hierarchy:assignuserposition',
            CAP_ALLOW,
            $role_id,
            $tenant_context
        );
        assign_capability('moodle/user:viewalldetails', CAP_ALLOW, $role_id, $tenant_context);
        role_assign($role_id, $api_user->id, $tenant_context);

        $this->setUser($api_user);

        // Create a system user then make him a tenant participant.
        $tenant_partipant_user = $this->getDataGenerator()->create_user();
        tenant_util::add_other_participant($tenant1->id, $tenant_partipant_user->id);
        // Give the tenant participant a job_assignment.
        $ja_idnumber_participant = 'SalesManagerJA' . uniqid();
        $participant_job_assignment = job_assignment::create(['userid' => $tenant_partipant_user->id,
            'idnumber' => $ja_idnumber_participant
        ]);

        $tenant_member_user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $tenant_member_user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);

        // Create a job assignment for a tenant member manager.
        $ja1_idnumber = 'SalesManagerJA' . uniqid();
        $job_assignment1 = job_assignment::create(['userid' => $tenant_member_user1->id, 'idnumber' => $ja1_idnumber]);

        // Create a job assignment, setting a tenant member manager.
        $ja2_idnumber = 'SalesAssistantJA' . uniqid();
        $job_assignment2 = job_assignment::create(['userid' => $tenant_member_user2->id, 'idnumber' => $ja2_idnumber,
            'managerjaid' => $job_assignment1->id
        ]);

        // Operate.
        // Try to update the job_assignment, updating the manager's job assignment to $participant_job_assignment.
        $updated_name_val = $job_assignment2->fullname . 'b';
        $response = $this->resolve_graphql_mutation(
            $this->mutation,
            [
                'target_job' => [
                    'idnumber' => $ja2_idnumber
                ],
                'input' => [
                    'fullname' => $updated_name_val,
                    'manager' => [
                        'id' => $participant_job_assignment->id
                    ]
                ]
            ]
        );

        // Assert.
        $this->assertEquals($ja2_idnumber, $response['job_assignment']->idnumber);
        $this->assertEquals($updated_name_val, $response['job_assignment']->fullname);
        $this->assertEquals($participant_job_assignment->id, $response['job_assignment']->managerjaid);
    }
}
