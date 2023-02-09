<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @package totara_competency
 */

namespace totara_competency;


/**
 * Pathway_evaluator factory class to obtain an instance of the pathway_evaluator for the specific pathway type
 */
class pathway_evaluator_factory {

    /**
     * Create a new instance of pathway_evaluator for the given pathway.
     *
     * @param pathway $pathway
     * @param pathway_evaluator_user_source $user_id_source
     * @return pathway_evaluator for the requested pathway type
     */
    public static function create(pathway $pathway, pathway_evaluator_user_source $user_id_source): pathway_evaluator {
        $classname = static::get_classname($pathway->get_path_type());
        return new $classname($pathway, $user_id_source);
    }

    /**
     * Returns the namespace that corresponds to this class.
     *
     * No checks are made to ensure the namespace exists or if the type is enabled.
     *
     * @param string $path_type
     * @return string The namespace of this type
     */
    public static function get_namespace(string $path_type): string {
        return "\\pathway_{$path_type}";
    }

    /**
     * @param string $path_type
     * @return string Namespaced name of the class corresponding to this pathway_type.
     * @throws \coding_exception if the type does not have a valid corresponding class.
     */
    public static function get_classname(string $path_type): string {
        $classname = static::get_namespace($path_type) . "\\{$path_type}_evaluator";
        if (!class_exists($classname) || !is_subclass_of($classname, 'totara_competency\pathway_evaluator')) {
            throw new \coding_exception(
                "Pathway type '{$path_type}' not found.",
                "Pathway type '{$path_type}' does not have a valid corresponding class"
            );
        }

        return $classname;
    }
}
