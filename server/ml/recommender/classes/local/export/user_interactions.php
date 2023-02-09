<?php
/**
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package ml_recommender
 */
namespace ml_recommender\local\export;

use ml_recommender\local\csv\writer;
use ml_recommender\local\environment;
use moodle_recordset;
use totara_engage\timeview\time_view;

/**
 * Export class for interaction table.
 */
class user_interactions extends export {

    public function get_name(): string {
        return 'user_interactions';
    }

    public function export(writer $writer): bool {
        // Column headings for csv file.
        $writer->add_headings([
            'user_id',
            'item_id',
            'rating',
            'timestamp',
        ]);
        
        $recordset = $this->get_export_recordset();

        $microlearning_time_view = time_view::LESS_THAN_FIVE;

        if (!$recordset->valid()) {
            return false;
        }

        foreach ($recordset as $interaction) {
            // Get the mapped item and user ids.
            $user_id = $interaction->user_id;

            // The timeview value will only be correct for, and pertinent to, engage_article.
            if ($interaction->component == 'engage_article' && $microlearning_time_view == $interaction->timeview) {
                $item_id = 'engage_microlearning' . $interaction->item_id;
            } else {
                $item_id = $interaction->component . $interaction->item_id;
            }
            $timestamp = $interaction->mytimestamp;

            // Normalise "rating" - more than 1 interaction is positive - implicit feedback.  See:
            // Collaborative prediction and ranking with non-random missing data (Benjamin M. Marlin, Richard S. Zemel)
            // https://media.netflix.com/en/company-blog/goodbye-stars-hello-thumbs
            $rating = 0;
            if ($interaction->myrating > 1) {
                $rating = 1;
            }

            // Create CSV record.
            $writer->add_data([
                $user_id,
                $item_id,
                $rating,
                $timestamp,
            ]);
        }
        $writer->close();
        $recordset->close();

        return true;
    }

    /**
     * Prepare and run SQL query to database to get interactions
     * @return moodle_recordset
     */
    private function get_export_recordset() {
        global $DB;

        // Get the minimum unix epoch timestamp for this export (after expressing weeks as seconds).
        $min_timestamp = time() - (environment::get_interactions_period() * 7 * 86400);

        $params_sql = [];
        // Tenant restrictions.
        $tenant_join_sql = '';
        if ($this->tenant) {
            $tenant_join_sql = 'INNER JOIN {cohort_members} cm ON (cm.cohortid = :cohort_id AND ri.user_id = cm.userid)';
            $params_sql['cohort_id'] = $this->tenant->cohortid;
        }

        $components = ['engage_article', 'totara_playlist', 'container_workspace'];
        [$components_in_sql, $components_params] = $DB->get_in_or_equal($components, SQL_PARAMS_NAMED);

        $sql = "
            SELECT ri.user_id, ri.item_id, mrc.component, 
                   MAX(ri.time_created) AS mytimestamp, SUM(ri.rating) AS myrating, tea.timeview
            FROM {ml_recommender_interactions} ri
            INNER JOIN {ml_recommender_components} mrc ON (mrc.id = ri.component_id)
            INNER JOIN {ml_recommender_interaction_types} mrit ON (mrit.id = ri.interaction_type_id)
            LEFT JOIN {engage_resource} ter on (ter.id = ri.item_id and mrc.component = 'engage_article')
            LEFT JOIN {engage_article} tea on (tea.id = ter.instanceid)
            $tenant_join_sql
            WHERE component $components_in_sql
              AND ri.time_created >= :mintimestamp
            GROUP BY user_id, item_id, component, timeview
        ";
        $params_sql['mintimestamp'] = $min_timestamp;


        if ($this->tenant) {
            $courselevel = CONTEXT_COURSE;
            $tenantid = $this->tenant->id;

            $ornotenant = '';
            if (empty($CFG->tenantsisolated)) {
                $ornotenant = 'OR c.tenantid IS NULL';
            }

            $tenant_join_sql = "
            INNER JOIN {context} c ON (
                c.contextlevel = $courselevel 
                AND e.courseid = c.instanceid 
                AND (c.tenantid = $tenantid $ornotenant))
            ";
        }
        // Interactions with self-enrol courses.
        $sql .= "
            UNION ALL
            SELECT ue.userid AS user_id,
                e.courseid AS item_id,
                cc.containertype AS component,
                ue.timecreated AS mytimestamp,
                2 AS myrating,
                null as timeview
            FROM {user_enrolments} ue
            INNER JOIN {enrol} e ON (ue.enrolid = e.id)
            INNER JOIN {course} cc ON e.courseid = cc.id 
            $tenant_join_sql
            WHERE e.enrol = 'self' AND e.status = " . ENROL_USER_ACTIVE .
            " AND cc.containertype = 'container_course'";

        // Set recordset cursor.
        $params = array_merge($params_sql, $components_params);

        return $DB->get_recordset_sql($sql, $params);
    }
}
