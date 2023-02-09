<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @package totara_competency
 */

namespace totara_competency;

use stdClass;
use totara_competency\entity\assignment;
use totara_competency\entity\competency_achievement;
use totara_competency\task\competency_aggregation_all;
use xmldb_table;

/**
 * Helper method for migrating competency achievements from Learn to Perform
 */
class migration_helper {

    private const MIGRATION_QUEUED = 0;
    private const MIGRATION_STARTED = 2;
    private const MIGRATION_FINISHED = 1;

    private const CONFIG_PLUGIN = 'totara_competency';
    private const CONFIG_SETTING = 'achievements_migrated';

    /**
     * Returns migration status
     *
     * @return int|null
     */
    public static function get_migration_status(): ?int {
        $status = get_config(self::CONFIG_PLUGIN, self::CONFIG_SETTING);
        // If the value is not set, get_config returns false.
        if ($status !== false) {
            return $status;
        }
        return null;
    }

    /**
     * Check whether the migration has been queued
     *
     * @return bool
     */
    public static function is_migration_queued(): bool {
        return self::get_migration_status() === self::MIGRATION_QUEUED;
    }

    /**
     * Check whether the migration has started and is running
     *
     * @return bool
     */
    public static function is_migration_started(): bool {
        return self::get_migration_status() === self::MIGRATION_STARTED;
    }

    /**
     * Either the migration has never been triggered or it finished
     *
     * @return bool
     */
    public static function is_migration_finished(): bool {
        $status = self::get_migration_status();
        return is_null($status) || $status === self::MIGRATION_FINISHED;
    }

    /**
     * Queues the migration to be picked up by this class
     */
    public static function queue_migration(): void {
        global $DB;

        // Only queue if there's something to migrate
        if (!self::required_tables_exist()  || $DB->count_records('comp_record_history') === 0) {
            return;
        }

        set_config(self::CONFIG_SETTING, self::MIGRATION_QUEUED, self::CONFIG_PLUGIN);
    }

    /**
     * Marks the migration as started, only usable by this class.
     * During migration the competency aggregation is not running to make sure there's no interference
     */
    private static function start_migration(): void {
        set_config(self::CONFIG_SETTING, self::MIGRATION_STARTED, self::CONFIG_PLUGIN);
    }

    /**
     * Marks the migration as finished
     */
    private static function finish_migration(): void {
        set_config(self::CONFIG_SETTING, self::MIGRATION_FINISHED, self::CONFIG_PLUGIN);
    }

    /**
     * Migrate all existing competency achievements from the comp_record
     * and comp_record_history tables in to the new totara_competency_achievement tables
     *
     * @param int $batch_size determines the batch size of records to process in one batch
     */
    public static function migrate_achievements(int $batch_size = 10000): void {
        global $DB;

        if (!self::is_migration_queued()) {
            return;
        }

        // Wrapping this in a transaction to avoid a half-migrated state
        $DB->transaction(function () use ($batch_size) {
            global $DB;

            self::start_migration();

            // We take from the history table as this actually includes current records as well.
            // We determine the current record from the following rules:
            // 1. The comp_history_record has a corresponding comp_record
            // 2. The timemodified on the comp_history_record is most recent.
            // There is a risk that you have 2 history records with the same timemodified, so we can choose the one that is actually linked
            // to a current record.
            // There is also a very narrow risk that somehow a history record has a later date than the current comp_record, if that
            // may have happened, we'll take the one linked to the comp_record since that's been the one the user considers current
            // up til now.
            // If there's no comp_record, then we'll just be making a history record the most recent timestamp the current one.
            $query_sql = "
                SELECT
                    crh.id,
                    crh.competencyid,
                    crh.userid,
                    crh.proficiency,
                    crh.timemodified,
                    crh.timeproficient,
                    COALESCE (cr.id, 0) AS comp_record_id
                FROM {comp_record_history} crh
                LEFT JOIN {comp_record} cr
                    ON crh.competencyid = cr.competencyid
                        AND crh.userid = cr.userid
                        AND (crh.proficiency = cr.proficiency OR crh.proficiency IS NULL AND cr.proficiency IS NULL)
                ORDER BY 
                    crh.competencyid,
                    crh.userid,
                    comp_record_id DESC,
                    crh.timemodified DESC,
                    crh.id
            ";

            $now = time();
            $all_scale_values = $DB->get_records('comp_scale_values');

            // Each combination of user/competency should have one active achievement record.
            // We've ordered by competency and user so that records for each combination are grouped together.
            $current_competency = null;
            $current_user = null;
            $comp_assignment = [];

            // This is true when we are at the first record for a given user and competency.
            $first = true;

            $offset = 0;
            $limit = $batch_size;
            $has_items = true;
            while ($has_items) {
                $histories = $DB->get_recordset_sql($query_sql, [], $offset, $limit);
                $has_items = $histories->valid();
                $offset += $limit;

                $comp_achievements = [];

                foreach ($histories as $history) {
                    if ($current_competency != $history->competencyid || $current_user != $history->userid) {
                        // This means we are now up to the records of another user/competency combination.
                        $current_competency = $history->competencyid;
                        $current_user = $history->userid;
                        $first = true;
                    }

                    // We need to create an assignment record, but only for the first record for a given user and competency
                    if ($first) {
                        $comp_assignment = [
                            'type' => 'legacy',
                            'user_group_type' => 'user',
                            'competency_id' => $history->competencyid,
                            'user_group_id' => $history->userid,
                            'optional' => 0,
                            'status' => assignment::STATUS_ARCHIVED,
                            'created_by' => 0,
                            'created_at' => $history->timemodified,
                            'updated_at' => $history->timemodified,
                            'archived_at' => $now,
                        ];

                        // We aren't going to batch assignments, since we'd need to get the assignment id anyway, to insert into achievements...
                        $comp_assignment['id'] = $DB->insert_record('totara_competency_assignments', $comp_assignment);
                    }

                    $comp_achievement = new stdClass();
                    $comp_achievement->competency_id = $history->competencyid;
                    $comp_achievement->user_id = $history->userid;
                    $comp_achievement->assignment_id = $comp_assignment['id'] ?? 0; // This should not be 0
                    $comp_achievement->scale_value_id = $history->proficiency;
                    $comp_achievement->proficient = $all_scale_values[$history->proficiency]->proficient ?? 0;

                    if ($first) {
                        // Represents an achievement from an archived assignment.
                        $comp_achievement->status = competency_achievement::ACTIVE_ASSIGNMENT;
                        // This makes sure we don't try to add another current record.
                        $first = false;
                    } else {
                        // Represents an achievement that was superseded by another.
                        $comp_achievement->status = competency_achievement::SUPERSEDED;
                    }

                    $comp_achievement->time_created = $history->timemodified;
                    $comp_achievement->time_proficient = $history->timeproficient;
                    $comp_achievement->time_status = $history->timemodified;
                    $comp_achievement->time_scale_value = $history->timemodified;
                    $comp_achievement->last_aggregated = $now;

                    $comp_achievements[] = $comp_achievement;
                }

                if (!empty($comp_achievements)) {
                    $DB->insert_records_via_batch('totara_competency_achievement', $comp_achievements);
                }

                $histories->close();
            }

            // Now make sure we have records for existing learning plan assignments
            $sql = "
                INSERT INTO {dp_plan_competency_value}
                    (
                        competency_id, 
                        user_id, 
                        scale_value_id, 
                        date_assigned, 
                        manual,
                        positionid,
                        organisationid,
                        assessorid,
                        assessorname,
                        assessmenttype
                    )
                SELECT 
                    competencyid, 
                    userid, 
                    proficiency, 
                    timecreated, 
                    manual,
                    positionid,
                    organisationid,
                    assessorid,
                    assessorname,
                    assessmenttype
                FROM {comp_record} cr WHERE EXISTS (
                    SELECT p.id
                    FROM {dp_plan_competency_assign} pca
                    JOIN {dp_plan} p ON p.id = pca.planid
                    WHERE pca.competencyid = cr.competencyid AND p.userid = cr.userid
                ) AND NOT EXISTS (
                    SELECT * FROM {dp_plan_competency_value} dpcv 
                    WHERE dpcv.competency_id = cr.competencyid AND dpcv.user_id = cr.userid
                )
            ";
            $DB->execute($sql);
        });

        // Run the full aggregation once to make sure all achievements are up-to-date
        $task = new competency_aggregation_all();
        $task->execute();

        // Set the configuration value to prevent running the migration again
        self::finish_migration();
    }

    /**
     * Check if required comp_record tables exist
     *
     * @return bool
     */
    private static function required_tables_exist(): bool {
        global $DB;
        $dbman = $DB->get_manager();
        $comp_record_table = new xmldb_table('comp_record');
        $comp_record_history_table = new xmldb_table('comp_record_history');

        return $dbman->table_exists($comp_record_table) && $dbman->table_exists($comp_record_history_table);
    }

}
