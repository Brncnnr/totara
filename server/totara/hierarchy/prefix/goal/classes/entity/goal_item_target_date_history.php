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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package hierarchy_goal
 */

namespace hierarchy_goal\entity;

use core\orm\entity\entity;

/**
 * Represents a goal_item_target_date_history record in the repository.
 *
 * @property-read int $id record id
 * @property int $scope personal or company goal
 * @property int $itemid personal or company goal id
 * @property int|null $targetdate associated target date timestamp
 * @property int $timemodified
 * @property int $usermodified
 *
 * @property-read scale_value $scale_value goal status value
 */
class goal_item_target_date_history extends entity {

    public const TABLE = 'goal_item_target_date_history';

    public const UPDATED_TIMESTAMP = 'timemodified';

    public const SET_UPDATED_WHEN_CREATED = true;
}
