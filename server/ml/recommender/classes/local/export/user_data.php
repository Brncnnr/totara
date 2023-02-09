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
use moodle_recordset;

/**
 * Export class for user data.
 */
class user_data extends export {
    /**
     * @return string
     */
    public function get_name(): string {
        return 'user_data';
    }

    /**
     * @param writer $writer
     * @return bool
     */
    public function export(writer $writer): bool {
        // Column headings for csv file.
        $writer->add_headings([
            'user_id',
            'lang',
            'city',
            'country',
            'interests',
            'asp_position',
            'positions',
            'organisations',
            'competencies_scale',
            'badges',
            'description'
        ]);

        $recordset = $this->get_export_recordset();
        if (!$recordset->valid()) {
            return false;
        }

        foreach ($recordset as $user) {
            // Swap out embedded double-quotes in text fields.
            $user->city = str_replace('"', "'", $user->city);
            $user->description = str_replace('"', "'", content_to_text($user->description, $user->descriptionformat));

            // Ensure we keep only the highest current proficiency per retrieved competency.
            $user->competencies = $this->get_highest_proficiencies($user->competencies, $user->proficiencies);

            // Remove any duplicated position or organisation ids.
            $user->positions = $this->get_unique_values($user->positions);
            $user->organisations = $this->get_unique_values($user->organisations);

            // Create CSV record.
            $writer->add_data([
                $user->user_id,
                $user->lang,
                $user->city,
                $user->country,
                $user->interests,
                $user->asp_position,
                $user->positions,
                $user->organisations,
                $user->competencies,
                $user->badges,
                $user->description,
            ]);
        }
        $writer->close();
        $recordset->close();

        return true;
    }

    /**
     * Prepare and run SQL query to database to get users and related profile data.
     *
     * @return moodle_recordset
     */
    private function get_export_recordset() {
        global $CFG, $DB;

        // Query parameter values.
        $params_sql = [
            'guest_id' => $CFG->siteguest,
            'active_assignment' => 0
        ];

        // Tenant restrictions.
        $tenant_join_sql = '';
        if ($this->tenant) {
            $tenant_join_sql = 'INNER JOIN {cohort_members} cm1 ON (cm1.cohortid = :cohort_id AND u.id = cm1.userid)';
            $params_sql['cohort_id'] = $this->tenant->cohortid;
        }

        $sql = "
            SELECT u.id as user_id,
                u.lang,
                u.city,
                u.country,
                tig.interests,
                tgag.asp_position,
                jag.positions,
                jag.organisations,
                jag.competencies,
                jag.proficiencies,
                big.badges,
                u.descriptionformat,
                u.description 
            FROM \"ttr_user\" u
            {$tenant_join_sql}
            LEFT JOIN (
                SELECT ti.itemid, 
                " . $DB->sql_group_concat('ti.id', '|') . " AS interests
                FROM \"ttr_tag_instance\" ti
                WHERE ti.component = 'core'
                AND ti.itemtype = 'user'
                    GROUP BY ti.itemid
                ) tig ON tig.itemid = u.id
            LEFT JOIN (
                SELECT tga.userid, 
                " . $DB->sql_group_concat('tga.positionid', '|') . " AS asp_position
                FROM  \"ttr_gap_aspirational\" tga
                GROUP BY tga.userid 
                ) tgag ON tgag.userid = u.id
            LEFT JOIN (
                SELECT jaco.userid, jaco.positions, jaco.organisations, jaco.competencies, jaco.proficiencies
                FROM (
                    SELECT
                        ja.userid,
                        " . $DB->sql_group_concat('ja.positionid', '|') . " AS positions,
                        " . $DB->sql_group_concat('ja.organisationid', '|') . " AS organisations,
                         comp.competencies,
                         comp.proficiencies
                    FROM \"ttr_job_assignment\" ja
                    LEFT JOIN (
                        SELECT tca.user_id,
                            " . $DB->sql_group_concat('tca.competency_id', '|') . " AS competencies,
                            " . $DB->sql_group_concat('csv.sortorder', '|') . " AS proficiencies
                        FROM \"ttr_totara_competency_achievement\" tca
                        JOIN \"ttr_comp_scale_values\" csv ON csv.id = tca.scale_value_id    
                        WHERE tca.status = :active_assignment
                        GROUP BY tca.user_id
                    ) comp ON comp.user_id = ja.userid
                    GROUP BY ja.userid, comp.competencies, comp.proficiencies
                ) jaco
                GROUP BY jaco.userid, jaco.positions, jaco.organisations, jaco.competencies, jaco.proficiencies
                ) jag ON jag.userid = u.id
            LEFT JOIN (
                SELECT bi.userid, 
                " . $DB->sql_group_concat('bi.badgeid', '|') . " AS badges
                FROM \"ttr_badge_issued\" bi
                GROUP BY bi.userid
                ) big ON big.userid = u.id
            WHERE
                u.deleted = 0
                AND u.suspended = 0
                AND u.id <> :guest_id
            ORDER BY u.id
        ";

        return $DB->get_recordset_sql($sql, $params_sql);
    }

    /**
     * A user may be assigned multiple times to a competency.  Each competency assignment may have a different
     * achieved proficiency level (defined by sort order).  Here we find the highest proficiency level for a
     * specific user on a specific competency.
     *
     * Format of returned string: {competency}:{proficiency|{competency}:{proficiency|...|{competency}:{proficiency}
     *
     * @param $competencies
     * @param $proficiencies
     * @return string
     */
    private function get_highest_proficiencies(?string $competencies, ?string $proficiencies):string {
        // Empty or null string.
        if ($competencies == null || $competencies == '') {
            return '';
        }

        // Split the pipe-separated values into arrays.
        $competencies = explode('|', $competencies);
        $proficiencies = explode('|', $proficiencies);

        // Keep only the highest proficiencies.
        $competencies_max = [];
        foreach ($competencies as $index => $competency) {
            // Empty proficiency level should be zero.
            if ($proficiencies[$index] == '' || $proficiencies[$index] == null) {
                $proficiencies[$index] = 0;
            }

            if (isset($competencies_max[$competency])) {
                if ($competencies_max[$competency] < $proficiencies[$index]) {
                    $competencies_max[$competency] = $proficiencies[$index];
                }
            } else {
                $competencies_max[$competency] = $proficiencies[$index];
            }
        }

        // Cast competency:proficiency pairs to pipe-delimited string.
        $highest_proficiency = [];
        foreach ($competencies_max as $competency => $proficiency) {
            $highest_proficiency[] = $competency . ':' . $proficiency;
        }

        return implode('|', $highest_proficiency);
    }

    /**
     * Users may have multiple assigned positions, multiple organisations and/or multiple competencies.  Here
     * we remove all duplicate ids and format the list as the recommender engine requires it to be.
     *
     * Take in a string containing either null, empty string, or a pipe-separated list of non-unique id values.
     *
     * Return either empty string (for null or empty string), or a pipe-separated list of unique id values.
     *
     * @param $list
     * @return string
     */
    private function get_unique_values(?string $list) {
        if ($list == null || $list == '') {
            return '';
        }

        $array = explode('|', $list);
        return implode('|', array_unique($array));
    }
}
