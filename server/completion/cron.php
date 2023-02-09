<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Code used by scheduled tasks for reviewing and aggregating course completion criteria.
 *
 * @package core_completion
 * @category completion
 * @copyright 2009 Catalyst IT Ltd
 * @author Aaron Barnes <aaronb@catalyst.net.nz>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir.'/completionlib.php');

/**
 * Mark users as started if the config option is set
 *
 * @return void
 */
function completion_cron_mark_started() {
    global $CFG, $DB;

    if (debugging()) {
        mtrace('Marking users as started');
    }

    if (!empty($CFG->gradebookroles)) {
        $roles = ' AND ra.roleid IN ('.$CFG->gradebookroles.')';
    } else {
        // This causes it to default to everyone (if there is no student role)
        $roles = '';
    }

    /**
     * A quick explaination of this horrible looking query
     *
     * It's purpose is to locate all the active participants
     * of a course with course completion enabled.
     *
     * We also only want the users with no course_completions
     * record as this functions job is to create the missing
     * ones :)
     *
     * We want to record the user's enrolment start time for the
     * course. This gets tricky because there can be multiple
     * enrolment plugins active in a course, hence the possibility
     * of multiple records for each couse/user in the results
     */
    $sql = "
        SELECT
            c.id AS course,
            u.id AS userid,
            crc.id AS completionid,
            ue.timestart AS timeenrolled,
            ue.timecreated
        FROM
            {user} u
        INNER JOIN
            {user_enrolments} ue
         ON ue.userid = u.id
        INNER JOIN
            {enrol} e
         ON e.id = ue.enrolid
        INNER JOIN
            {course} c
         ON c.id = e.courseid
        INNER JOIN
            {role_assignments} ra
         ON ra.userid = u.id
        LEFT JOIN
            {course_completions} crc
         ON crc.course = c.id
        AND crc.userid = u.id
        WHERE
            c.enablecompletion = 1
        AND crc.timeenrolled IS NULL
        AND ue.status = 0
        AND e.status = 0
        AND u.deleted = 0
        AND ue.timestart <= ?
        AND (ue.timeend > ? OR ue.timeend = 0)
            $roles
        ORDER BY
            course,
            userid
    ";

    $now = time();
    $rs = $DB->get_recordset_sql($sql, array($now, $now, $now, $now));

    // Check if result is empty
    if (!$rs->valid()) {
        $rs->close(); // Not going to iterate (but exit), close rs
        return;
    }

    /**
     * An explaination of the following loop
     *
     * We are essentially doing a group by in the code here (as I can't find
     * a decent way of doing it in the sql).
     *
     * Since there can be multiple enrolment plugins for each course, we can have
     * multiple rows for each particpant in the query result. This isn't really
     * a problem until you combine it with the fact that the enrolment plugins
     * can save the enrol start time in either timestart or timeenrolled.
     *
     * The purpose of this loop is to find the earliest enrolment start time for
     * each participant in each course.
     */
    $prev = null;
    while ($rs->valid() || $prev) {

        $current = $rs->current();

        if (!isset($current->course)) {
            $current = false;
        }
        else {
            // Not all enrol plugins fill out timestart correctly, so use whichever
            // is non-zero
            $current->timeenrolled = max($current->timecreated, $current->timeenrolled);
        }

        // If we are at the last record,
        // or we aren't at the first and the record is for a diff user/course
        if ($prev &&
            (!$rs->valid() ||
            ($current->course != $prev->course || $current->userid != $prev->userid))) {

            $completion = new completion_completion();
            $completion->userid = $prev->userid;
            $completion->course = $prev->course;
            $completion->timeenrolled = (string) $prev->timeenrolled;
            $completion->timestarted = 0;
            $completion->reaggregate = time();

            if ($prev->completionid) {
                $completion->id = $prev->completionid;
            }

            $completion->mark_enrolled();

            if (debugging()) {
                mtrace('Marked started user '.$prev->userid.' in course '.$prev->course);
            }
        }
        // Else, if this record is for the same user/course
        elseif ($prev && $current) {
            // Use oldest timeenrolled
            $current->timeenrolled = min($current->timeenrolled, $prev->timeenrolled);
        }

        // Move current record to previous
        $prev = $current;

        // Move to next record
        $rs->next();
    }

    $rs->close();
}

/**
 * Run installed criteria's data aggregation methods
 *
 * Loop through each installed criteria and run the
 * cron() method if it exists
 *
 * @return void
 */
function completion_cron_criteria() {

    // Process each criteria type
    global $CFG, $COMPLETION_CRITERIA_TYPES;

    foreach ($COMPLETION_CRITERIA_TYPES as $type) {

        $object = 'completion_criteria_'.$type;
        require_once $CFG->dirroot.'/completion/criteria/'.$object.'.php';

        $class = new $object();

        // Run the criteria type's cron method, if it has one
        if (method_exists($class, 'cron')) {

            if (debugging()) {
                mtrace('Running '.$object.'->cron()');
            }
            $class->cron();
        }
    }
}

/**
 * Aggregate each user's criteria completions
 *
 * @param int userid
 */
function completion_cron_completions(int $userid = 0) {
    global $DB;

    // Suppress output during tests.
    $quiet = !debugging() || PHPUNIT_TEST || defined('BEHAT_SITE_RUNNING');

    if (!$quiet) {
        mtrace('Aggregating completions');
    }

    // Wait one sec to prevent timestamp overlap with "reaggregate"
    // being set in completion_cron_criteria()
    sleep(1);

    // Save time started
    $timestarted = time();

    // Grab all criteria and their associated criteria completions
    $sql = '
        SELECT
            crc.*, c.enablecompletion
        FROM
            {course_completions} crc
        INNER JOIN
            {course} c
         ON crc.course = c.id
        WHERE
            c.enablecompletion = 1
        AND crc.timecompleted IS NULL
        AND crc.reaggregate > 0
        AND crc.reaggregate < :timestarted
    ';
    $params = ['timestarted' => $timestarted];

    if ($userid) {
        $sql .= "AND crc.userid = :userid";
        $params['userid'] = $userid;
    }

    $limit = $DB->get_max_in_params();
    $sql .= " ORDER BY crc.course";
    $rs = $DB->get_recordset_sql($sql, $params, 0, $limit);

    $course_info_cache = [];
    $completion_ids = [];

    if (!$quiet) {
        mtrace('... Recalculating criteria completion', '');
    }

    $cnt = 0;

    // Grab records for current user/course
    foreach ($rs as $record) {
        if (!$quiet && $cnt === 1000) {
            mtrace('.', '');
            $cnt = 0;
        }
        $cnt += 1;

        $completion_ids[] = $record->id;

        if (!isset($course_info_cache[$record->course])) {
            $course_info_cache[$record->course] = (object)[
                'id' => $record->course,
                'enablecompletion' => $record->enablecompletion,
            ];
            fetch_course_info($course_info_cache[$record->course]);
        }
        $cur_course_info = $course_info_cache[$record->course];

        if (!$cur_course_info->enabled) {
            unset($completion, $record);
            continue;
        }

        // Recalculate course's criteria
        completion_handle_criteria_recalc($record->course, $record->userid, $cur_course_info);

        unset($completion, $record);
    }

    $rs->close();

    if (empty($completion_ids)) {
        if (debugging() && !PHPUNIT_TEST) {
            mtrace('');
            mtrace('Finished aggregating completions');
        }
        return;
    }

    $cnt = $sub_total = 0;
    $total = count($completion_ids);

    if (!$quiet) {
        mtrace('');
        mtrace('... Aggregating');
    }

    // Reload the data from the db, because the previous function might have changed it.
    [$in_sql, $in_params] = $DB->get_in_or_equal($completion_ids, SQL_PARAMS_NAMED);

    $sql = "
        SELECT *
        FROM
            {course_completions}
         WHERE id {$in_sql}
         ORDER BY course
    ";

    $rs = $DB->get_recordset_sql($sql, $in_params);
    foreach ($rs as $record) {
        // Log progress every 1000
        if (!$quiet && $cnt === 1000) {
            $sub_total += $cnt;
            mtrace("   ... $sub_total of $total");
            $cnt = 0;
        }
        $cnt += 1;

        // No need to re-fetch course info. Added to the cache in the previous loop
        if (!isset($course_info_cache[$record->course])) {
            // Something went terribly wrong between execution of the 2 loops
            if (!$quiet) {
                mtrace('Unexpected caching error detected while aggregating completions');
            }
            return;
        }
        $cur_course_info = $course_info_cache[$record->course];

        $completion = new completion_completion((array) $record, false);

        // Aggregate the criteria and complete if necessary
        $completion->aggregate($cur_course_info);

        unset($completion, $record);
    }

    $rs->close();

    if (!empty($completion_ids)) {
        $resetsql = "
            UPDATE {course_completions}
               SET reaggregate = 0
             WHERE id {$in_sql}";
        $DB->execute($resetsql, $in_params);
    }

    if (!$quiet) {
        mtrace('Finished aggregating completions');
        $mem_peak = round(memory_get_peak_usage() / 1024 / 1024);
        mtrace("... peaked memory usage:  {$mem_peak}MB");
    }
}

/**
 * @param stdClass $course
 */
function fetch_course_info(stdClass $course): void {
    $course->info = new completion_info($course);
    $course->enabled = $course->info->is_enabled();
    if ($course->enabled) {
        $course->agg = $course->info->get_aggregation_method();

        $course->criteria = $course->info->get_criteria();
        foreach ($course->criteria as $criterion) {
            $criterion->agg_method = $course->info->get_aggregation_method($criterion->criteriatype);
        }
    }
    $course->progressinfobase = $course->info->get_progressinfo()->prepare_to_cache();
}
