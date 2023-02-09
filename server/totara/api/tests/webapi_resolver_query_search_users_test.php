<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Arshad Anwer <arshad.anwer@totaralearning.com>
 * @package totara_api
 */

use core\orm\query\builder;
use core_phpunit\testcase;
use totara_core\advanced_feature;
use totara_core\feature_not_available_exception;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group totara_api
 */
class totara_api_webapi_resolver_query_search_users_testcase extends testcase {
    use webapi_phpunit_helper;

    protected const QUERY = 'totara_api_search_users';

    /**
     * @return void
     */
    public function test_api_disabled(): void {
        self::setAdminUser();
        advanced_feature::disable('api');

        self::expectException(feature_not_available_exception::class);
        $this->resolve_graphql_query(
            self::QUERY,
            [
                'input' => [
                    'tenant_id' => '',
                    'pattern' => ''
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_search_users_with_empty_pattern(): void {
        self::setAdminUser();

        $generator = self::getDataGenerator();
        for ($i = 0; $i < 20; $i++) {
            $generator->create_user(['firstname' => 'username']);
        }

        $result = $this->resolve_graphql_query(
            self::QUERY,
            [
                'input' => [
                    'pattern' => 'username'
                ]
            ]
        );

        self::assertCount(20, $result['users']);
    }

    /**
     * @return void
     */
    public function test_search_users(): void {
        self::setAdminUser();
        $generator = self::getDataGenerator();
        $user1 = $generator->create_user(['firstname' => 'Bonny', 'lastname' => 'Driver']);
        $user2 = $generator->create_user(['firstname' => 'Bam', 'lastname' => 'Trip']);
        $user3 = $generator->create_user(['firstname' => 'Bavier', 'lastname' => 'Bornham']);

        $result = $this->resolve_graphql_query(
            'totara_api_search_users',
            [
                'input' => [
                    'pattern' => 'b'
                ]
            ]
        );

        self::assertNotEmpty($result);
        $users = $result['users'];
        self::assertCount(3, $users);

        $ids = array_map(function ($user){
            return $user->id;
        }, $users);

        self::assertTrue(in_array($user1->id, $ids));
        self::assertTrue(in_array($user2->id, $ids));
        self::assertTrue(in_array($user3->id, $ids));
    }

    /**
     * @return void
     */
    public function test_search_users_with_tenant_id(): void {
        self::setAdminUser();
        $generator = self::getDataGenerator();
        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');

        $tenant_generator->enable_tenants();
        // Create tenants.
        $tenant_one = $tenant_generator->create_tenant();
        $tenant_two = $tenant_generator->create_tenant();

        // Create tenant user.
        $user1 = $generator->create_user([
            'firstname' => 'Bonny', 'lastname' => 'Driver',
            'tenantid' => $tenant_one->id, 'tenantdomainmanager' => $tenant_one->idnumber
        ]);
        $user2 = $generator->create_user([
            'firstname' => 'Bam', 'lastname' => 'Trip',
            'tenantid' => $tenant_one->id, 'tenantdomainmanager' => $tenant_one->idnumber
        ]);
        $user3 = $generator->create_user([
            'firstname' => 'Bavier', 'lastname' => 'Bornham',
            'tenantid' => $tenant_one->id, 'tenantdomainmanager' => $tenant_one->idnumber
        ]);

        // Create tenant user with tenant two.
        $user4 = $generator->create_user([
            'firstname' => 'Base', 'lastname' => 'Bornham',
            'tenantid' => $tenant_two->id, 'tenantdomainmanager' => $tenant_two->idnumber
        ]);
        $user5 = $generator->create_user([
            'firstname' => 'Battt', 'lastname' => 'Bornham',
            'tenantid' => $tenant_two->id, 'tenantdomainmanager' => $tenant_two->idnumber
        ]);

        $result = $this->resolve_graphql_query(
            'totara_api_search_users',
            [
                'input' => [
                    'tenant_id' => $tenant_one->id,
                    'pattern' => 'b'
                ]
            ]
        );

        self::assertNotEmpty($result);
        $users = $result['users'];
        self::assertCount(3, $users);

        $ids = array_map(function ($user){
            return $user->id;
        }, $users);

        self::assertTrue(in_array($user1->id, $ids));
        self::assertTrue(in_array($user2->id, $ids));
        self::assertTrue(in_array($user3->id, $ids));
        self::assertFalse(in_array($user4->id, $ids));
        self::assertFalse(in_array($user5->id, $ids));
    }

    /**
     * @return void
     */
    public function test_search_delete_and_suspend_user(): void {
        global $CFG;

        self::setAdminUser();
        $generator = self::getDataGenerator();
        $user1 = $generator->create_user(['firstname' => 'Bonny', 'lastname' => 'Driver']);
        $user2 = $generator->create_user(['firstname' => 'Bam', 'lastname' => 'Trip']);
        $user3 = $generator->create_user(['firstname' => 'Bawm', 'lastname' => 'Triwp']);

        require_once("{$CFG->dirroot}/user/lib.php");
        user_suspend_user($user3->id);
        delete_user($user2);
        $result = $this->resolve_graphql_query(
            'totara_api_search_users',
            [
                'input' => [
                    'pattern' => 'b'
                ]
            ]
        );

        self::assertNotEmpty($result);
        $users = $result['users'];
        self::assertCount(1, $users);

        $ids = array_map(function ($user){
            return $user->id;
        }, $users);

        self::assertTrue(in_array($user1->id, $ids));
        self::assertFalse(in_array($user2->id, $ids));
        self::assertFalse(in_array($user3->id, $ids));
    }

    /**
     * @return void
     */
    public function test_search_users_with_system_user(): void {
        $user = self::getDataGenerator()->create_user();

        self::setUser($user);
        self::expectException(required_capability_exception::class);
        $this->resolve_graphql_query(
            self::QUERY,
            [
                'input' => [
                    'pattern' => '   '
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_search_users_with_guest_user(): void {
        self::setGuestUser();

        self::expectException(require_login_exception::class);
        $this->resolve_graphql_query(
            self::QUERY,
            [
                'input' => [
                    'pattern' => '   '
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_search_users_with_tenant_domain_manager(): void {
        $generator = self::getDataGenerator();
        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');

        $tenant_generator->enable_tenants();
        // Create tenants.
        $tenant_one = $tenant_generator->create_tenant();
        $tenant_two = $tenant_generator->create_tenant();

        // Create tenant user.
        $user1 = $generator->create_user([
            'firstname' => 'Bonny', 'lastname' => 'Driver',
            'tenantid' => $tenant_one->id, 'tenantdomainmanager' => $tenant_one->idnumber
        ]);
        $user2 = $generator->create_user([
            'firstname' => 'Bam', 'lastname' => 'Trip',
            'tenantid' => $tenant_one->id, 'tenantdomainmanager' => $tenant_one->idnumber
        ]);
        $user3 = $generator->create_user([
            'firstname' => 'Bavier', 'lastname' => 'Bornham',
            'tenantid' => $tenant_one->id, 'tenantdomainmanager' => $tenant_one->idnumber
        ]);

        // Create tenant user with tenant two.
        $user4 = $generator->create_user([
            'firstname' => 'Base', 'lastname' => 'Bornham',
            'tenantid' => $tenant_two->id, 'tenantdomainmanager' => $tenant_two->idnumber
        ]);
        $user5 = $generator->create_user([
            'firstname' => 'Battt', 'lastname' => 'Bornham',
            'tenantid' => $tenant_two->id, 'tenantdomainmanager' => $tenant_two->idnumber
        ]);

        // Create system users.
        $user6 = $generator->create_user(['firstname' => 'Batttewqe', 'lastname' => 'Bornham']);
        $user7 = $generator->create_user(['firstname' => 'Battwqet', 'lastname' => 'Bornham']);

        $tenantdomainmanager = builder::get_db()->get_record('role', ['archetype' => 'tenantdomainmanager']);

        $context_tenant_category = context_coursecat::instance($tenant_one->categoryid);
        role_assign($tenantdomainmanager->id, $user1->id, $context_tenant_category->id);

        // Login as user1 who is a tenant domain manager
        self::setUser($user1);

        $result = $this->resolve_graphql_query(
            'totara_api_search_users',
            [
                'input' => [
                    'tenant_id' => $tenant_one->id,
                    'pattern' => 'b'
                ]
            ]
        );

        self::assertNotEmpty($result);
        $users = $result['users'];
        $ids = array_map(function ($user){
            return $user->id;
        }, $users);

        self::assertTrue(in_array($user2->id, $ids));
        self::assertTrue(in_array($user3->id, $ids));

        // Can search yourself.
        self::assertTrue(in_array($user1->id, $ids));

        // System users and user who is under tenant_two should not in the list.
        self::assertFalse(in_array($user4->id, $ids));
        self::assertFalse(in_array($user5->id, $ids));
        self::assertFalse(in_array($user6->id, $ids));
        self::assertFalse(in_array($user7->id, $ids));
    }

    /**
     * @return void
     */
    public function test_search_users_by_user_with_capability(): void {
        $generator = self::getDataGenerator();
        $role_id = $generator->create_role();
        assign_capability('totara/api:manageclients', CAP_ALLOW, $role_id, context_system::instance());
        $user = $generator->create_user();
        role_assign($role_id, $user->id, context_system::instance());

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');

        $tenant_generator->enable_tenants();
        // Create tenants.
        $tenant_one = $tenant_generator->create_tenant();
        // Create tenant user.
        $user1 = $generator->create_user([
            'firstname' => 'Bonny', 'lastname' => 'Driver',
            'tenantid' => $tenant_one->id, 'tenantdomainmanager' => $tenant_one->idnumber
        ]);
        $user2 = $generator->create_user([
            'firstname' => 'Bam', 'lastname' => 'Trip',
            'tenantid' => $tenant_one->id, 'tenantdomainmanager' => $tenant_one->idnumber
        ]);

        // Create system users.
        $user3 = $generator->create_user(['firstname' => 'Batttewqe', 'lastname' => 'Bornham']);
        $user4 = $generator->create_user(['firstname' => 'Batttewqe', 'lastname' => 'Bornham']);
        self::setUser($user);

        $result = $this->resolve_graphql_query(
            'totara_api_search_users',
            [
                'input' => [
                    'pattern' => 'b'
                ]
            ]
        );
        self::assertNotEmpty($result);
        $users = $result['users'];
        $ids = array_map(function ($user){
            return $user->id;
        }, $users);

        self::assertFalse(in_array($user1->id, $ids));
        self::assertFalse(in_array($user2->id, $ids));
        self::assertTrue(in_array($user3->id, $ids));
        self::assertTrue(in_array($user4->id, $ids));

        // Pass tenant one
        $result = $this->resolve_graphql_query(
            'totara_api_search_users',
            [
                'input' => [
                    'tenant_id' => $tenant_one->id,
                    'pattern' => 'b'
                ]
            ]
        );

        self::assertNotEmpty($result);
        $users = $result['users'];

        $ids = array_map(function ($user){
            return $user->id;
        }, $users);

        self::assertTrue(in_array($user1->id, $ids));
        self::assertTrue(in_array($user2->id, $ids));
        self::assertFalse(in_array($user3->id, $ids));
        self::assertFalse(in_array($user4->id, $ids));
    }

    /**
     * @return void
     */
    public function test_search_users_with_wild_input(): void {
        self::setAdminUser();
        $generator = self::getDataGenerator();
        $user1 = $generator->create_user(['firstname' => 'Bonny', 'lastname' => 'Driver']);
        $user2 = $generator->create_user(['firstname' => 'Bam', 'lastname' => 'Trip']);
        $user3 = $generator->create_user(['firstname' => 'Bavier', 'lastname' => 'Bornham']);

        $inputs = [
            "id=111 and 1=1",
            "<h1>aaa</h1>",
             "<script>alert('vvv');</script>",
            "%dss*?",
            "-?dee#?*"
        ];

        $results = [];
        foreach ($inputs as $input) {
            $results[] = $this->resolve_graphql_query(
                'totara_api_search_users',
                [
                    'input' => [
                        'pattern' => $input
                    ]
                ]
            );
        }

        foreach ($results as $result) {
            self::assertNotEmpty($result);
            $users = $result['users'];
            self::assertCount(0, $users);
        }
    }

    /**
     * @return void
     */
    public function test_search_users_with_tenant_participant(): void {
        self::setAdminUser();
        $generator = self::getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');

        $tenant_generator->enable_tenants();
        // Create tenants.
        $tenant_one = $tenant_generator->create_tenant();

        $p1 = $generator->create_user([
            'firstname' => 'p1', 'lastname' => 'Driver',
        ]);
        $p2 = $generator->create_user([
            'firstname' => 'p2', 'lastname' => 'Driver',
        ]);

        totara_tenant\local\util::set_user_participation($p1->id, [$tenant_one->id]);
        totara_tenant\local\util::set_user_participation($p2->id, [$tenant_one->id]);

        // Create tenant user.
        $user1 = $generator->create_user([
            'firstname' => 'Bonny', 'lastname' => 'Driver',
            'tenantid' => $tenant_one->id, 'tenantdomainmanager' => $tenant_one->idnumber
        ]);
        $user2 = $generator->create_user([
            'firstname' => 'Bam', 'lastname' => 'Trip',
            'tenantid' => $tenant_one->id, 'tenantdomainmanager' => $tenant_one->idnumber
        ]);
        $user3 = $generator->create_user([
            'firstname' => 'Bavier', 'lastname' => 'Bornham',
            'tenantid' => $tenant_one->id, 'tenantdomainmanager' => $tenant_one->idnumber
        ]);

        $result = $this->resolve_graphql_query(
            'totara_api_search_users',
            [
                'input' => [
                    'tenant_id' => $tenant_one->id,
                    'pattern' => ''
                ]
            ]
        );

        self::assertNotEmpty($result);
        $users = $result['users'];
        self::assertCount(3, $users);

        $ids = array_map(function ($user){
            return $user->id;
        }, $users);

        self::assertTrue(in_array($user1->id, $ids));
        self::assertTrue(in_array($user2->id, $ids));
        self::assertTrue(in_array($user3->id, $ids));

        // tenant paticipants should not in the list.
        self::assertFalse(in_array($p1->id, $ids));
        self::assertFalse(in_array($p2->id, $ids));
    }

    /**
     * @return void
     */
    public function test_search_users_without_siteadmin(): void {
        global $CFG;

        self::setAdminUser();
        $gen = self::getDataGenerator();
        $admin_user = $gen->create_user(['idnumber' => 'admin']);
        $user1 = $gen->create_user();
        $user2 = $gen->create_user();

        // Assign user to siteadmin role.
        $CFG->siteadmins = $CFG->siteadmins . ',' . $admin_user->id;
        self::assertTrue(is_siteadmin($admin_user->id));
        $result = $this->resolve_graphql_query(
            'totara_api_search_users',
            [
                'input' => [
                    'pattern' => ''
                ]
            ]
        );

        self::assertNotEmpty($result);
        $users = $result['users'];
        self::assertCount(2, $users);

        $ids = array_map(function ($user){
            return $user->id;
        }, $users);

        self::assertTrue(in_array($user1->id, $ids));
        self::assertTrue(in_array($user2->id, $ids));
    }

    /**
     * @return void
     */
    public function test_view_user_profile_with_disabled_engage(): void {
        //Disable engage.
        advanced_feature::disable('engage_resources');
        self::assertTrue(advanced_feature::is_disabled('engage_resources'));

        $generator = self::getDataGenerator();
        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');

        $tenant_generator->enable_tenants();

        // Create tenants.
        $tenant_one = $tenant_generator->create_tenant();
        // Create tenant user.
        $user1 = $generator->create_user([
            'tenantid' => $tenant_one->id, 'tenantdomainmanager' => $tenant_one->idnumber
        ]);
        $generator->create_user(['tenantid' => $tenant_one->id]);

        $tenantdomainmanager = builder::get_db()->get_record('role', ['archetype' => 'tenantdomainmanager']);
        $context_tenant_category = context_coursecat::instance($tenant_one->categoryid);
        role_assign($tenantdomainmanager->id, $user1->id, $context_tenant_category->id);

        // Login as user1 who is a tenant domain manager
        self::setUser($user1);
        $result = $this->resolve_graphql_query(
            'totara_api_search_users',
            [
                'input' => [
                    'tenant_id' => $tenant_one->id,
                    'pattern' => ''
                ]
            ]
        );

        $users = $result['users'];
        self::assertCount(2, $users);
        foreach ($users as $user) {
            // Tenant domain manager can view profile
            $fullname = $this->resolve_graphql_type('core_user','fullname', $user);
            self::assertNotEmpty($fullname);
        }
    }
}