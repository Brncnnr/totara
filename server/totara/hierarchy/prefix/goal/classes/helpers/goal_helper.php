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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package hierarchy_goal
 */

namespace hierarchy_goal\helpers;

use hierarchy_goal\entity\goal_item_history;
use hierarchy_goal\entity\goal_item_target_date_history;
use hierarchy_goal\entity\scale_value;

/**
 * Helper class for goals.
 */
class goal_helper {

    /**
     * Get the scale value of a goal assignment for a specific moment in time.
     * Returns null if no value can be found for the given parameters.
     *
     * @param int $goal_scope
     * @param int $goal_assignment_id goal_personal.id for personal goals or goal_record.id for company goals
     * @param int $timestamp
     * @return scale_value|null
     */
    public static function get_goal_scale_value_at_timestamp(int $goal_scope, int $goal_assignment_id, int $timestamp): ?scale_value {
        /** @var goal_item_history $goal_item_history */
        $goal_item_history = goal_item_history::repository()
            ->with('scale_value')
            ->where('scope', $goal_scope)
            ->where('itemid', $goal_assignment_id)
            ->where('timemodified', '<=', $timestamp)
            ->order_by('timemodified', 'desc')
            ->order_by('id', 'desc')
            ->first();

        return $goal_item_history->scale_value ?? null;
    }

    /**
     * Get the target date of a goal for a specific moment in time.
     * Returns null if no value can be found for the given parameters.
     *
     * @param int $goal_scope
     * @param int $goal_id goal_personal.id for personal goals or goal.id for company goals
     * @param int $timestamp
     * @return int|null
     */
    public static function get_goal_target_date_at_timestamp(int $goal_scope, int $goal_id, int $timestamp): ?int {
        /** @var goal_item_target_date_history $goal_item_target_date_history */
        $goal_item_target_date_history = goal_item_target_date_history::repository()
            ->where('scope', $goal_scope)
            ->where('itemid', $goal_id)
            ->where('timemodified', '<=', $timestamp)
            ->order_by('timemodified', 'desc')
            ->order_by('id', 'desc')
            ->first();

        return $goal_item_target_date_history->targetdate ?? null;
    }
}
