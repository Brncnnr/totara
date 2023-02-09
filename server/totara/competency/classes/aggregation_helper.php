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
 * @package totara_competency
 */

namespace totara_competency;

use totara_core\advanced_feature;

/**
 * Some helper method for legacy aggregation
 */
class aggregation_helper {

    /**
     * Get sql string to load all assigned users.
     * If perform is not enabled it determines who is assigned in the following ways:
     *   1. Users with any value given in a learning plan
     *   2. Users who completed courses linked to competencies
     *   3. Same two ways as above for any child competencies
     *
     * If perform is enabled we use the assignment_users table to determine who is assigned
     *
     * @return string
     */
    public static function get_assigned_users_sql_table(): string {
        global $CFG;

        if (advanced_feature::is_enabled('competency_assignment')) {
            return "{totara_competency_assignment_users} ";
        } else {
            $sql = [];
            if ($CFG->enablecompletion) {
                $sql[] = "
                    (
                        -- load all users with completion records in courses linked to competencies
                        SELECT cc.userid as user_id, coc.competencyid as competency_id
                        FROM {comp_criteria} coc
                        JOIN {course_completions} cc ON cc.course = coc.iteminstance
                        WHERE coc.itemtype = 'coursecompletion'
                            AND cc.timecompleted > 0
                    )
                    UNION
                    (
                        -- PARENT: load all users with completion records in courses linked to competencies
                        SELECT cc.userid as user_id, c.parentid as competency_id
                        FROM {comp_criteria} coc
                        JOIN {course_completions} cc ON cc.course = coc.iteminstance
                        JOIN {comp} c ON coc.competencyid = c.id
                        WHERE coc.itemtype = 'coursecompletion' 
                            AND cc.timecompleted > 0
                            AND c.parentid > 0
                    )
                ";
            }

            // Only consider learning plans if they are enabled
            if (advanced_feature::is_enabled('learningplans')) {
                $sql[] = "
                    (
                        -- load all users with competencies assigned to learning plans
                        SELECT user_id, competency_id
                        FROM {dp_plan_competency_value} 
                    ) 
                    UNION
                    (
                        -- PARENT: load all users with competencies assigned to learning plans
                        SELECT pcv.user_id, c.parentid as competency_id
                        FROM {dp_plan_competency_value} pcv
                        JOIN {comp} c ON pcv.competency_id = c.id
                        WHERE c.parentid > 0
                    )
                ";
            }

            if (empty($sql)) {
                // If neither course completion nor learning plans are enabled
                // we just make sure nothing is returned by the query
                return "
                    (
                        SELECT 0 as user_id, 0 as competency_id
                        FROM {comp} 
                        WHERE 1 != 1
                    )
                ";
            }

            return "(".implode(" UNION ", $sql).")";
        }
    }

}
