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
 * @author  Michael Ivanov <michael.ivanov@totaralearning.com>
 * @package totara_api
 */

use core\orm\query\builder;
use core_phpunit\testcase;
use totara_api\entity\client;
use totara_api\entity\client_rate_limit as client_rate_limit_entity;
use core\entity\user as user_entity;

/**
 * @group totara_api
 */
class totara_api_entity_client_testcase extends testcase {
    /**
     * @return void
     */
    public function test_create_client(): void {
        $db = builder::get_db();
        self::assertEquals(0, $db->count_records(client::TABLE));

        $entity = new client();
        $entity->name = 'something else';
        $entity->status = 0;
        $entity->save();

        self::assertEquals(1, $db->count_records(client::TABLE));

        $record = $db->get_record(client::TABLE, ['id' => $entity->id]);
        self::assertNotNull($record->time_created);
        self::assertEquals($record->name, $entity->name);

        self::assertNull($record->description);
        self::assertEquals(0, $entity->status);
    }

    /**
     * @return void
     */
    public function test_delete_client(): void {
        $db = builder::get_db();
        self::assertEquals(0, $db->count_records(client::TABLE));

        $entity = new client();
        $entity->name = 'something else';
        $entity->save();

        self::assertEquals(1, $db->count_records(client::TABLE));
        self::assertTrue($entity->exists());

        $entity->delete();
        self::assertEquals(0, $db->count_records(client::TABLE));
        self::assertFalse($entity->exists());
    }

    /**
     * @return void
     */
    public function test_update_client(): void {
        $entity = new client();
        $entity->name = 'something else';
        $entity->save();
        self::assertEquals(1, $entity->status);

        $db = builder::get_db();

        self::assertTrue($db->record_exists(client::TABLE, ['name' => 'something else']));
        self::assertFalse($db->record_exists(client::TABLE, ['name' => 'new']));

        $entity->name = "new";
        $entity->status = 0;
        $entity->save();

        self::assertFalse($db->record_exists(client::TABLE, ['name' => 'something else']));
        self::assertTrue($db->record_exists(client::TABLE, ['name' => 'new']));
        self::assertEquals(0, $entity->status);
    }

    /**
     * @return void
     */
    public function test_client_rate_limit_entity(): void {
        /** @var \totara_api\testing\generator $api_generator */
        $api_generator = $this->getDataGenerator()->get_plugin_generator('totara_api');
        $client_rate_limit = $api_generator->create_client_rate_limit();

        $client_entity = new client($client_rate_limit->client_id);

        // Get the rate limit through the client entity.
        $this->assertInstanceOf(client_rate_limit_entity::class, $client_entity->client_rate_limit);
        $this->assertEquals($client_rate_limit->id, $client_entity->client_rate_limit->id);
    }

    /**
     * @param client $client_entity
     * @param user_entity $test_api_user
     * @return void
     */
    private function helper_reset_client_and_api_user(client &$client_entity) : void {
        $client_entity->user()->disassociate(); // or try $entity->user_id = null;
        $client_entity->save();
        $client_entity->refresh();
    }

    /**
     * @return void
     * @throws dml_exception
     */
    public function test_create_client_with_api_user(): void {
        $db = builder::get_db();

        $client_entity = new client();
        $client_entity->name = 'something else';
        $client_entity->status = 0;
        $client_entity->save();

        $test_api_user = user_entity::repository()->where('id', '>', '0')->order_by('id')->first();

        // Try the method of relating API user: $client->user_id = $user_id
        $client_entity->user_id = $test_api_user->id;
        $client_entity->save();

        // Assert
        $record = $db->get_record(client::TABLE, ['id' => $client_entity->id]);
        $this->assertNotNull($record->user_id);
        $this->assertEquals($record->user_id, $test_api_user->id);

        // Reset.
        $this->helper_reset_client_and_api_user($client_entity, $test_api_user);
        $this->assertNull($client_entity->user_id);

        // Try an alternative method of relating API user: $client->user()->associate($user);
        $client_entity->user()->associate($test_api_user);
        $client_entity->save();

        // Assert
        $record = $db->get_record(client::TABLE, ['id' => $client_entity->id]);
        $this->assertNotNull($record->user_id);
        $this->assertEquals($record->user_id, $test_api_user->id);
    }

}
