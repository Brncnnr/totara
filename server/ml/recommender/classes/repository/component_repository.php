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
use ml_recommender\entity\component;

/**
 * Class components_repository
 * @package ml_recommender\repository
 */
final class component_repository extends repository {
    /**
     * @return component[]
     */
    public function get_all(): array {
        $builder = builder::table(static::get_table());
        $builder->map_to(component::class);

        return $builder->fetch();
    }

    /**
     * Make sure that entry for component/area exists and return its id
     * @param string $component
     * @param string|null $area
     * @return int
     */
    public function ensure_id(string $component, ?string $area = null) {
        $record = builder::table(static::get_table())
            ->select('id')
            ->where('component', $component)
            ->where('area', $area)
            ->one();
        if ($record) {
            return $record->id;
        }

        $entity = new component();
        $entity->component = $component;
        $entity->area = $area;
        $entity->save();
        return $entity->id;
    }

    /**
     * Get all id's with component
     * @param $component
     * @return int[]
     */
    public function get_all_component_ids(string $component) {
        $component_ids = builder::table(static::get_table())
            ->select('id')
            ->where('component', $component)
            ->fetch();
        $result = [];
        foreach ($component_ids as $component_id) {
            $result[] = $component_id->id;
        }
        return $result;
    }
}