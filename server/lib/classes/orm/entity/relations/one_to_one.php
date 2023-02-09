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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package core_orm
 */

namespace core\orm\entity\relations;

use core\orm\collection;
use core\orm\entity\entity;
use core\orm\entity\repository;
use core\orm\query\builder;
use core\orm\query\field;

/**
 * Class has_one defines one to one relation between entities
 */
abstract class one_to_one extends relation {

    /**
     * This method should apply necessary constraints when loading a relation for a single entity
     *
     * @return void
     */
    public function constraints_for_entity() {
        if (!$this->entity->exists()) {
            $this->repo->where_raw('1 = 2');
            return;
        }

        $this->repo->where($this->get_foreign_key(), $this->entity->get_attribute($this->get_key()));
    }

    /**
     * This function allows to override how data is fetched from repository for one entity
     *
     * @return collection|entity|null
     */
    public function load_for_entity() {
        // If we don't have a value save us a query
        if ($this->entity->{$this->get_key()} === null) {
            return null;
        }

        return $this->get_repo()
            ->unless($this->has_order_by(), function (repository $builder) {
                $builder->order_by('id');
            })
            ->first();
    }

    /**
     * Apply constraints for loading relation for collection of items and map them back to the collection.
     *
     * @param string $name
     * @param collection $collection
     * @return void
     */
    public function load_for_collection(string $name, collection $collection) {
        $keys = $this->get_keys_from_collection($collection);

        // Chunk this to avoid too many value for IN condition
        $keys_chunked = array_chunk($keys, builder::get_db()->get_max_in_params());

        $field = new field($this->get_foreign_key(), $this->repo->get_builder());
        $field->set_identifier('one_to_one_foreign_key');

        foreach ($keys_chunked as $keys) {
            // Load all related objects
            $results = $this->repo
                ->remove_where($field)
                ->where($field, $keys)
                ->get(true);

            $results->key_by($this->get_foreign_key());

            // Now iterate over original collection of models and inject appropriate results there.
            $collection->map(
                function (entity $item) use ($results, $name) {
                    // Skip if the entity does not exist
                    if (!$item->exists()) {
                        return $item;
                    }

                    // Make sure it's marked as loaded even if we don't have any results yet
                    // as this could be chunked
                    if (!$item->relation_loaded($name)) {
                        $item->relate($name, null);
                    }

                    $value = $results->item($item->{$this->get_key()});
                    if ($value) {
                        $item->relate($name, $value);
                    }

                    return $item;
                }
            );
        }
    }

}
