<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

namespace core_course\local\archive_progress_helper;

use context_helper;
use context_program;

/**
 * This is the data access layer used in resetting enrolled user course progress.
 */
class data_repository {

    /**
     * Check if the user has progress on the specified course.
     *
     * @param int $course_id
     * @param int $user_id
     * @return bool
     */
    public static function user_has_progress(int $course_id, int $user_id): bool {
        global $DB;
        $status = array(
            COMPLETION_STATUS_INPROGRESS,
            COMPLETION_STATUS_COMPLETE,
            COMPLETION_STATUS_COMPLETEVIARPL,
        );

        list($status_sql, $params) = $DB->get_in_or_equal($status, SQL_PARAMS_NAMED, 'status');
        $sql = "SELECT COUNT(DISTINCT cc.userid) as total
                  FROM {course_completions} cc
                 WHERE cc.course = :courseid
                     AND cc.userid = :userid
                   AND cc.status {$status_sql}";
        $params['courseid'] = $course_id;
        $params['userid'] = $user_id;
        $user_count = $DB->get_field_sql($sql, $params);

        return (int)$user_count > 0;
    }

    /**
     * Get number of users that have completed the course.
     *
     * @return int
     */
    public static function get_course_completed_users_count(int $course_id): int {
        global $DB;
        $status = array(
            COMPLETION_STATUS_COMPLETE,
            COMPLETION_STATUS_COMPLETEVIARPL
        );

        list($status_sql, $params) = $DB->get_in_or_equal($status, SQL_PARAMS_NAMED, 'status');
        $sql = "SELECT COUNT(DISTINCT cc.userid) as total
                  FROM {course_completions} cc
                 WHERE cc.course = :courseid
                   AND cc.status {$status_sql}";
        $params['courseid'] = $course_id;

        return $DB->get_field_sql($sql, $params);
    }

    /**
     * Get ids of users that have completions in the course.
     *
     * @return array
     */
    public static function get_course_completed_users(int $course_id): array {
        global $DB;

        $status = array(COMPLETION_STATUS_COMPLETE, COMPLETION_STATUS_COMPLETEVIARPL);

        list($statussql, $params) = $DB->get_in_or_equal($status, SQL_PARAMS_NAMED, 'status');
        $sql = "SELECT DISTINCT cc.userid
                  FROM {course_completions} cc
                 WHERE cc.course = :courseid
                   AND cc.status {$statussql}";
        $params['courseid'] = $course_id;
        $userids = $DB->get_records_sql_menu($sql, $params);

        return array_keys($userids);
    }

    /**
     * Get programs and certifications linked to a course
     *
     * @param int $course_id
     * @return array[]
     */
    public static function get_linked_programs_and_certifications_names(int $course_id): array {
        global $DB;

        $ctxfields = context_helper::get_preload_record_columns_sql('ctx');
        $sql = "SELECT DISTINCT p.id, p.fullname, p.certifid, {$ctxfields}
                  FROM {prog_courseset_course} pcc
                  JOIN {prog_courseset} pc ON pcc.coursesetid = pc.id
                  JOIN {prog} p ON pc.programid = p.id
                  JOIN {context} ctx ON ctx.instanceid = p.id AND ctx.contextlevel = :ctxlevel
                 WHERE pcc.courseid = :courseid";
        $params = [
            'courseid' => $course_id,
            'ctxlevel' => CONTEXT_PROGRAM
        ];

        return $DB->get_records_sql($sql, $params);
    }
}