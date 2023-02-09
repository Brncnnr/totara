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
use core\orm\query\builder;
use core\orm\query\field;

/**
 * Class has_many_through
 *
 * Represents one to many relationship between entities connected through another entity
 */
class has_many_through extends relation {

    /**
     * Intermediate entity model class
     *
     * @var string|entity
     */
    protected $intermediate;

    /**
     * Intermediate entity key
     *
     * @var string
     */
    protected $intermediate_related_foreign_key;

    /**
     * Intermediate entity foreign key
     *
     * @var string
     */
    protected $intermediate_foreign_key;

    /**
     * @param entity $entity The entity this relation is for
     * @param string $intermediate The class name of the intermediate entity
     * @param string $related The class name of the table this relation is resolving
     * @param string $key The key in the given entity used as foreign_key in the intermediate table, usually 'id'
     * @param string $intermediate_foreign_key The foreign_key in the intermediate table matching the key in the entity table
     * @param string $intermediate_related_foreign_key The foreign_key in the intermed. table matching the key in the related table
     * @param string $related_key The key of the related table, usually 'id'
     */
    public function __construct(
        entity $entity,
        string $intermediate,
        string $related,
        string $key,
        string $intermediate_foreign_key,
        string $intermediate_related_foreign_key,
        string $related_key
    ) {
        $this->intermediate_foreign_key = $intermediate_foreign_key;
        $this->intermediate_related_foreign_key = $intermediate_related_foreign_key;
        $this->intermediate = $intermediate;

        parent::__construct($entity, $related, $related_key, $key);
    }

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

        $this->repo->join($this->intermediate::TABLE, $this->get_related_key(), $this->get_intermediate_related_foreign_key())
            ->where(new field(
                $this->get_intermediate_foreign_key(),
                builder::table($this->intermediate::TABLE)
            ), $this->entity->{$this->get_key()});
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

        $intermediate_builder = builder::table($this->intermediate::TABLE);

        // Chunk this to avoid too many value for IN condition
        $keys_chunked = array_chunk($keys, builder::get_db()->get_max_in_params());

        // Prepare the query
        $repository = $this->repo
            ->select('*')
            ->add_select(
                sprintf(
                    "\"%s\".%s as %s",
                    $intermediate_builder->get_table(),
                    $this->get_intermediate_foreign_key(),
                    $this->get_intermediate_key_name()
                )
            )
            ->join($this->intermediate::TABLE, $this->get_related_key(), $this->get_intermediate_related_foreign_key());

        $field = new field($this->get_intermediate_foreign_key(), $intermediate_builder);
        $field->set_identifier('has_many_through_intermediate_foreign_key');

        // Group the result so that we can get the related results quicker
        $grouped = [];
        foreach ($keys_chunked as $keys) {
            // Load possible values
            $results = $repository->remove_where($field)
                ->where($field, $keys)
                ->get(true);

            foreach ($results as $result) {
                $grouped[$result->{$this->get_intermediate_key_name()}][$result->id] = $result;

                // We add this key temporarily to link children to the parent,
                // Since we do want to return entities in a valid state,
                // We'll unset it
                unset($result->{$this->get_intermediate_key_name()});
            }
        }

        // No need to proceed
        if (empty($grouped)) {
            return;
        }

        // Now iterate over original collection and append the results there
        $collection->map(
            function (entity $item) use ($grouped, $name) {
                if ($item->exists()) {
                    $item->relate($name, new collection($grouped[$item->{$this->get_key()}] ?? []));
                }

                return $item;
            }
        );
    }

    /**
     * Delete related models
     *
     * @return $this
     */
    public function delete() {
        // Let's get IDS to delete
        $ids = $this->repo->select('id')->get()->pluck('id');

        if (!empty($ids)) {
            $this->related::repository()
                ->where('id', 'in', $ids)
                ->delete();
        }

        return $this;
    }

    /**
     * Update related models
     *
     * @param array $attributes Attributes to update
     * @return $this
     */
    public function update($attributes) {
        // Let's get IDS to delete
        $ids = $this->repo->select('id')->get()->pluck('id');

        if (!empty($ids)) {
            $this->related::repository()
                ->where('id', 'in', $ids)
                ->update($attributes);
        }

        return $this;
    }

    /**
     * Get intermediate entity key
     *
     * @return string
     */
    public function get_intermediate_related_foreign_key() {
        return $this->intermediate_related_foreign_key;
    }

    /**
     * Get intermediate entity foreign key
     *
     * @return string
     */
    public function get_intermediate_foreign_key() {
        return $this->intermediate_foreign_key;
    }

    /**
     * Get temporary key name that is used to connect models
     *
     * @return string
     */
    public function get_intermediate_key_name() {
        return $this->intermediate::TABLE . '___' . $this->get_intermediate_foreign_key();
    }

    /**
     * Alias for foreign key
     *
     * @return string
     */
    public function get_related_key() {
        return $this->get_foreign_key();
    }

}
