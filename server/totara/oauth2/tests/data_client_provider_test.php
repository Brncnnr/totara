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
use totara_oauth2\entity\client_provider as entity;
use totara_oauth2\data_provider\client_provider as data_provider;

/**
 * @group totara_oauth2
 */
class totara_oauth2_data_client_provider_testcase extends testcase {
    /**
     * @var entity
     */
    protected $entity;

    protected function setUp(): void {
        parent::setUp();

        $generator = generator::instance();
        $this->entity = $generator->create_client_provider("client_id_one");
    }

    protected function tearDown(): void {
        parent::tearDown();
        $this->entity = null;
    }

    public function test_data_client_provider(): void {
        $data_provider = new data_provider();

        self::assertEquals(1, $data_provider->get()->count());
        $entity = $data_provider->get()->first();
        self::assertEquals($this->entity->id, $entity->id);
        self::assertNotEmpty($data_provider->add_filters(['id' => $this->entity->id])->get()->first());
    }
}