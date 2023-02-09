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
 * @package totara_criteria
 */

namespace totara_criteria\evaluators;

use totara_competency\aggregation_users_table;

class item_evaluator_user_source {

    /** @var aggregation_users_table $user_source */
    private $temp_user_table = null;

    /** @var bool $full_user_set */
    private $full_user_set;

    /**
     * Return the source type
     *@return string
     */
    public function get_source_type(): string {
        return 'table';
    }

    /**
     * Return the user_id_source
     * @return mixed Source used for obtaining currently assigned users
     */
    public function get_source() {
        return $this->temp_user_table;
    }

    /**
     * Does the source hold a full set of user ids?
     * @return bool
     */
    public function is_full_user_set(): bool {
        return $this->full_user_set;
    }

    /**
     * Constructor.
     * @param aggregation_users_table $temp_user_table Source containing the user ids
     * @param bool $full_user_set Does this source contain all users?
     */
    public function __construct(aggregation_users_table $temp_user_table, bool $full_user_set = false) {
        $this->temp_user_table = $temp_user_table;
        $this->full_user_set = $full_user_set;
    }

    /**
     * Create item records for all users in the users_source who doesn't have a item_record
     * @param int $criterion_id
     * @param int $criterion_met Criterion met value to use when creating new item records
     * @param int|null $timeevaluated
     */
    public function create_item_records(int $criterion_id, int $criterion_met = 0, ?int $timeevaluated = null) {
        global $DB;

        if (is_null($timeevaluated)) {
            $timeevaluated = time();
        }

        $params = [
            'criterionid' => $criterion_id,
        ];

        $temp_table_name = $this->temp_user_table->get_table_name();
        $temp_user_id_column = $this->temp_user_table->get_user_id_column();
        [$temp_wh, $temp_wh_params] = $this->temp_user_table->get_filter_sql_with_params('tmp', false);

        // The user table may contain more than one record for a specific user (e.g. a record for each competency that
        // the user is assigned to. Therefore using DISTINCT
        // DML generated an error if the integer values were passed via params. Thus using it directly in the SELECT

        if ($criterion_met) {
            $timeachieved_insert = ", timeachieved";
            $timeachieved_value = ", {$timeevaluated}";
        } else {
            $timeachieved_insert = $timeachieved_value = '';
        }

        $sql = "INSERT INTO {totara_criteria_item_record}
                (user_id, criterion_item_id, criterion_met, timeevaluated {$timeachieved_insert})
                SELECT DISTINCT tmp." . $temp_user_id_column . ", tci.id, {$criterion_met}, {$timeevaluated} {$timeachieved_value}
                     FROM {" . $temp_table_name . "} tmp
                     JOIN {totara_criteria_item} tci
                       ON tci.criterion_id = :criterionid
                LEFT JOIN {totara_criteria_item_record} tcir
                       ON tcir.criterion_item_id = tci.id
                      AND tcir.user_id = tmp." . $temp_user_id_column . "
                    WHERE tcir.id IS NULL";

        if (!empty($temp_wh)) {
            $sql .= " AND " . $temp_wh;
            $params += $temp_wh_params;
        }

        $DB->execute($sql, $params);
    }

    /**
     * Delete item records for all users not in the users_source if the source contains the full list
     * @param int $criterion_id
     */
    public function delete_item_records(int $criterion_id) {
        global $DB;

        if (!$this->full_user_set) {
            return;
        }

        $temp_table_name = $this->temp_user_table->get_table_name();
        $temp_user_id_column = $this->temp_user_table->get_user_id_column();
        [$temp_wh, $temp_wh_params] = $this->temp_user_table->get_filter_sql_with_params('', false);

        $params = ['criterionid' => $criterion_id];
        $sql = "DELETE FROM {totara_criteria_item_record}
                      WHERE criterion_item_id IN (
                            SELECT tci.id
                              FROM {totara_criteria_item} tci
                             WHERE tci.criterion_id = :criterionid)
                        AND user_id NOT IN (
                            SELECT $temp_user_id_column
                              FROM {" . $temp_table_name . "}";

        if (!empty($temp_wh)) {
            $sql .= " WHERE " . $temp_wh;
            $params += $temp_wh_params;
        }

        $sql .= ")";

        $DB->execute($sql, $params);
    }

    /**
     * Mark users in the users_source whose item_record was updated since the specified time
     * @param int $criterion_id
     * @param int $checkfrom Time to use as start when checking updates of item records
     */
    public function mark_updated_assigned_users(int $criterion_id, int $checkfrom) {
        global $DB;

        $temp_has_changed_column = $this->temp_user_table->get_has_changed_column();
        if (empty($temp_has_changed_column)) {
            // Not specified - so probably not required
            return;
        }

        $temp_table_name = $this->temp_user_table->get_table_name();
        $temp_user_id_column = $this->temp_user_table->get_user_id_column();
        [$temp_set_sql, $temp_set_params] = $this->temp_user_table->get_set_has_changed_sql_with_params(1);
        [$temp_wh, $temp_wh_params] = $this->temp_user_table->get_filter_sql_with_params('', false);
        if (!empty($temp_wh)) {
            $temp_wh .= ' AND ';
        }

        // Include all if the criterion was updated since the last time we evaluated
        $sql = "
            SELECT tcir.user_id
              FROM {totara_criteria} tc
              JOIN {totara_criteria_item} tci
                ON tci.criterion_id = tc.id
              JOIN {totara_criteria_item_record} tcir
                ON tcir.criterion_item_id = tci.id
             WHERE tc.id = :criterionid
               AND (tcir.timeevaluated > :checkfrom
                OR tc.criterion_modified > :checkfrom2)";

        $user_ids = $DB->get_fieldset_sql(
            $sql,
            [
                'criterionid' => $criterion_id,
                'checkfrom' => $checkfrom,
                'checkfrom2' => $checkfrom,
            ]
        );

        if (!empty($user_ids)) {
            [$user_ids_sql, $user_ids_params] = $DB->get_in_or_equal($user_ids, SQL_PARAMS_NAMED);

            // We are not clearing the 'has_changed' flag so that the end
            // result is a union of users that were updated via any criteria
            $sql = "
                UPDATE {{$temp_table_name}}
                   SET {$temp_set_sql}
                 WHERE {$temp_wh}
                       {$temp_user_id_column} {$user_ids_sql}
            ";

            $params = array_merge(
                $user_ids_params,
                $temp_set_params,
                $temp_wh_params
            );

            $DB->execute($sql, $params);
        }
    }

}
