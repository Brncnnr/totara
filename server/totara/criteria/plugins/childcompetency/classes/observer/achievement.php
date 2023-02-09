<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package criteria_childcompetency
 */

namespace criteria_childcompetency\observer;

use totara_competency\event\assignment_user_archived;
use totara_competency\event\assignment_user_assigned;
use totara_competency\event\assignment_user_unassigned;
use totara_criteria\entity\criterion as criterion_entity;
use totara_criteria\hook\criteria_achievement_changed;

class achievement {

    public static function user_assigned(assignment_user_assigned $event) {
        // If the user is assigned to a child competency and the parent has 'childcompetency' criteria
        // we need to re-aggregate the user's rating on the parent
        static::trigger_parent_criteria_achievement_changed($event->relateduserid, $event->other['competency_id']);
    }

    public static function user_unassigned(assignment_user_unassigned $event) {
        // If the user is unassigned from a child competency and the parent has 'childcompetency' criteria
        // we need to re-aggregate the user's rating on the parent
        static::trigger_parent_criteria_achievement_changed($event->relateduserid, $event->other['competency_id']);
    }

    public static function user_assignment_archived(assignment_user_archived $event) {
        // If the user's assignment on a child competency is archived and the parent has 'childcompetency' criteria
        // we need to re-aggregate the user's rating on the parent
        static::trigger_parent_criteria_achievement_changed($event->relateduserid, $event->other['competency_id']);
    }

    /**
     * Trigger the criteria_achievement_changed event for all childcompetency criteria on the parent competency
     *
     * @param int $user_id
     * @param int $child_competency_id
     */
    private static function trigger_parent_criteria_achievement_changed(int $user_id, int $child_competency_id) {
        $criteria_ids = criterion_entity::repository()
            ->from_item_ids('competency', $child_competency_id)
            ->where('plugin_type', 'childcompetency')
            ->get()
            ->pluck('id');

        if (!empty($criteria_ids)) {
            $hook = new criteria_achievement_changed([$user_id => $criteria_ids]);
            $hook->execute();
        }
    }
}
