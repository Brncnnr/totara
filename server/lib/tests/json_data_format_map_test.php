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
use core_phpunit\testcase;
use core\json\cache\map\data_format_map;
use core\json\data_format\param_alpha;

class core_json_data_format_map_testcase extends testcase {
    /**
     * @return void
     */
    public function test_add_classes_for_component(): void {
        $map = new data_format_map();
        self::assertEmpty($map->get_all_classes());

        $map->add_classes_for_component('core', [param_alpha::class]);
        self::assertEquals([param_alpha::class], $map->get_all_classes());
    }

    /**
     * @return void
     */
    public function test_add_classes_for_component_with_invalid_classes(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage(
            "The format class 'core_json_data_format_map_testcase' is not a child of 'core\\json\\data_format\\data_format'"
        );

        $map = new data_format_map();
        $map->add_classes_for_component('core', [static::class]);
    }

    /**
     * @return void
     */
    public function test_prepare_caches(): void {
        $map = new data_format_map();
        self::assertEmpty($map->prepare_to_cache());

        $map->add_classes_for_component('core', [param_alpha::class]);
        self::assertEquals(
            ['core' => [param_alpha::class]],
            $map->prepare_to_cache()
        );
    }

    /**
     * @return void
     */
    public function test_wake_up_from_cache(): void {
        $map = data_format_map::wake_from_cache([
            'core' => [param_alpha::class]
        ]);

        self::assertEquals([param_alpha::class], $map->get_all_classes());
        self::assertEquals(
            ['core' => [param_alpha::class]],
            $map->prepare_to_cache()
        );
    }

    /**
     * @return void
     */
    public function test_wake_from_cache_with_invalid_component_name(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The component name '0' is invalid");

        data_format_map::wake_from_cache([[param_alpha::class]]);
    }

    /**
     * @return void
     */
    public function test_wake_from_cache_with_invalid_data(): void {
        $invalid_data = [
            'x',
            0,
            1.1,
            false
        ];

        foreach ($invalid_data as $invalid_datum) {
            try {
                data_format_map::wake_from_cache($invalid_datum);
                self::fail('Expects the wake from cache should yield errors');
            } catch (coding_exception $e) {
                self::assertStringContainsString(
                    'Invalid cache data',
                    $e->getMessage()
                );
            }
        }
    }
}