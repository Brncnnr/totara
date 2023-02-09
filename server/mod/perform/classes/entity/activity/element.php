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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\entity\activity;

use core\collection;
use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;
use mod_perform\models\activity\element_plugin;
use core\orm\entity\relations\has_many;

/**
 * Element entity
 *
 * Properties:
 *
 * @property-read int $id ID
 * @property int $context_id the context which owns this element, a performance activity or category/tenant
 * @property string $plugin_name name of the element plugin that controls this element
 * @property string $title a user-defined title to identify and describe this element
 * @property int $identifier_id used to match elements that share the same identifier
 * @property string $data configuration data specific to this type of element
 * @property bool $is_required used to check response required or optional
 * @property int|null $parent element parent.
 * @property int|null $sort_order element sort_order in parent parent.
 * @property-read element_identifier $element_identifier
 * @property-read section_element $section_element
 * @property-read collection|element[] $children
 * @property-read element $parent_element
 *
 * @method static element_repository repository()
 *
 * @package mod_perform\entity
 */
class element extends entity {
    public const TABLE = 'perform_element';

    /**
     * Cast is_required to bool type.
     *
     * @return bool
     */
    protected function get_is_required_attribute(): ?bool {
        $value = $this->get_attributes_raw()['is_required'];
        if (is_null($value)) {
            return null;
        } else {
            return (bool) $this->get_attributes_raw()['is_required'];
        }
    }

    /**
     * Get the element_identifier
     *
     * @return belongs_to
     */
    public function element_identifier(): belongs_to {
        return $this->belongs_to(element_identifier::class, 'identifier_id');
    }

    /**
     * An element belongs to a section.
     *
     * @return belongs_to
     */
    public function section_element(): belongs_to {
        return $this->belongs_to(section_element::class, 'id', 'element_id');
    }

    /**
     * Children elements of the element.
     *
     * @return has_many
     */
    public function children(): has_many {
        return $this->has_many(__CLASS__, 'parent')->order_by('sort_order');
    }

    /**
     * Parent element of the element.
     *
     * @return belongs_to
     */
    public function parent_element(): belongs_to {
        return $this->belongs_to(__CLASS__, 'parent');
    }

    /**
     * @inheritDoc
     */
    public function to_array(): array {
        $attributes =  parent::to_array();

        // Required by Redisplay and Aggregation admin which loads data encoded in element.data (not gql directly).
        $attributes['element_plugin'] = [
            'name' => element_plugin::load_by_plugin($this->plugin_name)->get_name(),
        ];

        return $attributes;
    }

}