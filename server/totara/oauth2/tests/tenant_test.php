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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_oauth2
 */

use core\testing\component_generator;
use core_phpunit\testcase;
use core\orm\query\builder;
use totara_oauth2\model\client_provider;
use totara_oauth2\server;
use totara_tenant\local\util;
use totara_oauth2\io\request;
use totara_oauth2\grant_type;
use totara_tenant\testing\generator as tenant_generator;

/**
 * totara_api
 */
class totara_oauth2_tenant_testcase extends testcase {

    /**
     * @return void
     */
    public function test_delete_tenant(): void {
        // Create tenant.
        /** @var tenant_generator $tenant_generator */
        $tenant_generator = $this->get_tenant_generator();
        $tenant_generator->enable_tenants();
        $tenant1 = $tenant_generator->create_tenant();
        $tenant2 = $tenant_generator->create_tenant();

        // Create client providers.
        $client_no_tenant = client_provider::create(
            'test',
            'xapi:write',
            FORMAT_PLAIN,
            '123',
            1
        );
        $client_tenant_1 = client_provider::create(
            'test',
            'xapi:write',
            FORMAT_PLAIN,
            '123',
            1,
            true,
            $tenant1->id
        );
        $client_tenant_2 = client_provider::create(
            'test',
            'xapi:write',
            FORMAT_PLAIN,
            '123',
            1,
            true,
            $tenant2->id
        );

        // Get a tokens for clients.
        $token1 = $this->get_grant_token($client_no_tenant);
        $token2 = $this->get_grant_token($client_tenant_1);
        $token3 = $this->get_grant_token($client_tenant_2);

        // Confirm totara_oauth2_client_provider exist.
        $records = builder::get_db()->get_records('totara_oauth2_client_provider');
        $this->assertCount(3, $records);

        // Confirm totara_oauth2_access_token exist.
        $records = builder::get_db()->get_records('totara_oauth2_access_token');
        $this->assertCount(3, $records);

        // Delete the tenant.
        util::delete_tenant($tenant1->id, util::DELETE_TENANT_USER_DELETE);

        // Confirm totara_oauth2_client_provider are deleted.
        $records = builder::get_db()->get_records('totara_oauth2_client_provider');
        $this->assertCount(2, $records);

        // Confirm totara_oauth2_access_token are deleted.
        $records = builder::get_db()->get_records('totara_oauth2_access_token');
        $this->assertCount(2, $records);
    }

    /**
     * @return string
     */
    private function get_grant_token(client_provider $client): string {
        $request = request::create_from_global(
            [],
            [
                "grant_type" => grant_type::get_client_credentials(),
                "client_id" => $client->client_id,
                "client_secret" => $client->client_secret
            ]
        );

        $server = server::create(time());
        $response = $server->handle_token_request($request)->getBody()->__toString();
        $parameters = json_decode($response, true);
        return $parameters['access_token'];
    }

    /**
     * @return component_generator
     */
    private function get_tenant_generator(): component_generator {
        return $this->getDataGenerator()->get_plugin_generator('totara_tenant');
    }

}