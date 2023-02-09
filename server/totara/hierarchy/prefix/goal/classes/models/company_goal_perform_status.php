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

namespace hierarchy_goal\models;

use coding_exception;
use goal;
use hierarchy_goal\entity\company_goal_assignment;
use hierarchy_goal\entity\scale_value;

class company_goal_perform_status extends perform_status {

    /**
     * @inheritDoc
     */
    public static function get_goal_type(): int {
        return goal::SCOPE_COMPANY;
    }

    /**
     * @inheritDoc
     */
    public static function get_goal_assignment_table(): string {
        return company_goal_assignment::TABLE;
    }

    /**
     * @inheritDoc
     */
    public static function get_goal_id_field(): string {
        return 'goal_id';
    }

    /**
     * @inheritDoc
     */
    protected static function is_scale_value_valid(int $scale_value_id, int $goal_id): bool {
        return scale_value::repository()
            ->join('goal_scale_assignments', 'scaleid', 'scaleid')
            ->join('goal', 'goal_scale_assignments.frameworkid', 'frameworkid')
            ->where('goal.id', $goal_id)
            ->where('id', $scale_value_id)
            ->exists();
    }

    /**
     * @inheritDoc
     */
    protected static function get_goal_id_from_assignment_id(int $goal_assignment_id): int {
        /** @var company_goal_assignment $goal_assignment */
        $goal_assignment = company_goal_assignment::repository()->find($goal_assignment_id);
        if (!$goal_assignment) {
            throw new coding_exception("Invalid company goal assignment id {$goal_assignment_id}");
        }
        return $goal_assignment->goalid;
    }
}

