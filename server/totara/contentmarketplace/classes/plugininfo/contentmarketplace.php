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
 * @author Sergey Vidusov <sergey.vidusov@androgogic.com>
 * @package totara_contentmarketplace
 */
namespace totara_contentmarketplace\plugininfo;

use admin_settingpage;
use core\plugininfo\base;
use core_plugin_manager;
use part_of_admin_tree;
use totara_contentmarketplace\entity\course_module_source;
use totara_contentmarketplace\local\contentmarketplace\collection;
use totara_contentmarketplace\local\contentmarketplace\contentmarketplace as local_instance;
use totara_contentmarketplace\local\contentmarketplace\search;

class contentmarketplace extends base {

    /**
     * @return array
     */
    public static function get_enabled_plugins() {
        global $DB;
        $sql = "SELECT plugin
            FROM {config_plugins}
            WHERE " . $DB->sql_like('plugin', ':pluginname') . "
                AND name = 'enabled'
                AND value = '1'";
        $records = $DB->get_records_sql($sql, ['pluginname' => 'contentmarketplace_%']);
        if (!$records) {
            return [];
        }

        $enabled = [];
        foreach ($records as $record) {
            $name = str_replace('contentmarketplace_', '', $record->plugin);
            $enabled[$name] = $name;
        }
        return $enabled;
    }

    /**
     * @return bool
     */
    public function is_uninstall_allowed() {
        if ($this->is_standard()) {
            return false;
        }
        return true;
    }

    /**
     * @return local_instance
     */
    public function contentmarketplace() {
        $classname = "\\{$this->component}\\contentmarketplace";
        return new $classname();
    }

    /**
     * @return search
     */
    public function search() {
        $classname = "\\{$this->component}\\search";
        return new $classname();
    }

    /**
     * @return collection
     */
    public function collection() {
        $classname = "\\{$this->component}\\collection";
        return new $classname();
    }

    /**
     * @param string $name
     * @param bool   $required If set to true (default) and the plugin doesn't exist a coding_exception is thrown.
     * @return contentmarketplace|null
     */
    public static function plugin($name, $required = true) {
        $component = $name;
        if (strpos($name, 'contentmarketplace_', 0) === false) {
            $component = 'contentmarketplace_' . $component;
        }

        $plugin = core_plugin_manager::instance()->get_plugin_info($component);
        if ($plugin === null) {
            if ($required) {
                throw new \coding_exception("Unknown content marketplace plugin requested: '{$name}'");
            }
            return null;
        }
        if (!$plugin instanceof contentmarketplace) {
            throw new \coding_exception("Content marketplace plugin '{$name}' is not of the correct type.");
        }
        return $plugin;
    }

    /**
     * @return void
     */
    public function enable() {
        global $USER;
        $userid = (isloggedin()) ? $USER->id : -1;
        set_config('enabled_by', $userid, $this->component);
        set_config('enabled_on', time(), $this->component);
        $this->set_enabled(1);
    }

    /**
     * @return void
     */
    public function disable() {
        set_config('enabled_by', '', $this->component);
        set_config('enabled_on', '', $this->component);
        $this->set_enabled(0);
    }

    /**
     * @param bool|int $value
     * @return void
     */
    protected function set_enabled($value) {
        set_config('enabled', $value, $this->component);
        \core_plugin_manager::reset_caches();
    }

    /**
     * @return bool
     */
    public function has_never_been_enabled() {
        return get_config($this->component, 'enabled') === false;
    }

    /**
     * @return string|null
     */
    public function get_settings_section_name(): ?string {
        return "content_marketplace_setting_{$this->name}";
    }

    /**
     * @param part_of_admin_tree $adminroot
     * @param string             $parentnodename
     * @param bool               $hassiteconfig
     */
    public function load_settings(part_of_admin_tree $adminroot, $parentnodename, $hassiteconfig) {
        // The reason why we are including these globals and declaring $ADMIN as because the legacy code from
        // {plugin}/settings.php that we are about to include is assuming these dark magics are available for them
        // by default.
        global $CFG, $USER, $DB, $PAGE, $OUTPUT;
        $ADMIN = $adminroot;

        if (!$this->is_installed_and_upgraded()) {
            return;
        }

        if (!$hassiteconfig) {
            return;
        }

        $full_path = $this->full_path('settings.php');

        if (file_exists($full_path)) {
            $display_name = $this->displayname;
            $component = $this->component;
            $string_manager = get_string_manager();

            if ($string_manager->string_exists('settings_title', $component)) {
                // Look up to the settings title so that we can set it as the title of the page.
                $display_name = $string_manager->get_string('settings_title', $component);
            }

            $settings_page = new admin_settingpage(
                $this->get_settings_section_name(),
                $display_name,
                ['moodle/site:config', 'totara/contentmarketplace:config'],
                !$this->is_enabled(),
            );

            include($full_path);
            $adminroot->add($parentnodename, $settings_page);
        }
    }

    /**
     * @inheritDoc
     */
    public function get_usage_for_registration_data() {
        $marketplace = $this->contentmarketplace();

        $data = [];

        $data["{$marketplace->name}enabled"] = (int) $this->is_enabled();

        $unique_course_count = course_module_source::repository()
            ->select_raw('DISTINCT course_modules.course')
            ->join('course_modules', 'cm_id', 'id')
            ->where('marketplace_component', $marketplace->get_plugin_name())
            ->group_by('course_modules.course')
            ->count();

        $data["num{$marketplace->name}courses"] = $unique_course_count;

        return $data;
    }

}
