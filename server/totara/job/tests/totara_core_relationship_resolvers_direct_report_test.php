<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package totara_job
 */

require_once('totara_core_relationship_resolvers_helper.php');

use core\entity\tenant;
use core_phpunit\testcase;
use totara_core\relationship\relationship;
use totara_core\relationship\relationship_resolver_dto;
use totara_job\job_assignment;
use totara_job\relationship\resolvers\direct_report;

/**
 * @group totara_core_relationship
 * @covers \totara_job\relationship\resolvers\direct_report
 */
class totara_job_totara_core_relationship_resolvers_direct_report_testcase extends testcase {

    use totara_core_relationship_resolvers_helper;

    private $job_assignments;
    private $direct_report_resolver;

    /**
     * Helper function for creating job assignments
     *
     * @return array
     * @throws coding_exception
     */
    private function create_job_assignments(): array {
        $users = $this->users;
        $job_assignments['manager_ja'] = job_assignment::create_default($users['manager']->id, ['idnumber' => 'manager_ja_1']);
        $job_assignments['direct_report1_ja'] = job_assignment::create_default($users['direct_report1']->id, [
            'idnumber' => 'direct_report1_ja_1',
            'managerjaid' => $job_assignments['manager_ja']->id
        ]);
        $job_assignments['direct_report2_ja']  = job_assignment::create_default($users['direct_report2']->id, [
            'idnumber' => 'direct_report2_ja_1',
            'managerjaid' => $job_assignments['manager_ja']->id
        ]);
        $job_assignments['direct_report3_ja']  = job_assignment::create_default($users['direct_report3']->id, [
            'idnumber' => 'direct_report3_ja_1',
            'managerjaid' => $job_assignments['direct_report2_ja']->id,
            'tempmanagerjaid' => $job_assignments['manager_ja']->id,
            'tempmanagerexpirydate' => time() + WEEKSECS,
        ]);

        return $job_assignments;
    }

    protected function setUp(): void {
        self::setAdminUser();
        $this->generator = self::getDataGenerator();
        $this->users = $this->create_users(['manager', 'direct_report1', 'direct_report2', 'direct_report3']);
        $this->job_assignments = $this->create_job_assignments();
        $this->direct_report_resolver = new direct_report(relationship::load_by_idnumber('direct_report'));
    }

    protected function tearDown(): void {
        $this->reset_helper_properties();
        $this->job_assignments = null;
        $this->direct_report_resolver = null;
    }

    public function test_get_users_from_job_assignment_id(): void {
        extract($this->users);
        extract($this->job_assignments);
        $direct_report_resolver = $this->direct_report_resolver;

        // We should get both direct reports for manager.
        $relationship_resolver_dtos = $direct_report_resolver->get_users(
            ['job_assignment_id' => $manager_ja->id],
            context_user::instance($manager->id)
        );
        self::assertEquals(
            [$direct_report1->id, $direct_report2->id, $direct_report3->id],
            relationship_resolver_dto::get_user_ids($relationship_resolver_dtos)
        );

        // direct_report1 is not the manager of anyone.
        self::assertEquals(
            [],
            $direct_report_resolver->get_users(
                ['job_assignment_id' => $direct_report1_ja->id],
                context_user::instance($direct_report1->id)
            )
        );
    }

    public function test_get_users_from_user_id(): void {
        extract($this->users);
        extract($this->job_assignments);
        $direct_report_resolver = $this->direct_report_resolver;

        // We should get both direct reports for manager.
        $relationship_resolver_dtos = $direct_report_resolver->get_users(
            ['user_id' => $manager->id],
            context_user::instance($manager->id)
        );
        self::assertEquals(
            [$direct_report1->id, $direct_report2->id, $direct_report3->id],
            relationship_resolver_dto::get_user_ids($relationship_resolver_dtos)
        );

        // direct_report1 is not the manager of anyone.
        self::assertEquals(
            [],
            $direct_report_resolver->get_users(
                ['user_id' => $direct_report1->id],
                context_user::instance($direct_report1->id)
            )
        );
    }

    public function test_get_users_with_incorrect_attributes(): void {
        extract($this->users);
        $direct_report_resolver = $this->direct_report_resolver;

        $direct_report_resolver->get_users(
            ['job_assignment_id' => -1],
            context_user::instance($manager->id)
        );
        $direct_report_resolver->get_users(
            ['user_id' => -1],
            context_user::instance($manager->id)
        );
        $direct_report_resolver->get_users(
            ['job_assignment_id' => -1, 'user_id' => -1],
            context_user::instance($manager->id)
        );
        $direct_report_resolver->get_users(
            ['job_assignment_id' => -1, 'incorrect attribute' => -1],
            context_user::instance($manager->id)
        );
        $direct_report_resolver->get_users(
            ['user_id' => -1, 'incorrect attribute' => -1],
            context_user::instance($manager->id)
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('The fields inputted into the ' . direct_report::class . ' relationship resolver are invalid');

        $direct_report_resolver->get_users(
            ['incorrect attribute' => -1],
            context_user::instance($manager->id)
        );
    }

    public function test_get_users_with_no_attributes(): void {
        extract($this->users);
        $direct_report_resolver = $this->direct_report_resolver;

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('The fields inputted into the ' . direct_report::class . ' relationship resolver are invalid');

        $direct_report_resolver->get_users(
            [],
            context_user::instance($manager->id)
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

        $manager = self::getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $direct_report_same_tenant = self::getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $direct_report_different_tenants = self::getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $system_user = self::getDataGenerator()->create_user();

        $manager_ja = job_assignment::create_default($manager->id);

        job_assignment::create_default($direct_report_same_tenant->id, ['managerjaid' => $manager_ja->id]);
        job_assignment::create_default($direct_report_different_tenants->id, ['managerjaid' => $manager_ja->id]);

        $relationship = relationship::load_by_idnumber('direct_report');
        $manager_resolver = new direct_report($relationship);

        $users = $manager_resolver->get_users(
            ['user_id' => $manager->id],
            context_user::instance($manager->id)
        );

        $this->assertCount(1, $users);
        $dto = $users[0];
        self::assertEquals($direct_report_same_tenant->id, $dto->get_user_id());

        // If we pass the system context we should also get the direct report from the other tenant.
        $users = $manager_resolver->get_users(
            ['user_id' => $manager->id],
            context_system::instance()
        );
        $this->assertCount(2, $users);

        $actual_user_ids = array_map(static function (relationship_resolver_dto $dto) {
            return $dto->get_user_id();
        }, $users);
        self::assertEquals(
            [$direct_report_same_tenant->id, $direct_report_different_tenants->id],
            $actual_user_ids
        );
    }

}
