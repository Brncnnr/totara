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
* @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package mod_perform
 */

use core\collection;
use core\entity\tenant;
use core\orm\query\builder;
use mod_perform\entity\activity\subject_instance;
use mod_perform\entity\activity\track;
use mod_perform\entity\activity\track_user_assignment;
use mod_perform\models\activity\activity;
use mod_perform\state\activity\active;
use mod_perform\state\activity\draft;
use totara_core\advanced_feature;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass \mod_perform\webapi\resolver\query\reportable_activities
 *
 * @group perform
 */
class mod_perform_webapi_resolver_query_reportable_activities_testcase extends advanced_testcase {
    private const QUERY = 'mod_perform_reportable_activities';

    use webapi_phpunit_helper;

    public function test_get_activities_for_normal_user(): void {

        $user = $this->getDataGenerator()->create_user();

        $this->setAdminUser();
        $this->create_test_data();

        self::setUser($user);

        $activities = $this->resolve_graphql_query(self::QUERY, []);
        $this->assertEmpty($activities);
    }

    public function test_get_activities_for_report_admin(): void {
        $user = $this->getDataGenerator()->create_user();
        // The capability is added to the role in the system context.
        $sys_context = context_system::instance();
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('mod/perform:report_on_all_subjects_responses', CAP_ALLOW, $roleid, $sys_context);

        // The role is granted in the user's own context.
        $user_context = \context_user::instance($user->id);
        role_assign($roleid, $user->id, $user_context);
        // Creating full activities as whether you can report on
        // depends on the actual subject users
        $this->setAdminUser();
        $expected_activities = $this->create_test_data();
        self::setUser($user);

        $actual_activities = $this->resolve_graphql_query(self::QUERY, []);
        $this->assertEqualsCanonicalizing(
            $expected_activities->pluck('id'),
            $actual_activities->pluck('id')
        );

        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();

        // Draft activites never should show up
        $this->setAdminUser();
        $draft_activity = $perform_generator->create_activity_in_container(['activity_status' => draft::get_code()]);
        self::setUser($user);

        $actual_activities = $this->resolve_graphql_query(self::QUERY, []);
        $this->assertNull($actual_activities->find('id', $draft_activity->id));

        // As the user has the super capability an activity without responsed will also show up
        $this->setAdminUser();
        $active_activity = $perform_generator->create_activity_in_container(['activity_status' => active::get_code()]);
        self::setUser($user);

        $actual_activities = $this->resolve_graphql_query(self::QUERY, []);
        $this->assertNotNull($actual_activities->find('id', $active_activity->id));
    }

    public function test_get_activities_for_report_admin_multi_tenancy_enabled(): void {
        $generator = $this->getDataGenerator();
        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = $generator->get_plugin_generator('mod_perform');

        $this->setAdminUser();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');

        $system_user = $generator->create_user();

        $tenant_generator->enable_tenants();

        $tenant1 = $tenant_generator->create_tenant();
        $tenant2 = $tenant_generator->create_tenant();

        $tenant1 = new tenant($tenant1);
        $tenant2 = new tenant($tenant2);

        $tenant1_category_context = context_coursecat::instance($tenant1->categoryid);
        $tenant2_category_context = context_coursecat::instance($tenant2->categoryid);

        $role_dm = builder::table('role')->where('shortname', 'tenantdomainmanager')->one();
        $role_um = builder::table('role')->where('shortname', 'tenantusermanager')->one();

        $tenant1_manager = $generator->create_user(['tenantid' => $tenant1->id]);
        role_assign($role_dm->id, $tenant1_manager->id, $tenant1_category_context->id);
        role_assign($role_um->id, $tenant1_manager->id, context_tenant::instance($tenant1->id));

        $tenant2_manager = $generator->create_user(['tenantid' => $tenant2->id]);
        role_assign($role_dm->id, $tenant2_manager->id, $tenant2_category_context->id);
        role_assign($role_um->id, $tenant2_manager->id, context_tenant::instance($tenant2->id));

        $this->setAdminUser();

        // Create a system activity
        $activities0 = $perform_generator->create_full_activities(
            \mod_perform\testing\activity_generator_configuration::new()
                ->set_number_of_activities(3)
        );

        $activities1 = $perform_generator->create_full_activities(
            \mod_perform\testing\activity_generator_configuration::new()
                ->set_number_of_activities(3)
                ->set_tenant_id($tenant1->id)
        );

        $activities2 = $perform_generator->create_full_activities(
            \mod_perform\testing\activity_generator_configuration::new()
                ->set_number_of_activities(3)
                ->set_tenant_id($tenant2->id)
        );


        $this->setUser($tenant1_manager);

        // Let's pick one user
        /** @var activity $activity1 */
        $activity1 = $activities1->first();

        $subject_instances = subject_instance::repository()
            ->join([track_user_assignment::TABLE, 'tua'], 'track_user_assignment_id', 'id')
            ->join([track::TABLE, 't'], 'tua.track_id', 'id')
            ->where('t.activity_id', $activity1->id)
            ->get();

        /** @var subject_instance $subject_instance1 */
        $subject_instance1 = $subject_instances->shift();
        /** @var subject_instance $subject_instance2 */
        $subject_instance2 = $subject_instances->shift();

        // Make sure we have two separate users
        $this->assertNotEquals($subject_instance1->subject_user_id, $subject_instance2->subject_user_id);

        assign_capability(
            'mod/perform:report_on_subject_responses',
            CAP_ALLOW,
            $role_um->id,
            context_user::instance($subject_instance1->subject_user_id)
        );

        // Now this activity should show up as the user has the capability to report on an user involved
        $actual_activities = $this->resolve_graphql_query(self::QUERY, []);
        $this->assertCount(1, $actual_activities);
        $this->assertEquals(
            [$activity1->id],
            $actual_activities->pluck('id')
        );

        $this->setUser($tenant2_manager);

        $actual_activities = $this->resolve_graphql_query(self::QUERY, []);
        $this->assertCount(0, $actual_activities);

        // Now give this user the capability to report on all users in the tenant
        assign_capability(
            'mod/perform:report_on_all_subjects_responses',
            CAP_ALLOW,
            $role_um->id,
            context_tenant::instance($tenant2->id)
        );

        $actual_activities = $this->resolve_graphql_query(self::QUERY, []);
        $this->assertCount(3, $actual_activities);
        $this->assertEqualsCanonicalizing(
            $activities2->pluck('id'),
            $actual_activities->pluck('id')
        );

        $this->setUser($system_user);

        $actual_activities = $this->resolve_graphql_query(self::QUERY, []);
        $this->assertCount(0, $actual_activities);

        // Now give the system user the capability to report on all users on the whole site
        // The capability is added to the role in the system context.
        $sys_context = context_system::instance();
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('mod/perform:report_on_all_subjects_responses', CAP_ALLOW, $roleid, $sys_context);
        // The role is granted in the user's own context.
        role_assign($roleid, $system_user->id, context_user::instance($system_user->id));

        $actual_activities = $this->resolve_graphql_query(self::QUERY, []);
        $this->assertCount(9, $actual_activities);
        $this->assertEqualsCanonicalizing(
            array_merge($activities0->pluck('id'), $activities1->pluck('id'), $activities2->pluck('id')),
            $actual_activities->pluck('id')
        );
    }

    public function test_successful_ajax_call(): void {
        self::setAdminUser();

        $expected_activities = $this->create_test_data();

        $result = $this->parsed_graphql_operation(self::QUERY, []);
        $this->assert_webapi_operation_successful($result);

        $data = $this->get_webapi_operation_data($result);
        $this->assertCount(3, $data);

        $this->assertEqualsCanonicalizing(
            $expected_activities->pluck('id'),
            array_column($data, 'id')
        );
    }

    public function test_failed_ajax_query(): void {
        self::setAdminUser();

        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();
        $element = $perform_generator->create_element();

        $args = [];

        $feature = 'performance_activities';
        advanced_feature::disable($feature);
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_failed($result, 'Feature performance_activities is not available.');
        advanced_feature::enable($feature);

        self::setUser();
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_failed($result, 'not logged in');
    }

    private function create_test_data(): collection {
        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();

        $activities = $perform_generator->create_full_activities(
            \mod_perform\testing\activity_generator_configuration::new()
                ->set_number_of_activities(3)
        );

        return $activities;
    }
}