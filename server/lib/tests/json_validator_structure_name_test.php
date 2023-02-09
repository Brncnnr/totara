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
 * @package core
 */

use core\json\cache\map\structure_map;
use core\testing\mock\json\simple_structure;
use core_phpunit\testcase;
use core\json\validation_adapter;
use core\json\cache\cache_helper;

class core_json_validator_structure_name_testcase extends testcase {
    /**
     * @return void
     */
    public function test_validate_json_object_with_simple_structure(): void {
        $map = new structure_map();
        $map->add_classes_to_component('core_testing', [simple_structure::class]);

        cache_helper::cache_structure($map);
        $validator = validation_adapter::create_default();
        $valid_json = json_encode([
            'id' => 42,
            'name' => str_repeat('c', 45)
        ]);

        $result = $validator->validate_by_structure_name(
            $valid_json,
            'simple_structure',
            'core_testing'
        );

        self::assertTrue($result->is_valid());
        self::assertEmpty($result->get_error_message());

        $invalid_json = json_encode([
            'id' => 0,
            'name' => str_repeat('d', 45)
        ]);

        $invalid_result = $validator->validate_by_structure_name(
            $invalid_json,
            'simple_structure',
            'core_testing'
        );

        self::assertFalse($invalid_result->is_valid());
        self::assertEquals(
            "Expect the value of field 'id' to exceed 1, actual value is 0.",
            $invalid_result->get_error_message()
        );
    }
}