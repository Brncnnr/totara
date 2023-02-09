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
use core\entity\user;
use core\orm\entity\model;
use goal;
use hierarchy_goal\entity\company_goal;
use hierarchy_goal\entity\goal_item_target_date_history as goal_item_target_date_history_entity;
use hierarchy_goal\entity\personal_goal;

global $CFG;
require_once($CFG->dirroot . '/totara/hierarchy/prefix/goal/lib.php');

/**
 * This model represents a single goal target date history entry.
 *
 * @property-read int $id record id
 * @property int $scope personal or company goal
 * @property int $itemid personal or company goal id
 * @property int|null $targetdate associated target date timestamp
 * @property int $timemodified
 * @property int $usermodified
 */
class goal_item_target_date_history extends model {

    protected $entity_attribute_whitelist = [
        'id',
        'scope',
        'itemid',
        'targetdate',
        'timemodified',
        'usermodified',
    ];

    protected $model_accessor_whitelist = [];

    /**
     * Create a new history record for a goal target date.
     *
     * @param int $scope
     * @param int $goal_id
     * @param int|null $target_date
     * @return static
     */
    public static function create(int $scope, int $goal_id, ?int $target_date): self {
        self::validate_goal($scope, $goal_id);

        $target_date_history = new goal_item_target_date_history_entity();
        $target_date_history->scope = $scope;
        $target_date_history->itemid = $goal_id;
        $target_date_history->targetdate = $target_date;
        $target_date_history->usermodified = user::logged_in()->id ?? 0;
        $target_date_history->save();

        return self::load_by_entity($target_date_history);
    }

    protected static function get_entity_class(): string {
        return goal_item_target_date_history_entity::class;
    }

    /**
     * @param int $scope
     * @param int $goal_id
     */
    private static function validate_goal(int $scope, int $goal_id): void {
        switch ($scope) {
            case goal::SCOPE_PERSONAL:
                if (!personal_goal::repository()->find($goal_id)) {
                    throw new coding_exception("Invalid goal item id: {$goal_id}");
                }
                break;
            case goal::SCOPE_COMPANY:
                if (!company_goal::repository()->find($goal_id)) {
                    throw new coding_exception("Invalid goal item id: {$goal_id}");
                }
                break;
            default:
                throw new coding_exception("Invalid goal scope: {$scope}");
        }
    }
}
