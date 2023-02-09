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
 * @author  Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_api
 */

use core\orm\query\builder;
use core_phpunit\testcase;
use totara_api\entity\client;
use totara_api\entity\client_rate_limit;
use core\testing\generator;
use totara_api\testing\generator as api_generator;

/**
 * @group totara_api
 */
class totara_api_entity_client_rate_limit_testcase extends testcase {

    /** @var generator */
    protected $generator;

    /** @var api_generator $api_generator */
    protected $api_generator;

    /**
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->generator = self::getDataGenerator();
        $this->api_generator = $this->generator->get_plugin_generator('totara_api');
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        $this->api_generator = null;
        $this->generator = null;
        parent::tearDown();
    }

    /**
     * @return void
     */
    public function test_create_client_rate_limit(): void {
        $db = builder::get_db();
        self::assertEquals(0, $db->count_records(client_rate_limit::TABLE));

        $client = $this->api_generator->create_client();

        $entity = new client_rate_limit();
        $entity->client_id = $client->id;
        $entity->save();
        self::assertEquals(1, $db->count_records(client_rate_limit::TABLE));

        $record = $db->get_record(client_rate_limit::TABLE, ['id' => $entity->id]);
        self::assertNotNull($record->time_created);
    }

    /**
     * @return void
     */
    public function test_create_client_rate_limit_generator(): void {
        $entity = $this->api_generator->create_client_rate_limit();

        $db = builder::get_db();
        self::assertTrue($db->record_exists(client::TABLE, ['id' => $entity->client_id]));

        $this->assertEquals(
            $entity->client->id,
            $entity->client_id
        );
    }

    /**
     * @return void
     */
    public function test_delete_client_rate_limit(): void {
        $db = builder::get_db();
        self::assertEquals(0, $db->count_records(client_rate_limit::TABLE));

        $entity = $this->api_generator->create_client_rate_limit();

        $entity->delete();
        self::assertEquals(0, $db->count_records(client_rate_limit::TABLE));
        self::assertFalse($entity->exists());
    }

    /**
     * @return void
     */
    public function test_update_client_rate_limit(): void {
        $entity = $this->api_generator->create_client_rate_limit();

        $db = builder::get_db();

        self::assertTrue($db->record_exists(client_rate_limit::TABLE, ['current_limit' => null]));
        self::assertFalse($db->record_exists(client_rate_limit::TABLE, ['current_limit' => '500']));

        $entity->current_limit = '500';
        $entity->save();

        self::assertTrue($db->record_exists(client_rate_limit::TABLE, ['current_limit' => '500']));
        self::assertFalse($db->record_exists(client_rate_limit::TABLE, ['current_limit' => null]));
    }

    /**
     * @return void
     */
    public function test_client_delete(): void {
        $entity = $this->api_generator->create_client_rate_limit();

        $db = builder::get_db();
        self::assertTrue($db->record_exists(client::TABLE, ['id' => $entity->client_id]));
        self::assertTrue($db->record_exists(client_rate_limit::TABLE, ['client_id' => $entity->client_id]));

        $entity->client->delete();

        self::assertFalse($db->record_exists(client::TABLE, ['id' => $entity->client_id]));
        self::assertFalse($db->record_exists(client_rate_limit::TABLE, ['client_id' => $entity->client_id]));
    }

}