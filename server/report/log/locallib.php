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
 * This file contains functions used by the log reports
 *
 * This files lists the functions that are used during the log report generation.
 *
 * @package    report_log
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if (!defined('REPORT_LOG_MAX_DISPLAY')) {
    define('REPORT_LOG_MAX_DISPLAY', 150); // days
}

require_once(__DIR__.'/lib.php');

/**
 * This function is used to generate and display the log activity graph
 *
 * @global stdClass $CFG
 * @param  stdClass $course course instance
 * @param  int|stdClass    $user id/object of the user whose logs are needed
 * @param  string $typeormode type of logs graph needed (usercourse.png/userday.png) or the mode (today, all).
 * @param  int $date timestamp in GMT (seconds since epoch)
 * @param  string $logreader Log reader.
 * @return void
 */
function report_log_print_graph($course, $user, $typeormode, $date=0, $logreader='') {
    global $CFG, $OUTPUT;

    if (!is_object($user)) {
        $user = core_user::get_user($user);
    }

    $logmanager = get_log_manager();
    $readers = $logmanager->get_readers();

    if (empty($logreader)) {
        $reader = reset($readers);
    } else {
        $reader = $readers[$logreader];
    }
    // If reader is not a sql_internal_table_reader and not legacy store then don't show graph.
    if (!($reader instanceof \core\log\sql_internal_table_reader) && !($reader instanceof logstore_legacy\log\store)) {
        return array();
    }
    $coursecontext = context_course::instance($course->id);

    $a = new stdClass();
    $a->coursename = format_string($course->shortname, true, array('context' => $coursecontext));
    $a->username = fullname($user, true);

    if ($typeormode == 'today' || $typeormode == 'userday.png') {
        $logs = report_log_usertoday_data($course, $user, $date, $logreader);
        $title = get_string("hitsoncoursetoday", "", $a);
    } else if ($typeormode == 'all' || $typeormode == 'usercourse.png') {
        $logs = report_log_userall_data($course, $user, $logreader);
        $title = get_string("hitsoncourse", "", $a);
    }

    if (!empty($CFG->preferlinegraphs)) {
        $chart = new \core\chart_line();
    } else {
        $chart = new \core\chart_bar();
    }

    $series = new \core\chart_series(get_string("hits"), $logs['series']);
    $chart->add_series($series);
    $chart->set_title($title);
    $chart->set_labels($logs['labels']);
    $yaxis = $chart->get_yaxis(0, true);
    $yaxis->set_label(get_string("hits"));
    $yaxis->set_stepsize(max(1, round(max($logs['series']) / 10)));

    echo $OUTPUT->render($chart);
}

/**
 * Select all log records for a given course and user
 *
 * @param int $userid The id of the user as found in the 'user' table.
 * @param int $courseid The id of the course as found in the 'course' table.
 * @param string $coursestart unix timestamp representing course start date and time.
 * @param string $logreader log reader to use.
 * @return array
 */
function report_log_usercourse($userid, $courseid, $coursestart, $logreader = '') {
    global $DB;

    $logmanager = get_log_manager();
    $readers = $logmanager->get_readers();
    if (empty($logreader)) {
        $reader = reset($readers);
    } else {
        $reader = $readers[$logreader];
    }

    // If reader is not a sql_internal_table_reader and not legacy store then return.
    if (!($reader instanceof \core\log\sql_internal_table_reader) && !($reader instanceof logstore_legacy\log\store)) {
        return array();
    }

    $coursestart = (int)$coursestart; // Note: unfortunately pg complains if you use name parameter or column alias in GROUP BY.
    if ($reader instanceof logstore_legacy\log\store) {
        $logtable = 'log';
        $timefield = 'time';
        $coursefield = 'course';
        // Anonymous actions are never logged in legacy log.
        $nonanonymous = '';
    } else {
        $logtable = $reader->get_internal_log_table_name();
        $timefield = 'timecreated';
        $coursefield = 'courseid';
        $nonanonymous = 'AND anonymous = 0';
    }

    $params = array();
    $courseselect = '';
    if ($courseid) {
        $courseselect = "AND $coursefield = :courseid";
        $params['courseid'] = $courseid;
    }
    $params['userid'] = $userid;
    return $DB->get_records_sql("SELECT FLOOR(($timefield - $coursestart)/" . DAYSECS . ") AS day, COUNT(*) AS num
                                   FROM {" . $logtable . "}
                                  WHERE userid = :userid
                                        AND $timefield > $coursestart $courseselect $nonanonymous
                               GROUP BY FLOOR(($timefield - $coursestart)/" . DAYSECS .")", $params);
}

/**
 * Select all log records for a given course, user, and day
 *
 * @param int $userid The id of the user as found in the 'user' table.
 * @param int $courseid The id of the course as found in the 'course' table.
 * @param string $daystart unix timestamp of the start of the day for which the logs needs to be retrived
 * @param string $logreader log reader to use.
 * @return array
 */
function report_log_userday($userid, $courseid, $daystart, $logreader = '') {
    global $DB;
    $logmanager = get_log_manager();
    $readers = $logmanager->get_readers();
    if (empty($logreader)) {
        $reader = reset($readers);
    } else {
        $reader = $readers[$logreader];
    }

    // If reader is not a sql_internal_table_reader and not legacy store then return.
    if (!($reader instanceof \core\log\sql_internal_table_reader) && !($reader instanceof logstore_legacy\log\store)) {
        return array();
    }

    $daystart = (int)$daystart; // Note: unfortunately pg complains if you use name parameter or column alias in GROUP BY.

    if ($reader instanceof logstore_legacy\log\store) {
        $logtable = 'log';
        $timefield = 'time';
        $coursefield = 'course';
        // Anonymous actions are never logged in legacy log.
        $nonanonymous = '';
    } else {
        $logtable = $reader->get_internal_log_table_name();
        $timefield = 'timecreated';
        $coursefield = 'courseid';
        $nonanonymous = 'AND anonymous = 0';
    }
    $params = array('userid' => $userid);

    $courseselect = '';
    if ($courseid) {
        $courseselect = "AND $coursefield = :courseid";
        $params['courseid'] = $courseid;
    }
    return $DB->get_records_sql("SELECT FLOOR(($timefield - $daystart)/" . HOURSECS . ") AS hour, COUNT(*) AS num
                                   FROM {" . $logtable . "}
                                  WHERE userid = :userid
                                        AND $timefield > $daystart $courseselect $nonanonymous
                               GROUP BY FLOOR(($timefield - $daystart)/" . HOURSECS . ") ", $params);
}

/**
 * Fetch logs since the start of the courses and structure in series and labels to be sent to Chart API.
 *
 * @param stdClass $course the course object
 * @param stdClass $user user object
 * @param string $logreader the log reader where the logs are.
 * @return array structured array to be sent to chart API, split in two indexes (series and labels).
 */
function report_log_userall_data($course, $user, $logreader) {
    global $CFG;
    $site = get_site();
    $timenow = time();
    $logs = [];
    if ($course->id == $site->id) {
        $courseselect = 0;
    } else {
        $courseselect = $course->id;
    }

    $maxseconds = REPORT_LOG_MAX_DISPLAY * 3600 * 24;  // Seconds.
    if ($timenow - $course->startdate > $maxseconds) {
        $course->startdate = $timenow - $maxseconds;
    }

    if (!empty($CFG->loglifetime)) {
        $maxseconds = $CFG->loglifetime * 3600 * 24;  // Seconds.
        if ($timenow - $course->startdate > $maxseconds) {
            $course->startdate = $timenow - $maxseconds;
        }
    }

    $timestart = $coursestart = usergetmidnight($course->startdate);

    $i = 0;
    $logs['series'][$i] = 0;
    $logs['labels'][$i] = 0;
    while ($timestart < $timenow) {
        $timefinish = $timestart + 86400;
        $logs['labels'][$i] = userdate($timestart, "%a %d %b");
        $logs['series'][$i] = 0;
        $i++;
        $timestart = $timefinish;
    }
    $rawlogs = report_log_usercourse($user->id, $courseselect, $coursestart, $logreader);

    foreach ($rawlogs as $rawlog) {
        if (isset($logs['labels'][$rawlog->day])) {
            $logs['series'][$rawlog->day] = $rawlog->num;
        }
    }

    return $logs;
}

/**
 * Fetch logs of the current day and structure in series and labels to be sent to Chart API.
 *
 * @param stdClass $course the course object
 * @param stdClass $user user object
 * @param int $date A time of a day (in GMT).
 * @param string $logreader the log reader where the logs are.
 * @return array $logs structured array to be sent to chart API, split in two indexes (series and labels).
 */
function report_log_usertoday_data($course, $user, $date, $logreader) {
    $site = get_site();
    $logs = [];

    if ($course->id == $site->id) {
        $courseselect = 0;
    } else {
        $courseselect = $course->id;
    }

    if ($date) {
        $daystart = usergetmidnight($date);
    } else {
        $daystart = usergetmidnight(time());
    }

    for ($i = 0; $i <= 23; $i++) {
        $hour = $daystart + $i * 3600;
        $logs['series'][$i] = 0;
        $logs['labels'][$i] = userdate($hour, "%H:00");
    }

    $rawlogs = report_log_userday($user->id, $courseselect, $daystart, $logreader);

    foreach ($rawlogs as $rawlog) {
        if (isset($logs['labels'][$rawlog->hour])) {
            $logs['series'][$rawlog->hour] = $rawlog->num;
        }
    }

    return $logs;
}
