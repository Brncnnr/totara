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
 * @package totara_competency
 */

namespace totara_competency;

use moodle_recordset;
use totara_competency\entity\pathway_achievement;

class pathway_evaluator_user_source {

    /** @var aggregation_users_table $temp_user_table  */
    protected $temp_user_table = null;

    /** @var bool $full_user_set */
    protected $full_user_set = false;

    /**
     * @param aggregation_users_table $temp_user_table Source containing the user ids
     * @param bool $full_user_set Does this source contain all users?
     */
    public function __construct(aggregation_users_table $temp_user_table, bool $full_user_set = false) {
        $this->temp_user_table = $temp_user_table;
        $this->full_user_set = $full_user_set;
    }

    /**
     * Return the user_id_source
     *
     * @return aggregation_users_table Source used for obtaining currently assigned users
     */
    public function get_source() {
        return $this->temp_user_table;
    }

    /**
     * Does the source hold a full set of user ids?
     *
     * @return bool
     */
    public function is_full_user_set(): bool {
        return $this->full_user_set;
    }

    /**
     * Archive patwhays achievement records of users no longer assigned
     *
     * @param pathway $pathway
     * @param int $aggregation_time
     */
    public function archive_non_assigned_achievements(pathway $pathway, int $aggregation_time) {
        global $DB;

        if (!$this->full_user_set) {
            return;
        }

        $temp_table_name = $this->temp_user_table->get_table_name();
        $temp_user_id_column = $this->temp_user_table->get_user_id_column();
        [$temp_wh, $temp_wh_params] = $this->temp_user_table->get_filter_sql_with_params('', false, null);
        $temp_wh = !empty($temp_wh) ? " WHERE {$temp_wh}" : '';

        $sql = "
            UPDATE {totara_competency_pathway_achievement}
            SET status = :archived,
                last_aggregated = :aggregationtime
            WHERE pathway_id = :pathwayid
                AND status = :currentstatus
                AND user_id NOT IN ( 
                    SELECT {$temp_user_id_column}
                    FROM {{$temp_table_name}}
                    {$temp_wh}
                )
        ";

        $params = array_merge(
            [
                'archived' => pathway_achievement::STATUS_ARCHIVED,
                'aggregationtime' => $aggregation_time,
                'pathwayid' => $pathway->get_id(),
                'currentstatus' => pathway_achievement::STATUS_CURRENT,
            ],
            $temp_wh_params
        );

        $DB->execute($sql, $params);
    }

    /**
     * Mark users who don't yet have a pathway_achievement record as having changed
     *
     * @param pathway $pathway
     */
    public function mark_newly_assigned_users(pathway $pathway) {
        global $DB;

        // No has_changed column - nothing to do
        if (empty($this->temp_user_table->get_has_changed_column())) {
            return;
        }

        $temp_table_name = $this->temp_user_table->get_table_name();
        $user_id_column = $this->temp_user_table->get_user_id_column();
        [$set_haschanged_sql, $set_haschanged_params] = $this->temp_user_table->get_set_has_changed_sql_with_params(1);
        [$temp_wh, $temp_wh_params] = $this->temp_user_table->get_filter_sql_with_params('', false, null);
        if (!empty($temp_wh)) {
            $temp_wh = " AND {$temp_wh}";
        }

        $params = array_merge(
            [
                'pathwayid' => $pathway->get_id(),
                'currentstatus' => pathway_achievement::STATUS_CURRENT,
            ],
            $set_haschanged_params,
            $temp_wh_params
        );

        $sql = "
            UPDATE {{$temp_table_name}}
                SET {$set_haschanged_sql}
            WHERE {$user_id_column} NOT IN (
                SELECT tcpa.user_id
                FROM {totara_competency_pathway_achievement} tcpa
                WHERE tcpa.pathway_id = :pathwayid
                    AND tcpa.status = :currentstatus
            )
            {$temp_wh}
        ";

        $DB->execute($sql, $params);
    }

    /**
     * Mark all users of the competency in the queue as changes
     *
     * @param int $competency_id
     */
    public function mark_all_users_with_competency(int $competency_id) {
        global $DB;

        $previous_competency_id_value = $this->temp_user_table->get_competency_id_value();

        $this->temp_user_table->set_competency_id_value($competency_id);
        $temp_table_name = $this->temp_user_table->get_table_name();
        [$set_haschanged_sql, $set_haschanged_params] = $this->temp_user_table->get_set_has_changed_sql_with_params(1);
        [$temp_wh, $temp_wh_params] = $this->temp_user_table->get_filter_sql_with_params('', false, null);
        if (!empty($temp_wh)) {
            $temp_wh = " AND {$temp_wh}";
        }

        $params = array_merge($set_haschanged_params, $temp_wh_params);

        $sql = "
            UPDATE {{$temp_table_name}}
                SET {$set_haschanged_sql}
            WHERE 1 = 1 {$temp_wh}
        ";

        $DB->execute($sql, $params);

        $this->temp_user_table->set_competency_id_value($previous_competency_id_value);
    }

    /**
     * Mark users who needs to be reaggregated
     *
     * @param pathway $pathway
     */
    public function mark_users_to_reaggregate(pathway $pathway) {
        // This should be overridden where required
    }

    /**
     * Set the operation key to distinguish between different pathways
     *
     * @param $update_operation_value
     */
    public function set_update_operation_value($update_operation_value) {
        $this->temp_user_table->set_update_operation_value($update_operation_value);
    }

    /**
     * Set the competency_id_value to use in filtering
     *
     * @param int $competency_id
     */
    public function set_competency_id_value(int $competency_id) {
        $this->temp_user_table->set_competency_id_value($competency_id);
    }

    /**
     * Reaggregate all users with changed completion values
     *
     * @param pathway $pathway
     * @return moodle_recordset
     */
    public function get_users_to_reaggregate(pathway $pathway): moodle_recordset {
        global $DB;

        $temp_alias = 'tmp';
        $temp_table_name = $this->temp_user_table->get_table_name();
        $userid_column = $this->temp_user_table->get_user_id_column();
        [$temp_wh, $temp_wh_params] = $this->temp_user_table->get_filter_sql_with_params($temp_alias, true, 1);

        $sql = "
            SELECT DISTINCT {$temp_alias}.{$userid_column} as user_id, 
                    tcpa.id as achievement_id,
                    tcpa.scale_value_id
            FROM {{$temp_table_name}} {$temp_alias}
            LEFT JOIN {totara_competency_pathway_achievement} tcpa
                 ON tcpa.pathway_id = :pathwayid
                    AND tcpa.user_id = {$temp_alias}.{$userid_column}
                    AND tcpa.status = :currentstatus
        ";

        $params = [
            'pathwayid' => $pathway->get_id(),
            'currentstatus' => pathway_achievement::STATUS_CURRENT,
        ];

        if (!empty($temp_wh)) {
            $sql .= " WHERE {$temp_wh}";
            $params = array_merge($params, $temp_wh_params);
        }

        return $DB->get_recordset_sql($sql, $params);
    }

}
