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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

namespace mod_approval\model\assignment\approver_type;

use core\collection;
use totara_core\entity\relationship as relationship_entity;
use totara_core\relationship\relationship as relationship_model;
use totara_core\webapi\resolver\type\relationship as relationship_resolver;

/**
 * Relationship approver type.
 */
class relationship implements approver_type {
    /**
     * Type Identifier.
     *
     * @var Int
     */
    public const TYPE_IDENTIFIER = 1;

    /**
     * List of allowed relationships.
     *
     * @var array
     */
    private const ALLOWED_RELATIONSHIPS = ['manager'];

    /**
     * Loaded relationships.
     *
     * @var collection|null
     */
    private $loaded_relationships;

    /**
     * @inheritDoc
     */
    public function entity(int $identifier) {
        return relationship_model::load_by_id($identifier);
    }

    /**
     * @inheritDoc
     */
    public function entity_name(int $identifier): string {
        return $this->entity($identifier)->name;
    }

    /**
     * @inheritDoc
     */
    public function label(): string {
        return get_string('model_assignment_approver_type_relationship', 'mod_approval');
    }

    /**
     * @inheritDoc
     */
    public function is_valid(int $identifier): bool {
        return relationship_entity::repository()
            ->where_in('idnumber', self::ALLOWED_RELATIONSHIPS)
            ->where('id', $identifier)
            ->exists();
    }

    /**
     * Get relationships.
     *
     * @return collection
     */
    private function get_relationships(): collection {
        if (is_null($this->loaded_relationships)) {
            $this->loaded_relationships = relationship_entity::repository()
                ->where_in('idnumber', self::ALLOWED_RELATIONSHIPS)
                ->order_by('sort_order')
                ->get()
                ->map_to(relationship_model::class);
        }

        return $this->loaded_relationships;
    }

    /**
     * @inheritDoc
     */
    public function options(): ?array {
        return $this->get_relationships()
            ->map(function (relationship_model $relationship) {
                return [
                    'identifier' => $relationship->id,
                    'idnumber' => $relationship->idnumber,
                    'name' => $relationship->name,
                ];
            })
            ->to_array();
    }

    /**
     * @inheritDoc
     */
    public static function get_code(): int {
        return self::TYPE_IDENTIFIER;
    }

    /**
     * @inheritDoc
     */
    public static function get_enum(): string {
        return 'RELATIONSHIP';
    }

    /**
     * @inheritDoc
     */
    public static function resolver_class(): string {
        return relationship_resolver::class;
    }
}
