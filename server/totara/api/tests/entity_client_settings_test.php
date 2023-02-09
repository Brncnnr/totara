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

use core\orm\query\builder;
use core_phpunit\testcase;
use totara_api\entity\client;
use totara_api\entity\client_settings;
use totara_api\testing\generator;

/**
 * Unit tests for the totara_api\entity\client_settings class.
 * @group totara_api
 */
class entity_client_settings_test extends testcase {
    /** @var generator - client_settings */
    protected $generator;

    /**
     * Get data generator
     * @return generator
     */
    public static function get_data_generator(): generator {
        return generator::instance();
    }

    /**
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->generator = self::get_data_generator();
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        $this->generator = null;
        parent::tearDown();
    }

    /**
     * @return void
     */
    public function test_create_client_settings(): void {
        $db = builder::get_db();
        self::assertEquals(0, $db->count_records(client_settings::TABLE));

        // Insert a client entity
        $entity_client = new client();
        $entity_client->name = 'test client name';
        $entity_client->status = 0;
        $entity_client->save();

        self::assertTrue($db->record_exists(client::TABLE, ['id' => $entity_client->id]));

        // Insert a client_settings entity
        $one_hour = 3600;
        $entity_cs = new client_settings();
        $entity_cs->default_token_expiry_time = time() + $one_hour;
        $entity_cs->client_rate_limit = 5;
        $entity_cs->client_id = $entity_client->id;

        // Add a 1-on-1 relationship from client to client_settings
        $entity_client->client_settings()->save($entity_cs);

        $cs_retrieved = $db->get_record(client_settings::TABLE, ['client_id' => $entity_client->id]);

        self::assertNotNull($cs_retrieved);
        self::assertNotNull($cs_retrieved->client_rate_limit);
        self::assertEquals($entity_client->id, $cs_retrieved->client_id);
    }

    /**
     * @return void
     */
    public function test_delete_client_settings(): void {
        $db = builder::get_db();
        $entity_cs = $this->generator->create_client_settings_entity();
        $client_record_id = $entity_cs->client_id;

        self::assertTrue($entity_cs->exists());
        $num_client_settings_records_before = $db->count_records(client_settings::TABLE);

        $entity_cs->delete();
        $num_client_settings_records_after = $db->count_records(client_settings::TABLE);

        self::assertLessThan($num_client_settings_records_before, $num_client_settings_records_after);
        self::assertFalse($entity_cs->exists());
        self::assertTrue($db->record_exists(client::TABLE, ['id' => $client_record_id]));
    }

    /**
     * @return void
     */
    public function test_update_client_settings(): void {
        $db = builder::get_db();
        $entity_cs = $this->generator->create_client_settings_entity();

        $new_default_token_expiry_time = ++$entity_cs->default_token_expiry_time;
        $client_rate_limit = ++$entity_cs->client_rate_limit;

        // Insert a **new** client entity
        $entity_client = new client();
        $entity_client->name = 'test client name 2';
        $entity_client->status = 0;
        $entity_client->save();

        self::assertTrue($db->record_exists(client::TABLE, ['id' => $entity_client->id]));
        $new_client_record_id = $entity_client->id;

        // Update our client_settings entity
        $entity_cs->default_token_expiry_time = $new_default_token_expiry_time;
        $entity_cs->client_rate_limit = $client_rate_limit;
        $entity_cs->client_id = $new_client_record_id;

        // Add an updated 1-on-1 relationship from client to client_settings
        $entity_client->client_settings()->save($entity_cs);

        $cs_retrieved = $db->get_record(client_settings::TABLE, ['client_id' => $entity_client->id]);

        // Check our client_settings entity was updated
        self::assertEquals($entity_client->id, $cs_retrieved->client_id);
        self::assertEquals($cs_retrieved->default_token_expiry_time, $new_default_token_expiry_time);
        self::assertEquals($cs_retrieved->client_rate_limit, $client_rate_limit);
    }

    /**
     * @return void
     */
    public function test_belongs_to() : void {
        $db = builder::get_db();
        $entity_cs = $this->generator->create_client_settings_entity();

        $relation = $entity_cs->client();
        $repo = $relation->get_repo();

        // Assert the relation will take us to something like the right entity repository
        $this->assertInstanceOf("totara_api\\repository\\client_repository", $repo);
    }

}