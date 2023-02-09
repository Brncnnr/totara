<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @package totara_userstatus
 */

namespace totara_competency\observers;

use hierarchy_competency\event\scale_min_proficient_value_updated;
use totara_competency\aggregation_helper;
use totara_competency\aggregation_users_table;
use totara_competency\entity\configuration_change;

class scale {

    /**
     * React if the minimum proficient value change
     *
     * @param scale_min_proficient_value_updated $event
     */
    public static function min_proficient_value_updated(scale_min_proficient_value_updated $event) {
        $scale_id = $event->objectid;
        $min_prof_id = $event->other['minproficiencyid'];

        configuration_change::min_proficiency_change($scale_id, $min_prof_id);

        self::queue_for_reaggregation($scale_id);
    }

    /**
     * Load and add all users assigned to competencies with the given scale to the queue
     *
     * @param int $scale_id
     */
    private static function queue_for_reaggregation(int $scale_id) {
        global $DB;

        $table = new aggregation_users_table();

        $process_key_cond = $has_changed_insert = $has_changed_select = $has_changed_cond = "";
        if ($table->get_process_key_column()) {
            $process_key_cond = "AND q.{$table->get_process_key_column()} IS NULL";
        }
        // Only if there's a has_changed column we always add 1 into it to make sure the competency will be aggregated
        // even if no other changes on pathways happened.
        if ($table->get_has_changed_column()) {
            $has_changed_insert = ", {$table->get_has_changed_column()}";
            $has_changed_select = ", 1 AS has_changed";
            $has_changed_cond = " AND q.has_changed = 1";
        }

        // For performance reasons we use one query to query and insert new rows
        // We do not insert values which are already in the queue table
        // to avoid rows being processed twice.

        $assignment_users_table = aggregation_helper::get_assigned_users_sql_table();

        $sql = "
            INSERT INTO {{$table->get_table_name()}}
                (
                    {$table->get_user_id_column()}, 
                    {$table->get_competency_id_column()}
                    {$has_changed_insert}
                )    
            SELECT DISTINCT tcau.user_id, tcau.competency_id {$has_changed_select}
            FROM {$assignment_users_table} tcau 
            JOIN {comp} c ON tcau.competency_id = c.id
            JOIN {comp_scale_assignments} csa ON c.frameworkid = csa.frameworkid
            LEFT JOIN {{$table->get_table_name()}} q 
                ON tcau.competency_id = q.{$table->get_competency_id_column()}
                 AND tcau.user_id = q.{$table->get_user_id_column()}
                 {$process_key_cond}
                 {$has_changed_cond}
            WHERE csa.scaleid = :scale_id AND q.id IS NULL
        ";

        $DB->execute($sql, ['scale_id' => $scale_id]);
    }

}