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
namespace core\json\structure;

use core\json\cache\cache_helper;
use core\json\cache\map\structure_map;
use core_component;

/**
 * A factory class to build up the map of json schema classes.
 */
final class factory {
    /**
     * factory constructor.
     * Prevent this class from instantiation.
     */
    private function __construct() {
    }

    /**
     * Returns the hashmap for json schema, the structure of hashmap will look like
     * the example below
     * @example
     *  return [
     *      'core' => [
     *          'structure_name' => 'class\\path\\to\\structure_name'
     *      ]
     *  ]
     *
     *
     * @param bool $reload
     * @return structure_map
     */
    public static function load(bool $reload = false): structure_map {
        // We are using the cache system in place to make sure that this process
        // of discovering all the sub classes of the data_format can perform better.
        // The reason why it is performing poorly without caching, is because the
        // function core_component::get_namespace_classes has to iterate through all known
        // classes and uses string matching to reduce the list to the desired classes.
        // Which it would consume lots of resources everytime this function is called.
        $structure_map = cache_helper::get_cache_structure();

        if (null !== $structure_map && !$reload) {
            return $structure_map;
        }

        $structure_map = new structure_map();
        // This is where to add structure classes that sits in core.

        $plugin_types = core_component::get_plugin_types();
        $plugin_types = array_keys($plugin_types);

        foreach ($plugin_types as $plugin_type) {
            $plugins = core_component::get_plugin_list($plugin_type);
            $plugins = array_keys($plugins);

            foreach ($plugins as $plugin_name) {
                $component = "{$plugin_type}_{$plugin_name}";
                $structure_classes = core_component::get_namespace_classes(
                    'core_json\\structure',
                    structure::class,
                    $component
                );

                if (empty($structure_classes)) {
                    continue;
                }

                $structure_map->add_classes_to_component($component, $structure_classes);
            }
        }

        cache_helper::cache_structure($structure_map);
        return $structure_map;
    }

    /**
     * Returns the structure class name. Null if nothing is found.
     *
     * @param string $component
     * @param string $name
     * @param bool   $reload
     *
     * @return string|null
     */
    public static function get_structure_class_name(string $name, string $component, bool $reload = false): ?string {
        $map = self::load($reload);
        return $map->find_structure_class($name, $component);
    }
}