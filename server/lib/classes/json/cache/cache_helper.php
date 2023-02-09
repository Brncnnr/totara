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
namespace core\json\cache;

use cache as inner_cache;
use cache_loader;
use core\json\cache\map\data_format_map;
use core\json\cache\map\structure_map;

/**
 * Internal API of json library. Please do not use outside of this library.
 */
class cache_helper {
    /**
     * The cache key for structure caches.
     *
     * @var string
     */
    private const STRUCTURE_KEY = 'structure';

    /**
     * The cache key for data format caches.
     *
     * @var string
     */
    private const DATA_FORMAT_KEY = 'data_format';

    /**
     * @return cache_loader
     */
    private static function get_loader(): cache_loader {
        return inner_cache::make('core', 'json_schema');
    }

    /**
     * @param structure_map $map
     * @return void
     */
    public static function cache_structure(structure_map $map): void {
        $loader = self::get_loader();
        $loader->set(self::STRUCTURE_KEY, $map);
    }

    /**
     * @return structure_map|null
     */
    public static function get_cache_structure(): ?structure_map {
        $loader = self::get_loader();
        $collection = $loader->get(self::STRUCTURE_KEY);

        if (false === $collection) {
            return null;
        }

        return $collection;
    }

    /**
     * @param data_format_map $map
     * @return void
     */
    public static function cache_data_format(data_format_map $map): void {
        $loader = self::get_loader();
        $loader->set(self::DATA_FORMAT_KEY, $map);
    }

    /**
     * @return data_format_map|null
     */
    public static function get_cache_data_format(): ?data_format_map {
        $loader = self::get_loader();
        $map = $loader->get(self::DATA_FORMAT_KEY);

        if (false === $map) {
            return null;
        }

        return $map;
    }
}