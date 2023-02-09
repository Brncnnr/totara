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
namespace totara_engage\sidepanel;

final class helper {

    /**
     * Get all classes that provides SidePanel content.
     *
     * @return array
     */
    public static function get_providers(): array {
        $classes = [];

        // Add engage_provider to classes.
        $classes[] = engage_provider::class;

        $classes = array_merge($classes, \core_component::get_namespace_classes(
            'totara_engage\\sidepanel',
            provider::class
        ));

        return $classes;
    }

    /**
     * Get all navigation panel section components.
     *
     * @return array
     */
    public static function get_navigation_sections(): array {
        $classes = self::get_providers();

        $components = [];
        /** @var provider $class */
        foreach ($classes as $class) {
            if ($class::provide_navigation_section()) {
                /** @var provider $instance */
                $instance = new $class();
                $components[] = $instance->get_navigation_section();
            }
        }

        return $components;
    }
}