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

namespace hierarchy_goal\observers;

use coding_exception;
use core\event\base;
use goal;
use hierarchy_goal\event\goal_created;
use hierarchy_goal\event\goal_updated;
use hierarchy_goal\models\goal_item_target_date_history;
use stdClass;

class company_goal {

    /**
     * @param base|goal_created $event
     */
    public static function add_target_date_history_goal_created(base $event): void {
        // Get the new goal item out of the event
        $snapshot = $event->get_record_snapshot('goal', $event->objectid);
        $target_date = $snapshot->targetdate ?? null;
        goal_item_target_date_history::create(goal::SCOPE_COMPANY, $event->objectid, $target_date);
    }

    /**
     * @param base|goal_updated $event
     */
    public static function add_target_date_history_goal_updated(base $event): void {
        // Get the changed goal item out of the event
        $snapshot = $event->get_record_snapshot('goal', $event->objectid);

        // Only if the target date changed create a history record.
        if (self::has_target_date_changed($event, $snapshot)) {
            goal_item_target_date_history::create(goal::SCOPE_COMPANY, $event->objectid, $snapshot->targetdate);
        }
    }

    /**
     * Check if the target date changed.
     *
     * @param base $event
     * @param stdClass $snapshot already updated goal item
     * @return bool
     */
    private static function has_target_date_changed(base $event, stdClass $snapshot): bool {
        $old_instance = $event->get_data()['other']['old_instance'] ?? null;
        if (!$old_instance) {
            throw new coding_exception('Missing old instance in goal_updated event');
        }

        if (!property_exists($snapshot, 'targetdate')) {
            return false;
        }

        /*
         * Previously even though targetdate was nullable in the goal tables, integer zero seemed to be
         * always used to represent "No target date". However TL-35631 changed the target date to be
         * non nullable and defaulting to 0. So now there is no difference between a null and 0 target date.
         *
         * Of course, custom code might differentiate between the 2 values, but the chance of this happening
         * was assessed to be very small - which is TL-35631 made the change.
         */
        $old_targetdate = is_null($old_instance['targetdate']) ? 0 : (int)$old_instance['targetdate'];
        $new_targetdate = is_null($snapshot->targetdate) ? 0 : (int)$snapshot->targetdate;

        return $old_targetdate !== $new_targetdate;
    }
}
