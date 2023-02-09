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

use core_phpunit\testcase;
use totara_oauth2\testing\generator;
use totara_webapi\phpunit\webapi_phpunit_helper;
use totara_oauth2\webapi\resolver\type\client_provider;
use totara_oauth2\model\client_provider as model;

/**
 * @group totara_oauth2
 */
class totara_oauth2_webapi_type_client_provider_testcase extends testcase {
    use webapi_phpunit_helper;

    /**
     * @var model
     */
    protected $model;

    protected function setUp(): void {
        parent::setUp();

        $generator = generator::instance();
        $entity = $generator->create_client_provider("client_id_one");

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
            $this->get_graphql_name(client_provider::class),
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
            $this->get_graphql_name(client_provider::class),
            'description',
            $this->model
        );

        self::assertEquals($this->model->description, $value);
    }

    /**
     * @return void
     */
    public function test_resolve_field_client_id(): void {
        $value = $this->resolve_graphql_type(
            $this->get_graphql_name(client_provider::class),
            'client_id',
            $this->model
        );

        self::assertEquals($this->model->client_id, $value);
    }

    /**
     * @return void
     */
    public function test_resolve_field_client_secret(): void {
        $value = $this->resolve_graphql_type(
            $this->get_graphql_name(client_provider::class),
            'client_secret',
            $this->model
        );

        self::assertEquals($this->model->client_secret, $value);
    }

    /**
     * @return void
     */
    public function test_resolve_field_id(): void {
        $value = $this->resolve_graphql_type(
            $this->get_graphql_name(client_provider::class),
            'id',
            $this->model
        );

        self::assertEquals($this->model->id, $value);
    }
}