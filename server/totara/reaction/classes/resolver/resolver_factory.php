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
 * @package totara_reaction
 */
namespace totara_reaction\resolver;

/**
 * Factory class to get all other resolver based on the component.
 */
final class resolver_factory {
    /**
     * @var base_resolver
     */
    private static $default;

    /**
     * Preventing this class from being instantiate.
     * resolver_factory constructor.
     */
    private function __construct() {
    }

    /**
     * @param string $component
     * @return base_resolver
     */
    public static function create_resolver(string $component): base_resolver {
        $classes = \core_component::get_namespace_classes(
            'totara_reaction\\resolver',
            base_resolver::class,
            $component
        );

        if (empty($classes)) {
            if (isset(static::$default) && static::$default->get_component() == $component) {
                return static::$default;
            }

            throw new \coding_exception("No resolver class for reaction found for component '{$component}'");
        } else if (1 !== count($classes)) {
            debugging("There are more than one class that extending resolver", DEBUG_DEVELOPER);
        }

        $cls = reset($classes);
        return new $cls();
    }

    /**
     * @param base_resolver $resolver
     * @return void
     */
    public static function phpunit_set_resolver(base_resolver $resolver): void {
        if (!defined('PHPUNIT_TEST') || !PHPUNIT_TEST) {
            debugging("Cannot set the resolver when it is not unit test environment", DEBUG_DEVELOPER);
            return;
        }

        static::$default = $resolver;
    }

    /**
     * @return void
     */
    public static function phpunit_clear_resolver(): void {
        static::$default = null;
    }
}