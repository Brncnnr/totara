<?php
/**
 * This file is part of Totara Core
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
 * @package container_course
 */
namespace container_course\hook;

use container_course\course;
use totara_core\hook\base;
use totara_core\identifier\component_area;

/**
 * A hook to allow any plugins to remove any module(s) from the modules list.
 */
class remove_module_hook extends base {
    /**
     * A map of module names, which the module name is the key,
     * and the value is the i18n name of itself.
     *
     * Normally is taken from the result of {@see course::get_module_types_supported()}
     *
     * @var array
     */
    private $module_names;

    /**
     * @var component_area|null
     */
    private $component_area;

    /**
     * remove_module_hook constructor.
     * @param array $module_names
     */
    public function __construct(array $module_names) {
        $this->module_names = $module_names;
        $this->component_area = null;
    }

    /**
     * @return component_area|null
     */
    public function get_component_area(): ?component_area {
        return $this->component_area;
    }

    /**
     * @param component_area $component_area
     */
    public function set_component_area(component_area $component_area): void {
        $this->component_area = $component_area;
    }

    /**
     * @param string $module_name
     * @return bool
     */
    public function has_module(string $module_name): bool {
        return isset($this->module_names[$module_name]);
    }

    /**
     * @param string $module_name
     * @return void
     */
    public function remove_module(string $module_name): void {
        if (!$this->has_module($module_name)) {
            debugging(
                "The  module '{$module_name}' does not exist in the list",
                DEBUG_DEVELOPER
            );

            return;
        }

        // Remove the item from the list.
        unset($this->module_names[$module_name]);
    }

    /**
     * Returns the map.
     * @return array
     */
    public function get_modules(): array {
        return $this->module_names;
    }
}