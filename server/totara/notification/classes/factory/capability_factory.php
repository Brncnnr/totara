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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_notification
 */
namespace totara_notification\factory;

use cache;
use cache_loader;
use coding_exception;
use core_component;

/**
 * A factory class that fetches all the capabilities related to managing and auditing notifications
 * within the system. These capabilities are provided by the db file which is located in
 * "{plugin_path}/db/notification_access.php"
 */
class capability_factory {
    /**
     * The cache's key that store all the capabilities.
     * @var string
     */
    public const MAP_KEY = 'capabilities';

    /**
     * capability_factory constructor.
     */
    private function __construct() {
        // Preventing this class from construction
    }

    /**
     * @return cache_loader
     */
    public static function get_cache_loader(): cache_loader {
        return cache::make('totara_notification', 'access');
    }

    /**
     * @return void
     */
    public static function load_map(): void {
        global $CFG;

        $cache = static::get_cache_loader();
        $map = $cache->get(static::MAP_KEY);

        if (!is_array($map)) {
            $map = [];

            $plugin_types = core_component::get_plugin_types();
            $plugin_types = array_keys($plugin_types);

            $all_accesses_files = [];

            // Adding core access file.
            if (file_exists("{$CFG->dirroot}/lib/db/notification_access.php")) {
                $all_accesses_files[] = "{$CFG->dirroot}/lib/db/notification_access.php";
            }

            // Adding the rest of system plugin type.
            foreach ($plugin_types as $plugin_type) {
                $locations = core_component::get_plugin_list_with_file($plugin_type, 'db/notification_access.php');
                if (!empty($locations)) {
                    $all_accesses_files = array_merge($all_accesses_files, $locations);
                }
            }

            foreach ($all_accesses_files as $access_file) {
                // We gonna have to use require for these kinds of metadata files, as we gonna have
                // to allow refresh cache and reload the file (main example is probably PHPUNIT environment).
                // The downside of this is that the located file might declare unexpected constant and that will
                // crash the notification system.
                $accesses = [];
                require($access_file);

                if (empty($accesses)) {
                    continue;
                }

                foreach ($accesses as $access) {
                    if (!array_key_exists('capability', $access) || !array_key_exists('context_levels', $access)) {
                        throw new coding_exception("Invalid access data schema included");
                    }

                    $context_levels = $access['context_levels'];
                    foreach ($context_levels as $context_level) {
                        if (!isset($map[$context_level])) {
                            $map[$context_level] = [];
                        }

                        $map[$context_level][] = $access['capability'];
                    }
                }

                // Clear any references from the db file.
                unset($accesses);
            }

            $cache->set(static::MAP_KEY, $map);
        }
    }

    /**
     * If $context_level is null, then we will use the CONTEXT_SYSTEM.
     *
     * @param int|null $context_level
     * @return array
     * @deprecated Since Totara 17.0
     */
    public static function get_capabilities(?int $context_level = null): array {
        debugging("get_capabilities() has been deprecated, please use get_manage_capabilities() or get_audit_capabilities()", DEBUG_DEVELOPER);

        return static::get_manage_capabilities($context_level);
    }

    /**
     * If $context_level is null, then we will use the CONTEXT_SYSTEM.
     *
     * @param int|null $context_level
     * @return array
     */
    public static function get_manage_capabilities(?int $context_level = null): array {
        $context_level = $context_level ?? CONTEXT_SYSTEM;
        static::load_map();

        $cache = static::get_cache_loader();
        $map = $cache->get(static::MAP_KEY);

        $context_caps = $map[$context_level] ?? [];

        // We are going to have to default to a static capabilities provided from our notification plugins.
        return array_merge($context_caps, ['totara/notification:managenotifications']);
    }

    /**
     * If $context_level is null, then we will use the CONTEXT_SYSTEM.
     *
     * @param int|null $context_level
     * @return array
     */
    public static function get_audit_capabilities(?int $context_level = null): array {
        $context_level = $context_level ?? CONTEXT_SYSTEM;
        static::load_map();

        $cache = static::get_cache_loader();
        $map = $cache->get(static::MAP_KEY);

        $context_caps = $map[$context_level] ?? [];

        // We are going to have to default to a static capabilities provided from our notification plugins.
        return array_merge($context_caps, ['totara/notification:auditnotifications']);
    }

    /**
     * @return array
     */
    public static function get_all_capabilities(): array {
        static::load_map();

        $cache = static::get_cache_loader();
        $map = $cache->get(static::MAP_KEY);

        $capabilities_map = array_values($map);
        $capabilities = array_merge(...$capabilities_map);

        // Trim out all the duplicated capabilities.
        return array_unique($capabilities);
    }
}