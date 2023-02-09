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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\relationship;

use coding_exception;
use core\collection;
use core\orm\entity\repository;
use totara_core\entity\relationship as relationship_entity;

/**
 * Data provider for relationships.
 *
 * @package totara_core\relationship
 */
class relationship_provider {

    /**
     * @var repository
     */
    private $query;

    /**
     * @var collection|relationship[]
     */
    private $items;

    public function __construct() {
        $this->query = relationship_entity::repository()
            ->with('resolvers')
            ->order_by('sort_order');
    }

    /**
     * Restrict the returned relationships to ones that are compatible with a plugin.
     *
     * @param string $component
     * @param bool $include_universal Whether to include relationships compatible with all plugins or not.
     * @return $this
     */
    public function filter_by_component(string $component, bool $include_universal = false): self {
        $this->query->where('component', $component);

        if ($include_universal) {
            $this->query->or_where_null('component');
        }

        return $this;
    }

    /**
     * Restrict the returned relationships by relationship type.
     *
     * @param int $type
     * @return $this
     */
    public function filter_by_type(int $type): self {
        $accepted_types = [relationship_entity::TYPE_MANUAL, relationship_entity::TYPE_STANDARD];

        if (!in_array($type, $accepted_types, true)) {
            throw new coding_exception('invalid relationship type');
        }
        $this->query->where('type', $type);

        return $this;
    }

    /**
     * Restrict the returned relationships to ones that are compatible with any and all plugins.
     *
     * @return $this
     */
    public function filter_by_universal(): self {
        $this->query->where_null('component');

        return $this;
    }

    /**
     * Get the relationships that are compatible with the specified fieldset.
     *
     * @param string[] $compatible_fields fieldset to check e.g. ['job_assignment_id'] or ['user_id', 'course_id']
     * @return collection|relationship[]
     */
    public function get_compatible_relationships(array $compatible_fields): collection {
        if (!$this->items) {
            $this->query_relationships();
        }

        if (empty($compatible_fields)) {
            throw new coding_exception('Must specify at least one field to filter_by_compatible()');
        }

        return $this->items->filter(static function (relationship $relationship) use ($compatible_fields) {
            return $relationship->is_acceptable_input($compatible_fields);
        });
    }

    /**
     * Get a collection of the relationship objects that has been queried.
     *
     * @return collection|relationship[]
     */
    public function get(): collection {
        if (!$this->items) {
            $this->query_relationships();
        }

        return $this->items;
    }

    /**
     * Query the data from the database.
     *
     * @return self
     */
    private function query_relationships(): self {
        $this->items = $this->query
            ->get()
            ->map_to(relationship::class);

        return $this;
    }
}
