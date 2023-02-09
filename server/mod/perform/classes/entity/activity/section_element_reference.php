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
 * @author Marco Song <marco.song@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\entity\activity;

use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;

/**
 * Element reference relationship entity.
 *
 * Links a perform element (abstract element), to a particular section element (element in a particular activity/section).
 *
 * Properties:
 * @property-read int $id ID
 * @property int $source_section_element_id ID of the selected section element
 * @property int $referencing_element_id ID of the referencing element
 *
 * Relationships:
 * @property-read section_element $source_section_element
 *
 * @package mod_perform\entity\activity
 *
 */
class section_element_reference extends entity {
    public const TABLE = 'perform_section_element_reference';

    /**
     * @return belongs_to
     */
    public function source_section_element(): belongs_to {
        return $this->belongs_to(section_element::class, 'source_section_element_id');
    }

}
