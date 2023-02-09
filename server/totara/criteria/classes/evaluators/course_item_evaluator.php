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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_criteria
 */

namespace totara_criteria\evaluators;

use totara_core\advanced_feature;
use totara_criteria\criterion;

class course_item_evaluator extends item_evaluator {

    /**
     * Evaluate criteria completion / satisfaction for users in the source
     * @param criterion $criterion
     * @param int $now
     */
    protected function update_criterion_completion(criterion $criterion, int $now) {
        global $DB;

        $criterion_id = $criterion->get_id();

        $transcation = $DB->start_delegated_transaction();

        // Not linking with the users_source here as we've already ensured that there is a record for applicable users in the parent

        // MySQL doesn't allow using the same table in the update and the where - therefore had to settle for multiple queries
        // First delete all the records to change, then bulk insert new records

        $meet_select_sql = "
             SELECT 
                    tcir.id, 
                    tcir.user_id,
                    tcir.criterion_item_id,
                    '1' as criterion_met,
                    '{$now}' as timeevaluated,
                    cc.timecompleted as timeachieved
               FROM {totara_criteria_item_record} tcir
               JOIN {totara_criteria_item} tci
                 ON tci.id = tcir.criterion_item_id
               JOIN {course_completions} cc
                 ON cc.course = tci.item_id
                AND cc.userid = tcir.user_id
                AND cc.timecompleted > 0
              WHERE tci.criterion_id = :criterionid
                AND tci.item_type = :itemtype
                AND tcir.criterion_met = :currentmet
              ORDER BY tcir.id
        ";

        $select_params = [
            'criterionid' => $criterion_id,
            'itemtype' => 'course',
            'currentmet' => 0,
        ];

        // To not create memory issues make sure we batch this as we don't know how many records there are
        $offset = 0;
        $limit = 10000;
        $has_items = true;
        $ids_to_delete = [];
        while ($has_items) {
            $item_records = $DB->get_recordset_sql($meet_select_sql, $select_params, $offset, $limit);
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

        // In case you have not enabled assignments we want to retain the existing behaviour:
        // If you ever got a value by completing a course then removing a completion record does not undo this
        // so we skip setting criteria met to 0.
        // Technically this is not necessary as in the overall competency aggregation
        // we ignore users who already have a previous achievement but it saves some queries
        if (advanced_feature::is_enabled('competency_assignment')) {
            $not_meet_select_sql = "
                 SELECT tcir.id
                   FROM {totara_criteria_item_record} tcir
                   JOIN {totara_criteria_item} tci
                     ON tci.id = tcir.criterion_item_id
              LEFT JOIN {course_completions} cc
                     ON cc.course = tci.item_id
                    AND cc.userid = tcir.user_id
                    AND cc.timecompleted > 0
                  WHERE tci.criterion_id = :criterionid
                    AND tci.item_type = :itemtype
                    AND tcir.criterion_met = :currentmet
                    AND cc.id IS NULL
            ";

            $update_sql =
                "UPDATE {totara_criteria_item_record}
               SET criterion_met = :newmet, 
                   timeevaluated = :now,
                   timeachieved = NULL
             WHERE id ";

            // Then users that must not meet - we need to handle users that doesn't yet have a completion record -
            // thus the need for the left join on course_completions
            $select_params['currentmet'] = 1;
            $must_not_meet_record_ids = $DB->get_fieldset_sql($not_meet_select_sql, $select_params);
            if (!empty($must_not_meet_record_ids)) {
                $update_params['newmet'] = 0;
                $update_params['now'] = $now;

                $ids_chunked = array_chunk($must_not_meet_record_ids, $DB->get_max_in_params());
                foreach ($ids_chunked as $ids_chunk) {
                    [$ids_sql, $ids_params] = $DB->get_in_or_equal($ids_chunk, SQL_PARAMS_NAMED);
                    $DB->execute($update_sql . $ids_sql, array_merge($update_params, $ids_params));
                }
            }
        }
    }

}
