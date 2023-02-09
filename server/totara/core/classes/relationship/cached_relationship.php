<?php
/*
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\relationship;

use coding_exception;

/**
 * Relationship model with cached instances
 */
final class cached_relationship extends relationship {

    /**
     * @var array
     */
    private static $cached_relationships_by_idnumber = [];

    /**
     * Loads from database by given id. Adds result to cache
     *
     * @param int $id
     * @return mixed|cached_relationship
     */
    public static function load_by_id(int $id) {
        foreach (self::$cached_relationships_by_idnumber as $item) {
            if ($item->id == $id) {
                return $item;
            }
        }
        $relationship = parent::load_by_id($id);
        self::$cached_relationships_by_idnumber[$relationship->idnumber] = $relationship;
        return $relationship;
    }

    /**
     * Gets a relationship based on the given idnumber, returns cached instance if possible
     *
     * @param string $idnumber
     * @return static
     * @throws coding_exception
     */
    public static function load_by_idnumber(string $idnumber) {
        if (isset(self::$cached_relationships_by_idnumber[$idnumber])) {
            return self::$cached_relationships_by_idnumber[$idnumber];
        }
        $entity = static::get_entity_class()::repository()
            ->where('idnumber', $idnumber)
            ->one(true);

        $relationship = static::load_by_entity($entity);
        self::$cached_relationships_by_idnumber[$idnumber] = $relationship;
        return $relationship;
    }

    /**
     * Resets the cache
     */
    public static function reset_cache(): void {
        self::$cached_relationships_by_idnumber = [];
    }

    /**
     * Create a new relationship.
     *
     * @param string[] $resolver_class_names Array of relationship resolver class names, e.g. [subject::class, manager::class]
     * @param string $idnumber Unique string identifier for this relationship.
     * @param int $sort_order
     * @param int|null $type Optional type identifier - defaults to standard type.
     * @param string|null $component Plugin that the relationship is exclusive to. Defaults to being available for all.
     * @return relationship
     */
    public static function create(
        array $resolver_class_names,
        string $idnumber,
        int $sort_order = 1,
        int $type = null,
        string $component = null
    ) {
        $relationship = parent::create($resolver_class_names, $idnumber, $sort_order, $type, $component);
        self::$cached_relationships_by_idnumber[$idnumber] = $relationship;

        return $relationship;
    }

    /**
     * Delete this relationship.
     */
    public function delete(): void {
        parent::delete();
        unset(self::$cached_relationships_by_idnumber[$this->idnumber]);
    }

}
