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
namespace core\json\cache\map;

use cacheable_object;
use coding_exception;
use core\json\data_format\data_format;

class data_format_map implements cacheable_object {
    /**
     * The hashmap that looks something like:
     * [
     *      'core' => [
     *          'path\\to\\class\\param_alpha'
     *      ]
     * ]
     * @var array
     */
    private $map;

    /**
     * data_format_map constructor.
     */
    public function __construct() {
        $this->map = [];
    }

    /**
     * Returns the hashmap.
     * @return array
     */
    public function prepare_to_cache(): array {
        return $this->map;
    }

    /**
     * @param string $component
     * @param array  $format_classes
     *
     * @return void
     */
    public function add_classes_for_component(string $component, array $format_classes): void {
        if (!isset($this->map[$component])) {
            $this->map[$component] = [];
        }

        $parent_class = data_format::class;

        foreach ($format_classes as $format_class) {
            if (!is_subclass_of($format_class, $parent_class)) {
                throw new coding_exception(
                    "The format class '{$format_class}' is not a child of '{$parent_class}'"
                );
            }

            $this->map[$component][] = $format_class;
        }
    }

    /**
     * This API is for internally usage only, which is invoked by the cache library.
     * Please do not use it to construct an instance of this map.
     *
     * @param array $data
     * @return data_format_map
     */
    public static function wake_from_cache($data): data_format_map {
        if (!is_array($data)) {
            throw new coding_exception('Invalid cache data');
        }

        $instance = new static();

        // This is the process of set the maps from cache data, plus also the validation of it.
        // Normally we should not do this, if it is directly called from cache API but who knows,
        // what would happen to the cache data, as it can be poluted with different data.
        foreach ($data as $component => $format_classes) {
            if (is_numeric($component)) {
                throw new coding_exception(
                    "The component name '{$component}' is invalid"
                );
            }

            $instance->add_classes_for_component($component, $format_classes);
        }

        return $instance;
    }

    /**
     * Returns the flatten list of classes name from the map.
     *
     * @return string[]
     */
    public function get_all_classes(): array {
        if (empty($this->map)) {
            return [];
        }

        $all_classes = array_values($this->map);
        return array_merge(...$all_classes);
    }
}