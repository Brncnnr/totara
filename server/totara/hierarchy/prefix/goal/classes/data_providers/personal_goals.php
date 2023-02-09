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

namespace hierarchy_goal\data_providers;

use hierarchy_goal\entity\personal_goal;

/**
 * Handles groups of personal goals.
 */
class personal_goals {
    // Mapping of sort field display names to physical entity _columns_.
    public const SORT_ASSIGNMENT_TYPE = 'assignment_type';
    public const SORT_GOAL_ID = 'goal_id';
    public const SORT_GOAL_NAME = 'goal_name';
    public const SORT_TARGET_DATE = 'target_date';

    public const SORT_FIELDS = [
        self::SORT_ASSIGNMENT_TYPE => 'assigntype',
        self::SORT_GOAL_ID => 'id',
        self::SORT_GOAL_NAME => 'name',
        self::SORT_TARGET_DATE => 'targetdate'
    ];

    /**
     * Creates an instance of the data provider.
     *
     * @return goal_data_provider the dataprovider.
     */
    public static function create(): goal_data_provider {
        return new goal_data_provider(
            personal_goal::class,
            self::SORT_FIELDS,
            'hierarchy_goal\entity\filters\personal_goal_filters::for'
        );
    }
}
