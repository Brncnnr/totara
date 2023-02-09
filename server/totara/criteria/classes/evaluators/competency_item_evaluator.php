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
 * @package totara_criteria
 */

namespace totara_criteria\evaluators;

use totara_competency\entity\competency_achievement;
use totara_core\advanced_feature;
use totara_criteria\criterion;

class competency_item_evaluator extends item_evaluator {

    protected function update_criterion_completion(criterion $criterion, int $now) {
        global $DB;

        /******************************************************************************************
         * NOTE: We do NOT take assignments into consideration!!!
         *       This was re-considered in TL-29309 - decision was to keep it as is
         *       i.e. proficiency via ANY assignment is considered a valid fulfillment of criteria
         ******************************************************************************************/
        $criterion_id = $criterion->get_id();

        $transcation = $DB->start_delegated_transaction();

        // Not linking with the users_source here as we've already ensured that there is a record in the parent function

        // Due to query restrictions we select the items to recreate first, then create new records, and at the end delete the old records

        // Find all item records where the currently indicated 'criterion_met' is wrong
        // Doing it in 2 sets as there can be a huge number of assigned users which may result in very large arrays

        // First users marked as not having met the criteria, but have satisfied all
        $select_sql = "
            SELECT 
                tcir.id, 
                tcir.user_id,
                tcir.criterion_item_id,
                '1' as criterion_met,
                '{$now}' as timeevaluated,
                (
                    SELECT MIN(tca2.time_created) 
                    FROM {totara_competency_achievement} tca2 
                    WHERE tca2.status = :achievementstatus2 
                      AND tca2.proficient = :isproficient2
                      AND tca2.competency_id = tci.item_id 
                      AND tca2.user_id = tcir.user_id
                ) as timeachieved
              FROM {totara_criteria_item_record} tcir
              JOIN {totara_criteria_item} tci
                ON tcir.criterion_item_id = tci.id
             WHERE tci.criterion_id = :criterionid
               AND tci.item_type = :itemtype
               AND tcir.criterion_met = :currentmet
               AND EXISTS (
                  SELECT tca.id
                    FROM {totara_competency_achievement} tca
                   WHERE tca.status = :achievementstatus
                      AND tca.proficient = :isproficient
                      AND tca.competency_id = tci.item_id
                      AND tca.user_id = tcir.user_id
               )
            ORDER BY tcir.id
               ";

        $select_params = [
            'achievementstatus' => competency_achievement::ACTIVE_ASSIGNMENT,
            'achievementstatus2' => competency_achievement::ACTIVE_ASSIGNMENT,
            'isproficient' => 1,
            'isproficient2' => 1,
            'criterionid' => $criterion_id,
            'itemtype' => 'competency',
            'currentmet' => 0,
        ];

        // To not create memory issues make sure we batch this as we don't know how many records there are
        $offset = 0;
        $limit = 10000;
        $has_items = true;
        $ids_to_delete = [];
        while ($has_items) {
            $item_records = $DB->get_recordset_sql($select_sql, $select_params, $offset, $limit);
            $has_items = $item_records->valid();
            $offset += $limit;

            $items_to_create = [];
            foreach ($item_records as $item_record) {
                $ids_to_delete[] = $item_record->id;
                unset($item_record->id);
                $items_to_create[] = $item_record;
            }

            // Then recreate the new records in the most efficient way
            if (!empty($items_to_create)) {
                $DB->insert_records_via_batch('totara_criteria_item_record', $items_to_create);
            }
        }

        // Now delete the existing records at the end otherwise the resultset would change along the way
        if (!empty($ids_to_delete)) {
            $ids_chunked = array_chunk($ids_to_delete, $DB->get_max_in_params());
            foreach ($ids_chunked as $ids_chunk) {
                [$ids_sql, $ids_params] = $DB->get_in_or_equal($ids_chunk, SQL_PARAMS_NAMED);
                $DB->delete_records_select('totara_criteria_item_record', "id {$ids_sql}", $ids_params);
            }
        }

        $transcation->allow_commit();

        // Now users marked as 'criterion_met' but doesn't actually satisfy the criteria
        // (only difference in select_sql is the use of left join to handle cases where the user doesn't have an achievement record anymore)

        // In case you have not enabled assignments we want to retain the existing behaviour:
        // If you ever got a value by becoming proficient in child competencies then any changes
        // in the child competencies proficiency won't undo this,
        // so we skip setting criteria met to 0. This saves us some queries.
        // Technically this is not necessary as in the overall competency aggregation
        // we ignore users who already have a previous achievement but it saves some queries
        if (advanced_feature::is_enabled('competency_assignment')) {
            $select_sql = "
                SELECT tcir.id
                  FROM {totara_criteria_item} tci
                  JOIN {totara_criteria_item_record} tcir
                    ON tcir.criterion_item_id = tci.id
                 WHERE tci.criterion_id = :criterionid
                   AND tci.item_type = :itemtype
                   AND tcir.criterion_met = :currentmet
                      AND NOT EXISTS (
                            SELECT tca.id
                              FROM {totara_competency_achievement} tca
                             WHERE tca.status = :achievementstatus
                                AND tca.proficient = :isproficient
                                AND tca.competency_id = tci.item_id
                                AND tca.user_id = tcir.user_id
                      )
            ";

            $select_params = [
                'achievementstatus' => competency_achievement::ACTIVE_ASSIGNMENT,
                'isproficient' => 1,
                'criterionid' => $criterion_id,
                'itemtype' => 'competency',
                'currentmet' => 1,
            ];

            $notmet = $DB->get_fieldset_sql($select_sql, $select_params);
            if (!empty($notmet)) {
                // Now update item_records with the correct proficient value
                $update_sql =
                    "UPDATE {totara_criteria_item_record}
                       SET criterion_met = :newmet, 
                           timeevaluated = :now,
                           timeachieved = NULL
                     WHERE id ";
                $update_params = ['newmet' => 0, 'now' => $now];

                $ids_chunked = array_chunk($notmet, $DB->get_max_in_params());
                foreach ($ids_chunked as $ids_chunk) {
                    [$ids_sql, $ids_params] = $DB->get_in_or_equal($ids_chunk, SQL_PARAMS_NAMED);
                    $DB->execute($update_sql . $ids_sql, array_merge($update_params, $ids_params));
                }
            }
        }
    }

}
