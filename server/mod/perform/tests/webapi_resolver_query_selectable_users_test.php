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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_perform
 */

use core\entity\tenant;
use core\entity\user;
use mod_perform\entity\activity\subject_instance as subject_instance_entity;
use mod_perform\entity\activity\track;
use mod_perform\entity\activity\track_user_assignment;
use mod_perform\models\activity\activity as activity_model;
use mod_perform\models\activity\subject_instance as subject_instance_model;
use mod_perform\util;
use totara_core\advanced_feature;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group perform
 */
class mod_perform_webapi_resolver_query_selectable_users_testcase extends advanced_testcase {

    private const QUERY = 'mod_perform_selectable_users';

    use webapi_phpunit_helper;

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
        $subject_instance2 = $this->get_subject_instance($activity2);

        $selectable_users = $this->get_query_data(['subject_instance_id' => $subject_instance1->id]);

        // The result should contain all users from tenant 1
        $this->assertCount(6, $selectable_users);
        $expected_ids = user::repository()
            ->where('tenantid', $tenant1->id)
            ->get()
            ->pluck('id');

        $this->assertEqualsCanonicalizing($expected_ids, array_column($selectable_users, 'id'));

        $selectable_users = $this->get_query_data(['subject_instance_id' => $subject_instance2->id]);

        // The result should contain all users from tenant 2

        $this->assertCount(6, $selectable_users);
        $expected_ids = user::repository()
            ->where('tenantid', $tenant2->id)
            ->get()
            ->pluck('id');

        $this->assertEqualsCanonicalizing($expected_ids, array_column($selectable_users, 'id'));
    }

    public function test_get_users() {
        $generator = $this->getDataGenerator();
        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = $generator->get_plugin_generator('mod_perform');

        $this->setAdminUser();

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
        $subject_instance2 = $this->get_subject_instance($activity2);

        $selectable_users = $this->get_query_data(['subject_instance_id' => $subject_instance1->id]);

        // The result should contain all users (except the guest, deleted and suspended)
        $this->assertCount(13, $selectable_users);
        $actual_user_ids = array_column($selectable_users, 'id');

        $this->assertContains($user1->id, $actual_user_ids);
        $this->assertContains($user2->id, $actual_user_ids);
        $this->assertContains(get_admin()->id, $actual_user_ids);
        $this->assertNotContainsEquals($guest_user->id, $actual_user_ids);
        $this->assertNotContainsEquals($deleted_user->id, $actual_user_ids);
        $this->assertNotContainsEquals($suspended_user->id, $actual_user_ids);

        $selectable_users = $this->get_query_data(['subject_instance_id' => $subject_instance2->id]);

        $this->assertCount(13, $selectable_users);
        $actual_user_ids = array_column($selectable_users, 'id');

        $this->assertContains($user1->id, $actual_user_ids);
        $this->assertContains($user2->id, $actual_user_ids);
        $this->assertContains(get_admin()->id, $actual_user_ids);
        $this->assertNotContainsEquals($guest_user->id, $actual_user_ids);
        $this->assertNotContainsEquals($deleted_user->id, $actual_user_ids);
        $this->assertNotContainsEquals($suspended_user->id, $actual_user_ids);

        // Now filter the data
        $selectable_users = $this->get_query_data([
            'subject_instance_id' => $subject_instance2->id,
            'filters' => [
                'exclude_users' => [$user1->id, get_admin()->id]
            ]
        ]);

        $this->assertCount(11, $selectable_users);
        $actual_user_ids = array_column($selectable_users, 'id');
        $this->assertNotContainsEquals($user1->id, $actual_user_ids);
        $this->assertNotContainsEquals(get_admin()->id, $actual_user_ids);

        $selectable_users = $this->get_query_data([
            'subject_instance_id' => $subject_instance2->id,
            'filters' => [
                'fullname' => 'Qwertz'
            ]
        ]);

        $this->assertCount(2, $selectable_users);
        $actual_user_ids = array_column($selectable_users, 'id');
        // The result is ordered by fullname, so user2 should come before user1
        $this->assertEquals([$user2->id, $user1->id], $actual_user_ids);

        $selectable_users = $this->get_query_data([
            'subject_instance_id' => $subject_instance2->id,
            'filters' => [
                'fullname' => 'asdfgh'
            ]
        ]);

        $this->assertCount(1, $selectable_users);
        $actual_user_ids = array_column($selectable_users, 'id');
        // The result is ordered by fullname, so user2 should come before user1
        $this->assertContains($user2->id, $actual_user_ids);

        // Test combination of filters
        $selectable_users = $this->get_query_data([
            'subject_instance_id' => $subject_instance2->id,
            'filters' => [
                'fullname' => 'Qwertz',
                'exclude_users' => [$user2->id]
            ]
        ]);

        $this->assertCount(1, $selectable_users);
        $actual_user_ids = array_column($selectable_users, 'id');
        $this->assertEquals([$user1->id], $actual_user_ids);
    }

    public function delete_or_suspend_data_provider(): array {
        return [
            'Deleted user should be hidden' => [
                static function (user $user) {
                    delete_user($user->to_record());
                }
            ],
            'Suspended user should be hidden with "hide_suspended_users" switched on' => [
                static function (user $user) {
                    set_config('perform_hide_suspended_users', 1);
                    user_suspend_user($user->id);
                }
            ],
            'Suspended user should NOT be hidden with "hide_suspended_users" switched off' => [
                static function (user $user) {
                    set_config('perform_hide_suspended_users', 0);
                    user_suspend_user($user->id);
                },
                false
            ],
            'The setting "perform_close_suspended_user_instances" should not have any effect' => [
                static function (user $user) {
                    set_config('perform_close_suspended_user_instances', 1);
                    user_suspend_user($user->id);
                },
                false
            ],
        ];
    }

    /**
     * @dataProvider delete_or_suspend_data_provider
     * @param closure $delete_or_suspend
     * @param bool $should_be_hidden
     * @return void
     */
    public function test_get_users_subject_user_got_deleted_or_suspended(
        closure $delete_or_suspend,
        bool $should_be_hidden = true
    ): void {
        $generator = self::getDataGenerator();
        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = $generator->get_plugin_generator('mod_perform');

        self::setAdminUser();

        $configuration = \mod_perform\testing\activity_generator_configuration::new()
            ->set_cohort_assignments_per_activity(1)
            ->set_number_of_users_per_user_group_type(5);

        $activities = $perform_generator->create_full_activities($configuration);
        /** @var activity_model $activity */
        $activity = $activities->first();
        $subject_instance = $this->get_subject_instance($activity);

        $params = ['subject_instance_id' => $subject_instance->id];

        $selectable_users = $this->get_query_data($params);

        self::assertNotEmpty($selectable_users);

        $delete_or_suspend($subject_instance->subject_user->get_user());

        $result = $this->parsed_graphql_operation(self::QUERY, $params);
        if ($should_be_hidden) {
            $this->assert_webapi_operation_failed($result, 'Invalid activity');
        } else {
            $this->assert_webapi_operation_successful($result);
        }
    }

    public function test_ajax_query_failed(): void {
        $generator = $this->getDataGenerator();
        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = $generator->get_plugin_generator('mod_perform');

        $this->setAdminUser();
        $activities = $perform_generator->create_full_activities();
        $subject_instance = $this->get_subject_instance($activities->first());

        $result = $this->parsed_graphql_operation(self::QUERY, ['subject_instance_id' => 1]);
        $this->assert_webapi_operation_failed($result, 'Invalid activity');

        $this->setUser(null);

        $result = $this->parsed_graphql_operation(self::QUERY, ['subject_instance_id' => $subject_instance->id]);
        $this->assert_webapi_operation_failed($result, 'You are not logged in');

        self::setAdminUser();

        $result = $this->parsed_graphql_operation(self::QUERY, []);
        $this->assert_webapi_operation_failed(
            $result,
            'Variable "$subject_instance_id" of required type "core_id!" was not provided.'
        );

        advanced_feature::disable('performance_activities');

        $result = $this->parsed_graphql_operation(self::QUERY, ['subject_instance_id' => 1]);
        $this->assert_webapi_operation_failed($result, 'Feature performance_activities is not available.');
    }

    private function get_query_data(array $params = []): array {
        global $PAGE;
        // Reset the page otherwise we'll run into trouble running the query multiple times
        $PAGE = new moodle_page();

        $result = $this->parsed_graphql_operation(self::QUERY, $params);
        $this->assert_webapi_operation_successful($result);
        return $this->get_webapi_operation_data($result);
    }

    private function get_subject_instance(activity_model $activity): subject_instance_model {
        $subject_instance = subject_instance_entity::repository()
            ->join([track_user_assignment::TABLE, 'tua'], 'track_user_assignment_id', 'id')
            ->join([track::TABLE, 't'], 'tua.track_id', 'id')
            ->where('t.activity_id', $activity->id)
            ->order_by('id')
            ->first();

        return subject_instance_model::load_by_entity($subject_instance);
    }

}
