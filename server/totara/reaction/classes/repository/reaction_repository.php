<?php
/**
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_reaction
 */
namespace totara_reaction\repository;

use core\orm\query\builder;
use totara_reaction\entity\reaction;
use core\orm\entity\repository;

/**
 * Repository for reaction
 */
final class reaction_repository extends repository {
    /**
     * Finding the reaction of a user against the specific item
     *
     * @param string $component
     * @param string $area
     * @param int    $instanceid
     * @param int    $userid
     *
     * @return reaction|null
     */
    public function find_for_instance(string $component, string $area, int $instanceid, int $userid): ?reaction {
        $builder = builder::table(static::get_table());
        $builder->map_to(reaction::class);

        $builder->where('component', $component);
        $builder->where('area', $area);
        $builder->where('instanceid', $instanceid);
        $builder->where('userid', $userid);

        /** @var reaction|null $reaction */
        $reaction = $builder->one();
        return $reaction;
    }

    /**
     * Counting the total number of reactions against a specific item.
     *
     * @param string $component
     * @param string $area
     * @param int $instance_id
     *
     * @return int
     */
    public function count_for_instance(string $component, string $area, int $instance_id): int {
        $builder = builder::table(static::get_table());

        $builder->where('component', $component);
        $builder->where('area', $area);
        $builder->where('instanceid', $instance_id);

        return $builder->count();
    }

    /**
     * @param int $user_id
     * @return int
     */
    public function count_for_user(int $user_id): int {
        $builder = builder::table(static::get_table());

        $builder->where('userid', $user_id);
        return $builder->count();
    }

    /**
     * @param int $user_id
     * @param int|null $page
     *
     * @return reaction[]
     */
    public function get_for_user(int $user_id, ?int $page = null): array {
        $builder = builder::table(static::get_table());
        $builder->where('userid', $user_id);

        $builder->map_to(reaction::class);

        if (null !== $page && 0 !== $page) {
            $paginator = $builder->paginate($page);
            return $paginator->get_items()->all();
        }

        return $builder->fetch();
    }
}