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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_webapi
 */

use core_phpunit\testcase;
use totara_webapi\endpoint_type\ajax;
use totara_webapi\endpoint_type\dev;
use totara_webapi\endpoint_type\external;

class totara_webapi_endpoint_type_testcase extends testcase {

    public function test_validation_rules(): void {
        //Disabled introspection.
        set_config('enable_introspection', 0, 'totara_api');

        $dev = new dev();
        self::assertEmpty($dev->get_validation_rules());

        $ajax = new ajax();
        self::assertEmpty($ajax->get_validation_rules());

        $external = new external();
        self::assertNotEmpty($external->get_validation_rules());
        self::assertCount(2, $external->get_validation_rules());
    }
}