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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\task;

use core\task\scheduled_task;
use totara_competency\aggregation_helper;
use totara_competency\aggregation_task;
use totara_competency\aggregation_users_table;

/**
 * Aggregates competency achievements for all users actively assigned to any competency in the system.
 *
 * Depending on the amount of data this task could run for a while. As all actions leading to
 * changes in achievement of a competency are picked up on the go via the competency_aggregation_queue_task
 * this task does not need to run regularly or only on demand.
 */
class competency_aggregation_all extends scheduled_task {

    /**
     * @var int
     */
    private $aggregation_time = null;

    /**
     * Set the force flag
     * @depreacted since Totara 14
     */
    public function force_run() {
        debugging('The method \totara_competency\task\competency_aggregation_all::force_run() is deprecated and has no effect anymore.', DEBUG_DEVELOPER);
    }

    /**
     * @param int $timestamp
     */
    public function set_aggregation_time(int $timestamp) {
        $this->aggregation_time = $timestamp;
    }

    public function get_name() {
        return get_string('aggregate_all_competencies_task', 'totara_competency');
    }

    public function execute() {
        $table = new aggregation_users_table('totara_competency_aggregation_temp', true);

        $this->fill_temp_table($table);

        $task = new aggregation_task($table, true);
        $task->execute($this->aggregation_time);

        // Explicitly trigger dropping the temporary table
        $table->drop_temp_table();
    }

    private function fill_temp_table(aggregation_users_table $table) {
        global $DB;

        $assignment_users_table = aggregation_helper::get_assigned_users_sql_table();

        $has_changed_column_sql = '';
        $has_changed_column_value = '';
        if ($table->get_has_changed_column()) {
            // We need to join with default queue to ensure that we pick up any has_changed flag that is already set
            $queue_table = new aggregation_users_table();

            $has_changed_column_sql = ", {$table->get_has_changed_column()})";
            $has_changed_join =
                "LEFT JOIN {{$queue_table->get_table_name()}} as qt 
                        ON tcau.user_id = qt.{$queue_table->get_user_id_column()}
                       AND tcau.competency_id = qt.{$queue_table->get_competency_id_column()}";
            if ($table->get_process_key_column()) {
                $has_changed_join .= " AND {$table->get_process_key_column()} IS NULL";
            }

            $has_changed_column_value = ", COALESCE(MAX(qt.{$queue_table->get_has_changed_column()}), 0)";
        }

        $sql = "
            INSERT INTO {{$table->get_table_name()}}
            (user_id, competency_id {$has_changed_column_sql}
             SELECT tcau.user_id, tcau.competency_id {$has_changed_column_value}
              FROM {$assignment_users_table} tcau
              JOIN {totara_competency_pathway} pw
                ON tcau.competency_id = pw.competency_id
              {$has_changed_join}  
          GROUP BY tcau.user_id, tcau.competency_id       
        ";

        $DB->execute($sql, []);
    }

}
