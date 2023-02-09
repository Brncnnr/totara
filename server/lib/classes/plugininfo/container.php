<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
namespace core\plugininfo;

use core_container\setting\setting;

class container extends base {
    /**
     * @return void
     */
    public function init_display_name() {
        if (static::class === container::class) {
            // For this specific container plugin, use this one.
            $this->displayname = get_string('container', 'core_container');
        } else {
            parent::init_display_name();
        }
    }

    /**
     * @return bool
     */
    public function is_uninstall_allowed() {
        return false;
    }

    /**
     * @param \part_of_admin_tree $admin_root
     * @param string $parent_node_name
     * @param bool $has_site_config
     */
    public function load_settings(\part_of_admin_tree $admin_root, $parent_node_name, $has_site_config) {
        // The reason why we are including these globals and declaring $ADMIN as because the legacy code from
        // {plugin}/settings.php that we are about to include is assuming these dark magics are available for them
        // by default.
        global $CFG, $USER, $DB, $OUTPUT, $PAGE;
        $ADMIN = $admin_root;

        if (!$this->is_installed_and_upgraded()) {
            return;
        }

        if ($has_site_config) {
            // Include the settings.php for container - only if the user has site config capability.
            $full_path = $this->full_path('settings.php');

            if (file_exists($full_path)) {
                // Including the setting page of contianer.
                $section = $this->get_settings_section_name();
                $settings = new \admin_settingpage($section, $this->displayname, 'moodle/site:config', !$this->is_enabled());

                include($full_path);

                if ($settings) {
                    $ADMIN->add($parent_node_name, $settings);
                }
            }
        }

        // Provide the ability to add the container's settings.
        // Note that this is by-passing the site config or moodle config capability. Because the capability check
        // should be done as a part of the children implementation that injecting the setting.
        // Load all the setting from the component that integrate with the containers.
        $classes = \core_component::get_namespace_classes("{$this->component}\\setting", setting::class);

        foreach ($classes as $plugin_setting_class) {
            /** @var setting $plugin_setting */
            $plugin_setting = new $plugin_setting_class();
            $plugin_setting->init($ADMIN);
        }
    }
}