<?php
/*
 * This file is part of Totara Learn
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_job
 */

require_once('totara_core_relationship_resolvers_helper.php');

use core\entity\tenant;
use core_phpunit\testcase;
use totara_core\relationship\relationship;
use totara_core\relationship\relationship_resolver_dto;
use totara_job\job_assignment;
use totara_job\relationship\resolvers\managers_manager;

/**
 * @group totara_core_relationship
 * @covers \totara_job\relationship\resolvers\managers_manager
 */
class totara_job_totara_core_relationship_resolvers_managers_manager_test extends testcase {

    use totara_core_relationship_resolvers_helper;

    private $user_job_assignments;
    private $resolver;

    protected function setUp(): void {
        self::setAdminUser();
        $this->generator = self::getDataGenerator();
        $this->users = $this->create_users([
            'user1',
            'user2',
            'user3',
            'user4',
            'manager1',
            'manager2',
            'manager3',
            'manager4',
            'managersmanager1',
            'managersmanager2',
            'managersmanager3',
            'managersmanager4',
            'managersmanager5',
        ]);
        $this->user_job_assignments = $this->create_job_assignments();
        $this->resolver = new managers_manager(relationship::load_by_idnumber('managers_manager'));
    }

    protected function tearDown(): void {
        $this->reset_helper_properties();
        $this->user_job_assignments = null;
        $this->resolver = null;
    }

    /**
     * Helper function for creating job assignments
     *
     * @return array
     * @throws coding_exception
     */
    private function create_job_assignments(): array {
        $users = $this->users;
        $assignments = [];
        $assignments['managersmanager1_ja1'] = job_assignment::create_default($users['managersmanager1']->id, [
            'idnumber' => 'managers_manager1_ja_1'
        ]);
        $assignments['managersmanager1_ja2'] = job_assignment::create_default($users['managersmanager1']->id, [
            'idnumber' => 'managers_manager1_ja_2'
        ]);
        $assignments['managersmanager2_ja1'] = job_assignment::create_default($users['managersmanager2']->id, [
            'idnumber' => 'managers_manager2_ja_1'
        ]);
        $assignments['managersmanager3_ja1'] = job_assignment::create_default($users['managersmanager3']->id, [
            'idnumber' => 'managers_manager3_ja_1'
        ]);
        $assignments['managersmanager4_ja1'] = job_assignment::create_default($users['managersmanager4']->id, [
            'idnumber' => 'managers_manager4_ja_1'
        ]);
        $assignments['managersmanager4_ja2'] = job_assignment::create_default($users['managersmanager5']->id, [
            'idnumber' => 'managers_manager4_ja_2'
        ]);
        $assignments['manager1_ja1'] = job_assignment::create_default($users['manager1']->id, [
            'idnumber' => 'manager1_ja_1',
            'managerjaid' => $assignments['managersmanager1_ja1']->id
        ]);
        $assignments['manager1_ja2'] = job_assignment::create_default($users['manager1']->id, [
            'idnumber' => 'manager1_ja_2',
            'managerjaid' => $assignments['managersmanager2_ja1']->id,
        ]);
        $assignments['manager2_ja1'] = job_assignment::create_default($users['manager2']->id, [
            'idnumber' => 'manager2_ja_1',
            'managerjaid' => $assignments['managersmanager3_ja1']->id,
            'tempmanagerjaid' => $assignments['managersmanager2_ja1']->id,
            'tempmanagerexpirydate' => time() + WEEKSECS,
        ]);
        $assignments['manager3_ja1'] = job_assignment::create_default($users['manager3']->id, ['idnumber' => 'manager3_ja_1']);

        $assignments['manager4_ja1'] = job_assignment::create_default($users['manager4']->id, [
            'idnumber' => 'manager4_ja_1',
            'managerjaid' => $assignments['managersmanager4_ja1']->id
        ]);
        $assignments['manager4_ja2'] = job_assignment::create_default($users['manager4']->id, [
            'idnumber' => 'manager4_ja_2',
            'managerjaid' => $assignments['managersmanager4_ja1']->id,
            'tempmanagerjaid' => $assignments['managersmanager4_ja2']->id,
            'tempmanagerexpirydate' => time() + WEEKSECS,
        ]);
        $assignments['user1_ja1'] = job_assignment::create_default($users['user1']->id, [
            'idnumber' => 'user1_ja_1',
            'managerjaid' => $assignments['manager1_ja1']->id
        ]);
        $assignments['user1_ja2'] = job_assignment::create_default($users['user1']->id, [
            'idnumber' => 'user1_ja_2',
            'managerjaid' => $assignments['manager2_ja1']->id,
            'tempmanagerjaid' => $assignments['manager4_ja2']->id,
            'tempmanagerexpirydate' => time() + WEEKSECS,
        ]);
        $assignments['user2_ja1'] = job_assignment::create_default($users['user2']->id, ['idnumber' => 'user2_ja_1']);
        $assignments['user3_ja1'] = job_assignment::create_default($users['user3']->id, [
            'idnumber' => 'user3_ja_1',
            'managerjaid' => $assignments['manager3_ja1']->id
        ]);
        $assignments['user4_ja1'] = job_assignment::create_default($users['user4']->id, [
            'idnumber' => 'user4_ja_1',
            'managerjaid' => $assignments['manager4_ja1']->id
        ]);

        return $assignments;
    }

    public function test_get_users_from_job_assignment_id(): void {

        // Managersmanager1 is the manager's manager of user1 in ja1.
        $relationship_resolver_dtos = $this->resolver->get_users(
            ['job_assignment_id' => $this->user_job_assignments['user1_ja1']->id],
            context_user::instance($this->users['user1']->id)
        );
        $this->assertEquals(
            [$this->users['managersmanager1']->id],
            relationship_resolver_dto::get_user_ids($relationship_resolver_dtos)
        );

        // Managersmanager3 is the manager's manager of user1 in ja2.
        $relationship_resolver_dtos = $this->resolver->get_users(
            ['job_assignment_id' => $this->user_job_assignments['user1_ja2']->id],
            context_user::instance($this->users['user1']->id)
        );
        $this->assertEquals([
            $this->users['managersmanager2']->id,
            $this->users['managersmanager3']->id,
            $this->users['managersmanager4']->id,
            $this->users['managersmanager5']->id
        ],
            relationship_resolver_dto::get_user_ids($relationship_resolver_dtos)
        );

        // User2 is not managed by anyone (they have no manager).
        $this->assertEquals(
            [],
            $this->resolver->get_users(
                ['job_assignment_id' => $this->user_job_assignments['user2_ja1']->id],
                context_user::instance($this->users['user2']->id)
            )
        );

        // User3 is not manager's managed by anyone (they have a manager, but no manager's manager).
        $this->assertEquals(
            [],
            $this->resolver->get_users(
                ['job_assignment_id' => $this->user_job_assignments['user3_ja1']->id],
                context_user::instance($this->users['user3']->id)
            )
        );
    }

    public function test_get_users_from_user_id(): void {

        // User1 has two manager's managers.
        $relationship_resolver_dtos = $this->resolver->get_users(
            ['user_id' => $this->users['user1']->id],
            context_user::instance($this->users['user1']->id)
        );
        $this->assertEqualsCanonicalizing([
                $this->users['managersmanager1']->id,
                $this->users['managersmanager2']->id,
                $this->users['managersmanager3']->id,
                $this->users['managersmanager4']->id,
                $this->users['managersmanager5']->id
            ],
            relationship_resolver_dto::get_user_ids($relationship_resolver_dtos)
        );

        // User2 is not managed by anyone (they have no manager).
        $this->assertEquals(
            [],
            $this->resolver->get_users(
                ['user_id' => $this->users['user2']->id],
                context_user::instance($this->users['user2']->id)
            )
        );

        // User3 is not manager's managed by anyone (they have a manager, but no manager's manager).
        $this->assertEquals(
            [],
            $this->resolver->get_users(
                ['user_id' => $this->users['user3']->id],
                context_user::instance($this->users['user3']->id)
            )
        );
    }

    public function test_get_users_with_incorrect_attributes(): void {

        $this->resolver->get_users(
            ['job_assignment_id' => -1],
            context_user::instance($this->users['user1']->id)
        );
        $this->resolver->get_users(
            ['user_id' => -1],
            context_user::instance($this->users['user1']->id)
        );
        $this->resolver->get_users(
            ['job_assignment_id' => -1, 'user_id' => -1],
            context_user::instance($this->users['user1']->id)
        );
        $this->resolver->get_users(
            ['job_assignment_id' => -1, 'incorrect attribute' => -1],
            context_user::instance($this->users['user1']->id)
        );
        $this->resolver->get_users(
            ['user_id' => -1, 'incorrect attribute' => -1],
            context_user::instance($this->users['user1']->id)
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage(
            'The fields inputted into the ' . managers_manager::class . ' relationship resolver are invalid'
        );

        $this->resolver->get_users(
            ['incorrect attribute' => -1],
            context_user::instance($this->users['user1']->id)
        );
    }

    public function test_get_users_with_no_attributes(): void {

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage(
            'The fields inputted into the ' . managers_manager::class . ' relationship resolver are invalid'
        );

        $this->resolver->get_users(
            [],
            context_user::instance($this->users['user1']->id)
        );
    }

    public function test_get_users_with_multi_tenancy_enabled(): void {
        $generator = $this->generator;

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');

        $tenant_generator->enable_tenants();

        $tenant1 = $tenant_generator->create_tenant();
        $tenant2 = $tenant_generator->create_tenant();

        $tenant1 = new tenant($tenant1);
        $tenant2 = new tenant($tenant2);

        $user1 = $generator->create_user(['tenantid' => $tenant1->id]);
        $user2 = $generator->create_user(['tenantid' => $tenant1->id]);
        $user2_manager = $generator->create_user(['tenantid' => $tenant1->id]);
        $user3 = $generator->create_user(['tenantid' => $tenant2->id]);
        $user3_manager = $generator->create_user(['tenantid' => $tenant2->id]);
        $system_user = $generator->create_user();
        $system_user_manager = $generator->create_user();

        $user2managerja = job_assignment::create_default($user2_manager->id);
        $user3managerja = job_assignment::create_default($user3_manager->id);
        $system_user_manager_ja = job_assignment::create_default($system_user_manager->id);

        $user2ja = job_assignment::create_default($user2->id, ['managerjaid' => $user2managerja->id]);
        $user3ja = job_assignment::create_default($user3->id, ['managerjaid' => $user3managerja->id]);
        $system_user_ja = job_assignment::create_default($system_user->id, ['managerjaid' => $system_user_manager_ja->id]);

        job_assignment::create_default($user1->id, ['managerjaid' => $user2ja->id]);
        job_assignment::create_default($user1->id, ['managerjaid' => $user3ja->id]);

        $manager_resolver = $this->resolver;

        $users = $manager_resolver->get_users(
            ['user_id' => $user1->id],
            context_user::instance($user1->id)
        );

        $this->assertCount(1, $users);
        $dto = $users[0];
        $this->assertEquals($user2_manager->id, $dto->get_user_id());

        // If we pass the system context we should also get the manager from the other tenant
        $users = $manager_resolver->get_users(
            ['user_id' => $user1->id],
            context_system::instance()
        );

        $this->assertCount(2, $users);
        $actual_user_ids = [];
        foreach ($users as $user) {
            $actual_user_ids[] = $user->get_user_id();
        }
        $this->assertEqualsCanonicalizing(
            [$user2_manager->id, $user3_manager->id],
            $actual_user_ids
        );

        // Assign a manager who is in the system context
        job_assignment::create_default($user1->id, ['managerjaid' => $system_user_ja->id]);

        // We should still get only the ones in the same tenant if we pass a context which is in a tenant
        $users = $manager_resolver->get_users(
            ['user_id' => $user1->id],
            context_user::instance($user1->id)
        );

        $this->assertCount(1, $users);
        $dto = $users[0];
        $this->assertEquals($user2_manager->id, $dto->get_user_id());

        // If we pass the system context we should also get the system user
        $users = $manager_resolver->get_users(
            ['user_id' => $user1->id],
            context_system::instance()
        );

        $this->assertCount(3, $users);
        $actual_user_ids = [];
        foreach ($users as $user) {
            $actual_user_ids[] = $user->get_user_id();
        }
        $this->assertEqualsCanonicalizing(
            [$user2_manager->id, $user3_manager->id, $system_user_manager->id],
            $actual_user_ids
        );

        // Now with tenant isolation mode on
        set_config('tenantsisolated', 1);

        // If checked in system context we should only get NON-tenant users
        $users = $manager_resolver->get_users(
            ['user_id' => $user1->id],
            context_system::instance()
        );

        $this->assertCount(1, $users);
        $actual_user_ids = [];
        foreach ($users as $user) {
            $actual_user_ids[] = $user->get_user_id();
        }
        $this->assertEqualsCanonicalizing(
            [$system_user_manager->id],
            $actual_user_ids
        );

        // If inside a tenant we should get only tenant users
        $users = $manager_resolver->get_users(
            ['user_id' => $user1->id],
            context_user::instance($user1->id)
        );

        $this->assertCount(1, $users);
        $actual_user_ids = [];
        foreach ($users as $user) {
            $actual_user_ids[] = $user->get_user_id();
        }
        $this->assertEqualsCanonicalizing(
            [$user2_manager->id],
            $actual_user_ids
        );
    }
}
