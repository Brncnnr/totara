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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\entity\activity;

use core\orm\collection;
use core\orm\entity\entity;
use core\orm\entity\relations\has_many;

/**
 * Element identifier entity
 *
 * Properties:
 * @property-read int $id ID
 * @property string $identifier used to match elements that share the same identifier
 * @property-read collection|element[] $elements
 *
 * @method static element_identifier_repository repository()
 *
 * @package mod_perform\entity
 */
class element_identifier extends entity {
    public const TABLE = 'perform_element_identifier';

    /**
     * An element identifier owns a collection of elements.
     *
     * @return has_many
     */
    public function elements(): has_many {
        return $this->has_many(element::class, 'identifier_id');
    }
}