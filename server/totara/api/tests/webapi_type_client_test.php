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

use core_phpunit\testcase;
use totara_api\testing\generator;
use totara_webapi\phpunit\webapi_phpunit_helper;
use totara_api\webapi\resolver\type\client;
use totara_api\model\client as model;

/**
 * @group totara_api
 */
class totara_api_webapi_type_client_testcase extends testcase {
    use webapi_phpunit_helper;

    /**
     * @var model
     */
    protected $model;

    protected function setUp(): void {
        parent::setUp();

        $generator = generator::instance();
        $entity = $generator->create_client();

        $this->model = model::load_by_entity($entity);
    }

    protected function tearDown(): void {
        parent::tearDown();
        $this->model = null;
    }

    /**
     * @return void
     */
    public function test_resolve_field_name(): void {
        $value = $this->resolve_graphql_type(
            $this->get_graphql_name(client::class),
            'name',
            $this->model
        );

        self::assertEquals($this->model->name, $value);
    }

    /**
     * @return void
     */
    public function test_resolve_field_description(): void {
        $value = $this->resolve_graphql_type(
            $this->get_graphql_name(client::class),
            'description',
            $this->model
        );

        self::assertEquals($this->model->description, $value);
    }

    /**
     * @return void
     */
    public function test_resolve_field_tenant_id(): void {
        $value = $this->resolve_graphql_type(
            $this->get_graphql_name(client::class),
            'tenant_id',
            $this->model
        );

        self::assertEquals($this->model->tenant_id, $value);
    }

    /**
     * @return void
     */
    public function test_resolve_field_id(): void {
        $value = $this->resolve_graphql_type(
            $this->get_graphql_name(client::class),
            'id',
            $this->model
        );

        self::assertEquals($this->model->id, $value);
    }

    /**
     * @return void
     */
    public function test_resolve_oauth2_client_providers(): void {
        $value = $this->resolve_graphql_type(
            $this->get_graphql_name(client::class),
            'oauth2_client_providers',
            $this->model
        );
        self::assertEquals([], $value);
    }
}