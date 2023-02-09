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
namespace core;

/**
 * Extends this class at plugin level to have your own getter
 * of configuration.
 *
 * Example code for extending this class and have a
 * concrete function for the settings name:
 *
 * @example
 *
 * class seminar_config extends base_plugin_config {
 *      protected static function get_component(): string {
 *          return 'mod_facetoface';
 *      }
 *
 *      public static function get_default_room_id(): {
 *          return static::get('default_room_id', 100);
 *      }
 * }
 */
abstract class base_plugin_config {
    /**
     * Returns the component name that this base plugin config stands for.
     * @return string
     */
    abstract protected static function get_component(): string;

    /**
     * @param string     $name
     * @param mixed|null $default
     *
     * @return mixed|null
     */
    protected static function get(string $name, $default = null) {
        $component = static::get_component();
        $value = get_config($component, $name);

        if (false === $value) {
            // False means that the config item had not yet been set.
            return $default;
        }

        return $value;
    }

    /**
     * @param string     $name
     * @param mixed|null $value
     */
    protected static function set(string $name, $value): void {
        $component = static::get_component();
        set_config($name, $value, $component);
    }

    /**
     * base_plugin_config constructor.
     * Prevent the ability to instantiate this class from its children.
     */
    private function __construct() {
    }
}