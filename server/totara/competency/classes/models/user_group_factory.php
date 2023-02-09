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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\models;

use coding_exception;
use core\collection;

/**
 * Factory to return an instance of a specific user group model identified by a type
 */
class user_group_factory {

    /**
     * @param assignment $assignment
     * @return user_group
     * @throws coding_exception
     */
    public static function create(assignment $assignment): user_group {
        /** @var user_group $class_name */
        $class_name = "\\totara_competency\\models\\user_group\\{$assignment->user_group_type}";
        if (class_exists($class_name) && is_subclass_of($class_name, user_group::class)) {
            return $assignment->get_user_group_entity() === null
                ? $class_name::load_by_id($assignment->user_group_id)
                : $class_name::load_by_entity($assignment->get_user_group_entity());
        }

        throw new coding_exception('Unknown user group given!');
    }

    /**
     * Batch query user groups by group type to reduce the number of queries for user groups
     *
     * @param collection $assignments
     * @return array
     */
    public static function load_user_groups(collection $assignments): array {
        $user_group_collection = [];
        $user_group_entities = [];

        foreach ($assignments as $assignment) {
            $user_group_collection[$assignment->user_group_type]['ids'][$assignment->user_group_id] = $assignment->user_group_id;
        }

        /** @var user_group $class_name */
        foreach ($user_group_collection as $user_group_type => $data) {
            $class_name = "\\totara_competency\\models\\user_group\\{$user_group_type}";
            if (class_exists($class_name) && is_subclass_of($class_name, user_group::class)) {
                $user_group_entities[$user_group_type] = $class_name::load_user_groups($data['ids'])->all(true);
            }
        }

        return $user_group_entities;
    }

}