<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @package totara_plan
 */

namespace totara_plan\watcher;

use development_plan;
use dp_competency_component;
use totara_competency\entity\competency_achievement;
use totara_competency\hook\competency_achievement_updated_bulk;

global $CFG;
require_once($CFG->dirroot . '/totara/plan/lib.php');

class competency {

    public static function achievement_updated_bulk(competency_achievement_updated_bulk $hook) {
        global $DB;

        $competency_id = $hook->get_competency_id();

        // TODO: This need update as soon as we introduce criteria per assignment which may result in different achieved values
        [$user_id_sql, $user_id_params] = $DB->get_in_or_equal($hook->get_user_ids(), SQL_PARAMS_NAMED);

        $sql =
            "SELECT DISTINCT pl.id AS plan_id, ach.competency_id, ach.user_id, ach.scale_value_id, 
                    CASE WHEN ach.proficient = 1 THEN :timeproficient ELSE NULL END AS timeproficient 
               FROM {dp_plan} pl 
               JOIN {dp_plan_competency_assign} ca 
                 ON ca.planid = pl.id
               JOIN {totara_competency_achievement} ach
                 ON ach.competency_id = ca.competencyid
                AND ach.user_id = pl.userid
                AND ach.status = :activestatus
          LEFT JOIN {dp_plan_competency_value} cv 
                 ON cv.competency_id = ca.competencyid
                AND cv.user_id = pl.userid
              WHERE pl.userid {$user_id_sql}
                AND cv.competency_id = :competencyid
                AND (cv.id IS NULL OR cv.scale_value_id <> ach.scale_value_id)";
        $params = array_merge($user_id_params,
            [
                'approvedplan' => DP_APPROVAL_APPROVED,
                'competencyid' => $competency_id,
                'activestatus' => competency_achievement::ACTIVE_ASSIGNMENT,
                'timeproficient' => time(),
            ]
        );

        $to_update = $DB->get_records_sql($sql, $params);
        foreach ($to_update as $record) {
            /** @var development_plan $development_plan */
            $development_plan = new development_plan($record->plan_id);
            /** @var dp_competency_component $component */
            $component = $development_plan->get_component('competency');
            $component->set_value($record->competency_id, $record->user_id, $record->scale_value_id,
                (object)['timeproficient' => $record->timeproficient]
            );
        }
    }

}
