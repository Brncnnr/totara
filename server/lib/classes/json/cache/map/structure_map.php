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
use core\json\structure\structure;

/**
 * Internal API, please do not use it outside of the json validator library.
 */
class structure_map implements cacheable_object {
    /**
     * The array should looks something like this:
     * [
     *  'core' => [
     *      'dummy_structure' => 'core\\testing\\mock\\dummy_structure'
     *  ]
     * ]
     *
     * @var array
     */
    private $maps;

    /**
     * structure_map constructor.
     */
    public function __construct() {
        $this->maps = [];
    }

    /**
     * Given the array of structure full class names, this function will map those
     * full name with its shortname.
     *
     * The data map will look like something bellow:
     * ['dummy_structure' => 'core\\testing\\mock_]
     *
     * @param string $component
     * @param array  $structure_classes
     * @return void
     */
    public function add_classes_to_component(string $component, array $structure_classes): void {
        if (!isset($this->structure_maps[$component])) {
            $this->maps[$component] = [];
        }

        $this->maps[$component] = array_merge(
            $this->maps[$component],
            self::build_classes_map($structure_classes)
        );
    }

    /**
     * The list of the class name.
     *
     * @param string[] $structure_classes
     * @return array
     */
    private static function build_classes_map(array $structure_classes): array {
        $maps = [];
        $parent_class = structure::class;

        foreach ($structure_classes as $class_name) {
            if (!is_subclass_of($class_name, $parent_class)) {
                throw new coding_exception(
                    "The class '{$class_name}' is not a child of '{$parent_class}'"
                );
            }

            $class_name = ltrim($class_name, '\\');
            $parts = explode('\\', $class_name);

            $name = array_pop($parts);
            $maps[$name] = $class_name;
        }

        return $maps;
    }

    /**
     * @return array
     */
    public function prepare_to_cache(): array {
        return $this->get_maps();
    }

    /**
     * @return array
     */
    public function get_maps(): array {
        return $this->maps;
    }

    /**
     * This API is only for the internal usage from cache library.
     * Please do not use it to instantiate the structure map instance.
     * Use the constructor and populate the map nicely with the set of APIs provided instead.
     *
     * @param array $data
     * @return structure_map
     */
    public static function wake_from_cache($data): structure_map {
        if (!is_array($data)) {
            throw new coding_exception("Invalid cache data, expects to be an array");
        }

        $structure_map = new static();

        // This is the process of set the maps from cache data, plus also the validation of it.
        // Normally we should not do this, if it is directly called from cache API but who knows,
        // what would happen to the cache data, as it can be poluted with different data.
        foreach ($data as $component => $structure_classes_map) {
            if (is_numeric($component)) {
                throw new coding_exception("The component key is invalid '{$component}'");
            }

            $structure_map->maps[$component] = [];

            foreach ($structure_classes_map as $name => $class_name) {
                if (is_numeric($name)) {
                    throw new coding_exception("The name for class map is invalid '{$name}'");
                }

                $structure_map->maps[$component][$name] = $class_name;
            }
        }

        return $structure_map;
    }

    /**
     * Returns the structure class name. Null if nothing is found.
     *
     * @param string $name
     * @param string $component
     * @return string|null
     */
    public function find_structure_class(string $name, string $component): ?string {
        if (!isset($this->maps[$component][$name])) {
            return null;
        }

        $class_name = $this->maps[$component][$name];
        $parent_class = structure::class;

        // This checks seems to be redundant in here, as it had already been checked by the setter method
        // where we populate the collection of classes. However, someone can use reflection to perform dangerous
        // things. Hence this check is here :)
        if (!is_subclass_of($class_name, $parent_class)) {
            throw new coding_exception(
                "The class '{$class_name}' is not a child of '{$parent_class}'"
            );
        }

        return $class_name;
    }
}