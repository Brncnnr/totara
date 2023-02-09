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
 * @author Michael Ivanov <michael.ivanov@totaralearning.com>
 * @package totara_api
 */

use core\orm\query\exceptions\record_not_found_exception;
use core_phpunit\testcase;
use totara_api\exception\create_client_exception;
use totara_api\global_api_config;
use totara_api\model\client;
use totara_api\pdo\client_service_account;
use totara_api\testing\generator;
use totara_api\entity\client as entity;
use totara_oauth2\model\client_provider;
use totara_oauth2\entity\client_provider as client_provider_entity;
use totara_api\model\client_rate_limit as client_rate_limit_model;
use core\entity\user as user_entity;

/**
 * @group totara_api
 */
class totara_api_client_model_testcase extends testcase {

    /** @var \core\testing\generator */
    protected $generator;

    /** @var \totara_tenant\testing\generator */
    protected $tenant_generator;

    /**
     * @return generator
     */
    protected function generator(): generator {
        return generator::instance();
    }

    protected function setUp(): void {
        parent::setUp();

        $this->generator = self::getDataGenerator();
        $this->tenant_generator = $this->generator->get_plugin_generator('totara_tenant');
    }

    protected function tearDown(): void {
        $this->tenant_generator = null;
        $this->generator = null;

        parent::tearDown();
    }

    public function test_create_without_tenant(): void {
        $this->setAdminUser();
        $user = $this->generator->create_user();

        $name = 'test';
        $description = 'description_test';
        $model = client::create($name, $user->id, $description);

        self::assertNotNull($model);
        self::assertEquals($name, $model->name);
        self::assertEquals($description, $model->description);
        self::assertNull($model->tenant_id);
    }

    public function test_create_with_tenant(): void {
        $this->setAdminUser();

        $name = 'test';
        $description = 'description_test';
        $this->tenant_generator->enable_tenants();
        $tenant = $this->tenant_generator->create_tenant();

        $user = $this->getDataGenerator()->create_user(['tenantid' => $tenant->id]);

        $model = client::create($name, $user->id, $description, $tenant->id);

        self::assertNotNull($model);
        self::assertEquals($name, $model->name);
        self::assertEquals($description, $model->description);
        self::assertEquals($tenant->id, $model->tenant_id);
    }

    public function test_create_with_non_existent_tenant(): void {
        $this->setAdminUser();

        $this->expectException(dml_missing_record_exception::class);
        client::create('test', null, 'description', 9999);
    }

    public function test_create_with_client_provider(): void {
        $this->setAdminUser();
        $user = $this->generator->create_user();

        $name = 'test';
        $description = 'description_test';
        $model = client::create($name, $user->id, $description, null, false, ['create_client_provider' => true]);

        self::assertNotNull($model);
        /** @var client_provider $provider */
        $provider = $model->oauth2_client_providers->first();
        self::assertEquals($model->name, $provider->name);
        self::assertEquals($model->description, $provider->description);
        self::assertEquals(false, $model->status);
        self::assertNotNull($provider->client_id);
        self::assertNotNull($provider->client_secret);
    }

    public function test_update(): void {
        $this->setAdminUser();

        $name = 'test';
        $description = 'description_test';
        $entity = $this->generator()->create_client();
        $model = client::load_by_entity($entity);
        $model->update($name, $description);

        self::assertNotNull($model);
        self::assertEquals($name, $model->name);
        self::assertEquals($description, $model->description);
        self::assertNull($model->tenant_id);
        self::assertEquals(true, $model->status);
    }

    public function test_update_with_client_provider(): void {
        $this->setAdminUser();
        $user = $this->generator->create_user();

        $model = client::create('123', $user->id, '', null, 1, ['create_client_provider' => true]);
        $name = 'new_name';
        $description = 'new_description';
        $model->update($name, $description);
        /** @var client_provider $client_provider */
        $client_provider = $model->oauth2_client_providers->first();
        self::assertEquals($name, $client_provider->name);
        self::assertEquals($description, $client_provider->description);
    }

    public function test_delete(): void {
        $this->setAdminUser();

        $entity = $this->generator()->create_client();
        $model = client::load_by_entity($entity);
        $model->delete();

        self::assertFalse(entity::repository()->where('id', '=', $model->id)->exists());
    }

    public function test_delete_with_client_provider(): void {
        $this->setAdminUser();
        $user = $this->generator->create_user();

        $model = client::create('123', $user->id, '', null, 1, ['create_client_provider' => true]);
        /** @var client_provider $provider */
        $provider = $model->oauth2_client_providers->first();
        $model->delete();

        self::assertFalse(entity::repository()->where('id', '=', $model->id)->exists());
        self::expectException(record_not_found_exception::class);
        $provider->get_entity_copy()->refresh();
    }

    public function test_delete_with_non_internal_client_provider(): void {
        $this->setAdminUser();
        $user = $this->generator->create_user();

        $model = client::create('123', $user->id, '', null, 1, ['create_client_provider' => true]);
        /** @var client_provider $provider */
        $provider = $model->oauth2_client_providers->first();
        /** @var client_provider_entity $provider_entity */
        $provider_entity = $provider->get_entity_copy();
        $provider_entity->internal = 0;
        $provider_entity->save();
        $model->delete();

        self::assertFalse(entity::repository()->where('id', '=', $model->id)->exists());
        self::assertNotNull($provider_entity->refresh());
    }

    /**
     * @return void
     */
    public function test_fetch_related_client_settings(): void {
        $this->setAdminUser();
        $user = $this->generator->create_user();

        /* Create an api_client entity using the api_client model.
        * This will automatically create an api_client_settings entity with a 1-on-1 relation to api_client. */
        $name = 'test';
        $description = 'description_test';
        $model_client = client::create($name, $user->id, $description);

        $client_settings_model_related = $model_client->client_settings;
        $this->assertNotNull($client_settings_model_related);
        $this->assertEquals($model_client->id, $client_settings_model_related->client_id);
    }

    /**
     * @return void
     */
    public function test_client_rate_limit_model(): void {
        /** @var \totara_api\testing\generator $api_generator */
        $api_generator = $this->getDataGenerator()->get_plugin_generator('totara_api');
        $client_rate_limit = $api_generator->create_client_rate_limit();

        $client_entity = new entity($client_rate_limit->client_id);
        $client_model = client::load_by_entity($client_entity);

        // Get the rate limit through the client model.
        $this->assertInstanceOf(client_rate_limit_model::class, $client_model->client_rate_limit);
        $this->assertEquals($client_rate_limit->id, $client_model->client_rate_limit->id);
    }

    /**
     * @return void
     */
    public function test_get_context(): void {
        $user = $this->generator->create_user();

        $client1 = client::create('123', $user->id);
        self::assertInstanceOf(context_system::class, $client1->get_context());

        $this->tenant_generator->enable_tenants();
        $tenant = $this->tenant_generator->create_tenant();

        $user_two = $this->getDataGenerator()->create_user(['tenantid' => $tenant->id]);

        $client2 = client::create('223', $user_two->id, '', $tenant->id);
        self::assertInstanceOf(context_coursecat::class, $client2->get_context());
        self::assertEquals($tenant->categoryid, $client2->get_context()->instanceid);
    }

    /**
     * @return void
     */
    public function test_create_with_valid_service_account_user(): void {
        global $DB, $CFG;
        // Set up
        $this->setAdminUser();
        $original_config = $CFG->tenantsenabled;

        // Make sure there are matching api user & client tenant ids.
        $this->tenant_generator->enable_tenants();
        $tenant2 = $this->tenant_generator->create_tenant();
        $test_api_user = self::getDataGenerator()->create_user();
        $this->tenant_generator->migrate_user_to_tenant($test_api_user->id, $tenant2->id);
        $test_api_user->tenantid = $tenant2->id;
        $test_api_user = new user_entity($test_api_user->id);

        $api_user_role = $DB->get_record('role', ['archetype' => 'apiuser'], 'id');
        role_assign($api_user_role->id, $test_api_user->id, context_tenant::instance($tenant2->id));

        $name = 'test';
        $description = 'description_test';
        $model = client::create($name, $test_api_user, $description, (int) $tenant2->id);

        // Assert
        $test_api_user->refresh();
        $this->assertNotNull($model);
        $this->assertEquals($test_api_user->id, $model->user_id);
        $this->assertEquals($tenant2->id, $model->tenant_id);

        $service_account = $model->get_service_account();
        $this->assertTrue($service_account->get_is_valid());
        $this->assertEquals($service_account->get_status(), client_service_account::VALID);
        $this->assertNotNull($service_account->get_user());
        $this->assertEquals($service_account->get_user()->id, $test_api_user->id);

        // Tear down
        set_config('tenantsenabled', $original_config);
    }

    /**
     * @return void
     */
    public function test_create_with_invalid_tenant_user(): void {
        $this->setAdminUser();

        $this->tenant_generator->enable_tenants();
        $tenant1 = $this->tenant_generator->create_tenant();
        $tenant2 = $this->tenant_generator->create_tenant();

        $test_api_user = self::getDataGenerator()->create_user(['username' => 'user4_invalid', 'tenantid' => $tenant1->id]);
        $test_api_user = new user_entity($test_api_user->id);

        $test_api_user2 = self::getDataGenerator()->create_user(['username' => 'user5_invalid']);
        $test_api_user2 = new user_entity($test_api_user2->id);

        $expected_exception = 'The user is not valid. The status is WRONG_TENANT';

        // Example error catching usage to try out.
        try {
            client::validate_api_user($test_api_user, $tenant2->id);
        } catch (create_client_exception $exception) {
            $this->assertEquals('The user is not valid. The status is WRONG_TENANT', $expected_exception);
        }
        // Example error catching usage to try out.
        try {
            client::validate_api_user($test_api_user2, $tenant2->id);
        } catch (create_client_exception $exception) {
            $this->assertEquals('The user is not valid. The status is WRONG_TENANT', $expected_exception);
        }
    }

    /**
     * @return void
     */
    public function test_validate_service_account_for_system_user_with_tenant_api_client(): void {
        // Set up.
        global $CFG, $DB;
        self::setAdminUser();
        $original_config = $CFG->tenantsenabled;

        $this->tenant_generator->enable_tenants();
        $tenant1 = $this->tenant_generator->create_tenant();

        // Create a system API user.
        $test_system_user = self::getDataGenerator()->create_user();
        $api_user_role = $DB->get_record('role', ['archetype' => 'apiuser'], 'id');
        role_assign($api_user_role->id, $test_system_user->id, context_system::instance());
        $test_system_user = new user_entity($test_system_user->id);

        // Validate the service_account user for a tenant API client.
        $validation_result = client::validate_api_user($test_system_user, $tenant1->id);
        $this->assertEquals(client_service_account::WRONGTENANT, $validation_result);

        // Tear down.
        set_config('tenantsenabled', $original_config);
    }

    /**
     * @return void
     */
    public function test_validate_service_account_for_tenant_user_with_system_api_client(): void {
        // Set up.
        global $CFG, $DB;
        self::setAdminUser();
        $original_config = $CFG->tenantsenabled;

        $this->tenant_generator->enable_tenants();
        $tenant1 = $this->tenant_generator->create_tenant();

        // Create a tenant API user.
        $test_tenant_user = self::getDataGenerator()->create_user([
            'username' => 'test_user' . uniqid(),
            'tenantid' => $tenant1->id
        ]);
        $api_user_role = $DB->get_record('role', ['archetype' => 'apiuser'], 'id');
        role_assign($api_user_role->id, $test_tenant_user->id, context_tenant::instance($tenant1->id));
        $test_tenant_user = new user_entity($test_tenant_user->id);

        // Validate the service_account user for a system API client.
        $validation_result = client::validate_api_user($test_tenant_user);
        $this->assertEquals(client_service_account::WRONGTENANT, $validation_result);

        // Tear down.
        set_config('tenantsenabled', $original_config);
    }

    /**
     * @return void
     */
    public function test_create_with_invalid_users(): void {
        $this->setAdminUser();
        $test_user1 = self::getDataGenerator()->create_user(['username' => 'suspended_user' . uniqid()]);
        $test_user1 = new user_entity($test_user1->id);
        $test_user1->suspended = 1;
        $test_user1->save();

        $test_user2 = self::getDataGenerator()->create_user(['username' => 'deleted_user' . uniqid()]);
        $test_user2 = new user_entity($test_user2->id);
        $test_user2->deleted = 1;
        $test_user2->save();

        $test_user3 = user_entity::repository()->where('username', '=', 'guest')->get()->first();
        $test_user4 = user_entity::repository()->where('username', '=', 'admin')->get()->first();

        try {
            client::validate_api_user($test_user1);
            client::create('client_name', $test_user1, 'client description');
        } catch (create_client_exception $exception) {
            $this->assertEquals($exception->getMessage(), 'The user is not valid. The status is SUSPENDED');
        }

        try {
            client::validate_api_user($test_user2);
            client::create('client_name', $test_user2, 'client description');
        } catch (create_client_exception $exception) {
            $this->assertEquals($exception->getMessage(), 'The user is not valid. The status is DELETED');
        }

        try {
            client::validate_api_user($test_user3);
            client::create('client_name', $test_user3, 'client description');
        } catch (create_client_exception $exception) {
            $this->assertEquals($exception->getMessage(), 'The user is not valid. The status is GUEST');
        }

        try {
            client::validate_api_user($test_user4);
            client::create('client_name', $test_user4, 'client description');
        } catch (create_client_exception $exception) {
            $this->assertEquals($exception->getMessage(), 'The user is not valid. The status is ADMIN');
        }
    }

    /**
     * Check the API client wasn't deleted if the API user was deleted.
     *
     * @return void
     * @throws moodle_exception
     */
    public function test_delete_api_user_on_api_client(): void {
        $this->setAdminUser();

        $test_api_user = self::getDataGenerator()->create_user();
        $test_api_user = new user_entity($test_api_user->id);
        $model = client::create('client_name', $test_api_user, 'client description');

        $this->assertEquals($test_api_user->id, $model->user_id);

        $test_api_user->refresh();
        $user_std = new \stdClass();
        $user_std->id = $test_api_user->id;
        $user_std->username = $test_api_user->username;
        $user_std->email = $test_api_user->email;

        delete_user($user_std);
        $test_api_user->refresh();
        $this->assertEquals('1', $test_api_user->deleted);

        // Check the API client wasn't deleted when the API user was deleted.
        $this->assertTrue(entity::repository()->find($model->id)->exists());
        // Currently deleting an API user will not set client_model->user_id to null automatically.
    }

    /**
     * @return void
     * @throws moodle_exception
     */
    public function test_create_client_with_same_user_on_multiple_clients(): void {
        $this->setAdminUser();

        // Make sure matching api user & client tenant ids
        $this->tenant_generator->enable_tenants();

        $test_api_user = self::getDataGenerator()->create_user(['username' => 'user4_multiple_clients']);
        $test_api_user = new user_entity($test_api_user->id);

        $name = 'client1';
        $description = 'description_test';
        $model_client_1 = client::create($name, $test_api_user, $description);

        $name = 'client2';
        $model_client_2 = client::create($name, $test_api_user, $description);

        $this->assertEquals($test_api_user->id, $model_client_1->user_id);
        $this->assertEquals($test_api_user->id, $model_client_2->user_id);

        $api_user1 = $model_client_1->user;
        $api_user2 = $model_client_1->user;
        $this->assertEquals($test_api_user->id, $api_user1->id);
        $this->assertEquals($test_api_user->id, $api_user2->id);
    }

    /**
     * @return void
     * @throws moodle_exception
     */
    public function test_update_with_valid_user(): void {
        $this->setAdminUser();

        $test_api_user1 = self::getDataGenerator()->create_user();
        $test_api_user1 = new user_entity($test_api_user1->id);
        $test_api_user2 = self::getDataGenerator()->create_user();
        $test_api_user2 = new user_entity($test_api_user2->id);

        $old_client_name = 'test client';
        $new_client_name = 'test clientb';
        $description = 'description_test';

        // Create an API client with no associated API user
        $model = client::create($old_client_name, $test_api_user1->id, $description);

        // Update the API client, now with an API user
        $model->update($new_client_name, null, null, $test_api_user1);

        $this->assertNotNull($model); // Check entity on model hasn't been deleted after updating for user.
        $this->assertEquals($test_api_user1->id, $model->user_id);

        // Update the API client, for another different API user
        $model->update(null, null, null, $test_api_user2);

        $this->assertNotNull($model);
        $this->assertNotEquals($test_api_user1->id, $model->user_id);
        $this->assertEquals($test_api_user2->id, $model->user_id);
    }

    /**
     * @return void
     */
    public function test_update_with_invalid_tenant_user(): void {
        $this->setAdminUser();

        $this->tenant_generator->enable_tenants();
        $tenant1 = $this->tenant_generator->create_tenant();
        $tenant2 = $this->tenant_generator->create_tenant();

        $test_api_user = self::getDataGenerator()->create_user();
        $this->tenant_generator->migrate_user_to_tenant($test_api_user->id, $tenant1->id);
        $test_api_user->tenantid = $tenant1->id;
        $test_api_user = new user_entity($test_api_user->id);

        $model = client::create('client_name', $test_api_user, 'client description', (int) $tenant1->id);

        $test_api_user2 = self::getDataGenerator()->create_user();
        $this->tenant_generator->migrate_user_to_tenant($test_api_user2->id, $tenant2->id);
        $test_api_user2->tenantid = $tenant2->id;
        $test_api_user2 = new user_entity($test_api_user2->id);

        $test_api_user3 = self::getDataGenerator()->create_user();
        $test_api_user3 = new user_entity($test_api_user3->id);

        $expected_exception = 'The user is not valid. The status is WRONG_TENANT';

        // Example error catching usage to try out.
        try {
            $model->update('client_name new', null, null, $test_api_user2);
        } catch (create_client_exception $exception) {
            $this->assertEquals($exception->getMessage(), $expected_exception);
        }
        try {
            $model->update('client_name new', null, null, $test_api_user3);
        } catch (create_client_exception $exception) {
            $this->assertEquals($exception->getMessage(), $expected_exception);
        }
    }

    /**
     * @return void
     */
    public function test_update_with_invalid_users(): void {
        $this->setAdminUser();
        $user = $this->generator->create_user();

        $model = client::create('client_name', $user->id, 'client description');

        $this->setAdminUser();
        $test_user1 = self::getDataGenerator()->create_user(['username' => 'suspended_user' . uniqid()]);
        $test_user1 = new user_entity($test_user1->id);
        $test_user1->suspended = 1;
        $test_user1->save();

        $test_user2 = self::getDataGenerator()->create_user(['username' => 'deleted_user' . uniqid()]);
        $test_user2 = new user_entity($test_user2->id);
        $test_user2->deleted = 1;
        $test_user2->save();

        $test_user3 = user_entity::repository()->where('username', '=', 'guest')->get()->first();
        $test_user4 = user_entity::repository()->where('username', '=', 'admin')->get()->first();

        try {
            client::validate_api_user($test_user1);
            $model->update('client_name new', null, null, $test_user1);
        } catch (create_client_exception $exception) {
            $this->assertEquals($exception->getMessage(), 'The user is not valid. The status is SUSPENDED');
        }

        try {
            client::validate_api_user($test_user2);
            $model->update('client_name new', null, null, $test_user2);
        } catch (create_client_exception $exception) {
            $this->assertEquals($exception->getMessage(), 'The user is not valid. The status is DELETED');
        }

        try {
            client::validate_api_user($test_user3);
            $model->update('client_name new', null, null, $test_user3);
        } catch (create_client_exception $exception) {
            $this->assertEquals($exception->getMessage(), 'The user is not valid. The status is GUEST');
        }

        try {
            client::validate_api_user($test_user4);
            $model->update('client_name new', null, null, $test_user4);
        } catch (create_client_exception $exception) {
            $this->assertEquals($exception->getMessage(), 'The user is not valid. The status is ADMIN');
        }
    }

    /**
     * @return void
     */
    public function test_create_with_global_client_settings(): void {
        $this->setAdminUser();
        $user = $this->generator->create_user();

        $model = client::create('test', $user->id, null, null, false, ['create_client_provider' => true]);
        $client_settings = $model->get_client_settings();

        // check global default value
        self::assertEquals(global_api_config::get_default_token_expiration(), $client_settings->default_token_expiry_time);
        self::assertEquals(global_api_config::get_client_rate_limit(), $client_settings->client_rate_limit);

        // Update default value
        set_config('default_token_expiration', 100, 'totara_api');
        set_config('client_rate_limit', 200, 'totara_api');

        $model = client::create('test', $user->id, null, null, false, ['create_client_provider' => true]);
        $client_settings = $model->get_client_settings();
        self::assertEquals(100, $client_settings->default_token_expiry_time);
        self::assertEquals(200, $client_settings->client_rate_limit);
    }
}