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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author  Michael Ivanov <michael.ivanov@totaralearning.com>
 * @package totara_api
 */

use totara_api\data_provider\client as client_provider;
use totara_api\entity\client as client_entity;
use totara_api\entity\filter\client_filter_factory;
use totara_api\testing\generator;
use core_phpunit\testcase;

/**
 * @group totara_api
 */
class totara_api_data_provider_testcase extends testcase {

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

    /**
     * @return void
     */
    public function test_provider(): void {
        $this->setAdminUser();

        // Create courses.
        $courses = $this->create_clients(3);

        // Create an instance of the data provider.
        $data_provider = client_provider::create();

        // Confirm that we get any data back (without any filters applied).
        $result = $data_provider->fetch();
        $this->assertNotNull($courses);

        // Confirm pagination works.
        $result = $data_provider->set_page_size(2)->fetch_paginated();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('next_cursor', $result);

        /** @var array $items **/
        $items = $result['items'];
        $this->assertCount(2, $items);

        $total = $result['total'];
        $this->assertEquals(3, $total);

        $next_cursor = $result['next_cursor'];
        $this->assertNotEmpty($next_cursor);

        // Provider should throw exception if filter factory not set.
        try {
            $data_provider->set_filters(['foo' => 'bar'])->fetch();
            $this->fail('Exception expected');
        } catch (Exception $e) {
            $this->assertEquals(
                'Coding error detected, it must be fixed by a programmer: No filter factory registered',
                $e->getMessage()
            );
        }
    }

    /**
     * @return void
     */
    public function test_tenant_filter(): void {
        $this->tenant_generator->enable_tenants();

        $tenant1 = $this->tenant_generator->create_tenant();
        $tenant2 = $this->tenant_generator->create_tenant();
        $tenant3 = $this->tenant_generator->create_tenant();

        // Create clients
        $clients = $this->create_clients(5);

        // Assign tenants to clients
        $clients[0]->tenant_id = $tenant1->id;
        $clients[2]->tenant_id = $tenant1->id;
        $clients[4]->tenant_id = $tenant2->id;
        foreach ($clients as $client) {
            $client->save();
        }

        // Confirm that we don't find any results.
        $data_provider = client_provider::create(new client_filter_factory());
        $result = $data_provider->set_filters(['tenant_id' => $tenant3->id])->fetch();
        $this->assertEquals(0, $result->count());

        // Confirm that we get the correct clients back for a specific tenant.
        $data_provider = client_provider::create(new client_filter_factory());
        $result = $data_provider->set_filters(['tenant_id' => $tenant1->id])->fetch();
        foreach ($clients as $client) {
            $this->assertTrue(
                !in_array($client->id, [$clients[0]->id, $clients[2]->id]) xor
                $result->has('id', $client->id)
            );
        }

        // Confirm that we can filter system level clients
        $data_provider = client_provider::create(new client_filter_factory());
        $result = $data_provider->set_filters(['tenant_id' => null])->fetch();
        foreach ($clients as $client) {
            $this->assertTrue(
                !in_array($client->id, [$clients[1]->id, $clients[3]->id]) xor
                $result->has('id', $client->id)
            );
        }
    }

    /**
     * @param int $total
     * @return client_entity[]
     */
    private function create_clients(int $total): array {
        $gen = $this->generator();

        $clients = [];
        for ($i = 0; $i < $total; ++$i) {
            $clients[] = $gen->create_client();
        }
        return $clients;
    }

}