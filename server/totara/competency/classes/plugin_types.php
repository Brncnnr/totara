<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency;

/**
 * Class for competency plugin management
 */
class plugin_types {

    private const DISABLE_CONFIG_POSTFIX = '_types_disabled';

    /**
     * Return information on all installed plugins of this type
     * The plugin names are used as type key
     *
     * @param  string $plugin_type Plugin type (e.g. pathway)
     * @param  string $config_scope Config scope (e.g. totara_competency)
     * @return array containing objects with type, display name, version and enabled
     *         attributes for all installed plugins of this type
     */
    public static function get_installed_plugins(string $plugin_type, string $config_scope): array {
        $plugin_infos = \core_plugin_manager::instance()->get_plugins_of_type($plugin_type);
        $disabled_plugins = static::get_disabled_plugins($plugin_type, $config_scope);

        $plugins = [];

        foreach ($plugin_infos as $plugin => $info) {
            $plugins[$plugin] = (object)[
                'type' => $plugin,
                'title' => $info->displayname,
                'version' => $info->versiondb,
                'enabled' => !in_array($plugin, $disabled_plugins),
            ];
        }

        return $plugins;
    }

    /**
     * Get list of enabled plugins of this type
     *
     * @param  string $plugin_type Plugin type (e.g. pathway)
     * @param  string $config_scope Config scope (e.g. totara_competency)
     * @return string[] List of plugin names that are currently enabled
     */
    public static function get_enabled_plugins(string $plugin_type, string $config_scope): array {
        $all_types = self::get_installed_plugins($plugin_type, $config_scope);
        $enabled_types = array_filter($all_types, function ($plugin) {
            return $plugin->enabled;
        });

        return array_keys($enabled_types);
    }

    /**
     * Get list of disabled plugins of this type
     *
     * @param  string $plugin_type Plugin type (e.g. pathway)
     * @param  string $config_scope Config scope (e.g. totara_competency)
     * @return string[] List of plugin names that are currently disabled
     */
    public static function get_disabled_plugins(string $plugin_type, string $config_scope): array {
        $disable_setting = $plugin_type . self::DISABLE_CONFIG_POSTFIX;
        $disabled_plugins = get_config($config_scope, $disable_setting);
        return !$disabled_plugins ? [] : explode(',', $disabled_plugins);
    }

    /**
     * Enable a specific plugin
     *
     * @param  string $plugin Plugin to enable
     * @param  string $plugin_type Plugin type (e.g. pathway)
     * @param  string $config_scope Config scope (e.g. totara_competency)
     * @return array Resulting array of enabled plugins
     */
    public static function enable_plugin(string $plugin, string $plugin_type, string $config_scope): array {
        $disabled_plugins = static::get_disabled_plugins($plugin_type, $config_scope);

        if (in_array($plugin, $disabled_plugins)) {
            $disable_setting = $plugin_type . self::DISABLE_CONFIG_POSTFIX;
            $disabled_plugins = array_diff($disabled_plugins, [$plugin]);
            set_config($disable_setting, implode(',', $disabled_plugins), $config_scope);
            \core_plugin_manager::reset_caches();
        }

        return static::get_enabled_plugins($plugin_type, $config_scope);
    }

    /**
     * Disable a specific plugin
     *
     * @param string $plugin Type to disable
     * @param  string $plugin_type Plugin type (e.g. pathway)
     * @param  string $config_scope Config scope (e.g. totara_competency)
     * @return array Resulting array of enabled plugins
     */
    public static function disable_plugin(string $plugin, string $plugin_type, string $config_scope): array {
        if (static::is_plugin_enabled($plugin, $plugin_type, $config_scope)) {
            $disable_setting = $plugin_type . self::DISABLE_CONFIG_POSTFIX;
            $disabled_plugins = static::get_disabled_plugins($plugin_type, $config_scope);
            $disabled_plugins[] = $plugin;

            set_config($disable_setting, implode(',', $disabled_plugins), $config_scope);
            \core_plugin_manager::reset_caches();
        }

        return static::get_enabled_plugins($plugin_type, $config_scope);
    }

    /**
     * @param string $plugin
     * @param string $plugin_type
     * @param string $config_scope
     * @return bool
     */
    public static function is_plugin_enabled(string $plugin, string $plugin_type, string $config_scope): bool {
        $enabled_plugins = self::get_enabled_plugins($plugin_type, $config_scope);
        return in_array($plugin, $enabled_plugins);
    }

    /**
     * @param string $plugin
     * @param string $plugin_type
     * @param string $config_scope
     * @return bool
     */
    public static function is_plugin_disabled(string $plugin, string $plugin_type, string $config_scope): bool {
        $disable_setting = $plugin_type . self::DISABLE_CONFIG_POSTFIX;
        $disabled_plugins = explode(',', get_config($config_scope, $disable_setting));

        return in_array($plugin, $disabled_plugins);
    }
}
