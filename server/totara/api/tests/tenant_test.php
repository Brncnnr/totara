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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_api
 */

use core\entity\user;
use core\testing\component_generator;
use core_phpunit\testcase;
use totara_api\model\client;
use core\orm\query\builder;
use totara_tenant\local\util;
use totara_tenant\testing\generator as tenant_generator;

/**
 * totara_api
 */
class totara_api_tenant_testcase extends testcase {

    /**
     * @return void
     */
    public function test_suspend_tenant():void {
        $generator = $this->getDataGenerator();

        // Create tenant.
        $tenant_generator = $this->get_tenant_generator();
        $tenant_generator->enable_tenants();
        $tenant = $tenant_generator->create_tenant();

        // Grant user authority to manage clients.
        $role_id = $generator->create_role();
        $user = $generator->create_user();
        assign_capability('totara/api:manageclients', CAP_ALLOW, $role_id, context_system::instance());
        role_assign($role_id, $user->id, context_coursecat::instance($tenant->categoryid));

        // Set current user.
        self::setUser($user);

        $user_two = $this->getDataGenerator()->create_user(['tenantid' => $tenant->id]);

        // Create clients.
        $client1 = client::create('test1', $user_two->id, null, $tenant->id, true, ['create_client_provider' => true]);
        $client2 = client::create('test2', $user_two->id, null, $tenant->id, true, ['create_client_provider' => true]);

        self::assertEquals($tenant->id, $client1->tenant_id);
        self::assertTrue($client1->status);
        self::assertEquals($tenant->id, $client2->tenant_id);
        self::assertTrue($client2->status);

        // Trigger update tenant event.
        totara_tenant\local\util::update_tenant(['id' => $tenant->id, 'suspended' => 1]);

        $records = builder::get_db()->get_records('totara_api_client', ['tenant_id' => $tenant->id]);
        self::assertCount(2, $records);
        foreach ($records as $record) {
            self::assertFalse((bool)$record->status);
        }
    }

    /**
     * @return void
     */
    public function test_delete_tenant(): void {
        $generator = $this->getDataGenerator();

        // Create tenant.
        /** @var tenant_generator $tenant_generator */
        $tenant_generator = $this->get_tenant_generator();
        $tenant_generator->enable_tenants();
        $tenant1 = $tenant_generator->create_tenant();
        $tenant2 = $tenant_generator->create_tenant();

        // Grant user authority to manage clients.
        $role_id = $generator->create_role();
        $user = $generator->create_user();
        assign_capability('totara/api:manageclients', CAP_ALLOW, $role_id, context_system::instance());
        role_assign($role_id, $user->id, context_coursecat::instance($tenant1->categoryid));
        role_assign($role_id, $user->id, context_coursecat::instance($tenant2->categoryid));

        // Set current user.
        $this->setUser($user);

        $user_two = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);

        // Create clients.
        $client1 = client::create('t1c1', $user_two->id, null, $tenant1->id, true, ['create_client_provider' => true]);
        $client2 = client::create('t1c2', $user_two->id, null, $tenant1->id, true, ['create_client_provider' => true]);

        $user_two = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);

        $client3 = client::create('t2c1', $user_two->id, null, $tenant2->id, true, ['create_client_provider' => true]);
        $client4 = client::create('t2c2', $user_two->id, null, $tenant2->id, true, ['create_client_provider' => true]);

        // Confirm totara_api_client exist.
        $records = builder::get_db()->get_records('totara_api_client', ['tenant_id' => $tenant1->id]);
        $this->assertCount(2, $records);
        $records = builder::get_db()->get_records('totara_api_client', ['tenant_id' => $tenant2->id]);
        $this->assertCount(2, $records);

        // Confirm totara_oauth2_client_provider exist.
        $records = builder::get_db()->get_records('totara_oauth2_client_provider');
        $this->assertCount(4, $records);

        // Delete the tenant.
        util::delete_tenant($tenant1->id, util::DELETE_TENANT_USER_DELETE);

        // Confirm totara_api_client are deleted for tenant1.
        $records = builder::get_db()->get_records('totara_api_client', ['tenant_id' => $tenant1->id]);
        $this->assertCount(0, $records);

        // Confirm totara_api_client are not deleted for tenant2.
        $records = builder::get_db()->get_records('totara_api_client', ['tenant_id' => $tenant2->id]);
        $this->assertCount(2, $records);

        // Confirm totara_oauth2_client_provider are deleted.
        $records = builder::get_db()->get_records('totara_oauth2_client_provider');
        $this->assertCount(2, $records);
    }

    /**
     * @return void
     */
    public function test_create_client_no_tenant(): void {
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();

        $client = client::create('t0c1', $user->id, null, 0, true, ['create_client_provider' => true]);
        $this->assertInstanceOf(client::class, $client);
    }

    /**
     * @return void
     */
    public function test_create_client_invalid_tenant(): void {
        try {
            $client = client::create('t1c1', null, null, 1, true, ['create_client_provider' => true]);
            $this->fail("Expected: Can not find data record in database");
        } catch (\core\orm\query\exceptions\record_not_found_exception $e) {
            $this->assertStringContainsString(
                'Can not find data record in database',
                $e->getMessage()
            );
        }
    }

    /**
     * @return component_generator
     */
    private function get_tenant_generator(): component_generator {
        return $this->getDataGenerator()->get_plugin_generator('totara_tenant');
    }

}