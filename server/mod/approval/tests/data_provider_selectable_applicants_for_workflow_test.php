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

use container_approval\approval;
use core\collection;
use core\entity\user;
use core\orm\query\builder;
use core_phpunit\testcase;
use mod_approval\data_provider\user\selectable_applicants_for_workflow;
use mod_approval\entity\workflow\workflow;
use mod_approval\model\assignment\assignment_type;
use mod_approval\model\status;
use mod_approval\model\workflow\workflow as workflow_model;
use mod_approval\testing\approval_workflow_test_setup;
use mod_approval\testing\assignment_generator_object;
use totara_job\job_assignment;

/**
 * @coversDefaultClass mod_approval\data_provider\user\selectable_applicants_for_workflow
 *
 * @group approval_workflow
 */
class mod_approval_data_provider_selectable_applicants_for_workflow_testcase extends testcase {

    use approval_workflow_test_setup;

    public function test_available_applicant_in_a_tenant() {
        $data = $this->setup_cohort_workflow_with_multi_tenant_members();

        // Site Manager selecting applicants.
        $site_manager = $data['site']['manager'];

        $provider = new selectable_applicants_for_workflow($data['workflow'], $site_manager->id);
        $selectable_applicants = $provider->get_page(null, 20);
        $this->assertEquals(12, $selectable_applicants['total']);

        $all_users = array_merge(
            $data['site']['users'],
            $data['tenants']['1']['users'],
            $data['tenants']['2']['users']
        );
        $all_user_ids = array_map(function($user){
            return $user->id;
        }, $all_users);
        $selectable_applicant_ids = array_map(function($user) {
            return $user->id;
        }, $selectable_applicants['items']);
        $this->assertEqualsCanonicalizing($all_user_ids, $selectable_applicant_ids);

        // Tenant 1 manager selecting applicants.
        $tenant_1_manager = $data['tenants']['1']['manager'];

        $provider = new selectable_applicants_for_workflow($data['workflow'], $tenant_1_manager->id);
        $tenant_1_applicants = $provider->get_page(null, 20);
        $this->assertEquals(5, $tenant_1_applicants['total']);
        $tenant_1_user_ids = array_map(function($user) {
            return $user->id;
        }, $data['tenants']['1']['users']);

        $tenant_1_applicant_ids = array_map(function($user) {
            return $user->id;
        }, $tenant_1_applicants['items']);

        $this->assertEqualsCanonicalizing($tenant_1_user_ids, $tenant_1_applicant_ids);
    }

    public function test_available_applicant_in_site_with_isolation_enabled_limits_to_site_users() {
        $data = $this->setup_cohort_workflow_with_multi_tenant_members();
        set_config('tenantsisolated', 1);

        // Site Manager selecting applicants.
        $site_manager = $data['site']['manager'];

        $provider = new selectable_applicants_for_workflow($data['workflow'], $site_manager->id);
        $selectable_applicants = $provider->get_page(null, 20);
        $this->assertEquals(4, $selectable_applicants['total']);

        $site_user_ids = array_map(function($user){
            return $user->id;
        }, $data['site']['users']);
        $selectable_applicant_ids = array_map(function($user) {
            return $user->id;
        }, $selectable_applicants['items']);
        $this->assertEqualsCanonicalizing($site_user_ids, $selectable_applicant_ids);
    }

    /**
     * @return array
     */
    private function setup_cohort_workflow_with_multi_tenant_members() {
        $this->setAdminUser();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $this->getDataGenerator()->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $context = approval::get_default_category_context();

        $workflow = $this->generator()->create_simple_request_workflow("Selectable applicants");

        $cohort_id = $this->getDataGenerator()->create_cohort()->id;
        $assignment_go = new assignment_generator_object(
            $workflow->course_id,
            assignment_type\cohort::get_code(),
            $cohort_id
        );
        $assignment_go->is_default = true;
        $assignment_go->status = status::ACTIVE;
        $this->generator()->create_assignment($assignment_go);

        // Setup site users & manager
        $site_users = $this->create_site_users(4);
        $site_manager = collection::new($site_users)->first();

        // Setup system workflow admin.
        $workflow_manager_role = builder::table('role')->where('shortname', 'approvalworkflowmanager')->one();
        role_assign($workflow_manager_role->id, $site_manager->id, $context);

        $tenantdomainmanager_role = builder::table('role')
            ->where('shortname', 'tenantdomainmanager')
            ->one();

        // Setup tenant 1 manager and users.
        $tenant_1 = $tenant_generator->create_tenant();
        $tenant_1_users = $this->create_site_users(5, ['tenantid' => $tenant_1->id]);
        $tenant_1_manager = collection::new($tenant_1_users)->first();
        role_assign($tenantdomainmanager_role->id, $tenant_1_manager->id, $context);

        // Setup tenant 2 manager and users.
        $tenant_2 = $tenant_generator->create_tenant();
        $tenant_2_users = $this->create_site_users(3, ['tenantid' => $tenant_2->id]);
        $tenant_2_manager = collection::new($tenant_2_users)->first();
        role_assign($tenantdomainmanager_role->id, $tenant_2_manager->id, $context);

        foreach (array_merge($site_users, $tenant_1_users, $tenant_2_users) as $user) {
            cohort_add_member($cohort_id, $user->id);
        }


        return [
            'workflow' => workflow_model::load_by_entity($workflow),
            'site' => [
                'users' => $site_users,
                'manager'=> $site_manager,
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

    public function test_available_applicants_from_workflow_with_organisation_assignments() {
        $organisation_workflow_data = $this->generate_data(assignment_type\organisation::get_code(), 5);

        // Extra site users.
        $this->create_site_users(4);

        $context = approval::get_default_category_context();
        $workflow_manager_user = self::getDataGenerator()->create_user();
        $workflow_manager_role = builder::table('role')->where('shortname', 'approvalworkflowmanager')->one();
        role_assign($workflow_manager_role->id, $workflow_manager_user->id, $context);
        $this->setUser($workflow_manager_user);

        $provider = new selectable_applicants_for_workflow($organisation_workflow_data['workflow'], $workflow_manager_user->id);
        $selectable_applicants = $provider->get_page(null, 20);

        $applicant_ids = collection::new($selectable_applicants['items'])->pluck('id');

        $organisation_users = array_merge(
            $organisation_workflow_data['assignment_data']['default']['users'],
            $organisation_workflow_data['assignment_data']['override_1']['users'],
            $organisation_workflow_data['assignment_data']['override_2']['users'],
        );
        $organisation_user_ids = collection::new($organisation_users)->pluck('id');

        $this->assertEquals(15, $selectable_applicants['total']);
        $this->assertEqualsCanonicalizing($organisation_user_ids, $applicant_ids);
    }

    public function test_available_applicants_from_workflow_with_position_assignments() {
        $position_workflow_data = $this->generate_data(assignment_type\position::get_code(), 5);

        // Extra site users.
        $this->create_site_users(4);

        $context = approval::get_default_category_context();
        $workflow_manager_user = self::getDataGenerator()->create_user();
        $workflow_manager_role = builder::table('role')->where('shortname', 'approvalworkflowmanager')->one();
        role_assign($workflow_manager_role->id, $workflow_manager_user->id, $context);
        $this->setUser($workflow_manager_user);

        $provider = new selectable_applicants_for_workflow($position_workflow_data['workflow'], $workflow_manager_user->id);
        $selectable_applicants = $provider->get_page(null, 20);

        $applicant_ids = collection::new($selectable_applicants['items'])->pluck('id');

        $position_users = array_merge(
            $position_workflow_data['assignment_data']['default']['users'],
            $position_workflow_data['assignment_data']['override_1']['users'],
            $position_workflow_data['assignment_data']['override_2']['users'],
        );
        $position_user_ids = collection::new($position_users)->pluck('id');

        $this->assertEquals(15, $selectable_applicants['total']);
        $this->assertEqualsCanonicalizing($position_user_ids, $applicant_ids);
    }

    public function test_available_applicants_from_workflow_with_cohort_assignments() {
        $cohort_workflow_data = $this->generate_data(assignment_type\cohort::get_code(), 5);
        $this->generate_data(assignment_type\organisation::get_code());

        // Extra site users.
        $this->create_site_users(4);

        $context = approval::get_default_category_context();
        $workflow_manager_user = self::getDataGenerator()->create_user();
        $workflow_manager_role = builder::table('role')->where('shortname', 'approvalworkflowmanager')->one();
        role_assign($workflow_manager_role->id, $workflow_manager_user->id, $context);
        $this->setUser($workflow_manager_user);

        $provider = new selectable_applicants_for_workflow($cohort_workflow_data['workflow'], $workflow_manager_user->id);
        $selectable_applicants = $provider->get_page(null, 20);

        $applicant_ids = collection::new($selectable_applicants['items'])->pluck('id');

        $all_cohort_users = array_merge(
            $cohort_workflow_data['assignment_data']['default']['users'],
            $cohort_workflow_data['assignment_data']['override_1']['users'],
            $cohort_workflow_data['assignment_data']['override_2']['users'],
        );

        $cohort_user_ids = collection::new($all_cohort_users)->pluck('id');

        $this->assertEquals(15, $selectable_applicants['total']);
        $this->assertEqualsCanonicalizing($cohort_user_ids, $applicant_ids);
    }

    public function test_available_applicants_with_create_any_capability() {
        $cohort_workflow_data = $this->generate_data(assignment_type\cohort::get_code(), 5);
        $this->generate_data(assignment_type\organisation::get_code());

        // Extra site users.
        $this->create_site_users(4);

        $context = approval::get_default_category_context();
        $user = self::getDataGenerator()->create_user();
        $user_role = builder::table('role')->where('shortname', 'user')->one();
        role_assign($user_role->id, $user->id, $context);
        assign_capability('mod/approval:create_application_any', CAP_ALLOW, $user_role->id, $context, true);
        $this->setUser($user);

        $provider = new selectable_applicants_for_workflow($cohort_workflow_data['workflow'], $user->id);
        $selectable_applicants = $provider->get_page(null, 20);

        $applicant_ids = collection::new($selectable_applicants['items'])->pluck('id');

        $all_assignment_users = array_merge(
            $cohort_workflow_data['assignment_data']['default']['users'],
            $cohort_workflow_data['assignment_data']['override_1']['users'],
            $cohort_workflow_data['assignment_data']['override_2']['users'],
        );
        $workflow_users = collection::new($all_assignment_users)->pluck('id');

        $this->assertEquals(15, $selectable_applicants['total']);
        $this->assertEqualsCanonicalizing($workflow_users, $applicant_ids);
    }

    public function test_available_applicants_with_create_user_capability() {
        $organisation_workflow_data = $this->generate_data(assignment_type\organisation::get_code(), 5);
        $this->generate_data(assignment_type\organisation::get_code());
        $this->create_site_users(4);

        $staff_manager = self::getDataGenerator()->create_user();
        $manager_job_assignment = job_assignment::create_default($staff_manager->id, [
            'organisationid' => $organisation_workflow_data['assignment_data']['default']['identifier']
        ]);
        $staff_manager_role = builder::table('role')->where('shortname', 'staffmanager')->one();

        // Grant capability to create for two users in default assignment.
        $default_assignment_users = collection::new($organisation_workflow_data['assignment_data']['default']['users']);
        $eligible_user_ids = [];

        for ($i = 0; $i < 2; $i++) {
            $assignment_user = $default_assignment_users->shift();
            $eligible_user_ids[] = $assignment_user->id;
            $context = context_user::instance($assignment_user->id);
            // create two user job assignments one for default assignment organisation.
            job_assignment::create([
                'userid' => $assignment_user->id,
                'idnumber' => uniqid(),
                'managerjaid' => $manager_job_assignment->id,
                'organisationid' => $organisation_workflow_data['assignment_data']['default']['identifier'],
            ]);

            // The other for override assignment organisation.
            job_assignment::create([
                'userid' => $assignment_user->id,
                'idnumber' => uniqid(),
                'managerjaid' => $manager_job_assignment->id,
                'organisationid' => $organisation_workflow_data['assignment_data']['override_1']['identifier'],
            ]);
            role_assign($staff_manager_role->id, $staff_manager->id, $context);
            assign_capability('mod/approval:create_application_user', CAP_ALLOW, $staff_manager_role->id, $context, true);
        }

        $this->setUser($staff_manager);

        $provider = new selectable_applicants_for_workflow($organisation_workflow_data['workflow'], $staff_manager->id);
        $selectable_applicants = $provider->get_page(null, 20);

        $this->assertEquals(2, $selectable_applicants['total']);
        $applicant_ids = array_map(function($user) {
            return $user->id;
        }, $selectable_applicants['items']);
        $this->assertEqualsCanonicalizing($eligible_user_ids, $applicant_ids);
    }

    /**
     * Tests it filters out guest, deleted and suspended users.
     */
    public function test_filter_out_invalid_users() {
        global $CFG;

        $cohort_workflow_data = $this->generate_data(assignment_type\cohort::get_code(), 5);
        $this->generate_data(assignment_type\organisation::get_code());

        // Extra site users.
        $this->create_site_users(4);

        $all_assignment_users = array_merge(
            $cohort_workflow_data['assignment_data']['default']['users'],
            $cohort_workflow_data['assignment_data']['override_1']['users'],
            $cohort_workflow_data['assignment_data']['override_2']['users'],
        );
        $workflow_users = collection::new($all_assignment_users);
        $user_to_delete = $workflow_users->shift();
        user::repository()
            ->where('id', $user_to_delete->id)
            ->update([
                'deleted' => 1
            ]);

        $user_to_suspend = $workflow_users->shift();
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

        $provider = new selectable_applicants_for_workflow($cohort_workflow_data['workflow'], $workflow_manager_user->id);
        $selectable_applicants = $provider->get_page(null, 20);

        $applicant_ids = collection::new($selectable_applicants['items'])->pluck('id');
        $cohort_user_ids = collection::new($all_assignment_users)
            ->filter(function ($user) use ($user_to_delete, $user_to_suspend) {
                return !in_array($user->id, [$user_to_suspend->id, $user_to_delete->id]);
            })->pluck('id');

        // 5 users per assignment(1 default, 2 overrides).
        $this->assertEquals(13, $selectable_applicants['total']);
        $this->assertEqualsCanonicalizing($cohort_user_ids, $applicant_ids);
        $this->assertNotContains($CFG->siteguest, $applicant_ids);
        $this->assertNotContains($user_to_delete->id, $applicant_ids);
        $this->assertNotContains($user_to_suspend->id, $applicant_ids);
    }

    /**
     * Test approval_workflow_test_setup trait's unaccent_name() method.
     *
     * Here rather than in the trait because a) it was used here first and b) if it was in the trait it would get
     * run in every testcase that used it.
     */
    public function test_local_unaccent_name_method() {
        $tests = [
            'Němcová' => 'Nemcova',
            'Lukas' => 'Lukas',
            '陽菜' => '陽菜',
            'Лебедева' => 'Лебедева',
            'Łukáš' => 'Lukas',
            'Weiß' => 'Weiss',
            'Göthe' => 'Gothe',
        ];

        foreach ($tests as $input => $expected) {
            $this->assertEquals($expected, $this->unaccent_name($input));
        }
    }

    public function test_filter_by_fullname() {
        $cohort_workflow_data = $this->generate_data(assignment_type\cohort::get_code(), 5);
        $this->generate_data(assignment_type\organisation::get_code());
        $this->create_site_users(4);

        $context = approval::get_default_category_context();
        $workflow_manager_user = self::getDataGenerator()->create_user();
        $workflow_manager_role = builder::table('role')->where('shortname', 'approvalworkflowmanager')->one();
        role_assign($workflow_manager_role->id, $workflow_manager_user->id, $context);
        $this->setUser($workflow_manager_user);

        $provider = new selectable_applicants_for_workflow($cohort_workflow_data['workflow'], $workflow_manager_user->id);

        $override_1_users = $cohort_workflow_data['assignment_data']['override_1']['users'];
        $name_search = collection::new($override_1_users)->shift()->firstname;
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
     * Test fullname filter using a specific example that is known to confuse ::test_filter_by_fullname() if an
     * accent-insensitive-supporting database is used.
     */
    public function test_filter_by_fullname_with_specific_example() {
        global $DB;

        $cohort_workflow_data = $this->generate_data(assignment_type\cohort::get_code(), 5);
        $this->generate_data(assignment_type\organisation::get_code());
        $this->create_site_users(4);

        // Create 'Nukas Schmidt' and assign to default assignment cohort.
        $user_lukas = $this->getDataGenerator()->create_user(['firstname' => 'Nukas', 'lastname' => 'Schmidt']);
        cohort_add_member($cohort_workflow_data['assignment_data']['default']['identifier'], $user_lukas->id);

        $context = approval::get_default_category_context();
        $workflow_manager_user = self::getDataGenerator()->create_user();
        $workflow_manager_role = builder::table('role')->where('shortname', 'approvalworkflowmanager')->one();
        role_assign($workflow_manager_role->id, $workflow_manager_user->id, $context);
        $this->setUser($workflow_manager_user);

        $provider = new selectable_applicants_for_workflow($cohort_workflow_data['workflow'], $workflow_manager_user->id);

        // Using Nukáš so that auto-generated user Lukáš doesn't break the test.
        $name_search = 'Nukáš';
        $selectable_applicants = $provider->add_filters([
            'fullname' => $name_search
        ])->get_page(null, 20);

        // Postgres is the only DB that does not support unaccented collation in LIKE, unless the unaccent() extension
        //  is present. See \pgsql_native_moodle_database::is_fts_accent_sensitive() for insight.
        if ($DB->get_dbfamily() == 'postgres' && $DB->is_fts_accent_sensitive()) {
            $this->assertEmpty($selectable_applicants['items']);
        } else {
            $this->assertNotEmpty($selectable_applicants['items']);
        }

        $name_search = $this->unaccent_name($name_search);
        foreach ($selectable_applicants['items'] as $applicant) {
            $applicant_name = $this->unaccent_name(sprintf('%s %s', $applicant->firstname, $applicant->lastname));
            $this->assertStringContainsString($name_search, $applicant_name);
        }
    }

    public function test_limit_and_cursor_on_available_applicants() {
        $cohort_workflow_data = $this->generate_data(assignment_type\cohort::get_code(), 3);
        $this->generate_data(assignment_type\organisation::get_code());
        $this->create_site_users(4);

        $context = approval::get_default_category_context();
        $workflow_manager_user = self::getDataGenerator()->create_user();
        $workflow_manager_role = builder::table('role')->where('shortname', 'approvalworkflowmanager')->one();
        role_assign($workflow_manager_role->id, $workflow_manager_user->id, $context);
        $this->setUser($workflow_manager_user);

        $provider = new selectable_applicants_for_workflow($cohort_workflow_data['workflow'], $workflow_manager_user->id);
        $page_size = 4;
        $selectable_applicants_page_1 = $provider->get_page(null, $page_size);

        // 3 assignments with 3 users each.
        $this->assertEquals(9, $selectable_applicants_page_1['total']);
        $this->assertCount($page_size, $selectable_applicants_page_1['items']);

        $selectable_applicants_page_2 = $provider->get_page($selectable_applicants_page_1['next_cursor'], $page_size);

        // 3 assignments with 3 users each.
        $this->assertEquals(9, $selectable_applicants_page_2['total']);
        $this->assertCount($page_size, $selectable_applicants_page_2['items']);
        $this->assertNotEqualsCanonicalizing(
            $selectable_applicants_page_1['items'],
            $selectable_applicants_page_2['items'],
            'Next page items should not be same as previous'
        );
    }

    /**
     * @param int $assignment_type
     * @param int $users_per_assignment
     *
     * @return array
     */
    private function generate_data(int $assignment_type, int $users_per_assignment = 2): array {
        $this->setAdminUser();
        $workflow = $this->generator()->create_simple_request_workflow("Selectable applicants");
        $assignment_data = $this->create_workflow_assignments($assignment_type, $users_per_assignment, $workflow);

        return [
            'workflow' => workflow_model::load_by_entity($workflow),
            'assignment_data' => $assignment_data,
        ];
    }

    /**
     * @param int $number_of_users
     * @param null $data
     * @return array
     */
    private function create_site_users(int $number_of_users, $data = null): array {
        $users = [];

        for ($i = 0; $i < $number_of_users; $i++) {
            $users[] = $this->getDataGenerator()->create_user($data);
        }

        return $users;
    }

    /**
     * Creates a default assignment and two override assignments for a workflow.
     *
     * @param int $assignment_type
     * @param int $number_of_users
     * @param workflow $workflow
     * @return array
     */
    private function create_workflow_assignments(int $assignment_type, int $number_of_users, workflow $workflow): array {
        switch ($assignment_type) {
            case assignment_type\organisation::get_code():
                $assignment = $this->setup_organisation_assignments($number_of_users);
                break;
            case assignment_type\position::get_code():
                $assignment = $this->setup_position_assignments($number_of_users);
                break;
            case assignment_type\cohort::get_code():
                $assignment = $this->setup_cohort_assignments($number_of_users);
                break;
            default:
                throw new coding_exception('Unknown type provided');
                break;
        }

        // create default assignment.
        $assignment_go = new assignment_generator_object(
            $workflow->course_id,
            $assignment_type,
            $assignment['default']['identifier']
        );
        $assignment_go->is_default = true;
        $assignment_go->status = status::ACTIVE;
        $this->generator()->create_assignment($assignment_go);

        // Create override 1 & 2.
        foreach ([1, 2] as $id) {
            $key = "override_$id";
            $assignment_go = new assignment_generator_object(
                $workflow->course_id,
                $assignment_type,
                $assignment[$key]['identifier']
            );
            $assignment_go->status = status::ACTIVE;
            $this->generator()->create_assignment($assignment_go);
        }

        return $assignment;
    }

    /**
     * @param int $number_of_users
     *
     * @return array[]
     */
    private function setup_cohort_assignments(int $number_of_users): array {
        $default_cohort = $this->getDataGenerator()->create_cohort();
        $override_cohort_1 = $this->getDataGenerator()->create_cohort();
        $override_cohort_2 = $this->getDataGenerator()->create_cohort();

        return [
            'default' => [
                'identifier' => $default_cohort->id,
                // generate users assigned to cohort.
                'users' => $this->create_users_in_cohort($number_of_users, $default_cohort->id)
            ],
            'override_1' => [
                'identifier' => $override_cohort_1->id,
                // generate users assigned to cohort.
                'users' => $this->create_users_in_cohort($number_of_users, $override_cohort_1->id)
            ],
            'override_2' => [
                'identifier' => $override_cohort_2->id,
                // generate users assigned to cohort.
                'users' => $this->create_users_in_cohort($number_of_users, $override_cohort_2->id)
            ],
        ];
    }

    /**
     * @param int $number_of_users
     *
     * @return array[]
     */
    private function setup_position_assignments(int $number_of_users): array {
        $framework = $this->generate_pos_hierarchy();

        return [
            'default' => [
                'identifier' => $framework->division->id,
                // generate users assigned to position.
                'users' => $this->create_users_with_job_assignment(
                    $number_of_users,
                    [
                        'positionid' => $framework->division->id
                    ]
                )
            ],
            'override_1' => [
                'identifier' => $framework->division->position_a->id,
                // generate users assigned to position.
                'users' => $this->create_users_with_job_assignment(
                    $number_of_users,
                    [
                        'positionid' => $framework->division->position_a->id
                    ]
                )
            ],
            'override_2' => [
                'identifier' => $framework->division->position_a->grade_a->id,
                // generate users assigned to position.
                'users' => $this->create_users_with_job_assignment(
                    $number_of_users,
                    [
                        'positionid' => $framework->division->position_a->grade_a->id
                    ]
                )
            ]
        ];
    }

    /**
     * @param int $number_of_users
     *
     * @return array[]
     */
    private function setup_organisation_assignments(int $number_of_users): array {
        $framework = $this->generate_org_hierarchy();
        return [
            'default' => [
                'identifier' => $framework->agency->id,
                // generate users assigned to organisation.
                'users' => $this->create_users_with_job_assignment(
                    $number_of_users,
                    [
                        'organisationid' => $framework->agency->id
                    ]
                )
            ],
            'override_1' => [
                'identifier' => $framework->agency->subagency_a->id,
                // generate users assigned to organisation.
                'users' => $this->create_users_with_job_assignment(
                    $number_of_users,
                    [
                        'organisationid' => $framework->agency->subagency_a->id
                    ]
                )
            ],
            'override_2' => [
                'identifier' => $framework->agency->subagency_a->program_a->id,
                // generate users assigned to organisation.
                'users' => $this->create_users_with_job_assignment(
                    $number_of_users,
                    [
                        'organisationid' => $framework->agency->subagency_a->program_a->id
                    ]
                )
            ]
        ];
    }

    /**
     * @param int $number_of_users
     * @param array $job_assignment_data
     * @return array
     */
    private function create_users_with_job_assignment(int $number_of_users, array $job_assignment_data): array {
        $users = [];

        for ($i = 0; $i < $number_of_users; $i++) {
            $users[] = $this->getDataGenerator()->create_user();
        }

        foreach ($users as $user) {
            job_assignment::create_default(
                $user->id,
                $job_assignment_data
            );
        }

        return $users;
    }

    /**
     * @param int $number_of_users
     * @param int $cohort_id
     * @return array
     */
    private function create_users_in_cohort(int $number_of_users, int $cohort_id): array {
        $users = [];

        for ($i = 0; $i < $number_of_users; $i++) {
            $users[] = $this->getDataGenerator()->create_user();
        }

        foreach ($users as $user) {
            cohort_add_member($cohort_id, $user->id);
        }

        return $users;
    }
}
