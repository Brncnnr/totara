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

use core\orm\collection;
use core\orm\entity\entity;
use core\orm\entity\relations\has_many;

/**
 * Represents a goal scale in the repository.
 *
 * @property-read int $id record id
 * @property string $name scale name
 * @property string $description scale description
 * @property int $timemodified time modified
 * @property int $usermodified time modified
 * @property int $defaultid
 *
 * @property-read collection|scale_value[] $values Values for this scale, sorted from lowest to highest value
 */
class scale extends entity {
    public const TABLE = 'goal_scale';

    /**
     * Values for this scale, sorted from lowest to highest value
     *
     * @return has_many
     */
    public function values(): has_many {
        return $this->has_many(scale_value::class, 'scaleid')
            ->order_by('sortorder', 'desc');
    }
}