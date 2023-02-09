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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_perform
 */

use core\entity\tenant;
use core\entity\user;
use core\orm\collection;
use mod_perform\data_providers\activity\selectable_users;
use mod_perform\entity\activity\subject_instance;
use mod_perform\entity\activity\track;
use mod_perform\entity\activity\track_user_assignment;
use mod_perform\models\activity\activity as activity_model;
use mod_perform\models\activity\subject_instance as subject_instance_model;
use mod_perform\util;

/**
 * @group perform
 */
class mod_perform_data_provider_selectable_users_testcase extends advanced_testcase {

    public function test_get_users() {
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = $generator->get_plugin_generator('mod_perform');

        $user1 = $generator->create_user(['firstname' => 'bvcxz', 'lastname' => 'Qwertz']);
        $user2 = $generator->create_user(['firstname' => 'asdfgh', 'lastname' => 'Qwertz']);
        $deleted_user = $generator->create_user(['deleted' => 1]);
        $suspended_user = $generator->create_user(['suspended' => 1]);
        $guest_user = guest_user();

        $configuration = \mod_perform\testing\activity_generator_configuration::new()
            ->set_cohort_assignments_per_activity(1)
            ->set_number_of_users_per_user_group_type(5);

        $activities1 = $perform_generator->create_full_activities($configuration);
        /** @var activity_model $activity1 */
        $activity1 = $activities1->first();

        $configuration2 = \mod_perform\testing\activity_generator_configuration::new()
            ->set_cohort_assignments_per_activity(1)
            ->set_number_of_users_per_user_group_type(5);

        $activities2 = $perform_generator->create_full_activities($configuration2);
        /** @var activity_model $activity2 */
        $activity2 = $activities2->first();

        $subject_instance1 = $this->get_subject_instance($activity1);

        $provider = new selectable_users($subject_instance1);
        $selectable_users = $provider->get();

        // The result should contain all users (except the guest, deleted and suspended)
        $this->assertCount(13, $selectable_users);
        $actual_user_ids = $selectable_users->pluck('id');

        $this->assertContainsEquals($user1->id, $actual_user_ids);
        $this->assertContainsEquals($user2->id, $actual_user_ids);
        $this->assertContainsEquals(get_admin()->id, $actual_user_ids);
        $this->assertNotContainsEquals($guest_user->id, $actual_user_ids);
        $this->assertNotContainsEquals($deleted_user->id, $actual_user_ids);
        $this->assertNotContainsEquals($suspended_user->id, $actual_user_ids);

        $subject_instance2 = $this->get_subject_instance($activity2);

        $provider = new selectable_users($subject_instance2);
        $selectable_users = $provider->get();

        $this->assertCount(13, $selectable_users);
        $actual_user_ids = $selectable_users->pluck('id');

        $this->assertContainsEquals($user1->id, $actual_user_ids);
        $this->assertContainsEquals($user2->id, $actual_user_ids);
        $this->assertContainsEquals(get_admin()->id, $actual_user_ids);
        $this->assertNotContainsEquals($guest_user->id, $actual_user_ids);
        $this->assertNotContainsEquals($deleted_user->id, $actual_user_ids);
        $this->assertNotContainsEquals($suspended_user->id, $actual_user_ids);

        // Now filter the data
        $provider = new selectable_users($subject_instance1);
        $selectable_users = $provider
            ->add_filters(['exclude_users' => [get_admin()->id, $user1->id]])
            ->get();

        $this->assertCount(11, $selectable_users);
        $actual_user_ids = $selectable_users->pluck('id');
        $this->assertNotContainsEquals($user1->id, $actual_user_ids);
        $this->assertNotContainsEquals(get_admin()->id, $actual_user_ids);

        $provider = new selectable_users($subject_instance1);
        $selectable_users = $provider
            ->add_filters(['fullname' => 'Qwertz'])
            ->get();

        $this->assertCount(2, $selectable_users);
        $actual_user_ids = $selectable_users->pluck('id');
        // The result is ordered by fullname, so user2 should come before user1
        $this->assertEquals([$user2->id, $user1->id], $actual_user_ids);

        $provider = new selectable_users($subject_instance1);
        $selectable_users = $provider
            ->add_filters(['fullname' => 'asdfgh'])
            ->get();

        $this->assertCount(1, $selectable_users);
        $actual_user_ids = $selectable_users->pluck('id');
        // The result is ordered by fullname, so user2 should come before user1
        $this->assertContainsEquals($user2->id, $actual_user_ids);

        // Test combination of filters
        $provider = new selectable_users($subject_instance1);
        $selectable_users = $provider
            ->add_filters(['fullname' => 'Qwertz'])
            ->add_filters(['exclude_users' => [$user1->id]])
            ->get();

        $this->assertCount(1, $selectable_users);
        $actual_user_ids = $selectable_users->pluck('id');
        $this->assertEquals([$user2->id], $actual_user_ids);

        // Test filter returns empty result
        $provider = new selectable_users($subject_instance1);
        $selectable_users = $provider
            ->add_filters(['fullname' => 'idontexist'])
            ->get();

        $this->assertInstanceOf(collection::class, $selectable_users);
        $this->assertCount(0, $selectable_users);

        // Test non-existing user id in filter
        $provider = new selectable_users($subject_instance1);
        $selectable_users = $provider
            ->add_filters(['fullname' => 'Qwertz'])
            ->add_filters(['exclude_users' => [999]])
            ->get();

        $this->assertCount(2, $selectable_users);
        $actual_user_ids = $selectable_users->pluck('id');
        $this->assertEquals([$user2->id, $user1->id], $actual_user_ids);
    }

    public function test_with_multi_tenancy_enabled(): void {
        $generator = $this->getDataGenerator();
        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = $generator->get_plugin_generator('mod_perform');

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');

        $tenant_generator->enable_tenants();

        $tenant1 = $tenant_generator->create_tenant();
        $tenant2 = $tenant_generator->create_tenant();

        $tenant1 = new tenant($tenant1);
        $tenant2 = new tenant($tenant2);

        $tenant_user1 = $generator->create_user(['tenantid' => $tenant1->id]);
        $this->setUser($tenant_user1);
        $perform_category_id_1 = util::get_default_category_id();

        $tenant_user2 = $generator->create_user(['tenantid' => $tenant2->id]);
        $this->setUser($tenant_user2);
        $perform_category_id_2 = util::get_default_category_id();

        $this->setAdminUser();

        $configuration = \mod_perform\testing\activity_generator_configuration::new()
            ->set_tenant_id($tenant1->id)
            ->set_category_id($perform_category_id_1)
            ->set_cohort_assignments_per_activity(1)
            ->set_number_of_users_per_user_group_type(5);

        $activities1 = $perform_generator->create_full_activities($configuration);
        /** @var activity_model $activity1 */
        $activity1 = $activities1->first();

        $configuration2 = \mod_perform\testing\activity_generator_configuration::new()
            ->set_tenant_id($tenant2->id)
            ->set_category_id($perform_category_id_2)
            ->set_cohort_assignments_per_activity(1)
            ->set_number_of_users_per_user_group_type(5);

        $activities2 = $perform_generator->create_full_activities($configuration2);
        /** @var activity_model $activity2 */
        $activity2 = $activities2->first();

        $subject_instance1 = $this->get_subject_instance($activity1);

        $provider = new selectable_users($subject_instance1);
        $selectable_users = $provider->get();

        // The result should contain all users from tenant 1
        $this->assertCount(6, $selectable_users);
        $expected_ids = user::repository()
            ->where('tenantid', $tenant1->id)
            ->get()
            ->pluck('id');

        $this->assertEqualsCanonicalizing($expected_ids, $selectable_users->pluck('id'));

        $subject_instance2 = $this->get_subject_instance($activity2);

        $provider = new selectable_users($subject_instance2);
        $selectable_users = $provider->get();

        // The result should contain all users from tenant 2

        $this->assertCount(6, $selectable_users);
        $expected_ids = user::repository()
            ->where('tenantid', $tenant2->id)
            ->get()
            ->pluck('id');

        $this->assertEqualsCanonicalizing($expected_ids, $selectable_users->pluck('id'));
    }

    private function get_subject_instance(activity_model $activity): subject_instance_model {
        $subject_instance = subject_instance::repository()
            ->join([track_user_assignment::TABLE, 'tua'], 'track_user_assignment_id', 'id')
            ->join([track::TABLE, 't'], 'tua.track_id', 'id')
            ->where('t.activity_id', $activity->id)
            ->order_by('id')
            ->first();

        return subject_instance_model::load_by_entity($subject_instance);
    }

}
