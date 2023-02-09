<?php
/*
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\relationship\helpers;

use coding_exception;
use context;
use context_system;
use context_user;
use core\collection;
use totara_core\entity\relationship as relationship_entity;
use totara_core\relationship\relationship;
use totara_core\relationship\relationship_resolver_dto;

/**
 * Class relationship_manager helps process a collection of relationships with the arguments.
 *
 * @package totara_core\relationship
 */
class relationship_collection_manager {

    /**
     * List of relationship ids mapped to their model.
     *
     * @var relationship[]
     */
    private $relationships = [];

    /** @var context|null */
    private $context;

    /**
     * Loads a list of relationships that can be used to get users.
     *
     * @param int[]|relationship[]|collection $relationships
     * @param context|null $context pass optional context as a fall back if relationship cannot determine it's own
     * @throws coding_exception
     */
    public function __construct($relationships, ?context $context = null) {
        if (empty($relationships)) {
            throw new coding_exception('Relationships required.');
        }

        $this->context = $context;

        if ($relationships instanceof collection) {
            // Collection of relationship models
            if (!$relationships->first() instanceof relationship) {
                throw new coding_exception('Relationships required.');
            }
            $this->relationships = $relationships->all(true);
        } else if (reset($relationships) instanceof relationship) {
            // Array of relationship models
            foreach ($relationships as $relationship) {
                $this->relationships[$relationship->id] = $relationship;
            }
        } else {
            // Array of relationship IDs
            $relationships_from_ids = $this->get_relationships_from_ids($relationships);

            if (empty($relationships_from_ids) || count($relationships_from_ids) !== count($relationships)) {
                throw new coding_exception('Invalid Relationship IDs.');
            }

            $this->relationships = $relationships_from_ids;
        }
    }

    /**
     * Provides the list of users for each relationship in the relationship_id list provided.
     *
     * @param array $args Relationship input values to provide to {@see relationship::get_users}
     * @param array|null $relationship_ids (Optional) Resolve only a sub-selection of the specified relationships.
     * @return array|relationship_resolver_dto[]
     * @throws coding_exception
     */
    public function get_users_for_relationships(array $args, array $relationship_ids = null): array {
        if ($relationship_ids === null) {
            $relationship_ids = array_keys($this->relationships);
        }

        // As this class deals with multiple users it tries to determine it's
        // own contexts per relationship unless a context was provided.
        if ($this->context) {
            $context = $this->context;
        } else if ($args['user_id']) {
            $context = context_user::instance($args['user_id'], IGNORE_MISSING);
            if (!$context) {
                // The user might have been deleted
                return [];
            }
        } else {
            $context = context_system::instance();
        }

        $users_per_relationship = [];
        foreach ($relationship_ids as $relationship_id) {
            if (!isset($this->relationships[$relationship_id])) {
                throw new coding_exception('Relationship ID not loaded.');
            }

            $users_per_relationship[$relationship_id] = $this->relationships[$relationship_id]->get_users(
                $args,
                $context
            );
        }

        return $users_per_relationship;
    }

    /**
     * Get relationships from relationship ids.
     *
     * @param array $relationship_ids
     * @return array
     */
    private function get_relationships_from_ids(array $relationship_ids): array {
        return relationship_entity::repository()
            ->where_in('id', $relationship_ids)
            ->with('resolvers')
            ->get()
            ->map_to(relationship::class)
            ->all(true);
    }

}
