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
 * @package core
 */

use core\json\cache\cache_helper;
use core\json\cache\map\data_format_map;
use core\json\data_format\factory;
use core_phpunit\testcase;

class core_json_data_format_factory_testcase extends testcase {
    /**
     * @return void
     */
    public function test_get_all_data_format(): void {
        $data_formats = factory::get_all_formats(false);
        self::assertNotEmpty($data_formats);

        // There are more than one data formats from the core system,
        // which they are represent for PARAM_*.
        self::assertGreaterThan(1, count($data_formats));

        // Clear the caches, and fetch again, which it should give us an empty list.
        $map = new data_format_map();
        cache_helper::cache_data_format($map);

        $data_formats = factory::get_all_formats(false);
        self::assertEmpty($data_formats);

        // Reload the cache to get from the system.
        $data_formats = factory::get_all_formats(true);
        self::assertNotEmpty($data_formats);
        self::assertGreaterThan(1, count($data_formats));
    }
}