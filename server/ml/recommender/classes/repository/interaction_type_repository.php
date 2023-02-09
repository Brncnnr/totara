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
 * @author  Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package ml_recommender
 */
namespace ml_recommender\repository;

use core\orm\entity\repository;
use core\orm\query\builder;
use ml_recommender\entity\interaction_type;

/**
 * Class interaction_type_repository
 * @package ml_recommender\repository
 */
final class interaction_type_repository extends repository {
    /**
     * @return interaction_type[]
     */
    public function get_all(): array {
        $builder = builder::table(static::get_table());
        $builder->map_to(interaction_type::class);

        return $builder->fetch();
    }

    /**
     * Make sure that entry for component/area exists and return its id
     * @param string $interaction_type
     * @return int
     */
    public function ensure_id(string $interaction_type) {
        $type = builder::table(static::get_table())
            ->select('id')
            ->where('interaction', $interaction_type)
            ->one();
        if ($type) {
            return $type->id;
        }

        $entity = new interaction_type();
        $entity->interaction = $interaction_type;
        $entity->save();
        return $entity->id;
    }
}