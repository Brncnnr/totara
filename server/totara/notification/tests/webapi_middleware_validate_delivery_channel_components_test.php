<?php

use core\webapi\resolver\payload;
use core_phpunit\testcase;

/**
 * This file is part of Totara Learn
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
 * @package totara_notification
 */

use core\webapi\execution_context;
use totara_notification\loader\delivery_channel_loader;
use totara_notification\webapi\middleware\validate_delivery_channel_components;
use core\webapi\resolver\result;

class totara_notification_webapi_middleware_validate_delivery_channel_components_testcase extends testcase {
    /**
     * @return void
     */
    public function test_validate_required_identifiers(): void {
        $ec = execution_context::create('dev');
        $payload = new payload([], $ec);

        $middleware = new validate_delivery_channel_components('identifiers', true);
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The field 'identifiers' is required for validation");

        $middleware->handle(
            $payload,
            function (payload $payload): result {
                return new result($payload->get_variables());
            }
        );
    }

    /**
     * @return void
     */
    public function test_validate_identifiers_that_has_invalid_values(): void {
        $ec = execution_context::create('dev');
        $payload = new payload(
            ['identifiers' => ['comment_area']],
            $ec
        );

        $middleware = new validate_delivery_channel_components('identifiers', true);
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The channel 'comment_area' is not a valid delivery channel class");

        $middleware->handle(
            $payload,
            function (payload $payload): result {
                return new result($payload->get_variables());
            }
        );
    }

    /**
     * @return void
     */
    public function test_validate_identifiers(): void {
        $ec = execution_context::create('dev');
        $payload = new payload(
            ['identifiers' => array_keys(delivery_channel_loader::get_defaults())],
            $ec
        );

        $middleware = new validate_delivery_channel_components('identifiers', true);
        $result = $middleware->handle(
            $payload,
            function (payload $payload): result {
                return new result($payload->get_variables());
            }
        );

        $data = $result->get_data();
        self::assertIsArray($data);
        self::assertArrayHasKey('identifiers', $data);
    }
}