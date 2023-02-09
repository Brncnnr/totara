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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package hierarchy_goal
 */

namespace hierarchy_goal\entity;

use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;

/**
 * Represents a goal_item_history record in the repository.
 *
 * @property-read int $id record id
 * @property int $scope personal or company goal
 * @property int $itemid personal or company goal id
 * @property int $scalevalueid associated scale value
 * @property int $timemodified
 * @property int $usermodified
 *
 * @property-read scale_value $scale_value goal status value
 */
class goal_item_history extends entity {
    public const TABLE = 'goal_item_history';

    /**
     * Establishes the relationship with scale value entities.
     *
     * @return belongs_to the relationship.
     */
    public function scale_value(): belongs_to {
        return $this->belongs_to(scale_value::class, 'scalevalueid');
    }
}
