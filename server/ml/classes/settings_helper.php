<?php
/**
 * This file is part of Totara Core
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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package core_ml
 */

namespace core_ml;

use core_plugin_manager;
use totara_core\advanced_feature;

class settings_helper {
    /**
     * Called when the ml_recommender advanced feature is toggled.
     * Will disable/enable the attached plugin
     *
     */
    public static function recommender_advanced_features_callback() {
        if (advanced_feature::is_disabled('ml_recommender')) {
            // We need to disable the recommender plugin
            self::disable_ml_plugin('recommender');
        } else {
            self::enable_ml_plugin('recommender');
        }
    }

    /**
     * Enable the specific machine learning plugin
     *
     * @param string $plugin
     */
    public static function enable_ml_plugin(string $plugin): void {
        global $CFG;
        $current_plugins = self::get_ml_plugins();
        if (!in_array($plugin, $current_plugins)) {
            $current_plugins[] = $plugin;

            $old_value = $CFG->machine_learning ?? '';
            $CFG->machine_learning = implode(", ", $current_plugins);

            set_config('machine_learning', $CFG->machine_learning);
            add_to_config_log('machine_learning', $old_value, $CFG->machine_learning, null);

            core_plugin_manager::reset_caches();
        }
    }

    /**
     * Disable the specific machine learning plugin
     *
     * @param string $plugin
     */
    public static function disable_ml_plugin(string $plugin): void {
        global $CFG;
        $current_plugins = self::get_ml_plugins();
        if (in_array($plugin, $current_plugins)) {
            $current_plugins = array_filter(
                $current_plugins,
                function($current_plugin) use ($plugin): bool {
                    return $current_plugin != $plugin;
                }
            );

            $old_value = $CFG->machine_learning ?? '';
            $CFG->machine_learning = $current_plugins ? implode(", ", $current_plugins) : '';

            set_config('machine_learning', $CFG->machine_learning);
            add_to_config_log('machine_learning', $old_value, $CFG->machine_learning, null);

            core_plugin_manager::reset_caches();
        }
    }

    /**
     * Grab the current list of machine learning plugins
     *
     * @return array
     */
    private static function get_ml_plugins(): array {
        global $CFG;
        $current_plugins = [];

        if (isset($CFG->machine_learning) && !empty($CFG->machine_learning)) {
            $old_value = $CFG->machine_learning;
            $current_plugins = explode(",", $old_value);
            $current_plugins = array_map('trim', $current_plugins);

            // Removing all the empty string within the array.
            $current_plugins = array_filter(
                $current_plugins,
                function($current_plugin): bool {
                    return !empty($current_plugin);
                }
            );
        }

        return $current_plugins;
    }
}