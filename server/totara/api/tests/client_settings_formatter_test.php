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

use core_phpunit\testcase;
use totara_api\formatter\client_settings_formatter;
use totara_api\model\client as client_model;

/**
 * Unit tests for client_settings_formatter.
 * @group totara_api
 */
class totara_api_client_settings_formatter_testcase extends testcase {
    /**
     * @return void
     */
    public function test_client_setting_formatter(): void {
        $generator = self::getDataGenerator();

        $user = $generator->create_user();

        // Set up
        // Get a client model first
        $name = 'test client';
        $description = 'description_test';
        $model_client = client_model::create($name, $user->id, $description);

        $model_client_settings = $model_client->client_settings;
        $formatter = new client_settings_formatter($model_client_settings,  context_system::instance());

        self::assertEquals($model_client_settings->default_token_expiry_time, $formatter->format('default_token_expiry_time'));
        self::assertEquals($model_client_settings->client_id, $formatter->format('client_id'));
        self::assertEquals($model_client_settings->client_rate_limit, $formatter->format('client_rate_limit'));
        self::assertEquals($model_client_settings->response_debug, $formatter->format('response_debug'));
    }

}