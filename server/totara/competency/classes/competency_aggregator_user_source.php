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

use core\orm\collection;
use core\orm\query\builder;
use stdClass;
use totara_competency\entity\assignment;
use totara_competency\entity\competency_achievement;
use totara_competency\entity\competency_assignment_user;
use totara_core\advanced_feature;

class competency_aggregator_user_source {

    /** @var aggregation_users_table $temp_user_table  */
    protected $temp_user_table = null;

    /** @var bool $full_user_set */
    protected $full_user_set = false;

    /**
     * Constructor.
     * @param aggregation_users_table $temp_user_table Source containing the user ids
     * @param bool $full_user_set Does this source contain all users?
     */
    public function __construct(aggregation_users_table $temp_user_table, bool $full_user_set = false) {
        $this->temp_user_table = $temp_user_table;
        $this->full_user_set = $full_user_set;
    }

    public function set_competency_id(?int $competency_id) {
        $this->temp_user_table->set_competency_id_value($competency_id);
    }

    public function get_competency_id(): ?int {
        return $this->temp_user_table->get_competency_id_value();
    }

    /**
     * Archive achievements of users no longer assigned
     * @param int $competency_id
     * @param int $aggregation_time
     */
    public function archive_non_assigned_achievements(int $competency_id, int $aggregation_time) {
        // We do not use the assignments in learn-only so we can skip this
        if (!advanced_feature::is_enabled('competency_assignment')) {
            return;
        }

        global $DB;

        if (is_null($this->temp_user_table)) {
            return;
        }

        // We rely on the competency being set in the table
        if ($this->temp_user_table->get_competency_id_value() != $competency_id) {
            throw new \coding_exception('Competency mismatch detected');
        }

        $temp_table_name = $this->temp_user_table->get_table_name();
        $temp_user_id_column = $this->temp_user_table->get_user_id_column();
        [$temp_wh, $temp_wh_params] = $this->temp_user_table->get_filter_sql_with_params('', false, null);
        if (!empty($temp_wh)) {
            $temp_wh = ' WHERE ' . $temp_wh;
        }

        // If this is a full list - archive all users not assigned
        if ($this->full_user_set) {
            $sql = "
                UPDATE {totara_competency_achievement}
                SET status = :newstatus, time_status = :timestatus
                WHERE competency_id = :competencyid
                    AND status = :currentstatus
                    AND user_id NOT IN (
                        SELECT {$temp_user_id_column}
                        FROM {" . $temp_table_name . "}
                            {$temp_wh}
                    )
            ";
            $params = array_merge(
                [
                    'competencyid' => $competency_id,
                    'newstatus' => competency_achievement::ARCHIVED_ASSIGNMENT,
                    'currentstatus' => competency_achievement::ACTIVE_ASSIGNMENT,
                    'timestatus' => $aggregation_time,
                ],
                $temp_wh_params
            );

            $DB->execute($sql, $params);
        }

        // We also need to always archive competency_achievements linked to assignments that are no longer active / available
        $sql = "
            UPDATE {totara_competency_achievement}
            SET status = :newstatus, time_status = :timestatus
            WHERE competency_id = :competencyid
                AND status = :currentstatus
                AND user_id IN (
                    SELECT {$temp_user_id_column}
                    FROM {" . $temp_table_name . "}
                        {$temp_wh}
                )
                AND assignment_id NOT IN (
                    SELECT assignment_id
                    FROM {totara_competency_assignment_users}
                    WHERE competency_id = :competencyid2
                )
        ";

        $params = array_merge(
            [
                'competencyid' => $competency_id,
                'competencyid2' => $competency_id,
                'newstatus' => competency_achievement::ARCHIVED_ASSIGNMENT,
                'currentstatus' => competency_achievement::ACTIVE_ASSIGNMENT,
                'timestatus' => $aggregation_time,
            ],
            $temp_wh_params
        );

        $DB->execute($sql, $params);
    }

    /**
     * Get users to consider for reaggregation
     *
     * @param int $competency_id
     * @return collection
     */
    public function get_users_to_reaggregate(int $competency_id) {
        // We rely on the competency being set in the table
        if ($this->temp_user_table->get_competency_id_value() != $competency_id) {
            throw new \coding_exception('Competency mismatch detected');
        }

        if (!advanced_feature::is_enabled('competency_assignment')) {
            return $this->get_users_to_reaggregate_legacy($competency_id);
        }

        // Find assignments of all users that were marked as having changes
        $subquery = builder::table($this->temp_user_table->get_table_name())
            ->select($this->temp_user_table->get_user_id_column())
            ->where_raw(...$this->temp_user_table->get_filter_sql_with_params("", false, 1));

        // First we get all user assignments for the competency where we have users
        // in the queue table who are marked as having changes
        $assignment_users = competency_assignment_user::repository()
            ->select(['id', 'user_id', 'assignment_id'])
            ->where('competency_id', $competency_id)
            ->where('user_id', 'in', $subquery)
            ->with('assignment')
            ->get();

        // First we get all user assignments for the competency where we have users
        // in the queue table who are marked as having changes

        $assignment_users_builder = builder::table(competency_assignment_user::TABLE)
            ->select('id')
            ->where('competency_id', $competency_id)
            ->where('user_id', 'in', $subquery)
            ->where_field('assignment_id', 'ach.assignment_id')
            ->where_field('user_id', 'ach.user_id');

        // Now for all of the assignments / user combinations query
        // the active achievements. This reduces the number of queries
        // down to only two and avoids the N+1 problem here
        $achievements = competency_achievement::repository()
            ->as('ach')
            ->where_exists($assignment_users_builder)
            ->where('status', competency_achievement::ACTIVE_ASSIGNMENT)
            ->get();

        $achievements_keyed = [];
        // Key for faster search
        foreach ($achievements as $achievement) {
            $achievements_keyed[$achievement->user_id.$achievement->assignment_id] = $achievement;
        }

        $result = new collection();
        // Now combine the two results returning, mapping the achievement to the assignment user record
        foreach ($assignment_users as $assignment_user) {
            $achievement = $achievements_keyed[$assignment_user->user_id.$assignment_user->assignment_id] ?? null;

            $result->append((object) [
                'user_id' => $assignment_user->user_id,
                'assignment' => $assignment_user->assignment,
                'achievement' => $achievement
            ]);
        }

        return $result;
    }

    /**
     * Get users to consider for reaggregation. We load all users who can potentially
     * receive a value due the competency being in a learning plan or completed a course linked to a competency
     *
     * @param int $competency_id
     * @return collection
     */
    private function get_users_to_reaggregate_legacy(int $competency_id) {
        global $DB, $CFG;

        [$q_cond, $q_params] = $this->temp_user_table->get_filter_sql_with_params("q", false, 1);

        $table_name = $this->temp_user_table->get_table_name();
        $user_id_column = $this->temp_user_table->get_user_id_column();
        $competency_id_column = $this->temp_user_table->get_competency_id_column();

        $exists_sql = [];
        if ($CFG->enablecompletion) {
            $exists_sql[] = "
                EXISTS (
                    -- with completion records in courses linked to competencies (or children)
                    SELECT coc.id
                    FROM {comp_criteria} coc
                    JOIN {course_completions} cc ON cc.course = coc.iteminstance
                    JOIN {comp} c ON coc.competencyid = c.id
                    WHERE coc.itemtype = 'coursecompletion'
                        AND cc.timecompleted > 0
                        AND (c.id = q.{$competency_id_column} OR c.parentid = q.{$competency_id_column})
                        AND cc.userid = q.{$user_id_column}
                )
            ";
        }

        // Only consider learning plans if its enabled
        if (advanced_feature::is_enabled('learningplans')) {
            $exists_sql[] = "
                EXISTS (
                    -- with competencies (or children) assigned to learning plans
                    SELECT pcv.id
                    FROM {dp_plan_competency_value} pcv
                    JOIN {comp} c ON pcv.competency_id = c.id
                    WHERE (c.id = q.{$competency_id_column} OR c.parentid = q.{$competency_id_column})
                         AND pcv.user_id = q.{$user_id_column}
                )
            ";
        }

        // Nothing to query for so return empty handed
        if (empty($exists_sql)) {
            return new collection();
        }

        $exists_sql = implode(" OR ", $exists_sql);

        // Find assignments of all users that were marked as having changes
        $sql = "
            SELECT q.{$user_id_column} as user_id, tca.id, tca.minproficiencyid
            FROM {{$table_name}} q
            LEFT JOIN {totara_competency_assignments} tca
                ON q.{$user_id_column} = tca.user_group_id
                    AND q.{$competency_id_column} = tca.competency_id
                    AND tca.type = :assignment_type
                    AND tca.user_group_type = :user_group_type
            WHERE {$q_cond}
                AND (
                    {$exists_sql}
                )
        ";

        $params = [
            'assignment_type' => assignment::TYPE_LEGACY,
            'user_group_type' => user_groups::USER,
        ];

        $params = array_merge($params, $q_params);

        $assignments = collection::new($DB->get_records_sql($sql, $params));

        // Now for all of the assignments / user combinations query
        // the active achievements. This reduces the number of queries
        // down to only two and avoids the N+1 problem here
        $achievements = competency_achievement::repository()
            ->where('competency_id', $competency_id)
            ->where('user_id', 'in', $assignments->pluck('user_id'))
            ->where('status', competency_achievement::ACTIVE_ASSIGNMENT)
            ->get();

        // Now combine the two results returning, mapping the achievement to the assignment user record
        return $assignments->map(function (stdClass $assignment) use ($achievements) {
            $achievement = $achievements->find('user_id', $assignment->user_id);

            return (object) [
                'user_id' => $assignment->user_id,
                'assignment' => $assignment,
                'achievement' => $achievement
            ];
        });
    }

    /**
     * Mark users who don't yet have a achievement record for a competency in any assignment
     *
     * @param int $competency_id
     */
    public function mark_newly_assigned_users(int $competency_id) {
        global $DB;

        if ($competency_id != $this->get_competency_id()) {
            throw new \coding_exception('Competency id mismatch, different competency set in the table instance');
        }

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
                'competency_id' => $competency_id,
                'status' => competency_achievement::ACTIVE_ASSIGNMENT,
            ],
            $set_haschanged_params,
            $temp_wh_params
        );

        $sql = "
            UPDATE {{$temp_table_name}}
                SET {$set_haschanged_sql}
            WHERE {$user_id_column} IN (
                SELECT tcau.user_id
                    FROM {totara_competency_assignment_users} tcau
                    LEFT JOIN {totara_competency_achievement} tca
                        ON tcau.assignment_id = tca.assignment_id
                            AND tcau.user_id = tca.user_id
                            AND tca.status = :status
                    WHERE tca.id IS NULL
                        AND tcau.competency_id = :competency_id
            )
            {$temp_wh}
        ";

        $DB->execute($sql, $params);
    }

}
