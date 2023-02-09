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
use core_phpunit\testcase;
use core\testing\mock\json\simple_structure;

class core_json_structure_map_testcase extends testcase {
    /**
     * @covers \core\json\cache\map\structure_map::build_classes_map
     * @return void
     */
    public function test_build_structure_classes(): void {
        $ref_class = new ReflectionClass(structure_map::class);
        $ref_method = $ref_class->getMethod('build_classes_map');
        $ref_method->setAccessible(true);

        self::assertEquals(
            ['simple_structure' => simple_structure::class],
            $ref_method->invokeArgs(null, [[simple_structure::class]])
        );
    }

    /**
     * @return void
     */
    public function test_build_structure_classes_with_invalid_classes(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage(
            "The class 'core_phpunit\\testcase' is not a child of 'core\json\structure\structure'"
        );

        $ref_class = new ReflectionClass(structure_map::class);
        $ref_method = $ref_class->getMethod('build_classes_map');
        $ref_method->setAccessible(true);

        $ref_method->invokeArgs(null, [[testcase::class]]);
    }

    /**
     * @return void
     */
    public function test_add_classes_to_component(): void {
        $map = new structure_map();
        self::assertEmpty($map->get_maps());

        $map->add_classes_to_component('core', [simple_structure::class]);
        self::assertEquals(
            ['core' => ['simple_structure' => simple_structure::class]],
            $map->get_maps()
        );
    }

    /**
     * @return void
     */
    public function test_add_classes_with_invalid_classes(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage(
            "The class 'core_json_structure_map_testcase' is not a child of 'core\\json\\structure\\structure'"
        );

        $map = new structure_map();
        $map->add_classes_to_component('core', [static::class]);
    }

    /**
     * @return void
     */
    public function test_prepare_to_cache(): void {
        $map = new structure_map();
        $map->add_classes_to_component('core', [simple_structure::class]);
        $map->add_classes_to_component('totara_core', [simple_structure::class]);

        self::assertEquals(
            [
                'core' => ['simple_structure' => simple_structure::class],
                'totara_core' => ['simple_structure' => simple_structure::class]
            ],
            $map->prepare_to_cache()
        );
    }

    /**
     * @return void
     */
    public function test_wake_from_cache(): void {
        $map = structure_map::wake_from_cache([
            'core' => [
                'simple_structure' => simple_structure::class
            ]
        ]);

        self::assertEquals(
            ['core' => ['simple_structure' => simple_structure::class]],
            $map->prepare_to_cache()
        );
    }

    /**
     * @return void
     */
    public function test_wake_from_cache_with_invalid_component(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('The component key is invalid \'0\'');

        structure_map::wake_from_cache([
            ['simple_structure' => simple_structure::class]
        ]);
    }

    /**
     * @return void
     */
    public function test_wake_from_cache_with_invalid_name(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('The name for class map is invalid \'0\'');

        structure_map::wake_from_cache([
            'core' => [simple_structure::class]
        ]);
    }

    /**
     * @return void
     */
    public function test_find_structure_class(): void {
        $map = new structure_map();
        self::assertNull($map->find_structure_class('simple_structure', 'core'));

        $map->add_classes_to_component('core', [simple_structure::class]);
        self::assertEquals(
            simple_structure::class,
            $map->find_structure_class('simple_structure', 'core')
        );
    }

    /**
     * @return void
     */
    public function test_find_structure_class_with_poluted_data(): void {
        $map = new structure_map();

        $ref_class = new ReflectionClass($map);
        $property = $ref_class->getProperty('maps');

        $property->setAccessible(true);
        $property->setValue($map, ['core' => ['x' => static::class]]);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage(
            "The class 'core_json_structure_map_testcase' is not a child of 'core\\json\\structure\\structure'"
        );

        $map->find_structure_class('x', 'core');
    }

    /**
     * @return void
     */
    public function test_wake_up_from_cache_with_invalid_data(): void {
        $invalid_data = ['x', 0, 1.1, true];

        foreach ($invalid_data as $invalid_datum) {
            try {
                structure_map::wake_from_cache($invalid_datum);
                self::fail("Expect the wake from cache should yield errors");
            } catch (coding_exception $e) {
                self::assertStringContainsString(
                    "Invalid cache data, expects to be an array",
                    $e->getMessage()
                );
            }
        }
    }
}