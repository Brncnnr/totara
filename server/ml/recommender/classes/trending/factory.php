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
 * @author  Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package ml_recommender
 */
namespace ml_recommender\trending;

final class factory {

    /**
     * factory constructor.
     */
    private function __construct() {}

    /**
     * Create a new instance of a trending component.
     *
     * @param string $component
     */
    public static function create(string $component): trending {
        $classes = \core_component::get_namespace_classes(
            'ml_recommender\\trending',
            trending::class,
            $component
        );

        // No resolver found for component.
        if (empty($classes)) {
            throw new \coding_exception("No trending resolver found for component '{$component}'");
        }

        // More than one resolver found.
        if (sizeof($classes) > 1) {
            debugging("More than one resolver found for component '{$component}'");
        }

        // Return an instance of the resolver.
        $cls = reset($classes);
        return new $cls();
    }

}