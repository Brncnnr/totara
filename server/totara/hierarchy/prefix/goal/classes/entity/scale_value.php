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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package hierarchy_goal
 */

namespace hierarchy_goal\entity;

use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;

/**
 * Represents a goal scale in the repository.
 *
 * @property-read int $id record id
 * @property string $name scale value name
 * @property string $idnumber alternative scale identfier
 * @property string $description scale description
 * @property int $scaleid parent scale
 * @property int $numericscore scale value
 * @property int $sortorder
 * @property int $timemodified time modified
 * @property int $usermodified time modified
 * @property int $proficient
 *
 * @property-read scale $scale The scale this value belongs to
 */
class scale_value extends entity {
    public const TABLE = 'goal_scale_values';

    /**
     * Establishes the relationship with scale entities.
     *
     * @return belongs_to the relationship.
     */
    public function scale(): belongs_to {
        return $this->belongs_to(scale::class, 'scaleid');
    }
}