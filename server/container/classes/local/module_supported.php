<?php
/**
 * This file is part of Totara Learn
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
namespace core_container\local;

use core_container\hook\module_supported_in_container;

/**
 * A singleton to get the module supported within a container.
 */
final class module_supported {
    /**
     * @var module_supported
     */
    private static $instance;

    /**
     * A hash map of module's name and the availability of its in system.
     * @var array
     */
    private $modules;

    /**
     * An array of mod names that are being supported within container
     * @var array
     */
    private $modsincontainer;

    /**
     * @return module_supported
     */
    public static function instance(): module_supported {
        if (null == static::$instance) {
            static::$instance = new static();

            static::$instance->modules = [];
            static::$instance->modsincontainer = [];
        }

        return static::$instance;
    }

    /**
     * Preload the modules from table {modules} and setup its name with both plural and non-plural.
     * @return void
     */
    private function setup_modules(): void {
        global $DB, $CFG;

        if (empty($this->modules)) {
            $this->modules = [];
            $allmods = $DB->get_records('modules', null, '', 'id, name, visible');

            foreach ($allmods as $mod) {
                $file = "{$CFG->dirroot}/mod/{$mod->name}/lib.php";
                if (file_exists($file)) {
                    $this->modules[$mod->name] = $mod->visible;
                }
            }
        }
    }

    /**
     * Execute the hooks to load all the posible modules supported by the workspace.
     *
     * @param string $container_type
     * @return void
     */
    private function load_modules_for_container(string $container_type): void {
        $this->setup_modules();

        if (array_key_exists($container_type, $this->modsincontainer)) {
            return;
        }

        $module_names = array_keys($this->modules);

        $hook = new module_supported_in_container($container_type, $module_names);
        $hook->execute();

        $supported_modules = $hook->get_mods();
        $modules_in_container = [];

        foreach ($supported_modules as $module_name) {
            if (!array_key_exists($module_name, $this->modules)) {
                debugging(
                    "The module '{$module_name}' does not appear in the list of supported modules",
                    DEBUG_DEVELOPER
                );

                continue;
            }

            $modules_in_container[$module_name] = $this->modules[$module_name];
        }

        $this->modsincontainer[$container_type] = $modules_in_container;
    }

    /**
     * Returning a hashmap of module name, given the key as the actual plugin name.
     * Note that this function will only return the list of available module - not all the available module
     * in the system. Unless $include_all is specified to TRUE, then the return list will include the
     * invisible items in the list.
     *
     * @param string    $containertype
     * @param bool      $plural
     * @param bool      $include_disabled
     *
     * @return array
     */
    public function get_for_container(string $containertype, bool $plural = false,
                                      bool $include_disabled = false): array {
        $this->load_modules_for_container($containertype);
        $supported_modules = $this->modsincontainer[$containertype];

        $identifier = "modulename";
        if ($plural) {
            $identifier = "modulenameplural";
        }

        $modules = [];
        foreach ($supported_modules as $module_name => $visible) {
            if (!$visible && !$include_disabled) {
                // Skip this module.
                continue;
            }

            $modules[$module_name] = get_string($identifier, "mod_{$module_name}");
        }

        \core_collator::asort($modules);
        return $modules;
    }

    /**
     * @return void
     */
    public static function purge_caches(): void {
        if (!isset(self::$instance)) {
            return;
        }

        self::$instance->modsincontainer = [];
        self::$instance->modules = [];
    }
}