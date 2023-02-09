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
use core\json\structure\factory;
use core\testing\mock\json\simple_structure;
use core_phpunit\testcase;
use core\json\cache\cache_helper;

class core_json_structure_factory_testcase extends testcase {
    /**
     * @return void
     */
    public function test_get_structure_class_by_name_and_component(): void {
        $map = new structure_map();
        cache_helper::cache_structure($map);

        self::assertNull(factory::get_structure_class_name('simple_structure', 'core'));

        $map->add_classes_to_component('core', [simple_structure::class]);
        cache_helper::cache_structure($map);

        self::assertIsString(factory::get_structure_class_name('simple_structure', 'core'));
        self::assertEquals(simple_structure::class, factory::get_structure_class_name('simple_structure', 'core'));
    }

    /**
     * @return void
     */
    public function test_get_structure_map_with_reload(): void {
        $expected_map = new structure_map();
        $expected_map->add_classes_to_component('core', [simple_structure::class]);

        $result_map = factory::load();
        self::assertNotEquals(
            $expected_map->prepare_to_cache(),
            $result_map->prepare_to_cache()
        );

        cache_helper::cache_structure($expected_map);
        $result_map = factory::load();

        self::assertEquals($expected_map->prepare_to_cache(), $result_map->prepare_to_cache());
        self::assertNotEquals(
            $expected_map->prepare_to_cache(),
            factory::load(true)->prepare_to_cache()
        );
    }
}