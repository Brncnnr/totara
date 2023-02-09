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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_engage
 */

namespace totara_engage\interactor;

use totara_engage\access\accessible;

final class interactor_factory {

    /** @var interactor[] */
    private static $interactors = null;

    /**
     * resolver constructor.
     */
    private function __construct() {
        // Not allowed to instantiate this class.
    }

    /**
     * Create an interactor for specific component.
     *
     * @param string $component
     * @param array $resource_data
     * @param int|null $actor_id
     *
     * @return interactor
     */
    public static function create(string $component, array $resource_data, ?int $actor_id = null): interactor {
        $interactor_class = self::get_interactor_class($component);
        return new $interactor_class($resource_data, $actor_id);
    }

    /**
     * Create an interactor for specific component based on an accessible resource that's already created.
     *
     * @param accessible $resource
     * @param int|null $actor_id
     *
     * @return interactor
     */
    public static function create_from_accessible(accessible $resource, ?int $actor_id = null): interactor {
        $component = $resource::get_resource_type();
        /** @var interactor $interactor_class */
        $interactor_class = self::get_interactor_class($component);
        return $interactor_class::create_from_accessible($resource, $actor_id);
    }

    /**
     * @param $component
     * @return String
     */
    private static function get_interactor_class($component): string {
        // If the interactor class is not cached already then get the interactor class for this
        // component and cache it for this request.
        if (isset(self::$interactors[$component])) {
            return self::$interactors[$component];
        } else {
            /** @var interactor[] $classes */
            $classes = \core_component::get_namespace_classes(
                'totara_engage\interactor',
                interactor::class,
                $component
            );

            if (empty($classes)) {
                throw new \coding_exception("No interactor defined for '{$component}'");
            } else if (1 != count($classes)) {
                debugging("More than one interactor defined for '{$component}'", DEBUG_DEVELOPER);
            }

            $interactor_class = reset($classes);
            self::$interactors[$component] = $interactor_class;

            return $interactor_class;
        }
    }

}