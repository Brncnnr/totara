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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\plugininfo;

use admin_category;
use admin_externalpage;
use admin_root;
use admin_settingpage;
use core\plugininfo\base;
use core_plugin_manager;
use lang_string;
use mod_approval\entity\form\form;
use moodle_url;
use part_of_admin_tree;
use totara_core\advanced_feature;

defined('MOODLE_INTERNAL') || die();

/**
 * approvalform sub-plugin.
 * @property-read string $component the component name, type_name
 */
class approvalform extends base {
    /**
     * Finds all enabled plugins.
     *
     * @return array of enabled plugins [$pluginname1 => $pluginname1, $pluginname2 => $pluginname2]
     */
    public static function get_enabled_plugins() {
        global $CFG;
        if (empty($CFG->approvalform_plugins)) {
            return array();
        }
        $enabled = explode(',', $CFG->approvalform_plugins);
        // filter out disabled plugins
        $plugins = array_intersect(self::get_all_plugins(), $enabled);
        return array_combine($plugins, $plugins);
    }

    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
    public function is_uninstall_allowed() {
        // NOTE: direct uninstallation of a sub plugin is not allowed
        // however, uninstalling mod_approval results in the uninstallation of all sub plugins
        $has_form = form::repository()->where('plugin_name', '=', $this->name)->count();
        if ($has_form > 0) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @inheritDoc
     */
    public function get_settings_section_name() {
        return $this->component;
    }

    /**
     * Initialises plugin settings for approvalform plugins.
     *
     * @param admin_root $admin_root
     */
    public static function init_plugin_settings(admin_root $admin_root): void {
        // Check on/off switch.
        if (advanced_feature::is_disabled('approval_workflows')) {
            return;
        }

        $admin_root->add('modules', new admin_category('approvalformsfolder', new lang_string('plugintype_approvalform', 'mod_approval')));

        $form_plugin_page = new admin_externalpage(
            'approvalformplugins',
            get_string('approvalform_plugins_list', 'mod_approval'),
            new moodle_url('/mod/approval/form/manage_plugins.php'),
            'moodle/site:config',
            false
        );
        $admin_root->add('approvalformsfolder', $form_plugin_page);

        foreach (core_plugin_manager::instance()->get_plugins_of_type('approvalform') as $plugin) {
            /** @var approvalform $plugin */
            $plugin->load_settings($admin_root, 'approvalformsfolder', true);
        }
    }

    /**
     * @inheritDoc
     *
     * @param part_of_admin_tree $adminroot
     * @param string $parentnodename
     * @param boolean $hassiteconfig
     * @codeCoverageIgnore
     */
    public function load_settings(part_of_admin_tree $adminroot, $parentnodename, $hassiteconfig) {
        if (!$this->is_installed_and_upgraded()) {
            return;
        }

        // Check on/off switch.
        if (advanced_feature::is_disabled('approval_workflows')) {
            return;
        }

        $fullpath = $this->full_path('settings.php');
        if (!$hassiteconfig || empty($fullpath) || !file_exists($fullpath)) {
            return;
        }

        $section = $this->get_settings_section_name();
        require_once($fullpath);
        $function_name = $this->component . '_load_settings';

        if (function_exists($function_name)) {
            /** @var \admin_root $adminroot */
            $fulltree = $adminroot->fulltree;
            $hidden = false;
            $node = $function_name($section, $this->displayname, $fulltree, $hidden);
            if ($node) {
                $adminroot->add($parentnodename, $node);
            }
        }
    }

    /**
     * Enable a plugin.
     *
     * @param string $plugin
     * @param bool $skip_installed_check
     * @return boolean
     */
    public static function enable_plugin(string $plugin, bool $skip_installed_check = false): bool {
        return self::toggle_plugin($plugin, true, $skip_installed_check);
    }

    /**
     * Disable a plugin.
     *
     * @param string $plugin
     * @return boolean
     */
    public static function disable_plugin(string $plugin): bool {
        return self::toggle_plugin($plugin, false);
    }

    /**
     * @return string[]
     */
    private static function get_all_plugins(): array {
        return array_keys(core_plugin_manager::instance()->get_installed_plugins('approvalform'));
    }

    /**
     * Toggle whether plugin is enabled or disabled.
     *
     * @param string $plugin
     * @param boolean $enable
     * @param bool $skip_installed_check
     * @return boolean
     */
    private static function toggle_plugin(string $plugin, bool $enable, bool $skip_installed_check = false): bool {
        global $CFG;
        // During install or upgrade, the plugin will not be in the list of installed plugins.
        if (!in_array($plugin, self::get_all_plugins()) && $enable && !$skip_installed_check) {
            throw new \moodle_exception("Tried to enable unknown approvalform plugin: {$plugin}");
        }
        $enabled = array_keys(self::get_enabled_plugins());
        if ($enable) {
            if (in_array($plugin, $enabled)) {
                return false;
            }
            $enabled[] = $plugin;
        } else {
            if (!in_array($plugin, $enabled)) {
                return false;
            }
            $enabled = array_diff($enabled, [$plugin]);
        }
        $enabled = array_unique($enabled);
        $oldvalue = $CFG->approvalform_plugins ?? '';
        $newvalue = implode(',', $enabled);
        if ($oldvalue == $newvalue) {
            return false;
        }
        set_config('approvalform_plugins', $newvalue);
        add_to_config_log('approvalform_plugins', $oldvalue, $newvalue, null);
        core_plugin_manager::reset_caches();
        return true;
    }

    /**
     * Use a plugin name to load an approvalform plugininfo instance.
     *
     * @param string $plugin_name
     * @return null|base|approvalform
     */
    public static function from_plugin_name(string $plugin_name): ?base {
        if (substr($plugin_name, 0, 13) != 'approvalform_') {
            $plugin_name = 'approvalform_' . $plugin_name;
        }
        return core_plugin_manager::instance()->get_plugin_info($plugin_name);
    }
}
