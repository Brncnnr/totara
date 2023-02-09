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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package pathway_criteria_group
 */

namespace pathway_criteria_group;

use moodle_recordset;
use totara_competency\entity\pathway_achievement;
use totara_competency\pathway;
use totara_competency\pathway_evaluator_user_source;
use totara_core\advanced_feature;

class criteria_group_evaluator_user_source extends pathway_evaluator_user_source {

    /**
     * Get all users we should consider for reaggregation
     *
     * @param pathway $pathway
     * @return moodle_recordset
     */
    public function get_users_to_reaggregate(pathway $pathway): moodle_recordset {
        if (advanced_feature::is_enabled('competency_assignment')) {
            return parent::get_users_to_reaggregate($pathway);
        }

        // For learn-only we have a slight different behaviour:
        // We ignore every users who already got a value in that pathway as we do
        // not remove any existing value

        global $DB;

        $temp_alias = 'tmp';
        $temp_table_name = $this->temp_user_table->get_table_name();
        $userid_column = $this->temp_user_table->get_user_id_column();
        [$temp_wh, $temp_wh_params] = $this->temp_user_table->get_filter_sql_with_params($temp_alias, true, 1);

        // Only consider users who don't have an achievement in this pathway yet
        $sql = "
            SELECT DISTINCT {$temp_alias}.{$userid_column} as user_id, 
                    tcpa.id as achievement_id,
                    tcpa.scale_value_id
            FROM {{$temp_table_name}} {$temp_alias}
            LEFT JOIN {totara_competency_pathway_achievement} tcpa
                 ON tcpa.pathway_id = :pathwayid
                    AND tcpa.user_id = {$temp_alias}.{$userid_column}
                    AND tcpa.status = :currentstatus
            WHERE tcpa.scale_value_id IS NULL
        ";

        $params = [
            'pathwayid' => $pathway->get_id(),
            'currentstatus' => pathway_achievement::STATUS_CURRENT,
        ];

        if (!empty($temp_wh)) {
            $sql .= " AND {$temp_wh}";
            $params = array_merge($params, $temp_wh_params);
        }

        return $DB->get_recordset_sql($sql, $params);
    }

}
