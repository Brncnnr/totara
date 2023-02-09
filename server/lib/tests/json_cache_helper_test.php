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

use core\json\cache\map\data_format_map;
use core\json\cache\map\structure_map;
use core_phpunit\testcase;
use core\json\cache\cache_helper;

class core_json_cache_helper_testcase extends testcase {
    /**
     * @return void
     */
    public function test_get_structure(): void {
        self::assertNull(cache_helper::get_cache_structure());

        $map = new structure_map();
        cache_helper::cache_structure($map);

        self::assertInstanceOf(
            structure_map::class,
            cache_helper::get_cache_structure(),
        );

        $cached_map = cache_helper::get_cache_structure();
        self::assertEmpty($cached_map->prepare_to_cache());
    }

    /**
     * @return void
     */
    public function test_get_data_format(): void {
        self::assertNull(cache_helper::get_cache_data_format());
        $map = new data_format_map();
        cache_helper::cache_data_format($map);

        $cached_map = cache_helper::get_cache_data_format();

        self::assertInstanceOf(data_format_map::class, $cached_map);
        self::assertEmpty($cached_map->prepare_to_cache());
    }
}