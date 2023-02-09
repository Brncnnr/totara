<?php
/**
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package ml_recommender
 */
namespace ml_recommender\repository;

use core\orm\entity\repository;
use core\orm\query\builder;
use ml_recommender\entity\component;
use ml_recommender\entity\interaction;

/**
 * Class interaction_repository
 * @package ml_recommender\repository
 */
final class interaction_repository extends repository {
    /**
     * @return interaction[]
     */
    public function get_all(): array {
        $builder = builder::table(static::get_table());
        $builder->map_to(interaction::class);

        return $builder->fetch();
    }

    /**
     * Delete any interaction events for the provided component/item.
     * Used when the source item has been dropped & interactions need removing.
     *
     * @param string $component
     * @param int $item_id
     */
    public function delete_for_component(string $component, int $item_id): void {
        /**
         * @var component_repository $component_repo
         */
        $component_repo = component::repository();

        $builder = builder::table(static::get_table());
        $builder->where_in('component_id', $component_repo->get_all_component_ids($component));
        $builder->where('item_id', $item_id);

        $builder->delete();
    }

    /**
     * Checks whether the interaction already exists in the database.
     * @param interaction $entity
     * @return bool
     */
    public function has_interaction(interaction $entity): bool {
        return $this->where('user_id', '=', $entity->user_id)
            ->where('component_id', '=', $entity->component_id)
            ->where('interaction_type_id', '=', $entity->interaction_type_id)
            ->where('item_id', '=', $entity->item_id)
            ->where('time_created', '=', $entity->time_created)
            ->exists();
    }
}