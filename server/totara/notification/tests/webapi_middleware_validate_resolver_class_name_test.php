<?php
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
use core\webapi\resolver\payload;
use core\webapi\resolver\result;
use core_phpunit\testcase;
use totara_notification\testing\generator;
use totara_notification\webapi\middleware\validate_resolver_class_name;
use totara_notification_mock_notifiable_event_resolver as mock_resolver;

class totara_notification_webapi_middleware_validate_resolver_class_name_testcase extends testcase {
    /**
     * @return void
     */
    public function test_validate_invalid_notifiable_event_resolver(): void {
        $ec = execution_context::create('dev');
        $payload = new payload(['resolver_class' => 'dota_2'], $ec);

        $middleware = new validate_resolver_class_name('resolver_class', true);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The resolver class is not a notifiable event resolver");

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
    public function test_validate_resolver_without_existing_in_payload(): void {
        $ec = execution_context::create('dev');
        $payload = new payload(['ccc' => 'ddd'], $ec);

        $middleware = new validate_resolver_class_name('ddd', true);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The payload does not have variable 'ddd'");

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
    public function test_validate_resolver_without_existing_in_payload_but_not_required(): void {
        $ec = execution_context::create('dev');
        $payload = new payload(['ccc' => 'dd'], $ec);

        $middleware = new validate_resolver_class_name('ddd');
        $result = $middleware->handle(
            $payload,
            function (payload $payload): result {
                return new result($payload->get_variables());
            }
        );

        // Nothing should really be changed.
        $data = $result->get_data();
        self::assertSame(['ccc' => 'dd'], $data);
    }

    /**
     * @return void
     */
    public function test_validate_notifiable_event_resolver(): void {
        $generator = generator::instance();
        $generator->include_mock_notifiable_event_resolver();

        $ec = execution_context::create('dev');
        $payload = new payload(
            ['resolver_name' => mock_resolver::class],
            $ec
        );

        $middleware = new validate_resolver_class_name('resolver_name', true);
        $result = $middleware->handle(
            $payload,
            function (payload $payload): result {
                return new result($payload->get_variables());
            }
        );

        $data = $result->get_data();
        self::assertSame(
            ['resolver_name' => mock_resolver::class],
            $data
        );
    }
}