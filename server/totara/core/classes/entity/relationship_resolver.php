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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\entity;

use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;

/**
 * Relationship resolver entity.
 *
 * @property-read int $id
 * @property int $relationship_id
 * @property string $class_name
 *
 * @property-read relationship $relationship
 *
 * @package totara_core\entity
 */
class relationship_resolver extends entity {

    public const TABLE = 'totara_core_relationship_resolver';

    /**
     * Relationship this resolver is a part of.
     *
     * @return belongs_to
     */
    public function relationship(): belongs_to {
        return $this->belongs_to(relationship::class, 'relationship_id');
    }

}
