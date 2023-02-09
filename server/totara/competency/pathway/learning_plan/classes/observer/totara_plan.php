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
 * @package pathway_learning_plan
 */

namespace pathway_learning_plan\observer;

use totara_competency\aggregation_users_table;
use totara_competency\pathway;
use totara_core\advanced_feature;
use totara_plan\event\competency_value_set;

class totara_plan {

    public static function competency_value_set(competency_value_set $event) {
        global $DB;

        $competency_id = (int)$event->other['competency_id'];
        $user_id = (int)$event->relateduserid;

        // If perform is not enabled we do not need to check any assignments just reaggregate
        if (!advanced_feature::is_enabled('competency_assignment')) {
            (new aggregation_users_table())->queue_for_aggregation($user_id, $competency_id);
            return;
        }

        // Check that we have an active learning_plan pathway and the user is assigned to the competency
        $sql = "
            SELECT 1
              FROM {totara_competency_assignment_users} tcau
              JOIN {totara_competency_pathway} tcp
                ON tcp.competency_id = tcau.competency_id
             WHERE tcau.competency_id = :compid
               AND tcau.user_id = :userid
               AND tcp.path_type = :pathtype
               AND tcp.status = :activestatus
       ";

        $params = [
            'compid' => $competency_id,
            'userid' => $user_id,
            'pathtype' => 'learning_plan',
            'activestatus' => pathway::PATHWAY_STATUS_ACTIVE
        ];

        if ($DB->record_exists_sql($sql, $params)) {
            (new aggregation_users_table())->queue_for_aggregation($user_id, $competency_id);
        };
    }
}
