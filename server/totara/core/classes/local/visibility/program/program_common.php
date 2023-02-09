<?php
/*
 * This file is part of Totara LMS
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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\local\visibility\program;

use core\dml\sql;

defined('MOODLE_INTERNAL') || die();

/**
 * Methods common to all forms of Program and Certification visibility.
 *
 * @internal
 */
trait program_common {

    /**
     * Returns an array containing category id's and a count of the programs the given user can see within it.
     *
     * @param int $userid
     * @param bool $certification Set true when looking for certification visibility
     * @return int[] The key is the category id, the value is the count of visible items.
     */
    protected function program_common_get_visible_counts_for_all_categories(int $userid, bool $certification = false): array {
        global $DB;

        $certifidnull = $certification ? 'NOT NULL' : 'NULL';

        $sql = (new sql('SELECT p.category, COUNT(p.id) as programcount
                                FROM {prog} p
                               WHERE p.certifid IS ' . $certifidnull))
            ->append($this->sql_where_visible($userid, 'p'), ' AND ')
            ->append('GROUP BY p.category');
        $results = $DB->get_records_sql_menu($sql);
        return $results;
    }

    /**
     * Returns an array of programs visible to the given user within the given category.
     *
     * @param int $categoryid
     * @param int $userid
     * @param array $fields The fields to fetch for the item.
     * @param bool $certification Set true when looking for certifications
     * @return array The resulting items.
     */
    protected function program_common_get_visible_in_category(int $categoryid, int $userid, array $fields = ['*'], bool $certification = false): array {
        global $DB;

        $certifidnull = $certification ? 'NOT NULL' : 'NULL';

        $paramname = \moodle_database::get_unique_param('categoryid');
        $params[$paramname] = $categoryid;
        $fields = array_map(
            function ($field) {
                return 'p.' . $field;
            },
            $fields
        );
        $fields = join(', ', $fields);

        $ctxfields = \context_helper::get_preload_record_columns_sql('ctx');
        $sql = (new sql(
            "SELECT {$fields}, {$ctxfields}
                      FROM {prog} p
                 LEFT JOIN {context} ctx ON ctx.instanceid = p.id AND ctx.contextlevel = 45
                     WHERE p.category = :{$paramname} AND p.certifid IS {$certifidnull}", $params))
            ->append($this->sql_where_visible($userid, 'p'), ' AND ')
            ->append('ORDER BY p.sortorder ASC');

        $results = $DB->get_records_sql($sql);
        foreach ($results as &$program) {
            \context_helper::preload_from_record($program);
        }
        return $results;
    }

    /**
     * Returns an SQL snippet the resolves whether the user has an assignment on program given its ID field.
     *
     * @param int $userid
     * @param string $field_id
     * @return array An array with two items, string:SQL, array:params
     */
    protected function program_common_sql_user_assignment(int $userid, string $field_id): sql {
        $param_user = \moodle_database::get_unique_param('user');
        $params = [
            $param_user => $userid,
        ];
        $sql = "EXISTS (
                    SELECT 1
                      FROM {prog_user_assignment} pua
                     WHERE pua.programid = {$field_id}
                       AND pua.userid = :{$param_user})";
        return new sql($sql, $params);
    }
}
