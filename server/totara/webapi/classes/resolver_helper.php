<?php
/*
 *  This file is part of Totara TXP
 *
 *  Copyright (C) 2022 onwards Totara Learning Solutions LTD
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  @author Simon Coggins <simon.coggins@totaralearning.com>
 *
 */

namespace totara_webapi;

use core\webapi\execution_context;
use core\webapi\mutation_resolver;
use core\webapi\query_resolver;
use core_component;
use GraphQL\Type\Definition\ResolveInfo;

class resolver_helper {

    private static $split_type_name_cache = [];

    /**
     * @param ResolveInfo $info
     * @param execution_context $ec
     * @return array|mutation_resolver|query_resolver|string|null
     * @throws \coding_exception
     */
    public static function get_resolver_classname_and_component(ResolveInfo $info, execution_context $ec): array {
        // phpcs:disable Totara.NamingConventions.ValidVariableName.LowerCaseUnderscores

        if ($info->parentType->name === 'Query' or $info->parentType->name === 'Mutation') {
            $otype = ($info->parentType->name === 'Query') ? 'query' : 'mutation';

            [$component, $name] = self::split_type_name($info->fieldName);
            if (empty($component)) {
                throw new \coding_exception('GraphQL ' . $otype . ' name is invalid', $info->fieldName);
            }
            /** @var query_resolver|mutation_resolver $classname */
            $classname = "{$component}\\webapi\\resolver\\{$otype}\\{$name}";
            if (!class_exists($classname)) {
                throw new \coding_exception('GraphQL ' . $otype . ' resolver class is missing', $info->fieldName);
            }

            return [$classname, $component];
        }

        // Regular data type.
        $parts = explode('_', $info->parentType->name);
        if (!self::is_introspection_type($info->parentType->name) && count($parts) > 1) {
            self::check_for_deprecation_messages($ec, $info);
            [$component, $name] = self::split_type_name($info->parentType->name);
            if (empty($name)) {
                throw new \coding_exception('Type resolvers must be named as component_name, e.g. totara_job_job');
            }
            return ["{$component}\\webapi\\resolver\\type\\{$name}", $component];
        }

        return [null, null];
        // phpcs:enable
    }

    /**
     * Split type name, i.e. totara_competency_my_query_name into component (totara_competency) and the rest (my_query_name)
     *
     * @param string $name
     * @return array
     */
    public static function split_type_name(string $name) {
        if (isset(self::$split_type_name_cache[$name])) {
            return self::$split_type_name_cache[$name];
        }

        if (strpos($name, 'core_') === 0) {
            self::$split_type_name_cache[$name] = ['core', substr($name, 5)];
            return self::$split_type_name_cache[$name];
        }

        // Build flat list out of all plugins and subplugins
        $components = [];
        $types = core_component::get_plugin_types();
        foreach ($types as $type => $typedir) {
            $plugins = core_component::get_plugin_list($type);
            foreach ($plugins as $plugin => $plugindir) {
                $plugin_component = $type.'_'.$plugin;
                $components[$plugin_component] = $plugin_component;
                $subplugins = core_component::get_subplugins($plugin_component);
                foreach ($subplugins ?? [] as $prefix => $subplugin_names) {
                    foreach ($subplugin_names as $subplugin) {
                        $subplugin_component = $prefix . '_' . $subplugin;
                        $components[$subplugin_component] = $subplugin_component;
                    }
                }
            }
        }

        // Now try to find component name by reducing the name one by one and checking existence in component list
        $parts = explode('_', $name);
        while (count($parts) > 0) {
            array_pop($parts);
            $component_search_name = implode('_', $parts);
            if (isset($components[$component_search_name])) {
                self::$split_type_name_cache[$name] = [
                    $component_search_name,
                    substr($name, strlen($component_search_name) + 1)
                ];
                return self::$split_type_name_cache[$name];
            }
        }

        self::$split_type_name_cache[$name] = [null, null];
        return [null, null];
    }

    /**
     * Check if the type name is one used in introspections, which usually starts with two underscores
     * @param string $name
     * @return bool
     */
    public static function is_introspection_type(string $name): bool {
        return strpos($name, '__') === 0;
    }

    /**
     * Check for deprecation messages and if found add them to the execution context
     *
     * @param execution_context $execution_context
     * @param ResolveInfo $info
     */
    public static function check_for_deprecation_messages(
        execution_context $execution_context,
        ResolveInfo $info
    ): void {
        // phpcs:disable Totara.NamingConventions.ValidVariableName.LowerCaseUnderscores

        if ($info->schema) {
            $type = $info->schema->getType($info->parentType->name);
            if ($type) {
                $field = $type->getField($info->fieldName);
                if (!empty($field->deprecationReason)) {
                    $execution_context->add_deprecation_warning(
                        $info->parentType->name,
                        $info->fieldName,
                        $field->deprecationReason
                    );
                }
            }
        }
    }
}