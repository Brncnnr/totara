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
 * @package pathway_manual
 */

namespace pathway_manual;

use totara_competency\entity\pathway_achievement;
use totara_competency\pathway;
use totara_competency\pathway_evaluator_user_source;

class manual_evaluator_user_source extends pathway_evaluator_user_source {

    /**
     * Mark users who needs to be reaggregated
     *
     * @param pathway $pathway
     */
    public function mark_users_to_reaggregate(pathway $pathway) {
        global $DB;

        $temp_has_changed_column = $this->temp_user_table->get_has_changed_column();
        if (empty($temp_has_changed_column)) {
            // Not specified - so nothing to do
            return;
        }

        // Re-aggregate when
        //     the user has neither a rating, nor an achievement record
        //     (will result in the user getting an achievement record during aggregation),
        //  OR the user has a rating, but not yet an achievement record
        //  OR the user has a new rating

        $temp_table_name = $this->temp_user_table->get_table_name();
        $temp_user_id_column = $this->temp_user_table->get_user_id_column();
        [$temp_set_sql, $temp_set_params] = $this->temp_user_table->get_set_has_changed_sql_with_params(1);
        [$temp_wh, $temp_wh_params] = $this->temp_user_table->get_filter_sql_with_params('', false, null);
        if (!empty($temp_wh)) {
            $temp_wh = "{$temp_wh} AND ";
        }

        $competency_id = $pathway->get_competency()->id;

        // Using 2 queries for clarity. Might consider joining them in future if it is more performant
        // First query - Mark all users with one or more rating since the last achievement aggregation
        // or who has a rating without an achievement record yet>
        $sql = "
            UPDATE {{$temp_table_name}}
            SET {$temp_set_sql}
            WHERE {$temp_wh}
                {$temp_user_id_column} IN (
                    SELECT pmr.user_id
                    FROM {pathway_manual_rating} pmr
                    LEFT JOIN {totara_competency_pathway_achievement} tcpa
                        ON tcpa.pathway_id = :pathwayid
                           AND tcpa.user_id = pmr.user_id
                           AND tcpa.status = :activestatus
                    WHERE pmr.competency_id = :competencyid
                        AND (tcpa.id IS NULL OR pmr.date_assigned >= tcpa.last_aggregated)
                )
        ";

        $params = array_merge(
            [
                'pathwayid' => $pathway->get_id(),
                'activestatus' => pathway_achievement::STATUS_CURRENT,
                'competencyid' => $competency_id,
            ],
            $temp_set_params,
            $temp_wh_params
        );

        $DB->execute($sql, $params);

        // Second query - user has no rating and no achievement record
        $sql = "
            UPDATE {{$temp_table_name}}
            SET {$temp_set_sql}
            WHERE {$temp_wh}
                 {$temp_user_id_column} NOT IN (
                    SELECT pmr.user_id
                    FROM {pathway_manual_rating} pmr
                    WHERE pmr.competency_id = :competencyid
                )
                AND {$temp_user_id_column} NOT IN (
                    SELECT tcpa.user_id
                    FROM {totara_competency_pathway_achievement} tcpa
                    WHERE tcpa.pathway_id = :pathwayid
                        AND tcpa.status = :activestatus
                )
        ";

        $params = array_merge(
            [
                'pathwayid' => $pathway->get_id(),
                'activestatus' => pathway_achievement::STATUS_CURRENT,
                'competencyid' => $competency_id,
            ],
            $temp_set_params,
            $temp_wh_params
        );

        $DB->execute($sql, $params);
    }

}
