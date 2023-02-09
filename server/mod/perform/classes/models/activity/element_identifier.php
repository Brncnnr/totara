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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\models\activity;

use coding_exception;
use core\orm\collection;
use core\orm\entity\model;
use core_text;
use mod_perform\entity\activity\element_identifier as element_identifier_entity;

/**
 * Class element_identifier
 *
 * Identifier tags an element for reporting purposes.
 *
 * @property-read int $id ID
 * @property string $identifier used to match elements that share the same identifier
 * @property-read collection|element[] $elements
 *
 * @package mod_perform\models\element_identifier
 */
class element_identifier extends model {

    public const MAX_LENGTH = 255;

    protected $entity_attribute_whitelist = [
        'id',
        'identifier',
    ];

    protected $model_accessor_whitelist = [
        'elements',
    ];

    /**
     * @var element_identifier_entity
     */
    protected $entity;

    /**
     * @inheritDoc
     */
    protected static function get_entity_class(): string {
        return element_identifier_entity::class;
    }

    /**
     * Create a new section element, by joining the section and element
     *
     * @param string $identifier
     * @return element_identifier
     * @throws coding_exception
     */
    public static function create(string $identifier): self {
        if (empty($identifier)) {
            throw new coding_exception('Cannot create empty identifier');
        }

        if (core_text::strlen($identifier) > self::MAX_LENGTH) {
            throw new coding_exception('Identifier string exceeds maximum length');
        }

        $entity = new element_identifier_entity();
        $entity->identifier = $identifier;
        $entity->save();

        return static::load_by_entity($entity);
    }

    /**
     * Retrieves an existing element_identifier model or creates a new one if required.
     *
     * If an empty identifier is passed in null is returned since an identifier is not required.
     *
     * @param string $identifier
     * @return model|null
     */
    public static function fetch_or_create_identifier(string $identifier): ?element_identifier {
        if ($identifier == '') {
            return null;
        }

        // Try loading first.
        $identifier_entity = element_identifier_entity::repository()
            ->filter_by_identifier($identifier)
            ->get()
            ->first();

        if ($identifier_entity) {
            return self::load_by_entity($identifier_entity);
        }

        // If not found create it.
        return self::create($identifier);
    }

    /**
     * Delete the element identifier
     */
    public function delete(): void {
        $this->entity->delete();
    }

    /**
     * Get the elements for this element identifier.
     *
     * @return collection|element[]
     */
    public function get_elements(): collection {
        return $this->entity->elements->map_to(element::class);
    }

}
