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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_oauth2
 */
use core_phpunit\testcase;
use totara_oauth2\entity\client_provider;
use totara_oauth2\testing\generator;

/**
 * @group totara_oauth2
 */
class totara_oauth2_client_provider_repository_testcase extends testcase {
    /**
     * @return void
     */
    public function test_find_by_client_id(): void {
        $generator = generator::instance();
        $client_provider = $generator->create_client_provider("client_id_one");

        $repository = client_provider::repository();

        $result_one = $repository->find_by_client_id("client_id_two");
        self::assertNull($result_one);

        $result_two = $repository->find_by_client_id("client_id_one");
        self::assertNotNull($result_two);

        self::assertEquals($client_provider->id, $result_two->id);
        self::assertEquals($client_provider->client_id, $result_two->client_id);
        self::assertEquals($client_provider->client_secret, $result_two->client_secret);

        self::assertEquals($client_provider->status, true);

        $result_two = $repository->find_by_client_id("client_id_one", false, false);
        self::assertNull($result_two);
    }
}