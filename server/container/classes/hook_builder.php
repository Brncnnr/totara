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

use core_container\hook\base_redirect;

/**
 * This is just a helper to provide a list of hooks. It is only here to make developer life easier.
 */
final class hook_builder {
    /**
     * hook_builder constructor.
     */
    private function __construct() {
    }

    /**
     * Returning a collection of class names that are a child of the redirect hook {@see base_redirect}.
     * This function only scans for the plugin systems.
     *
     * @return string[]
     */
    public static function get_redirect_hooks_from_plugins(): array {
        $hooks = [];
        $plugintypes = \core_component::get_plugin_types();

        // Scan the whole systems to get any single hook that is a child of the container base_redirect hook.
        // Then start building the watchers map.
        foreach ($plugintypes as $plugintype => $plugintypelocation) {
            $plugins = \core_component::get_plugin_list($plugintype);
            foreach ($plugins as $plugin => $pluginlocation) {
                $component = "{$plugintype}_{$plugin}";
                $hookclasses = \core_component::get_namespace_classes('hook', base_redirect::class, $component, true);

                if (!empty($hookclasses)) {
                    $hooks = array_merge($hooks, $hookclasses);
                }
            }
        }

        return $hooks;
    }

    /**
     * Returning a collection of class names that are a child of redirect hook {@see base_redirect}
     * This function scans the core sub-systems.
     *
     * @return string[]
     */
    public static function get_redirect_hooks_from_core_subsystems(): array {
        $hooks = [];
        $subsystems = \core_component::get_core_subsystems();

        foreach ($subsystems as $subsystem => $location) {
            $namespace = "core_{$subsystem}";
            $hookclasses = \core_component::get_namespace_classes('hook', base_redirect::class, $namespace, true);

            if (!empty($hookclasses)) {
                $hooks = array_merge($hooks, $hookclasses);
            }
        }

        return $hooks;
    }
}