<?php
/**
 * This file is part of Totara Core
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @package totara_oauth2
 */

use totara_oauth2\exception\delete_provider_exception;
use totara_oauth2\testing\generator;
use core_phpunit\testcase;
use totara_webapi\phpunit\webapi_phpunit_helper;
use core\orm\query\builder;
use totara_oauth2\entity\client_provider;
use totara_oauth2\entity\access_token;

/**
 * @group totara_oauth2
 */
class webapi_resolver_mutation_delete_client_provider_testcase extends testcase {
    use webapi_phpunit_helper;

    private const MUTATION = 'totara_oauth2_delete_provider';

    /**
     * @return void
     */
    public function test_delete_provider_by_admin(): void {
        self::setAdminUser();

        $generator = generator::instance();
        $provider = $generator->create_client_provider("client_id_one");
        $provider1 = $generator->create_client_provider("client_id_two");
        $generator->create_access_token_from_client_provider($provider);

        $db = builder::get_db();
        self::assertTrue($db->record_exists(client_provider::TABLE, ['id' => $provider->id]));
        self::assertTrue($db->record_exists(access_token::TABLE, ['client_provider_id' => $provider->id]));

        $id = $provider->id;
        $result = $this->resolve_graphql_mutation(self::MUTATION, ['id' => $id]);
        self::assertTrue($result);
        self::assertFalse($db->record_exists(client_provider::TABLE, ['id' => $id]));
        self::assertFalse($db->record_exists(access_token::TABLE, ['client_provider_id' => $provider->id]));

        self::assertTrue($db->record_exists(client_provider::TABLE, ['id' => $provider1->id]));
    }

    /**
     * @return void
     */
    public function test_delete_provider_by_system_user(): void {
        $user = self::getDataGenerator()->create_user();
        self::setUser($user);

        $generator = generator::instance();
        $provider = $generator->create_client_provider("client_id_one");

        self::expectException(required_capability_exception::class);
        $result = $this->resolve_graphql_mutation(self::MUTATION, ['id' => $provider->id]);
    }


    /**
     * @return void
     */
    public function test_create_client_providers_with_capabilities(): void {
        $gen = self::getDataGenerator();
        $db = builder::get_db();
        $manager = $gen->create_user();
        $role = $db->get_record('role', array('shortname' => 'manager'));
        $gen->role_assign($role->id, $manager->id);

        self::setUser($manager);

        $generator = generator::instance();
        $provider = $generator->create_client_provider("client_id_one");
        $result = $this->resolve_graphql_mutation(self::MUTATION, ['id' => $provider->id]);
        self::assertTrue($result);
    }

    public function test_delete_internal_provider(): void {
        self::setAdminUser();

        $generator = generator::instance();
        $provider = $generator->create_client_provider('client_id_one', ['internal' => 1]);

        $id = $provider->id;
        self::expectException(delete_provider_exception::class);
        $this->resolve_graphql_mutation(self::MUTATION, ['id' => $id]);
    }

}