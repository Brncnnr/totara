<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\data_provider\application;

use core\collection;
use core\orm\entity\repository;
use core\orm\query\builder;
use mod_approval\data_provider\provider;
use mod_approval\model\application\application as application_model;

abstract class application_provider_base extends provider {

    /**
     * @var int
     */
    protected $user_id;

    /**
     * @var array
     */
    protected $capability_maps = [];

    /**
     * Get all the defined capability_map classes that this provider uses.
     *
     * @return array
     */
    abstract public static function capability_map_classes(): array;

    /**
     * Create a new instance of this data_provider for a user.
     *
     * @param int $user_id The id of the user for whom we are loading applications.
     */
    public function __construct(int $user_id) {
        $this->user_id = $user_id;
        $this->require_capability_maps();
    }

    /**
     * Applies the table joins necessary for each active capability map.
     *
     * @param repository $repository
     */
    protected function apply_capability_map_joins(repository $repository): void {
        foreach ($this->capability_maps as $map) {
            $map->apply_map_join($repository);
        }
    }

    /**
     * Applies the conditions necessary for each active capability map.
     *
     * @param builder $builder
     */
    protected function apply_capability_map_conditions(builder $builder): void {
        foreach ($this->capability_maps as $map) {
            $builder->when($map->is_active(), function (builder $condition) use ($map) {
                $map->get_or_where_condition($condition);
            });
        }
    }

    /**
     * Checks to see if the user has any of the mapped capabilities.
     *
     * @return bool
     */
    public function has_any_capability(): bool {
        foreach ($this->capability_maps as $map) {
            if ($map->is_active()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Builds capability maps for the current user.
     */
    protected function require_capability_maps(): void {
        // Check for and build maps, and store the instances locally.
        foreach (static::capability_map_classes() as $class) {
            $this->capability_maps[] = new $class($this->user_id);
        }
    }

    /**
     * Map the application entities to their respective model class.
     *
     * @return collection|application_model[]
     */
    protected function process_fetched_items(): collection {
        return $this->items->map_to(application_model::class);
    }
}