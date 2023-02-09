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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package core_orm
 */

namespace core\orm\entity;

use Closure;
use core\orm\collection;
use core\orm\entity\relations\relation;

/**
 * This buffer can be used to defer loading of entities based on their relationship.
 *
 * All it needs is an entity and the relation name to be deferred. You can call 'defer'
 * multiple times. Only once you call the returned closure the entities are fetched
 * from the database in the least amount of queries possible.
 *
 * Example:
 *
 * $deferred1 = \core\orm\entity\buffer::defer($competency1, 'framework');
 * $deferred2 = \core\orm\entity\buffer::defer($competency2, 'framework');
 *
 * // No queries got issued so far.
 * // Later calling the closure returned will result in loading the buffer and returning the item.
 * // Only one query was issued for that relationship.
 *
 * $framework1 = $deferred1();
 * $framework2 = $deferred2();
 */
class buffer {

    /**
     * All entities are added to the buffer during deferring
     * and are grouped by the identifier (combination of entity name and relation).
     * Each group contains the relation instance, the relation name and the collection
     * of entities belonging to the group.
     *
     * @var array
     */
    protected static $buffered = [];

    /**
     * Once the buffer is loaded all loaded entities are moved into here.
     * Entities are grouped by the identifier (combination of entity name and relation)
     *
     * @var array
     */
    protected static $loaded = [];

    /**
     * @param entity $entity
     * @param string $relation_name
     * @return Closure
     */
    public static function defer($entity, string $relation_name): Closure {
        // If we already have the relation we don't need to buffer it
        if ($entity->relation_loaded($relation_name)) {
            return function () use ($entity, $relation_name) {
                return $entity->$relation_name;
            };
        }

        $classname = get_class($entity);

        /** @var relation $relation */
        $relation = $entity::repository()->get_relation($relation_name);
        if ($relation === null) {
            throw new \coding_exception("Unknown relation with name '{$relation_name}' in entity '".$classname."'");
        }

        // The relation gives us the key so lets get the unique id from the entity
        $id = $entity->{$relation->get_key()};

        // There's nothing to load
        if ($id === null) {
            return function () {
                return null;
            };
        }

        // We want to group all relations and entities together
        $identifier = $classname.'/'.$relation_name;
        if (!isset(static::$buffered[$identifier])) {
            $collection = new collection();
            static::$buffered[$identifier] = [$relation, $relation_name, $collection];
            $collection->append($entity);
        } else {
            /** @var collection $collection */
            [$relation, $relation_name, $collection] = static::$buffered[$identifier];
            // If the entity already got buffered or loaded don't add it again
            if (!$collection->find('id', $entity->id)) {
                $collection->append($entity);
            }
        }

        // The deferred instance will be called after all the resolvers
        // were called, the graphql library calls the callback we pass to it
        return function () use ($identifier, $id) {
            // Load (if not yet loaded)
            buffer::load_buffered();

            return buffer::get($identifier, $id);
        };
    }

    /**
     * Returns one result from the loaded entities
     *
     * @param string $identifier
     * @param int $id
     * @return entity|collection|null
     */
    public static function get(string $identifier, int $id) {
        return static::$loaded[$identifier][$id] ?? null;
    }

    /**
     * Load all entities in the buffer. Will only be trigger queries once.
     */
    protected static function load_buffered(): void {
        // Nothing to load
        if (empty(static::$buffered)) {
            return;
        }

        /** @var relation $relation */
        /** @var collection $collection */

        // Go through each type of entity and relation and load the relation for all entities in it.
        // Add the related entity/entities to the loaded property at the end and remove it from the buffer
        foreach (static::$buffered as $identifier => [$relation, $relation_name, $collection]) {
            $relation->load_for_collection($relation_name, $collection);

            if (!isset(static::$loaded[$identifier])) {
                static::$loaded[$identifier] = [];
            }

            foreach ($collection as $item) {
                // We replace existing loaded entities to make sure we always
                // have the newest version in the buffer in case it's loaded multiple times.
                // The alternative would be to just not add it to the buffer when the same entity
                // is already in the loaded ones but re-querying feels more secure.
                static::$loaded[$identifier][(int)$item->{$relation->get_key()}] = $item->{$relation_name};
            }

            unset(static::$buffered[$identifier]);
        }
    }

    /**
     * Clear all buffered and loaded entities
     */
    public static function clear() {
        static::$loaded = [];
        static::$buffered = [];
    }
}