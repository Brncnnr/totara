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
 * @author Angela Kuznetsova <angela.kuznetsova@totaralearning.com>
 * @package core
 */

use core\entity\user;
use core_phpunit\testcase;
use core_user\exception\delete_user_exception;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group core_user
 */
class core_webapi_resolver_mutation_user_delete_user_testcase extends testcase {
    use webapi_phpunit_helper;

    private const MUTATION = 'core_user_delete_user';

    /**
     * @return void
     */
    public function test_delete_user_with_success(): void {
        global $CFG;

        $CFG->allowuserthemes = 1;
        $generator = $this->getDataGenerator();
        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $this->setAdminUser();

        $tenant1 = $tenant_generator->create_tenant();
        $user = self::getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $tenant_generator->migrate_user_to_tenant($user->id, $tenant1->id);
        $user->tenantid = $tenant1->id;

        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'idnumber' => $user->idnumber,
                ]
            ]
        );

        self::assertNotEmpty($result);
        self::assertEquals($user->id, $result['user_id']);
        $user = new user($result['user_id']);
        self::assertEquals(1, $user->deleted);
    }

    /**
     * @return void
     */
    public function test_delete_user_without_passing_required_params(): void {
        self::setAdminUser();

        self::expectExceptionMessage("Required parameter 'target_user' not being passed");
        self::expectException(coding_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'user1',
                    'firstname' => 'first name',
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_delete_user_with_valid_cap(): void {
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $user_for_deletion = $generator->create_user();
        $role_id = self::getDataGenerator()->create_role();
        assign_capability('moodle/user:delete', CAP_ALLOW, $role_id, context_system::instance());
        role_assign($role_id, $user->id, context_system::instance());

        self::setUser($user);
        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $user_for_deletion->id,
                    'username' => $user_for_deletion->username,
                    'email' => $user_for_deletion->email,
                    'idnumber' => $user_for_deletion->idnumber
                ]
            ]
        );

        self::assertNotEmpty($result);
        self::assertEquals($user_for_deletion->id, $result['user_id']);
        $deleted_user = new user($result['user_id']);
        self::assertEquals(1, $deleted_user->deleted);
    }

    /**
     * @return void
     */
    public function test_delete_user_tenant(): void {
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');

        $tenant_generator->enable_tenants();

        $tenant1 = $tenant_generator->create_tenant();
        $tenant2 = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user->id, $tenant1->id);
        $user->tenantid = $tenant1->id;

        $roles = get_archetype_roles('apiuser');
        $role = reset($roles);
        role_assign($role->id, $user->id, context_tenant::instance($tenant1->id));

        self::setUser($user);
        // Delete user from tenant user
        $user_for_deletion2 = $generator->create_user();
        $tenant_generator->migrate_user_to_tenant($user_for_deletion2->id, $tenant1->id);
        $user_for_deletion2->tenantid = $tenant1->id;

        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $user_for_deletion2->id,
                    'username' => $user_for_deletion2->username,
                    'email' => $user_for_deletion2->email,
                    'idnumber' => $user_for_deletion2->idnumber
                ]
            ]
        );

        self::assertNotEmpty($result);
        self::assertEquals($user_for_deletion2->id, $result['user_id']);
        $deleted_user = new user($result['user_id']);
        self::assertEquals(1, $deleted_user->deleted);
        self::assertEquals($tenant1->id, $deleted_user->tenantid);

        // But you cannot delete user from other tenant
        self::expectExceptionMessage('There was a problem finding a single user record match or you do not have sufficient capabilities.');
        self::expectException(delete_user_exception::class);
        $user_for_deletion3 = $generator->create_user();
        $tenant_generator->migrate_user_to_tenant($user_for_deletion3->id, $tenant2->id);
        $user_for_deletion3->tenantid = $tenant2->id;

        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $user_for_deletion3->id,
                    'username' => $user_for_deletion3->username,
                    'email' => $user_for_deletion3->email,
                    'idnumber' => $user_for_deletion3->idnumber
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_delete_system_user_tenant_api_user(): void {
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $user_for_deletion2 = $generator->create_user();
        $user_for_deletion3 = $generator->create_user();
        $roles = get_archetype_roles('apiuser');
        $role = reset($roles);
        role_assign($role->id, $user->id, context_system::instance());
        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');

        $tenant_generator->enable_tenants();

        $tenant1 = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user->id, $tenant1->id);
        $user->tenantid = $tenant1->id;

        self::setUser($user);
        // Delete user from tenant user
        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $user_for_deletion2->id,
                    'username' => $user_for_deletion2->username,
                    'email' => $user_for_deletion2->email,
                    'idnumber' => $user_for_deletion2->idnumber
                ]
            ]
        );

        self::assertNotEmpty($result);
        self::assertEquals($user_for_deletion2->id, $result['user_id']);
        $deleted_user = new user($result['user_id']);
        self::assertEquals(1, $deleted_user->deleted);
        self::assertEquals(null, $deleted_user->tenantid);

        set_config('tenantsisolated', 1);
        try {
            $this->resolve_graphql_mutation(
                self::MUTATION,
                [
                    'target_user' => [
                        'id' => $user_for_deletion3->id,
                        'username' => $user_for_deletion3->username,
                        'email' => $user_for_deletion3->email,
                        'idnumber' => $user_for_deletion3->idnumber
                    ]
                ]
            );
            $this->fail('delete_user_exception expected');
        } catch (delete_user_exception $e) {
            $this->assertStringContainsString('There was a problem finding a single user record match or you do not have sufficient capabilities.', $e->getMessage());
        }
    }

    /**
     * @return void
     */
    public function test_delete_user_by_authenticate_user(): void {
        $user = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        self::setUser($user);

        self::expectException(delete_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $user2->id,
                    'username' => $user2->username,
                    'email' => $user2->email,
                    'idnumber' => $user2->idnumber
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_delete_user_with_double_email(): void {
        global $CFG;
        $CFG->allowaccountssameemail = 1;
        $user = self::getDataGenerator()->create_user(['email' => 'login@example.com']);
        self::getDataGenerator()->create_user(['email' => 'login@example.com']);


        self::expectExceptionMessage('There was a problem finding a single user record match or you do not have sufficient capabilities.');
        self::expectException(delete_user_exception::class);
        self::setAdminUser();
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'email' => $user->email,
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_delete_user_with_record_not_found(): void {
        $user = self::getDataGenerator()->create_user();
        self::setAdminUser();

        self::expectExceptionMessage("There was a problem finding a single user record match or you do not have sufficient capabilities.");
        self::expectException(delete_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $user->id,
                    'username' => 'username',
                    'email' => 'email',
                    'idnumber' => $user->idnumber
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_delete_user_without_passing_param(): void {
        self::setAdminUser();

        self::expectExceptionMessage("There was a problem finding a single user record match or you do not have sufficient capabilities.");
        self::expectException(delete_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => []
            ]
        );
    }

    /**
     * @return void
     */
    public function test_delete_guest(): void {
        $user = guest_user();

        self::expectExceptionMessage('For deleting a user: Guest user can not be specified for User.');
        self::expectException(delete_user_exception::class);
        self::setAdminUser();
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'idnumber' => $user->idnumber
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_delete_admin(): void {
        $user = get_admin();

        $apiuser = self::getDataGenerator()->create_user(['password' => 'Sjewnwicnqn.', 'email' => 'www@example.com']);
        $roles = get_archetype_roles('apiuser');
        $role = reset($roles);
        role_assign($role->id, $apiuser->id, context_system::instance());
        self::setUser($apiuser);

        self::expectExceptionMessage('For deleting a user: Admin user can not be specified for User.');
        self::expectException(delete_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'idnumber' => $user->idnumber
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_delete_deleted_user(): void {
        $user = self::getDataGenerator()->create_user(['deleted' => 1]);

        self::expectExceptionMessage('There was a problem finding a single user record match or you do not have sufficient capabilities.');
        self::expectException (delete_user_exception::class);
        self::setAdminUser();
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'idnumber' => $user->idnumber
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_api_user_cannot_delete_themself(): void {
        $apiuser = self::getDataGenerator()->create_user(['password' => 'Sjewnwicnqn.', 'email' => 'www@example.com']);
        $roles = get_archetype_roles('apiuser');
        $role = reset($roles);
        role_assign($role->id, $apiuser->id, context_system::instance());

        // Login as api user
        self::setUser($apiuser);

        self::expectException(delete_user_exception::class);
        self::expectExceptionMessage('A service account user is not allowed to delete itself when making a request.');

        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $apiuser->id
                ]
            ]
        );
    }
}