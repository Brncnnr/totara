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

namespace totara_core\local\visibility\course;

defined('MOODLE_INTERNAL') || die();

use core\dml\sql;

/**
 * Methods common to all forms of Course visibility.
 *
 * @internal
 */
trait common {

    /**
     * Returns an instance of the course map.
     *
     * @return map
     */
    public function map(): \totara_core\local\visibility\map {
        return new map();
    }

    /**
     * Returns the context level relevant to this item.
     * @return int
     */
    protected function get_context_level(): int {
        return CONTEXT_COURSE;
    }

    /**
     * Returns an array containing category id's and a count of the courses the given user can see within it.
     *
     * @param int $userid
     * @return int[] The key is the category id, the value is the count of visible items.
     */
    public function get_visible_counts_for_all_categories(int $userid): array {
        global $DB;
        $sql = (new sql('SELECT c.category, COUNT(c.id) as coursecount FROM {course} c'))
            ->append($this->sql_where_visible($userid, 'c'), ' WHERE ')
            ->append('GROUP BY c.category');
        $results = $DB->get_records_sql_menu($sql);
        unset($results[0]);
        return $results;
    }

    /**
     * Returns an array of courses visible to the given user within the given category.
     *
     * @param int $categoryid
     * @param int $userid
     * @param array $fields The fields to fetch for the item.
     * @return array The resulting items.
     */
    public function get_visible_in_category(int $categoryid, int $userid, array $fields = ['*']): array {
        global $DB;
        $paramname = \moodle_database::get_unique_param('categoryid');
        $params[$paramname] = $categoryid;
        $fields = array_map(
            function ($field) {
                return 'c.' . $field;
            },
            $fields
        );
        $fields = join(', ', $fields);

        $ctxfields = \context_helper::get_preload_record_columns_sql('ctx');
        $sql = (new sql(
            "SELECT {$fields}, {$ctxfields}
                      FROM {course} c
                 LEFT JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = 50
                     WHERE c.category = :{$paramname}", $params))
            ->append($this->sql_where_visible($userid, 'c'), ' AND ')
            ->append('ORDER BY c.sortorder ASC');

        $results = $DB->get_records_sql($sql);
        foreach ($results as &$course) {
            \context_helper::preload_from_record($course);
        }
        return $results;
    }

    /**
     * Returns an SQL snippet the resolves whether the user has an assignment on course given its ID field.
     *
     * @param int $userid
     * @param string $field_id
     * @return sql
     */
    protected function sql_user_assignment(int $userid, string $field_id): sql {
        $param_user = \moodle_database::get_unique_param('user');
        $params = [
            $param_user => $userid,
        ];
        $sql = "EXISTS (
                    SELECT 1
                      FROM {user_enrolments} ua_ue
                      JOIN {enrol} ua_e ON ua_e.id = ua_ue.enrolid
                     WHERE ua_e.courseid = {$field_id}
                       AND ua_ue.userid = :{$param_user})";
        return new sql($sql, $params);
    }

    /**
     * Returns an SQL snippet that resolves whether the course is currently available or not.
     *
     * @param int $userid
     * @param string $course_tablealias
     * @return sql|null An array with two items, string:SQL, array:params OR null if there is no availability to calculate.
     */
    protected function get_availability_sql(int $userid, string $course_tablealias): sql {
        return new sql('');
    }

}