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

use core\format;
use core\orm\query\builder;
use core_phpunit\testcase;
use totara_oauth2\model\client_provider;
use totara_webapi\phpunit\webapi_phpunit_helper;
use totara_oauth2\exception\create_provider_exception;

/**
 * @group totara_oauth2
 */
class totara_oauth2_webapi_resolver_mutation_create_client_provider_testcase extends testcase {
    use webapi_phpunit_helper;

    private const MUTATION = 'totara_oauth2_create_provider';

    /**
     * @return void
     */
    public function test_create_client_providers_by_admin(): void {
        self::setAdminUser();
        $name = 'test name';

        $db = builder::get_db();
        self::assertFalse($db->record_exists('totara_oauth2_client_provider', ['name' => $name]));
        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'name' => $name,
                    'scope_type' => 'XAPI_WRITE'
                ]
            ]
        );

        self::assertNotNull($result);
        self::assertEquals($name, $result->name);
        self::assertEquals(true, $result->status);
    }

    /**
     * @return void
     */
    public function test_create_client_providers_by_system_user(): void {
        $user = self::getDataGenerator()->create_user();
        self::setUser($user);

        self::expectException(required_capability_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'name' => 'test name',
                    'scope_type' => 'XAPI_WRITE'
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_client_providers_with_exception(): void {
        self::setAdminUser();

        self::expectException(\totara_oauth2\exception\create_provider_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'name' => 'test name',
                    'scope_type' => 'wrong one'
                ]
            ]
        );
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

        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'name' => 'test name',
                    'scope_type' => 'XAPI_WRITE'
                ]
            ]
        );

        self::assertNotNull($result);
        self::assertEquals('test name', $result->name);
    }

    /**
     * @return void
     */
    public function test_create_client_provider_with_invalid_format(): void {
        self::setAdminUser();

        self::expectExceptionMessage(get_string('error_invalid_format', 'totara_oauth2'));
        self::expectException(create_provider_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'name' => 'test name',
                    'scope_type' => 'XAPI_WRITE',
                    'format' => FORMAT_HTML
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_client_provider_with_valid_format(): void {
        self::setAdminUser();

        $modal = client_provider::create("<h1>test name</h1>", 'XAPI_WRITE', FORMAT_PLAIN);
        self::assertEquals(
            'test name',
            $this->resolve_graphql_type(
                'totara_oauth2_client_provider',
                'name',
                $modal,
                ['format' => format::FORMAT_PLAIN]
            )
        );

        self::assertEquals(
            "<h1>test name</h1>",
            $this->resolve_graphql_type(
                'totara_oauth2_client_provider',
                'name',
                $modal,
                ['format' => format::FORMAT_RAW]
            )
        );
    }


    /**
     * @return void
     */
    public function test_create_client_provider_with_invalid_description(): void {
        self::setAdminUser();

        $description = 'ABCDEFGHIJ';
        for ($i = 0; $i <= 101; $i++) {
            $description .= 'ABCDEFGHIJ';
        }
        self::assertGreaterThan(1024, strlen($description));

        self::expectExceptionMessage(get_string('error_provider_description_length', 'totara_oauth2'));
        self::expectException(create_provider_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'name' => 'test name',
                    'description' => $description,
                    'scope_type' => 'XAPI_WRITE'
                ]
            ]
        );
    }
}