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
 * @package totara_webapi
 */

use core_phpunit\testcase;
use totara_webapi\graphql;
use totara_webapi\endpoint_type\ajax;
use totara_webapi\endpoint_type\dev;
use totara_webapi\endpoint_type\external;
use totara_webapi\endpoint_type\factory as endpoint_type_factory;

class totara_webapi_type_factory_testcase extends testcase {

    public function test_get_all_types(): void {
        $all_types = endpoint_type_factory::get_all_types();
        $this->assertContains(ajax::class, $all_types);
        $this->assertContains(dev::class, $all_types);
    }

    public function test_types_exist(): void {
        $this->assertTrue(endpoint_type_factory::type_exists(graphql::TYPE_AJAX));
        $this->assertTrue(endpoint_type_factory::type_exists(graphql::TYPE_DEV));
        $this->assertTrue(endpoint_type_factory::type_exists(graphql::TYPE_EXTERNAL));
    }

    public function test_types_not_exist(): void {
        $this->assertFalse(endpoint_type_factory::type_exists('foo'));
        $this->assertFalse(endpoint_type_factory::type_exists('bar'));
    }

    public function test_instances(): void {
        $this->assertInstanceOf(ajax::class, endpoint_type_factory::get_instance(graphql::TYPE_AJAX));
        $this->assertInstanceOf(dev::class, endpoint_type_factory::get_instance(graphql::TYPE_DEV));
        $this->assertInstanceOf(external::class, endpoint_type_factory::get_instance(graphql::TYPE_EXTERNAL));
    }

    public function test_invalid_instances(): void {
        try {
            endpoint_type_factory::get_instance('foo');
            $this->fail('An instance should not be created for an invalid type');
        } catch (coding_exception $e) {
            $this->assertEquals(
                "Coding error detected, it must be fixed by a programmer: Invalid type 'foo'",
                $e->getMessage()
            );
        }
    }

}