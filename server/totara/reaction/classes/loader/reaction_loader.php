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
 * @package totara_reaction
 */
namespace totara_reaction\loader;

use core\orm\paginator;
use core\orm\query\builder;
use totara_reaction\reaction;
use totara_reaction\entity\reaction as entity;
use totara_reaction\repository\reaction_repository;
use totara_reaction\resolver\resolver_factory;

/**
 * Class reaction_loader
 * @package totara_reaction\loader
 */
final class reaction_loader {
    /**
     * Preventing this class from being instantiated.
     * reaction_loader constructor.
     */
    private function __construct() {
    }

    /**
     * Count the total number of reactions against the item.
     *
     * @param int $instance_id
     * @param string $component
     * @param string $area
     *
     * @return int
     */
    public static function count(int $instance_id, string $component, string $area): int {
        /** @var reaction_repository $repo */
        $repo = entity::repository();
        return $repo->count_for_instance($component, $area, $instance_id);
    }

    /**
     * Get the base builder to fetch the record(s) of reaction.
     *
     * @return builder
     */
    protected static function base_builder(): builder {
        $builder = builder::table('reaction', 'r');
        $builder->join(['user', 'u'], 'r.userid', 'u.id');
        $builder->select([
            'r.id as reactionid',
            'r.userid as reactionuserid',
            'r.instanceid as reactioninstanceid',
            'r.component as reactioncomponent',
            'r.area as reactionarea',
            'r.contextid as reactioncontextid',
            'r.timecreated as reactiontimecreated',
            'u.firstname',
            'u.id',
            'u.lastname',
            'u.email',
        ]);

        $builder->results_as_arrays();
        $builder->map_to([static::class, 'create_reaction']);

        return $builder;
    }

    /**
     * @param string $component
     * @param string $area
     * @param int $instanceid
     * @param int $page
     *
     * @return paginator
     */
    public static function get_paginator(string $component, string $area, int $instanceid, int $page = 1): paginator {
        $builder = static::base_builder();

        $builder->where('r.component', $component);
        $builder->where('r.area', $area);
        $builder->where('r.instanceid', $instanceid);
        $builder->where('u.deleted', 0);

        $resolver = resolver_factory::create_resolver($component);
        $perpage = $resolver->items_per_page();

        return $builder->paginate($page, $perpage);
    }

    /**
     * @param array $result
     * @return reaction
     *
     * @internal
     */
    public static function create_reaction(array $result): reaction {
        $map = [
            'id' => 'reactionid',
            'userid' => 'reactionuserid',
            'instanceid' => 'reactioninstanceid',
            'component' => 'reactioncomponent',
            'area' => 'reactionarea',
            'contextid' => 'reactioncontextid',
            'timecreated' => 'reactiontimecreated'
        ];

        $entity = new entity();
        foreach ($map as $attribute => $key) {
            if (!array_key_exists($key, $result)) {
                throw new \coding_exception("No key '{$key}' existing in the array of result");
            }

            if (!$entity->has_attribute($attribute)) {
                debugging("Entity does not have attribute '{$attribute}'", DEBUG_DEVELOPER);
                continue;
            }

            $entity->set_attribute($attribute, $result[$key]);
            unset($result[$key]);
        }

        // Everything else in the array of result is the attribute of user's record.
        $user = (object) $result;
        return reaction::from_entity($entity, $user);
    }

    /**
     * @param int $instance_id
     * @param string $component
     * @param string $area
     * @param int|null $user_id
     *
     * @return bool
     */
    public static function exist(int $instance_id, string $component, string $area, ?int $user_id = null): bool {
        global $USER;

        if (null === $user_id || 0 === $user_id) {
            $user_id = $USER->id;
        }

        $builder = builder::table('reaction', 'r');
        $builder->join(['user', 'u'], 'r.userid', 'u.id');

        $builder->where('r.instanceid', $instance_id);
        $builder->where('r.component', $component);
        $builder->where('r.area', $area);
        $builder->where('r.userid', $user_id);
        $builder->where('u.deleted', 0);

        return $builder->exists();
    }

    /**
     * Note that if the user has already been deleted, then this API will return an null record.
     *
     * @param string    $component
     * @param string    $area
     * @param int       $instance_id
     * @param int|null  $user_id
     *
     * @return reaction|null
     */
    public static function find_by_parameters(string $component, string $area, int $instance_id, ?int $user_id = null): ?reaction {
        global $USER;

        if (null === $user_id || 0 === $user_id) {
            $user_id = $USER->id;
        }

        $builder = static::base_builder();
        $builder->where('r.component', $component);
        $builder->where('r.area', $area);
        $builder->where('r.instanceid', $instance_id);
        $builder->where('r.userid', $user_id);
        $builder->where('u.deleted', 0);

        /** @var null|reaction $reaction */
        $reaction = $builder->one();
        return $reaction;
    }
}