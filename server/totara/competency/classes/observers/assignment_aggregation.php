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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package pathway_criteria_group
 */

namespace totara_competency\observers;


use totara_competency\aggregation_users_table;
use totara_competency\entity\competency_achievement;
use totara_competency\entity\pathway_achievement;
use totara_competency\event\assignment_user_archived;
use totara_competency\event\assignment_user_assigned;
use totara_competency\event\assignment_user_assigned_bulk;
use totara_competency\event\assignment_user_unassigned;

class assignment_aggregation {

    public static function user_assigned(assignment_user_assigned $event) {
        // Simply mark the user for aggregation
        (new aggregation_users_table())->queue_for_aggregation($event->relateduserid, $event->get_competency_id());
    }

    public static function user_assigned_bulk(assignment_user_assigned_bulk $event) {
        $user_ids = $event->get_user_ids();
        if (empty($user_ids)) {
            return;
        }

        $user_ids_to_queue = [];
        foreach ($user_ids as $user_id) {
            $user_ids_to_queue[] = [
                'user_id' => $user_id,
                'competency_id' => $event->get_competency_id()
            ];
        }

        // Simply mark the users for aggregation
        (new aggregation_users_table())->queue_multiple_for_aggregation($user_ids_to_queue);
    }

    public static function user_unassigned(assignment_user_unassigned $event) {
        // Nothing needed for re-aggregation - parents will be picked up by childcompetency criteria
        static::archive_user_achievements($event->relateduserid, $event->get_competency_id());
    }

    public static function user_archived(assignment_user_archived $event) {
        // Nothing needed for re-aggregation - parents will be picked up by childcompetency criteria
        static::archive_user_achievements($event->relateduserid, $event->get_competency_id());
    }

    /**
     * Archive the competency_achievement as well as the pathway_achievements of this user
     * @param int $user_id
     * @param int $competency_id
     */
    private static function archive_user_achievements(int $user_id, int $competency_id) {
        global $DB;

        $now = time();

        // Pathway achievement
        $sql =
            "UPDATE {totara_competency_pathway_achievement}
                SET status = :archived,
                    last_aggregated = :now
              WHERE status = :currentstatus
                AND user_id = :userid
                AND pathway_id IN ( 
                    SELECT id
                      FROM {totara_competency_pathway}
                     WHERE competency_id = :competencyid)";

        $params = [
            'archived' => pathway_achievement::STATUS_ARCHIVED,
            'now' => $now,
            'currentstatus' => pathway_achievement::STATUS_CURRENT,
            'userid' => $user_id,
            'competencyid' => $competency_id,
        ];

        $DB->execute($sql, $params);

        // Competency achievement
        $sql =
            "UPDATE {totara_competency_achievement}
                SET status = :archived,
                    time_status = :timestatus
              WHERE competency_id = :competencyid
                AND status = :currentstatus
                AND user_id = :userid";
        $params = [
            'competencyid' => $competency_id,
            'archived' => competency_achievement::ARCHIVED_ASSIGNMENT,
            'currentstatus' => competency_achievement::ACTIVE_ASSIGNMENT,
            'timestatus' => $now,
            'userid' => $user_id
        ];

        $DB->execute($sql, $params);
    }

}
