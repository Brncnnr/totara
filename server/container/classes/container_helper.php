<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core_container
 */
namespace core_container;

use core\orm\query\builder;

final class container_helper {
    /**
     * Preventing this class from being instantiation
     * container_helper constructor.
     */
    private function __construct() {
    }

    /**
     * Checking whether the container's attribute value has been taken or not.
     *
     * @param string   $field
     * @param          $value
     * @param int|null $excludedid
     *
     * @return bool
     */
    public static function is_container_existing_with_field(string $field, $value, int $excludedid = null): bool {
        if (null === $value || '' === $value) {
            // No point to check when the value is pretty much empty. We do exclude zero.
            return false;
        }

        $builder = builder::table('course');
        $builder->where($field, $value);

        if (null != $excludedid) {
            // Including zero check.
            $builder->where('id', '<>', $excludedid);
        }

        return $builder->exists();
    }

    /**
     * Returning the container type - franken style base on the class name.
     *
     * @param string|object $value  Value can be a class name, or it can be an object.
     * @return string
     */
    public static function get_container_type_from_classname($value): string {
        $classname = null;

        if (is_string($value)) {
            if (!class_exists($value)) {
                throw new \coding_exception("Cannot find the class '{$value}'");
            }

            $classname = $value;
        } else if (is_object($value)) {
            $classname = get_class($value);
        } else {
            throw new \coding_exception("Invalid parameter of value");
        }

        $parts = explode("\\", $classname);
        $component = (string) reset($parts);

        // Make sure that it is a container component, and not a core_container component.
        if (false === stripos($component, 'container') || \core_component::is_core_subsystem($component)) {
            throw new \coding_exception("Cannot find a container type from value");
        }

        return $component;
    }
}