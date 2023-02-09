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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_criteria
 */

namespace totara_criteria;

use coding_exception;
use stdClass;
use totara_competency\plugin_types;
use totara_criteria\entity\criterion as criterion_entity;

/**
 * Criterion factory class to obtain an instance of the specific criterion type
 */
class criterion_factory {

    /**
     * Instantiate an instance of the specified criterion type
     *
     * @param string $type Criterion type to instantiate
     * @return criterion of the requested type
     */
    public static function create(string $type) {
        static::require_enabled($type);

        /** @var criterion $classname */
        $classname = static::get_classname($type);

        return new $classname();
    }

    /**
     * Instantiate a criterion of the specified type and fetch
     * its detail from the database
     *
     * @param string $type Criterion type to instantiate
     * @param int $id Id of the criterion to fetch
     * @return criterion of the requested type
     */
    public static function fetch(string $type, int $id) {
        static::require_enabled($type);

        /** @var criterion $classname */
        $classname = static::get_classname($type);

        return $classname::fetch($id);
    }

    /**
     * Instantiate a criterion from a given entity
     *
     * @param criterion_entity $criterion
     * @return criterion of the requested type
     * @throws coding_exception
     */
    public static function fetch_from_entity(criterion_entity $criterion) {
        if (!$criterion->exists()) {
            throw new coding_exception('A criterion needs to exist');
        }
        static::require_enabled($criterion->plugin_type);

        /** @var criterion $classname */
        $classname = static::get_classname($criterion->plugin_type);

        return $classname::fetch_from_entity($criterion);
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
        return "\\criteria_{$type}";
    }

    /**
     * Returns the classname associated with the given $type.
     *
     * Ensures that the classname is a subclass of the totara_criteria\criterion base class.
     *
     * Does not check that the type is enabled.
     *
     * @param string $type
     * @return string
     * @throws coding_exception
     */
    public static function get_classname(string $type): string {
        $classname = static::get_namespace($type) . "\\{$type}";
        if (!class_exists($classname) || !is_subclass_of($classname, 'totara_criteria\criterion')) {
            throw new coding_exception("Invalid type", "Criterion type '{$type}' does not exist");
        }

        return $classname;
    }

    /**
     * @param string $type
     * @return void
     * @throws coding_exception if the type is not enabled
     */
    private static function require_enabled($type) {
        // All considered enabled in v1
        return true;
    }

    /**
     * Dump the criterion configuration
     *
     * @param string $type Criterion type
     * @param int $id Id of the criterion
     * @return stdClass
     */
    public static function dump_criterion_configuration(string $type, int $id) {
        $installed = plugin_types::get_installed_plugins('criteria', 'totara_criteria');
        if (!array_key_exists($type, $installed)) {
            throw new coding_exception("Invalid type", "Invalid criterion type '{$type}'");
        }

        /** @var criterion $classname */
        $classname = static::get_classname($type);

        return $classname::dump_criterion_configuration($id);
    }
}
