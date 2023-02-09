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
use core_container\hook\module_supported_in_container;

/**
 * A mock watcher class that is used for testing the ability to remove/add module
 * from the list of available modules within the container.
 */
final class container_remove_module_watcher {
    /**
     * @var string[]
     */
    private static $modules_to_be_removed;

    /**
     * Set the modules to be removed, where $modules is an array of module name that stored
     * within table "ttr_modules".
     *
     * @param string[] $modules
     * @return void
     */
    public static function set_modules_to_be_removed(array $modules): void {
        if (!isset(self::$modules_to_be_removed)) {
            self::$modules_to_be_removed = [];
        }

        self::$modules_to_be_removed = $modules;
    }

    /**
     * @return void
     */
    public static function reset(): void {
        self::$modules_to_be_removed = [];
    }

    /**
     * @param module_supported_in_container $hook
     * @return void
     */
    public static function remove_module(module_supported_in_container $hook): void {
        global $CFG;

        if (!defined('PHPUNIT_TEST') || !PHPUNIT_TEST) {
            throw new coding_exception("Cannot run the code outside of phpunit environment");
        } else if (!isset(self::$modules_to_be_removed)) {
            return;
        }

        require_once("{$CFG->dirroot}/container/tests/fixtures/core_container_mock_container.php");
        $container_type = $hook->get_containertype();
        if (core_container_mock_container::class === $container_type) {
            foreach (self::$modules_to_be_removed as $module_name) {
                $hook->remove_mod($module_name);
            }
        }
    }
}