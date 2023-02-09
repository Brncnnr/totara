<?php
/**
 * This file is part of Totara Core
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
 * @author Scott Davies <scott.davies@totaralearning.com>
 * @package totara_api
 */

use core\entity\user;
use core_phpunit\testcase;
use totara_api\pdo\client_service_account;
use totara_core\advanced_feature;

/**
 * @group totara_api
 * Unit tests for client_service_account data object model.
 */
class pdo_client_service_account_test extends testcase {
    /**
     * @return void
     */
    public function test_validation_of_user_when_created(): void {
        global $DB;
        // Create a test user
        $test_api_user = self::getDataGenerator()->create_user([
            'username' => 'user' . uniqid(),
        ]);
        $api_user_role = $DB->get_record('role', ['archetype' => 'apiuser'], 'id');
        role_assign($api_user_role->id, $test_api_user->id, context_system::instance());
        $user_entity_with_role = user::repository()->find($test_api_user->id);

        // Create a test user
        $test_api_user = self::getDataGenerator()->create_user([
            'username' => 'user' . uniqid(),
            'suspended' => 1
        ]);
        $api_user_role = $DB->get_record('role', ['archetype' => 'apiuser'], 'id');
        role_assign($api_user_role->id, $test_api_user->id, context_system::instance());
        $user_entity_suspended = user::repository()->find($test_api_user->id);

        // Create a test user
        $test_api_user = self::getDataGenerator()->create_user([
            'username' => 'user' . uniqid(),
        ]);
        $api_user_role = $DB->get_record('role', ['archetype' => 'apiuser'], 'id');
        role_assign($api_user_role->id, $test_api_user->id, context_system::instance());
        $user_entity_deleted = user::repository()->find($test_api_user->id);
        $user_entity_deleted->deleted = true;
        $user_entity_deleted->save();

        // Get a test user
        $guest_user = user::repository()->where('username', '=', 'guest')->get()->first();
        $admin_user = user::repository()->where('username', '=', 'admin')->get()->first();

        // Operate
        $service_account = new client_service_account($user_entity_with_role);
        $expected_status = client_service_account::VALID;
        // Assert
        $this->assertEquals($expected_status, $service_account->get_status());
        $this->assertEquals(true, $service_account->get_is_valid());
        $user_result = $service_account->get_user();
        $this->assertNotNull($user_result);
        $this->assertEquals($user_entity_with_role->id, $user_result->id);

        // Operate
        $service_account = new client_service_account($user_entity_suspended);
        $expected_status = client_service_account::SUSPENDED;
        // Assert
        $this->assertEquals($expected_status, $service_account->get_status());
        $this->assertEquals(false, $service_account->get_is_valid());
        $user_result = $service_account->get_user();
        $this->assertNotNull($user_result);
        $this->assertEquals($user_entity_suspended->id, $user_result->id);

        // Operate
        $service_account = new client_service_account($user_entity_deleted);
        $expected_status = client_service_account::DELETED;
        // Assert
        $this->assertEquals($expected_status, $service_account->get_status());
        $this->assertEquals(false, $service_account->get_is_valid());
        $user_result = $service_account->get_user();
        $this->assertNull($user_result);

        // Operate
        $service_account = new client_service_account($guest_user);
        $expected_status = client_service_account::GUEST;
        // Assert
        $this->assertEquals($expected_status, $service_account->get_status());
        $this->assertEquals(false, $service_account->get_is_valid());
        $user_result = $service_account->get_user();
        $this->assertNotNull($user_result);
        $this->assertEquals($guest_user->id, $user_result->id);

        // Operate
        $service_account = new client_service_account($admin_user);
        $expected_status = client_service_account::ADMIN;
        // Assert
        $this->assertEquals($expected_status, $service_account->get_status());
        $this->assertEquals(false, $service_account->get_is_valid());
        $user_result = $service_account->get_user();
        $this->assertNotNull($user_result);
        $this->assertEquals($admin_user->id, $user_result->id);
    }

    /**
     * @return void
     */
    public function test_user_tenant_invalid(): void {
        global $DB;

        // Create a test user
        $tenant_generator = self::getDataGenerator()->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();
        $tenant1 = $tenant_generator->create_tenant(); // user tenant ID
        $tenant2 = $tenant_generator->create_tenant(); // API client tenant ID

        $test_api_user = self::getDataGenerator()->create_user([
            'username' => 'user' . uniqid(),
            'tenantid' => $tenant1->id
        ]);
        $api_user_role = $DB->get_record('role', ['archetype' => 'apiuser'], 'id');
        role_assign($api_user_role->id, $test_api_user->id, context_tenant::instance($tenant1->id));

        self::setUser($test_api_user);
        $user_entity = user::repository()->find($test_api_user->id);

        // Operate
        $service_account = new client_service_account($user_entity, $tenant2->id);
        $this->assertEquals('WRONG_TENANT', $service_account->get_status());
        $this->assertFalse($service_account->get_is_valid());
        $this->assertNotNull($service_account->get_user());
    }

    /**
     * @return void
     */
    public function test_service_account_fullname_valid_with_capabilities(): void {
        advanced_feature::disable('engage_resources');
        self::assertTrue(advanced_feature::is_disabled('engage_resources'));

        // Create a test user
        $tenant_generator = self::getDataGenerator()->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();
        $tenant1 = $tenant_generator->create_tenant();
        $tenant2 = $tenant_generator->create_tenant();

        $user = self::getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $role_id = self::getDataGenerator()->create_role();
        assign_capability('moodle/user:viewalldetails', CAP_ALLOW, $role_id, context_user::instance($user->id));
        role_assign($role_id, $user->id, context_user::instance($user->id));

        // Login user with capabilities
        self::setUser($user);
        $user_entity = user::repository()->find($user->id);

        // Operate
        $service_account = new client_service_account($user_entity, $tenant2->id);
        $this->assertEquals('WRONG_TENANT', $service_account->get_status());
        $this->assertFalse($service_account->get_is_valid());
        $this->assertNotNull($service_account->get_user());
    }

    /**
     * @return void
     */
    public function test_user_tenant_valid(): void {
        global $DB;
        // Create a test user
        $tenant_generator = self::getDataGenerator()->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();
        $tenant1 = $tenant_generator->create_tenant(); // user tenant ID
        $tenant2 = $tenant_generator->create_tenant(); // API client tenant ID

        $test_api_user = self::getDataGenerator()->create_user([
            'username' => 'user' . uniqid(),
            'tenantid' => $tenant1->id
        ]);
        $api_user_role = $DB->get_record('role', ['archetype' => 'apiuser'], 'id');
        role_assign($api_user_role->id, $test_api_user->id, context_tenant::instance($tenant1->id));
        $user_entity = user::repository()->find($test_api_user->id);

        // Operate
        $service_account = new client_service_account($user_entity, $tenant1->id);

        $this->assertEquals(client_service_account::VALID, $service_account->get_status());
        $this->assertEquals($test_api_user->id, $service_account->get_user()->id);
        $this->assertTrue($service_account->get_is_valid());
        $this->assertEquals($test_api_user->id, $service_account->get_user()->id);
    }
}