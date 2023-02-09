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
 * @package totara_contentmarketplace
 */
namespace totara_contentmarketplace\learning_object;

use coding_exception;
use core_component;
use totara_contentmarketplace\learning_object\abstraction\resolver;

class factory {
    /**
     * factory constructor.
     */
    private function __construct() {
        // Prevent this class from instantiation.
    }

    /**
     * Given the marketplace's component name (the system component name $type_$name),
     * this function is able to instantiate the resolver instance.
     *
     * @param string $marketplace_component
     * @return resolver
     */
    public static function get_resolver(string $marketplace_component): resolver {
        $resolver_class = self::resolve_resolver_class_name($marketplace_component);

        /** @see resolver::__construct */
        return new $resolver_class();
    }

    /**
     * Given the sub plugin type ($type). This function will try to resolve the
     * resolver class name which is under the special namespace.
     *
     * @param string $marketplace_component
     * @return string
     */
    public static function resolve_resolver_class_name(string $marketplace_component): string {
        if (!self::is_valid_marketplace_component($marketplace_component)) {
            throw new coding_exception("Invalid marketplace type '{$marketplace_component}'");
        }

        $class_name = "{$marketplace_component}\\learning_object\\resolver";
        $parent_class = resolver::class;

        if (!is_subclass_of($class_name, $parent_class)) {
            throw new coding_exception("The class '{$class_name}' is not a child of '{$parent_class}'");
        }

        return $class_name;
    }

    /**
     * Checks if the $component name is a valid content marketplace.
     *
     * @param string $component
     * @return bool
     */
    public static function is_valid_marketplace_component(string $component): bool {
        [$plugin_type, $plugin_name] = core_component::normalize_component($component);

        if ('contentmarketplace' !== $plugin_type) {
            return false;
        }

        $plugins = core_component::get_subplugins('totara_contentmarketplace');
        $sub_plugins = $plugins['contentmarketplace'];
        $sub_plugins = array_flip($sub_plugins);

        return isset($sub_plugins[$plugin_name]);
    }

    /**
     * Get the list of content marketplace plugins and their respective resolver classes.
     *
     * @return resolver[]|string[] Resolver class names
     */
    public static function get_marketplace_plugin_resolvers(): array {
        return core_component::get_namespace_classes('learning_object', resolver::class);
    }

}