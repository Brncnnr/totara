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

use coding_exception;
use core\orm\collection;
use core\orm\entity\entity;
use core\orm\entity\repository;
use core\orm\query\builder;
use core\orm\query\field;

/**
 * Class has_one_through
 *
 * Represents one to one relationship between entities connected through another entity
 */
class has_one_through extends has_many_through {

    /**
     * An instance of intermediate builder
     *
     * @var builder
     */
    protected $intermediate_builder;

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

        $intermediate_builder = $this->get_intermediate_builder();

        // Chunk this to avoid too many value for IN condition
        $keys_chunked = array_chunk($keys, builder::get_db()->get_max_in_params());

        // Prepare query
        $repository = $this->repo
            ->add_select('*')
            ->add_select(
                sprintf(
                    "\"%s\".%s as %s",
                    $intermediate_builder->get_table(),
                    $this->get_intermediate_foreign_key(),
                    $this->get_intermediate_key_name()
                )
            )
            ->join($this->intermediate::TABLE, $this->get_foreign_key(), $this->get_intermediate_related_foreign_key());

        $field = new field($this->get_intermediate_foreign_key(), $intermediate_builder);
        $field->set_identifier('has_one_through_intermediate_foreign_key');

        foreach ($keys_chunked as $keys) {
            // Load possible values
            $results = $repository
                ->remove_where($field)
                ->where($field, $keys)
                ->get(true);

            $results->key_by($this->get_intermediate_key_name());

            // Now iterate over original collection and append the results there
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

                        // We add this key temporarily to link children to the parent,
                        // Since we do want to return entities in a valid state,
                        // We'll unset it
                        unset($item->{$this->get_intermediate_key_name()});
                    }

                    return $item;
                }
            );
        }
    }

    /**
     * Return an instance of a builder for the intermediate table
     *
     * @return builder
     */
    protected function get_intermediate_builder(): builder {
        if (!$this->intermediate_builder) {
            $this->intermediate_builder = builder::table($this->intermediate::TABLE);
        }

        return $this->intermediate_builder;
    }

}
