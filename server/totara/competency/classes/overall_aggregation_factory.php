<?php
/*
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency;

/**
 * Aggregation factory to obtain an instance of the specific pathway aggregation type
 */
class overall_aggregation_factory {

    /**
     * Instantiate an instance of the given type of pathway aggregation.
     *
     * @param string $type
     * @return overall_aggregation of the requested type
     */
    public static function create(string $type): overall_aggregation {
        static::require_enabled($type);

        $classname = static::get_classname($type);

        return new $classname();
    }

    /**
     * Returns the namespace that corresponds to this class.
     *
     * No checks are made to ensure the namespace exists or if the type is enabled.
     *
     * @param string $type
     * @return string The namespace of this type
     */
    public static function get_namespace(string $type): string {
        return "\\aggregation_{$type}";
    }

    /**
     * Returns the classname associated with the given $type.
     * Ensures that the classname is a subclass of the overall_aggregation base class.
     *
     * Does not check that the type is enabled.
     *
     * @param string $type
     * @return string
     * @throws \coding_exception if the type does not exist
     */
    public static function get_classname(string $type): string {
        $classname = static::get_namespace($type) . '\\' . $type;
        if (!class_exists($classname) || !is_subclass_of($classname, overall_aggregation::class)) {
            throw new \coding_exception('Invalid type', 'Type, ' . $classname . ', is not valid');
        }

        return $classname;
    }

    /**
     * @param string $type
     * @return void
     * @throws \coding_exception if the type is not enabled
     */
    private static function require_enabled($type) {
        // All considered enabled in v1
        return true;
    }
}
