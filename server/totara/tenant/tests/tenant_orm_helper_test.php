<?php
/**
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_tenant
 */

use core\entity\tenant;
use core\entity\user;
use core\orm\query\builder;
use core\orm\query\field;
use core\tenant_orm_helper;

defined('MOODLE_INTERNAL') || die();

/**
 * This test covers the orm helper which can be used to apply tenant restrictions to orm queries
 */
class tenant_orm_helper_test extends advanced_testcase {

    public function test_multi_tenancy_disabled() {
        $generator = $this->getDataGenerator();

        $generator->create_user();

        $course = $generator->create_course();

        $generator->create_user();
        $generator->create_user();
        $generator->create_user();
        $generator->create_user();

        // All users should be loaded
        $builder = builder::table('user')
            ->where_not_in('username', ['guest', 'admin']);
        tenant_orm_helper::restrict_users($builder, new field('id', $builder), context_system::instance());

        $result = $builder->get();
        $this->assertEquals(5, $result->count());

        // Use an alias
        $builder = builder::table('user')
            ->as('u')
            ->where_not_in('username', ['guest', 'admin']);
        tenant_orm_helper::restrict_users($builder, 'u.id', context_system::instance());

        $result = $builder->get();
        $this->assertEquals(5, $result->count());

        // Pass a course context
        $course_context = context_course::instance($course->id);
        $builder = builder::table('user')
            ->where_not_in('username', ['guest', 'admin']);
        tenant_orm_helper::restrict_users($builder, new field('id', $builder), $course_context);

        $result = $builder->get();
        $this->assertEquals(5, $result->count());
    }

    public function test_multi_tenancy_disabled_with_repository() {
        $generator = $this->getDataGenerator();

        $generator->create_user();

        $course = $generator->create_course();

        $generator->create_user();
        $generator->create_user();
        $generator->create_user();
        $generator->create_user();

        // All users should be loaded
        $repo = user::repository()
            ->where_not_in('username', ['guest', 'admin']);
        tenant_orm_helper::restrict_users($repo, new field('id', $repo->get_builder()), context_system::instance());

        $result = $repo->get();
        $this->assertEquals(5, $result->count());

        $builder = builder::table('user')
            ->as('u')
            ->where_not_in('username', ['guest', 'admin']);
        tenant_orm_helper::restrict_users($builder, 'u.id', context_system::instance());

        $result = $builder->get();
        $this->assertEquals(5, $result->count());

        // Pass a course context
        $course_context = context_course::instance($course->id);
        $repo = user::repository()
            ->where_not_in('username', ['guest', 'admin']);
        tenant_orm_helper::restrict_users($repo, new field('id', $repo->get_builder()), $course_context);

        $result = $repo->get();
        $this->assertEquals(5, $result->count());
    }

    public function test_multi_tenancy_enabled() {
        $generator = $this->getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');

        $tenant_generator->enable_tenants();

        $tenant1 = $tenant_generator->create_tenant();
        $tenant2 = $tenant_generator->create_tenant();

        $tenant1 = new tenant($tenant1);
        $tenant2 = new tenant($tenant2);

        $system_category = $generator->create_category();

        $system_course = $generator->create_course([
            'category' => $system_category->id
        ]);

        $tenant1_category = builder::table('course_categories')->find($tenant1->categoryid);
        $tenant2_category = builder::table('course_categories')->find($tenant2->categoryid);

        $system_user = $generator->create_user();

        $course1 = $generator->create_course([
            'category' => $tenant1_category->id
        ]);

        $course2 = $generator->create_course([
            'category' => $tenant2_category->id
        ]);

        $user11 = $generator->create_user(['tenantid' => $tenant1->id]);
        $user12 = $generator->create_user(['tenantid' => $tenant1->id]);
        $user21 = $generator->create_user(['tenantid' => $tenant2->id]);
        $user22 = $generator->create_user(['tenantid' => $tenant2->id]);

        // Given the system context all users should be loaded as isolation is off
        $builder = builder::table('user')
            ->where_not_in('username', ['guest', 'admin']);
        tenant_orm_helper::restrict_users($builder, new field('id', $builder), context_system::instance());

        $result = $builder->get();
        $this->assertEquals(5, $result->count());

        // Now lets pass a tenant course context
        $builder = builder::table('user')
            ->where_not_in('username', ['guest', 'admin']);
        tenant_orm_helper::restrict_users($builder, new field('id', $builder), context_course::instance($course1->id));

        // We should get only the users from the same tenant as the course
        $result = $builder->get();
        $this->assertEquals(2, $result->count());
        $this->assertEqualsCanonicalizing(
            [$user11->id, $user12->id],
            $result->pluck('id')
        );

        // Just to verify try the other one
        $builder = builder::table('user')
            ->where_not_in('username', ['guest', 'admin']);
        tenant_orm_helper::restrict_users($builder, new field('id', $builder), context_course::instance($course2->id));

        // We should get only the users from the same tenant as the course
        $result = $builder->get();
        $this->assertEquals(2, $result->count());
        $this->assertEqualsCanonicalizing(
            [$user21->id, $user22->id],
            $result->pluck('id')
        );

        // Now lets pass a user context from a user who is in a tenant
        $builder = builder::table('user')
            ->where_not_in('username', ['guest', 'admin']);
        tenant_orm_helper::restrict_users($builder, new field('id', $builder), context_user::instance($user21->id));

        // We should get only the users from the same tenant as the other user
        $result = $builder->get();
        $this->assertEquals(2, $result->count());
        $this->assertEqualsCanonicalizing(
            [$user21->id, $user22->id],
            $result->pluck('id')
        );

        // Now lets pass a user context from the system user
        $builder = builder::table('user')
            ->where_not_in('username', ['guest', 'admin']);
        tenant_orm_helper::restrict_users($builder, new field('id', $builder), context_user::instance($system_user->id));

        // We should get all users as tenant isolation is off
        $result = $builder->get();
        $this->assertEquals(5, $result->count());
        $this->assertEqualsCanonicalizing(
            [$system_user->id, $user11->id, $user12->id, $user21->id, $user22->id],
            $result->pluck('id')
        );

        // Now lets pass the system course context
        $builder = builder::table('user')
            ->where_not_in('username', ['guest', 'admin']);
        tenant_orm_helper::restrict_users($builder, new field('id', $builder), context_course::instance($system_course->id));

        // We should get only the users from the same tenant as the course
        $result = $builder->get();
        $this->assertEquals(5, $result->count());
        $this->assertEqualsCanonicalizing(
            [$system_user->id, $user11->id, $user12->id, $user21->id, $user22->id],
            $result->pluck('id')
        );

        // Now enable isolation
        set_config('tenantsisolated', 1);

        // Given the system context only non-tenant users should be loaded
        $builder = builder::table('user')
            ->where_not_in('username', ['guest', 'admin']);
        tenant_orm_helper::restrict_users($builder, new field('id', $builder), context_system::instance());

        $result = $builder->get();
        $this->assertEquals(1, $result->count());
        $this->assertEqualsCanonicalizing([$system_user->id], $result->pluck('id'));

        // Now lets pass a tenant course context
        $builder = builder::table('user')
            ->where_not_in('username', ['guest', 'admin']);
        tenant_orm_helper::restrict_users($builder, new field('id', $builder), context_course::instance($course1->id));

        // We should get only the users from the same tenant as the course
        $result = $builder->get();
        $this->assertEquals(2, $result->count());
        $this->assertEqualsCanonicalizing(
            [$user11->id, $user12->id],
            $result->pluck('id')
        );

        // Just to verify try the other one
        $builder = builder::table('user')
            ->where_not_in('username', ['guest', 'admin']);
        tenant_orm_helper::restrict_users($builder, new field('id', $builder), context_course::instance($course2->id));

        // We should get only the users from the same tenant as the course
        $result = $builder->get();
        $this->assertEquals(2, $result->count());
        $this->assertEqualsCanonicalizing(
            [$user21->id, $user22->id],
            $result->pluck('id')
        );

        // Now lets pass a user context from a user who is in a tenant
        $builder = builder::table('user')
            ->where_not_in('username', ['guest', 'admin']);
        tenant_orm_helper::restrict_users($builder, new field('id', $builder), context_user::instance($user21->id));

        // We should get only the users from the same tenant as the other user
        $result = $builder->get();
        $this->assertEquals(2, $result->count());
        $this->assertEqualsCanonicalizing(
            [$user21->id, $user22->id],
            $result->pluck('id')
        );

        // Now lets pass a user context from the system user
        $builder = builder::table('user')
            ->where_not_in('username', ['guest', 'admin']);
        tenant_orm_helper::restrict_users($builder, new field('id', $builder), context_user::instance($system_user->id));

        // We should only get the non-tenant users
        $result = $builder->get();
        $this->assertEquals(1, $result->count());
        $this->assertEqualsCanonicalizing([$system_user->id], $result->pluck('id'));

        // Now lets pass the system course context
        $builder = builder::table('user')
            ->where_not_in('username', ['guest', 'admin']);
        tenant_orm_helper::restrict_users($builder, new field('id', $builder), context_course::instance($system_course->id));

        // We should only get the non-tenant users
        $result = $builder->get();
        $this->assertEquals(1, $result->count());
        $this->assertEqualsCanonicalizing([$system_user->id], $result->pluck('id'));
    }

}