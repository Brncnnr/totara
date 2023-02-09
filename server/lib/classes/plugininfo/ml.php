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
 * @author Vernon Denny <vernon.denny@totaralearning.com>
 * @package core
 */
namespace core\plugininfo;

use moodle_url, part_of_admin_tree, admin_settingpage;

/**
 * Plugin info for machine learning.
 */
class ml extends base {
    /**
     * Initialise the display name.
     * @return void
     */
    public function init_display_name(): void {
        if (static::class === ml::class) {
            $this->displayname = get_string('ml', 'ml');
        } else {
            parent::init_display_name();
        }
    }

    /**
     * Finds all enabled plugins, the result may include missing plugins.
     * @return array|null of enabled plugins $pluginname=>$pluginname, null means unknown
     */
    public static function get_enabled_plugins() {
        global $CFG;

        if (!isset($CFG->machine_learning) || empty($CFG->machine_learning)) {
            return [];
        }

        $enabled = explode(',', $CFG->machine_learning);
        $plugins = \core_component::get_plugin_list('ml');
        $rtn = [];

        foreach ($enabled as $name) {
            $name = trim($name);
            if (empty($name)) {
                continue;
            }

            if (!isset($plugins[$name])) {
                debugging("Cannot find plugin '{$name}' of type 'ml'");
                continue;
            }

            $rtn[$name] = $name;
        }

        return $rtn;
    }

    /**
     * @return string|null
     */
    final public function get_settings_section_name(): ?string {
        if (static::class === ml::class) {
            return 'ml';
        }

        return "ml_setting_{$this->name}";
    }

    /**
     * @param part_of_admin_tree $admin_root
     * @param string $parent_node_name
     * @param bool $hassiteconfig
     *
     * @return void
     */
    public function load_settings(part_of_admin_tree $admin_root, $parent_node_name, $hassiteconfig): void {
        global $CFG, $USER, $DB, $OUTPUT, $PAGE;
        $ADMIN = $admin_root;

        // Global variables and $ADMIN are used in settings.php for callback setting.

        if (!$this->is_installed_and_upgraded()) {
            return;
        }

        if (!$hassiteconfig) {
            return;
        }

        if (!file_exists($this->full_path('settings.php'))) {
            return;
        }

        $fullpath = $this->full_path('settings.php');

        $section = $this->get_settings_section_name();
        $settings = new admin_settingpage($section, $this->displayname, 'moodle/site:config', !$this->is_enabled());

        // This may also set $settings to null.
        include($fullpath);

        if ($settings) {
            $ADMIN->add($parent_node_name, $settings);
        }
    }

    /**
     * @return bool
     */
    public function is_uninstall_allowed(): bool {
        return false;
    }

    /**
     * Return URL used for management of plugins of this type.
     * @return moodle_url
     */
    public static function get_manage_url() {
        return new moodle_url('/admin/machine_learning.php');
    }

    /**
     * If true, this ML plugin can be toggled on/off on the settings page
     *
     * @return bool
     */
    public function can_toggle(): bool {
        return true;
    }
}
