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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_job
 */

use core\entity\tenant;
use totara_core\relationship\relationship;
use totara_core\relationship\relationship_resolver_dto;
use totara_job\job_assignment;
use totara_job\relationship\resolvers\appraiser;

/**
 * @group totara_core_relationship
 * @covers \totara_job\relationship\resolvers\appraiser
 */
class totara_job_totara_core_relationship_resolvers_appraiser_testcase extends \advanced_testcase {

    private function create_data(): array {
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $user2ja = job_assignment::create_default($user2->id);
        $user3ja = job_assignment::create_default($user3->id);

        $user1ja1 = job_assignment::create_default($user1->id, ['appraiserid' => $user2->id]);
        $user1ja2 = job_assignment::create_default($user1->id, ['appraiserid' => $user3->id]);

        $relationship = relationship::load_by_idnumber('appraiser');
        $appraiser_resolver = new appraiser($relationship);

        return [$user1, $user2, $user3, $user1ja1, $user1ja2, $user2ja, $user3ja, $appraiser_resolver];
    }

    public function test_get_users_from_job_assignment_id(): void {
        [$user1, $user2, $user3, $user1ja1, $user1ja2, $user2ja, $user3ja, $appraiser_resolver] = $this->create_data();

        // user2 is the appraiser of user1 in ja1
        $relationship_resolver_dtos = $appraiser_resolver->get_users(
            ['job_assignment_id' => $user1ja1->id],
            context_user::instance($user1->id)
        );
        $this->assertEquals(
            [$user2->id],
            relationship_resolver_dto::get_user_ids($relationship_resolver_dtos)
        );

        // user3 is the appraiser of user1 in ja2
        $relationship_resolver_dtos = $appraiser_resolver->get_users(
            ['job_assignment_id' => $user1ja2->id],
            context_user::instance($user1->id)
        );
        $this->assertEquals(
            [$user3->id],
            relationship_resolver_dto::get_user_ids($relationship_resolver_dtos)
        );

        // user2 is not appraised by anyone
        $this->assertEquals(
            [],
            $appraiser_resolver->get_users(
                ['job_assignment_id' => $user2ja->id],
                context_user::instance($user2->id)
            )
        );

        // user3 is not appraised by anyone
        $this->assertEquals(
            [],
            $appraiser_resolver->get_users(
                ['job_assignment_id' => $user3ja->id],
                context_user::instance($user3->id)
            )
        );
    }

    public function test_get_users_from_user_id(): void {
        [$user1, $user2, $user3, $user1ja1, $user1ja2, $user2ja, $user3ja, $appraiser_resolver] = $this->create_data();

        // user2 and user3 are the appraisers of user1
        $relationship_resolver_dtos = $appraiser_resolver->get_users(
            ['user_id' => $user1->id],
            context_user::instance($user1->id)
        );
        $this->assertEqualsCanonicalizing(
            [$user2->id, $user3->id],
            relationship_resolver_dto::get_user_ids($relationship_resolver_dtos)
        );

        // user2 is not appraised by anyone
        $this->assertEquals(
            [],
            $appraiser_resolver->get_users(
                ['user_id' => $user2->id],
                context_user::instance($user2->id)
            )
        );

        // user3 is not appraised by anyone
        $this->assertEquals(
            [],
            $appraiser_resolver->get_users(
                ['user_id' => $user2->id],
                context_user::instance($user2->id))
        );
    }

    public function test_get_users_with_incorrect_attributes(): void {
        [$user1, $user2, $user3, $user1ja1, $user1ja2, $user2ja, $user3ja, $appraiser_resolver] = $this->create_data();

        $appraiser_resolver->get_users(
            ['job_assignment_id' => -1],
            context_user::instance($user1->id)
        );
        $appraiser_resolver->get_users(
            ['user_id' => -1],
            context_user::instance($user1->id));
        $appraiser_resolver->get_users(
            ['job_assignment_id' => -1, 'user_id' => -1],
            context_user::instance($user1->id));
        $appraiser_resolver->get_users(
            ['job_assignment_id' => -1, 'incorrect attribute' => -1],
            context_user::instance($user1->id));
        $appraiser_resolver->get_users(
            ['user_id' => -1, 'incorrect attribute' => -1],
            context_user::instance($user1->id));

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('The fields inputted into the ' . appraiser::class . ' relationship resolver are invalid');

        $appraiser_resolver->get_users(
            ['incorrect attribute' => -1],
            context_user::instance($user1->id)
        );
    }

    public function test_get_users_with_no_attributes(): void {
        [$user1, $user2, $user3, $user1ja1, $user1ja2, $user2ja, $user3ja, $appraiser_resolver] = $this->create_data();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('The fields inputted into the ' . appraiser::class . ' relationship resolver are invalid');

        $appraiser_resolver->get_users(
            [],
            context_user::instance($user1->id)
        );
    }

    public function test_get_users_with_multi_tenancy_enabled(): void {
        $generator = $this->getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');

        $tenant_generator->enable_tenants();

        $tenant1 = $tenant_generator->create_tenant();
        $tenant2 = $tenant_generator->create_tenant();

        $tenant1 = new tenant($tenant1);
        $tenant2 = new tenant($tenant2);

        $user1 = self::getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user2 = self::getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user3 = self::getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $system_user = self::getDataGenerator()->create_user();

        job_assignment::create_default($user1->id, ['appraiserid' => $user2->id]);
        job_assignment::create_default($user1->id, ['appraiserid' => $user3->id]);

        $relationship = relationship::load_by_idnumber('appraiser');
        $appraiser_resolver = new appraiser($relationship);

        $users = $appraiser_resolver->get_users(
            ['user_id' => $user1->id],
            context_user::instance($user1->id)
        );

        $this->assertCount(1, $users);
        $dto = $users[0];
        $this->assertEquals($user2->id, $dto->get_user_id());

        // If we pass the system context we should also get the appraiser from the other tenant
        $users = $appraiser_resolver->get_users(
            ['user_id' => $user1->id],
            context_system::instance()
        );

        $this->assertCount(2, $users);
        $actual_user_ids = [];
        foreach ($users as $user) {
            $actual_user_ids[] = $user->get_user_id();
        }
        $this->assertEqualsCanonicalizing(
            [$user2->id, $user3->id],
            $actual_user_ids
        );

        // Assign an appraiser who is in the system context
        job_assignment::create_default($user1->id, ['appraiserid' => $system_user->id]);

        // We should still get only the ones in the same tenant if we pass a context which is in a tenant
        $users = $appraiser_resolver->get_users(
            ['user_id' => $user1->id],
            context_user::instance($user1->id)
        );

        $this->assertCount(1, $users);
        $dto = $users[0];
        $this->assertEquals($user2->id, $dto->get_user_id());

        // If we pass the system context we should also get the system user
        $users = $appraiser_resolver->get_users(
            ['user_id' => $user1->id],
            context_system::instance()
        );

        $this->assertCount(3, $users);
        $actual_user_ids = [];
        foreach ($users as $user) {
            $actual_user_ids[] = $user->get_user_id();
        }
        $this->assertEqualsCanonicalizing(
            [$user2->id, $user3->id, $system_user->id],
            $actual_user_ids
        );

        // Now with tenant isolation mode on
        set_config('tenantsisolated', 1);

        // If checked in system context we should only get NON-tenant users
        $users = $appraiser_resolver->get_users(
            ['user_id' => $user1->id],
            context_system::instance()
        );

        $this->assertCount(1, $users);
        $actual_user_ids = [];
        foreach ($users as $user) {
            $actual_user_ids[] = $user->get_user_id();
        }
        $this->assertEqualsCanonicalizing(
            [$system_user->id],
            $actual_user_ids
        );

        // If inside a tenant we should get only tenant users
        $users = $appraiser_resolver->get_users(
            ['user_id' => $user1->id],
            context_user::instance($user1->id)
        );

        $this->assertCount(1, $users);
        $actual_user_ids = [];
        foreach ($users as $user) {
            $actual_user_ids[] = $user->get_user_id();
        }
        $this->assertEqualsCanonicalizing(
            [$user2->id],
            $actual_user_ids
        );
    }

}
