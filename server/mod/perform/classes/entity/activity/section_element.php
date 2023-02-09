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

use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;

/**
 * Section element entity
 *
 * Properties:
 * @property-read int $id ID
 * @property int $section_id ID of the section that this element appears in
 * @property int $element_id ID of the element that appears in the section
 * @property int $sort_order the position within the section where the element should appear
 *
 * Relationships:
 * @property-read section $section
 * @property-read element $element
 *
 * @method static section_element_repository repository()
 *
 * @package mod_perform\entity
 */
class section_element extends entity {
    public const TABLE = 'perform_section_element';

    /**
     * Get the section
     *
     * @return belongs_to
     */
    public function section(): belongs_to {
        return $this->belongs_to(section::class, 'section_id');
    }

    /**
     * get the element
     *
     * @return belongs_to
     */
    public function element(): belongs_to {
        return $this->belongs_to(element::class, 'element_id');
    }
}