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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

use core_phpunit\testcase;
use mod_approval\data_provider\user\selectable_applicants_for_category;
use container_approval\approval;
use core\collection;
use core\entity\user;
use core\orm\query\builder;
use mod_approval\model\assignment\assignment;
use mod_approval\model\workflow\workflow;
use mod_approval\testing\approval_workflow_test_setup;

/**
 * @coversDefaultClass mod_approval\data_provider\user\selectable_applicants_for_category
 *
 * @group approval_workflow
 */
class mod_approval_data_provider_selectable_applicants_for_category_testcase extends testcase {

    use approval_workflow_test_setup;

    public function test_available_applicant_in_a_tenant() {
        $data = $this->setup_multi_tenant_users();

        // Site Manager selecting applicants.
        $site_manager = $data['site']['manager'];

        $provider = new selectable_applicants_for_category($site_manager->id);
        $selectable_applicants = $provider->get_page(null, 20);
        // 12 users plus admin.
        $this->assertEquals(13, $selectable_applicants['total']);

        $all_users = array_merge(
            $data['site']['users'],
            $data['tenants']['1']['users'],
            $data['tenants']['2']['users']
        );
        $all_user_ids = array_map(function ($user) {
            return $user->id;
        }, $all_users);
        $admin_user = user::repository()->where('username', 'admin')->one();
        $all_user_ids[] = $admin_user->id;
        $selectable_applicant_ids = array_map(function ($user) {
            return $user->id;
        }, $selectable_applicants['items']);
        $this->assertEqualsCanonicalizing($all_user_ids, $selectable_applicant_ids);

        // Tenant 1 manager selecting applicants.
        $tenant_1_manager = $data['tenants']['1']['manager'];
        $this->setUser($tenant_1_manager);

        $provider = new selectable_applicants_for_category($tenant_1_manager->id);
        $tenant_1_applicants = $provider->get_page(null, 20);
        $this->assertEquals(5, $tenant_1_applicants['total']);
        $tenant_1_user_ids = array_map(function ($user) {
            return $user->id;
        }, $data['tenants']['1']['users']);

        $tenant_1_applicant_ids = array_map(function ($user) {
            return $user->id;
        }, $tenant_1_applicants['items']);

        $this->assertEqualsCanonicalizing($tenant_1_user_ids, $tenant_1_applicant_ids);
    }

    public function test_available_applicant_in_site_with_isolation_enabled_limits_to_site_users() {
        $data = $this->setup_multi_tenant_users();
        set_config('tenantsisolated', 1);

        // Site Manager selecting applicants.
        $site_manager = $data['site']['manager'];

        $provider = new selectable_applicants_for_category($site_manager->id);
        $selectable_applicants = $provider->get_page(null, 20);
        // 4 site users plus the admin
        $this->assertEquals(5, $selectable_applicants['total']);

        $site_user_ids = array_map(function ($user) {
            return $user->id;
        }, $data['site']['users']);
        $admin_user = user::repository()->where('username', 'admin')->one();
        $site_user_ids[] = $admin_user->id;
        $selectable_applicant_ids = array_map(function ($user) {
            return $user->id;
        }, $selectable_applicants['items']);
        $this->assertEqualsCanonicalizing($site_user_ids, $selectable_applicant_ids);
    }

    /**
     * @return array
     */
    private function setup_multi_tenant_users() {
        $this->setAdminUser();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $this->getDataGenerator()->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        // Setup site users & manager
        $site_users = $this->create_site_users(4);
        $site_manager = collection::new($site_users)->first();

        // Setup system workflow manager.
        $workflow_manager_role = builder::table('role')->where('shortname', 'approvalworkflowmanager')->one();
        role_assign($workflow_manager_role->id, $site_manager->id, context_system::instance());

        $tenantdomainmanager_role = builder::table('role')
            ->where('shortname', 'tenantdomainmanager')
            ->one();

        // Setup tenant 1 manager and users.
        $tenant_1 = $tenant_generator->create_tenant();
        $tenant_1_users = $this->create_site_users(5, ['tenantid' => $tenant_1->id]);
        $tenant_1_manager = collection::new($tenant_1_users)->first();
        role_assign($tenantdomainmanager_role->id, $tenant_1_manager->id, $tenant_1->context);

        // Setup tenant 2 manager and users.
        $tenant_2 = $tenant_generator->create_tenant();
        $tenant_2_users = $this->create_site_users(3, ['tenantid' => $tenant_2->id]);
        $tenant_2_manager = collection::new($tenant_2_users)->first();
        role_assign($tenantdomainmanager_role->id, $tenant_2_manager->id, $tenant_2->context);

        assign_capability(
            'mod/approval:create_application_any',
            CAP_ALLOW,
            $tenantdomainmanager_role->id,
            $tenant_1->context,
            true
        );
        assign_capability(
            'mod/approval:create_application_any',
            CAP_ALLOW,
            $tenantdomainmanager_role->id,
            $tenant_2->context,
            context_tenant::instance($tenant_2_manager->tenantid),
            true
        );

        return [
            'site' => [
                'users' => $site_users,
                'manager' => $site_manager,
            ],
            'tenants' => [
                '1' => [
                    'users' => $tenant_1_users,
                    'manager' => $tenant_1_manager,
                ],
                '2' => [
                    'users' => $tenant_2_users,
                    'manager' => $tenant_2_manager,
                ]
            ],
        ];
    }

    public function test_available_applicants_with_create_any_capability() {
        $this->create_site_users(4);

        $context = approval::get_default_category_context();
        $user = self::getDataGenerator()->create_user();
        $user_role = builder::table('role')->where('shortname', 'user')->one();
        role_assign($user_role->id, $user->id, $context);
        assign_capability('mod/approval:create_application_any', CAP_ALLOW, $user_role->id, $context, true);
        $this->setUser($user);

        $provider = new selectable_applicants_for_category($user->id);
        $selectable_applicants = $provider->get_page(null, 20);

        // 6 comprising of 4 site users created, admin and user himself.
        $this->assertEquals(6, $selectable_applicants['total']);
    }

    public function test_available_applicants_with_create_any_capability_in_workflow_context() {
        $this->create_site_users(4);

        list($workflow_entity, $framework, $assignment_entity) = $this->create_workflow_and_assignment();
        $workflow = workflow::load_by_entity($workflow_entity);

        $context = $workflow->get_context();
        $user = self::getDataGenerator()->create_user();
        $user_role = builder::table('role')->where('shortname', 'user')->one();
        role_assign($user_role->id, $user->id, $context);
        assign_capability('mod/approval:create_application_any', CAP_ALLOW, $user_role->id, $context, true);
        $this->setUser($user);

        $provider = new selectable_applicants_for_category($user->id);
        $selectable_applicants = $provider->get_page(null, 20);

        // 6 comprising of 4 site users created, admin and user himself.
        $this->assertEquals(6, $selectable_applicants['total']);
    }

    public function test_available_applicants_with_create_any_capability_in_assignment_context() {
        $this->create_site_users(4);

        list($workflow_entity, $framework, $assignment_entity) = $this->create_workflow_and_assignment();
        $assignment = assignment::load_by_entity($assignment_entity);

        $context = $assignment->get_context();
        $user = self::getDataGenerator()->create_user();
        $user_role = builder::table('role')->where('shortname', 'user')->one();
        role_assign($user_role->id, $user->id, $context);
        assign_capability('mod/approval:create_application_any', CAP_ALLOW, $user_role->id, $context, true);
        $this->setUser($user);

        $provider = new selectable_applicants_for_category($user->id);
        $selectable_applicants = $provider->get_page(null, 20);

        // 6 comprising of 4 site users created, admin and user himself.
        $this->assertEquals(6, $selectable_applicants['total']);
    }

    public function test_available_applicants_with_create_user_capability() {
        $site_users = $this->create_site_users(5);

        $user_i_can_create_for = collection::new($site_users)->first();
        $user_context = context_user::instance($user_i_can_create_for->id);

        $user = self::getDataGenerator()->create_user();
        $user_role = builder::table('role')->where('shortname', 'user')->one();
        role_assign($user_role->id, $user->id, $user_context);
        assign_capability('mod/approval:create_application_user', CAP_ALLOW, $user_role->id, $user_context, true);
        $this->setUser($user);

        $provider = new selectable_applicants_for_category($user->id);
        $selectable_applicants = $provider->get_page(null, 20);

        $this->assertEquals(1, $selectable_applicants['total']);

        $applicant = collection::new($selectable_applicants['items'])->first();
        $this->assertEquals($user_i_can_create_for->id, $applicant->id);
    }

    /**
     * Tests it filters out guest, deleted and suspended users.
     */
    public function test_filter_out_invalid_users() {
        global $CFG;

        $users = $this->create_site_users(5);

        $users_collection = collection::new($users);
        $user_to_delete = $users_collection->shift();
        user::repository()
            ->where('id', $user_to_delete->id)
            ->update([
                'deleted' => 1
            ]);

        $user_to_suspend = $users_collection->shift();
        user::repository()
            ->where('id', $user_to_suspend->id)
            ->update([
                'suspended' => 1
            ]);

        $context = approval::get_default_category_context();
        $workflow_manager_user = self::getDataGenerator()->create_user();
        $workflow_manager_role = builder::table('role')->where('shortname', 'approvalworkflowmanager')->one();
        role_assign($workflow_manager_role->id, $workflow_manager_user->id, $context);
        $this->setUser($workflow_manager_user);

        $provider = new selectable_applicants_for_category($workflow_manager_user->id);
        $selectable_applicants = $provider->get_page(null, 20);

        $applicant_ids = collection::new($selectable_applicants['items'])->pluck('id');

        $this->assertEquals(5, $selectable_applicants['total']);
        $this->assertNotContains($CFG->siteguest, $applicant_ids);
        $this->assertNotContains($user_to_delete->id, $applicant_ids);
        $this->assertNotContains($user_to_suspend->id, $applicant_ids);
    }

    public function test_limit_and_cursor_on_available_applicants() {
        $this->create_site_users(20);

        $context = approval::get_default_category_context();
        $workflow_manager_user = self::getDataGenerator()->create_user();
        $workflow_manager_role = builder::table('role')->where('shortname', 'approvalworkflowmanager')->one();
        role_assign($workflow_manager_role->id, $workflow_manager_user->id, $context);
        $this->setUser($workflow_manager_user);

        $provider = new selectable_applicants_for_category($workflow_manager_user->id);
        $page_size = 4;
        $selectable_applicants_page_1 = $provider->get_page(null, $page_size);

        $this->assertEquals(22, $selectable_applicants_page_1['total']);
        $this->assertCount($page_size, $selectable_applicants_page_1['items']);

        $selectable_applicants_page_2 = $provider->get_page($selectable_applicants_page_1['next_cursor'], $page_size);

        $this->assertEquals(22, $selectable_applicants_page_2['total']);
        $this->assertCount($page_size, $selectable_applicants_page_2['items']);
        $this->assertNotEqualsCanonicalizing(
            $selectable_applicants_page_1['items'],
            $selectable_applicants_page_2['items'],
            'Next page items should not be same as previous'
        );
    }

    public function test_sorting_applicants() {
        global $CFG;

        $user1 = self::getDataGenerator()->create_user(['firstname' => 'Sammy', 'lastname' => 'Sam']);
        $user2 = self::getDataGenerator()->create_user(['firstname' => 'Sally', 'lastname' => 'Sam']);
        $user3 = self::getDataGenerator()->create_user(['firstname' => 'Sammy', 'lastname' => 'Sal']);
        $user4 = self::getDataGenerator()->create_user(['firstname' => 'Sally', 'lastname' => 'Sal']);
        $admin = get_admin();
        $context = approval::get_default_category_context();
        $workflow_manager_user = self::getDataGenerator()->create_user(['firstname' => 'Antony', 'lastname' => 'Beam']);
        $workflow_manager_role = builder::table('role')->where('shortname', 'approvalworkflowmanager')->one();
        role_assign($workflow_manager_role->id, $workflow_manager_user->id, $context);
        $this->setUser($workflow_manager_user);

        $provider = new selectable_applicants_for_category($workflow_manager_user->id);
        $page_size = 6;
        $original = $CFG->fullnamedisplay;
        $CFG->fullnamedisplay = 'language';
        $this->overrideLangString('fullnamedisplay', '', 'firstname, lastname');

        $selectable_applicants_page_1 = $provider->get_page(null, $page_size);
        $this->assertEquals($admin->id, $selectable_applicants_page_1['items'][0]->id);
        $this->assertEquals($workflow_manager_user->id, $selectable_applicants_page_1['items'][1]->id);
        $this->assertEquals($user4->id, $selectable_applicants_page_1['items'][2]->id);
        $this->assertEquals($user2->id, $selectable_applicants_page_1['items'][3]->id);
        $this->assertEquals($user3->id, $selectable_applicants_page_1['items'][4]->id);
        $this->assertEquals($user1->id, $selectable_applicants_page_1['items'][5]->id);

        $this->overrideLangString('fullnamedisplay', '', 'firstname');

        $selectable_applicants_page_1 = $provider->get_page(null, $page_size);
        $this->assertEquals($admin->id, $selectable_applicants_page_1['items'][0]->id);
        $this->assertEquals($workflow_manager_user->id, $selectable_applicants_page_1['items'][1]->id);
        $this->assertEquals($user2->id, $selectable_applicants_page_1['items'][2]->id);
        $this->assertEquals($user4->id, $selectable_applicants_page_1['items'][3]->id);
        $this->assertEquals($user1->id, $selectable_applicants_page_1['items'][4]->id);
        $this->assertEquals($user3->id, $selectable_applicants_page_1['items'][5]->id);

        $this->overrideLangString('fullnamedisplay', '', 'lastname, firstname');
        $selectable_applicants_page_1 = $provider->get_page(null, $page_size);
        $this->assertEquals($workflow_manager_user->id, $selectable_applicants_page_1['items'][0]->id);
        $this->assertEquals($user4->id, $selectable_applicants_page_1['items'][1]->id);
        $this->assertEquals($user3->id, $selectable_applicants_page_1['items'][2]->id);
        $this->assertEquals($user2->id, $selectable_applicants_page_1['items'][3]->id);
        $this->assertEquals($user1->id, $selectable_applicants_page_1['items'][4]->id);
        $this->assertEquals($admin->id, $selectable_applicants_page_1['items'][5]->id);

        $this->overrideLangString('fullnamedisplay', '', 'lastname');
        $selectable_applicants_page_1 = $provider->get_page(null, $page_size);
        $this->assertEquals($workflow_manager_user->id, $selectable_applicants_page_1['items'][0]->id);
        $this->assertEquals($user3->id, $selectable_applicants_page_1['items'][1]->id);
        $this->assertEquals($user4->id, $selectable_applicants_page_1['items'][2]->id);
        $this->assertEquals($user1->id, $selectable_applicants_page_1['items'][3]->id);
        $this->assertEquals($user2->id, $selectable_applicants_page_1['items'][4]->id);
        $this->assertEquals($admin->id, $selectable_applicants_page_1['items'][5]->id);

        $this->overrideLangString('fullnamedisplay', '', '');
        $selectable_applicants_page_1 = $provider->get_page(null, $page_size);
        $this->assertEquals($admin->id, $selectable_applicants_page_1['items'][0]->id);
        $this->assertEquals($workflow_manager_user->id, $selectable_applicants_page_1['items'][1]->id);
        $this->assertEquals($user4->id, $selectable_applicants_page_1['items'][2]->id);
        $this->assertEquals($user2->id, $selectable_applicants_page_1['items'][3]->id);
        $this->assertEquals($user3->id, $selectable_applicants_page_1['items'][4]->id);
        $this->assertEquals($user1->id, $selectable_applicants_page_1['items'][5]->id);

        $CFG->fullnamedisplay = 'lastname firstname';
        $selectable_applicants_page_1 = $provider->get_page(null, $page_size);
        $this->assertEquals($workflow_manager_user->id, $selectable_applicants_page_1['items'][0]->id);
        $this->assertEquals($user4->id, $selectable_applicants_page_1['items'][1]->id);
        $this->assertEquals($user3->id, $selectable_applicants_page_1['items'][2]->id);
        $this->assertEquals($user2->id, $selectable_applicants_page_1['items'][3]->id);
        $this->assertEquals($user1->id, $selectable_applicants_page_1['items'][4]->id);
        $this->assertEquals($admin->id, $selectable_applicants_page_1['items'][5]->id);

        $CFG->fullnamedisplay = 'firstname lastname';
        $selectable_applicants_page_1 = $provider->get_page(null, $page_size);
        $this->assertEquals($admin->id, $selectable_applicants_page_1['items'][0]->id);
        $this->assertEquals($workflow_manager_user->id, $selectable_applicants_page_1['items'][1]->id);
        $this->assertEquals($user4->id, $selectable_applicants_page_1['items'][2]->id);
        $this->assertEquals($user2->id, $selectable_applicants_page_1['items'][3]->id);
        $this->assertEquals($user3->id, $selectable_applicants_page_1['items'][4]->id);
        $this->assertEquals($user1->id, $selectable_applicants_page_1['items'][5]->id);

        $this->assertEquals(6, $selectable_applicants_page_1['total']);
        $this->assertCount($page_size, $selectable_applicants_page_1['items']);
    }

    public function test_filter_by_fullname() {
        $site_users = $this->create_site_users(4);

        $context = approval::get_default_category_context();
        $workflow_manager_user = self::getDataGenerator()->create_user();
        $workflow_manager_role = builder::table('role')->where('shortname', 'approvalworkflowmanager')->one();
        role_assign($workflow_manager_role->id, $workflow_manager_user->id, $context);
        $this->setUser($workflow_manager_user);

        $provider = new selectable_applicants_for_category($workflow_manager_user->id);

        $name_search = collection::new($site_users)->shift()->firstname;
        $selectable_applicants = $provider->add_filters([
            'fullname' => $name_search
        ])->get_page(null, 20);

        $this->assertNotEmpty($selectable_applicants['items']);

        /**
         * If $name_search has accented characters, it will (often) match non-accented characters in the database.
         * So we can't safely use StringContainsString here unless we convert to basic ASCII.
         */
        $name_search = $this->unaccent_name($name_search);
        foreach ($selectable_applicants['items'] as $applicant) {
            $applicant_name = $this->unaccent_name(sprintf('%s %s', $applicant->firstname, $applicant->lastname));
            $this->assertStringContainsString($name_search, $applicant_name);
        }
    }

    /**
     * @param int $number_of_users
     * @param null $data
     *
     * @return array
     */
    private function create_site_users(int $number_of_users, $data = null): array {
        $users = [];

        for ($i = 0; $i < $number_of_users; $i++) {
            $users[] = $this->getDataGenerator()->create_user($data);
        }

        return $users;
    }

}
