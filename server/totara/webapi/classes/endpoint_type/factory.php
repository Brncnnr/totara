<?php
/**
 * This file is part of Totara TXP
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package totara_webapi
 */

namespace totara_webapi\endpoint_type;

use coding_exception;
use core_component;
use ReflectionClass;

class factory {

    /**
     * Get an array of class names containing all the valid webapi types.
     * Names should be unique - in cases where the names are the same the one found
     * first in the type array will be used. See get_type_class below.
     *
     * @return array
     */
    public static function get_all_types(): array {
        // Get all types in totara_webapi component.
        $types = core_component::get_namespace_classes(
            'endpoint_type',
            base::class,
            'totara_webapi'
        );

        // Get all types from other components.
        return array_merge($types,
            core_component::get_namespace_classes(
                'totara_webapi\\endpoint_type',
                base::class
            )
        );
    }

    /**
     * Check if a type string exists as an endpoint type class
     *
     * @param string $type
     * @return bool
     */
    public static function type_exists(string $type): bool {
        $class_name = self::get_type_class($type);
        return !empty($class_name);
    }

    /**
     * Get the class name for the endpoint type.
     *
     * @param string $type
     * @return string|null
     */
    private static function get_type_class(string $type): ?string {
        $all_types = self::get_all_types();
        $all_types = array_filter($all_types, function ($class) use ($type) {
            return strpos($class, "totara_webapi\\endpoint_type\\$type") !== false;
        });
        return empty($all_types) ? null : reset($all_types);
    }

    /**
     * @param string $type String name of the type to create an instance of.
     * @return base
     * @throws coding_exception
     */
    public static function get_instance(string $type): base {
        $class_name = self::get_type_class($type);
        if (empty($class_name)) {
            throw new coding_exception("Invalid type '$type'");
        }
        return self::get_instance_by_class_name($class_name);
    }

    /**
     * Get an instance of the endpoint type class.
     *
     * @param string $type_class_name
     * @return base
     * @throws coding_exception
     */
    public static function get_instance_by_class_name(string $type_class_name): base {
        if (!class_exists($type_class_name)
            || !is_a($type_class_name, base::class, true)
            || (new ReflectionClass($type_class_name))->isAbstract()) {
            throw new coding_exception("$type_class_name is not a valid webapi type");
        }
        return new $type_class_name();
    }

}