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
namespace core\json\data_format;

use core\json\cache\cache_helper;
use core\json\cache\map\data_format_map;
use core_component;

final class factory {
    /**
     * factory constructor.
     * Prevent this factory class from instantiation.
     */
    private function __construct() {
    }

    /**
     * Returns an a hashmap of component and its collection data format.
     * @example
     *  return [
     *      'core' => [
     *          'path\\to\\class\\param_alpha'
     *      ]
     *  ]
     *
     * @param bool $reload
     * @return data_format_map
     */
    public static function load(bool $reload = false): data_format_map {
        // We are using the cache system in place to make sure that this process
        // of discovering all the sub classes of the data_format can perform better.
        // The reason why it is performing poorly without caching, is because the
        // function core_component::get_namespace_classes has to iterate through all known
        // classes and uses string matching to reduce the list to the desired classes.
        // Which it would consume lots of resources everytime this function is called.
        $map = cache_helper::get_cache_data_format();

        if (null !== $map && !$reload) {
            return $map;
        }

        $map = new data_format_map();

        // Get the classes from core first.
        $map->add_classes_for_component(
            'core',
            core_component::get_namespace_classes(
                'json\\data_format',
                data_format::class,
                'core'
            )
        );

        // Then get the classes from the plugins system.
        $plugin_types = core_component::get_plugin_types();
        $plugin_types = array_keys($plugin_types);

        foreach ($plugin_types as $plugin_type) {
            $plugins = core_component::get_plugin_list($plugin_type);
            $plugins = array_keys($plugins);

            foreach ($plugins as $plugin_name) {
                $component = "{$plugin_type}_{$plugin_name}";
                $format_classes = core_component::get_namespace_classes(
                    'core_json\\data_format',
                    data_format::class,
                    $component
                );

                if (!empty($format_classes)) {
                    $map->add_classes_for_component($component, $format_classes);
                }
            }
        }

        cache_helper::cache_data_format($map);
        return $map;
    }

    /**
     * Returns the instances of data_format.
     *
     * @param bool $reload
     * @return data_format[]
     */
    public static function get_all_formats(bool $reload = false): array {
        $map = self::load($reload);
        $all_classes = $map->get_all_classes();

        if (empty($all_classes)) {
            return [];
        }

        $formats = [];

        foreach ($all_classes as $cls_name) {
            $formats[] = new $cls_name();
        }

        return $formats;
    }
}