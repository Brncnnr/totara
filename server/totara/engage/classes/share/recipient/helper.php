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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_engage
 */

namespace totara_engage\share\recipient;

use cache;

abstract class helper {

    /**
     * Get the component name from the class name.
     * @return string
     */
    public static function get_component(string $class): string {
        if (!is_subclass_of($class, recipient::class)) {
            throw new \coding_exception("Class '{$class}' is not a valid recipient");
        }

        $parts = explode('\\', $class);
        return reset($parts);
    }

    /**
     * @param string $component
     * @param string $area
     * @return string
     */
    public static function get_recipient_class(string $component, string $area): string {
        $area = strtolower($area);
        $component = strtolower($component);
        $cache = cache::make('totara_engage', 'share_recipient_class');
        $key = $component . '__' . $area;

        // Check if we have it cached.
        $class = $cache->get($key);
        if (!empty($class)) {
            return $class;
        }

        // Local handler.
        if ($component === 'totara_engage') {
            $class = "totara_engage\\share\\recipient\\{$area}";
            if (class_exists($class) && is_subclass_of($class, recipient::class)) {
                $cache->set($key, $class);
                return $class;
            }
        }

        // First check if we can instantiate the class directly.
        $class = "{$component}\\totara_engage\\share\\recipient\\{$area}";
        if (class_exists($class) && is_subclass_of($class, recipient::class)) {
            $cache->set($key, $class);
            return $class;
        }

        // Class might be in another namespace so we should do a lookup.
        $classes = self::get_recipient_classes();
        foreach ($classes as $class) {
            /** @var recipient $cls */
            $cls = new $class();
            $key = $cls->get_component() . '__' . $cls->get_area();
            $cache->set($key, $class);

            $parts = explode('\\', $class);
            if (end($parts) === $area) {
                return $class;
            }
        }

        throw new \coding_exception("No recipient handler found for '{$area}'");
    }

    /**
     * Get all recipient classes.
     *
     * @param bool $usecache
     * @return array
     */
    public static function get_recipient_classes(bool $usecache = true): array {
        $cache = cache::make('totara_engage', 'share_recipient_class');
        if ($usecache) {
            $all = $cache->get('all_classes');
            if (!empty($all)) {
                return $all;
            }
        }

        // Get local recipient classes.
        $classes = \core_component::get_namespace_classes(
            'share\\recipient',
            recipient::class,
            'totara_engage'
        );

        // Get other recipient classes.
        $classes = array_merge(\core_component::get_namespace_classes(
            'totara_engage\\share\\recipient',
            recipient::class
        ), $classes);

        // Cache all classes.
        $cache->set('all_classes', $classes);

        return $classes;
    }

    /**
     * @return array
     */
    public static function get_recipient_area_info(string $area): array {
        $cache = cache::make('totara_engage', 'share_recipient_areas');
        $areainfo = $cache->get($area);
        if (!empty($areainfo)) {
            return $areainfo;
        }

        // Get all recipient classes.
        $classes = self::get_recipient_classes();

        // If there are multiple recipient classes linked to the same area then the info
        // from the last recipient class in the array will persist in the cache.
        foreach ($classes as $class) {
            /** @var recipient $cls */
            $cls = new $class();
            $cache->set($cls->get_area(), [
                'label' => $cls->get_label()
            ]);
        }

        $areainfo = $cache->get($area);

        // Throw error if areainfo still empty at this point.
        if (empty($areainfo)) {
            throw new \coding_exception("Recipient area '{$area}' not found");
        }

        return $areainfo;
    }
}