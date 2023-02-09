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
 * @author  Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package totara_hierarchy
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Create initial records for goal_item_target_history.
 * For each goal, we just take the current target date and generate a history record with the timestamp of the
 * goal creation date. That ensures we have complete history records for every goal. The resulting inaccuracy for
 * goals that had target date changes in the past cannot be avoided because we can't know about it.
 *
 * We batch insert in case there are very many goals. That may be less likely for company goals, but it's more likely for
 * personal goals.
 */
function totara_hierarchy_upgrade_init_goal_target_date_history(): void {
    global $DB;

    // Select personal goals that don't have a history yet.
    $goal_queries[1] = "
        SELECT id, targetdate, timecreated, usermodified
          FROM {goal_personal} pg
         WHERE deleted = 0
           AND NOT EXISTS (
               SELECT id FROM {goal_item_target_date_history} history
                WHERE history.scope = 1
                  AND history.itemid = pg.id
           )
    ";

    // Select company goals that don't have a history yet.
    $goal_queries[2] = "
        SELECT id, targetdate, timecreated, usermodified
          FROM {goal} cg
         WHERE NOT EXISTS (
               SELECT id FROM {goal_item_target_date_history} history
                WHERE history.scope = 2
                  AND history.itemid = cg.id
         )
    ";

    $batch_size = 500;
    foreach ($goal_queries as $goal_scope => $goal_query) {
        $goals = $DB->get_recordset_sql($goal_query);

        $records = [];
        $record_count = 0;
        foreach ($goals as $goal) {
            $records[] = (object)[
                'scope' => $goal_scope,
                'itemid' => $goal->id,
                'targetdate' => $goal->targetdate,
                // Best we can do here is to assume the current targetdate has been there since goal creation.
                'timemodified' => $goal->timecreated,
                'usermodified' => $goal->usermodified,
            ];

            $record_count ++;
            if ($record_count >= $batch_size) {
                $DB->insert_records_via_batch('goal_item_target_date_history', $records);
                $records = [];
                $record_count = 0;
            }
        }
        $DB->insert_records_via_batch('goal_item_target_date_history', $records);
    }
}