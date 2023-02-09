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
 * @package core
 */

namespace core\theme\file;

use theme_config;

/**
 * Class helper
 *
 * Theme file helper.
 *
 * @package core\theme\file
 */
final class helper {

    /**
     * Get all theme file classes which can be set through the theme settings.
     *
     * @return array
     */
    public static function get_classes(): array {
        // Return from cache.
        $cache = \cache::make('core', 'themefileclasses');
        if ($classes = $cache->get('allclasses')) {
            return $classes;
        }

        // Setup cache.
        $classes = \core_component::get_namespace_classes(
            'theme\\file',
            theme_file::class
        );

        // Also setup cache per theme_file to speed up requests.
        /** @var theme_file $class */
        foreach ($classes as $key => $class) {
            // Redefine key.
            unset($classes[$key]);
            $classes[$class::get_id()] = $class;
        };

        $cache->set('allclasses', $classes);

        return $classes;
    }

    /**
     * Get a theme file handler for the component and area if any.
     *
     * @param theme_config $theme_config
     * @param string $component
     * @param string $area
     *
     * @return theme_file|null
     */
    public static function get_class_for_component(theme_config $theme_config, string $component, string $area): ?theme_file {
        $classes = self::get_classes();
        $key = "{$component}/{$area}";
        if (!empty($classes[$key])) {
            return new $classes[$key]($theme_config);
        }
        return null;
    }

}