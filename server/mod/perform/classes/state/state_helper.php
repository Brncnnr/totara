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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\state;

use coding_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * This class provides some state related convenience methods.
 */
class state_helper {

    private static $get_all_cache = [];

    /**
     * Get all state classes for the given object type.
     *
     * @param string $object_type
     * @return state[]
     */
    public static function get_all(string $object_type): array {
        if (isset(static::$get_all_cache[$object_type])) {
            return static::$get_all_cache[$object_type];
        }

        static::$get_all_cache[$object_type] = \core_component::get_namespace_classes(
            'state\\' . $object_type,
            'mod_perform\state\state',
            'mod_perform'
        );

        return static::$get_all_cache[$object_type];
    }

    /**
     * Get an array of all state names, indexed by state code.
     *
     * @param string $object_type
     * @param string $state_type The state type. e.g progress, availability.
     * @return array
     */
    public static function get_all_names(string $object_type, string $state_type): array {
        $translated = [];
        foreach (self::get_all($object_type) as $state_class) {
            if ($state_class::get_type() !== $state_type) {
                continue;
            }
            $translated[$state_class::get_code()] = $state_class::get_name();
        }
        return $translated;
    }

    /**
     * Get an array of all translated state names, indexed by state code.
     *
     * @param string $object_type
     * @param string $state_type The state type. e.g progress, availability.
     * @return array
     */
    public static function get_all_display_names(string $object_type, string $state_type): array {
        $translated = [];
        foreach (self::get_all($object_type) as $state_class) {
            if ($state_class::get_type() !== $state_type) {
                continue;
            }
            $translated[$state_class::get_code()] = $state_class::get_display_name();
        }
        return $translated;
    }

    /**
     * Get state class from DB code.
     *
     * @param int $code The code to create a state of.
     * @param string $object_type
     * @param string $state_type The status type. e.g progress, availability.
     * @return string|state
     */
    public static function from_code(int $code, string $object_type, string $state_type): string {
        $all_states = static::get_all($object_type);
        foreach ($all_states as $state_class) {
            if ($state_class::get_type() !== $state_type) {
                continue;
            }
            if ($state_class::get_code() === $code) {
                return $state_class;
            }
        }
        throw new coding_exception("Cannot find state with code: $code");
    }

    /**
     * Get state class from the name.
     *
     * @param string $name The name to create a state of.
     * @param string $object_type
     * @param string $state_type The status type. e.g progress, availability.
     * @return string|state
     */
    public static function from_name(string $name, string $object_type, string $state_type): string {
        $all_states = static::get_all($object_type);
        foreach ($all_states as $state_class) {
            if ($state_class::get_type() !== $state_type) {
                continue;
            }
            if ($state_class::get_name() === $name) {
                return $state_class;
            }
        }
        throw new coding_exception("Cannot find state with name: $name");
    }

}
