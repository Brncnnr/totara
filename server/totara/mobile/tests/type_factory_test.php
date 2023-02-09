<?php
/**
 * This file is part of Totara TXP
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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_mobile
 */

use core_phpunit\testcase;
use totara_mobile\totara_webapi\endpoint_type\mobile;
use totara_webapi\graphql;
use totara_webapi\endpoint_type\factory as endpoint_type_factory;

class totara_mobile_type_factory_testcase extends testcase {

    public function test_get_all_types(): void {
        $all_types = endpoint_type_factory::get_all_types();
        $this->assertContains(mobile::class, $all_types);
    }

    public function test_mobile_exists(): void {
        $this->assertTrue(endpoint_type_factory::type_exists(graphql::TYPE_MOBILE));
    }

    public function test_instances(): void {
        $this->assertInstanceOf(mobile::class, endpoint_type_factory::get_instance(graphql::TYPE_MOBILE));
    }

}