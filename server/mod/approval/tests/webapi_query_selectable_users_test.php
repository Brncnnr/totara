<?php
/**
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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_approval
 */

use core\entity\user;
use core\entity\tenant;
use core_phpunit\testcase;
use core\orm\query\builder;
use container_approval\approval as approval_container;
use mod_approval\exception\access_denied_exception;
use mod_approval\testing\approval_workflow_test_setup;
use mod_approval\model\workflow\workflow;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group approval_workflow
 */
class mod_approval_webapi_query_selectable_users_testcase extends testcase {

    private const QUERY = 'mod_approval_selectable_users';

    use webapi_phpunit_helper;
    use approval_workflow_test_setup;

    /**
     * Gets the approval workflow generator instance
     *
     * @return \mod_approval\testing\generator
     */
    protected function generator(): \mod_approval\testing\generator {
        return \mod_approval\testing\generator::instance();
    }

    public function test_with_multi_tenancy_enabled(): void {
        global $DB, $CFG;
        $generator = $this->getDataGenerator();
        $data = $this->generate_data();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');

        $tenant_generator->enable_tenants();

        $tenant1 = $tenant_generator->create_tenant();
        $tenant2 = $tenant_generator->create_tenant();

        $tenant1 = new tenant($tenant1);
        $tenant2 = new tenant($tenant2);

        // Generate 3 system users
        $user1_t0 = $generator->create_user();
        $user2_t0 = $generator->create_user();
        $user3_t0 = $generator->create_user();

        // Generate 5 users for tenant 1
        $user1_t1 = $generator->create_user(['tenantid' => $tenant1->id]);
        $user2_t1 = $generator->create_user(['tenantid' => $tenant1->id]);
        $user3_t1 = $generator->create_user(['tenantid' => $tenant1->id]);
        $user4_t1 = $generator->create_user(['tenantid' => $tenant1->id]);
        $user5_t1 = $generator->create_user(['tenantid' => $tenant1->id]);

        // Generate 8 users for tenant 2
        $user1_t2 = $generator->create_user(['tenantid' => $tenant2->id]);
        $user2_t2 = $generator->create_user(['tenantid' => $tenant2->id]);
        $user3_t2 = $generator->create_user(['tenantid' => $tenant2->id]);
        $user4_t2 = $generator->create_user(['tenantid' => $tenant2->id]);
        $user5_t2 = $generator->create_user(['tenantid' => $tenant2->id]);
        $user6_t2 = $generator->create_user(['tenantid' => $tenant2->id]);
        $user7_t2 = $generator->create_user(['tenantid' => $tenant2->id]);
        $user8_t2 = $generator->create_user(['tenantid' => $tenant2->id]);


        // Create a system workflow admin.
        $context = approval_container::get_default_category_context();
        $workflow_manager_role = builder::table('role')->where('shortname', 'approvalworkflowmanager')->one();
        role_assign($workflow_manager_role->id, $user1_t0->id, $context);
        assign_capability('mod/approval:manage_individual_workflow_approvers', CAP_ALLOW, $workflow_manager_role->id, $context, true);

        // Create tenant domain workflow admins.
        $context = context_course::instance($data['workflow']->course_id);
        $tenantdomainmanager = $DB->get_record('role', array('shortname' => 'tenantdomainmanager'));
        role_assign($tenantdomainmanager->id, $user1_t1->id, $context);
        role_assign($tenantdomainmanager->id, $user1_t2->id, $context);
        assign_capability('mod/approval:manage_individual_workflow_approvers', CAP_ALLOW, $tenantdomainmanager->id, $context, true);

        $this->setUser($user1_t0);
        $args['input'] = [
            'workflow_id' => $data['workflow']->id,
            'filters' => [],
            'pagination' => [
                'limit' => 20,
                'cursor' => null,
            ],
        ];
        $selectable_users = $this->get_query_data($args);
        // Count includes admin plus 16 generated users.
        $this->assertCount(17, $selectable_users['items']);
        $expected_ids = user::repository()
            ->where('id', '!=', $CFG->siteguest)
            ->get()
            ->pluck('id');

        $this->assertEqualsCanonicalizing($expected_ids, array_column($selectable_users['items'], 'id'));

        $this->setUser($user1_t1);
        $args['input'] = [
            'workflow_id' => $data['workflow']->id,
            'filters' => [],
            'pagination' => [
                'limit' => 10,
                'cursor' => null,
            ],
        ];
        $selectable_users = $this->get_query_data($args);
        $this->assertCount(5, $selectable_users['items']);
        $expected_ids = user::repository()
            ->where('tenantid', $tenant1->id)
            ->get()
            ->pluck('id');

        $this->assertEqualsCanonicalizing($expected_ids, array_column($selectable_users['items'], 'id'));

        $this->setUser($user1_t2);
        $selectable_users = $this->get_query_data($args);
        $this->assertCount(8, $selectable_users['items']);
        $expected_ids = user::repository()
            ->where('tenantid', $tenant2->id)
            ->get()
            ->pluck('id');

        $this->assertEqualsCanonicalizing($expected_ids, array_column($selectable_users['items'], 'id'));

        // Test the total and next cursor
        $args['input'] = [
            'workflow_id' => $data['workflow']->id,
            'filters' => [],
            'pagination' => [
                'limit' => 3,
                'cursor' => null,
            ],
        ];
        $selectable_users = $this->get_query_data($args);
        $this->assertCount(3, $selectable_users['items']);
        $this->assertEquals(8, $selectable_users['total']);
        $this->assertStringContainsString("eyJsaW1pdCI6MywiY29", $selectable_users['next_cursor']);
    }

    public function test_with_multi_tenancy_isolation_enabled(): void {
        global $DB, $CFG;
        $generator = $this->getDataGenerator();
        $data = $this->generate_data();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');

        $tenant_generator->enable_tenants();
        set_config('tenantsisolated', 1);

        $tenant1 = $tenant_generator->create_tenant();
        $tenant2 = $tenant_generator->create_tenant();

        $tenant1 = new tenant($tenant1);
        $tenant2 = new tenant($tenant2);

        // Generate 3 system users
        $user1_t0 = $generator->create_user();
        $user2_t0 = $generator->create_user();
        $user3_t0 = $generator->create_user();

        // Generate 5 users for tenant 1
        $user1_t1 = $generator->create_user(['tenantid' => $tenant1->id]);
        $user2_t1 = $generator->create_user(['tenantid' => $tenant1->id]);
        $user3_t1 = $generator->create_user(['tenantid' => $tenant1->id]);
        $user4_t1 = $generator->create_user(['tenantid' => $tenant1->id]);
        $user5_t1 = $generator->create_user(['tenantid' => $tenant1->id]);

        // Generate 8 users for tenant 2
        $user1_t2 = $generator->create_user(['tenantid' => $tenant2->id]);
        $user2_t2 = $generator->create_user(['tenantid' => $tenant2->id]);
        $user3_t2 = $generator->create_user(['tenantid' => $tenant2->id]);
        $user4_t2 = $generator->create_user(['tenantid' => $tenant2->id]);
        $user5_t2 = $generator->create_user(['tenantid' => $tenant2->id]);
        $user6_t2 = $generator->create_user(['tenantid' => $tenant2->id]);
        $user7_t2 = $generator->create_user(['tenantid' => $tenant2->id]);
        $user8_t2 = $generator->create_user(['tenantid' => $tenant2->id]);


        // Create a system workflow admin.
        $context = approval_container::get_default_category_context();
        $workflow_manager_role = builder::table('role')->where('shortname', 'approvalworkflowmanager')->one();
        role_assign($workflow_manager_role->id, $user1_t0->id, $context);
        assign_capability('mod/approval:manage_individual_workflow_approvers', CAP_ALLOW, $workflow_manager_role->id, $context, true);

        // Create tenant domain workflow admins.
        $context = context_course::instance($data['workflow']->course_id);
        $tenantdomainmanager = $DB->get_record('role', array('shortname' => 'tenantdomainmanager'));
        role_assign($tenantdomainmanager->id, $user1_t1->id, $context);
        role_assign($tenantdomainmanager->id, $user1_t2->id, $context);
        assign_capability('mod/approval:manage_individual_workflow_approvers', CAP_ALLOW, $tenantdomainmanager->id, $context, true);

        $this->setUser($user1_t0);
        $args['input'] = [
            'workflow_id' => $data['workflow']->id,
            'filters' => [],
            'pagination' => [
                'limit' => 20,
                'cursor' => null,
            ],
        ];
        $selectable_users = $this->get_query_data($args);
        // Count includes admin user plus 3 system users only.
        $this->assertCount(4, $selectable_users['items']);
        $expected_ids = user::repository()
            ->where('id', '!=', $CFG->siteguest)
            ->where_null('tenantid')
            ->get()
            ->pluck('id');

        $this->assertEqualsCanonicalizing($expected_ids, array_column($selectable_users['items'], 'id'));
    }

    public function test_get_approvers() {
        $data = $this->generate_data();
        $generator = $this->getDataGenerator();

        $this->setAdminUser();

        $user1 = $generator->create_user(['firstname' => 'bvcxz', 'lastname' => 'Qwertz']);
        $user2 = $generator->create_user(['firstname' => 'asdfgh', 'lastname' => 'Qwertz']);
        $deleted_user = $generator->create_user(['deleted' => 1]);
        $suspended_user = $generator->create_user(['suspended' => 1]);
        $guest_user = guest_user();

        $args['input'] = [
            'workflow_id' => $data['workflow']->id,
            'pagination' => [
                'limit' => 5,
                'cursor' => null,
            ],
        ];
        $selectable_users = $this->get_query_data($args);

        // The result should contain all users (except the guest, deleted and suspended)
        $this->assertCount(3, $selectable_users['items']);
        $actual_user_ids = array_column($selectable_users['items'], 'id');

        $this->assertContains($user1->id, $actual_user_ids);
        $this->assertContains($user2->id, $actual_user_ids);
        $this->assertContains(get_admin()->id, $actual_user_ids);
        $this->assertNotContainsEquals($guest_user->id, $actual_user_ids);
        $this->assertNotContainsEquals($deleted_user->id, $actual_user_ids);
        $this->assertNotContainsEquals($suspended_user->id, $actual_user_ids);

        $args['input'] = [
            'workflow_id' => $data['workflow']->id,
            'filters' => [],
            'pagination' => [
                'limit' => 5,
                'cursor' => null,
            ],
        ];

        $selectable_users = $this->get_query_data($args);

        $this->assertCount(3, $selectable_users['items']);
        $actual_user_ids = array_column($selectable_users['items'], 'id');

        $this->assertContains($user1->id, $actual_user_ids);
        $this->assertContains($user2->id, $actual_user_ids);
        $this->assertContains(get_admin()->id, $actual_user_ids);
        $this->assertNotContainsEquals($guest_user->id, $actual_user_ids);
        $this->assertNotContainsEquals($deleted_user->id, $actual_user_ids);
        $this->assertNotContainsEquals($suspended_user->id, $actual_user_ids);

        // Now filter the data
        $args['input'] = [
            'workflow_id' => $data['workflow']->id,
            'filters' => ['fullname' => 'Qwertz'],
            'pagination' => [
                'limit' => 5,
                'cursor' => null,
            ],
        ];
        $selectable_users = $this->get_query_data($args);

        $this->assertCount(2, $selectable_users['items']);
        $actual_user_ids = array_column($selectable_users['items'], 'id');
        // The result is ordered by fullname, so user2 should come before user1
        $this->assertEquals([$user2->id, $user1->id], $actual_user_ids);

        $args['input'] = [
            'workflow_id' => $data['workflow']->id,
            'filters' => ['fullname' => 'asdfgh'],
            'pagination' => [
                'limit' => 5,
                'cursor' => null,
            ],
        ];
        $selectable_users = $this->get_query_data($args);

        $this->assertCount(1, $selectable_users['items']);
        $actual_user_ids = array_column($selectable_users['items'], 'id');
        // The result is ordered by fullname, so user2 should come before user1
        $this->assertContains($user2->id, $actual_user_ids);
    }

    public function test_ajax_query_failed(): void {
        $data = $this->generate_data();
        $args['input'] = [
            'workflow_id' => $data['workflow']->id,
            'pagination' => [
                'limit' => 5,
                'cursor' => null,
            ],
        ];
        $this->setUser(0);
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_failed($result, 'You are not logged in');
    }

    public function test_query_without_login() {
        $data = $this->generate_data();
        $args['input'] = [
            'workflow_id' => $data['workflow']->id,
            'pagination' => [
                'limit' => 5,
                'cursor' => null,
            ],
        ];
        $this->setUser(0);
        $this->expectException('require_login_exception');
        $result = $this->resolve_graphql_query(self::QUERY, $args);
    }

    public function test_query_as_guest() {
        $data = $this->generate_data();
        $args['input'] = [
            'workflow_id' => $data['workflow']->id,
        ];
        $this->setGuestUser();
        $this->expectException('require_login_exception');
        $result = $this->resolve_graphql_query(self::QUERY, $args);
    }

    public function test_query_as_admin() {
        $data = $this->generate_data();
        $args['input'] = [
            'workflow_id' => $data['workflow']->id,
            'pagination' => [
                'limit' => 5,
                'cursor' => null,
            ],
        ];
        $this->setAdminUser();
        $result = $this->resolve_graphql_query(self::QUERY, $args);
        $this->assertCount(1, $result['items']);
    }

    public function test_require_workflow_id() {
        $data = $this->generate_data();
        $args['input'] = [
            'pagination' => [
                'limit' => 5,
                'cursor' => null,
            ],
        ];
        $this->setAdminUser();
        try {
            $this->resolve_graphql_query(self::QUERY, $args);
            $this->fail('invalid_parameter_exception expected');
        } catch (invalid_parameter_exception $e) {
            $this->assertStringContainsString('invalid workflow_id', $e->getMessage());
        }
    }

    public function test_capability() {
        global $CFG;
        require_once($CFG->libdir . '/coursecatlib.php');

        $context = approval_container::get_default_category_context();
        $workflow_manager_user = self::getDataGenerator()->create_user();
        $workflow_manager_role = builder::table('role')->where('shortname', 'approvalworkflowmanager')->one();
        role_assign($workflow_manager_role->id, $workflow_manager_user->id, $context);

        $data = $this->generate_data();
        $args['input'] = [
            'workflow_id' => $data['workflow']->id,
            'pagination' => [
                'limit' => 5,
                'cursor' => null,
            ],
        ];

        // Test without 'mod/approval:manage_individual_workflow_approvers' capability
        $this->setUser($workflow_manager_user);
        try {
            $this->resolve_graphql_query(self::QUERY, $args);
            $this->fail('access_denied_exception expected');
        } catch (access_denied_exception $e) {
            $this->assertStringContainsString('Cannot manage approvers', $e->getMessage());
        }

        // Test with 'mod/approval:manage_individual_workflow_approvers' capability
        assign_capability('mod/approval:manage_individual_workflow_approvers', CAP_ALLOW, $workflow_manager_role->id, $context, true);
        $result = $this->resolve_graphql_query(self::QUERY, $args);
        $this->assertCount(2, $result['items']);
    }

    private function get_query_data(array $params = []): array {
        global $PAGE;
        // Reset the page otherwise we'll run into trouble running the query multiple times
        $PAGE = new moodle_page();

        $result = $this->parsed_graphql_operation(self::QUERY, $params);
        $this->assert_webapi_operation_successful($result);
        return $this->get_webapi_operation_data($result);
    }

    private function generate_data(): array {

        list($workflow) = $this->create_workflow_and_assignment();

        return [
            'workflow' => workflow::load_by_entity($workflow),
        ];
    }
}
