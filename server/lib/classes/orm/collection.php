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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package core
 * @group orm
 */

namespace core\orm;

use core\collection as core_collection;
use core\orm\entity\entity;
use core\orm\entity\repository;

/**
 * Class collection
 *
 * ORM specific version of the collection designed to work with collection of entities
 *
 * @package core\orm
 */
class collection extends core_collection {

    /**
     * Relations to load
     *
     * @param string|array $relation Relations to load
     * @return $this
     */
    public function load($relation) {
        if (empty($this->items)) {
            return $this;
        }

        $class = $this->get_entity_class();

        /** @var repository $repo */
        $class::repository()->with($relation)->load_relations($this);

        return $this;
    }

    /**
     * Iterate over collection items and check that desired relation has been loaded for all of them
     *
     * @param string $relation
     * @return bool
     */
    public function relation_loaded(string $relation): bool {
        $this->sanity_check();

        return $this->reduce(function ($previous, entity $entity) use ($relation) {
            return $previous && $entity->relation_loaded($relation);
        }, true);
    }

    /**
     * Get entity class for the entities in this collection
     *
     * @return string
     */
    public function get_entity_class(): string {
        $this->sanity_check();

        return get_class($this->first());
    }

    /**
     * Check that collection consists of the entities of the same type to load relations
     *
     * @throws \coding_exception
     */
    protected function sanity_check() {
        if (empty($this)) {
            return;
        }

        $sample = $this->first();

        if (!$sample instanceof entity) {
            throw new \coding_exception('This must be a collection of entities');
        }

        $sample_class = get_class($sample);

        foreach ($this->items as $item) {
            if (!$item instanceof $sample_class) {
                throw new \coding_exception("Expected it to be a collection of '{$sample_class}', but it's not...");
            }
        }
    }

}