<?php
/**
 * This file is part of Totara Core
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package contentmarketplace_linkedin
 */
namespace contentmarketplace_linkedin\model;

use contentmarketplace_linkedin\entity\classification as classification_entity;
use core\collection;
use core\orm\entity\model;

/**
 * Model class for learning object classification.
 *
 * @property-read int $id
 * @property-read string $urn
 * @property-read string $locale_language
 * @property-read string $locale_country
 * @property-read string $name
 * @property-read string $type
 *
 * Computed properties:
 * @property-read classification[]|collection $parents
 * @property-read classification[]|collection $children
 */
class classification extends model {

    /**
     * @var classification_entity
     */
    protected $entity;

    /**
     * @var string[]
     */
    protected $entity_attribute_whitelist = [
        'id',
        'urn',
        'locale_language',
        'locale_country',
        'name',
        'type'
    ];

    /**
     * @var string[]
     */
    protected $model_accessor_whitelist = [
        'parents',
        'children',
    ];

    /**
     * @return string
     */
    protected static function get_entity_class(): string {
        return classification_entity::class;
    }

    /**
     * @return classification[]|collection
     */
    public function get_parents(): collection {
        return $this->entity->parents->map_to(self::class);
    }

    /**
     * Get the children of this classification.
     *
     * @return classification[]|collection
     */
    public function get_children(): collection {
        return $this->entity->children->map_to(self::class);
    }

    /**
     * Refreshing the entity, with parameter to determine whether should
     * the refresh also reload the relation or not.
     *
     * @param bool $with_relationships
     * @return $this
     */
    public function refresh(bool $with_relationships = false): classification {
        $this->entity->refresh();

        if ($with_relationships) {
            if ($this->entity->relation_loaded("children")) {
                $this->entity->load_relation("children");
            }

            if ($this->entity->relation_loaded("parents")) {
                $this->entity->load_relation("parents");
            }
        }

        return $this;
    }
}